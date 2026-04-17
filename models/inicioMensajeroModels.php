<?php
require_once __DIR__ . '/conexionGlobal.php';

class InicioMensajeroModels
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

    public function obtenerEstadisticas($mensajeroId)
    {
        $sql = "SELECT
                    (SELECT COUNT(*) FROM paquetes p WHERE p.mensajero_id = :mensajero_id AND p.estado = 'entregado' AND DATE(p.fecha_entrega) = CURDATE()) AS entregadas_hoy,
                    (SELECT COUNT(*) FROM paquetes p WHERE p.mensajero_id = :mensajero_id AND p.estado = 'pendiente') AS pendientes,
                    (SELECT COALESCE(SUM(e.recaudo_real), 0) FROM entregas e WHERE e.mensajero_id = :mensajero_id AND DATE(e.fecha_entrega) = CURDATE()) AS recaudo_hoy";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute([':mensajero_id' => $mensajeroId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function obtenerRecoleccionesPendientes($mensajeroId)
    {
        $sql = "SELECT
                    r.id,
                    r.numero_orden,
                    r.direccion_recoleccion,
                    r.horario_preferido,
                    r.estado,
                    r.fecha_asignacion
                FROM recolecciones r
                WHERE r.mensajero_id = :mensajero_id
                  AND r.estado IN ('asignada', 'en_curso')
                ORDER BY r.fecha_asignacion DESC, r.id DESC
                LIMIT 20";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([':mensajero_id' => $mensajeroId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function obtenerEntregasEnCurso($mensajeroId)
    {
        $sql = "SELECT
                    p.id,
                    p.numero_guia,
                    p.direccion_destino,
                    p.estado
                FROM paquetes p
                WHERE p.mensajero_id = :mensajero_id
                  AND p.estado = 'pendiente'
                ORDER BY p.fecha_asignacion DESC, p.id DESC
                LIMIT 20";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([':mensajero_id' => $mensajeroId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function extraerNumeroGuia($texto)
    {
        $raw = strtoupper(trim((string) $texto));
        if ($raw === '') {
            return '';
        }

        if (preg_match('/(?:GUIA|GUÍA|NUMERO_GUIA|NRO_GUIA|CODIGO|CÓDIGO|QR_CODE|CODE)\s*[:#-]?\s*([A-Z0-9][A-Z0-9\-_\/]{2,})/u', $raw, $match)) {
            return trim($match[1]);
        }

        if (preg_match('/\b(?:[A-Z]{2,10}-)?\d{2,6}-[A-Z0-9]{2,}\b/', $raw, $match)) {
            return trim($match[0]);
        }

        return $raw;
    }

    public function validarGuiaParaEscaneo($mensajeroId, $numeroGuia)
    {
        $guia = $this->extraerNumeroGuia($numeroGuia);
        if ($guia === '') {
            return ['status' => 'invalid'];
        }

        $guiaBase = preg_replace('/^QR-/', '', $guia);

        $sql = "SELECT id, numero_guia, estado, mensajero_id
                FROM paquetes
                WHERE numero_guia IN (:guia, :guia_base)
                LIMIT 1";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([
            ':guia' => $guia,
            ':guia_base' => $guiaBase
        ]);
        $paquete = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$paquete) {
            return ['status' => 'not_found'];
        }

        if (($paquete['estado'] ?? '') === 'entregado') {
            return ['status' => 'delivered'];
        }

        $prevMensajeroId = $paquete['mensajero_id'] !== null ? (int) $paquete['mensajero_id'] : null;
        $mensajeroId = (int) $mensajeroId;
        $reassigned = $prevMensajeroId !== $mensajeroId;

        if ($reassigned) {
            $sqlUpdate = "UPDATE paquetes
                          SET mensajero_id = :mensajero_id
                          WHERE id = :id";
            $stmtUpdate = $this->conn->prepare($sqlUpdate);
            $stmtUpdate->execute([
                ':mensajero_id' => $mensajeroId,
                ':id' => (int) $paquete['id']
            ]);
        }

        return [
            'status' => 'ok',
            'reassigned' => $reassigned,
            'prev_mensajero_id' => $prevMensajeroId,
            'paquete' => [
                'id' => (int) $paquete['id'],
                'numero_guia' => $paquete['numero_guia'],
                'estado' => $paquete['estado']
            ]
        ];
    }
}
