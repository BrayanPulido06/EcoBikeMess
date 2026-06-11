<?php
require_once __DIR__ . '/../includes/paths.php';

session_start();
session_unset();
session_destroy();

header('Location: ' . app_url('index.php'), true, 302);
exit;
?>
