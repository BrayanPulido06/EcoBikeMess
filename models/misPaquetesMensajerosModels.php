<?php
require_once __DIR__ . '/conexionGlobal.php';

class MisPaquetesMensajerosModels
{
    private $conn;

    public function __construct()
    {
        $this->conn = conexionDB();
    }

    public function obtenerMensajeroPorUsuario($usuarioId)
    {
        $sql = "SELECT m.id, u.nombres, u.apellidos
                FROM mensajeros m
                INNER JOIN usuarios u ON u.id = m.usuario_id
                WHERE m.usuario_id = :usuario_id
                LIMIT 1";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([':usuario_id' => $usuarioId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function listarPaquetes($mensajeroId)
    {
        $sql = "SELECT
                    p.id,
                    p.numero_guia,
                    p.destinatario_nombre,
                    p.destinatario_telefono,
                    p.direccion_destino,
                    p.descripcion_contenido,
                    p.instrucciones_entrega,
                    p.costo_envio,
                    p.recaudo_esperado,
                    p.estado,
                    p.escaneado,
                    e.nombre_receptor,
                    e.parentesco_cargo,
                    e.documento_receptor,
                    e.recaudo_real,
                    e.fecha_entrega
                FROM paquetes p
                LEFT JOIN entregas e ON e.paquete_id = p.id
                WHERE p.mensajero_id = :mensajero_id
                ORDER BY p.fecha_asignacion DESC, p.id DESC";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([':mensajero_id' => $mensajeroId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function obtenerPaquete($paqueteId, $mensajeroId)
    {
        $sql = "SELECT id, numero_guia, cliente_id
                FROM paquetes
                WHERE id = :paquete_id
                  AND mensajero_id = :mensajero_id
                LIMIT 1";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([
            ':paquete_id' => $paqueteId,
            ':mensajero_id' => $mensajeroId
        ]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function obtenerPaquetePorGuia($numeroGuia)
    {
        $sql = "SELECT id, numero_guia, cliente_id, mensajero_id
                FROM paquetes
                WHERE numero_guia = :numero_guia
                LIMIT 1";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([':numero_guia' => $numeroGuia]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function registrarEntrega($mensajeroId, array $payload)
    {
        $paquete = null;
        $paqueteId = (int) ($payload['paquete_id'] ?? 0);
        $numeroGuia = trim((string) ($payload['numero_guia'] ?? ''));

        if ($paqueteId > 0) {
            $paquete = $this->obtenerPaquete($paqueteId, $mensajeroId);
        }
        if (!$paquete && $numeroGuia !== '') {
            $paquete = $this->obtenerPaquetePorGuia($numeroGuia);
        }

        if (!$paquete) {
            throw new Exception('Paquete no encontrado para este mensajero');
        }

        $this->conn->beginTransaction();
        try {
            $sqlUpdatePaquete = "UPDATE paquetes
                                 SET estado = 'entregado',
                                     mensajero_id = :mensajero_id,
                                     fecha_entrega = NOW()
                                 WHERE id = :id";
            $stmt = $this->conn->prepare($sqlUpdatePaquete);
            $stmt->execute([
                ':id' => $paquete['id'],
                ':mensajero_id' => (int) $mensajeroId
            ]);

            $sqlEntrega = "INSERT INTO entregas (
                                paquete_id, mensajero_id, nombre_receptor, parentesco_cargo,
                                documento_receptor, recaudo_real, coordenadas_entrega_lat,
                                coordenadas_entrega_lng, foto_entrega, foto_adicional, observaciones
                           ) VALUES (
                                :paquete_id, :mensajero_id, :nombre_receptor, :parentesco_cargo,
                                :documento_receptor, :recaudo_real, :lat, :lng, :foto_entrega,
                                :foto_adicional, :observaciones
                           )
                           ON DUPLICATE KEY UPDATE
                                nombre_receptor = VALUES(nombre_receptor),
                                parentesco_cargo = VALUES(parentesco_cargo),
                                documento_receptor = VALUES(documento_receptor),
                                recaudo_real = VALUES(recaudo_real),
                                coordenadas_entrega_lat = VALUES(coordenadas_entrega_lat),
                                coordenadas_entrega_lng = VALUES(coordenadas_entrega_lng),
                                foto_entrega = VALUES(foto_entrega),
                                foto_adicional = VALUES(foto_adicional),
                                observaciones = VALUES(observaciones),
                                fecha_entrega = CURRENT_TIMESTAMP";

            $stmtEntrega = $this->conn->prepare($sqlEntrega);
            $stmtEntrega->execute([
                ':paquete_id' => (int) $paquete['id'],
                ':mensajero_id' => (int) $mensajeroId,
                ':nombre_receptor' => $payload['nombre_receptor'],
                ':parentesco_cargo' => $payload['parentesco_cargo'] ?: null,
                ':documento_receptor' => $payload['documento_receptor'] ?: null,
                ':recaudo_real' => (float) $payload['recaudo_real'],
                ':lat' => $payload['lat'] !== null ? (float) $payload['lat'] : null,
                ':lng' => $payload['lng'] !== null ? (float) $payload['lng'] : null,
                ':foto_entrega' => $payload['foto_entrega'],
                ':foto_adicional' => $payload['foto_adicional'] ?: null,
                ':observaciones' => $payload['observaciones'] ?: null
            ]);

            $sqlComprobante = "INSERT INTO comprobantes (
                                    paquete_id, cliente_id, numero_comprobante, numero_guia,
                                    nombre_receptor, parentesco_cargo, recaudo, observaciones, foto_entrega
                               ) VALUES (
                                    :paquete_id, :cliente_id, :numero_comprobante, :numero_guia,
                                    :nombre_receptor, :parentesco_cargo, :recaudo, :observaciones, :foto_entrega
                               )
                               ON DUPLICATE KEY UPDATE
                                    nombre_receptor = VALUES(nombre_receptor),
                                    parentesco_cargo = VALUES(parentesco_cargo),
                                    recaudo = VALUES(recaudo),
                                    observaciones = VALUES(observaciones),
                                    foto_entrega = VALUES(foto_entrega),
                                    fecha_generacion = CURRENT_TIMESTAMP";

            $numeroComprobante = 'COMP-' . date('Ymd') . '-' . str_pad((string) $paquete['id'], 6, '0', STR_PAD_LEFT);
            $stmtComp = $this->conn->prepare($sqlComprobante);
            $stmtComp->execute([
                ':paquete_id' => (int) $paquete['id'],
                ':cliente_id' => (int) $paquete['cliente_id'],
                ':numero_comprobante' => $numeroComprobante,
                ':numero_guia' => $paquete['numero_guia'],
                ':nombre_receptor' => $payload['nombre_receptor'],
                ':parentesco_cargo' => $payload['parentesco_cargo'] ?: null,
                ':recaudo' => (float) $payload['recaudo_real'],
                ':observaciones' => $payload['observaciones'] ?: null,
                ':foto_entrega' => $payload['foto_entrega']
            ]);

            $this->conn->commit();
            return true;
        } catch (Throwable $e) {
            $this->conn->rollBack();
            throw $e;
        }
    }
}
