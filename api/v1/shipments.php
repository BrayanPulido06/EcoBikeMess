<?php
declare(strict_types=1);

require_once __DIR__ . '/../../includes/paths.php';
require_once __DIR__ . '/../../models/conexionGlobal.php';
require_once __DIR__ . '/../../models/enviarPaqueteModels.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

function apiReply(int $status, array $payload): void
{
    http_response_code($status);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function apiTrackingUrl(string $guide): string
{
    $baseUrl = rtrim((string) (getenv('ECOBIKE_PUBLIC_BASE_URL') ?: 'https://ecobikemess.com'), '/');
    return filter_var($baseUrl, FILTER_VALIDATE_URL) ? $baseUrl . route_url('home', ['guia' => $guide]) . '#rastreo' : '';
}

function apiBool(mixed $value): int
{
    return in_array($value, [true, 1, '1', 'true', 'yes', 'si', 'on'], true) ? 1 : 0;
}

function apiRequiredString(array $data, string $key, int $maxLength = 2000): string
{
    $value = trim((string) ($data[$key] ?? ''));
    if ($value === '' || strlen($value) > $maxLength) {
        throw new InvalidArgumentException("El campo {$key} es obligatorio o excede el máximo permitido.");
    }
    return $value;
}

try {
    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
        header('Allow: POST');
        apiReply(405, ['success' => false, 'error' => 'method_not_allowed', 'message' => 'Usa POST para crear un envío.']);
    }

    $contentType = strtolower((string) ($_SERVER['CONTENT_TYPE'] ?? ''));
    if (!str_contains($contentType, 'application/json')) {
        apiReply(415, ['success' => false, 'error' => 'unsupported_media_type', 'message' => 'Envía el contenido como application/json.']);
    }

    $rawKey = trim((string) ($_SERVER['HTTP_X_API_KEY'] ?? ''));
    if ($rawKey === '' && preg_match('/^Bearer\s+(.+)$/i', (string) ($_SERVER['HTTP_AUTHORIZATION'] ?? ''), $matches)) {
        $rawKey = trim($matches[1]);
    }
    if ($rawKey === '' || strlen($rawKey) > 200) {
        apiReply(401, ['success' => false, 'error' => 'unauthorized', 'message' => 'Falta una clave API válida.']);
    }

    $db = conexionDB();
    $keyStmt = $db->prepare("SELECT k.id AS api_key_id, k.cliente_id, c.usuario_id,
            c.nombre_emprendimiento, c.direccion_principal, u.telefono AS remitente_telefono
        FROM api_client_keys k
        INNER JOIN clientes c ON c.id = k.cliente_id
        INNER JOIN usuarios u ON u.id = c.usuario_id AND u.estado = 'activo'
        WHERE k.key_hash = :key_hash AND k.estado = 'activo'
        LIMIT 1");
    $keyStmt->execute([':key_hash' => hash('sha256', $rawKey)]);
    $apiClient = $keyStmt->fetch(PDO::FETCH_ASSOC);
    if (!$apiClient) {
        apiReply(401, ['success' => false, 'error' => 'unauthorized', 'message' => 'La clave API no es válida o está inactiva.']);
    }
    $senderName = trim((string) ($apiClient['nombre_emprendimiento'] ?? ''));
    $senderPhone = trim((string) ($apiClient['remitente_telefono'] ?? ''));
    $senderAddress = trim((string) ($apiClient['direccion_principal'] ?? ''));
    if ($senderName === '' || $senderPhone === '' || $senderAddress === '') {
        throw new InvalidArgumentException('Completa el nombre, teléfono y dirección principal de la tienda en Mi perfil para usar la API.');
    }
    $db->prepare('UPDATE api_client_keys SET ultimo_uso = NOW() WHERE id = :id')->execute([':id' => $apiClient['api_key_id']]);
    $envioModel = new EnvioModel();

    $body = json_decode((string) file_get_contents('php://input'), true);
    if (!is_array($body)) {
        apiReply(400, ['success' => false, 'error' => 'invalid_json', 'message' => 'El cuerpo debe ser un objeto JSON válido.']);
    }

    $externalOrderId = apiRequiredString($body, 'external_order_id', 100);
    $duplicate = $db->prepare('SELECT numero_guia, costo_envio FROM paquetes WHERE cliente_id = :cliente_id AND external_order_id = :external_order_id LIMIT 1');
    $duplicate->execute([':cliente_id' => $apiClient['cliente_id'], ':external_order_id' => $externalOrderId]);
    $existingPackage = $duplicate->fetch(PDO::FETCH_ASSOC);
    if ($existingPackage) {
        apiReply(200, ['success' => true, 'duplicate' => true, 'external_order_id' => $externalOrderId, 'guide' => $existingPackage['numero_guia'], 'shipping_cost' => (float) $existingPackage['costo_envio'], 'tracking_url' => apiTrackingUrl((string) $existingPackage['numero_guia'])]);
    }

    $dimensionOptions = [
        '0' => 'Menor o igual a 20 x 20 cm',
        '2000' => 'Entre 21x21 y 30x30 cm',
        '4000' => 'Entre 31x31 y 35x35 cm',
        '7000' => 'Entre 36x36 y 40x40 cm',
        '10000' => 'Entre 41x41 y 45x45 cm',
        '12000' => 'Entre 46x46 y 49x49 cm',
        'notificar' => 'Igual o mayor a 50 x 50 cm',
    ];
    $dimensionKey = (string) ($body['dimensiones_paquete'] ?? '');
    if (!isset($dimensionOptions[$dimensionKey]) || $dimensionKey === 'notificar') {
        throw new InvalidArgumentException('dimensiones_paquete debe ser uno de: 0, 2000, 4000, 7000, 10000 o 12000. Para paquetes de 50 cm o más, solicita una cotización por WhatsApp.');
    }

    $recipientPhone = apiRequiredString($body, 'destinatario_telefono', 20);
    if (!preg_match('/^(?:\d{10}|Flex)$/i', $recipientPhone)) {
        throw new InvalidArgumentException('destinatario_telefono debe tener 10 dígitos o ser Flex.');
    }
    $payer = strtolower(apiRequiredString($body, 'envio_destinatario', 10));
    if (!in_array($payer, ['si', 'no'], true)) {
        throw new InvalidArgumentException('envio_destinatario debe ser si o no.');
    }

    $pickupDate = trim((string) ($body['fecha_programada_recoleccion'] ?? ''));
    if ($pickupDate !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $pickupDate)) {
        throw new InvalidArgumentException('fecha_programada_recoleccion debe tener formato YYYY-MM-DD.');
    }
    if ($pickupDate !== '') {
        $parsedPickupDate = DateTimeImmutable::createFromFormat('!Y-m-d', $pickupDate, new DateTimeZone('America/Bogota'));
        $dateErrors = DateTimeImmutable::getLastErrors();
        if (!$parsedPickupDate || ($dateErrors !== false && ($dateErrors['warning_count'] > 0 || $dateErrors['error_count'] > 0)) || $parsedPickupDate->format('Y-m-d') !== $pickupDate || $parsedPickupDate < new DateTimeImmutable('today', new DateTimeZone('America/Bogota'))) {
            throw new InvalidArgumentException('fecha_programada_recoleccion debe ser hoy o una fecha futura válida.');
        }
    }
    foreach (['valor_recaudo'] as $amountField) {
        if (!isset($body[$amountField]) || !is_numeric($body[$amountField]) || (float) $body[$amountField] < 0) {
            throw new InvalidArgumentException("{$amountField} debe ser un número mayor o igual a cero.");
        }
    }
    $hasCollection = apiBool($body['tiene_recaudo'] ?? false);
    $collectionAmount = $hasCollection ? (float) $body['valor_recaudo'] : 0.0;
    $shippingCost = 8000
        + (int) $dimensionKey
        + (apiBool($body['envio_mismo_dia'] ?? false) ? 2000 : 0)
        + (apiBool($body['zona_periferica'] ?? false) ? 4000 : 0)
        + (apiBool($body['recoger_cambios'] ?? false) ? 5000 : 0)
        + (apiBool($body['embalaje'] ?? false) ? 1000 : 0);
    if ($hasCollection) {
        $shippingCost += 3000 + ($collectionAmount >= 300000 ? (int) (floor(($collectionAmount - 300000) / 100000) * 1000) : 0);
    }

    $data = [
        'cliente_id' => (int) $apiClient['cliente_id'],
        'external_order_id' => $externalOrderId,
        'creado_por' => (int) $apiClient['usuario_id'],
        'remitente_nombre' => $senderName,
        'remitente_telefono' => $senderPhone,
        'remitente_direccion' => $senderAddress,
        'observaciones_recoleccion' => trim((string) ($body['observaciones_recoleccion'] ?? '')),
        'fecha_programada_recoleccion' => $pickupDate,
        'destinatario_nombre' => apiRequiredString($body, 'destinatario_nombre'),
        'destinatario_telefono' => $recipientPhone,
        'destinatario_direccion' => apiRequiredString($body, 'destinatario_direccion'),
        'instrucciones_entrega' => trim((string) ($body['instrucciones_entrega'] ?? '')),
        'descripcion_contenido' => apiRequiredString($body, 'descripcion_contenido'),
        'dimensiones' => $dimensionOptions[$dimensionKey],
        'envio_mismo_dia' => apiBool($body['envio_mismo_dia'] ?? false),
        'zona_periferica' => apiBool($body['zona_periferica'] ?? false),
        'recoger_cambios' => apiBool($body['recoger_cambios'] ?? false),
        'embalaje' => apiBool($body['embalaje'] ?? false),
        'envio_destinatario' => $payer,
        'tiene_recaudo' => $hasCollection,
        'valor_recaudo' => $collectionAmount,
        'costo_total' => $shippingCost,
    ];

    $guide = '';
    for ($attempt = 0; $attempt < 5; $attempt++) {
        $guide = 'EBM-' . date('Ymd') . '-' . strtoupper(bin2hex(random_bytes(3)));
        if (!$envioModel->verificarGuia($guide)) {
            break;
        }
    }
    if ($guide === '' || $envioModel->verificarGuia($guide)) {
        throw new RuntimeException('No se pudo generar una guía única. Intenta de nuevo.');
    }
    $data['numero_guia'] = $guide;

    try {
        $envioModel->registrarEnvio($data);
    } catch (Throwable $e) {
        if ($duplicate->execute([':cliente_id' => $apiClient['cliente_id'], ':external_order_id' => $externalOrderId])) {
            $existingPackage = $duplicate->fetch(PDO::FETCH_ASSOC);
            if ($existingPackage) {
                apiReply(200, ['success' => true, 'duplicate' => true, 'external_order_id' => $externalOrderId, 'guide' => $existingPackage['numero_guia'], 'shipping_cost' => (float) $existingPackage['costo_envio'], 'tracking_url' => apiTrackingUrl((string) $existingPackage['numero_guia'])]);
            }
        }
        throw $e;
    }

    $trackingUrl = apiTrackingUrl($guide);
    if ($trackingUrl === '') {
        throw new RuntimeException('No se pudo construir el enlace público de rastreo.');
    }
    apiReply(201, [
        'success' => true,
        'duplicate' => false,
        'external_order_id' => $externalOrderId,
        'guide' => $guide,
        'shipping_cost' => $shippingCost,
        'tracking_url' => $trackingUrl,
        'message' => 'Envío creado. Comparte la guía y el enlace de rastreo con el comprador.',
    ]);
} catch (InvalidArgumentException $e) {
    apiReply(422, ['success' => false, 'error' => 'validation_error', 'message' => $e->getMessage()]);
} catch (Throwable $e) {
    error_log('[shop-api] ' . $e->getMessage());
    apiReply(500, ['success' => false, 'error' => 'internal_error', 'message' => 'No fue posible procesar el envío.']);
}
