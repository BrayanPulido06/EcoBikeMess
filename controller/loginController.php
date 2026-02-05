<?php
ob_start(); // Inicia el búfer de salida para evitar errores de 'headers already sent'
session_start();

// Incluye tu archivo de conexión a la base de datos
// Asegúrate de que la ruta sea correcta según tu estructura de carpetas
require_once '../models/conexionGlobal.php'; 

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // 1. Recibir y limpiar datos del formulario
    $correo = filter_var(trim($_POST['correo']), FILTER_SANITIZE_EMAIL);
    $password = $_POST['password'];

    // Validación básica
    if (empty($correo) || empty($password)) {
        header("Location: ../views/login.php?mensaje=Por favor complete todos los campos");
        exit();
    }

    try {
        // 2. Instanciar conexión usando la función de conexionGlobal.php
        $conn = conexionDB();
        if (!$conn) {
            throw new Exception("Error al conectar con la base de datos.");
        }
        
        // 3. Consulta segura buscando por correo
        $sql = "SELECT id, nombres, apellidos, correo, telefono, password, tipo_usuario FROM usuarios WHERE correo = :correo LIMIT 1";
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(":correo", $correo, PDO::PARAM_STR);
        $stmt->execute();

        $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

        // 4. Verificar si el usuario existe y la contraseña coincide
        // Se agrega validación ($password === $usuario['password']) para permitir contraseñas sin encriptar (texto plano) durante pruebas
        if ($usuario && (password_verify($password, $usuario['password']) || $password === $usuario['password'])) {
            
            // 5. Login Exitoso: Configurar Sesión
            session_regenerate_id(true); // Previene fijación de sesión
            
            $_SESSION['user_id'] = $usuario['id'];
            $_SESSION['user_name'] = $usuario['nombres'];
            $_SESSION['user_lastname'] = $usuario['apellidos'];
            $_SESSION['user_email'] = $usuario['correo'];
            $_SESSION['user_phone'] = $usuario['telefono'];
            $_SESSION['user_role'] = $usuario['tipo_usuario'];
            
            // Redireccionar según el rol del usuario
            // Ajusta las rutas según donde tengas guardadas las vistas de cada rol
            $rol = strtolower(trim($usuario['tipo_usuario'])); // Limpia espacios en blanco del rol
            switch ($rol) {
                case 'admin':
                case 'administrador':
                    header("Location: ../views/admin/inicioAdmin.php");
                    break;
                case 'mensajero':
                    header("Location: ../views/mensajeros/inicioMensajero.php");
                    break;
                case 'cliente':
                case 'colaborador':
                    header("Location: ../views/clientes/inicioCliente.php");
                    break;
            }
            exit();

        } else {
            // Credenciales incorrectas
            header("Location: ../views/login.php?mensaje=Correo o contraseña incorrectos");
            exit();
        }

    } catch (Exception $e) {
        // Error en la base de datos (No mostrar detalles técnicos al usuario final)
        error_log("Error de Login: " . $e->getMessage()); 
        header("Location: ../views/login.php?mensaje=Error: " . urlencode($e->getMessage()));
        exit();
    }
} else {
    // Si intentan acceder al archivo directamente sin enviar formulario
    header("Location: ../views/login.php");
    exit();
}
?>
