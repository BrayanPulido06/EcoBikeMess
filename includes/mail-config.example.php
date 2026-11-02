<?php
/**
 * Copia este archivo como mail-config.local.php y completa los datos SMTP
 * de la cuenta de correo creada para el dominio.
 * No subas mail-config.local.php al repositorio.
 */
return [
    'host' => 'smtp.hostinger.com',
    'port' => 465,
    'encryption' => 'ssl', // ssl para 465; tls para 587
    'username' => '', // correo completo de la cuenta emisora
    'password' => '', // contraseña de esa cuenta de correo
    'from_email' => '', // normalmente el mismo correo de username
    'from_name' => 'EcoBikeMess',
    'app_url' => 'https://ecobikemess.com',
];
