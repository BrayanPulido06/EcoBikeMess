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

    public function obtenerVistaAdmin(): array
    {
        return [
            'cliente' => $this->obtenerResumenClientes(),
            'mensajero' => $this->obtenerResumenMensajeros(false),
        ];
    }

    public function obtenerVistaCliente(int $clienteId): array
    {
        return [
            'cliente' => $this->obtenerResumenClientes($clienteId),
        ];
    }

    public function obtenerVistaMensajero(int $mensajeroId): array
    {
        return [
            'mensajero' => $this->obtenerResumenMensajeros(true, $mensajeroId),
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

    private function obtenerResumenClientes(?int $clienteId = null): array
    {
        $params = [];
        $where = '';

        if ($clienteId !== null) {
            $where = 'WHERE p.cliente_id = :cliente_id';
            $params[':cliente_id'] = $clienteId;
        }

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
                    c.id AS cliente_id,
                    c.nombre_emprendimiento AS cliente_nombre,
                    CONCAT(COALESCE(uc.nombres, ''), ' ', COALESCE(uc.apellidos, '')) AS cliente_contacto
                FROM paquetes p
                INNER JOIN clientes c ON c.id = p.cliente_id
                INNER JOIN usuarios uc ON uc.id = c.usuario_id
                LEFT JOIN entregas e ON e.paquete_id = p.id
                {$where}
                ORDER BY p.fecha_creacion DESC, p.id DESC";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return $this->mapearResumenClientes($rows);
    }

    private function obtenerResumenMensajeros(bool $soloVisibles, ?int $mensajeroId = null): array
    {
        $conditions = [];
        $params = [];

        if ($soloVisibles) {
            $conditions[] = 'f.mostrar_al_mensajero = 1';
        }
        if ($mensajeroId !== null) {
            $conditions[] = 'p.mensajero_id = :mensajero_id';
            $params[':mensajero_id'] = $mensajeroId;
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

        return $this->mapearResumenMensajeros($rows);
    }

    private function mapearResumenClientes(array $rows): array
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
            $fechaDia = substr((string) $row['fecha_creacion'], 0, 10);
            if (!isset($diario[$fechaDia])) {
                $diario[$fechaDia] = 0;
            }
            $diario[$fechaDia]++;
        }

        foreach ($rows as $row) {
            $fechaDia = substr((string) $row['fecha_creacion'], 0, 10);

            $valorEnvio = (float) $row['costo_envio'];
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
        ];
    }

    private function mapearResumenMensajeros(array $rows): array
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

        foreach ($rows as $row) {
            $fechaBase = substr((string) ($row['fecha_entrega'] ?: $row['fecha_creacion']), 0, 10);

            $valorPago = (float) $row['valor_pago_mensajero'];
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
