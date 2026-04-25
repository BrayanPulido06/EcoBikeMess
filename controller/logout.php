<?php
require_once __DIR__ . '/../includes/paths.php';

session_start();
session_unset();
session_destroy();

redirect_route('home');
?>
