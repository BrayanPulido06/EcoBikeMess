<?php
require_once ('config.php');

$email = $_POST['email'];
$password = $_POST['password'];

$query = "SELECT * FROM tp_registro WHERE correo = '$email' AND password = '$password' AND estado = 1";
$result = $conexion->query($query);

if($result->num_rows > 0) {
    header("Location: ../views/inicio.php");
} else {
    header("Location: ../views/login/login.php");
}