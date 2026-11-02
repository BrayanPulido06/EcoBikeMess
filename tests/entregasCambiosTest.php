<?php
// Ejecutar con: php tests/entregasCambiosTest.php (requiere pdo_sqlite).
require_once __DIR__ . '/../models/misPaquetesMensajerosModels.php';
require_once __DIR__ . '/../models/enviarPaqueteMensajeroModels.php';

class EntregasCambiosTestPDO extends PDO
{
    public function prepare(string $query, array $options = []): PDOStatement|false
    {
        // Adaptar los upserts de MySQL para ejecutar los modelos reales en memoria.
        $query = str_replace('ON DUPLICATE KEY UPDATE', 'ON CONFLICT(paquete_id) DO UPDATE SET', $query);
        $query = preg_replace('/VALUES\((\w+)\)/i', 'excluded.$1', $query);
        return parent::prepare($query, $options);
    }
}

function modeloConConexion(string $clase, PDO $db): object
{
    $reflection = new ReflectionClass($clase);
    $modelo = $reflection->newInstanceWithoutConstructor();
    $conn = $reflection->getProperty('conn');
    $conn->setAccessible(true);
    $conn->setValue($modelo, $db);
    return $modelo;
}

function verificar(bool $condicion, string $mensaje): void
{
    if (!$condicion) {
        throw new RuntimeException($mensaje);
    }
}

$db = new EntregasCambiosTestPDO('sqlite::memory:');
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$db->sqliteCreateFunction('NOW', static fn() => '2026-10-19 12:00:00');
$db->exec("CREATE TABLE paquetes (
    id INTEGER PRIMARY KEY, numero_guia TEXT UNIQUE, cliente_id INTEGER,
    creado_por INTEGER, remitente_nombre TEXT, remitente_telefono TEXT,
    direccion_origen TEXT, observaciones_recoleccion TEXT, destinatario_nombre TEXT,
    destinatario_telefono TEXT, direccion_destino TEXT, instrucciones_entrega TEXT,
    descripcion_contenido TEXT, dimensiones TEXT, envio_mismo_dia INTEGER,
    zona_periferica INTEGER, recoger_cambios INTEGER, envio_destinatario TEXT,
    tipo_servicio TEXT, recaudo_esperado NUMERIC, costo_envio NUMERIC,
    mensajero_id INTEGER, estado TEXT, fecha_asignacion TEXT,
    fecha_creacion TEXT, fecha_entrega TEXT
);
CREATE TABLE entregas (
    paquete_id INTEGER UNIQUE, mensajero_id INTEGER, nombre_receptor TEXT,
    parentesco_cargo TEXT, documento_receptor TEXT, recaudo_real NUMERIC,
    recibio_cambios INTEGER, coordenadas_entrega_lat NUMERIC,
    coordenadas_entrega_lng NUMERIC, foto_entrega TEXT, foto_adicional TEXT,
    observaciones TEXT, fecha_entrega TEXT
);
CREATE TABLE comprobantes (
    paquete_id INTEGER UNIQUE, cliente_id INTEGER, numero_comprobante TEXT,
    numero_guia TEXT, nombre_receptor TEXT, parentesco_cargo TEXT,
    recaudo NUMERIC, observaciones TEXT, foto_entrega TEXT, fecha_generacion TEXT
)");

$modelo = modeloConConexion(MisPaquetesMensajerosModels::class, $db);
$payload = [
    'nombre_receptor' => 'Receptor', 'parentesco_cargo' => '',
    'documento_receptor' => '123', 'recaudo_real' => 20000,
    'lat' => null, 'lng' => null, 'foto_entrega' => 'evidencia.jpg',
    'foto_adicional' => null, 'observaciones' => ''
];

$casos = [
    ['sin adicional previo', 8000, 0, 1, 13000, 1],
    ['adicional ya incluido', 13000, 1, 1, 13000, 1],
    ['sin cambio recibido', 8000, 0, 0, 8000, 0],
    ['con otros adicionales', 15000, 0, 1, 20000, 1],
    ['indicador previo nulo', 8000, null, 1, 13000, 1],
    ['cambio solicitado sin recibir', 13000, 1, 0, 13000, 1],
];
foreach ($casos as $indice => [$nombre, $costo, $solicitado, $recibido, $total, $indicador]) {
    $id = $indice + 1;
    $stmt = $db->prepare('INSERT INTO paquetes (id, numero_guia, cliente_id, mensajero_id, costo_envio, recoger_cambios, recaudo_esperado) VALUES (?, ?, 10, 7, ?, ?, 20000)');
    $stmt->execute([$id, 'TEST-' . $id, $costo, $solicitado]);
    $entrega = $payload + ['paquete_id' => $id, 'recibio_cambios' => $recibido];
    for ($intento = 0; $intento < 2; $intento++) {
        $modelo->registrarEntrega(7, $entrega);
        $paquete = $db->query("SELECT * FROM paquetes WHERE id = {$id}")->fetch(PDO::FETCH_ASSOC);
        verificar((float) $paquete['costo_envio'] === (float) $total, "{$nombre}: total incorrecto");
        verificar((int) $paquete['recoger_cambios'] === $indicador, "{$nombre}: indicador incorrecto");
        verificar((float) $paquete['recaudo_esperado'] === 20000.0, "{$nombre}: cambió el recaudo esperado");
        verificar($paquete['estado'] === 'entregado', "{$nombre}: estado incorrecto");
    }
    $entregaGuardada = $db->query("SELECT * FROM entregas WHERE paquete_id = {$id}")->fetch(PDO::FETCH_ASSOC);
    verificar((int) $entregaGuardada['recibio_cambios'] === $recibido, "{$nombre}: entrega incorrecta");
    verificar((float) $entregaGuardada['recaudo_real'] === 20000.0, "{$nombre}: cambió el dinero recibido");
    echo "OK: {$nombre}, incluido reintento\n";
}

// La entrega sin rótulo crea primero el paquete y usa el mismo registro de entrega.
$envio = modeloConConexion(EnvioMensajeroModel::class, $db);
$envio->registrarEnvio([
    'cliente_id' => 10, 'creado_por' => 7, 'numero_guia' => 'MANUAL-1',
    'remitente_nombre' => '-', 'remitente_telefono' => '0', 'remitente_direccion' => '-',
    'observaciones_recoleccion' => 'ENTREGA_MANUAL_MENSAJERO',
    'destinatario_nombre' => 'Destinatario', 'destinatario_telefono' => '0',
    'destinatario_direccion' => 'Sin dirección registrada', 'instrucciones_entrega' => '',
    'descripcion_contenido' => 'Entrega creada desde mis paquetes',
    'recoger_cambios' => 0, 'costo_total' => 8000, 'valor_recaudo' => 20000,
    'mensajero_id' => 7
]);
$manualId = (int) $db->lastInsertId();
$modelo->registrarEntrega(7, $payload + ['numero_guia' => 'MANUAL-1', 'recibio_cambios' => 1]);
$manual = $db->query("SELECT * FROM paquetes WHERE id = {$manualId}")->fetch(PDO::FETCH_ASSOC);
verificar((float) $manual['costo_envio'] === 13000.0 && (int) $manual['recoger_cambios'] === 1, 'Entrega sin rótulo: no sumó el adicional');
echo "OK: entrega sin rótulo creada por el mensajero\n";

$db->exec("INSERT INTO paquetes (id, numero_guia, cliente_id, mensajero_id, costo_envio, recoger_cambios, estado) VALUES (99, 'FALLO', 10, 7, 8000, 0, 'pendiente');
CREATE TRIGGER fallo_comprobante BEFORE INSERT ON comprobantes WHEN NEW.paquete_id = 99 BEGIN SELECT RAISE(ABORT, 'fallo de comprobante'); END");
$fallo = false;
try {
    $modelo->registrarEntrega(7, $payload + ['paquete_id' => 99, 'recibio_cambios' => 1]);
} catch (PDOException $e) {
    $fallo = true;
}
$paquete = $db->query('SELECT * FROM paquetes WHERE id = 99')->fetch(PDO::FETCH_ASSOC);
verificar($fallo && (float) $paquete['costo_envio'] === 8000.0 && (int) $paquete['recoger_cambios'] === 0 && $paquete['estado'] === 'pendiente', 'El fallo debe revertir el adicional y la entrega');
verificar((int) $db->query('SELECT COUNT(*) FROM entregas WHERE paquete_id = 99')->fetchColumn() === 0, 'El fallo dejó una entrega parcial');
echo "OK: rollback del cobro cuando falla la entrega\n";

$rechazado = false;
try {
    $modelo->registrarEntrega(8, $payload + ['paquete_id' => 99, 'recibio_cambios' => 1]);
} catch (Exception $e) {
    $rechazado = true;
}
verificar($rechazado, 'Otro mensajero no debe poder modificar el paquete');
echo "OK: se conserva la autorización del mensajero\n";
