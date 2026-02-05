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
        $sql = "SELECT m.id, u.nombres, u.apellidos, m.estado 
                FROM mensajeros m 
                JOIN usuarios u ON m.usuario_id = u.id 
                WHERE m.estado IN ('activo', 'en_ruta', 'descanso')
                ORDER BY u.nombres ASC";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute();
        
        $mensajeros = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            // Opcional: Podrías contar cuántas recolecciones tiene asignadas hoy
            $mensajeros[] = [
                'id' => $row['id'],
                'nombre' => $row['nombres'] . ' ' . $row['apellidos'],
                'estado' => $row['estado']
            ];
        }
        return $mensajeros;
    }

    // Crear una nueva recolección
    public function crearRecoleccion($datos) {
        try {
            // Generar número de orden único requerido por la tabla recolecciones
            $orden = 'REC-' . date('Ymd') . '-' . rand(1000, 9999);
            
            // Como la tabla no tiene campo 'fecha_programada', la agregamos a observaciones
            $obs = "Fecha: " . $datos['fechaRecoleccion'] . ". " . $datos['observaciones'];

            // Usamos la tabla 'recolecciones' definida en tu SQL
            $sql = "INSERT INTO recolecciones (
                        numero_orden, cliente_id, nombre_contacto, telefono_contacto,
                        direccion_recoleccion, coordenadas_lat, coordenadas_lng,
                        descripcion_paquetes, cantidad_estimada,
                        horario_preferido, prioridad, observaciones_recoleccion,
                        mensajero_id, estado, creada_por
                    ) VALUES (
                        :orden,
                        :cliente_id, :contacto, :telefono,
                        :direccion, :lat, :lon,
                        :descripcion, :cantidad,
                        :horario, :prioridad, :obs,
                        :mensajero_id, :estado, :creada_por
                    )";
            
            // Tu tabla SQL solo permite: 'asignada', 'en_curso', 'completada', 'cancelada'.
            // No existe 'pendiente', así que usamos 'asignada' por defecto (aunque no tenga mensajero aún).
            $estado = 'asignada';
            
            $stmt = $this->conn->prepare($sql);
            return $stmt->execute([
                ':orden' => $orden,
                ':cliente_id' => $datos['cliente_id'],
                ':contacto' => $datos['contacto'],
                ':telefono' => $datos['telefono'],
                ':direccion' => $datos['direccion'],
                ':lat' => !empty($datos['latitud']) ? $datos['latitud'] : null,
                ':lon' => !empty($datos['longitud']) ? $datos['longitud'] : null,
                ':descripcion' => $datos['descripcion'],
                ':cantidad' => $datos['cantidad'],
                ':horario' => $datos['horario'],
                ':prioridad' => $datos['prioridad'],
                ':obs' => $obs,
                ':mensajero_id' => !empty($datos['mensajero_id']) ? $datos['mensajero_id'] : null,
                ':estado' => $estado,
                ':creada_por' => $datos['creado_por'] // Nota: en la tabla es 'creada_por' (femenino)
            ]);
        } catch (PDOException $e) {
            // Si la tabla no existe o hay error, lo capturamos
            error_log("Error al crear recolección: " . $e->getMessage());
            return false;
        }
    }

    // Listar recolecciones con filtros
    public function listarRecolecciones($filtros) {
        $sql = "SELECT r.*, 
                       u_cli.nombres as cli_nombres, u_cli.apellidos as cli_apellidos, c.nombre_emprendimiento,
                       u_mens.nombres as mens_nombres, u_mens.apellidos as mens_apellidos
                FROM recolecciones r
                LEFT JOIN clientes c ON r.cliente_id = c.id
                LEFT JOIN usuarios u_cli ON c.usuario_id = u_cli.id
                LEFT JOIN mensajeros m ON r.mensajero_id = m.id
                LEFT JOIN usuarios u_mens ON m.usuario_id = u_mens.id
                WHERE 1=1";
        
        $params = [];

        if (!empty($filtros['busqueda'])) {
            $sql .= " AND (r.direccion_recoleccion LIKE :search OR u_cli.nombres LIKE :search OR c.nombre_emprendimiento LIKE :search)";
            $params[':search'] = '%' . $filtros['busqueda'] . '%';
        }
        if (!empty($filtros['estado'])) {
            $sql .= " AND r.estado = :estado";
            $params[':estado'] = $filtros['estado'];
        }
        if (!empty($filtros['prioridad'])) {
            $sql .= " AND r.prioridad = :prioridad";
            $params[':prioridad'] = $filtros['prioridad'];
        }
        if (!empty($filtros['fecha'])) {
            $sql .= " AND DATE(r.fecha_creacion) = :fecha";
            $params[':fecha'] = $filtros['fecha'];
        }

        $sql .= " GROUP BY r.id ORDER BY r.fecha_creacion ASC";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);
        
        $result = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $cliente = !empty($row['nombre_emprendimiento']) ? $row['nombre_emprendimiento'] : ($row['cli_nombres'] . ' ' . $row['cli_apellidos']);
            $mensajero = $row['mens_nombres'] ? ($row['mens_nombres'] . ' ' . $row['mens_apellidos']) : 'Sin asignar';
            
            // Añadimos el nombre del cliente formateado al array de datos
            $row['cliente_nombre'] = $cliente;
            $row['mensajero_nombre'] = $mensajero;
            
            // Mapeamos campos para que el JS los entienda si espera nombres específicos
            $row['contacto_nombre'] = $row['nombre_contacto']; // Alias para compatibilidad
            
            $result[] = $row;
        }
        return $result;
    }

    public function cancelarRecoleccion($id, $motivo) {
        $sql = "UPDATE recolecciones SET estado = 'cancelada', justificacion_cancelacion = :motivo WHERE id = :id";
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute([':id' => $id, ':motivo' => $motivo]);
    }
}
?>
