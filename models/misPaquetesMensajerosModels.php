<?php
require_once __DIR__ . '/conexionGlobal.php';

class MisPaquetesMensajerosModels
{
    private $conn;

    public function __construct()
    {
        $this->conn = conexionDB();
        $this->asegurarTablaNovedades();
        $this->asegurarTablaCierresJornada();
    }

    private function asegurarTablaNovedades()
    {
        $sql = "CREATE TABLE IF NOT EXISTS novedades_entrega (
                    id INT PRIMARY KEY AUTO_INCREMENT,
                    paquete_id INT NOT NULL,
                    mensajero_id INT NOT NULL,
                    tipo ENUM('aplazado', 'cancelado') NOT NULL,
                    descripcion TEXT NOT NULL,
                    foto_evidencia VARCHAR(255) NOT NULL,
                    coordenada_lat DECIMAL(10, 8) NULL,
                    coordenada_lng DECIMAL(11, 8) NULL,
                    fecha_registro TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    INDEX idx_novedades_paquete_fecha (paquete_id, fecha_registro),
                    INDEX idx_novedades_tipo (tipo),
                    FOREIGN KEY (paquete_id) REFERENCES paquetes(id) ON DELETE CASCADE,
                    FOREIGN KEY (mensajero_id) REFERENCES mensajeros(id) ON DELETE CASCADE
                )";
        $this->conn->exec($sql);
    }

    private function asegurarTablaCierresJornada()
    {
        $sql = "CREATE TABLE IF NOT EXISTS cierres_jornada_mensajero (
                    id INT PRIMARY KEY AUTO_INCREMENT,
                    mensajero_id INT NOT NULL,
                    fecha_jornada DATE NOT NULL,
                    total_paquetes INT NOT NULL DEFAULT 0,
                    entregados INT NOT NULL DEFAULT 0,
                    aplazados INT NOT NULL DEFAULT 0,
                    cancelados INT NOT NULL DEFAULT 0,
                    recaudo_total DECIMAL(10,2) NOT NULL DEFAULT 0.00,
                    observacion VARCHAR(255) NULL,
                    detalle_json LONGTEXT NULL,
                    fecha_registro TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    fecha_actualizacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    UNIQUE KEY uniq_mensajero_fecha (mensajero_id, fecha_jornada),
                    INDEX idx_cierres_fecha (fecha_jornada),
                    FOREIGN KEY (mensajero_id) REFERENCES mensajeros(id) ON DELETE CASCADE
                )";
        $this->conn->exec($sql);
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
                    COALESCE(NULLIF(c.nombre_emprendimiento, ''), CONCAT(uc.nombres, ' ', uc.apellidos)) AS remitente,
                    ne.tipo AS ultima_novedad_tipo,
                    ne.descripcion AS ultima_novedad_descripcion,
                    ne.foto_evidencia AS ultima_novedad_foto,
                    ne.fecha_registro AS ultima_novedad_fecha,
                    e.nombre_receptor,
                    e.parentesco_cargo,
                    e.documento_receptor,
                    e.recaudo_real,
                    e.fecha_entrega
                FROM paquetes p
                LEFT JOIN clientes c ON p.cliente_id = c.id
                LEFT JOIN usuarios uc ON c.usuario_id = uc.id
                LEFT JOIN (
                    SELECT n1.*
                    FROM novedades_entrega n1
                    INNER JOIN (
                        SELECT paquete_id, MAX(id) AS max_id
                        FROM novedades_entrega
                        GROUP BY paquete_id
                    ) ult ON ult.max_id = n1.id
                ) ne ON ne.paquete_id = p.id
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

    public function obtenerPaquetePorGuia($numeroGuia, $mensajeroId)
    {
        $sql = "SELECT id, numero_guia, cliente_id, mensajero_id
                FROM paquetes
                WHERE numero_guia = :numero_guia
                  AND mensajero_id = :mensajero_id
                LIMIT 1";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([
            ':numero_guia' => $numeroGuia,
            ':mensajero_id' => $mensajeroId
        ]);
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
            $paquete = $this->obtenerPaquetePorGuia($numeroGuia, $mensajeroId);
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

            $numeroComprobante = 'COMP-' . date('dmY') . '-' . str_pad((string) $paquete['id'], 6, '0', STR_PAD_LEFT);
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

    public function registrarNovedad($mensajeroId, array $payload)
    {
        $paquete = null;
        $paqueteId = (int) ($payload['paquete_id'] ?? 0);
        $numeroGuia = trim((string) ($payload['numero_guia'] ?? ''));

        if ($paqueteId > 0) {
            $paquete = $this->obtenerPaquete($paqueteId, $mensajeroId);
        }
        if (!$paquete && $numeroGuia !== '') {
            $paquete = $this->obtenerPaquetePorGuia($numeroGuia, $mensajeroId);
        }
        if (!$paquete) {
            throw new Exception('Paquete no encontrado para este mensajero');
        }

        $tipo = trim((string) ($payload['tipo'] ?? ''));
        if (!in_array($tipo, ['aplazado', 'cancelado'], true)) {
            throw new Exception('Tipo de novedad no válido');
        }

        $this->conn->beginTransaction();
        try {
            $sqlNovedad = "INSERT INTO novedades_entrega (
                                paquete_id, mensajero_id, tipo, descripcion, foto_evidencia,
                                coordenada_lat, coordenada_lng
                           ) VALUES (
                                :paquete_id, :mensajero_id, :tipo, :descripcion, :foto_evidencia,
                                :lat, :lng
                           )";
            $stmtNovedad = $this->conn->prepare($sqlNovedad);
            $stmtNovedad->execute([
                ':paquete_id' => (int) $paquete['id'],
                ':mensajero_id' => (int) $mensajeroId,
                ':tipo' => $tipo,
                ':descripcion' => trim((string) ($payload['descripcion'] ?? '')),
                ':foto_evidencia' => trim((string) ($payload['foto_evidencia'] ?? '')),
                ':lat' => isset($payload['lat']) ? (float) $payload['lat'] : null,
                ':lng' => isset($payload['lng']) ? (float) $payload['lng'] : null
            ]);

            if ($tipo === 'cancelado') {
                $sqlUpdate = "UPDATE paquetes
                              SET estado = 'cancelado'
                              WHERE id = :id";
                $stmtUpdate = $this->conn->prepare($sqlUpdate);
                $stmtUpdate->execute([':id' => (int) $paquete['id']]);
            }

            $this->conn->commit();
            return true;
        } catch (Throwable $e) {
            $this->conn->rollBack();
            throw $e;
        }
    }

    public function guardarCierreJornada($mensajeroId, array $payload)
    {
        $hoy = date('Y-m-d');

        $sql = "INSERT INTO cierres_jornada_mensajero (
                    mensajero_id, fecha_jornada, total_paquetes, entregados, aplazados,
                    cancelados, recaudo_total, observacion, detalle_json
                ) VALUES (
                    :mensajero_id, :fecha_jornada, :total_paquetes, :entregados, :aplazados,
                    :cancelados, :recaudo_total, :observacion, :detalle_json
                )
                ON DUPLICATE KEY UPDATE
                    total_paquetes = VALUES(total_paquetes),
                    entregados = VALUES(entregados),
                    aplazados = VALUES(aplazados),
                    cancelados = VALUES(cancelados),
                    recaudo_total = VALUES(recaudo_total),
                    observacion = VALUES(observacion),
                    detalle_json = VALUES(detalle_json),
                    fecha_actualizacion = CURRENT_TIMESTAMP";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute([
            ':mensajero_id' => (int) $mensajeroId,
            ':fecha_jornada' => $hoy,
            ':total_paquetes' => (int) ($payload['total_paquetes'] ?? 0),
            ':entregados' => (int) ($payload['entregados'] ?? 0),
            ':aplazados' => (int) ($payload['aplazados'] ?? 0),
            ':cancelados' => (int) ($payload['cancelados'] ?? 0),
            ':recaudo_total' => (float) ($payload['recaudo_total'] ?? 0),
            ':observacion' => $payload['observacion'] ?? null,
            ':detalle_json' => $payload['detalle_json'] ?? null
        ]);

        return true;
    }
}
