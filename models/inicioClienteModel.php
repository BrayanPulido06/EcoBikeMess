<?php
require_once __DIR__ . '/conexionGlobal.php';

class InicioClienteModel {
    private $conn;

    public function __construct() {
        $this->conn = conexionDB();
    }

    // Obtener el ID del cliente (tienda) según el rol del usuario logueado
    public function obtenerIdCliente($usuario_id, $rol) {
        try {
            if ($rol === 'cliente') {
                $sql = "SELECT id FROM clientes WHERE usuario_id = :uid";
                $stmt = $this->conn->prepare($sql);
                $stmt->execute([':uid' => $usuario_id]);
                return $stmt->fetchColumn();
            } elseif ($rol === 'colaborador') {
                $sql = "SELECT cliente_id FROM colaboradores_cliente WHERE usuario_id = :uid";
                $stmt = $this->conn->prepare($sql);
                $stmt->execute([':uid' => $usuario_id]);
                return $stmt->fetchColumn();
            }
        } catch (PDOException $e) {
            return null;
        }
        return null;
    }

    public function obtenerEstadisticas($cliente_id) {
        $stats = [
            'pedidos_mes' => 0,
            'en_transito' => 0,
            'saldo_pendiente' => 0,
            'entregados_total' => 0
        ];

        try {
            // 1. Pedidos del mes actual
            $sqlMes = "SELECT COUNT(*) FROM paquetes 
                       WHERE cliente_id = :cid AND MONTH(fecha_creacion) = MONTH(CURRENT_DATE()) 
                       AND YEAR(fecha_creacion) = YEAR(CURRENT_DATE())";
            $stmt = $this->conn->prepare($sqlMes);
            $stmt->execute([':cid' => $cliente_id]);
            $stats['pedidos_mes'] = $stmt->fetchColumn();

            // 2. En tránsito (estados activos)
            $sqlTransito = "SELECT COUNT(*) FROM paquetes 
                            WHERE cliente_id = :cid AND estado IN ('en_transito', 'en_proceso', 'recogido', 'pendiente')";
            $stmt = $this->conn->prepare($sqlTransito);
            $stmt->execute([':cid' => $cliente_id]);
            $stats['en_transito'] = $stmt->fetchColumn();

            // 3. Saldo pendiente (Suma de costos de envío del mes actual no cancelados)
            // Nota: Esto es una aproximación. Idealmente se usaría una tabla de facturación.
            $sqlSaldo = "SELECT SUM(costo_envio) FROM paquetes 
                         WHERE cliente_id = :cid AND MONTH(fecha_creacion) = MONTH(CURRENT_DATE()) 
                         AND YEAR(fecha_creacion) = YEAR(CURRENT_DATE()) AND estado != 'cancelado'";
            $stmt = $this->conn->prepare($sqlSaldo);
            $stmt->execute([':cid' => $cliente_id]);
            $stats['saldo_pendiente'] = $stmt->fetchColumn() ?: 0;

            // 4. Entregados total histórico
            $sqlEntregados = "SELECT COUNT(*) FROM paquetes WHERE cliente_id = :cid AND estado = 'entregado'";
            $stmt = $this->conn->prepare($sqlEntregados);
            $stmt->execute([':cid' => $cliente_id]);
            $stats['entregados_total'] = $stmt->fetchColumn();

        } catch (PDOException $e) {
            // En caso de error, retornamos ceros
        }

        return $stats;
    }

    public function obtenerUltimosPedidos($cliente_id, $limit = 5) {
        try {
            $sql = "SELECT id, numero_guia, direccion_destino, estado, fecha_creacion 
                    FROM paquetes 
                    WHERE cliente_id = :cid 
                    ORDER BY fecha_creacion DESC 
                    LIMIT :limit";
            $stmt = $this->conn->prepare($sql);
            $stmt->bindValue(':cid', $cliente_id, PDO::PARAM_INT);
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return [];
        }
    }

    public function obtenerDatosGrafica($cliente_id, $periodo = 'year') {
        try {
            $condicion = "AND YEAR(fecha_creacion) = YEAR(CURRENT_DATE())"; // Por defecto año actual
            
            if ($periodo === '30_days') {
                $condicion = "AND fecha_creacion >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
            } elseif ($periodo === '3_months') {
                $condicion = "AND fecha_creacion >= DATE_SUB(NOW(), INTERVAL 3 MONTH)";
            }

            // Agrupar por mes del año actual
            $sql = "SELECT MONTH(fecha_creacion) as mes, COUNT(*) as total, 
                    SUM(CASE WHEN estado = 'entregado' THEN 1 ELSE 0 END) as entregados
                    FROM paquetes 
                    WHERE cliente_id = :cid $condicion
                    GROUP BY MONTH(fecha_creacion)
                    ORDER BY mes ASC";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([':cid' => $cliente_id]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return [];
        }
    }
}
?>