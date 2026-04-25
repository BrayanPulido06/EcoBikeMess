<?php
$sessionLifetime = 2592000;
ini_set('session.gc_maxlifetime', (string) $sessionLifetime);
session_set_cookie_params($sessionLifetime, "/");

session_start();

require_once '../models/conexionGlobal.php';
require_once __DIR__ . '/../includes/paths.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $conn = conexionDB();

    if (isset($_POST['email'])) {
        $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);

        try {
            $sql = "SELECT id, nombres FROM usuarios WHERE correo = :correo";
            $stmt = $conn->prepare($sql);
            $stmt->execute([':correo' => $email]);
            $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($usuario) {
                $token = bin2hex(random_bytes(32));
                $expiracion = date('Y-m-d H:i:s', strtotime('+1 hour'));

                $updateSql = "UPDATE usuarios SET token = :token, token_expiracion = :expiracion WHERE id = :id";
                $updateStmt = $conn->prepare($updateSql);
                $updateStmt->execute([
                    ':token' => $token,
                    ':expiracion' => $expiracion,
                    ':id' => $usuario['id']
                ]);

                $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
                $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
                $link = $scheme . '://' . $host . route_url('reset-password', ['token' => $token]);

                redirect_route('forgot-password', [
                    'mensaje' => 'Enlace generado (Ver URL)',
                    'debug_link' => $link,
                ]);
            }

            redirect_route('forgot-password', ['mensaje' => 'Si el correo esta registrado, recibiras instrucciones.']);
        } catch (Exception $e) {
            error_log("Error recuperacion: " . $e->getMessage());
            redirect_route('forgot-password', ['mensaje' => 'Ocurrio un error inesperado.']);
        }
    } elseif (isset($_POST['token']) && isset($_POST['password'])) {
        $token = (string) $_POST['token'];
        $password = (string) $_POST['password'];
        $confirm_password = (string) ($_POST['confirm_password'] ?? '');

        if ($password !== $confirm_password) {
            redirect_route('reset-password', ['token' => $token, 'error' => 'Las contrasenas no coinciden']);
        }

        try {
            $sql = "SELECT id FROM usuarios WHERE token = :token AND token_expiracion > NOW()";
            $stmt = $conn->prepare($sql);
            $stmt->execute([':token' => $token]);
            $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($usuario) {
                $newPasswordHash = password_hash($password, PASSWORD_DEFAULT);

                $updateSql = "UPDATE usuarios SET password = :password, token = NULL, token_expiracion = NULL WHERE id = :id";
                $updateStmt = $conn->prepare($updateSql);
                $updateStmt->execute([
                    ':password' => $newPasswordHash,
                    ':id' => $usuario['id']
                ]);

                redirect_route('login', ['mensaje' => 'Contrasena actualizada correctamente. Inicia sesion.']);
            }

            redirect_route('login', ['error' => 'El enlace es invalido o ha expirado.']);
        } catch (Exception $e) {
            redirect_route('reset-password', ['token' => $token, 'error' => 'Error al actualizar la contrasena']);
        }
    }
} else {
    redirect_route('login');
}
?>
