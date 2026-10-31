<?php
declare(strict_types=1);

if (!defined('APP_TIMEZONE')) {
    define('APP_TIMEZONE', 'America/Bogota');
}

date_default_timezone_set(APP_TIMEZONE);

if (!defined('APP_REQUEST_TIMING_REGISTERED')) {
    define('APP_REQUEST_TIMING_REGISTERED', true);
    $appRequestStartedAt = hrtime(true);
    register_shutdown_function(static function () use ($appRequestStartedAt): void {
        $durationMs = (hrtime(true) - $appRequestStartedAt) / 1_000_000;
        if ($durationMs < 5000) {
            return;
        }

        $requestPath = parse_url((string) ($_SERVER['REQUEST_URI'] ?? ''), PHP_URL_PATH) ?: 'cli';
        $method = (string) ($_SERVER['REQUEST_METHOD'] ?? 'CLI');
        $status = http_response_code();
        error_log(sprintf(
            '[slow-request] duration_ms=%.1f method=%s status=%s path=%s memory_peak_bytes=%d',
            $durationMs,
            $method,
            $status === false ? 'unknown' : (string) $status,
            $requestPath,
            memory_get_peak_usage(true)
        ));
    });
}
