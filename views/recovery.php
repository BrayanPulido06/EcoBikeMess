<?php
ob_start();

use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\PHPMailer;

require_once __DIR__ . '/../models/conexionGlobal.php';
require_once __DIR__ . '/../includes/paths.php';
require_once __DIR__ . '/../PHPMailer/Exception.php';
require_once __DIR__ . '/../PHPMailer/PHPMailer.php';
require_once __DIR__ . '/../PHPMailer/SMTP.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['email'])) {
    redirect_route('forgot-password', ['mensaje' => 'Peticion no valida.']);
}

$correo = filter_var(trim($_POST['email']), FILTER_SANITIZE_EMAIL);
if (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
    redirect_route('forgot-password', ['mensaje' => 'Correo no valido.']);
}

$db = conexionDB();

try {
    $stmt = $db->prepare("SELECT id, nombres, apellidos FROM usuarios WHERE correo = :correo LIMIT 1");
    $stmt->execute([':correo' => $correo]);
    $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$usuario) {
        redirect_route('forgot-password', ['error' => 'El correo ingresado no esta registrado en el sistema.']);
    }

    $token = bin2hex(random_bytes(32));
    $expiracion = date('Y-m-d H:i:s', strtotime('+1 hour'));

    $updateSql = "UPDATE usuarios SET token = :token, token_expiracion = :expiracion WHERE id = :id";
    $updateStmt = $db->prepare($updateSql);
    $resultadoUpdate = $updateStmt->execute([
        ':token' => $token,
        ':expiracion' => $expiracion,
        ':id' => $usuario['id']
    ]);

    if (!$resultadoUpdate) {
        throw new Exception("Error al guardar el token en la base de datos.");
    }

    $protocolo = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $urlRecuperacion = "{$protocolo}://{$host}" . route_url('reset-password', ['token' => $token]);

    $mail = new PHPMailer(true);
    $mail->isSMTP();
    $mail->CharSet = 'UTF-8';
    $mail->Host = 'smtp.gmail.com';
    $mail->SMTPAuth = true;
    $mail->Username = 'Eco.bikemess@gmail.com';
    $mail->Password = 'qqzi vzlz kytz pecp';
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port = 587;
    $mail->SMTPOptions = [
        'ssl' => [
            'verify_peer' => false,
            'verify_peer_name' => false,
            'allow_self_signed' => true,
        ],
    ];

    $mail->setFrom('Eco.bikemess@gmail.com', 'EcoBikeMess Soporte');
    $mail->addAddress($correo);
    $mail->isHTML(true);
    $mail->Subject = 'Recuperacion de Contrasena - EcoBikeMess';
    $mail->Body = '
    <div style="background-color: #f4f7f6; padding: 40px 0; font-family: sans-serif; color: #333;">
        <div style="max-width: 600px; margin: 0 auto; background-color: #ffffff; border-radius: 15px; overflow: hidden; box-shadow: 0 4px 10px rgba(0,0,0,0.1);">
            <div style="background: linear-gradient(135deg, #5cb85c 0%, #4cae4c 100%); padding: 30px; text-align: center;">
                <h1 style="color: white; margin: 0; font-size: 28px;">EcoBikeMess</h1>
                <p style="color: #e8f5e9; margin: 5px 0 0; font-size: 14px;">Mensajeria Ecologica y Sostenible</p>
            </div>
            <div style="padding: 40px 30px;">
                <h2 style="color: #2d3e50; margin-top: 0;">Hola, ' . htmlspecialchars($usuario['nombres']) . '</h2>
                <p style="font-size: 16px; line-height: 1.6; color: #555;">
                    Recibimos una solicitud para restablecer la contrasena de tu cuenta. Haz clic en el boton de abajo para crear una nueva clave:
                </p>
                <div style="text-align: center; margin: 35px 0;">
                    <a href="' . $urlRecuperacion . '" style="background-color: #5cb85c; color: white; padding: 15px 35px; text-decoration: none; border-radius: 50px; font-weight: bold; font-size: 16px; display: inline-block;">
                        Restablecer mi contrasena
                    </a>
                </div>
                <hr style="border: 0; border-top: 1px solid #eee; margin: 30px 0;">
                <p style="font-size: 13px; color: #999; text-align: center;">
                    Este enlace tiene una validez de <strong>1 hora</strong>.<br>
                    Si tu no solicitaste este cambio, puedes ignorar este correo.
                </p>
            </div>
            <div style="background-color: #fcfdfc; padding: 20px; text-align: center; font-size: 12px; color: #aaa;">
                &copy; ' . date('Y') . ' EcoBikeMess Bogota. Todos los derechos reservados.
            </div>
        </div>
    </div>';
    $mail->AltBody = "Hola " . $usuario['nombres'] . ". Visita este enlace para cambiar tu clave: " . $urlRecuperacion;
    $mail->send();

    redirect_route('login', ['mensaje' => 'Enlace de recuperacion enviado. Revisa tu correo electronico.']);
} catch (\Throwable $e) {
    $infoError = (isset($mail) && $mail->ErrorInfo) ? $mail->ErrorInfo : $e->getMessage();
    redirect_route('forgot-password', ['error' => 'Error: ' . $infoError]);
}
?>
