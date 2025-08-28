<?php
require_once ('../../config/conexionGlobal.php');
session_start();

// Verificar que los datos lleguen por POST
if (!isset($_POST['numDocumento']) || !isset($_POST['new_password']) || !isset($_POST['token']) || !isset($_POST['tipo_usuario'])) {
    header("location: login.php?mensaje=Error: Datos incompletos");
    exit;
}

$numDocumento = trim($_POST['numDocumento']);
$new_password = trim($_POST['new_password']);
$token = trim($_POST['token']);
$tipo_usuario = trim($_POST['tipo_usuario']);

// Validar que no estén vacíos
if (empty($numDocumento) || empty($new_password) || empty($token) || empty($tipo_usuario)) {
    header("location: login.php?mensaje=Error: Todos los campos son obligatorios");
    exit;
}

// Validar longitud mínima de contraseña
if (strlen($new_password) < 6) {
    header("location: Contraseña.php?token=$token&type=$tipo_usuario&mensaje=Error: La contraseña debe tener al menos 6 caracteres");
    exit;
}

// Conectar a la base de datos
$conexion = mysqli_connect("localhost", "root", "", "ecobikemess");
if (!$conexion) {
    header("location: login.php?mensaje=Error: No se pudo conectar a la base de datos");
    exit;
}

// Determinar la tabla según el tipo de usuario
$tabla = '';
switch($tipo_usuario) {
    case 'administrador':
        $tabla = 'administradores';
        break;
    case 'cliente':
        $tabla = 'clientes';
        break;
    case 'mensajero':
        $tabla = 'mensajeros';
        break;
    default:
        mysqli_close($conexion);
        header("location: login.php?mensaje=Error: Tipo de usuario no válido");
        exit;
}

// Escapar datos para evitar inyección SQL
$token_escaped = mysqli_real_escape_string($conexion, $token);
$numDocumento_escaped = mysqli_real_escape_string($conexion, $numDocumento);

// Verificar que el token sea válido y corresponda al usuario
$consulta_verificar = "SELECT id, numDocumento FROM $tabla WHERE tokenPassword = '$token_escaped' AND numDocumento = '$numDocumento_escaped'";
$resultado_verificar = mysqli_query($conexion, $consulta_verificar);

if (!$resultado_verificar || mysqli_num_rows($resultado_verificar) == 0) {
    mysqli_close($conexion);
    header("location: login.php?mensaje=Error: Token inválido o número de documento incorrecto");
    exit;
}

// Usuario encontrado, proceder con la actualización
$usuario = mysqli_fetch_assoc($resultado_verificar);
$usuario_id = $usuario['id'];

// Encriptar la nueva contraseña
$password_hash = password_hash($new_password, PASSWORD_DEFAULT);

// Actualizar la contraseña y limpiar el token
$password_escaped = mysqli_real_escape_string($conexion, $password_hash);
$id_escaped = mysqli_real_escape_string($conexion, $usuario_id);

$consulta_update = "UPDATE $tabla SET 
                    password = '$password_escaped', 
                    tokenPassword = NULL 
                    WHERE id = '$id_escaped'";

$resultado_update = mysqli_query($conexion, $consulta_update);

if (!$resultado_update) {
    $error_mensaje = mysqli_error($conexion);
    mysqli_close($conexion);
    header("location: Contraseña.php?token=$token&type=$tipo_usuario&mensaje=Error al actualizar la contraseña: " . urlencode($error_mensaje));
    exit;
}

// Verificar si se actualizó alguna fila
$filas_afectadas = mysqli_affected_rows($conexion);
mysqli_close($conexion);

if ($filas_afectadas > 0) {
    header("location: login.php?mensaje=Contraseña actualizada correctamente. Ya puedes iniciar sesión.");
    exit;
} else {
    header("location: login.php?mensaje=Error: No se pudo actualizar la contraseña");
    exit;
}
?>