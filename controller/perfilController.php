<?php
session_start();
require_once '../models/conexionGlobal.php';

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action'])) {
    
    if (!isset($_SESSION['user_id'])) {
        header("Location: ../views/login.php");
        exit();
    }

    $user_id = $_SESSION['user_id'];

    if ($_POST['action'] == 'update_profile') {
        $nombres = trim($_POST['nombres']);
        $apellidos = trim($_POST['apellidos']);
        $telefono = trim($_POST['telefono']);

        try {
            $conn = conexionDB();
            $conn->beginTransaction();

            // 1. Actualizar tabla usuarios
            $sqlUser = "UPDATE usuarios SET nombres = :nombres, apellidos = :apellidos, telefono = :telefono WHERE id = :id";
            $stmtUser = $conn->prepare($sqlUser);
            $stmtUser->execute([
                ':nombres' => $nombres,
                ':apellidos' => $apellidos,
                ':telefono' => $telefono,
                ':id' => $user_id
            ]);

            // 2. Actualizar tabla específica según rol
            if ($_SESSION['user_role'] == 'cliente') {
                $nombre_emprendimiento = trim($_POST['nombre_emprendimiento'] ?? '');
                $tipo_producto = trim($_POST['tipo_producto'] ?? '');
                $instagram = trim($_POST['instagram'] ?? '');
                $direccion_principal = trim($_POST['direccion_principal'] ?? '');

                $sqlCliente = "UPDATE clientes SET nombre_emprendimiento = :emp, tipo_producto = :prod, instagram = :insta, direccion_principal = :dir WHERE usuario_id = :uid";
                $stmtCliente = $conn->prepare($sqlCliente);
                $stmtCliente->execute([
                    ':emp' => $nombre_emprendimiento,
                    ':prod' => $tipo_producto,
                    ':insta' => $instagram,
                    ':dir' => $direccion_principal,
                    ':uid' => $user_id
                ]);
            } elseif ($_SESSION['user_role'] == 'mensajero') {
                // Datos específicos de mensajero
                $sqlMensajero = "UPDATE mensajeros SET tipo_documento = :tdoc, numDocumento = :ndoc, tipo_sangre = :tsangre, direccion_residencia = :dir, tipo_transporte = :transp, placa_vehiculo = :placa WHERE usuario_id = :uid";
                $stmtMensajero = $conn->prepare($sqlMensajero);
                $stmtMensajero->execute([
                    ':tdoc' => trim($_POST['tipo_documento'] ?? ''),
                    ':ndoc' => trim($_POST['numDocumento'] ?? ''),
                    ':tsangre' => trim($_POST['tipo_sangre'] ?? ''),
                    ':dir' => trim($_POST['direccion_residencia'] ?? ''),
                    ':transp' => trim($_POST['tipo_transporte'] ?? ''),
                    ':placa' => trim($_POST['placa_vehiculo'] ?? ''),
                    ':uid' => $user_id
                ]);
            }

            // 3. Verificar si se solicitó cambio de contraseña
            if (!empty($_POST['new_password'])) {
                $current_password = $_POST['current_password'] ?? '';
                $new_password = $_POST['new_password'];
                $confirm_password = $_POST['confirm_password'] ?? '';

                if (empty($current_password)) {
                    throw new Exception("Debe ingresar su contraseña actual para realizar el cambio.");
                }
                if ($new_password !== $confirm_password) {
                    throw new Exception("Las nuevas contraseñas no coinciden.");
                }

                // Verificar contraseña actual
                $stmtPass = $conn->prepare("SELECT password FROM usuarios WHERE id = :id");
                $stmtPass->execute([':id' => $user_id]);
                $userPass = $stmtPass->fetch(PDO::FETCH_ASSOC);

                if ($userPass && (password_verify($current_password, $userPass['password']) || $current_password === $userPass['password'])) {
                    $newHash = password_hash($new_password, PASSWORD_DEFAULT);
                    $updPass = $conn->prepare("UPDATE usuarios SET password = :pass WHERE id = :id");
                    $updPass->execute([':pass' => $newHash, ':id' => $user_id]);
                } else {
                    throw new Exception("La contraseña actual es incorrecta.");
                }
            }

            $conn->commit();
            
            // Actualizar datos en sesión para que el Navbar se actualice al instante
            $_SESSION['user_name'] = $nombres;
            $_SESSION['user_lastname'] = $apellidos;
            $_SESSION['user_phone'] = $telefono;

            header("Location: ../views/layouts/miPerfil.php?mensaje=Perfil actualizado correctamente");
            exit();

        } catch (Exception $e) {
            $conn->rollBack();
            header("Location: ../views/layouts/miPerfil.php?error=Error al actualizar: " . urlencode($e->getMessage()));
            exit();
        }
    }
} else {
    header("Location: ../views/layouts/miPerfil.php");
    exit();
}
?>
