<?php
require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/paths.php';
function ensureSessionStarted()
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
}

function userHasRole($roles)
{
    if ($roles === null) {
        return true;
    }
    $role = $_SESSION['user_role'] ?? '';
    return in_array($role, $roles, true);
}

function requireWebAuth($roles = null, $redirect = null)
{
    ensureSessionStarted();
    $redirect = $redirect ?? route_url('login', ['error' => 'Debes iniciar sesión.']);
    if (!isset($_SESSION['user_id']) || !userHasRole($roles)) {
        header("Location: {$redirect}");
        exit;
    }
}

function requireApiAuth($roles = null, $message = 'No autorizado')
{
    ensureSessionStarted();
    if (!isset($_SESSION['user_id']) || !userHasRole($roles)) {
        http_response_code(401);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['success' => false, 'message' => $message]);
        exit;
    }
}

function requireApiAuthLegacy($roles = null, $message = 'No autorizado')
{
    ensureSessionStarted();
    if (!isset($_SESSION['user_id']) || !userHasRole($roles)) {
        http_response_code(401);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['status' => 'error', 'msg' => $message]);
        exit;
    }
}
?>
