<?php
declare(strict_types=1);

/**
 * URL base path where the project is mounted.
 * Local XAMPP example: "/ecobikemess"
 * Host root example:   ""
 */
function app_base_path(): string
{
    $projectRoot = realpath(__DIR__ . DIRECTORY_SEPARATOR . '..');
    $documentRoot = isset($_SERVER['DOCUMENT_ROOT']) ? realpath((string) $_SERVER['DOCUMENT_ROOT']) : false;

    if ($projectRoot && $documentRoot) {
        $projectRootNorm = str_replace('\\', '/', $projectRoot);
        $documentRootNorm = rtrim(str_replace('\\', '/', $documentRoot), '/');

        if ($documentRootNorm !== '' && strpos($projectRootNorm, $documentRootNorm) === 0) {
            $relative = substr($projectRootNorm, strlen($documentRootNorm));
            $relative = '/' . ltrim((string) $relative, '/');
            $relative = rtrim($relative, '/');
            return $relative === '/' ? '' : $relative;
        }
    }

    $scriptDir = isset($_SERVER['SCRIPT_NAME']) ? dirname((string) $_SERVER['SCRIPT_NAME']) : '';
    $scriptDir = str_replace('\\', '/', (string) $scriptDir);
    $scriptDir = rtrim($scriptDir, '/');
    return $scriptDir === '/' ? '' : $scriptDir;
}

function app_url(string $path = ''): string
{
    $base = app_base_path();
    $path = ltrim($path, '/');
    return $base . ($path !== '' ? '/' . $path : '');
}

function app_asset_url(string $path = ''): string
{
    return app_url('public/' . ltrim($path, '/'));
}

function app_controller_url(string $path = ''): string
{
    return app_url('controller/' . ltrim($path, '/'));
}

function app_routes(): array
{
    static $routes = null;

    if ($routes !== null) {
        return $routes;
    }

    $routes = [
        'home' => '',
        'login' => 'login',
        'register' => 'crear-cuenta',
        'forgot-password' => 'recuperar-contrasena',
        'reset-password' => 'cambiar-contrasena',
        'client.dashboard' => 'cliente/inicio',
        'client.send-package' => 'cliente/enviar-paquete',
        'client.orders' => 'cliente/mis-pedidos',
        'client.team' => 'cliente/equipo-trabajo',
        'client.billing' => 'cliente/facturacion',
        'client.profile' => 'cliente/mi-perfil',
        'admin.dashboard' => 'admin/inicio',
        'admin.packages' => 'admin/paquetes',
        'admin.create-shipment' => 'admin/digitar-envio',
        'admin.collections' => 'admin/asignar-recolecciones',
        'admin.billing' => 'admin/facturacion',
        'admin.users' => 'admin/usuarios',
        'admin.profile' => 'admin/mi-perfil',
        'messenger.dashboard' => 'mensajero/inicio',
        'messenger.packages' => 'mensajero/mis-paquetes',
        'messenger.history' => 'mensajero/historial',
        'messenger.pickups' => 'mensajero/recolecciones',
        'messenger.profile' => 'mensajero/mi-perfil',
        'logout' => 'logout',
    ];

    return $routes;
}

function route_path(string $name): string
{
    $routes = app_routes();
    if (!array_key_exists($name, $routes)) {
        throw new InvalidArgumentException("Ruta no definida: {$name}");
    }

    return $routes[$name];
}

function route_url(string $name, array $query = []): string
{
    $url = app_url(route_path($name));

    if ($query !== []) {
        $separator = strpos($url, '?') === false ? '?' : '&';
        $url .= $separator . http_build_query($query);
    }

    return $url;
}

function redirect_route(string $name, array $query = [], int $statusCode = 302): void
{
    header('Location: ' . route_url($name, $query), true, $statusCode);
    exit;
}
