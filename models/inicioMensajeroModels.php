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
}

