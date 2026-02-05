<?php
require_once __DIR__ . '/conexionGlobal.php';

class MisPedidosModel {
    private $conn;

    public function __construct() {
        $this->conn = conexionDB();
    }

    // Obtener historial de pedidos por ID de cliente (tienda)
    public function obtenerPedidosPorCliente($cliente_id) {
        try {
            $sql = "SELECT p.* 
                    FROM paquetes p
                    WHERE p.cliente_id = :cliente_id
                    ORDER BY p.fecha_creacion DESC";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([':cliente_id' => $cliente_id]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return [];
        }
    }
}
?>
