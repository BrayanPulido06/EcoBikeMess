<?php
require_once __DIR__ . '/conexionGlobal.php';

class MisPedidosModel {
    private $conn;

    public function __construct() {
        $this->conn = conexionDB();
    }

    // Obtener el ID del cliente basado en el usuario logueado
    public function obtenerIdCliente($usuario_id, $rol) {
        if ($rol === 'cliente') {
            $sql = "SELECT id FROM clientes WHERE usuario_id = :uid";
        } elseif ($rol === 'colaborador') {
            $sql = "SELECT cliente_id AS id FROM colaboradores_cliente WHERE usuario_id = :uid AND estado = 'activo'";
        } else {
            return null;
        }

        $stmt = $this->conn->prepare($sql);
        $stmt->execute([':uid' => $usuario_id]);
        $res = $stmt->fetch(PDO::FETCH_ASSOC);
        return $res ? $res['id'] : null;
    }

    // Listar paquetes (adaptado para la vista de facturación)
    public function listarFacturas($cliente_id, $filtros = []) {
        $sql = "SELECT p.*, CONCAT(um.nombres, ' ', um.apellidos) as mensajero_nombre
                FROM paquetes p
                LEFT JOIN mensajeros m ON p.mensajero_id = m.id
                LEFT JOIN usuarios um ON m.usuario_id = um.id
                WHERE p.cliente_id = :cliente_id";

        $params = [':cliente_id' => $cliente_id];

        // Filtro por búsqueda (guía o destinatario)
        if (!empty($filtros['search'])) {
            $sql .= " AND (numero_guia LIKE :search OR destinatario_nombre LIKE :search)";
            $params[':search'] = "%" . $filtros['search'] . "%";
        }

        // Filtro por estado
        if (!empty($filtros['estado'])) {
            $sql .= " AND estado = :estado";
            $params[':estado'] = $filtros['estado'];
        }

        // Filtro por fechas
        if (!empty($filtros['fechaDesde'])) {
            $sql .= " AND fecha_creacion >= :fechaDesde";
            $params[':fechaDesde'] = $filtros['fechaDesde'] . " 00:00:00";
        }
        if (!empty($filtros['fechaHasta'])) {
            $sql .= " AND fecha_creacion <= :fechaHasta";
            $params[':fechaHasta'] = $filtros['fechaHasta'] . " 23:59:59";
        }

        // Filtro por rango de monto
        if (!empty($filtros['monto'])) {
            switch ($filtros['monto']) {
                case '0-50000': $sql .= " AND costo_envio BETWEEN 0 AND 50000"; break;
                case '50000-100000': $sql .= " AND costo_envio BETWEEN 50000 AND 100000"; break;
                case '100000-500000': $sql .= " AND costo_envio BETWEEN 100000 AND 500000"; break;
                case '500000+': $sql .= " AND costo_envio > 500000"; break;
            }
        }

        $sql .= " ORDER BY fecha_creacion DESC";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Obtener detalles de un paquete específico
    public function obtenerDetalleFactura($factura_id, $cliente_id) {
        // 1. Obtener info del paquete
        $sql = "SELECT p.*, c.nombre_emprendimiento, CONCAT(um.nombres, ' ', um.apellidos) as mensajero_nombre
                FROM paquetes p
                JOIN clientes c ON p.cliente_id = c.id
                LEFT JOIN mensajeros m ON p.mensajero_id = m.id
                LEFT JOIN usuarios um ON m.usuario_id = um.id
                WHERE p.id = :id AND p.cliente_id = :cliente_id";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([':id' => $factura_id, ':cliente_id' => $cliente_id]);
        $factura = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$factura) return null;

        // Obtener información de la entrega si existe
        if ($factura['estado'] === 'entregado') {
            $sqlEntrega = "SELECT * FROM entregas WHERE paquete_id = :id";
            $stmtEntrega = $this->conn->prepare($sqlEntrega);
            $stmtEntrega->execute([':id' => $factura_id]);
            $entrega = $stmtEntrega->fetch(PDO::FETCH_ASSOC);
            
            if ($entrega) {
                $factura['infoEntrega'] = [
                    'nombreRecibe' => $entrega['nombre_receptor'] ?? '',
                    'parentesco' => $entrega['parentesco_cargo'] ?? '',
                    'documento' => $entrega['documento_receptor'] ?? '',
                    'recaudo' => $entrega['recaudo_real'] ?? 0,
                    'fecha' => $entrega['fecha_entrega'] ?? '',
                    'observaciones' => $entrega['observaciones_entrega'] ?? '',
                    'fotoPrincipal' => $entrega['foto_entrega'] ?? '',
                    'fotoAdicional' => $entrega['foto_adicional'] ?? ''
                ];
            }
        }

        // 2. Obtener historial como "items" para mantener estructura
        // Si tienes tabla historial_paquetes úsala, si no, devolvemos array vacío
        $items = []; 
        // Ejemplo si existiera historial:
        // $sqlHist = "SELECT * FROM historial_paquetes WHERE paquete_id = :id";
        // ...

        return ['info' => $factura, 'items' => $items];
    }

    // Obtener estadísticas rápidas (Adaptadas a paquetes)
    public function obtenerEstadisticas($cliente_id, $filtros = []) {
        $sql = "SELECT 
                    COALESCE(SUM(costo_envio), 0) as total_envios,
                    COALESCE(SUM(recaudo_esperado), 0) as total_recaudos
                FROM paquetes 
                WHERE cliente_id = :id AND estado != 'cancelado'";
        
        $params = [':id' => $cliente_id];

        // Aplicar filtros (Misma lógica que listarFacturas)
        if (!empty($filtros['search'])) {
            $sql .= " AND (numero_guia LIKE :search OR destinatario_nombre LIKE :search)";
            $params[':search'] = "%" . $filtros['search'] . "%";
        }

        if (!empty($filtros['estado'])) {
            $sql .= " AND estado = :estado";
            $params[':estado'] = $filtros['estado'];
        }

        if (!empty($filtros['fechaDesde'])) {
            $sql .= " AND fecha_creacion >= :fechaDesde";
            $params[':fechaDesde'] = $filtros['fechaDesde'] . " 00:00:00";
        }
        if (!empty($filtros['fechaHasta'])) {
            $sql .= " AND fecha_creacion <= :fechaHasta";
            $params[':fechaHasta'] = $filtros['fechaHasta'] . " 23:59:59";
        }

        if (!empty($filtros['monto'])) {
            switch ($filtros['monto']) {
                case '0-50000': $sql .= " AND costo_envio BETWEEN 0 AND 50000"; break;
                case '50000-100000': $sql .= " AND costo_envio BETWEEN 50000 AND 100000"; break;
                case '100000-500000': $sql .= " AND costo_envio BETWEEN 100000 AND 500000"; break;
                case '500000+': $sql .= " AND costo_envio > 500000"; break;
            }
        }
        
        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);
        $res = $stmt->fetch(PDO::FETCH_ASSOC);

        $total_envios = $res['total_envios'] ?? 0;
        $total_recaudos = $res['total_recaudos'] ?? 0;

        // Lógica: Recaudo (Dinero que tiene el mensajero) - Envio (Dinero que debe el cliente)
        $balance = $total_recaudos - $total_envios;

        $saldo_favor = 0;
        $saldo_pagar = 0;

        if ($balance > 0) {
            $saldo_favor = $balance; // Recaudo supera al costo -> A favor del cliente
        } else {
            $saldo_pagar = abs($balance); // Costo supera al recaudo -> Cliente debe pagar
        }

        return [
            'saldo_favor' => $saldo_favor,
            'saldo_pagar' => $saldo_pagar
        ];
    }
}
?>
