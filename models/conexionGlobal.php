<?php
require_once __DIR__ . '/../includes/bootstrap.php';

function conexionDB(){
    try {
        $host = (string) env_value('DB_HOST', '127.0.0.1');
        $port = (string) env_value('DB_PORT', '3306');
        $dbname = (string) env_value('DB_NAME', 'ecobikemess');
        $charset = (string) env_value('DB_CHARSET', 'utf8mb4');
        $user = (string) env_value('DB_USER', 'root');
        $password = (string) env_value('DB_PASSWORD', '');
        $timezone = (string) env_value('DB_TIMEZONE', '-05:00');

        $dsn = "mysql:host={$host};port={$port};dbname={$dbname};charset={$charset}";
        $db = new PDO($dsn, $user, $password);
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $db->exec("SET time_zone = " . $db->quote($timezone));
        return $db;
    } catch (PDOException $PDOe) {
        throw new Exception("Fallo SQL: " . $PDOe->getMessage());
    }
}
