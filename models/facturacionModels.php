<?php
require_once __DIR__ . '/conexionGlobal.php';

class FacturacionModels
{
    private $conn;

    public function __construct()
    {
        $this->conn = conexionDB();
        $this->ensureFacturacionTable();
        $this->ensureAbonosClienteTable();
        $this->ensureHiddenClientGroupsTable();
        $this->ensureHiddenMessengerGroupsTable();
        $this->ensureClientGroupStatusTable();
        $this->ensureAbonosMensajeroTable();
        $this->ensureMessengerGroupStatusTable();
        $this->ensurePerformanceIndexes();
        $this->syncFacturacionRows();
    }

    private function ensureFacturacionTable(): void
    {
        $sql = "CREATE TABLE IF NOT EXISTS facturacion (
                    id INT PRIMARY KEY AUTO_INCREMENT,
                    paquete_id INT NOT NULL UNIQUE,
                    cliente_id INT NOT NULL,
                    mensajero_id INT NULL,
                    valor_pago_mensajero DECIMAL(10,2) NOT NULL DEFAULT 7000.00,
                    mostrar_al_mensajero BOOLEAN NOT NULL DEFAULT FALSE,
                    costo_adicional_servicio DECIMAL(10,2) NOT NULL DEFAULT 0.00,
                    observaciones_admin TEXT NULL,
                    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    fecha_actualizacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    FOREIGN KEY (paquete_id) REFERENCES paquetes(id) ON DELETE CASCADE,
                    FOREIGN KEY (cliente_id) REFERENCES clientes(id) ON DELETE CASCADE,
                    FOREIGN KEY (mensajero_id) REFERENCES mensajeros(id) ON DELETE SET NULL,
                    INDEX idx_facturacion_cliente (cliente_id),
                    INDEX idx_facturacion_mensajero (mensajero_id),
                    INDEX idx_facturacion_visible (mostrar_al_mensajero)
                )";
        $this->conn->exec($sql);
        $this->ensureFacturacionColumn('costo_adicional_servicio', "ALTER TABLE facturacion ADD COLUMN costo_adicional_servicio DECIMAL(10,2) NOT NULL DEFAULT 0.00 AFTER mostrar_al_mensajero");
        $this->ensureFacturacionColumn('observaciones_admin', "ALTER TABLE facturacion ADD COLUMN observaciones_admin TEXT NULL AFTER costo_adicional_servicio");
    }

    private function ensureFacturacionColumn(string $column, string $alterSql): void
    {
        $stmt = $this->conn->prepare("SHOW COLUMNS FROM facturacion LIKE :column_name");
        $stmt->execute([':column_name' => $column]);
        if (!$stmt->fetch(PDO::FETCH_ASSOC)) {
            $this->conn->exec($alterSql);
        }
    }

    private function syncFacturacionRows(): void
    {
        $sql = "INSERT INTO facturacion (paquete_id, cliente_id, mensajero_id, valor_pago_mensajero)
                SELECT p.id, p.cliente_id, p.mensajero_id, 7000.00
                FROM paquetes p
                LEFT JOIN facturacion f ON f.paquete_id = p.id
                WHERE f.paquete_id IS NULL";
        $this->conn->exec($sql);

        $updateSql = "UPDATE facturacion f
                      INNER JOIN paquetes p ON p.id = f.paquete_id
                      SET f.cliente_id = p.cliente_id,
                          f.mensajero_id = p.mensajero_id
                      WHERE (f.cliente_id <> p.cliente_id)
                         OR (f.mensajero_id IS NULL AND p.mensajero_id IS NOT NULL)
                         OR (f.mensajero_id IS NOT NULL AND p.mensajero_id IS NULL)
                         OR (f.mensajero_id <> p.mensajero_id)";
        $this->conn->exec($updateSql);
    }

    private function ensureAbonosClienteTable(): void
    {
        $sql = "CREATE TABLE IF NOT EXISTS facturacion_abonos_cliente (
                    id INT PRIMARY KEY AUTO_INCREMENT,
                    cliente_id INT NOT NULL,
                    fecha_grupo DATE NOT NULL,
                    monto DECIMAL(10,2) NOT NULL DEFAULT 0.00,
                    metodo_pago ENUM('efectivo', 'transferencia') NOT NULL,
                    observaciones TEXT NULL,
                    registrado_por INT NULL,
                    fecha_registro TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    FOREIGN KEY (cliente_id) REFERENCES clientes(id) ON DELETE CASCADE,
                    FOREIGN KEY (registrado_por) REFERENCES usuarios(id) ON DELETE SET NULL,
                    INDEX idx_abonos_cliente_fecha (cliente_id, fecha_grupo)
                )";
        $this->conn->exec($sql);
    }

    private function ensureHiddenClientGroupsTable(): void
    {
        $sql = "CREATE TABLE IF NOT EXISTS facturacion_grupos_cliente_ocultos (
                    id INT PRIMARY KEY AUTO_INCREMENT,
                    cliente_id INT NOT NULL,
                    fecha_grupo DATE NOT NULL,
                    ocultado_por INT NULL,
                    fecha_registro TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    UNIQUE KEY uniq_cliente_fecha (cliente_id, fecha_grupo),
                    FOREIGN KEY (cliente_id) REFERENCES clientes(id) ON DELETE CASCADE,
                    FOREIGN KEY (ocultado_por) REFERENCES usuarios(id) ON DELETE SET NULL,
                    INDEX idx_ocultos_fecha (fecha_grupo)
                )";
        $this->conn->exec($sql);
    }

    private function ensureHiddenMessengerGroupsTable(): void
    {
        $sql = "CREATE TABLE IF NOT EXISTS facturacion_grupos_mensajero_ocultos (
                    id INT PRIMARY KEY AUTO_INCREMENT,
                    mensajero_id INT NOT NULL,
                    fecha_grupo DATE NOT NULL,
                    ocultado_por INT NULL,
                    fecha_registro TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    UNIQUE KEY uniq_mensajero_fecha (mensajero_id, fecha_grupo),
                    FOREIGN KEY (mensajero_id) REFERENCES mensajeros(id) ON DELETE CASCADE,
                    FOREIGN KEY (ocultado_por) REFERENCES usuarios(id) ON DELETE SET NULL,
                    INDEX idx_ocultos_mensajero_fecha (fecha_grupo)
                )";
        $this->conn->exec($sql);
    }

    private function ensureClientGroupStatusTable(): void
    {
        $sql = "CREATE TABLE IF NOT EXISTS facturacion_estados_cliente (
                    id INT PRIMARY KEY AUTO_INCREMENT,
                    cliente_id INT NOT NULL,
                    fecha_grupo DATE NOT NULL,
                    estado ENUM('pendiente', 'pagado') NOT NULL DEFAULT 'pendiente',
                    actualizado_por INT NULL,
                    fecha_actualizacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    UNIQUE KEY uniq_estado_cliente_fecha (cliente_id, fecha_grupo),
                    FOREIGN KEY (cliente_id) REFERENCES clientes(id) ON DELETE CASCADE,
                    FOREIGN KEY (actualizado_por) REFERENCES usuarios(id) ON DELETE SET NULL,
                    INDEX idx_estado_cliente_fecha (cliente_id, fecha_grupo)
                )";
        $this->conn->exec($sql);
    }

    private function ensureAbonosMensajeroTable(): void
    {
        $sql = "CREATE TABLE IF NOT EXISTS facturacion_abonos_mensajero (
                    id INT PRIMARY KEY AUTO_INCREMENT,
                    mensajero_id INT NOT NULL,
                    fecha_grupo DATE NOT NULL,
                    monto DECIMAL(10,2) NOT NULL DEFAULT 0.00,
                    metodo_pago ENUM('efectivo', 'transferencia') NOT NULL,
                    observaciones TEXT NULL,
                    registrado_por INT NULL,
                    fecha_registro TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    FOREIGN KEY (mensajero_id) REFERENCES mensajeros(id) ON DELETE CASCADE,
                    FOREIGN KEY (registrado_por) REFERENCES usuarios(id) ON DELETE SET NULL,
                    INDEX idx_abonos_mensajero_fecha (mensajero_id, fecha_grupo)
                )";
        $this->conn->exec($sql);
    }

    private function ensureMessengerGroupStatusTable(): void
    {
        $sql = "CREATE TABLE IF NOT EXISTS facturacion_estados_mensajero (
                    id INT PRIMARY KEY AUTO_INCREMENT,
                    mensajero_id INT NOT NULL,
                    fecha_grupo DATE NOT NULL,
                    estado ENUM('pendiente', 'pagado') NOT NULL DEFAULT 'pendiente',
                    actualizado_por INT NULL,
                    fecha_actualizacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    UNIQUE KEY uniq_estado_mensajero_fecha (mensajero_id, fecha_grupo),
                    FOREIGN KEY (mensajero_id) REFERENCES mensajeros(id) ON DELETE CASCADE,
                    FOREIGN KEY (actualizado_por) REFERENCES usuarios(id) ON DELETE SET NULL,
                    INDEX idx_estado_mensajero_fecha (mensajero_id, fecha_grupo)
                )";
        $this->conn->exec($sql);
    }

    private function ensurePerformanceIndexes(): void
    {
        $indexes = [
            ['paquetes', 'idx_paquetes_facturacion_cliente_estado_fecha', 'CREATE INDEX idx_paquetes_facturacion_cliente_estado_fecha ON paquetes (cliente_id, estado, fecha_entrega, fecha_creacion)'],
            ['paquetes', 'idx_paquetes_facturacion_mensajero_fecha', 'CREATE INDEX idx_paquetes_facturacion_mensajero_fecha ON paquetes (mensajero_id, fecha_entrega, fecha_creacion)'],
            ['entregas', 'idx_entregas_paquete', 'CREATE INDEX idx_entregas_paquete ON entregas (paquete_id)'],
        ];

        foreach ($indexes as [$table, $indexName, $sql]) {
            $stmt = $this->conn->prepare("SHOW INDEX FROM {$table} WHERE Key_name = :index_name");
            $stmt->execute([':index_name' => $indexName]);
            if (!$stmt->fetch(PDO::FETCH_ASSOC)) {
                $this->conn->exec($sql);
            }
        }
    }

    public function obtenerClienteIdPorUsuario(int $usuarioId): ?int
    {
        $sql = "SELECT id FROM clientes WHERE usuario_id = :usuario_id LIMIT 1";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([':usuario_id' => $usuarioId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ? (int) $row['id'] : null;
    }

    public function obtenerMensajeroIdPorUsuario(int $usuarioId): ?int
    {
        $sql = "SELECT id FROM mensajeros WHERE usuario_id = :usuario_id LIMIT 1";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([':usuario_id' => $usuarioId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ? (int) $row['id'] : null;
    }

    public function obtenerVistaAdmin(?string $panel = null): array
    {
        if ($panel === 'cliente') {
            return [
                'cliente' => $this->obtenerResumenClientes(),
            ];
        }

        if ($panel === 'mensajero') {
            return [
                'mensajero' => $this->obtenerResumenMensajeros(false, null, true, date('Y-m-d')),
            ];
        }

        return [
            'cliente' => $this->obtenerResumenClientes(),
            'mensajero' => $this->obtenerResumenMensajeros(false, null, true, date('Y-m-d')),
        ];
    }

    public function obtenerVistaCliente(int $clienteId): array
    {
        $fechaInicioFacturacionCliente = date('Y-m-d');

        return [
            'cliente' => $this->obtenerResumenClientes($clienteId, false, $fechaInicioFacturacionCliente),
        ];
    }

    public function obtenerVistaMensajero(int $mensajeroId): array
    {
        return [
            'mensajero' => $this->obtenerResumenMensajeros(true, $mensajeroId, false),
        ];
    }

    public function actualizarPagoMensajero(int $paqueteId, float $valorPago, bool $mostrarAlMensajero): bool
    {
        $sql = "UPDATE facturacion
                SET valor_pago_mensajero = :valor_pago_mensajero,
                    mostrar_al_mensajero = :mostrar_al_mensajero
                WHERE paquete_id = :paquete_id";
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute([
            ':valor_pago_mensajero' => $valorPago,
            ':mostrar_al_mensajero' => $mostrarAlMensajero ? 1 : 0,
            ':paquete_id' => $paqueteId,
        ]);
    }

    public function registrarAbonoCliente(
        int $clienteId,
        string $fechaGrupo,
        float $monto,
        string $metodoPago,
        ?string $observaciones,
        ?int $registradoPor
    ): bool {
        $sql = "INSERT INTO facturacion_abonos_cliente (
                    cliente_id, fecha_grupo, monto, metodo_pago, observaciones, registrado_por
                ) VALUES (
                    :cliente_id, :fecha_grupo, :monto, :metodo_pago, :observaciones, :registrado_por
                )";
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute([
            ':cliente_id' => $clienteId,
            ':fecha_grupo' => $fechaGrupo,
            ':monto' => $monto,
            ':metodo_pago' => $metodoPago,
            ':observaciones' => $observaciones,
            ':registrado_por' => $registradoPor,
        ]);
    }

    public function registrarAbonoMensajero(
        int $mensajeroId,
        string $fechaGrupo,
        float $monto,
        string $metodoPago,
        ?string $observaciones,
        ?int $registradoPor
    ): bool {
        $sql = "INSERT INTO facturacion_abonos_mensajero (
                    mensajero_id, fecha_grupo, monto, metodo_pago, observaciones, registrado_por
                ) VALUES (
                    :mensajero_id, :fecha_grupo, :monto, :metodo_pago, :observaciones, :registrado_por
                )";
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute([
            ':mensajero_id' => $mensajeroId,
            ':fecha_grupo' => $fechaGrupo,
            ':monto' => $monto,
            ':metodo_pago' => $metodoPago,
            ':observaciones' => $observaciones,
            ':registrado_por' => $registradoPor,
        ]);
    }

    public function actualizarCostoAdicionalPaquete(int $paqueteId, float $monto, ?string $descripcion): bool
    {
        $sql = "UPDATE facturacion
                SET costo_adicional_servicio = :monto,
                    observaciones_admin = :descripcion
                WHERE paquete_id = :paquete_id";
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute([
            ':paquete_id' => $paqueteId,
            ':monto' => $monto,
            ':descripcion' => $descripcion,
        ]);
    }

    public function ocultarGrupoCliente(int $clienteId, string $fechaGrupo, ?int $ocultadoPor): bool
    {
        $sql = "INSERT INTO facturacion_grupos_cliente_ocultos (
                    cliente_id, fecha_grupo, ocultado_por
                ) VALUES (
                    :cliente_id, :fecha_grupo, :ocultado_por
                )
                ON DUPLICATE KEY UPDATE
                    ocultado_por = VALUES(ocultado_por)";
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute([
            ':cliente_id' => $clienteId,
            ':fecha_grupo' => $fechaGrupo,
            ':ocultado_por' => $ocultadoPor,
        ]);
    }

    public function ocultarGrupoMensajero(int $mensajeroId, string $fechaGrupo, ?int $ocultadoPor): bool
    {
        $sql = "INSERT INTO facturacion_grupos_mensajero_ocultos (
                    mensajero_id, fecha_grupo, ocultado_por
                ) VALUES (
                    :mensajero_id, :fecha_grupo, :ocultado_por
                )
                ON DUPLICATE KEY UPDATE
                    ocultado_por = VALUES(ocultado_por)";
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute([
            ':mensajero_id' => $mensajeroId,
            ':fecha_grupo' => $fechaGrupo,
            ':ocultado_por' => $ocultadoPor,
        ]);
    }

    public function actualizarEstadoGrupoCliente(int $clienteId, string $fechaGrupo, string $estado, ?int $actualizadoPor): bool
    {
        if (!in_array($estado, ['pendiente', 'pagado'], true)) {
            throw new InvalidArgumentException('Estado de facturacion invalido.');
        }

        $sql = "INSERT INTO facturacion_estados_cliente (
                    cliente_id, fecha_grupo, estado, actualizado_por
                ) VALUES (
                    :cliente_id, :fecha_grupo, :estado, :actualizado_por
                )
                ON DUPLICATE KEY UPDATE
                    estado = VALUES(estado),
                    actualizado_por = VALUES(actualizado_por)";
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute([
            ':cliente_id' => $clienteId,
            ':fecha_grupo' => $fechaGrupo,
            ':estado' => $estado,
            ':actualizado_por' => $actualizadoPor,
        ]);
    }

    public function actualizarEstadoGrupoMensajero(int $mensajeroId, string $fechaGrupo, string $estado, ?int $actualizadoPor): bool
    {
        if (!in_array($estado, ['pendiente', 'pagado'], true)) {
            throw new InvalidArgumentException('Estado de facturacion invalido.');
        }

        $sql = "INSERT INTO facturacion_estados_mensajero (
                    mensajero_id, fecha_grupo, estado, actualizado_por
                ) VALUES (
                    :mensajero_id, :fecha_grupo, :estado, :actualizado_por
                )
                ON DUPLICATE KEY UPDATE
                    estado = VALUES(estado),
                    actualizado_por = VALUES(actualizado_por)";
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute([
            ':mensajero_id' => $mensajeroId,
            ':fecha_grupo' => $fechaGrupo,
            ':estado' => $estado,
            ':actualizado_por' => $actualizadoPor,
        ]);
    }

    private function obtenerGruposClienteOcultos(): array
    {
        $sql = "SELECT cliente_id, fecha_grupo FROM facturacion_grupos_cliente_ocultos";
        $stmt = $this->conn->query($sql);
        $rows = $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];

        return array_map(static function ($row) {
            return [
                'cliente_id' => (int) ($row['cliente_id'] ?? 0),
                'fecha_grupo' => (string) ($row['fecha_grupo'] ?? ''),
            ];
        }, $rows);
    }

    private function obtenerGruposMensajeroOcultos(): array
    {
        $sql = "SELECT mensajero_id, fecha_grupo FROM facturacion_grupos_mensajero_ocultos";
        $stmt = $this->conn->query($sql);
        $rows = $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];

        return array_map(static function ($row) {
            return [
                'mensajero_id' => (int) ($row['mensajero_id'] ?? 0),
                'fecha_grupo' => (string) ($row['fecha_grupo'] ?? ''),
            ];
        }, $rows);
    }

    private function obtenerEstadosCliente(?int $clienteId = null): array
    {
        $params = [];
        $where = '';

        if ($clienteId !== null) {
            $where = 'WHERE cliente_id = :cliente_id';
            $params[':cliente_id'] = $clienteId;
        }

        $sql = "SELECT cliente_id, fecha_grupo, estado FROM facturacion_estados_cliente {$where}";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);

        return array_map(static function ($row) {
            return [
                'cliente_id' => (int) ($row['cliente_id'] ?? 0),
                'fecha_grupo' => (string) ($row['fecha_grupo'] ?? ''),
                'estado' => (string) ($row['estado'] ?? 'pendiente'),
            ];
        }, $stmt->fetchAll(PDO::FETCH_ASSOC));
    }

    private function obtenerAbonosCliente(?int $clienteId = null): array
    {
        $params = [];
        $where = '';

        if ($clienteId !== null) {
            $where = 'WHERE a.cliente_id = :cliente_id';
            $params[':cliente_id'] = $clienteId;
        }

        $sql = "SELECT
                    a.id,
                    a.cliente_id,
                    a.fecha_grupo,
                    a.monto,
                    a.metodo_pago,
                    a.observaciones,
                    a.fecha_registro,
                    CONCAT(COALESCE(u.nombres, ''), ' ', COALESCE(u.apellidos, '')) AS registrado_por_nombre
                FROM facturacion_abonos_cliente a
                LEFT JOIN usuarios u ON u.id = a.registrado_por
                {$where}
                ORDER BY a.fecha_grupo DESC, a.fecha_registro DESC, a.id DESC";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);

        return array_map(function ($row) {
            return [
                'id' => (int) $row['id'],
                'cliente_id' => (int) $row['cliente_id'],
                'fecha_grupo' => $row['fecha_grupo'],
                'monto' => (float) $row['monto'],
                'metodo_pago' => $row['metodo_pago'],
                'observaciones' => $row['observaciones'],
                'fecha_registro' => $row['fecha_registro'],
                'registrado_por_nombre' => trim((string) $row['registrado_por_nombre']),
            ];
        }, $stmt->fetchAll(PDO::FETCH_ASSOC));
    }

    private function obtenerEstadosMensajero(?int $mensajeroId = null): array
    {
        $params = [];
        $where = '';

        if ($mensajeroId !== null) {
            $where = 'WHERE mensajero_id = :mensajero_id';
            $params[':mensajero_id'] = $mensajeroId;
        }

        $sql = "SELECT mensajero_id, fecha_grupo, estado FROM facturacion_estados_mensajero {$where}";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);

        return array_map(static function ($row) {
            return [
                'mensajero_id' => (int) ($row['mensajero_id'] ?? 0),
                'fecha_grupo' => (string) ($row['fecha_grupo'] ?? ''),
                'estado' => (string) ($row['estado'] ?? 'pendiente'),
            ];
        }, $stmt->fetchAll(PDO::FETCH_ASSOC));
    }

    private function obtenerAbonosMensajero(?int $mensajeroId = null): array
    {
        $params = [];
        $where = '';

        if ($mensajeroId !== null) {
            $where = 'WHERE a.mensajero_id = :mensajero_id';
            $params[':mensajero_id'] = $mensajeroId;
        }

        $sql = "SELECT
                    a.id,
                    a.mensajero_id,
                    a.fecha_grupo,
                    a.monto,
                    a.metodo_pago,
                    a.observaciones,
                    a.fecha_registro,
                    CONCAT(COALESCE(u.nombres, ''), ' ', COALESCE(u.apellidos, '')) AS registrado_por_nombre
                FROM facturacion_abonos_mensajero a
                LEFT JOIN usuarios u ON u.id = a.registrado_por
                {$where}
                ORDER BY a.fecha_grupo DESC, a.fecha_registro DESC, a.id DESC";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);

        return array_map(function ($row) {
            return [
                'id' => (int) $row['id'],
                'mensajero_id' => (int) $row['mensajero_id'],
                'fecha_grupo' => $row['fecha_grupo'],
                'monto' => (float) $row['monto'],
                'metodo_pago' => $row['metodo_pago'],
                'observaciones' => $row['observaciones'],
                'fecha_registro' => $row['fecha_registro'],
                'registrado_por_nombre' => trim((string) $row['registrado_por_nombre']),
            ];
        }, $stmt->fetchAll(PDO::FETCH_ASSOC));
    }

    private function obtenerResumenClientes(?int $clienteId = null, bool $aplicarOcultos = true, ?string $fechaDesde = null): array
    {
        $params = [];
        $conditions = ["p.estado = 'entregado'"];

        if ($clienteId !== null) {
            $conditions[] = 'p.cliente_id = :cliente_id';
            $params[':cliente_id'] = $clienteId;
        }

        if ($fechaDesde !== null) {
            $conditions[] = '(p.fecha_entrega >= :fecha_desde OR (p.fecha_entrega IS NULL AND p.fecha_creacion >= :fecha_desde))';
            $params[':fecha_desde'] = $fechaDesde . ' 00:00:00';
        }

        $where = $conditions ? 'WHERE ' . implode(' AND ', $conditions) : '';

        $sql = "SELECT
                    p.id AS paquete_id,
                    p.numero_guia,
                    p.fecha_creacion,
                    p.fecha_entrega,
                    p.estado,
                    p.destinatario_nombre,
                    p.direccion_destino,
                    p.instrucciones_entrega,
                    p.costo_envio,
                    p.envio_destinatario,
                    p.recaudo_esperado,
                    COALESCE(e.recaudo_real, 0) AS recaudo_real,
                    COALESCE(f.costo_adicional_servicio, 0) AS costo_adicional_servicio,
                    COALESCE(f.observaciones_admin, '') AS observaciones_admin,
                    c.id AS cliente_id,
                    c.nombre_emprendimiento AS cliente_nombre,
                    CONCAT(COALESCE(uc.nombres, ''), ' ', COALESCE(uc.apellidos, '')) AS cliente_contacto
                FROM paquetes p
                INNER JOIN clientes c ON c.id = p.cliente_id
                INNER JOIN usuarios uc ON uc.id = c.usuario_id
                LEFT JOIN entregas e ON e.paquete_id = p.id
                LEFT JOIN facturacion f ON f.paquete_id = p.id
                {$where}
                ORDER BY p.fecha_creacion DESC, p.id DESC";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return $this->mapearResumenClientes($rows, $clienteId, $aplicarOcultos);
    }

    private function obtenerResumenMensajeros(bool $soloVisibles, ?int $mensajeroId = null, bool $aplicarOcultos = true, ?string $fechaDesde = null): array
    {
        $conditions = ["p.estado = 'entregado'"];
        $params = [];

        if ($soloVisibles) {
            $conditions[] = 'f.mostrar_al_mensajero = 1';
        }
        if ($mensajeroId !== null) {
            $conditions[] = 'p.mensajero_id = :mensajero_id';
            $params[':mensajero_id'] = $mensajeroId;
        }
        if ($fechaDesde !== null) {
            $conditions[] = '(p.fecha_entrega >= :fecha_desde OR (p.fecha_entrega IS NULL AND p.fecha_creacion >= :fecha_desde))';
            $params[':fecha_desde'] = $fechaDesde . ' 00:00:00';
        }

        $where = $conditions ? 'WHERE ' . implode(' AND ', $conditions) : '';

        $sql = "SELECT
                    p.id AS paquete_id,
                    p.numero_guia,
                    p.fecha_creacion,
                    p.fecha_entrega,
                    p.estado,
                    p.destinatario_nombre,
                    p.costo_envio,
                    p.envio_destinatario,
                    p.recaudo_esperado,
                    COALESCE(e.recaudo_real, 0) AS recaudo_real,
                    COALESCE(e.observaciones, '') AS observaciones,
                    c.nombre_emprendimiento AS cliente_nombre,
                    m.id AS mensajero_id,
                    CONCAT(COALESCE(um.nombres, ''), ' ', COALESCE(um.apellidos, '')) AS mensajero_nombre,
                    f.valor_pago_mensajero,
                    f.mostrar_al_mensajero
                FROM paquetes p
                INNER JOIN facturacion f ON f.paquete_id = p.id
                LEFT JOIN entregas e ON e.paquete_id = p.id
                LEFT JOIN clientes c ON c.id = p.cliente_id
                LEFT JOIN mensajeros m ON m.id = p.mensajero_id
                LEFT JOIN usuarios um ON um.id = m.usuario_id
                {$where}
                ORDER BY COALESCE(p.fecha_entrega, p.fecha_creacion) DESC, p.id DESC";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return $this->mapearResumenMensajeros($rows, $mensajeroId, $aplicarOcultos);
    }

    private function mapearResumenClientes(array $rows, ?int $clienteId = null, bool $aplicarOcultos = true): array
    {
        $items = [];
        $totales = [
            'saldo_actual' => 0.0,
            'total_envios' => 0.0,
            'total_recaudos' => 0.0,
            'cantidad_paquetes' => 0,
            'paquetes_entregados' => 0,
        ];
        $diario = [];

        foreach ($rows as $row) {
            $fechaBase = (string) ($row['fecha_entrega'] ?: $row['fecha_creacion']);
            $fechaDia = substr($fechaBase, 0, 10);
            if (!isset($diario[$fechaDia])) {
                $diario[$fechaDia] = 0;
            }
            $diario[$fechaDia]++;
        }

        $hiddenMap = [];
        if ($aplicarOcultos) {
            $hiddenGroups = $this->obtenerGruposClienteOcultos();
            foreach ($hiddenGroups as $hiddenGroup) {
                $hiddenMap[(int) $hiddenGroup['cliente_id'] . '__' . (string) $hiddenGroup['fecha_grupo']] = true;
            }
        }

        foreach ($rows as $row) {
            $fechaBase = (string) ($row['fecha_entrega'] ?: $row['fecha_creacion']);
            $fechaDia = substr($fechaBase, 0, 10);
            $hiddenKey = (int) $row['cliente_id'] . '__' . $fechaDia;
            if (isset($hiddenMap[$hiddenKey])) {
                continue;
            }

            $valorEnvioBase = (float) $row['costo_envio'];
            $costoAdicional = (float) ($row['costo_adicional_servicio'] ?? 0);
            $valorEnvio = $valorEnvioBase + $costoAdicional;
            $recaudoEsperado = (float) $row['recaudo_esperado'];
            $recaudoReal = (float) $row['recaudo_real'];
            $saldoRegistro = $recaudoReal - $valorEnvio;
            $agregadoRecaudo = ($row['envio_destinatario'] ?? 'no') === 'si';

            $totales['saldo_actual'] += $saldoRegistro;
            $totales['total_envios'] += $valorEnvio;
            $totales['total_recaudos'] += $recaudoReal;
            $totales['cantidad_paquetes']++;
            if (($row['estado'] ?? '') === 'entregado') {
                $totales['paquetes_entregados']++;
            }

            $items[] = [
                'paquete_id' => (int) $row['paquete_id'],
                'numero_guia' => $row['numero_guia'],
                'fecha_ingreso' => $row['fecha_creacion'],
                'fecha_entrega' => $row['fecha_entrega'],
                'estado' => $row['estado'],
                'cliente_id' => (int) $row['cliente_id'],
                'cliente_nombre' => trim((string) $row['cliente_nombre']),
                'cliente_contacto' => trim((string) $row['cliente_contacto']),
                'destinatario_nombre' => $row['destinatario_nombre'],
                'direccion_destino' => $row['direccion_destino'],
                'instrucciones_entrega' => $row['instrucciones_entrega'],
                'valor_envio' => $valorEnvio,
                'valor_envio_base' => $valorEnvioBase,
                'costo_adicional_servicio' => $costoAdicional,
                'observaciones_admin' => $row['observaciones_admin'],
                'agregado_al_recaudo' => $agregadoRecaudo,
                'valor_recaudo' => $recaudoEsperado,
                'valor_recaudo_real' => $recaudoReal,
                'cantidad_paquetes_dia' => $diario[$fechaDia],
                'saldo_registro' => $saldoRegistro,
            ];
        }

        return [
            'summary' => $this->normalizarResumen($totales),
            'items' => $items,
            'abonos' => $this->obtenerAbonosCliente($clienteId),
            'estados' => $this->obtenerEstadosCliente($clienteId),
        ];
    }

    private function mapearResumenMensajeros(array $rows, ?int $mensajeroId = null, bool $aplicarOcultos = true): array
    {
        $items = [];
        $totales = [
            'saldo_actual' => 0.0,
            'total_envios' => 0.0,
            'total_recaudos' => 0.0,
            'cantidad_paquetes' => 0,
            'paquetes_entregados' => 0,
        ];
        $diario = [];

        foreach ($rows as $row) {
            $fechaBase = substr((string) ($row['fecha_entrega'] ?: $row['fecha_creacion']), 0, 10);
            if (!isset($diario[$fechaBase])) {
                $diario[$fechaBase] = 0;
            }
            $diario[$fechaBase]++;
        }

        $hiddenMap = [];
        if ($aplicarOcultos) {
            $hiddenGroups = $this->obtenerGruposMensajeroOcultos();
            foreach ($hiddenGroups as $hiddenGroup) {
                $hiddenMap[(int) $hiddenGroup['mensajero_id'] . '__' . (string) $hiddenGroup['fecha_grupo']] = true;
            }
        }

        foreach ($rows as $row) {
            $fechaBase = substr((string) ($row['fecha_entrega'] ?: $row['fecha_creacion']), 0, 10);
            $hiddenKey = (int) ($row['mensajero_id'] ?? 0) . '__' . $fechaBase;
            if (isset($hiddenMap[$hiddenKey])) {
                continue;
            }

            $valorPago = (float) $row['valor_pago_mensajero'];
            if ($valorPago <= 0) {
                $valorPago = 7000.00;
            }
            $valorEnvio = (float) $row['costo_envio'];
            $recaudoEsperado = (float) $row['recaudo_esperado'];
            $recaudoReal = (float) $row['recaudo_real'];
            $agregadoRecaudo = ($row['envio_destinatario'] ?? 'no') === 'si';

            $totales['saldo_actual'] += $valorPago;
            $totales['total_envios'] += $valorEnvio;
            $totales['total_recaudos'] += $recaudoReal;
            $totales['cantidad_paquetes']++;
            if (($row['estado'] ?? '') === 'entregado') {
                $totales['paquetes_entregados']++;
            }

            $items[] = [
                'paquete_id' => (int) $row['paquete_id'],
                'numero_guia' => $row['numero_guia'],
                'fecha_ingreso' => $row['fecha_creacion'],
                'fecha_entrega' => $row['fecha_entrega'],
                'estado' => $row['estado'],
                'mensajero_id' => $row['mensajero_id'] !== null ? (int) $row['mensajero_id'] : null,
                'mensajero_nombre' => trim((string) $row['mensajero_nombre']) !== '' ? trim((string) $row['mensajero_nombre']) : 'Sin asignar',
                'cliente_nombre' => trim((string) $row['cliente_nombre']),
                'destinatario_nombre' => $row['destinatario_nombre'],
                'valor_envio' => $valorEnvio,
                'agregado_al_recaudo' => $agregadoRecaudo,
                'valor_recaudo' => $recaudoEsperado,
                'valor_recaudo_real' => $recaudoReal,
                'cantidad_paquetes_dia' => $diario[$fechaBase],
                'valor_pago_mensajero' => $valorPago,
                'mostrar_al_mensajero' => (bool) $row['mostrar_al_mensajero'],
                'observaciones' => $row['observaciones'],
            ];
        }

        return [
            'summary' => $this->normalizarResumen($totales),
            'items' => $items,
            'abonos' => $this->obtenerAbonosMensajero($mensajeroId),
            'estados' => $this->obtenerEstadosMensajero($mensajeroId),
        ];
    }

    private function normalizarResumen(array $totales): array
    {
        return [
            'saldo_actual' => round((float) $totales['saldo_actual'], 2),
            'total_envios' => round((float) $totales['total_envios'], 2),
            'total_recaudos' => round((float) $totales['total_recaudos'], 2),
            'cantidad_paquetes' => (int) $totales['cantidad_paquetes'],
            'paquetes_entregados' => (int) $totales['paquetes_entregados'],
        ];
    }
}
