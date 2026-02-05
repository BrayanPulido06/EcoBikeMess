<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;

// 1. Cargar dependencias y configuración
// Ajuste de rutas: Estamos en views/, así que models/ está en ../models/
require_once __DIR__ . '/../models/conexionGlobal.php';

// Asumiendo que la carpeta PHPMailer está en la raíz del proyecto (ecobikemess/PHPMailer)
require_once __DIR__ . '/../PHPMailer/Exception.php';
require_once __DIR__ . '/../PHPMailer/PHPMailer.php';
require_once __DIR__ . '/../PHPMailer/SMTP.php';

// 2. Validar la petición
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['email'])) {
    header("Location: recuperarContraseña.php?mensaje=Petición no válida.");
    exit;
}

// 3. Sanitizar y obtener el correo
$correo = filter_var(trim($_POST['email']), FILTER_SANITIZE_EMAIL);
if (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
    header("Location: recuperarContraseña.php?mensaje=Correo no válido.");
    exit;
}

// 4. Conectar a la BD
$db = conexionDB();

try {
    // Verificar si el usuario existe en la tabla 'usuarios'
    $stmt = $db->prepare("SELECT id, nombres, apellidos FROM usuarios WHERE correo = :correo");
    $stmt->execute([':correo' => $correo]);
    $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($usuario) {
        // 5. Generar token único y fecha de expiración (1 hora)
        $token = bin2hex(random_bytes(32));
        $expiracion = date('Y-m-d H:i:s', strtotime('+1 hour'));

        // Guardar token en la BD
        $updateSql = "UPDATE usuarios SET token = :token, token_expiracion = :expiracion WHERE id = :id";
        $updateStmt = $db->prepare($updateSql);
        $updateStmt->execute([
            ':token' => $token,
            ':expiracion' => $expiracion,
            ':id' => $usuario['id']
        ]);

        // 6. Construir la URL hacia cambioContraseña.php
        $protocolo = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https" : "http";
        $host = $_SERVER['HTTP_HOST'];
        // dirname($_SERVER['SCRIPT_NAME']) nos da la ruta de /views
        $pathViews = dirname($_SERVER['SCRIPT_NAME']);
        $urlRecuperacion = "{$protocolo}://{$host}{$pathViews}/cambioContraseña.php?token=" . $token;
        
        // 7. Configurar PHPMailer
        $mail = new PHPMailer(true);

        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        
        // --- CONFIGURACIÓN OBLIGATORIA: PON TUS DATOS AQUÍ ---
        $mail->Username   = 'TU_CORREO@gmail.com'; // <--- Pon tu correo Gmail real aquí
        $mail->Password   = 'xxxx xxxx xxxx xxxx'; // <--- Pon tu Contraseña de Aplicación de 16 letras aquí
        // -----------------------------------------------------
        
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;

        $mail->SMTPOptions = [
            'ssl' => [
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true,
            ]
        ];

        $mail->setFrom($mail->Username, 'EcoBikeMess Soporte'); // Usa el mismo correo para evitar bloqueos
        $mail->addAddress($correo);

        $mail->isHTML(true);
        $mail->Subject = 'Recuperacion de Contrasena - EcoBikeMess';
        
        // HTML Template adaptado a EcoBikeMess (Verde)
        $htmlBody = ' 
        <!DOCTYPE html>
        <html lang="es">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Recuperación de Contraseña - EcoBikeMess</title>
            <style>
                body { font-family: "Segoe UI", sans-serif; background: #e8f8f5; padding: 20px; }
                .email-container { background: #ffffff; max-width: 600px; margin: 0 auto; border-radius: 20px; overflow: hidden; box-shadow: 0 10px 20px rgba(0,0,0,0.1); }
                .header { background: linear-gradient(135deg, #5cb85c 0%, #4cae4c 100%); padding: 40px 30px; text-align: center; color: white; }
                .brand-name { font-size: 32px; font-weight: 800; margin-bottom: 10px; }
                .content { padding: 40px 30px; text-align: center; color: #2d3e50; }
                .cta-button { display: inline-block; background: #5cb85c; color: white !important; padding: 15px 30px; text-decoration: none; border-radius: 50px; font-weight: bold; margin-top: 20px; }
                .footer { background: #f8fdf9; padding: 20px; text-align: center; color: #6c757d; font-size: 12px; }
            </style>
        </head>
        <body>
            <div class="email-container">
                <div class="header">
                    <div class="brand-name">🚴 EcoBikeMess</div>
                    <div>Mensajería Ecológica</div>
                </div>
                <div class="content">
                    <h2>Recuperación de Contraseña</h2>
                    <p>Hola ' . htmlspecialchars($usuario['nombres']) . ',</p>
                    <p>Hemos recibido una solicitud para restablecer tu contraseña. Haz clic en el siguiente botón para crear una nueva:</p>
                    <a href="' . $urlRecuperacion . '" class="cta-button">Restablecer Contraseña</a>
                    <p style="margin-top: 30px; font-size: 14px; color: #7f8c8d;">Este enlace expirará en 1 hora.</p>
                </div>
                <div class="footer">
                    <p>Si no solicitaste este cambio, puedes ignorar este correo.</p>
                </div>
            </div>
        </body>
        </html>';

        $mail->Body = $htmlBody;
        $mail->AltBody = 'Hola, este es un mensaje de recuperación de contraseña. Por favor visita: ' . $urlRecuperacion;

        $mail->send();
        header("location: recuperarContraseña.php?mensaje=Correo enviado correctamente. Revisa tu bandeja de entrada.");
        exit;
    } else {
        // CAMBIO: Mensaje explícito para que verifiques que la validación funciona
        header("location: recuperarContraseña.php?mensaje=Error: El correo ingresado NO está registrado en el sistema.");
        exit;
    }

} catch (Exception $e) {
    // CAMBIO: Mostrar el error exacto de PHPMailer para saber por qué no llega el correo
    $errorDetallado = $mail->ErrorInfo;
    header("location: recuperarContraseña.php?mensaje=Fallo el envio: " . $errorDetallado);
    exit;
}
?>