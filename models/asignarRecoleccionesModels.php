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
                    p.estado as estado_paquete,
                    MAX(r.estado) as estado_recoleccion,
                    p.mensajero_id,
                    COUNT(*) as cantidad,
                    GROUP_CONCAT(p.id) as ids,
                    GROUP_CONCAT(p.numero_guia SEPARATOR ', ') as guias,
                    MAX(p.fecha_creacion) as fecha_creacion,
                    c.nombre_emprendimiento,
                    u_cli.nombres as cli_nombres, 
                    u_cli.apellidos as cli_apellidos,
                    CONCAT(u_mens.nombres, ' ', u_mens.apellidos) as mensajero_nombre,
                    CASE
                        WHEN DATE(p.fecha_creacion) < CURDATE() THEN 'verde'
                        WHEN HOUR(p.fecha_creacion) < 13 THEN 'verde'
                        WHEN HOUR(p.fecha_creacion) < 17 THEN 'amarillo'
                        ELSE 'rojo'
                    END as color_prioridad
                FROM paquetes p
                LEFT JOIN clientes c ON p.cliente_id = c.id
                LEFT JOIN usuarios u_cli ON c.usuario_id = u_cli.id
                LEFT JOIN mensajeros m ON p.mensajero_id = m.id
                LEFT JOIN usuarios u_mens ON m.usuario_id = u_mens.id
                LEFT JOIN recolecciones r ON p.recoleccion_id = r.id
                WHERE p.estado IN ('pendiente', 'asignado', 'en_transito', 'en_ruta', 'entregado')";
        
        $params = [];

        if (!empty($filtros['busqueda'])) {
            $sql .= " AND (p.direccion_origen LIKE :search OR u_cli.nombres LIKE :search OR c.nombre_emprendimiento LIKE :search OR u_mens.nombres LIKE :search)";
            $params[':search'] = '%' . $filtros['busqueda'] . '%';
        }

        // Agrupar por dirección, estado, mensajero, fecha y franja horaria (antes/después 1 PM)
        $sql .= " GROUP BY p.direccion_origen, 
                           IFNULL(p.recoleccion_id, p.estado), 
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
            
            // Si tiene un estado de recolección real (ej: completada), usamos ese. Si no, usamos el del paquete.
            if (!empty($row['estado_recoleccion'])) {
                $row['estado'] = $row['estado_recoleccion'];
            } else {
                $row['estado'] = $row['estado_paquete'];
            }
            
            $result[] = $row;
        }
        return $result;
    }

    // Nueva función: Asignar mensajero a un grupo de paquetes
    public function asignarMensajeroPaquetes($ids, $mensajeroId, $creadoPor) {
        // Convertimos la lista de IDs "1,2,3" en un array seguro para SQL si fuera necesario,
        // pero FIND_IN_SET o IN() con parámetros es mejor. Aquí usaremos IN con string directo validado.
        
        // Validar que ids sean solo números y comas para evitar inyección
        if (!preg_match('/^[0-9,]+$/', $ids)) return false;
        
        try {
            $this->conn->beginTransaction();

            // 1. Obtener datos clave de los paquetes (Cliente, Dirección, Contacto) para crear la recolección
            // Usamos LIMIT 1 porque asumimos que los paquetes agrupados pertenecen al mismo origen
            $sqlInfo = "SELECT p.cliente_id, p.direccion_origen, c.nombre_emprendimiento, u.nombres, u.apellidos, u.telefono 
                        FROM paquetes p 
                        LEFT JOIN clientes c ON p.cliente_id = c.id
                        LEFT JOIN usuarios u ON c.usuario_id = u.id
                        WHERE p.id IN ($ids) LIMIT 1";
            $stmtInfo = $this->conn->query($sqlInfo);
            $info = $stmtInfo->fetch(PDO::FETCH_ASSOC);

            if ($info) {
                // Generar número de orden único
                $numeroOrden = 'REC-' . date('ymd') . '-' . rand(1000, 9999);
                $contacto = !empty($info['nombre_emprendimiento']) ? $info['nombre_emprendimiento'] : ($info['nombres'] . ' ' . $info['apellidos']);
                $cantidad = count(explode(',', $ids));

                // 2. Insertar en la tabla 'recolecciones'
                $sqlInsert = "INSERT INTO recolecciones 
                              (numero_orden, cliente_id, mensajero_id, direccion_recoleccion, nombre_contacto, telefono_contacto, cantidad_estimada, estado, fecha_asignacion, creada_por)
                              VALUES 
                              (:orden, :cliente, :mensajero, :direccion, :contacto, :telefono, :cantidad, 'asignada', NOW(), :creada_por)";
                
                $stmtInsert = $this->conn->prepare($sqlInsert);
                $stmtInsert->execute([
                    ':orden' => $numeroOrden,
                    ':cliente' => $info['cliente_id'],
                    ':mensajero' => $mensajeroId,
                    ':direccion' => $info['direccion_origen'],
                    ':contacto' => $contacto,
                    ':telefono' => $info['telefono'],
                    ':cantidad' => $cantidad,
                    ':creada_por' => $creadoPor
                ]);
                
                $recoleccionId = $this->conn->lastInsertId();

                // 3. Actualizar paquetes vinculándolos a esta recolección
                // Nota: Asegúrate de haber ejecutado el ALTER TABLE para agregar recoleccion_id
                $sql = "UPDATE paquetes SET mensajero_id = :mensajero_id, mensajero_recoleccion_id = :mensajero_id, recoleccion_id = :recoleccion_id, estado = 'asignado' WHERE id IN ($ids)";
                $stmt = $this->conn->prepare($sql);
                $stmt->execute([':mensajero_id' => $mensajeroId, ':recoleccion_id' => $recoleccionId]);
            }

            return $this->conn->commit();
        } catch (Exception $e) {
            $this->conn->rollBack();
            // Re-lanzamos la excepción para que el controlador pueda capturar el mensaje de error específico.
            throw $e;
        }
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

    // Obtener detalles de una recolección asociada a un paquete
    public function getRecoleccionPorPaquete($paqueteId) {
        $sql = "SELECT r.* 
                FROM recolecciones r
                JOIN paquetes p ON p.recoleccion_id = r.id
                WHERE p.id = :paquete_id
                LIMIT 1";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([':paquete_id' => $paqueteId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}
?>
