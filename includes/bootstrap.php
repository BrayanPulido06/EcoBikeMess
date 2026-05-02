<?php
declare(strict_types=1);

if (!defined('APP_TIMEZONE')) {
    define('APP_TIMEZONE', 'America/Bogota');
}

date_default_timezone_set(APP_TIMEZONE);

if (!function_exists('loadEnvFile')) {
    function loadEnvFile(string $path): void
    {
        if (!is_file($path) || !is_readable($path)) {
            return;
        }

        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if ($lines === false) {
            return;
        }

        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '' || str_starts_with($line, '#')) {
                continue;
            }

            $parts = explode('=', $line, 2);
            if (count($parts) !== 2) {
                continue;
            }

            $key = trim($parts[0]);
            $value = trim($parts[1]);

            if ($key === '') {
                continue;
            }

            if (
                (str_starts_with($value, '"') && str_ends_with($value, '"')) ||
                (str_starts_with($value, "'") && str_ends_with($value, "'"))
            ) {
                $value = substr($value, 1, -1);
            }

            $_ENV[$key] = $value;
            $_SERVER[$key] = $value;
            putenv($key . '=' . $value);
        }
    }
}

if (!defined('APP_ENV_FILES_LOADED')) {
    $projectRoot = dirname(__DIR__);
    loadEnvFile($projectRoot . '/.env');
    loadEnvFile($projectRoot . '/.env.local');
    define('APP_ENV_FILES_LOADED', true);
}

if (!function_exists('env_value')) {
    function env_value(string $key, mixed $default = null): mixed
    {
        $value = $_ENV[$key] ?? $_SERVER[$key] ?? getenv($key);
        if ($value === false || $value === null || $value === '') {
            return $default;
        }
        return $value;
    }
}

if (!function_exists('app_environment')) {
    function app_environment(): string
    {
        return (string) env_value('APP_ENV', 'production');
    }
}

if (!function_exists('is_local_environment')) {
    function is_local_environment(): bool
    {
        if (app_environment() === 'local') {
            return true;
        }

        $host = strtolower((string) ($_SERVER['HTTP_HOST'] ?? $_SERVER['SERVER_NAME'] ?? ''));
        return $host === 'localhost'
            || $host === '127.0.0.1'
            || $host === '::1'
            || str_starts_with($host, '192.168.')
            || str_starts_with($host, '10.')
            || preg_match('/^172\.(1[6-9]|2[0-9]|3[0-1])\./', $host) === 1;
    }
}
