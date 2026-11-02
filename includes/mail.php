<?php
declare(strict_types=1);

use PHPMailer\PHPMailer\PHPMailer;

function obtenerConfiguracionCorreo(): array
{
    $configPath = __DIR__ . '/mail-config.local.php';
    if (!is_file($configPath)) {
        throw new RuntimeException('Falta configurar includes/mail-config.local.php');
    }

    $config = require $configPath;
    if (!is_array($config)) {
        throw new RuntimeException('La configuración SMTP no es válida');
    }

    foreach (['host', 'port', 'encryption', 'username', 'password', 'from_email', 'from_name'] as $key) {
        if (!isset($config[$key]) || trim((string) $config[$key]) === '') {
            throw new RuntimeException('Falta el dato SMTP requerido: ' . $key);
        }
    }

    if (!filter_var((string) $config['from_email'], FILTER_VALIDATE_EMAIL)
        || !filter_var((string) $config['username'], FILTER_VALIDATE_EMAIL)) {
        throw new RuntimeException('El usuario SMTP y el remitente deben ser correos válidos');
    }

    $config['app_url'] = rtrim((string) ($config['app_url'] ?? 'https://ecobikemess.com'), '/');
    if (!filter_var($config['app_url'], FILTER_VALIDATE_URL)
        || parse_url($config['app_url'], PHP_URL_SCHEME) !== 'https') {
        throw new RuntimeException('La URL pública debe ser una URL HTTPS válida');
    }

    return $config;
}

function enviarCorreoRecuperacion(string $destinatario, string $nombre, string $url): void
{
    $config = obtenerConfiguracionCorreo();

    require_once __DIR__ . '/../PHPMailer/Exception.php';
    require_once __DIR__ . '/../PHPMailer/PHPMailer.php';
    require_once __DIR__ . '/../PHPMailer/SMTP.php';

    $mail = new PHPMailer(true);
    $mail->isSMTP();
    $mail->Host = (string) $config['host'];
    $mail->SMTPAuth = true;
    $mail->Username = (string) $config['username'];
    $mail->Password = (string) $config['password'];
    $mail->Port = (int) $config['port'];
    $mail->SMTPSecure = strtolower((string) $config['encryption']) === 'ssl'
        ? PHPMailer::ENCRYPTION_SMTPS
        : PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Timeout = 15;
    $mail->CharSet = PHPMailer::CHARSET_UTF8;

    $mail->setFrom((string) $config['from_email'], (string) $config['from_name']);
    $mail->addAddress($destinatario, $nombre);
    $mail->isHTML(true);
    $mail->Subject = 'Recupera tu contrasena de EcoBikeMess';

    $nombreSeguro = htmlspecialchars($nombre !== '' ? $nombre : 'usuario', ENT_QUOTES, 'UTF-8');
    $urlSegura = htmlspecialchars($url, ENT_QUOTES, 'UTF-8');
    $mail->Body = '<p>Hola ' . $nombreSeguro . ',</p>'
        . '<p>Recibimos una solicitud para cambiar la contrasena de tu cuenta de EcoBikeMess.</p>'
        . '<p><a href="' . $urlSegura . '">Crear una nueva contrasena</a></p>'
        . '<p>Este enlace vence en 30 minutos. Si no solicitaste el cambio, ignora este correo.</p>';
    $mail->AltBody = "Hola " . ($nombre !== '' ? $nombre : 'usuario')
        . ",\n\nPara cambiar tu contrasena de EcoBikeMess abre este enlace:\n"
        . $url . "\n\nEl enlace vence en 30 minutos. Si no solicitaste el cambio, ignora este correo.";

    $mail->send();
}
