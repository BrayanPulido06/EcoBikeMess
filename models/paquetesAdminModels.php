<?php
require_once 'conexionGlobal.php';

class PaquetesAdminModel {
    private $conn;

    public function __construct() {
        $this->conn = conexionDB();
    }

    private function columnExists(string $table, string $column): bool
    {
        try {
            $stmt = $this->conn->prepare("SHOW COLUMNS FROM {$table} LIKE :col");
            $stmt->execute([':col' => $column]);
            return (bool) $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (Throwable $e) {
            return false;
        }
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
                       CONCAT(um.nombres, ' ', um.apellidos) as mensajero_entrega,
                       CONCAT(um_rec.nombres, ' ', um_rec.apellidos) as mensajero_recoleccion,
                       r.estado as estado_recoleccion,
                       p.envio_destinatario as envio_destinatario,
                       p.costo_envio as costo_envio,
                       p.recaudo_esperado as recaudo_esperado,
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
        if (empty($filters['estado'])) {
            $sql .= " AND p.estado <> 'cancelado'";
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
        $imagenes = [];
        $novedades = [];
        $error = null;

        // 1. Obtener información completa del paquete
        try {
            $sqlInfo = "SELECT p.numero_guia, 
                               p.id as paquete_id,
                               p.fecha_creacion,
                               COALESCE(NULLIF(p.remitente_nombre, ''), NULLIF(c.nombre_emprendimiento, ''), CONCAT(uc.nombres, ' ', uc.apellidos)) as remitente,
                               p.remitente_nombre as remitente_editable,
                               p.destinatario_nombre, 
                               p.destinatario_telefono,
                               p.destinatario_telefono2,
                               p.direccion_destino, 
                               p.descripcion_contenido,
                               p.dimensiones,
                               p.envio_destinatario,
                               p.tipo_servicio as tipo_paquete,
                               p.costo_envio,
                               p.recaudo_esperado,
                               p.instrucciones_entrega,
                               p.estado,
                               p.mensajero_id,
                               p.mensajero_recoleccion_id,
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

            if ($info && $info['estado'] === 'cancelado') {
                // infoCancelacion se rellenará desde el historial de novedades (más abajo)
            }
        } catch (PDOException $e) {
            $error = "Error al obtener info: " . $e->getMessage();
        }

        // 1.5 Obtener historial de novedades (aplazado/cancelado)
        if ($info) {
            try {
                $hasFotoAdicional = $this->columnExists('novedades_entrega', 'foto_adicional');
                $selectFotoAdicional = $hasFotoAdicional ? ", n.foto_adicional" : ", NULL as foto_adicional";

                $sqlNov = "SELECT n.id,
                                  n.tipo,
                                  n.descripcion,
                                  n.foto_evidencia
                                  {$selectFotoAdicional},
                                  n.fecha_registro,
                                  CONCAT(u.nombres, ' ', u.apellidos) AS mensajero
                           FROM novedades_entrega n
                           LEFT JOIN mensajeros m ON n.mensajero_id = m.id
                           LEFT JOIN usuarios u ON m.usuario_id = u.id
                           WHERE n.paquete_id = :id
                           ORDER BY n.fecha_registro DESC";
                $stmtNov = $this->conn->prepare($sqlNov);
                $stmtNov->execute([':id' => $id]);
                $novedades = $stmtNov->fetchAll(PDO::FETCH_ASSOC);

                // Mantener compatibilidad: infoCancelacion = última cancelación si existe
                foreach ($novedades as $nov) {
                    if (($nov['tipo'] ?? '') === 'cancelado') {
                        $info['infoCancelacion'] = [
                            'motivo' => $nov['descripcion'] ?? '',
                            'foto' => $nov['foto_evidencia'] ?? '',
                            'fecha' => $nov['fecha_registro'] ?? '',
                            'mensajero' => $nov['mensajero'] ?? ''
                        ];
                        break;
                    }
                }
            } catch (Throwable $e) {
                // No bloquear detalles si falla novedades
            }
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

        // 3. Obtener imágenes adicionales del paquete
        if ($info) {
            try {
                $sqlImgs = "SELECT id, tipo, ruta_archivo, fecha_subida
                            FROM paquete_imagenes
                            WHERE paquete_id = :id
                            ORDER BY fecha_subida DESC";
                $stmtImgs = $this->conn->prepare($sqlImgs);
                $stmtImgs->execute([':id' => $id]);
                $imagenes = $stmtImgs->fetchAll(PDO::FETCH_ASSOC);
            } catch (PDOException $e) {
                // No bloquear detalles si falla imágenes
            }
        }

        return ['info' => $info, 'historial' => $historial, 'imagenes' => $imagenes, 'novedades' => $novedades, 'error' => $error];
    }

    public function updatePaqueteAdmin($id, $data) {
        $sql = "UPDATE paquetes SET 
                    numero_guia = :numero_guia,
                    remitente_nombre = :remitente_nombre,
                    destinatario_nombre = :destinatario,
                    destinatario_telefono = :telefono,
                    direccion_destino = :direccion,
                    descripcion_contenido = :contenido,
                    tipo_servicio = :tipo,
                    costo_envio = :valor,
                    recaudo_esperado = :recaudo,
                    instrucciones_entrega = :observaciones,
                    estado = :estado,
                    mensajero_id = :mensajero_id,
                    mensajero_recoleccion_id = :mensajero_recoleccion_id,
                    fecha_creacion = :fecha_creacion
                WHERE id = :id";
        
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute([
            ':numero_guia' => $data['numero_guia'],
            ':remitente_nombre' => $data['remitente_nombre'],
            ':destinatario' => $data['destinatario_nombre'],
            ':telefono' => $data['destinatario_telefono'],
            ':direccion' => $data['direccion_destino'],
            ':contenido' => $data['descripcion_contenido'],
            ':tipo' => $data['tipo_servicio'],
            ':valor' => $data['costo_envio'],
            ':recaudo' => $data['recaudo_esperado'],
            ':observaciones' => $data['instrucciones_entrega'],
            ':estado' => $data['estado'],
            ':mensajero_id' => $data['mensajero_id'] ?: null,
            ':mensajero_recoleccion_id' => $data['mensajero_recoleccion_id'] ?: null,
            ':fecha_creacion' => $data['fecha_creacion'] ?: null,
            ':id' => $id
        ]);
    }

    public function updateEntregaInfo($paqueteId, $data) {
        $sql = "UPDATE entregas SET
                    nombre_receptor = :nombre_receptor,
                    parentesco_cargo = :parentesco,
                    documento_receptor = :documento,
                    recaudo_real = :recaudo,
                    fecha_entrega = :fecha_entrega,
                    observaciones = :observaciones
                WHERE paquete_id = :id";
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute([
            ':nombre_receptor' => $data['nombre_receptor'],
            ':parentesco' => $data['parentesco_cargo'] ?: null,
            ':documento' => $data['documento_receptor'] ?: null,
            ':recaudo' => $data['recaudo_real'],
            ':fecha_entrega' => $data['fecha_entrega'] ?: null,
            ':observaciones' => $data['observaciones'] ?: null,
            ':id' => $paqueteId
        ]);
    }

    public function updateCancelacionInfo($paqueteId, $data) {
        $sqlId = "SELECT id FROM novedades_entrega
                  WHERE paquete_id = :id AND tipo = 'cancelado'
                  ORDER BY fecha_registro DESC
                  LIMIT 1";
        $stmtId = $this->conn->prepare($sqlId);
        $stmtId->execute([':id' => $paqueteId]);
        $row = $stmtId->fetch(PDO::FETCH_ASSOC);
        if (!$row) {
            return false;
        }

        $sql = "UPDATE novedades_entrega SET descripcion = :descripcion WHERE id = :id";
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute([
            ':descripcion' => $data['descripcion'],
            ':id' => $row['id']
        ]);
    }

    public function addPaqueteImagen($paqueteId, $tipo, $ruta, $userId) {
        $sql = "INSERT INTO paquete_imagenes (paquete_id, tipo, ruta_archivo, creado_por)
                VALUES (:paquete_id, :tipo, :ruta, :creado_por)";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([
            ':paquete_id' => $paqueteId,
            ':tipo' => $tipo,
            ':ruta' => $ruta,
            ':creado_por' => $userId ?: null
        ]);
        return $this->conn->lastInsertId();
    }

    public function getPaqueteImagenById($imageId) {
        $sql = "SELECT id, ruta_archivo FROM paquete_imagenes WHERE id = :id";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([':id' => $imageId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function deletePaqueteImagen($imageId) {
        $sql = "DELETE FROM paquete_imagenes WHERE id = :id";
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute([':id' => $imageId]);
    }

    public function getEntregaFotos($paqueteId) {
        $sql = "SELECT foto_entrega, foto_adicional FROM entregas WHERE paquete_id = :id";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([':id' => $paqueteId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function updateEntregaFoto($paqueteId, $campo, $ruta) {
        if (!in_array($campo, ['foto_entrega', 'foto_adicional'], true)) {
            return false;
        }
        $sql = "UPDATE entregas SET {$campo} = :ruta WHERE paquete_id = :id";
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute([':ruta' => $ruta, ':id' => $paqueteId]);
    }

    public function getCancelacionFoto($paqueteId) {
        $sql = "SELECT foto_evidencia FROM novedades_entrega
                WHERE paquete_id = :id AND tipo = 'cancelado'
                ORDER BY fecha_registro DESC
                LIMIT 1";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([':id' => $paqueteId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function updateCancelacionFoto($paqueteId, $ruta) {
        $sqlId = "SELECT id FROM novedades_entrega
                  WHERE paquete_id = :id AND tipo = 'cancelado'
                  ORDER BY fecha_registro DESC
                  LIMIT 1";
        $stmtId = $this->conn->prepare($sqlId);
        $stmtId->execute([':id' => $paqueteId]);
        $row = $stmtId->fetch(PDO::FETCH_ASSOC);
        if (!$row) {
            return false;
        }
        $sql = "UPDATE novedades_entrega SET foto_evidencia = :ruta WHERE id = :id";
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute([':ruta' => $ruta, ':id' => $row['id']]);
    }

    public function assignMensajero($paqueteId, $mensajeroId, $userId) {
        try {
            $this->conn->beginTransaction();

            // Actualizar paquete
            $sql = "UPDATE paquetes SET mensajero_id = :mensajero_id, estado = 'en_transito', fecha_asignacion = NOW() WHERE id = :id";
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
