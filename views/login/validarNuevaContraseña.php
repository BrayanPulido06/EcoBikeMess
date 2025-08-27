<?php
require_once ('../../config/conexionGlobal.php');
session_start();

// Verificar que los datos lleguen por POST
if (!isset($_POST['numDocumento']) || !isset($_POST['new_password'])) {
    header("location: login.php?mensaje=Error: Datos no recibidos");
    exit;
}

$numDocumento = trim($_POST['numDocumento']);
$new_password = trim($_POST['new_password']);

// Validar que no estén vacíos
if (empty($numDocumento) || empty($new_password)) {
    header("location: login.php?mensaje=Error: Número de documento y contraseña son obligatorios");
    exit;
}

// Validar longitud mínima de contraseña
if (strlen($new_password) < 6) {
    header("location: login.php?mensaje=Error: La contraseña debe tener al menos 6 caracteres");
    exit;
}

// Usar la conexión del archivo global si existe, si no, crear una nueva
if (isset($conexion) && $conexion) {
    $conn = $conexion;
} else {
    $conn = mysqli_connect("localhost", "root", "", "ecobikemess");
    if (!$conn) {
        header("location: login.php?mensaje=Error: No se pudo conectar a la base de datos");
        exit;
    }
}

// Verificar que el usuario existe usando prepared statement
$consulta_verificar = "SELECT numDocumento, nombres, apellidos FROM tp_usuarios WHERE numDocumento = ?";
$stmt_verificar = mysqli_prepare($conn, $consulta_verificar);

if (!$stmt_verificar) {
    if (!isset($conexion)) mysqli_close($conn);
    header("location: login.php?mensaje=Error: No se pudo preparar la consulta de verificación");
    exit;
}

mysqli_stmt_bind_param($stmt_verificar, "s", $numDocumento);
mysqli_stmt_execute($stmt_verificar);
$resultado_verificar = mysqli_stmt_get_result($stmt_verificar);

if (!$resultado_verificar || mysqli_num_rows($resultado_verificar) == 0) {
    mysqli_stmt_close($stmt_verificar);
    if (!isset($conexion)) mysqli_close($conn);
    header("location: login.php?mensaje=Error: No se encontró un usuario con ese número de documento");
    exit;
}

// Usuario encontrado, proceder con la actualización
$usuario = mysqli_fetch_assoc($resultado_verificar);
mysqli_stmt_close($stmt_verificar);

// Encriptar la nueva contraseña
$password_hash = password_hash($new_password, PASSWORD_DEFAULT);

// Actualizar la contraseña - sin el campo solicitarContraseña que no existe
$consulta_update = "UPDATE tp_usuarios SET password = ? WHERE numDocumento = ?";
$stmt_update = mysqli_prepare($conn, $consulta_update);

if (!$stmt_update) {
    if (!isset($conexion)) mysqli_close($conn);
    header("location: login.php?mensaje=Error: No se pudo preparar la consulta de actualización");
    exit;
}

// Vincular parámetros y ejecutar
mysqli_stmt_bind_param($stmt_update, "ss", $password_hash, $numDocumento);
$resultado_update = mysqli_stmt_execute($stmt_update);

if (!$resultado_update) {
    $error_mensaje = mysqli_stmt_error($stmt_update);
    mysqli_stmt_close($stmt_update);
    if (!isset($conexion)) mysqli_close($conn);
    header("location: login.php?mensaje=Error al actualizar la contraseña: " . $error_mensaje);
    exit;
}

// Verificar si se actualizó alguna fila
$filas_afectadas = mysqli_stmt_affected_rows($stmt_update);
mysqli_stmt_close($stmt_update);

// Cerrar conexión solo si la creamos nosotros
if (!isset($conexion)) {
    mysqli_close($conn);
}

if ($filas_afectadas > 0) {
    header("location: login.php?mensaje=Contraseña actualizada correctamente");
    exit;
} else {
    header("location: login.php?mensaje=Error: No se pudo actualizar la contraseña - Ninguna fila afectada");
    exit;
}
?>