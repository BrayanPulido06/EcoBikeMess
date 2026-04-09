<?php
function conexionDB(){
    try {
        // IMPORTANTE: Si tu XAMPP dice Port: 3307, cambia el 3306 de abajo por 3307
        $dsn = "mysql:host=localhost;port=3306;dbname=u478643953_ecobikemess;charset=utf8";
        $db = new PDO($dsn, 'u478643953_ecobikemess', '/Brayan3675602');
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        return $db;
    } catch (PDOException $PDOe) {
        throw new Exception("Fallo SQL: " . $PDOe->getMessage());
    }
}
