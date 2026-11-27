<?php
require_once __DIR__ . '/../includes/auth.php';
requireWebAuth(['cliente'], route_url('login', ['error' => 'Debes iniciar sesión como cliente.']));
require_once __DIR__ . '/../models/conexionGlobal.php';

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    http_response_code(405);
    exit('Método no permitido.');
}

$csrf = (string) ($_POST['csrf_token'] ?? '');
if ($csrf === '' || !hash_equals((string) ($_SESSION['api_keys_csrf'] ?? ''), $csrf)) {
    redirect_route('client.profile', ['error' => 'La solicitud expiró. Recarga la página e inténtalo de nuevo.']);
}

try {
    $db = conexionDB();
    $clientStmt = $db->prepare('SELECT id FROM clientes WHERE usuario_id = :usuario_id LIMIT 1');
    $clientStmt->execute([':usuario_id' => (int) $_SESSION['user_id']]);
    $clientId = (int) $clientStmt->fetchColumn();
    if ($clientId <= 0) {
        throw new RuntimeException('No encontramos una tienda asociada a tu cuenta.');
    }

    $db->exec("CREATE TABLE IF NOT EXISTS api_client_keys (
        id INT PRIMARY KEY AUTO_INCREMENT,
        cliente_id INT NOT NULL,
        key_hash CHAR(64) NOT NULL UNIQUE,
        key_prefix VARCHAR(24) NOT NULL,
        nombre VARCHAR(100) NOT NULL,
        estado ENUM('activo', 'inactivo') NOT NULL DEFAULT 'activo',
        fecha_creacion TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        ultimo_uso TIMESTAMP NULL,
        FOREIGN KEY (cliente_id) REFERENCES clientes(id) ON DELETE CASCADE,
        INDEX idx_api_client_keys_cliente_estado (cliente_id, estado)
    )");

    $action = (string) ($_POST['action'] ?? '');
    if ($action === 'create') {
        $plainKey = 'ebm_live_' . rtrim(strtr(base64_encode(random_bytes(32)), '+/', '-_'), '=');
        $db->beginTransaction();
        $db->prepare("UPDATE api_client_keys SET estado = 'inactivo' WHERE cliente_id = :cliente_id AND estado = 'activo'")
            ->execute([':cliente_id' => $clientId]);
        $insert = $db->prepare("INSERT INTO api_client_keys (cliente_id, key_hash, key_prefix, nombre, estado)
            VALUES (:cliente_id, :key_hash, :key_prefix, 'API de tienda', 'activo')");
        $insert->execute([
            ':cliente_id' => $clientId,
            ':key_hash' => hash('sha256', $plainKey),
            ':key_prefix' => substr($plainKey, 0, 18),
        ]);
        $db->commit();
        $_SESSION['api_key_created_once'] = $plainKey;
        redirect_route('client.profile', ['api_key' => 'created']);
    }

    if ($action === 'revoke') {
        $db->prepare("UPDATE api_client_keys SET estado = 'inactivo' WHERE cliente_id = :cliente_id AND estado = 'activo'")
            ->execute([':cliente_id' => $clientId]);
        unset($_SESSION['api_key_created_once']);
        redirect_route('client.profile', ['api_key' => 'revoked']);
    }

    throw new InvalidArgumentException('Acción no válida.');
} catch (Throwable $error) {
    if (isset($db) && $db instanceof PDO && $db->inTransaction()) {
        $db->rollBack();
    }
    error_log('[client-api-key] ' . $error->getMessage());
    redirect_route('client.profile', ['error' => 'No fue posible actualizar el acceso a la API.']);
}
