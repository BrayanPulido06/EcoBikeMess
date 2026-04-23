<?php
require_once 'conexionGlobal.php';

class AsignarRecoleccionesModel {
    private $conn;

    public function __construct() {
        $this->conn = conexionDB();
    }

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

    public function getMensajeros() {
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

    public function listarRecolecciones($filtros) {
        $sql = "SELECT 
                    p.direccion_origen,
                    p.estado as estado_paquete,
                    MAX(r.estado) as estado_recoleccion,
                    p.mensajero_id,
                    p.mensajero_recoleccion_id,
                    COUNT(*) as cantidad,
                    GROUP_CONCAT(p.id) as ids,
                    GROUP_CONCAT(p.numero_guia SEPARATOR ', ') as guias,
                    MAX(p.fecha_creacion) as fecha_creacion,
                    c.nombre_emprendimiento,
                    u_cli.nombres as cli_nombres, 
                    u_cli.apellidos as cli_apellidos,
                    COALESCE(
                        CONCAT(u_mens_rec.nombres, ' ', u_mens_rec.apellidos),
                        CONCAT(u_mens.nombres, ' ', u_mens.apellidos)
                    ) as mensajero_nombre,
                    CASE
                        WHEN DATE(p.fecha_creacion) < CURDATE() THEN 'verde'
                        WHEN HOUR(p.fecha_creacion) < 13 THEN 'verde'
                        WHEN HOUR(p.fecha_creacion) < 16 THEN 'amarillo'
                        ELSE 'rojo'
                    END as color_prioridad
                FROM paquetes p
                LEFT JOIN clientes c ON p.cliente_id = c.id
                LEFT JOIN usuarios u_cli ON c.usuario_id = u_cli.id
                LEFT JOIN mensajeros m ON p.mensajero_id = m.id
                LEFT JOIN usuarios u_mens ON m.usuario_id = u_mens.id
                LEFT JOIN mensajeros m_rec ON p.mensajero_recoleccion_id = m_rec.id
                LEFT JOIN usuarios u_mens_rec ON m_rec.usuario_id = u_mens_rec.id
                LEFT JOIN recolecciones r ON p.recoleccion_id = r.id
                WHERE p.estado IN ('pendiente', 'asignado', 'en_transito', 'en_ruta', 'entregado')
                  AND COALESCE(TRIM(p.direccion_origen), '') <> ''";

        $params = [];

        if (!empty($filtros['busqueda'])) {
            $sql .= " AND (p.direccion_origen LIKE :search OR u_cli.nombres LIKE :search OR c.nombre_emprendimiento LIKE :search OR u_mens.nombres LIKE :search)";
            $params[':search'] = '%' . $filtros['busqueda'] . '%';
        }

        $sql .= " GROUP BY p.direccion_origen, 
                           IFNULL(p.recoleccion_id, p.estado), 
                           p.mensajero_id,
                           p.mensajero_recoleccion_id,
                           DATE(p.fecha_creacion), 
                           CASE WHEN HOUR(p.fecha_creacion) < 13 THEN 'AM' ELSE 'PM' END 
                  ORDER BY fecha_creacion DESC";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);

        $result = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $cliente = !empty($row['nombre_emprendimiento']) ? $row['nombre_emprendimiento'] : ($row['cli_nombres'] . ' ' . $row['cli_apellidos']);

            $row['cliente_nombre'] = $cliente;
            $row['mensajero_nombre'] = $row['mensajero_nombre'] ? $row['mensajero_nombre'] : 'Sin asignar';

            if (!empty($row['estado_recoleccion'])) {
                $row['estado'] = $row['estado_recoleccion'];
            } else {
                $row['estado'] = $row['estado_paquete'];
            }

            $result[] = $row;
        }
        return $result;
    }

    public function asignarMensajeroPaquetes($ids, $mensajeroId, $creadoPor) {
        if (!preg_match('/^[0-9,]+$/', $ids)) {
            throw new Exception('IDs de paquetes inválidos.');
        }

        try {
            $this->conn->beginTransaction();

            $sqlInfo = "SELECT p.cliente_id, p.direccion_origen, c.nombre_emprendimiento, u.nombres, u.apellidos, u.telefono
                        FROM paquetes p
                        LEFT JOIN clientes c ON p.cliente_id = c.id
                        LEFT JOIN usuarios u ON c.usuario_id = u.id
                        WHERE p.id IN ($ids)
                        LIMIT 1";
            $stmtInfo = $this->conn->query($sqlInfo);
            $info = $stmtInfo->fetch(PDO::FETCH_ASSOC);

            if (!$info) {
                throw new Exception('No se encontraron paquetes para asignar.');
            }

            $sqlRecoleccionExistente = "SELECT recoleccion_id
                                        FROM paquetes
                                        WHERE id IN ($ids)
                                          AND recoleccion_id IS NOT NULL
                                        LIMIT 1";
            $stmtRecoleccionExistente = $this->conn->query($sqlRecoleccionExistente);
            $recoleccionExistenteId = $stmtRecoleccionExistente->fetchColumn();

            $numeroOrden = 'REC-' . date('ymd') . '-' . rand(1000, 9999);
            $contacto = !empty($info['nombre_emprendimiento']) ? $info['nombre_emprendimiento'] : ($info['nombres'] . ' ' . $info['apellidos']);
            $cantidad = count(array_filter(explode(',', $ids)));

            if ($recoleccionExistenteId) {
                $sqlActualizar = "UPDATE recolecciones
                                  SET mensajero_id = :mensajero,
                                      direccion_recoleccion = :direccion,
                                      nombre_contacto = :contacto,
                                      telefono_contacto = :telefono,
                                      cantidad_estimada = :cantidad,
                                      estado = 'asignada',
                                      fecha_asignacion = NOW()
                                  WHERE id = :recoleccion_id";
                $stmtActualizar = $this->conn->prepare($sqlActualizar);
                $stmtActualizar->execute([
                    ':mensajero' => $mensajeroId,
                    ':direccion' => $info['direccion_origen'],
                    ':contacto' => $contacto,
                    ':telefono' => $info['telefono'],
                    ':cantidad' => $cantidad,
                    ':recoleccion_id' => $recoleccionExistenteId
                ]);

                $recoleccionId = $recoleccionExistenteId;
            } else {
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
            }

            $sql = "UPDATE paquetes
                    SET mensajero_recoleccion_id = :mensajero_id,
                        recoleccion_id = :recoleccion_id,
                        estado = 'asignado'
                    WHERE id IN ($ids)";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([
                ':mensajero_id' => $mensajeroId,
                ':recoleccion_id' => $recoleccionId
            ]);

            return $this->conn->commit();
        } catch (Exception $e) {
            if ($this->conn->inTransaction()) {
                $this->conn->rollBack();
            }
            throw $e;
        }
    }

    public function obtenerDetallesPaquetes($ids) {
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

    public function cancelarPaquetes($ids) {
        if (!preg_match('/^[0-9,]+$/', $ids)) return false;
        $sql = "UPDATE paquetes SET estado = 'cancelado' WHERE id IN ($ids)";
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute();
    }

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