<?php
require_once 'conexionGlobal.php';

class PaquetesAdminModel {
    private $conn;

    public function __construct() {
        $this->conn = conexionDB();
    }

    public function getFilters() {
        // Obtener Clientes para el filtro
        $sqlClientes = "SELECT c.id, 
                               COALESCE(NULLIF(c.nombre_emprendimiento, ''), CONCAT(u.nombres, ' ', u.apellidos)) as nombre
                        FROM clientes c
                        JOIN usuarios u ON c.usuario_id = u.id
                        ORDER BY nombre ASC";
        $stmtC = $this->conn->query($sqlClientes);
        $clientes = $stmtC->fetchAll(PDO::FETCH_ASSOC);

        // Obtener Mensajeros para el filtro y asignación
        $sqlMensajeros = "SELECT m.id, CONCAT(u.nombres, ' ', u.apellidos) as nombre, u.estado,
                                 (SELECT COUNT(*) FROM paquetes p WHERE p.mensajero_id = m.id AND p.estado IN ('en_transito', 'asignado')) as tareas_activas
                          FROM usuarios u
                          JOIN mensajeros m ON u.id = m.usuario_id
                          WHERE u.tipo_usuario = 'mensajero'
                          ORDER BY nombre ASC";
        $stmtM = $this->conn->query($sqlMensajeros);
        $mensajeros = $stmtM->fetchAll(PDO::FETCH_ASSOC);

        return ['clientes' => $clientes, 'mensajeros' => $mensajeros];
    }

    public function getPaquetes($filters) {
        // Consulta principal mapeando columnas de BD a lo que espera el JS
        $sql = "SELECT p.id, 
                       p.numero_guia as guia, 
                       p.fecha_creacion as fechaIngreso,
                       COALESCE(NULLIF(c.nombre_emprendimiento, ''), CONCAT(uc.nombres, ' ', uc.apellidos)) as remitente,
                       p.destinatario_nombre as destinatario, 
                       p.destinatario_telefono as telefonoDestinatario,
                       p.direccion_destino as direccion, 
                       '' as zona, 
                       p.estado,
                       CONCAT(um.nombres, ' ', um.apellidos) as mensajero,
                       CONCAT(um_rec.nombres, ' ', um_rec.apellidos) as mensajero_recoleccion,
                       r.estado as estado_recoleccion,
                       p.costo_envio as valor, 
                       p.recaudo_esperado as recaudo,
                       p.tipo_servicio as tipo, 
                       p.instrucciones_entrega as observaciones,
                       CASE WHEN p.tipo_servicio = 'urgente' THEN 1 ELSE 0 END as urgente,
                       0 as problema
                FROM paquetes p
                LEFT JOIN clientes c ON p.cliente_id = c.id
                LEFT JOIN usuarios uc ON c.usuario_id = uc.id
                LEFT JOIN mensajeros m ON p.mensajero_id = m.id
                LEFT JOIN usuarios um ON m.usuario_id = um.id
                LEFT JOIN mensajeros m_rec ON p.mensajero_recoleccion_id = m_rec.id
                LEFT JOIN usuarios um_rec ON m_rec.usuario_id = um_rec.id
                LEFT JOIN recolecciones r ON p.recoleccion_id = r.id
                WHERE 1=1";
        
        $params = [];

        // Aplicar filtros dinámicos
        if (!empty($filters['search'])) {
            $sql .= " AND (p.numero_guia LIKE :search OR p.destinatario_nombre LIKE :search OR p.destinatario_telefono LIKE :search)";
            $params[':search'] = '%' . $filters['search'] . '%';
        }
        if (!empty($filters['fechaDesde'])) {
            $sql .= " AND DATE(p.fecha_creacion) >= :fechaDesde";
            $params[':fechaDesde'] = $filters['fechaDesde'];
        }
        if (!empty($filters['fechaHasta'])) {
            $sql .= " AND DATE(p.fecha_creacion) <= :fechaHasta";
            $params[':fechaHasta'] = $filters['fechaHasta'];
        }
        if (!empty($filters['cliente_id'])) {
            $sql .= " AND p.cliente_id = :cliente_id";
            $params[':cliente_id'] = $filters['cliente_id'];
        }
        if (!empty($filters['estado'])) {
            $sql .= " AND p.estado = :estado";
            $params[':estado'] = $filters['estado'];
        }
        // La columna zona no existe en la tabla paquetes, se comenta el filtro para evitar error
        // if (!empty($filters['zona'])) {
        //    $sql .= " AND p.zona = :zona";
        //    $params[':zona'] = $filters['zona'];
        // }
        if (!empty($filters['mensajero_id'])) {
            $sql .= " AND p.mensajero_id = :mensajero_id";
            $params[':mensajero_id'] = $filters['mensajero_id'];
        }
        if (!empty($filters['tipo'])) {
            $sql .= " AND p.tipo_servicio = :tipo";
            $params[':tipo'] = $filters['tipo'];
        }

        $sql .= " ORDER BY p.fecha_creacion DESC";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getPaqueteDetails($id) {
        $info = null;
        $historial = [];
        $error = null;

        // 1. Obtener información completa del paquete
        try {
            $sqlInfo = "SELECT p.numero_guia, 
                               p.fecha_creacion,
                               COALESCE(NULLIF(c.nombre_emprendimiento, ''), CONCAT(uc.nombres, ' ', uc.apellidos)) as remitente,
                               p.destinatario_nombre, 
                               p.destinatario_telefono,
                               p.direccion_destino, 
                               p.descripcion_contenido,
                               p.tipo_servicio as tipo_paquete,
                               p.costo_envio,
                               p.recaudo_esperado,
                               p.instrucciones_entrega,
                               p.estado,
                               CONCAT(um.nombres, ' ', um.apellidos) as mensajero,
                               CONCAT(um_rec.nombres, ' ', um_rec.apellidos) as mensajero_recoleccion
                        FROM paquetes p
                        LEFT JOIN clientes c ON p.cliente_id = c.id
                        LEFT JOIN usuarios uc ON c.usuario_id = uc.id
                        LEFT JOIN mensajeros m ON p.mensajero_id = m.id
                        LEFT JOIN usuarios um ON m.usuario_id = um.id
                        LEFT JOIN mensajeros m_rec ON p.mensajero_recoleccion_id = m_rec.id
                        LEFT JOIN usuarios um_rec ON m_rec.usuario_id = um_rec.id
                        WHERE p.id = :id";
            
            $stmtInfo = $this->conn->prepare($sqlInfo);
            $stmtInfo->execute([':id' => $id]);
            $info = $stmtInfo->fetch(PDO::FETCH_ASSOC);

            if ($info && $info['estado'] === 'entregado') {
                $sqlEntrega = "SELECT * FROM entregas WHERE paquete_id = :id";
                $stmtEntrega = $this->conn->prepare($sqlEntrega);
                $stmtEntrega->execute([':id' => $id]);
                $entrega = $stmtEntrega->fetch(PDO::FETCH_ASSOC);

                if ($entrega) {
                    $info['infoEntrega'] = [
                        'nombreRecibe' => $entrega['nombre_receptor'] ?? '',
                        'parentesco' => $entrega['parentesco_cargo'] ?? '',
                        'documento' => $entrega['documento_receptor'] ?? '',
                        'recaudo' => $entrega['recaudo_real'] ?? 0,
                        'fecha' => $entrega['fecha_entrega'] ?? '',
                        'observaciones' => $entrega['observaciones'] ?? '',
                        'fotoPrincipal' => $entrega['foto_entrega'] ?? '',
                        'fotoAdicional' => $entrega['foto_adicional'] ?? ''
                    ];
                }
            }
        } catch (PDOException $e) {
            $error = "Error al obtener info: " . $e->getMessage();
        }

        // 2. Obtener historial (solo si tenemos info)
        if ($info) {
            try {
                $sqlHist = "SELECT h.fecha_creacion as fecha, 
                               h.estado_nuevo as estado, 
                               h.observaciones as descripcion,
                               CONCAT(u.nombres, ' ', u.apellidos) as usuario
                        FROM historial_paquetes h
                        LEFT JOIN usuarios u ON h.usuario_id = u.id
                        WHERE h.paquete_id = :id
                        ORDER BY h.fecha_creacion DESC";
                
                $stmtHist = $this->conn->prepare($sqlHist);
                $stmtHist->execute([':id' => $id]);
                $historial = $stmtHist->fetchAll(PDO::FETCH_ASSOC);
            } catch (PDOException $e) {
                // Si falla el historial, no bloqueamos la info principal
                // $error = "Error historial: " . $e->getMessage();
            }
        }

        return ['info' => $info, 'historial' => $historial, 'error' => $error];
    }

    public function updatePaquete($id, $data) {
        $sql = "UPDATE paquetes SET 
                    destinatario_nombre = :destinatario,
                    destinatario_telefono = :telefono,
                    direccion_destino = :direccion,
                    tipo_servicio = :tipo,
                    costo_envio = :valor,
                    instrucciones_entrega = :observaciones
                WHERE id = :id";
        
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute([
            ':destinatario' => $data['destinatario'],
            ':telefono' => $data['telefono'],
            ':direccion' => $data['direccion'],
            ':tipo' => $data['tipo'],
            ':valor' => $data['valor'],
            ':observaciones' => $data['observaciones'],
            ':id' => $id
        ]);
    }

    public function assignMensajero($paqueteId, $mensajeroId, $userId) {
        try {
            $this->conn->beginTransaction();

            // Actualizar paquete
            $sql = "UPDATE paquetes SET mensajero_id = :mensajero_id, estado = 'en_transito' WHERE id = :id";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([':mensajero_id' => $mensajeroId, ':id' => $paqueteId]);

            // Insertar historial (intento seguro)
            try {
                $sqlHist = "INSERT INTO historial_paquetes (paquete_id, estado_anterior, estado_nuevo, usuario_id, observaciones, fecha_creacion)
                            VALUES (:id, 'pendiente', 'en_transito', :user_id, 'Mensajero asignado manualmente', NOW())";
                $stmtHist = $this->conn->prepare($sqlHist);
                $stmtHist->execute([':id' => $paqueteId, ':user_id' => $userId]);
            } catch (PDOException $e) {
                // Continuar si falla el historial
            }

            $this->conn->commit();
            return true;
        } catch (Exception $e) {
            $this->conn->rollBack();
            // Lanzar la excepción para que el controlador la capture y muestre el mensaje de error
            throw new Exception("Error en BD: " . $e->getMessage());
        }
    }
}
?>