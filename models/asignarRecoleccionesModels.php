<?php
require_once 'conexionGlobal.php';

class AsignarRecoleccionesModel {
    private $conn;

    public function __construct() {
        $this->conn = conexionDB();
    }

    // Obtener lista de clientes para el select
    public function getClientes() {
        $sql = "SELECT c.id, u.nombres, u.apellidos, c.nombre_emprendimiento 
                FROM clientes c 
                JOIN usuarios u ON c.usuario_id = u.id 
                ORDER BY u.nombres ASC";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute();
        $clientes = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $nombre = !empty($row['nombre_emprendimiento']) ? $row['nombre_emprendimiento'] : ($row['nombres'] . ' ' . $row['apellidos']);
            $clientes[] = ['id' => $row['id'], 'nombre' => $nombre];
        }
        return $clientes;
    }

    // Obtener mensajeros activos para asignar
    public function getMensajeros() {
        // Consultamos usuarios con rol 'mensajero' y sus tareas activas
        $sql = "SELECT m.id, CONCAT(u.nombres, ' ', u.apellidos) as nombre, u.estado,
                       (SELECT COUNT(*) FROM paquetes p WHERE p.mensajero_id = m.id AND p.estado IN ('asignado', 'en_transito', 'en_ruta')) as tareas_activas
                FROM usuarios u 
                JOIN mensajeros m ON u.id = m.usuario_id 
                WHERE u.tipo_usuario = 'mensajero'
                ORDER BY u.nombres ASC";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute();
        
        $mensajeros = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $mensajeros[] = [
                'id' => $row['id'],
                'nombre' => $row['nombre'],
                'estado' => $row['estado'],
                'tareas_activas' => $row['tareas_activas']
            ];
        }
        return $mensajeros;
    }

    // Listar recolecciones con filtros
    public function listarRecolecciones($filtros) {
        // Consultamos la tabla paquetes agrupando por dirección de origen
        $sql = "SELECT 
                    p.direccion_origen,
                    p.estado,
                    p.mensajero_id,
                    COUNT(*) as cantidad,
                    GROUP_CONCAT(p.id) as ids,
                    GROUP_CONCAT(p.numero_guia SEPARATOR ', ') as guias,
                    MAX(p.fecha_creacion) as fecha_creacion,
                    c.nombre_emprendimiento,
                    u_cli.nombres as cli_nombres, 
                    u_cli.apellidos as cli_apellidos,
                    CONCAT(u_mens.nombres, ' ', u_mens.apellidos) as mensajero_nombre
                FROM paquetes p
                LEFT JOIN clientes c ON p.cliente_id = c.id
                LEFT JOIN usuarios u_cli ON c.usuario_id = u_cli.id
                LEFT JOIN mensajeros m ON p.mensajero_id = m.id
                LEFT JOIN usuarios u_mens ON m.usuario_id = u_mens.id
                WHERE p.estado IN ('pendiente', 'asignado', 'en_transito', 'en_ruta')";
        
        $params = [];

        if (!empty($filtros['busqueda'])) {
            $sql .= " AND (p.direccion_origen LIKE :search OR u_cli.nombres LIKE :search OR c.nombre_emprendimiento LIKE :search OR u_mens.nombres LIKE :search)";
            $params[':search'] = '%' . $filtros['busqueda'] . '%';
        }

        // Agrupar por dirección, estado, mensajero, fecha y franja horaria (antes/después 1 PM)
        $sql .= " GROUP BY p.direccion_origen, 
                           p.estado, 
                           p.mensajero_id, 
                           DATE(p.fecha_creacion), 
                           CASE WHEN HOUR(p.fecha_creacion) < 13 THEN 'AM' ELSE 'PM' END 
                  ORDER BY fecha_creacion DESC";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);
        
        $result = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $cliente = !empty($row['nombre_emprendimiento']) ? $row['nombre_emprendimiento'] : ($row['cli_nombres'] . ' ' . $row['cli_apellidos']);
            
            // Añadimos el nombre del cliente formateado al array de datos
            $row['cliente_nombre'] = $cliente;
            $row['mensajero_nombre'] = $row['mensajero_nombre'] ? $row['mensajero_nombre'] : 'Sin asignar';
            
            $result[] = $row;
        }
        return $result;
    }

    // Nueva función: Asignar mensajero a un grupo de paquetes
    public function asignarMensajeroPaquetes($ids, $mensajeroId) {
        // Convertimos la lista de IDs "1,2,3" en un array seguro para SQL si fuera necesario,
        // pero FIND_IN_SET o IN() con parámetros es mejor. Aquí usaremos IN con string directo validado.
        
        // Validar que ids sean solo números y comas para evitar inyección
        if (!preg_match('/^[0-9,]+$/', $ids)) return false;

        // Asignamos tanto al mensajero actual (para que le aparezca en la app) como al histórico de recolección
        $sql = "UPDATE paquetes SET mensajero_id = :mensajero_id, mensajero_recoleccion_id = :mensajero_id, estado = 'asignado' WHERE id IN ($ids)";
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute([':mensajero_id' => $mensajeroId]);
    }

    // Nueva función: Obtener detalles completos de los paquetes por IDs
    public function obtenerDetallesPaquetes($ids) {
        // Validar que ids sean solo números y comas para seguridad
        if (!preg_match('/^[0-9,]+$/', $ids)) return [];

        $sql = "SELECT p.*, 
                       c.nombre_emprendimiento, 
                       u.nombres as cli_nombres, u.apellidos as cli_apellidos, u.telefono as cli_telefono
                FROM paquetes p
                LEFT JOIN clientes c ON p.cliente_id = c.id
                LEFT JOIN usuarios u ON c.usuario_id = u.id
                WHERE p.id IN ($ids)";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Nueva función: Cancelar (soft delete) un grupo de paquetes
    public function cancelarPaquetes($ids) {
        // Validar que ids sean solo números y comas para seguridad
        if (!preg_match('/^[0-9,]+$/', $ids)) return false;
        $sql = "UPDATE paquetes SET estado = 'cancelado' WHERE id IN ($ids)";
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute();
    }
}
?>
