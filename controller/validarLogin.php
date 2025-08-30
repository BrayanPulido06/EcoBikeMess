<?php
// Iniciar sesión al principio
session_start();

// Validación de datos iniciales
if (!isset($_POST['correo']) || !isset($_POST['password'])) {
    header("location: login.php?mensaje=Datos incompletos");
    exit;
}

$correo = trim($_POST['correo']);
$password = trim($_POST['password']);

// Validación de campos vacíos
if (empty($correo) || empty($password)) {
    header("location: login.php?mensaje=Correo y contraseña son obligatorios");
    exit;
}

// Conexión a la base de datos usando el método original
$conexion = mysqli_connect("localhost", "root", "", "ecobikemess");

if (!$conexion) {
    header("location: login.php?mensaje=Error de conexión a la base de datos");
    exit;
}

// Configurar charset para evitar problemas de codificación
mysqli_set_charset($conexion, "utf8");

// Escape del correo para prevenir inyección SQL
$correo_escaped = mysqli_real_escape_string($conexion, $correo);

$usuario_encontrado = null;
$tipo_usuario = null;

// PASO 1: Buscar en tabla de administradores
// Removido el campo 'estado' que no existe en la BD
$consulta_admin = "SELECT id, tipo_documento, numDocumento, nombres, apellidos, correo, telefono, password, fecha_registro 
                   FROM administradores 
                   WHERE correo = '$correo_escaped'";

$resultado_admin = mysqli_query($conexion, $consulta_admin);

if (!$resultado_admin) {
    mysqli_close($conexion);
    header("location: login.php?mensaje=Error en la consulta");
    exit;
}

if (mysqli_num_rows($resultado_admin) > 0) {
    $usuario_encontrado = mysqli_fetch_assoc($resultado_admin);
    $tipo_usuario = 'administrador';
    mysqli_free_result($resultado_admin);
} else {
    mysqli_free_result($resultado_admin);
    
    // PASO 2: Buscar en tabla de clientes
    // Removido el campo 'estado' que no existe en la BD
    $consulta_cliente = "SELECT id, tipo_documento, numDocumento, nombre_emprendimiento, tipo_producto, cuenta_bancaria, nombres, apellidos, correo, telefono, instagram, password, fecha_registro 
                        FROM clientes 
                        WHERE correo = '$correo_escaped'";
    
    $resultado_cliente = mysqli_query($conexion, $consulta_cliente);
    
    if (!$resultado_cliente) {
        mysqli_close($conexion);
        header("location: login.php?mensaje=Error en la consulta");
        exit;
    }
    
    if (mysqli_num_rows($resultado_cliente) > 0) {
        $usuario_encontrado = mysqli_fetch_assoc($resultado_cliente);
        $tipo_usuario = 'cliente';
        mysqli_free_result($resultado_cliente);
    } else {
        mysqli_free_result($resultado_cliente);
        
        // PASO 3: Buscar en tabla de mensajeros
        $consulta_mensajero = "SELECT id, tipo_documento, numDocumento, nombres, apellidos, telefono, correo, password, fecha_registro 
                              FROM mensajeros
                              WHERE correo = '$correo_escaped'";
        
        $resultado_mensajero = mysqli_query($conexion, $consulta_mensajero);
        
        if (!$resultado_mensajero) {
            mysqli_close($conexion);
            header("location: login.php?mensaje=Error en la consulta");
            exit;
        }
        
        if (mysqli_num_rows($resultado_mensajero) > 0) {
            $usuario_encontrado = mysqli_fetch_assoc($resultado_mensajero);
            $tipo_usuario = 'mensajero';
            mysqli_free_result($resultado_mensajero);
        } else {
            mysqli_free_result($resultado_mensajero);
        }
    }
}

// PASO 4: Validar si se encontró el usuario
if ($usuario_encontrado === null) {
    mysqli_close($conexion);
    header("location: login.php?mensaje=Datos incompletos");
    exit;
}

// PASO 5: Verificar la contraseña
$password_bd = $usuario_encontrado['password'];
$password_valida = false;

if (substr($password_bd, 0, 3) === '$2y') {
    // Contraseña encriptada - usar password_verify
    $password_valida = password_verify($password, $password_bd);
} else {
    // Contraseña en texto plano (para compatibilidad)
    $password_valida = ($password === $password_bd);
    
    // OPCIONAL: Actualizar a contraseña encriptada automáticamente
    if ($password_valida) {
        $nueva_hash = password_hash($password, PASSWORD_DEFAULT);
        $nueva_hash_escaped = mysqli_real_escape_string($conexion, $nueva_hash);
        $id_escaped = mysqli_real_escape_string($conexion, $usuario_encontrado['id']);
        
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
        }
        
        if (!empty($tabla)) {
            $actualizar = "UPDATE $tabla SET password = '$nueva_hash_escaped' WHERE id = '$id_escaped'";
            mysqli_query($conexion, $actualizar);
        }
    }
}

// PASO 6: Procesar login exitoso o fallido
if ($password_valida) {
    // Login exitoso - Configurar sesión según el tipo de usuario
    
    // Datos comunes para todos los tipos de usuarios
    $_SESSION['user_id'] = $usuario_encontrado['id'];
    $_SESSION['user_email'] = $usuario_encontrado['correo'];
    $_SESSION['user_phone'] = $usuario_encontrado['telefono'];
    $_SESSION['user_type'] = $tipo_usuario;
    $_SESSION['login_time'] = time();
    
    if ($tipo_usuario === 'administrador') {
        // Datos específicos del administrador
        $_SESSION['user_name'] = $usuario_encontrado['nombres'] . ' ' . $usuario_encontrado['apellidos'];
        $_SESSION['admin_documento_tipo'] = $usuario_encontrado['tipo_documento'];
        $_SESSION['admin_cedula'] = $usuario_encontrado['numDocumento'];
        $_SESSION['is_admin'] = true;
        
        mysqli_close($conexion);
        header("location: ../views/administrador/homepage/adminHomepage.php");
        exit;
        
    } elseif ($tipo_usuario === 'cliente') {
        // Datos específicos del cliente
        $_SESSION['user_name'] = $usuario_encontrado['nombres'] . ' ' . $usuario_encontrado['apellidos'];
        $_SESSION['client_documento_tipo'] = $usuario_encontrado['tipo_documento'];
        $_SESSION['client_num_documento'] = $usuario_encontrado['numDocumento'];
        $_SESSION['client_emprendimiento'] = $usuario_encontrado['nombre_emprendimiento'];
        $_SESSION['client_tipo_producto'] = $usuario_encontrado['tipo_producto'];
        $_SESSION['client_cuenta_bancaria'] = $usuario_encontrado['cuenta_bancaria'];
        $_SESSION['client_instagram'] = $usuario_encontrado['instagram'];
        $_SESSION['is_client'] = true;
        
        mysqli_close($conexion);
        header("location: ../views/clientes/homepage/clientHomepage.php");
        exit;
        
    } elseif ($tipo_usuario === 'mensajero') {
        // Datos específicos del mensajero
        $_SESSION['user_name'] = $usuario_encontrado['nombres'] . ' ' . $usuario_encontrado['apellidos'];
        $_SESSION['mensajero_documento_tipo'] = $usuario_encontrado['tipo_documento'];
        $_SESSION['mensajero_num_documento'] = $usuario_encontrado['numDocumento'];
        $_SESSION['mensajero_nombres'] = $usuario_encontrado['nombres'];
        $_SESSION['mensajero_apellidos'] = $usuario_encontrado['apellidos'];
        $_SESSION['is_mensajero'] = true;
        
        mysqli_close($conexion);
        header("location: ../mensajeros/homepage/mensajeroHomepage.php");
        exit;
    }
    
} else {
    // Contraseña incorrecta
    mysqli_close($conexion);
    header("location: login.php?mensaje=Datos incompletos");
    exit;
}
?>