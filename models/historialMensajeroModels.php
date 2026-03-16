<?php
require_once __DIR__ . '/conexionGlobal.php';

class HistorialMensajeroModels
{
    private $conn;

    public function __construct()
    {
        $this->conn = conexionDB();
    }

    public function obtenerMensajeroPorUsuario($usuarioId)
    {
        $sql = "SELECT m.id
                FROM mensajeros m
                WHERE m.usuario_id = :usuario_id
                LIMIT 1";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([':usuario_id' => $usuarioId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function listarHistorial($mensajeroId)
    {
        $sql = "SELECT
                    p.id,
                    p.numero_guia,
                    p.destinatario_nombre,
                    p.direccion_destino,
                    p.descripcion_contenido,
                    p.costo_envio,
                    e.nombre_receptor,
                    e.parentesco_cargo,
                    e.documento_receptor,
                    e.recaudo_real,
                    e.fecha_entrega,
                    e.foto_entrega,
                    e.foto_adicional,
                    e.observaciones
                FROM paquetes p
                INNER JOIN entregas e ON e.paquete_id = p.id
                WHERE e.mensajero_id = :mensajero_id
                ORDER BY e.fecha_entrega DESC, e.id DESC";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([':mensajero_id' => $mensajeroId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
