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

    private function obtenerClienteOperativoPorUsuario(int $usuarioId): ?int
    {
        $sql = "SELECT c.id
                FROM clientes c
                WHERE c.usuario_id = :usuario_id
                LIMIT 1";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([':usuario_id' => $usuarioId]);
        $clienteId = $stmt->fetchColumn();
        return $clienteId ? (int) $clienteId : null;
    }

    private function obtenerNombreCompletoUsuario(int $usuarioId): string
    {
        $sql = "SELECT CONCAT(nombres, ' ', apellidos) AS nombre
                FROM usuarios
                WHERE id = :usuario_id
                LIMIT 1";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([':usuario_id' => $usuarioId]);
        $nombre = trim((string) $stmt->fetchColumn());
        return $nombre;
    }

    private function construirFiltroPropietario(int $usuarioId, ?int $clienteId, string $nombreCompleto, array &$params): string
    {
        $condiciones = [
            'p.cliente_id = :cliente_id',
            'p.creado_por = :usuario_id'
        ];

        $params[':cliente_id'] = $clienteId ?? 0;
        $params[':usuario_id'] = $usuarioId;

        if ($nombreCompleto !== '') {
            $condiciones[] = "LOWER(TRIM(COALESCE(p.remitente_nombre, ''))) = LOWER(:nombre_completo)";
            $condiciones[] = "LOWER(TRIM(COALESCE(c.nombre_emprendimiento, CONCAT(uc.nombres, ' ', uc.apellidos), ''))) = LOWER(:nombre_completo)";
            $params[':nombre_completo'] = $nombreCompleto;
        }

        return '(' . implode(' OR ', $condiciones) . ')';
    }

    private function construirFiltroRemitenteAsignado(string $nombreCompleto, array &$params): string
    {
        if ($nombreCompleto === '') {
            return '(1 = 0)';
        }

        $params[':nombre_completo'] = $nombreCompleto;

        return "(
            LOWER(TRIM(COALESCE(p.remitente_nombre, ''))) = LOWER(:nombre_completo)
            OR LOWER(TRIM(COALESCE(c.nombre_emprendimiento, CONCAT(uc.nombres, ' ', uc.apellidos), ''))) = LOWER(:nombre_completo)
        )";
    }

    private function condicionEntregaManual(): string
    {
        return "(
            COALESCE(p.observaciones_recoleccion, '') LIKE 'ENTREGA_MANUAL_MENSAJERO%'
            OR COALESCE(p.observaciones_recoleccion, '') LIKE 'Entrega registrada manualmente por mensajero%'
            OR COALESCE(p.descripcion_contenido, '') = 'Entrega creada desde mis paquetes'
        )";
    }

    private function condicionRemitenteReal(): string
    {
        return "TRIM(COALESCE(p.remitente_nombre, '')) NOT IN ('', '-', 'Pendiente por definir')";
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
        $clienteId = $this->obtenerClienteOperativoPorUsuario($usuarioId);
        $nombreCompleto = $this->obtenerNombreCompletoUsuario($usuarioId);
        $params = [':id' => $paqueteId];
        $filtroPropietario = $this->construirFiltroPropietario($usuarioId, $clienteId, $nombreCompleto, $params);
        $filtroRemitenteAsignado = $this->construirFiltroRemitenteAsignado($nombreCompleto, $params);
        $esEntregaManual = $this->condicionEntregaManual();
        $tieneRemitenteReal = $this->condicionRemitenteReal();

        $sql = "SELECT p.*,
                       c.nombre_emprendimiento,
                       CONCAT(um.nombres, ' ', um.apellidos) AS mensajero_asignado
                FROM paquetes p
                LEFT JOIN clientes c ON p.cliente_id = c.id
                LEFT JOIN usuarios uc ON c.usuario_id = uc.id
                LEFT JOIN mensajeros m ON p.mensajero_id = m.id
                LEFT JOIN usuarios um ON m.usuario_id = um.id
                WHERE p.id = :id
                  AND (
                      (NOT {$esEntregaManual} AND {$filtroPropietario})
                      OR ({$esEntregaManual} AND {$tieneRemitenteReal} AND {$filtroRemitenteAsignado})
                  )
                LIMIT 1";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);

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

    public function listarPedidos(int $usuarioId, array $filtros = []): array
    {
        $clienteId = $this->obtenerClienteOperativoPorUsuario($usuarioId);
        $nombreCompleto = $this->obtenerNombreCompletoUsuario($usuarioId);
        $params = [];
        $filtroPropietario = $this->construirFiltroPropietario($usuarioId, $clienteId, $nombreCompleto, $params);
        $filtroRemitenteAsignado = $this->construirFiltroRemitenteAsignado($nombreCompleto, $params);
        $esEntregaManual = $this->condicionEntregaManual();
        $tieneRemitenteReal = $this->condicionRemitenteReal();

        $sql = "SELECT p.*,
                       CONCAT(um.nombres, ' ', um.apellidos) AS mensajero_asignado
                FROM paquetes p
                LEFT JOIN clientes c ON p.cliente_id = c.id
                LEFT JOIN usuarios uc ON c.usuario_id = uc.id
                LEFT JOIN mensajeros m ON p.mensajero_id = m.id
                LEFT JOIN usuarios um ON m.usuario_id = um.id
                WHERE (
                      (NOT {$esEntregaManual} AND {$filtroPropietario})
                      OR ({$esEntregaManual} AND {$tieneRemitenteReal} AND {$filtroRemitenteAsignado})
                  )";
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
}
