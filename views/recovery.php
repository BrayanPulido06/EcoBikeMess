<?php
ob_start();
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
    // Traemos nombres para personalizar el correo
    $stmt = $db->prepare("SELECT id, nombres, apellidos FROM usuarios WHERE correo = :correo LIMIT 1");
    $stmt->execute([':correo' => $correo]);
    $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($usuario) {
        // 5. Generar token único y fecha de expiración (1 hora)
        $token = bin2hex(random_bytes(32));
        $expiracion = date('Y-m-d H:i:s', strtotime('+1 hour'));

        // Guardar token en la BD
        $updateSql = "UPDATE usuarios SET token = :token, token_expiracion = :expiracion WHERE id = :id";
        $updateStmt = $db->prepare($updateSql);
        $resultadoUpdate = $updateStmt->execute([
            ':token' => $token,
            ':expiracion' => $expiracion,
            ':id' => $usuario['id']
        ]);

        if (!$resultadoUpdate) {
            throw new \Exception("Error al guardar el token en la base de datos.");
        }

        // 6. Construir la URL hacia cambioContraseña.php
        $protocolo = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https" : "http";
        $host = $_SERVER['HTTP_HOST'];
        // dirname($_SERVER['SCRIPT_NAME']) nos da la ruta de /views
        $pathViews = dirname($_SERVER['SCRIPT_NAME']);
        $urlRecuperacion = "{$protocolo}://{$host}" . rtrim($pathViews, '/') . "/cambioContraseña.php?token=" . $token;
        
        // 7. Configurar PHPMailer
        $mail = new PHPMailer(true);

        // Descomenta la siguiente línea si quieres ver el log detallado de conexión en pantalla
        // $mail->SMTPDebug = SMTP::DEBUG_SERVER;

        $mail->isSMTP();
        $mail->CharSet = 'UTF-8';
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;

        $mail->Username = 'Eco.bikemess@gmail.com';
        $mail->Password = 'qqzi vzlz kytz pecp'; // Contraseña de aplicación de 16 letras

        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;

        // Opciones necesarias para XAMPP / Localhost
        $mail->SMTPOptions = [
            'ssl' => [
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true
            ]
        ];

        $mail->setFrom('Eco.bikemess@gmail.com', 'EcoBikeMess Soporte');
        $mail->addAddress($correo);

        $mail->isHTML(true);
        $mail->Subject = 'Recuperación de Contraseña - EcoBikeMess';
        
        // Diseño de correo más amigable y profesional
        $mail->Body = '
        <div style="background-color: #f4f7f6; padding: 40px 0; font-family: sans-serif; color: #333;">
            <div style="max-width: 600px; margin: 0 auto; background-color: #ffffff; border-radius: 15px; overflow: hidden; box-shadow: 0 4px 10px rgba(0,0,0,0.1);">
                
                <!-- Header -->
                <div style="background: linear-gradient(135deg, #5cb85c 0%, #4cae4c 100%); padding: 30px; text-align: center;">
                    <h1 style="color: white; margin: 0; font-size: 28px;">
                        EcoBikeMess
                    </h1>
                    <p style="color: #e8f5e9; margin: 5px 0 0; font-size: 14px;">Mensajería Ecológica y Sostenible</p>
                </div>

                <!-- Body -->
                <div style="padding: 40px 30px;">
                    <h2 style="color: #2d3e50; margin-top: 0;">Hola, ' . htmlspecialchars($usuario['nombres']) . '</h2>
                    <p style="font-size: 16px; line-height: 1.6; color: #555;">
                        Recibimos una solicitud para restablecer la contraseña de tu cuenta. No te preocupes, esto sucede a veces. Haz clic en el botón de abajo para crear una nueva clave:
                    </p>
                    
                    <div style="text-align: center; margin: 35px 0;">
                        <a href="' . $urlRecuperacion . '" style="background-color: #5cb85c; color: white; padding: 15px 35px; text-decoration: none; border-radius: 50px; font-weight: bold; font-size: 16px; display: inline-block; transition: background 0.3s;">
                            Restablecer mi contraseña
                        </a>
                    </div>
                    
                    <hr style="border: 0; border-top: 1px solid #eee; margin: 30px 0;">
                    <p style="font-size: 13px; color: #999; text-align: center;">
                        Este enlace tiene una validez de <strong>1 hora</strong>.<br>
                        Si tú no solicitaste este cambio, puedes ignorar este correo con total seguridad.
                    </p>
                </div>

                <!-- Footer -->
                <div style="background-color: #fcfdfc; padding: 20px; text-align: center; font-size: 12px; color: #aaa;">
                    &copy; ' . date('Y') . ' EcoBikeMess Bogotá. Todos los derechos reservados.
                </div>
            </div>
        </div>';

        $mail->AltBody = "Hola " . $usuario['nombres'] . ". Visita este enlace para cambiar tu clave: " . $urlRecuperacion;

        $mail->send();

        header("Location: login.php?mensaje=" . urlencode("¡Enlace de recuperación enviado! Revisa tu correo electrónico."));
        exit;
    } else {
        header("Location: recuperarContraseña.php?error=" . urlencode("El correo ingresado no está registrado en el sistema."));
        exit;
    }
} catch (\Throwable $e) {
    // Capturamos cualquier error (PHPMailer, DB, o errores de sintaxis)
    $infoError = (isset($mail) && $mail->ErrorInfo) ? $mail->ErrorInfo : $e->getMessage();
    header("Location: recuperarContraseña.php?error=" . urlencode("Error: " . $infoError));
    exit;
}
?>
