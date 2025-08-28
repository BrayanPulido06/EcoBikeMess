<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;

require '../../PHPMailer/Exception.php';
require '../../PHPMailer/PHPMailer.php';
require '../../PHPMailer/SMTP.php';

require_once ('../../config/conexionGlobal.php');
$db = conexionDB();

$correo = $_POST['correo'];
session_start();
$_SESSION['correo'] = $correo;

$conexion = mysqli_connect("localhost","root","","ecobikemess");

// Escape del correo para prevenir inyección SQL
$correo_escaped = mysqli_real_escape_string($conexion, $correo);

$usuario_encontrado = null;
$tipo_usuario = null;
$documento_id = null;

// PASO 1: Buscar en tabla de administradores
$consulta_admin = "SELECT id, tipo_documento, numDocumento, nombres, apellidos, correo FROM administradores WHERE correo = '$correo_escaped' AND sesionCaducada = '1'";
$resultado_admin = mysqli_query($conexion, $consulta_admin);

if (mysqli_num_rows($resultado_admin) > 0) {
    $usuario_encontrado = mysqli_fetch_assoc($resultado_admin);
    $tipo_usuario = 'administrador';
    $documento_id = $usuario_encontrado['numDocumento'];
    mysqli_free_result($resultado_admin);
} else {
    mysqli_free_result($resultado_admin);
    
    // PASO 2: Buscar en tabla de clientes
    $consulta_cliente = "SELECT id, tipo_documento, numDocumento, nombres, apellidos, correo FROM clientes WHERE correo = '$correo_escaped' AND sesionCaducada = '1'";
    $resultado_cliente = mysqli_query($conexion, $consulta_cliente);
    
    if (mysqli_num_rows($resultado_cliente) > 0) {
        $usuario_encontrado = mysqli_fetch_assoc($resultado_cliente);
        $tipo_usuario = 'cliente';
        $documento_id = $usuario_encontrado['numDocumento'];
        mysqli_free_result($resultado_cliente);
    } else {
        mysqli_free_result($resultado_cliente);
        
        // PASO 3: Buscar en tabla de mensajeros
        $consulta_mensajero = "SELECT id, tipo_documento, numDocumento, nombres, apellidos, correo FROM mensajeros WHERE correo = '$correo_escaped' AND sesionCaducada = '1'";
        $resultado_mensajero = mysqli_query($conexion, $consulta_mensajero);
        
        if (mysqli_num_rows($resultado_mensajero) > 0) {
            $usuario_encontrado = mysqli_fetch_assoc($resultado_mensajero);
            $tipo_usuario = 'mensajero';
            $documento_id = $usuario_encontrado['numDocumento'];
            mysqli_free_result($resultado_mensajero);
        } else {
            mysqli_free_result($resultado_mensajero);
        }
    }
}

if($usuario_encontrado !== null){
    
    // Generar token único para mayor seguridad
    $token = bin2hex(random_bytes(32));
    $token_escaped = mysqli_real_escape_string($conexion, $token);
    $id_escaped = mysqli_real_escape_string($conexion, $usuario_encontrado['id']);
    
    // Actualizar el token en la base de datos correspondiente
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
    
    $actualizar_token = "UPDATE $tabla SET tokenPassword = '$token_escaped' WHERE id = '$id_escaped'";
    $resultado_token = mysqli_query($conexion, $actualizar_token);
    
    if (!$resultado_token) {
        header("location: login.php?mensaje=Error al generar token de recuperación");
        exit;
    }
    
    $mail = new PHPMailer(true);

    try {
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'eco.bikemess@gmail.com';
        $mail->Password   = 'irto oqxm eayj kuyf';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;

        // Permitir certificados autofirmados (solo para pruebas locales)
        $mail->SMTPOptions = [
            'ssl' => [
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true,
            ]
        ];

        $mail->setFrom('eco.bikemess@gmail.com', 'EcoBikeMess');
        $mail->addAddress($correo);

        $mail->isHTML(true);
        $mail->Subject = 'Recuperacion de Contrasena - EcoBikeMess';

        // Obtener el nombre del usuario según el tipo
        $nombre_usuario = '';
        switch($tipo_usuario) {
            case 'administrador':
                $nombre_usuario = $usuario_encontrado['nombres'] . ' ' . $usuario_encontrado['apellidos'];
                break;
            case 'cliente':
                $nombre_usuario = $usuario_encontrado['nombres'] . ' ' . $usuario_encontrado['apellidos'];
                break;
            case 'mensajero':
                $nombre_usuario = $usuario_encontrado['nombres'] . ' ' . $usuario_encontrado['apellidos'];
                break;
        }
        
        // HTML Template corregido para EcoBikeMess
        $htmlBody = '
        <!DOCTYPE html>
        <html lang="es">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Recuperación de Contraseña - EcoBikeMess</title>
            <style>
                * {
                    margin: 0;
                    padding: 0;
                    box-sizing: border-box;
                }
                
                body {
                    font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
                    background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%);
                    min-height: 100vh;
                    margin: 0;
                    padding: 20px;
                    width: 100%;
                }
                
                .email-container {
                    background: #ffffff;
                    max-width: 600px;
                    width: 100%;
                    margin: 0 auto;
                    border-radius: 20px;
                    box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
                    overflow: hidden;
                    animation: slideIn 0.8s ease-out;
                }
                
                @keyframes slideIn {
                    from {
                        opacity: 0;
                        transform: translateY(30px);
                    }
                    to {
                        opacity: 1;
                        transform: translateY(0);
                    }
                }
                
                .header {
                    background: linear-gradient(135deg, #16a34a 0%, #22c55e 100%);
                    padding: 40px 30px;
                    text-align: center;
                    position: relative;
                    overflow: hidden;
                }
                
                .header::before {
                    content: "";
                    position: absolute;
                    top: -50%;
                    left: -50%;
                    width: 200%;
                    height: 200%;
                    background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 70%);
                    animation: shimmer 3s ease-in-out infinite;
                }
                
                @keyframes shimmer {
                    0%, 100% { transform: translate(-50%, -50%) rotate(0deg); }
                    50% { transform: translate(-50%, -50%) rotate(180deg); }
                }
                
                .brand-name {
                    color: white;
                    font-size: 42px;
                    font-weight: 800;
                    margin-bottom: 10px;
                    position: relative;
                    z-index: 1;
                    letter-spacing: 2px;
                    text-shadow: 0 2px 4px rgba(0,0,0,0.2);
                }
                
                .header-subtitle {
                    color: rgba(255, 255, 255, 0.9);
                    font-size: 16px;
                    position: relative;
                    z-index: 1;
                }
                
                .content {
                    padding: 50px 40px;
                    text-align: center;
                }
                
                .title {
                    color: #2c3e50;
                    font-size: 32px;
                    font-weight: 700;
                    margin-bottom: 20px;
                    background: linear-gradient(135deg, #16a34a 0%, #22c55e 100%);
                    -webkit-background-clip: text;
                    -webkit-text-fill-color: transparent;
                    background-clip: text;
                }
                
                .greeting {
                    color: #374151;
                    font-size: 20px;
                    font-weight: 600;
                    margin-bottom: 15px;
                }
                
                .message {
                    color: #5a6c7d;
                    font-size: 18px;
                    line-height: 1.6;
                    margin-bottom: 40px;
                    max-width: 400px;
                    margin-left: auto;
                    margin-right: auto;
                }
                
                .cta-button {
                    color: #ffffff !important;
                    display: inline-block;
                    background: linear-gradient(135deg, #16a34a 0%, #22c55e 100%);
                    text-decoration: none;
                    padding: 18px 40px;
                    border-radius: 50px;
                    font-size: 18px;
                    font-weight: 600;
                    transition: all 0.3s ease;
                    box-shadow: 0 10px 30px rgba(22, 163, 74, 0.3);
                    position: relative;
                    overflow: hidden;
                    border: none;
                }
                
                .cta-button::before {
                    content: "";
                    position: absolute;
                    top: 0;
                    left: -100%;
                    width: 100%;
                    height: 100%;
                    background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
                    transition: left 0.5s ease;
                }
                
                .cta-button:hover::before {
                    left: 100%;
                }
                
                .cta-button:hover {
                    transform: translateY(-2px);
                    box-shadow: 0 15px 35px rgba(22, 163, 74, 0.4);
                }
                
                .security-info {
                    background: linear-gradient(135deg, #f0fdf4 0%, #dcfce7 100%);
                    padding: 30px;
                    border-radius: 15px;
                    margin-top: 40px;
                    border: 1px solid rgba(34, 197, 94, 0.1);
                }
                
                .security-title {
                    color: #166534;
                    font-size: 16px;
                    font-weight: 600;
                    margin-bottom: 15px;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    gap: 10px;
                }
                
                .security-text {
                    color: #15803d;
                    font-size: 14px;
                    line-height: 1.5;
                }
                
                .footer {
                    background: #f8fafc;
                    padding: 30px;
                    text-align: center;
                    border-top: 1px solid #e2e8f0;
                }
                
                .footer-text {
                    color: #a0aec0;
                    font-size: 14px;
                    line-height: 1.5;
                }
                
                .footer-link {
                    color: #22c55e;
                    text-decoration: none;
                }
                
                .footer-link:hover {
                    text-decoration: underline;
                }
                
                @media (max-width: 600px) {
                    .email-container {
                        margin: 10px;
                        border-radius: 15px;
                    }
                    
                    .content {
                        padding: 30px 20px;
                    }
                    
                    .title {
                        font-size: 28px;
                    }
                    
                    .message {
                        font-size: 16px;
                    }
                    
                    .cta-button {
                        padding: 16px 30px;
                        font-size: 16px;
                    }
                }
            </style>
        </head>
        <body>
            <div class="email-container">
                <!-- Header -->
                <div class="header">
                    <div class="logo">';
        
        // Icono de bicicleta para EcoBikeMess
        $htmlBody .= '<svg viewBox="0 0 24 24" fill="white" width="50" height="50">
                        <path d="M15.5 5.5c1.1 0 2-.9 2-2s-.9-2-2-2-2 .9-2 2 .9 2 2 2zM5 12c-2.8 0-5 2.2-5 5s2.2 5 5 5 5-2.2 5-5-2.2-5-5-5zm0 8.5c-1.9 0-3.5-1.6-3.5-3.5s1.6-3.5 3.5-3.5 3.5 1.6 3.5 3.5-1.6 3.5-3.5 3.5zm10 0c-1.9 0-3.5-1.6-3.5-3.5s1.6-3.5 3.5-3.5 3.5 1.6 3.5 3.5-1.6 3.5-3.5 3.5zm0-8.5c-2.8 0-5 2.2-5 5s2.2 5 5 5 5-2.2 5-5-2.2-5-5-5zm-7.5-4h2.4l1.5 1.5h2.2l-1.9-1.9c-.3-.3-.7-.4-1.1-.2L8.5 7H7c-.6 0-1 .4-1 1s.4 1 1 1h1.8l2.8 2.8c.1.1.3.2.5.2h3.5c.6 0 1-.4 1-1s-.4-1-1-1h-3l-1.9-1.9z"/>
                      </svg>';
        
        $htmlBody .= '</div>
                    <div class="brand-name">EcoBikeMess</div>
                    <div class="header-subtitle">Mensajería Ecológica Sostenible</div>
                </div>
                
                <!-- Content -->
                <div class="content">
                    <h1 class="title">Recuperación de Contraseña</h1>
                    
                    <p class="greeting">¡Hola '.htmlspecialchars($nombre_usuario).'!</p>
                    
                    <p class="message">
                        Recibimos una solicitud para restablecer tu contraseña. 
                        Haz clic en el botón de abajo para continuar con el proceso de recuperación.
                    </p>
                    
                    <a href="http://localhost/ecobikemess/views/login/Contraseña.php?token='.$token.'&type='.$tipo_usuario.'" class="cta-button">
                        Recuperar Contraseña
                    </a>
                    
                    <div class="security-info">
                        <div class="security-title">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
                                <path d="M12 2L2 7v10c0 5.55 3.84 9.739 9 11 5.16-1.261 9-5.45 9-11V7l-10-5z"/>
                            </svg>
                            Información de Seguridad
                        </div>
                        <div class="security-text">
                            Este enlace es válido por 24 horas. Si no solicitaste este cambio, 
                            puedes ignorar este correo de forma segura. Tu contraseña actual 
                            permanecerá sin cambios.
                        </div>
                    </div>
                </div>
                
                <!-- Footer -->
                <div class="footer">
                    <div class="footer-text">
                        Este es un mensaje automático de <strong>EcoBikeMess</strong><br>
                        Si tienes problemas, contacta a nuestro 
                        <a href="mailto:eco.bikemess@gmail.com" class="footer-link">equipo de soporte</a>
                    </div>
                </div>
            </div>
        </body>
        </html>';

        $mail->Body = $htmlBody;
        $mail->AltBody = 'Hola '.$nombre_usuario.', este es un mensaje de recuperación de contraseña. Por favor visita: http://localhost/ecobikemess/views/login/Contraseña.php?token='.$token.'&type='.$tipo_usuario;

        $mail->send();
        header("location: login.php?mensaje=Correo enviado correctamente. Revisa tu bandeja de entrada.");
    } catch (Exception $e) {
        header("location: login.php?mensaje=Error al enviar el correo: {$mail->ErrorInfo}");
    }

} else {
    header("location: login.php?mensaje=El correo no existe o no es válido");
}

mysqli_close($conexion);

?>