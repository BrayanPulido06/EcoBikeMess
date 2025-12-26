<?php
function conexionDB(){
    try {
        $dsn = "mysql:host=127.0.0.1;port=3307;dbname=ecobikemess;charset=utf8";
        $db = new PDO($dsn, 'root', '');
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        return $db;
    } catch (PDOException $PDOe) {
        error_log("Error de conexión PDO: " . $PDOe->getMessage());
        return null;
    }
}
