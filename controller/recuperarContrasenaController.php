<?php
$sessionLifetime = 2592000;
ini_set('session.gc_maxlifetime', (string) $sessionLifetime);
session_set_cookie_params($sessionLifetime, "/");

session_start();

require_once __DIR__ . '/../includes/paths.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['email'])) {
        redirect_route('forgot-password', [
            'error' => 'La recuperacion por correo no esta configurada. Contacta al equipo de soporte.',
        ]);
    } elseif (isset($_POST['token']) && isset($_POST['password'])) {
        require_once __DIR__ . '/../models/conexionGlobal.php';
        $conn = conexionDB();
        $token = (string) $_POST['token'];
        $password = (string) $_POST['password'];
        $confirm_password = (string) ($_POST['confirm_password'] ?? '');

        if ($password !== $confirm_password) {
            redirect_route('reset-password', ['token' => $token, 'error' => 'Las contrasenas no coinciden']);
        }

        try {
            if (strlen($password) < 8) {
                redirect_route('reset-password', ['token' => $token, 'error' => 'La contrasena debe tener al menos 8 caracteres']);
            }

            $sql = "SELECT id FROM usuarios WHERE token = :token AND token_expiracion >= NOW() LIMIT 1";
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

            redirect_route('forgot-password', [
                'error' => 'Este enlace ya fue usado o no corresponde al ultimo enlace enviado. Solicita uno nuevo para ingresar sin inconvenientes.',
            ]);
        } catch (Exception $e) {
            redirect_route('reset-password', ['token' => $token, 'error' => 'Error al actualizar la contrasena']);
        }
    }
} else {
    redirect_route('login');
}
?>
