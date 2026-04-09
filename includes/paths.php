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
