<?php
session_start();
// Destruir todas las variables de sesión
session_unset();
// Destruir la sesión
session_destroy();
// Redirigir al index principal
header("Location: ../index.php");
exit();
?>
