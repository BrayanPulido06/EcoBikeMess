<?php
require_once __DIR__ . '/conexionGlobal.php';

class MisPedidosMensajeroModel
{
    private PDO $conn;

    public function __construct()
    {
        $this->conn = conexionDB();
    }

    public function obtenerMensajeroPorUsuario(int $usuarioId): ?array
    {
        $sql = "SELECT m.id, u.nombres, u.apellidos
                FROM mensajeros m
                INNER JOIN usuarios u ON u.id = m.usuario_id
                WHERE m.usuario_id = :usuario_id
                LIMIT 1";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([':usuario_id' => $usuarioId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function listarPedidos(int $usuarioId, array $filtros = []): array
    {
        $sql = "SELECT p.*,
                       CONCAT(um.nombres, ' ', um.apellidos) AS mensajero_asignado
                FROM paquetes p
                LEFT JOIN mensajeros m ON p.mensajero_id = m.id
                LEFT JOIN usuarios um ON m.usuario_id = um.id
                WHERE p.creado_por = :usuario_id
                  AND COALESCE(p.observaciones_recoleccion, '') NOT LIKE 'ENTREGA_MANUAL_MENSAJERO%'
                  AND COALESCE(p.observaciones_recoleccion, '') NOT LIKE 'Entrega registrada manualmente por mensajero%'";
        $params = [':usuario_id' => $usuarioId];

        if (!empty($filtros['search'])) {
            $sql .= " AND (p.numero_guia LIKE :search OR p.destinatario_nombre LIKE :search OR p.direccion_destino LIKE :search)";
            $params[':search'] = '%' . $filtros['search'] . '%';
        }

        if (!empty($filtros['estado'])) {
            $sql .= " AND p.estado = :estado";
            $params[':estado'] = $filtros['estado'];
        }

        if (!empty($filtros['fechaDesde'])) {
            $sql .= " AND p.fecha_creacion >= :fecha_desde";
            $params[':fecha_desde'] = $filtros['fechaDesde'] . ' 00:00:00';
        }

        if (!empty($filtros['fechaHasta'])) {
            $sql .= " AND p.fecha_creacion <= :fecha_hasta";
            $params[':fecha_hasta'] = $filtros['fechaHasta'] . ' 23:59:59';
        }

        $sql .= " ORDER BY p.fecha_creacion DESC";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function obtenerEstadisticas(int $usuarioId, array $filtros = []): array
    {
        $rows = $this->listarPedidos($usuarioId, $filtros);

        $stats = [
            'total' => count($rows),
            'pendientes' => 0,
            'entregados' => 0,
            'cancelados' => 0,
            'valor_envios' => 0.0,
            'valor_recaudos' => 0.0
        ];

        foreach ($rows as $row) {
            $estado = strtolower((string) ($row['estado'] ?? ''));
            if (isset($stats[$estado])) {
                $stats[$estado] += 1;
            } elseif ($estado === 'pendiente' || $estado === 'asignado' || $estado === 'en_transito' || $estado === 'en_ruta') {
                $stats['pendientes'] += 1;
            }

            $stats['valor_envios'] += (float) ($row['costo_envio'] ?? 0);
            $stats['valor_recaudos'] += (float) ($row['recaudo_esperado'] ?? 0);
        }

        return $stats;
    }

    public function obtenerDetalle(int $paqueteId, int $usuarioId): ?array
    {
        $sql = "SELECT p.*,
                       c.nombre_emprendimiento,
                       CONCAT(um.nombres, ' ', um.apellidos) AS mensajero_asignado
                FROM paquetes p
                LEFT JOIN clientes c ON p.cliente_id = c.id
                LEFT JOIN mensajeros m ON p.mensajero_id = m.id
                LEFT JOIN usuarios um ON m.usuario_id = um.id
                WHERE p.id = :id
                  AND p.creado_por = :usuario_id
                  AND COALESCE(p.observaciones_recoleccion, '') NOT LIKE 'ENTREGA_MANUAL_MENSAJERO%'
                  AND COALESCE(p.observaciones_recoleccion, '') NOT LIKE 'Entrega registrada manualmente por mensajero%'
                LIMIT 1";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([
            ':id' => $paqueteId,
            ':usuario_id' => $usuarioId
        ]);

        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) {
            return null;
        }

        if (($row['estado'] ?? '') === 'entregado') {
            $sqlEntrega = "SELECT * FROM entregas WHERE paquete_id = :id LIMIT 1";
            $stmtEntrega = $this->conn->prepare($sqlEntrega);
            $stmtEntrega->execute([':id' => $paqueteId]);
            $entrega = $stmtEntrega->fetch(PDO::FETCH_ASSOC);

            if ($entrega) {
                $row['infoEntrega'] = [
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

        if (($row['estado'] ?? '') === 'cancelado') {
            $sqlCancel = "SELECT n.descripcion,
                                 n.foto_evidencia,
                                 n.fecha_registro,
                                 CONCAT(u.nombres, ' ', u.apellidos) AS mensajero
                          FROM novedades_entrega n
                          LEFT JOIN mensajeros m ON n.mensajero_id = m.id
                          LEFT JOIN usuarios u ON m.usuario_id = u.id
                          WHERE n.paquete_id = :id
                            AND n.tipo = 'cancelado'
                          ORDER BY n.fecha_registro DESC
                          LIMIT 1";
            $stmtCancel = $this->conn->prepare($sqlCancel);
            $stmtCancel->execute([':id' => $paqueteId]);
            $cancel = $stmtCancel->fetch(PDO::FETCH_ASSOC);

            if ($cancel) {
                $row['infoCancelacion'] = [
                    'motivo' => $cancel['descripcion'] ?? '',
                    'foto' => $cancel['foto_evidencia'] ?? '',
                    'fecha' => $cancel['fecha_registro'] ?? '',
                    'mensajero' => $cancel['mensajero'] ?? ''
                ];
            }
        }

        return $row;
    }
}
