<?php
require_once __DIR__ . '/conexionGlobal.php';

class EnvioModel {
    private $conn;

    public function __construct() {
        $this->conn = conexionDB();
        $this->ensureAdditionalColumns();
    }

    private function columnExists(string $table, string $column): bool
    {
        try {
            $stmt = $this->conn->prepare("SHOW COLUMNS FROM {$table} LIKE :column");
            $stmt->execute([':column' => $column]);
            return (bool) $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (Throwable $e) {
            return false;
        }
    }

    private function ensureAdditionalColumns(): void
    {
        $columns = [
            'envio_mismo_dia' => "ALTER TABLE paquetes ADD COLUMN envio_mismo_dia TINYINT(1) NOT NULL DEFAULT 0 AFTER dimensiones",
            'zona_periferica' => "ALTER TABLE paquetes ADD COLUMN zona_periferica TINYINT(1) NOT NULL DEFAULT 0 AFTER envio_mismo_dia",
            'recoger_cambios' => "ALTER TABLE paquetes ADD COLUMN recoger_cambios TINYINT(1) NOT NULL DEFAULT 0 AFTER zona_periferica"
        ];

        foreach ($columns as $column => $sql) {
            if (!$this->columnExists('paquetes', $column)) {
                try {
                    $this->conn->exec($sql);
                } catch (Throwable $e) {
                    // No bloqueamos el registro si la alteración falla.
                }
            }
        }
    }

    // Verificar si el número de guía ya existe para evitar duplicados
    public function verificarGuia($numero_guia) {
        $sql = "SELECT COUNT(*) FROM paquetes WHERE numero_guia = :numero_guia";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([':numero_guia' => $numero_guia]);
        return $stmt->fetchColumn() > 0;
    }

    public function registrarEnvio($datos) {
        if (empty($datos['cliente_id'])) {
            throw new Exception("Error de validación: No se ha identificado el ID del cliente para este envío.");
        }

        // Determinar el tipo de servicio basado en si hay recaudo
        $tipo_servicio = (!empty($datos['tiene_recaudo']) && $datos['tiene_recaudo'] == 1) ? 'contraentrega' : 'entrega_simple';

        try {
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
                        envio_mismo_dia,
                        zona_periferica,
                        recoger_cambios,
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
                        :remitente_nombre, :remitente_telefono, :remitente_email, :remitente_direccion,
                        :destinatario_nombre, :destinatario_telefono, :destinatario_direccion, :instrucciones_entrega,
                        :descripcion_contenido, :dimensiones, :envio_mismo_dia, :zona_periferica, :recoger_cambios, :envio_destinatario,
                        :tipo_servicio, :valor_recaudo, :costo_total, 'pendiente', NOW()
                    )";
        
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
                ':envio_mismo_dia' => !empty($datos['envio_mismo_dia']) ? 1 : 0,
                ':zona_periferica' => !empty($datos['zona_periferica']) ? 1 : 0,
                ':recoger_cambios' => !empty($datos['recoger_cambios']) ? 1 : 0,
                ':envio_destinatario' => $datos['envio_destinatario'] ?? 'no',
                ':tipo_servicio' => $tipo_servicio,
                ':valor_recaudo' => $datos['valor_recaudo'],
                ':costo_total' => $datos['costo_total']
            ]);

        } catch (PDOException $e) {
            // Log del error para el desarrollador
            error_log("Error en EnvioModel::registrarEnvio: " . $e->getMessage());
            // Lanzar excepción genérica para el controlador
            throw new Exception("Error al guardar el envío en la base de datos: " . $e->getMessage());
        }
    }
}
?>
