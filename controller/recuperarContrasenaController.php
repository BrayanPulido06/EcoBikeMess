<?php
$sessionLifetime = 2592000;
ini_set('session.gc_maxlifetime', (string) $sessionLifetime);
session_set_cookie_params($sessionLifetime, "/");

session_start();

require_once __DIR__ . '/../includes/paths.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['email'])) {
        $email = trim((string) $_POST['email']);
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            redirect_route('forgot-password', ['error' => 'Ingresa un correo electrónico válido.']);
        }

        $mailHelperPath = __DIR__ . '/../includes/mail.php';
        if (!is_file($mailHelperPath)) {
            error_log('Falta includes/mail.php para procesar la recuperación de contraseña.');
            redirect_route('forgot-password', [
                'error' => 'El servicio de recuperación no está completo. Contacta a soporte.',
            ]);
        }
        require_once $mailHelperPath;

        try {
            $mailConfig = obtenerConfiguracionCorreo();
        } catch (Throwable $e) {
            error_log('Configuración SMTP de recuperación incompleta: ' . $e->getMessage());
            redirect_route('forgot-password', [
                'error' => 'La recuperación por correo no está disponible en este momento. Intenta más tarde o contacta a soporte.',
            ]);
        }

        try {
            require_once __DIR__ . '/../models/conexionGlobal.php';
            $conn = conexionDB();
            $sql = "SELECT id, nombres FROM usuarios WHERE correo = :correo";
            $stmt = $conn->prepare($sql);
            $stmt->execute([':correo' => $email]);
            $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($usuario) {
                $token = bin2hex(random_bytes(32));
                $tokenHash = hash('sha256', $token);
                $expiracion = (new DateTimeImmutable('+30 minutes'))->format('Y-m-d H:i:s');

                $conn->beginTransaction();
                $updateSql = "UPDATE usuarios SET token = :token, token_expiracion = :expiracion WHERE id = :id";
                $updateStmt = $conn->prepare($updateSql);
                $updateStmt->execute([
                    ':token' => $tokenHash,
                    ':expiracion' => $expiracion,
                    ':id' => $usuario['id']
                ]);

                $baseUrl = $mailConfig['app_url'] . app_url(route_path('reset-password'));
                $link = $baseUrl . '?token=' . rawurlencode($token);
                enviarCorreoRecuperacion($email, (string) ($usuario['nombres'] ?? ''), $link);
                $conn->commit();
            }

            redirect_route('forgot-password', [
                'mensaje' => 'Si el correo está registrado, recibirás un enlace de recuperación. Revisa también la carpeta de correo no deseado.',
            ]);
        } catch (Throwable $e) {
            if (isset($conn) && $conn->inTransaction()) {
                $conn->rollBack();
            }
            error_log('Error en solicitud de recuperación de contraseña: ' . $e->getMessage());
            redirect_route('forgot-password', [
                'mensaje' => 'Si el correo está registrado, recibirás un enlace de recuperación. Revisa también la carpeta de correo no deseado.',
            ]);
        }
    } elseif (isset($_POST['token']) && isset($_POST['password'])) {
        require_once __DIR__ . '/../models/conexionGlobal.php';
        $conn = conexionDB();
        $token = (string) $_POST['token'];
        $tokenHash = hash('sha256', $token);
        $password = (string) $_POST['password'];
        $confirm_password = (string) ($_POST['confirm_password'] ?? '');

        if ($password !== $confirm_password) {
            redirect_route('reset-password', ['token' => $token, 'error' => 'Las contrasenas no coinciden']);
        }

        try {
            if (strlen($password) < 8) {
                redirect_route('reset-password', ['token' => $token, 'error' => 'La contrasena debe tener al menos 8 caracteres']);
            }

            $sql = "SELECT id FROM usuarios WHERE (token = :token_hash OR token = :token_legacy) AND token_expiracion >= NOW() LIMIT 1";
            $stmt = $conn->prepare($sql);
            $stmt->execute([':token_hash' => $tokenHash, ':token_legacy' => $token]);
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
