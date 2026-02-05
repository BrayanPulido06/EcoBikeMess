<?php
session_start();
require_once '../models/conexionGlobal.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $conn = conexionDB();
    
    // --- ACCIÓN 1: SOLICITAR ENLACE (Viene de recuperarContraseña.php) ---
    if (isset($_POST['email'])) {
        $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
        
        try {
            // 1. Verificar si el correo existe
            $sql = "SELECT id, nombres FROM usuarios WHERE correo = :correo";
            $stmt = $conn->prepare($sql);
            $stmt->execute([':correo' => $email]);
            $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($usuario) {
                // 2. Generar token único y fecha de expiración (1 hora)
                $token = bin2hex(random_bytes(32));
                $expiracion = date('Y-m-d H:i:s', strtotime('+1 hour'));

                // 3. Guardar token en la BD
                $updateSql = "UPDATE usuarios SET token = :token, token_expiracion = :expiracion WHERE id = :id";
                $updateStmt = $conn->prepare($updateSql);
                $updateStmt->execute([
                    ':token' => $token,
                    ':expiracion' => $expiracion,
                    ':id' => $usuario['id']
                ]);

                // 4. Generar enlace (En producción, aquí enviarías el correo con mail() o PHPMailer)
                $link = "http://localhost/ecobikemess/views/recovery.php?token=" . $token;
                
                // NOTA: Como estamos en local, redirigimos con el link visible en la URL para que puedas probarlo
                // En producción, cambia esto por un mensaje genérico y envía el correo real.
                header("Location: ../views/recuperarContraseña.php?mensaje=Enlace generado (Ver URL)&debug_link=" . urlencode($link));
            } else {
                // Por seguridad, no decimos si el correo existe o no, o decimos que "si existe, se envió"
                header("Location: ../views/recuperarContraseña.php?mensaje=Si el correo está registrado, recibirás instrucciones.");
            }
        } catch (Exception $e) {
            error_log("Error recuperación: " . $e->getMessage());
            header("Location: ../views/recuperarContraseña.php?mensaje=Ocurrió un error inesperado.");
        }
    }
    
    // --- ACCIÓN 2: CAMBIAR CONTRASEÑA (Viene de recovery.php) ---
    elseif (isset($_POST['token']) && isset($_POST['password'])) {
        $token = $_POST['token'];
        $password = $_POST['password'];
        $confirm_password = $_POST['confirm_password'];

        if ($password !== $confirm_password) {
            header("Location: ../views/recovery.php?token=$token&error=Las contraseñas no coinciden");
            exit();
        }

        try {
            // 1. Verificar token válido y no expirado
            $sql = "SELECT id FROM usuarios WHERE token = :token AND token_expiracion > NOW()";
            $stmt = $conn->prepare($sql);
            $stmt->execute([':token' => $token]);
            $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($usuario) {
                // 2. Actualizar contraseña y borrar token
                $newPasswordHash = password_hash($password, PASSWORD_DEFAULT);
                
                $updateSql = "UPDATE usuarios SET password = :password, token = NULL, token_expiracion = NULL WHERE id = :id";
                $updateStmt = $conn->prepare($updateSql);
                $updateStmt->execute([
                    ':password' => $newPasswordHash,
                    ':id' => $usuario['id']
                ]);

                header("Location: ../views/login.php?mensaje=Contraseña actualizada correctamente. Inicia sesión.");
            } else {
                header("Location: ../views/login.php?mensaje=El enlace es inválido o ha expirado.");
            }
        } catch (Exception $e) {
            header("Location: ../views/recovery.php?token=$token&error=Error al actualizar la contraseña");
        }
    }
} else {
    header("Location: ../views/login.php");
}
?>
