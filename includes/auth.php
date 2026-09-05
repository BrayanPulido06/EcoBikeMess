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
    $role = strtolower(trim((string) ($_SESSION['user_role'] ?? '')));
    $roles = array_map(function ($allowedRole) {
        return strtolower(trim((string) $allowedRole));
    }, (array) $roles);
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

function adminModulePermissions(): array
{
    return [
        'admin_dashboard',
        'admin_packages',
        'admin_create_shipment',
        'admin_collections',
        'admin_billing',
        'admin_notes',
        'admin_users',
    ];
}

function getCurrentAdminData(): ?array
{
    ensureSessionStarted();
    $userId = (int) ($_SESSION['user_id'] ?? 0);
    if ($userId <= 0 || !userHasRole(['admin', 'administrador'])) {
        return null;
    }

    static $cache = [];
    if (array_key_exists($userId, $cache)) {
        return $cache[$userId];
    }

    try {
        require_once __DIR__ . '/../models/conexionGlobal.php';
        $conn = conexionDB();
        $stmt = $conn->prepare(
            "SELECT a.rol, a.permisos_especiales
             FROM administradores a
             WHERE a.usuario_id = :usuario_id
             LIMIT 1"
        );
        $stmt->execute([':usuario_id' => $userId]);
        $admin = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    } catch (Throwable $e) {
        $admin = null;
    }

    $cache[$userId] = $admin;
    return $admin;
}

function currentAdminPermissions(): array
{
    $admin = getCurrentAdminData();
    if (!$admin) {
        return [];
    }

    $permissions = json_decode((string) ($admin['permisos_especiales'] ?? '[]'), true);
    return is_array($permissions) ? array_values(array_filter(array_map('strval', $permissions))) : [];
}

function currentAdminRole(): string
{
    $admin = getCurrentAdminData();
    return strtolower(trim((string) ($admin['rol'] ?? '')));
}

function adminCanAccess(string $permission): bool
{
    if (!userHasRole(['admin', 'administrador'])) {
        return false;
    }

    $role = currentAdminRole();
    if ($role === 'super_admin') {
        return true;
    }

    $permissions = currentAdminPermissions();
    if (in_array('todos', $permissions, true) || in_array($permission, $permissions, true)) {
        return true;
    }

    $hasModulePermissions = count(array_intersect($permissions, adminModulePermissions())) > 0;
    $configuredWithModulePermissions = in_array('admin_permissions_configured', $permissions, true);

    return !$configuredWithModulePermissions && !$hasModulePermissions;
}

function firstAllowedAdminRoute(): string
{
    $routes = [
        'admin_dashboard' => 'admin.dashboard',
        'admin_packages' => 'admin.packages',
        'admin_create_shipment' => 'admin.create-shipment',
        'admin_collections' => 'admin.collections',
        'admin_billing' => 'admin.billing',
        'admin_notes' => 'admin.notes',
        'admin_users' => 'admin.users',
    ];

    foreach ($routes as $permission => $route) {
        if (adminCanAccess($permission)) {
            return $route;
        }
    }

    return 'admin.profile';
}

function requireAdminPermission(string $permission, string $message = 'No autorizado'): void
{
    ensureSessionStarted();
    if (!adminCanAccess($permission)) {
        http_response_code(403);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['success' => false, 'message' => $message]);
        exit;
    }
}

function requireAdminPagePermission(string $permission): void
{
    ensureSessionStarted();
    if (!adminCanAccess($permission)) {
        header('Location: ' . route_url(firstAllowedAdminRoute(), ['error' => 'No tienes permiso para ese apartado.']));
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
