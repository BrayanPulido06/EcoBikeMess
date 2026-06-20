<?php
require_once 'conexionGlobal.php';

class PaquetesAdminModel {
    private $conn;

    public function __construct() {
        $this->conn = conexionDB();
        $this->ensureEntregaAdditionalColumns();
        $this->ensureChecklistVerdeColumn();
        $this->ensureNovedadesAdminSupport();
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

    private function ensureEntregaAdditionalColumns(): void
    {
        $columns = [
            'recibio_cambios' => "ALTER TABLE entregas ADD COLUMN recibio_cambios TINYINT(1) NOT NULL DEFAULT 0 AFTER recaudo_real"
        ];

        foreach ($columns as $column => $sql) {
            if (!$this->columnExists('entregas', $column)) {
                try {
                    $this->conn->exec($sql);
                } catch (Throwable $e) {
                    // No bloqueamos la app si la alteración falla.
                }
            }
        }
    }

    private function ensureNovedadesAdminSupport(): void
    {
        try {
            $stmt = $this->conn->query("SHOW COLUMNS FROM novedades_entrega LIKE 'mensajero_id'");
            $column = $stmt ? $stmt->fetch(PDO::FETCH_ASSOC) : null;
            if ($column && strtoupper((string) ($column['Null'] ?? 'NO')) === 'NO') {
                $this->conn->exec("ALTER TABLE novedades_entrega MODIFY COLUMN mensajero_id INT NULL");
            }
        } catch (Throwable $e) {
            // No bloquear la app si falla el ajuste.
        }
    }
    private function ensureChecklistVerdeColumn(): void
    {
        if (!$this->columnExists('paquetes', 'checklist_verde')) {
            try {
                $this->conn->exec("ALTER TABLE paquetes ADD COLUMN checklist_verde TINYINT(1) NOT NULL DEFAULT 0 AFTER fecha_escaneo");
            } catch (Throwable $e) {
                // No bloqueamos la app si la alteración falla.
            }
        }
    }


    private function getEntregaRowId(int $paqueteId): ?int
    {
        try {
            $stmt = $this->conn->prepare("SELECT id FROM entregas WHERE paquete_id = :id ORDER BY id DESC LIMIT 1");
            $stmt->execute([':id' => $paqueteId]);
            $value = $stmt->fetchColumn();
            return $value ? (int) $value : null;
        } catch (Throwable $e) {
            return null;
        }
    }

    private function buscarClientePorRemitente(string $remitente): ?array
    {
        $remitente = trim($remitente);
        if ($remitente === '' || $remitente === '-') {
            return null;
        }

        $sql = "SELECT c.id,
                       COALESCE(NULLIF(c.nombre_emprendimiento, ''), CONCAT(u.nombres, ' ', u.apellidos)) AS nombre
                FROM clientes c
                LEFT JOIN usuarios u ON c.usuario_id = u.id
                WHERE COALESCE(NULLIF(c.nombre_emprendimiento, ''), CONCAT(u.nombres, ' ', u.apellidos)) = :nombre
                LIMIT 1";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([':nombre' => $remitente]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    private function buscarMensajeroPorNombre(string $nombre): ?array
    {
        $nombre = trim($nombre);
        if ($nombre === '' || $nombre === '-') {
            return null;
        }

        $sql = "SELECT m.id AS mensajero_id,
                       u.id AS usuario_id,
                       CONCAT(u.nombres, ' ', u.apellidos) AS nombre
                FROM mensajeros m
                INNER JOIN usuarios u ON u.id = m.usuario_id
                WHERE CONCAT(u.nombres, ' ', u.apellidos) = :nombre
                LIMIT 1";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([':nombre' => $nombre]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    private function obtenerOCrearClienteOperativoMensajero(array $mensajero): int
    {
        $usuarioId = (int) ($mensajero['usuario_id'] ?? 0);
        if ($usuarioId <= 0) {
            return 0;
        }

        $stmt = $this->conn->prepare("SELECT id FROM clientes WHERE usuario_id = :usuario_id LIMIT 1");
        $stmt->execute([':usuario_id' => $usuarioId]);
        $clienteId = $stmt->fetchColumn();
        if ($clienteId) {
            return (int) $clienteId;
        }

        $nombre = trim((string) ($mensajero['nombre'] ?? ''));
        if ($nombre === '') {
            $nombre = 'Mensajero';
        }

        $stmtInsert = $this->conn->prepare(
            "INSERT INTO clientes (
                usuario_id,
                nombre_emprendimiento,
                tipo_producto,
                instagram,
                direccion_principal,
                saldo_pendiente,
                limite_credito
            ) VALUES (
                :usuario_id,
                :nombre_emprendimiento,
                :tipo_producto,
                :instagram,
                :direccion_principal,
                0,
                0
            )"
        );

        $stmtInsert->execute([
            ':usuario_id' => $usuarioId,
            ':nombre_emprendimiento' => 'Operativo Mensajero - ' . $nombre,
            ':tipo_producto' => 'Envios creados por mensajero',
            ':instagram' => 'mensajero',
            ':direccion_principal' => '-'
        ]);

        return (int) $this->conn->lastInsertId();
    }

    private function obtenerClienteActualPaquete(int $paqueteId): ?int
    {
        $stmt = $this->conn->prepare("SELECT cliente_id FROM paquetes WHERE id = :id LIMIT 1");
        $stmt->execute([':id' => $paqueteId]);
        $value = $stmt->fetchColumn();
        return $value ? (int) $value : null;
    }

    private function ensureEntregaRecord(int $paqueteId, ?int $mensajeroId = null): ?int
    {
        $existingId = $this->getEntregaRowId($paqueteId);
        if ($existingId) {
            return $existingId;
        }

        try {
            $stmt = $this->conn->prepare("INSERT INTO entregas (paquete_id, mensajero_id, fecha_entrega) VALUES (:paquete_id, :mensajero_id, NOW())");
            $stmt->execute([
                ':paquete_id' => $paqueteId,
                ':mensajero_id' => $mensajeroId ?: null
            ]);
            return (int) $this->conn->lastInsertId();
        } catch (Throwable $e) {
            return $this->getEntregaRowId($paqueteId);
        }
    }

    private function getUltimaCancelacionId(int $paqueteId): ?int
    {
        $sql = "SELECT id FROM novedades_entrega
                WHERE paquete_id = :id AND tipo = 'cancelado'
                ORDER BY fecha_registro DESC, id DESC
                LIMIT 1";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([':id' => $paqueteId]);
        $value = $stmt->fetchColumn();
        return $value ? (int) $value : null;
    }

    public function ensureCancelacionRecord(int $paqueteId): ?int
    {
        $existingId = $this->getUltimaCancelacionId($paqueteId);
        if ($existingId) {
            return $existingId;
        }

        $sql = "INSERT INTO novedades_entrega (paquete_id, tipo, descripcion, fecha_registro)
                VALUES (:paquete_id, 'cancelado', '', NOW())";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([':paquete_id' => $paqueteId]);
        return (int) $this->conn->lastInsertId();
    }

    public function getFilters() {
        // Obtener Clientes para el filtro
        $sqlClientes = "SELECT MIN(id) as id, nombre
                        FROM (
                            SELECT c.id,
                                   TRIM(CASE
                                       WHEN COALESCE(NULLIF(c.nombre_emprendimiento, ''), '') LIKE 'Operativo Mensajero - %'
                                       THEN COALESCE(
                                           (
                                               SELECT NULLIF(NULLIF(p2.remitente_nombre, 'Pendiente por definir'), '')
                                               FROM paquetes p2
                                               WHERE p2.cliente_id = c.id
                                               ORDER BY p2.fecha_creacion DESC, p2.id DESC
                                               LIMIT 1
                                           ),
                                           NULLIF(TRIM(REPLACE(c.nombre_emprendimiento, 'Operativo Mensajero - ', '')), ''),
                                           CONCAT(u.nombres, ' ', u.apellidos)
                                       )
                                       ELSE COALESCE(NULLIF(c.nombre_emprendimiento, ''), CONCAT(u.nombres, ' ', u.apellidos))
                                   END) as nombre
                            FROM clientes c
                            JOIN usuarios u ON c.usuario_id = u.id
                            UNION ALL
                            SELECT COALESCE(p.cliente_id, 0) AS id,
                                   TRIM(CASE
                                       WHEN COALESCE(p.observaciones_recoleccion, '') LIKE 'ENTREGA_MANUAL_MENSAJERO%'
                                            OR COALESCE(p.observaciones_recoleccion, '') LIKE 'Entrega registrada manualmente por mensajero%'
                                            OR COALESCE(NULLIF(c.nombre_emprendimiento, ''), '') LIKE 'Operativo Mensajero - %'
                                            OR COALESCE(p.descripcion_contenido, '') = 'Entrega creada desde mis paquetes'
                                       THEN COALESCE(NULLIF(NULLIF(p.remitente_nombre, 'Pendiente por definir'), ''), '-')
                                       ELSE COALESCE(NULLIF(c.nombre_emprendimiento, ''), CONCAT(u.nombres, ' ', u.apellidos), '-')
                                   END) AS nombre
                            FROM paquetes p
                            LEFT JOIN clientes c ON p.cliente_id = c.id
                            LEFT JOIN usuarios u ON c.usuario_id = u.id
                        ) clientes_filtro
                        WHERE nombre NOT IN ('', '-', 'Pendiente por definir')
                        GROUP BY nombre
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
        $hasRecibioCambiosEntrega = $this->columnExists('entregas', 'recibio_cambios');

        // Consulta principal mapeando columnas de BD a lo que espera el JS
        $fallbackCambiosExpr = "CASE
            WHEN LOWER(COALESCE(e.observaciones, '')) LIKE '%recibio cambios: si%' THEN 1
            WHEN LOWER(COALESCE(e.observaciones, '')) LIKE '%recibió cambios: sí%' THEN 1
            ELSE 0
        END";

        $sql = "SELECT p.id, 
                       p.numero_guia as guia, 
                       p.fecha_creacion as fechaIngreso,
                       COALESCE(p.checklist_verde, 0) as checklist_verde,
                       CASE
                           WHEN COALESCE(p.observaciones_recoleccion, '') LIKE 'ENTREGA_MANUAL_MENSAJERO%'
                                OR COALESCE(p.observaciones_recoleccion, '') LIKE 'Entrega registrada manualmente por mensajero%'
                                OR COALESCE(NULLIF(c.nombre_emprendimiento, ''), '') LIKE 'Operativo Mensajero - %'
                                OR COALESCE(p.descripcion_contenido, '') = 'Entrega creada desde mis paquetes'
                           THEN COALESCE(NULLIF(NULLIF(p.remitente_nombre, 'Pendiente por definir'), ''), '-')
                           ELSE COALESCE(NULLIF(c.nombre_emprendimiento, ''), CONCAT(uc.nombres, ' ', uc.apellidos), '-')
                       END as remitente,
                       CASE
                           WHEN COALESCE(p.observaciones_recoleccion, '') LIKE 'ENTREGA_MANUAL_MENSAJERO%'
                                OR COALESCE(p.observaciones_recoleccion, '') LIKE 'Entrega registrada manualmente por mensajero%'
                                OR COALESCE(NULLIF(c.nombre_emprendimiento, ''), '') LIKE 'Operativo Mensajero - %'
                                OR COALESCE(p.descripcion_contenido, '') = 'Entrega creada desde mis paquetes'
                           THEN '-'
                           ELSE COALESCE(NULLIF(CONCAT(uc.nombres, ' ', uc.apellidos), ''), '-')
                       END as nombre_persona,
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
                       COALESCE(e.recaudo_real, 0) as recaudo_real,
                       p.descripcion_contenido as nombre_paquete,
                       " . ($hasRecibioCambiosEntrega ? "CASE WHEN COALESCE(e.recibio_cambios, 0) = 1 THEN 1 ELSE {$fallbackCambiosExpr} END" : $fallbackCambiosExpr) . " as recibio_cambios,
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
                LEFT JOIN entregas e ON e.paquete_id = p.id
                WHERE 1=1";
        
        $params = [];

        // Aplicar filtros dinámicos
        if (!empty($filters['search'])) {
            $sql .= " AND (p.numero_guia LIKE :search OR p.destinatario_nombre LIKE :search OR p.destinatario_telefono LIKE :search)";
            $params[':search'] = '%' . $filters['search'] . '%';
        }
        $usarFechaEntrega = !empty($filters['estado']) && $filters['estado'] === 'entregado';
        $campoFechaFiltro = $usarFechaEntrega ? 'e.fecha_entrega' : 'p.fecha_creacion';

        if (!empty($filters['fechaDesde'])) {
            $sql .= " AND DATE({$campoFechaFiltro}) >= :fechaDesde";
            $params[':fechaDesde'] = $filters['fechaDesde'];
        }
        if (!empty($filters['fechaHasta'])) {
            $sql .= " AND DATE({$campoFechaFiltro}) <= :fechaHasta";
            $params[':fechaHasta'] = $filters['fechaHasta'];
        }
        if (!empty($filters['cliente'])) {
            $sql .= " AND CASE
                            WHEN COALESCE(p.observaciones_recoleccion, '') LIKE 'ENTREGA_MANUAL_MENSAJERO%'
                                 OR COALESCE(p.observaciones_recoleccion, '') LIKE 'Entrega registrada manualmente por mensajero%'
                                 OR COALESCE(NULLIF(c.nombre_emprendimiento, ''), '') LIKE 'Operativo Mensajero - %'
                                 OR COALESCE(p.descripcion_contenido, '') = 'Entrega creada desde mis paquetes'
                            THEN COALESCE(NULLIF(NULLIF(p.remitente_nombre, 'Pendiente por definir'), ''), '-')
                            ELSE COALESCE(NULLIF(c.nombre_emprendimiento, ''), CONCAT(uc.nombres, ' ', uc.apellidos), '-')
                         END COLLATE utf8mb4_unicode_ci LIKE :cliente";
            $params[':cliente'] = '%' . trim((string) $filters['cliente']) . '%';
        }
        if (!empty($filters['estado'])) {
            if ($filters['estado'] === 'sin_asignar') {
                $sql .= " AND (p.mensajero_id IS NULL OR p.mensajero_id = 0)";
            } else {
                $sql .= " AND p.estado = :estado";
                $params[':estado'] = $filters['estado'];
            }
        }
        if (empty($filters['estado'])) {
            $sql .= " AND p.estado <> 'cancelado'";
        }
        // La columna zona no existe en la tabla paquetes, se comenta el filtro para evitar error
        // if (!empty($filters['zona'])) {
        //    $sql .= " AND p.zona = :zona";
        //    $params[':zona'] = $filters['zona'];
        // }
        if (!empty($filters['mensajero'])) {
            $sql .= " AND (
                        CONCAT(um.nombres, ' ', um.apellidos) COLLATE utf8mb4_unicode_ci LIKE :mensajero
                        OR CONCAT(um_rec.nombres, ' ', um_rec.apellidos) COLLATE utf8mb4_unicode_ci LIKE :mensajero
                     )";
            $params[':mensajero'] = '%' . trim((string) $filters['mensajero']) . '%';
        }
        if (!empty($filters['recaudo'])) {
            if ($filters['recaudo'] === 'con_recaudo') {
                $sql .= " AND COALESCE(e.recaudo_real, 0) > 0";
            } elseif ($filters['recaudo'] === 'sin_recaudo') {
                $sql .= " AND COALESCE(e.recaudo_real, 0) <= 0";
            }
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

    public function updateChecklistVerde(int $paqueteId, int $checked): bool
    {
        $sql = "UPDATE paquetes SET checklist_verde = :checked WHERE id = :id";
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute([
            ':checked' => $checked === 1 ? 1 : 0,
            ':id' => $paqueteId
        ]);
    }

    public function getPaqueteDetails($id) {
        $info = null;
        $historial = [];
        $imagenes = [];
        $novedades = [];
        $error = null;

        // 1. Obtener información completa del paquete
        try {
            $hasEnvioMismoDia = $this->columnExists('paquetes', 'envio_mismo_dia');
            $hasZonaPeriferica = $this->columnExists('paquetes', 'zona_periferica');
            $hasRecogerCambios = $this->columnExists('paquetes', 'recoger_cambios');

            $sqlInfo = "SELECT p.numero_guia, 
                               p.id as paquete_id,
                               p.fecha_creacion,
                               CASE
                                   WHEN COALESCE(p.observaciones_recoleccion, '') LIKE 'ENTREGA_MANUAL_MENSAJERO%'
                                        OR COALESCE(p.observaciones_recoleccion, '') LIKE 'Entrega registrada manualmente por mensajero%'
                                        OR COALESCE(NULLIF(c.nombre_emprendimiento, ''), '') LIKE 'Operativo Mensajero - %'
                                        OR COALESCE(p.descripcion_contenido, '') = 'Entrega creada desde mis paquetes'
                                   THEN COALESCE(NULLIF(NULLIF(p.remitente_nombre, 'Pendiente por definir'), ''), '-')
                                   ELSE COALESCE(NULLIF(c.nombre_emprendimiento, ''), CONCAT(uc.nombres, ' ', uc.apellidos), '-')
                               END as tienda_nombre,
                               CASE
                                   WHEN COALESCE(p.observaciones_recoleccion, '') LIKE 'ENTREGA_MANUAL_MENSAJERO%'
                                        OR COALESCE(p.observaciones_recoleccion, '') LIKE 'Entrega registrada manualmente por mensajero%'
                                        OR COALESCE(NULLIF(c.nombre_emprendimiento, ''), '') LIKE 'Operativo Mensajero - %'
                                        OR COALESCE(p.descripcion_contenido, '') = 'Entrega creada desde mis paquetes'
                                   THEN COALESCE(NULLIF(NULLIF(p.remitente_nombre, 'Pendiente por definir'), ''), '-')
                                   ELSE COALESCE(NULLIF(c.nombre_emprendimiento, ''), CONCAT(uc.nombres, ' ', uc.apellidos), '-')
                               END as remitente,
                               CASE
                                   WHEN p.remitente_nombre = '-'
                                        OR p.remitente_nombre = 'Pendiente por definir'
                                        OR (
                                            (
                                                COALESCE(p.observaciones_recoleccion, '') LIKE 'ENTREGA_MANUAL_MENSAJERO%'
                                                OR COALESCE(p.observaciones_recoleccion, '') LIKE 'Entrega registrada manualmente por mensajero%'
                                                OR COALESCE(NULLIF(c.nombre_emprendimiento, ''), '') LIKE 'Operativo Mensajero - %'
                                                OR COALESCE(p.descripcion_contenido, '') = 'Entrega creada desde mis paquetes'
                                            )
                                            AND COALESCE(NULLIF(p.remitente_nombre, ''), '-') = '-'
                                        ) THEN ''
                                   ELSE COALESCE(p.remitente_nombre, '')
                               END as remitente_editable,
                               p.destinatario_nombre, 
                               p.destinatario_telefono,
                               p.destinatario_telefono2,
                               p.direccion_destino, 
                               p.descripcion_contenido,
                               p.dimensiones,
                               " . ($hasEnvioMismoDia ? "p.envio_mismo_dia" : "0") . " as envio_mismo_dia,
                               " . ($hasZonaPeriferica ? "p.zona_periferica" : "0") . " as zona_periferica,
                               " . ($hasRecogerCambios ? "p.recoger_cambios" : "0") . " as recoger_cambios,
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

            if ($info) {
                $sqlEntrega = "SELECT * FROM entregas WHERE paquete_id = :id ORDER BY id DESC LIMIT 1";
                $stmtEntrega = $this->conn->prepare($sqlEntrega);
                $stmtEntrega->execute([':id' => $id]);
                $entrega = $stmtEntrega->fetch(PDO::FETCH_ASSOC) ?: [];

                
                    $recibioCambiosEntrega = 0;
                    if (isset($entrega['recibio_cambios'])) {
                        $recibioCambiosEntrega = (int) $entrega['recibio_cambios'] === 1 ? 1 : 0;
                    }
                    if ($recibioCambiosEntrega === 0) {
                        $obsEntrega = strtolower((string) ($entrega['observaciones'] ?? ''));
                        if (strpos($obsEntrega, 'recibio cambios: si') !== false || strpos($obsEntrega, 'recibió cambios: sí') !== false) {
                            $recibioCambiosEntrega = 1;
                        }
                    }

                    $info['infoEntrega'] = [
                        'nombreRecibe' => $entrega['nombre_receptor'] ?? '',
                        'parentesco' => $entrega['parentesco_cargo'] ?? '',
                        'documento' => $entrega['documento_receptor'] ?? '',
                        'recaudo' => isset($entrega['recaudo_real']) ? (float) $entrega['recaudo_real'] : 0,
                        'recibioCambios' => $recibioCambiosEntrega,
                        'fecha' => $entrega['fecha_entrega'] ?? '',
                        'observaciones' => $entrega['observaciones'] ?? '',
                        'fotoPrincipal' => $entrega['foto_entrega'] ?? '',
                        'fotoAdicional' => $entrega['foto_adicional'] ?? ''
                    ];
            }

                // infoCancelacion se rellenará desde el historial de novedades (más abajo)
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
        $clienteAsignado = $this->buscarClientePorRemitente((string) ($data['remitente_nombre'] ?? ''));
        $mensajeroAsignado = $this->buscarMensajeroPorNombre((string) ($data['remitente_nombre'] ?? ''));

        $clienteIdFinal = $clienteAsignado['id'] ?? null;
        $creadoPorFinal = null;

        if ($mensajeroAsignado) {
            $clienteIdFinal = $this->obtenerOCrearClienteOperativoMensajero($mensajeroAsignado);
            $creadoPorFinal = (int) ($mensajeroAsignado['usuario_id'] ?? 0);
        }

        if (!$clienteIdFinal) {
            $clienteIdFinal = $this->obtenerClienteActualPaquete((int) $id);
        }

        $sql = "UPDATE paquetes SET 
                    numero_guia = :numero_guia,
                    cliente_id = :cliente_id,
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
                    fecha_creacion = :fecha_creacion";

        if ($creadoPorFinal !== null && $creadoPorFinal > 0) {
            $sql .= ",
                    creado_por = :creado_por";
        }

        $params = [
            ':numero_guia' => $data['numero_guia'],
            ':cliente_id' => $clienteIdFinal,
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
        ];

        if ($creadoPorFinal !== null && $creadoPorFinal > 0) {
            $params[':creado_por'] = $creadoPorFinal;
        }

        if (!empty($data['fecha_entrega'])) {
            $sql .= ",
                    fecha_entrega = :fecha_entrega";
            $params[':fecha_entrega'] = $data['fecha_entrega'];
        }

        $sql .= "
                WHERE id = :id";
        
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute($params);
    }

    public function updateEntregaInfo($paqueteId, $data) {
        $entregaId = $this->ensureEntregaRecord((int) $paqueteId, (int) ($data['mensajero_id'] ?? 0));
        if (!$entregaId) {
            return false;
        }

        $sql = "UPDATE entregas SET
                    paquete_id = :paquete_id,
                    mensajero_id = :mensajero_id,
                    nombre_receptor = :nombre_receptor,
                    parentesco_cargo = :parentesco,
                    documento_receptor = :documento,
                    recaudo_real = :recaudo,
                    recibio_cambios = :recibio_cambios,
                    fecha_entrega = :fecha_entrega,
                    observaciones = :observaciones
                WHERE id = :entrega_id";
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute([
            ':paquete_id' => $paqueteId,
            ':mensajero_id' => $data['mensajero_id'] ?: null,
            ':nombre_receptor' => $data['nombre_receptor'],
            ':parentesco' => $data['parentesco_cargo'] ?: null,
            ':documento' => $data['documento_receptor'] ?: null,
            ':recaudo' => $data['recaudo_real'],
            ':recibio_cambios' => !empty($data['recibio_cambios']) ? 1 : 0,
            ':fecha_entrega' => $data['fecha_entrega'] ?: null,
            ':observaciones' => $data['observaciones'] ?: null,
            ':entrega_id' => $entregaId
        ]);
    }

    public function updateCancelacionInfo($paqueteId, $data) {
        $cancelacionId = $this->getUltimaCancelacionId((int) $paqueteId);
        if (!$cancelacionId) {
            return false;
        }

        $sql = "UPDATE novedades_entrega SET descripcion = :descripcion WHERE id = :id";
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute([
            ':descripcion' => $data['descripcion'],
            ':id' => $cancelacionId
        ]);
    }

    public function crearEntregaSinRotuloAdmin(array $data): array
    {
        $clienteId = (int) ($data['cliente_id'] ?? 0);
        $mensajeroId = (int) ($data['mensajero_id'] ?? 0);
        $numeroGuia = trim((string) ($data['numero_guia'] ?? ''));
        $destinatario = trim((string) ($data['destinatario_nombre'] ?? ''));
        $nombreReceptor = trim((string) ($data['nombre_receptor'] ?? ''));
        $documento = trim((string) ($data['documento_receptor'] ?? ''));
        $fotoEntrega = trim((string) ($data['foto_entrega'] ?? ''));

        if ($clienteId <= 0 || $mensajeroId <= 0 || $numeroGuia === '' || $destinatario === '' || $nombreReceptor === '' || $documento === '' || $fotoEntrega === '') {
            throw new Exception('Debes completar tienda, mensajero, destinatario, quien recibe, documento/placa y evidencia.');
        }

        $stmtGuia = $this->conn->prepare("SELECT COUNT(*) FROM paquetes WHERE numero_guia = :guia");
        $stmtGuia->execute([':guia' => $numeroGuia]);
        if ((int) $stmtGuia->fetchColumn() > 0) {
            throw new Exception('La guía generada ya existe. Intenta de nuevo.');
        }

        $stmtCliente = $this->conn->prepare(
            "SELECT c.id,
                    COALESCE(NULLIF(c.nombre_emprendimiento, ''), CONCAT(u.nombres, ' ', u.apellidos)) AS nombre,
                    COALESCE(NULLIF(c.direccion_principal, ''), '-') AS direccion
             FROM clientes c
             LEFT JOIN usuarios u ON u.id = c.usuario_id
             WHERE c.id = :id
             LIMIT 1"
        );
        $stmtCliente->execute([':id' => $clienteId]);
        $cliente = $stmtCliente->fetch(PDO::FETCH_ASSOC);
        if (!$cliente) {
            throw new Exception('La tienda seleccionada no existe.');
        }

        $stmtMensajero = $this->conn->prepare("SELECT id FROM mensajeros WHERE id = :id LIMIT 1");
        $stmtMensajero->execute([':id' => $mensajeroId]);
        if (!$stmtMensajero->fetch(PDO::FETCH_ASSOC)) {
            throw new Exception('El mensajero seleccionado no existe.');
        }

        $recaudo = (float) ($data['recaudo_real'] ?? 0);
        $recibioCambios = !empty($data['recibio_cambios']) ? 1 : 0;
        $observaciones = trim((string) ($data['observaciones'] ?? ''));
        $parentesco = trim((string) ($data['parentesco_cargo'] ?? ''));
        $fotoAdicional = trim((string) ($data['foto_adicional'] ?? ''));
        $usuarioId = (int) ($data['creado_por'] ?? 0);

        $this->conn->beginTransaction();
        try {
            $stmtPaquete = $this->conn->prepare(
                "INSERT INTO paquetes (
                    cliente_id, creado_por, numero_guia, remitente_nombre, remitente_telefono,
                    direccion_origen, observaciones_recoleccion, destinatario_nombre,
                    destinatario_telefono, direccion_destino, instrucciones_entrega,
                    descripcion_contenido, dimensiones, envio_mismo_dia, zona_periferica,
                    recoger_cambios, envio_destinatario, tipo_servicio, recaudo_esperado,
                    costo_envio, estado, mensajero_id, fecha_asignacion, fecha_entrega, fecha_creacion
                ) VALUES (
                    :cliente_id, :creado_por, :numero_guia, :remitente_nombre, '0',
                    :direccion_origen, :observaciones_recoleccion, :destinatario_nombre,
                    '0', 'Sin dirección registrada', :instrucciones_entrega,
                    'Entrega sin rótulo registrada por admin', NULL, 0, 0,
                    0, 'no', :tipo_servicio, :recaudo_esperado,
                    0, 'entregado', :mensajero_id, NOW(), NOW(), NOW()
                )"
            );
            $stmtPaquete->execute([
                ':cliente_id' => $clienteId,
                ':creado_por' => $usuarioId,
                ':numero_guia' => $numeroGuia,
                ':remitente_nombre' => $cliente['nombre'] ?: 'Tienda',
                ':direccion_origen' => $cliente['direccion'] ?: '-',
                ':observaciones_recoleccion' => 'ENTREGA_SIN_ROTULO_ADMIN',
                ':destinatario_nombre' => $destinatario,
                ':instrucciones_entrega' => $observaciones,
                ':tipo_servicio' => $recaudo > 0 ? 'contraentrega' : 'entrega_simple',
                ':recaudo_esperado' => $recaudo,
                ':mensajero_id' => $mensajeroId
            ]);

            $paqueteId = (int) $this->conn->lastInsertId();

            $stmtEntrega = $this->conn->prepare(
                "INSERT INTO entregas (
                    paquete_id, mensajero_id, nombre_receptor, parentesco_cargo,
                    documento_receptor, recaudo_real, recibio_cambios, foto_entrega,
                    foto_adicional, observaciones, fecha_entrega
                ) VALUES (
                    :paquete_id, :mensajero_id, :nombre_receptor, :parentesco_cargo,
                    :documento_receptor, :recaudo_real, :recibio_cambios, :foto_entrega,
                    :foto_adicional, :observaciones, NOW()
                )"
            );
            $stmtEntrega->execute([
                ':paquete_id' => $paqueteId,
                ':mensajero_id' => $mensajeroId,
                ':nombre_receptor' => $nombreReceptor,
                ':parentesco_cargo' => $parentesco ?: null,
                ':documento_receptor' => $documento,
                ':recaudo_real' => $recaudo,
                ':recibio_cambios' => $recibioCambios,
                ':foto_entrega' => $fotoEntrega,
                ':foto_adicional' => $fotoAdicional !== '' ? $fotoAdicional : null,
                ':observaciones' => $observaciones !== '' ? $observaciones : null
            ]);

            $numeroComprobante = 'COMP-' . date('dmY') . '-' . str_pad((string) $paqueteId, 6, '0', STR_PAD_LEFT);
            $stmtComprobante = $this->conn->prepare(
                "INSERT INTO comprobantes (
                    paquete_id, cliente_id, numero_comprobante, numero_guia,
                    nombre_receptor, parentesco_cargo, recaudo, observaciones, foto_entrega
                ) VALUES (
                    :paquete_id, :cliente_id, :numero_comprobante, :numero_guia,
                    :nombre_receptor, :parentesco_cargo, :recaudo, :observaciones, :foto_entrega
                )"
            );
            $stmtComprobante->execute([
                ':paquete_id' => $paqueteId,
                ':cliente_id' => $clienteId,
                ':numero_comprobante' => $numeroComprobante,
                ':numero_guia' => $numeroGuia,
                ':nombre_receptor' => $nombreReceptor,
                ':parentesco_cargo' => $parentesco ?: null,
                ':recaudo' => $recaudo,
                ':observaciones' => $observaciones !== '' ? $observaciones : null,
                ':foto_entrega' => $fotoEntrega
            ]);

            $this->conn->commit();
            return ['paquete_id' => $paqueteId, 'guia' => $numeroGuia];
        } catch (Throwable $e) {
            if ($this->conn->inTransaction()) {
                $this->conn->rollBack();
            }
            throw $e;
        }
    }

    public function cancelarServicioAdmin(int $paqueteId, string $motivo, string $fotoRuta, int $usuarioId = 0): bool
    {
        $motivo = trim($motivo);
        if ($paqueteId <= 0 || $motivo === '') {
            throw new Exception('Debes indicar un motivo para cancelar el servicio.');
        }

        try {
            $this->conn->beginTransaction();

            $stmtPaquete = $this->conn->prepare("SELECT id, estado, mensajero_id FROM paquetes WHERE id = :id LIMIT 1");
            $stmtPaquete->execute([':id' => $paqueteId]);
            $paquete = $stmtPaquete->fetch(PDO::FETCH_ASSOC);

            if (!$paquete) {
                throw new Exception('El paquete no existe.');
            }

            $stmtNovedad = $this->conn->prepare(
                "INSERT INTO novedades_entrega (paquete_id, mensajero_id, tipo, descripcion, foto_evidencia, fecha_registro)
                 VALUES (:paquete_id, :mensajero_id, 'cancelado', :descripcion, :foto_evidencia, NOW())"
            );
            $stmtNovedad->execute([
                ':paquete_id' => $paqueteId,
                ':mensajero_id' => !empty($paquete['mensajero_id']) ? (int) $paquete['mensajero_id'] : null,
                ':descripcion' => $motivo,
                ':foto_evidencia' => $fotoRuta
            ]);

            $stmtUpdate = $this->conn->prepare("UPDATE paquetes SET estado = 'cancelado' WHERE id = :id");
            $stmtUpdate->execute([':id' => $paqueteId]);

            try {
                $stmtHist = $this->conn->prepare(
                    "INSERT INTO historial_paquetes (paquete_id, estado_anterior, estado_nuevo, usuario_id, observaciones, fecha_creacion)
                     VALUES (:paquete_id, :estado_anterior, 'cancelado', :usuario_id, :observaciones, NOW())"
                );
                $stmtHist->execute([
                    ':paquete_id' => $paqueteId,
                    ':estado_anterior' => $paquete['estado'] ?? '',
                    ':usuario_id' => $usuarioId > 0 ? $usuarioId : null,
                    ':observaciones' => $motivo
                ]);
            } catch (Throwable $e) {
                // No bloqueamos si el historial falla.
            }

            $this->conn->commit();
            return true;
        } catch (Throwable $e) {
            if ($this->conn->inTransaction()) {
                $this->conn->rollBack();
            }
            throw $e;
        }
    }

    public function getPaqueteResumenParaEliminar(int $paqueteId): ?array
    {
        $stmt = $this->conn->prepare(
            "SELECT id, numero_guia, destinatario_nombre, descripcion_contenido
             FROM paquetes
             WHERE id = :id
             LIMIT 1"
        );
        $stmt->execute([':id' => $paqueteId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function eliminarPaqueteAdmin(int $paqueteId): array
    {
        if ($paqueteId <= 0) {
            throw new Exception('ID de paquete inválido.');
        }

        $resumen = $this->getPaqueteResumenParaEliminar($paqueteId);
        if (!$resumen) {
            throw new Exception('El paquete no existe.');
        }

        $rutas = [];

        try {
            $this->conn->beginTransaction();

            $stmtEntrega = $this->conn->prepare("SELECT foto_entrega, foto_adicional FROM entregas WHERE paquete_id = :id");
            $stmtEntrega->execute([':id' => $paqueteId]);
            foreach ($stmtEntrega->fetchAll(PDO::FETCH_ASSOC) as $row) {
                foreach (['foto_entrega', 'foto_adicional'] as $campo) {
                    if (!empty($row[$campo])) {
                        $rutas[] = $row[$campo];
                    }
                }
            }

            $stmtNovedades = $this->conn->prepare("SELECT foto_evidencia FROM novedades_entrega WHERE paquete_id = :id");
            $stmtNovedades->execute([':id' => $paqueteId]);
            foreach ($stmtNovedades->fetchAll(PDO::FETCH_ASSOC) as $row) {
                if (!empty($row['foto_evidencia'])) {
                    $rutas[] = $row['foto_evidencia'];
                }
            }

            $stmtImagenes = $this->conn->prepare("SELECT ruta_archivo FROM paquete_imagenes WHERE paquete_id = :id");
            $stmtImagenes->execute([':id' => $paqueteId]);
            foreach ($stmtImagenes->fetchAll(PDO::FETCH_ASSOC) as $row) {
                if (!empty($row['ruta_archivo'])) {
                    $rutas[] = $row['ruta_archivo'];
                }
            }

            $stmtComprobantes = $this->conn->prepare("SELECT archivo_pdf, foto_entrega FROM comprobantes WHERE paquete_id = :id");
            $stmtComprobantes->execute([':id' => $paqueteId]);
            foreach ($stmtComprobantes->fetchAll(PDO::FETCH_ASSOC) as $row) {
                foreach (['archivo_pdf', 'foto_entrega'] as $campo) {
                    if (!empty($row[$campo])) {
                        $rutas[] = $row[$campo];
                    }
                }
            }

            $stmtDelete = $this->conn->prepare("DELETE FROM historial_paquetes WHERE paquete_id = :id");
            try { $stmtDelete->execute([':id' => $paqueteId]); } catch (Throwable $e) {}

            $stmtDelete = $this->conn->prepare("DELETE FROM detalle_facturas WHERE paquete_id = :id");
            try { $stmtDelete->execute([':id' => $paqueteId]); } catch (Throwable $e) {}

            $stmtDelete = $this->conn->prepare("DELETE FROM facturacion WHERE paquete_id = :id");
            try { $stmtDelete->execute([':id' => $paqueteId]); } catch (Throwable $e) {}

            $stmtDelete = $this->conn->prepare("DELETE FROM paquete_imagenes WHERE paquete_id = :id");
            $stmtDelete->execute([':id' => $paqueteId]);

            $stmtDelete = $this->conn->prepare("DELETE FROM imagenes WHERE comprobante_id IN (SELECT id FROM comprobantes WHERE paquete_id = :id)");
            try { $stmtDelete->execute([':id' => $paqueteId]); } catch (Throwable $e) {}

            $stmtDelete = $this->conn->prepare("DELETE FROM comprobantes WHERE paquete_id = :id");
            try { $stmtDelete->execute([':id' => $paqueteId]); } catch (Throwable $e) {}

            $stmtDelete = $this->conn->prepare("DELETE FROM novedades_entrega WHERE paquete_id = :id");
            $stmtDelete->execute([':id' => $paqueteId]);

            $stmtDelete = $this->conn->prepare("DELETE FROM entregas WHERE paquete_id = :id");
            $stmtDelete->execute([':id' => $paqueteId]);

            $stmtDelete = $this->conn->prepare("DELETE FROM paquetes WHERE id = :id");
            $stmtDelete->execute([':id' => $paqueteId]);

            $this->conn->commit();

            return [
                'resumen' => $resumen,
                'rutas' => array_values(array_unique(array_filter($rutas)))
            ];
        } catch (Throwable $e) {
            if ($this->conn->inTransaction()) {
                $this->conn->rollBack();
            }
            throw $e;
        }
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
        $sql = "SELECT foto_entrega, foto_adicional FROM entregas WHERE paquete_id = :id ORDER BY id DESC LIMIT 1";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([':id' => $paqueteId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function updateEntregaFoto($paqueteId, $campo, $ruta) {
        if (!in_array($campo, ['foto_entrega', 'foto_adicional'], true)) {
            return false;
        }
        $entregaId = $this->ensureEntregaRecord((int) $paqueteId, null);
        if (!$entregaId) {
            return false;
        }
        $sql = "UPDATE entregas SET {$campo} = :ruta WHERE id = :id";
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute([':ruta' => $ruta, ':id' => $entregaId]);
    }

    public function getCancelacionFoto($paqueteId) {
        $sql = "SELECT foto_evidencia FROM novedades_entrega
                WHERE paquete_id = :id AND tipo = 'cancelado'
                ORDER BY fecha_registro DESC, id DESC
                LIMIT 1";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([':id' => $paqueteId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function updateCancelacionFoto($paqueteId, $ruta) {
        $cancelacionId = $this->getUltimaCancelacionId((int) $paqueteId);
        if (!$cancelacionId) {
            return false;
        }
        $sql = "UPDATE novedades_entrega SET foto_evidencia = :ruta WHERE id = :id";
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute([':ruta' => $ruta, ':id' => $cancelacionId]);
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

    public function assignMensajeroBulk(array $paqueteIds, int $mensajeroId, int $userId): int
    {
        $paqueteIds = array_values(array_unique(array_filter(array_map('intval', $paqueteIds), static fn($id) => $id > 0)));
        if (empty($paqueteIds)) {
            return 0;
        }

        try {
            $this->conn->beginTransaction();

            $placeholders = implode(',', array_fill(0, count($paqueteIds), '?'));
            $sqlIds = "SELECT id
                       FROM paquetes
                       WHERE id IN ($placeholders) AND estado NOT IN ('entregado', 'cancelado')";
            $stmtIds = $this->conn->prepare($sqlIds);
            $stmtIds->execute($paqueteIds);
            $idsAsignables = array_map('intval', $stmtIds->fetchAll(PDO::FETCH_COLUMN) ?: []);

            if (empty($idsAsignables)) {
                $this->conn->commit();
                return 0;
            }

            $placeholdersAsignables = implode(',', array_fill(0, count($idsAsignables), '?'));
            $sql = "UPDATE paquetes
                    SET mensajero_id = ?, estado = 'en_transito', fecha_asignacion = NOW()
                    WHERE id IN ($placeholdersAsignables) AND estado NOT IN ('entregado', 'cancelado')";

            $stmt = $this->conn->prepare($sql);
            $stmt->execute(array_merge([$mensajeroId], $idsAsignables));
            $asignados = (int) $stmt->rowCount();

            if ($asignados > 0) {
                try {
                    $sqlHist = "INSERT INTO historial_paquetes (paquete_id, estado_anterior, estado_nuevo, usuario_id, observaciones, fecha_creacion)
                                VALUES (:id, 'pendiente', 'en_transito', :user_id, 'Mensajero asignado manualmente (masivo)', NOW())";
                    $stmtHist = $this->conn->prepare($sqlHist);
                    foreach ($idsAsignables as $paqueteId) {
                        $stmtHist->execute([
                            ':id' => $paqueteId,
                            ':user_id' => $userId
                        ]);
                    }
                } catch (PDOException $e) {
                    // Continuar si falla el historial.
                }
            }

            $this->conn->commit();
            return $asignados;
        } catch (Exception $e) {
            if ($this->conn->inTransaction()) {
                $this->conn->rollBack();
            }
            throw new Exception("Error en BD: " . $e->getMessage());
        }
    }
}
?>
