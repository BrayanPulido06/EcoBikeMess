<?php
require_once __DIR__ . '/conexionGlobal.php';

class RecoleccionesMensajeroModels
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

    public function listarRecolecciones($mensajeroId)
    {
        $sql = "SELECT
                    r.id,
                    r.numero_orden,
                    r.estado,
                    r.prioridad,
                    r.fecha_asignacion,
                    r.direccion_recoleccion,
                    r.coordenadas_lat,
                    r.coordenadas_lng,
                    r.nombre_contacto,
                    r.telefono_contacto,
                    r.cantidad_estimada,
                    r.cantidad_real,
                    r.foto_recoleccion,
                    r.horario_preferido,
                    r.descripcion_paquetes,
                    r.observaciones_recoleccion
                FROM recolecciones r
                WHERE r.mensajero_id = :mensajero_id
                  AND r.estado IN ('asignada', 'en_curso', 'completada')
                ORDER BY r.fecha_asignacion DESC, r.id DESC";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([':mensajero_id' => $mensajeroId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function iniciarRecoleccion($recoleccionId, $mensajeroId)
    {
        $sql = "UPDATE recolecciones
                SET estado = 'en_curso'
                WHERE id = :id
                  AND mensajero_id = :mensajero_id
                  AND estado IN ('asignada', 'en_curso')";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([
            ':id' => $recoleccionId,
            ':mensajero_id' => $mensajeroId
        ]);
        return $stmt->rowCount() > 0;
    }

    public function completarRecoleccion($recoleccionId, $mensajeroId, array $payload)
    {
        $sql = "UPDATE recolecciones
                SET estado = 'completada',
                    cantidad_real = :cantidad_real,
                    fecha_completada = NOW(),
                    foto_recoleccion = :foto_recoleccion,
                    observaciones_recoleccion = :observaciones,
                    conformidad = :conformidad
                WHERE id = :id
                  AND mensajero_id = :mensajero_id
                  AND estado IN ('asignada', 'en_curso')";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([
            ':cantidad_real' => (int) $payload['cantidad_real'],
            ':foto_recoleccion' => $payload['foto_recoleccion'] ?: null,
            ':observaciones' => $payload['observaciones'] ?: null,
            ':conformidad' => (int) $payload['conformidad'],
            ':id' => $recoleccionId,
            ':mensajero_id' => $mensajeroId
        ]);
        return $stmt->rowCount() > 0;
    }

    public function listarPaquetesRecoleccion($recoleccionId, $mensajeroId)
    {
        $sql = "SELECT p.numero_guia, p.destinatario_nombre
                FROM recolecciones r
                INNER JOIN paquetes p
                    ON p.recoleccion_id = r.id
                WHERE r.id = :recoleccion_id
                  AND r.mensajero_id = :mensajero_id
                  AND p.estado IN ('pendiente', 'asignado', 'asignada', 'en_curso', 'en_transito', 'en_ruta')
                ORDER BY p.id DESC";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([
            ':recoleccion_id' => $recoleccionId,
            ':mensajero_id' => $mensajeroId
        ]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function cancelarRecoleccion($recoleccionId, $mensajeroId, $motivo)
    {
        $sql = "UPDATE recolecciones
                SET estado = 'cancelada',
                    observaciones_recoleccion = :motivo,
                    fecha_completada = NOW()
                WHERE id = :id
                  AND mensajero_id = :mensajero_id
                  AND estado IN ('asignada', 'en_curso')";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([
            ':motivo' => $motivo ?: null,
            ':id' => $recoleccionId,
            ':mensajero_id' => $mensajeroId
        ]);
        return $stmt->rowCount() > 0;
    }
}
