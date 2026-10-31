<?php
require_once __DIR__ . '/../includes/bootstrap.php';

if (!class_exists('EcoBikeSlowQueryStatement', false)) {
    class EcoBikeSlowQueryStatement extends PDOStatement
    {
        protected function __construct()
        {
        }

        public function execute(?array $params = null): bool
        {
            $startedAt = hrtime(true);
            try {
                return parent::execute($params);
            } finally {
                ecoBikeLogSlowSql($this->queryString, $startedAt);
            }
        }
    }
}

if (!class_exists('EcoBikeInstrumentedPDO', false)) {
    class EcoBikeInstrumentedPDO extends PDO
    {
        public function query(string $query, ?int $fetchMode = null, mixed ...$fetchModeArgs): PDOStatement|false
        {
            $startedAt = hrtime(true);
            try {
                if ($fetchMode === null) {
                    return parent::query($query);
                }
                return parent::query($query, $fetchMode, ...$fetchModeArgs);
            } finally {
                ecoBikeLogSlowSql($query, $startedAt);
            }
        }

        public function exec(string $statement): int|false
        {
            $startedAt = hrtime(true);
            try {
                return parent::exec($statement);
            } finally {
                ecoBikeLogSlowSql($statement, $startedAt);
            }
        }
    }
}

if (!function_exists('ecoBikeLogSlowSql')) {
    function ecoBikeLogSlowSql(string $sql, int $startedAt): void
    {
        $durationMs = (hrtime(true) - $startedAt) / 1_000_000;
        if ($durationMs < 500) {
            return;
        }

        $requestPath = parse_url((string) ($_SERVER['REQUEST_URI'] ?? ''), PHP_URL_PATH) ?: 'cli';
        $normalizedSql = preg_replace('/\\s+/', ' ', trim($sql)) ?? trim($sql);
        error_log(sprintf(
            '[slow-sql] duration_ms=%.1f path=%s sql=%s',
            $durationMs,
            $requestPath,
            substr($normalizedSql, 0, 2000)
        ));
    }
}

function conexionDB(){
    try {
        // IMPORTANTE: Si tu XAMPP dice Port: 3307, cambia el 3306 de abajo por 3307
        $dsn = "mysql:host=localhost;dbname=u478643953_ecobikemess;charset=utf8";
        $connectionStartedAt = hrtime(true);
        $db = new EcoBikeInstrumentedPDO($dsn, 'u478643953_ecobikemess', '/EcoBikeMess0310');
        $db->setAttribute(PDO::ATTR_STATEMENT_CLASS, [EcoBikeSlowQueryStatement::class, []]);
        $connectionDurationMs = (hrtime(true) - $connectionStartedAt) / 1_000_000;
        if ($connectionDurationMs >= 1000) {
            $requestPath = parse_url((string) ($_SERVER['REQUEST_URI'] ?? ''), PHP_URL_PATH) ?: 'cli';
            error_log(sprintf('[slow-db-connect] duration_ms=%.1f path=%s', $connectionDurationMs, $requestPath));
        }
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $db->exec("SET time_zone = '-05:00'");
        return $db;
    } catch (PDOException $PDOe) {
        throw new Exception("Fallo SQL: " . $PDOe->getMessage());
    }
}
