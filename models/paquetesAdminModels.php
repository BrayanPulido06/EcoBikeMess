<?php
require_once 'conexionGlobal.php';

class PaquetesAdminModel {
    private $conn;

    public function __construct() {
        $this->conn = conexionDB();
    }

    // Obtener listas para los filtros (Clientes y Mensajeros)
    public function getFilters() {
        // Obtener Clientes
        $sqlClientes = "SELECT c.id, u.nombres, u.apellidos, c.nombre_emprendimiento 
                        FROM clientes c 
                        JOIN usuarios u ON c.usuario_id = u.id 
                        ORDER BY u.nombres ASC";
        $stmt = $this->conn->prepare($sqlClientes);
        $stmt->execute();
        $clientes = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $nombre = !empty($row['nombre_emprendimiento']) ? $row['nombre_emprendimiento'] : ($row['nombres'] . ' ' . $row['apellidos']);
            $clientes[] = ['id' => $row['id'], 'nombre' => $nombre];
        }

        // Obtener Mensajeros
        $sqlMensajeros = "SELECT m.id, u.nombres, u.apellidos, m.estado 
                          FROM mensajeros m 
                          JOIN usuarios u ON m.usuario_id = u.id 
                          ORDER BY u.nombres ASC";
        $stmt = $this->conn->prepare($sqlMensajeros);
        $stmt->execute();
        $mensajeros = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            // Contar tareas activas (estados en curso)
            $sqlTareas = "SELECT COUNT(*) as total FROM paquetes WHERE mensajero_id = :mid AND estado IN ('asignado', 'en_curso', 'en_transito')";
            $stmtT = $this->conn->prepare($sqlTareas);
            $stmtT->execute([':mid' => $row['id']]);
            $tareas = $stmtT->fetch(PDO::FETCH_ASSOC)['total'];

            $mensajeros[] = [
                'id' => $row['id'],
                'nombre' => $row['nombres'] . ' ' . $row['apellidos'],
                'estado' => $row['estado'] ?? 'activo',
                'tareas_activas' => $tareas
            ];
        }

        return ['clientes' => $clientes, 'mensajeros' => $mensajeros];
    }

    // Obtener lista de paquetes con filtros aplicados
    public function getPaquetes($filters) {
        $sql = "SELECT e.*, 
                       u_cli.nombres as rem_nombres, u_cli.apellidos as rem_apellidos, c.nombre_emprendimiento,
                       u_mens.nombres as mens_nombres, u_mens.apellidos as mens_apellidos
                FROM paquetes e
                LEFT JOIN clientes c ON e.cliente_id = c.id
                LEFT JOIN usuarios u_cli ON c.usuario_id = u_cli.id
                LEFT JOIN mensajeros m ON e.mensajero_id = m.id
                LEFT JOIN usuarios u_mens ON m.usuario_id = u_mens.id
                WHERE 1=1";
        
        $params = [];

        if (!empty($filters['search'])) {
            $sql .= " AND (e.numero_guia LIKE :search OR e.destinatario_nombre LIKE :search OR u_cli.nombres LIKE :search OR c.nombre_emprendimiento LIKE :search)";
            $params[':search'] = '%' . $filters['search'] . '%';
        }
        if (!empty($filters['fechaDesde'])) {
            $sql .= " AND DATE(e.fecha_creacion) >= :desde";
            $params[':desde'] = $filters['fechaDesde'];
        }
        if (!empty($filters['fechaHasta'])) {
            $sql .= " AND DATE(e.fecha_creacion) <= :hasta";
            $params[':hasta'] = $filters['fechaHasta'];
        }
        if (!empty($filters['cliente_id'])) {
            $sql .= " AND e.cliente_id = :cid";
            $params[':cid'] = $filters['cliente_id'];
        }
        if (!empty($filters['estado'])) {
            $sql .= " AND e.estado = :estado";
            $params[':estado'] = $filters['estado'];
        }
        if (!empty($filters['zona'])) {
            $sql .= " AND e.zona = :zona";
            $params[':zona'] = $filters['zona'];
        }
        if (!empty($filters['mensajero_id'])) {
            $sql .= " AND e.mensajero_id = :mid";
            $params[':mid'] = $filters['mensajero_id'];
        }
        if (!empty($filters['tipo'])) {
            $sql .= " AND e.tipo_paquete = :tipo";
            $params[':tipo'] = $filters['tipo'];
        }

        $sql .= " ORDER BY e.fecha_creacion DESC";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);
        
        $result = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $remitente = !empty($row['nombre_emprendimiento']) ? $row['nombre_emprendimiento'] : ($row['rem_nombres'] . ' ' . $row['rem_apellidos']);
            $mensajero = $row['mens_nombres'] ? ($row['mens_nombres'] . ' ' . $row['mens_apellidos']) : null;
            
            $result[] = [
                'id' => $row['id'],
                'guia' => $row['numero_guia'],
                'fechaIngreso' => $row['fecha_creacion'],
                'remitente' => $remitente,
                'destinatario' => $row['destinatario_nombre'],
                'telefonoDestinatario' => $row['destinatario_telefono'],
                'direccion' => $row['direccion_destino'],
                'zona' => $row['zona'],
                'estado' => $row['estado'],
                'mensajero' => $mensajero,
                'valor' => $row['costo_envio'],
                'tipo' => $row['tipo_paquete'],
                'urgente' => (stripos($row['tipo_paquete'], 'urgente') !== false || stripos($row['tipo_paquete'], 'express') !== false),
                'problema' => ($row['estado'] === 'problema' || $row['estado'] === 'devolucion'),
                'peso' => $row['peso'],
                'observaciones' => $row['instrucciones_entrega']
            ];
        }
        return $result;
    }

    // Obtener historial de un paquete
    public function getPaqueteDetails($id) {
        $sql = "SELECT h.*, u.nombres, u.apellidos 
                FROM historial_paquetes h 
                LEFT JOIN usuarios u ON h.usuario_id = u.id 
                WHERE h.paquete_id = :id 
                ORDER BY h.fecha_cambio DESC";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([':id' => $id]);
        
        $historial = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $historial[] = [
                'fecha' => $row['fecha_cambio'],
                'estado' => $row['estado_nuevo'],
                'descripcion' => $row['descripcion'],
                'usuario' => $row['nombres'] . ' ' . $row['apellidos']
            ];
        }
        return $historial;
    }

    // Actualizar datos del paquete
    public function updatePaquete($id, $data) {
        $sql = "UPDATE paquetes SET 
                destinatario_nombre = :dest,
                destinatario_telefono = :tel,
                direccion_destino = :dir,
                zona = :zona,
                tipo_paquete = :tipo,
                costo_envio = :valor,
                peso = :peso,
                instrucciones_entrega = :obs
                WHERE id = :id";
        
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute([
            ':dest' => $data['destinatario'],
            ':tel' => $data['telefono'],
            ':dir' => $data['direccion'],
            ':zona' => $data['zona'],
            ':tipo' => $data['tipo'],
            ':valor' => $data['valor'],
            ':peso' => $data['peso'],
            ':obs' => $data['observaciones'],
            ':id' => $id
        ]);
    }

    // Asignar mensajero
    public function assignMensajero($paqueteId, $mensajeroId, $usuarioAdminId) {
        try {
            $this->conn->beginTransaction();

            // Actualizar envío
            $sql = "UPDATE paquetes SET mensajero_id = :mid, estado = 'asignado' WHERE id = :pid";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([':mid' => $mensajeroId, ':pid' => $paqueteId]);

            // Registrar en historial
            $sqlHist = "INSERT INTO historial_paquetes (paquete_id, estado_anterior, estado_nuevo, descripcion, fecha_cambio, usuario_id) 
                        VALUES (:pid, 'pendiente', 'asignado', 'Mensajero asignado manualmente por admin', NOW(), :uid)";
            $stmtH = $this->conn->prepare($sqlHist);
            $stmtH->execute([':pid' => $paqueteId, ':uid' => $usuarioAdminId]);

            $this->conn->commit();
            return true;
        } catch (Exception $e) {
            $this->conn->rollBack();
            return false;
        }
    }
}
?>
