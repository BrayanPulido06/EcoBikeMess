<?php
require_once __DIR__ . '/../includes/paths.php';

ob_start();

$sessionLifetime = 2592000;
ini_set('session.gc_maxlifetime', (string) $sessionLifetime);
session_set_cookie_params($sessionLifetime, "/");

session_start();

require_once '../models/conexionGlobal.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $correo = filter_var(trim($_POST['correo']), FILTER_SANITIZE_EMAIL);
    $password = $_POST['password'];

    if (empty($correo) || empty($password)) {
        redirect_route('login', ['error' => 'Por favor complete todos los campos']);
    }

    try {
        $conn = conexionDB();
        if (!$conn) {
            throw new Exception("Error al conectar con la base de datos.");
        }

        $sql = "SELECT id, nombres, apellidos, correo, telefono, password, tipo_usuario FROM usuarios WHERE correo = :correo LIMIT 1";
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(":correo", $correo, PDO::PARAM_STR);
        $stmt->execute();

        $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($usuario && (password_verify($password, $usuario['password']) || $password === $usuario['password'])) {
            session_regenerate_id(true);

            $_SESSION['user_id'] = $usuario['id'];
            $_SESSION['user_name'] = $usuario['nombres'];
            $_SESSION['user_lastname'] = $usuario['apellidos'];
            $_SESSION['user_email'] = $usuario['correo'];
            $_SESSION['user_phone'] = $usuario['telefono'];
            $_SESSION['user_role'] = $usuario['tipo_usuario'];

            if (isset($_POST['remember_me'])) {
                setcookie('remember_email', $correo, time() + $sessionLifetime, "/");
            } else {
                if (isset($_COOKIE['remember_email'])) {
                    setcookie('remember_email', '', time() - 3600, "/");
                }
                if (isset($_COOKIE['remember_password'])) {
                    setcookie('remember_password', '', time() - 3600, "/");
                }
            }

            $rol = strtolower(trim($usuario['tipo_usuario']));
            switch ($rol) {
                case 'admin':
                case 'administrador':
                    redirect_route('admin.dashboard');
                    break;
                case 'mensajero':
                    redirect_route('messenger.dashboard');
                    break;
                case 'cliente':
                case 'colaborador':
                    redirect_route('client.dashboard');
                    break;
                default:
                    redirect_route('login', ['error' => 'El rol del usuario no es valido.']);
                    break;
            }
        } else {
            redirect_route('login', ['error' => 'Correo o contrasena incorrectos']);
        }
    } catch (Exception $e) {
        error_log("Error de conexion o SQL en Login: " . $e->getMessage());
        redirect_route('login', ['error' => 'Error de conexion con el servidor. Intente mas tarde.']);
    }
} else {
    redirect_route('login');
}
?>
