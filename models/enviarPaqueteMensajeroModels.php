<?php
require_once __DIR__ . '/conexionGlobal.php';

class EnvioMensajeroModel
{
    private PDO $conn;

    public function __construct()
    {
        $this->conn = conexionDB();
    }

    public function verificarGuia(string $numero_guia): bool
    {
        $sql = "SELECT COUNT(*) FROM paquetes WHERE numero_guia = :numero_guia";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([':numero_guia' => $numero_guia]);
        return (int) $stmt->fetchColumn() > 0;
    }

    public function obtenerOCrearClienteOperativo(int $usuarioId, array $usuarioSesion): int
    {
        $stmt = $this->conn->prepare("SELECT id FROM clientes WHERE usuario_id = :usuario_id LIMIT 1");
        $stmt->execute([':usuario_id' => $usuarioId]);
        $clienteId = $stmt->fetchColumn();

        if ($clienteId) {
            return (int) $clienteId;
        }

        $nombre = trim((string) (($usuarioSesion['user_name'] ?? '') . ' ' . ($usuarioSesion['user_lastname'] ?? '')));
        if ($nombre === '') {
            $nombre = 'Mensajero EcoBikeMess';
        }

        $telefono = trim((string) ($usuarioSesion['user_phone'] ?? ''));
        $correo = trim((string) ($usuarioSesion['user_email'] ?? ''));
        $direccion = trim((string) ($usuarioSesion['user_address'] ?? ''));

        $stmtInsert = $this->conn->prepare(
            "INSERT INTO clientes (
                usuario_id,
                nombre_emprendimiento,
                tipo_producto,
                instagram,
                direccion_principal,
                saldo_pendiente,
                limite_credito
            ) VALUES (
                :usuario_id,
                :nombre_emprendimiento,
                :tipo_producto,
                :instagram,
                :direccion_principal,
                0,
                0
            )"
        );

        $stmtInsert->execute([
            ':usuario_id' => $usuarioId,
            ':nombre_emprendimiento' => 'Operativo Mensajero - ' . $nombre,
            ':tipo_producto' => 'Envíos creados por mensajero',
            ':instagram' => $correo !== '' ? $correo : ($telefono !== '' ? $telefono : 'mensajero'),
            ':direccion_principal' => $direccion
        ]);

        return (int) $this->conn->lastInsertId();
    }

    public function registrarEnvio(array $datos): bool
    {
        if (empty($datos['cliente_id'])) {
            throw new Exception("No se identificó el cliente operativo del mensajero.");
        }

        $tipo_servicio = (!empty($datos['tiene_recaudo']) && (int) $datos['tiene_recaudo'] === 1)
            ? 'contraentrega'
            : 'entrega_simple';

        $sql = "INSERT INTO paquetes (
                    cliente_id,
                    creado_por,
                    numero_guia,
                    remitente_nombre,
                    remitente_telefono,
                    remitente_correo,
                    direccion_origen,
                    destinatario_nombre,
                    destinatario_telefono,
                    direccion_destino,
                    instrucciones_entrega,
                    descripcion_contenido,
                    dimensiones,
                    envio_destinatario,
                    tipo_servicio,
                    recaudo_esperado,
                    costo_envio,
                    estado,
                    fecha_creacion
                ) VALUES (
                    :cliente_id,
                    :creado_por,
                    :numero_guia,
                    :remitente_nombre,
                    :remitente_telefono,
                    :remitente_email,
                    :remitente_direccion,
                    :destinatario_nombre,
                    :destinatario_telefono,
                    :destinatario_direccion,
                    :instrucciones_entrega,
                    :descripcion_contenido,
                    :dimensiones,
                    :envio_destinatario,
                    :tipo_servicio,
                    :valor_recaudo,
                    :costo_total,
                    'pendiente',
                    NOW()
                )";

        try {
            $stmt = $this->conn->prepare($sql);
            return $stmt->execute([
                ':cliente_id' => $datos['cliente_id'],
                ':creado_por' => $datos['creado_por'],
                ':numero_guia' => $datos['numero_guia'],
                ':remitente_nombre' => $datos['remitente_nombre'],
                ':remitente_telefono' => $datos['remitente_telefono'],
                ':remitente_email' => $datos['remitente_email'],
                ':remitente_direccion' => $datos['remitente_direccion'],
                ':destinatario_nombre' => $datos['destinatario_nombre'],
                ':destinatario_telefono' => $datos['destinatario_telefono'],
                ':destinatario_direccion' => $datos['destinatario_direccion'],
                ':instrucciones_entrega' => $datos['instrucciones_entrega'],
                ':descripcion_contenido' => trim((string) ($datos['descripcion_contenido'] ?? '')),
                ':dimensiones' => $datos['dimensiones'] ?? null,
                ':envio_destinatario' => $datos['envio_destinatario'] ?? 'no',
                ':tipo_servicio' => $tipo_servicio,
                ':valor_recaudo' => $datos['valor_recaudo'],
                ':costo_total' => $datos['costo_total']
            ]);
        } catch (PDOException $e) {
            error_log("Error en EnvioMensajeroModel::registrarEnvio: " . $e->getMessage());
            throw new Exception("Error al guardar el envío del mensajero: " . $e->getMessage());
        }
    }
}
