<?php
session_start();
require_once '../../models/conexionGlobal.php';

// Verificar sesión (Permitir acceso a cualquier rol logueado)
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$rol = $_SESSION['user_role'] ?? 'usuario'; // Obtener el rol
$conn = conexionDB();

// 1. Obtener datos básicos del usuario (Común para todos)
$sql = "SELECT * FROM usuarios WHERE id = :id";
$stmt = $conn->prepare($sql);
$stmt->execute([':id' => $user_id]);
$usuario = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$usuario) {
    echo "Error: Usuario no encontrado.";
    exit();
}

// 2. Obtener datos específicos según el rol
$datos_extra = [];
if ($rol === 'cliente') {
    $stmtExtra = $conn->prepare("SELECT * FROM clientes WHERE usuario_id = :id");
    $stmtExtra->execute([':id' => $user_id]);
    $datos_extra = $stmtExtra->fetch(PDO::FETCH_ASSOC);
} elseif ($rol === 'colaborador') {
    // Obtener datos del emprendimiento al que pertenece el colaborador
    $sqlColab = "SELECT c.* FROM clientes c 
                 JOIN colaboradores_cliente cc ON c.id = cc.cliente_id 
                 WHERE cc.usuario_id = :id";
    $stmtExtra = $conn->prepare($sqlColab);
    $stmtExtra->execute([':id' => $user_id]);
    $datos_extra = $stmtExtra->fetch(PDO::FETCH_ASSOC);
} elseif ($rol === 'mensajero') {
    $stmtExtra = $conn->prepare("SELECT * FROM mensajeros WHERE usuario_id = :id");
    $stmtExtra->execute([':id' => $user_id]);
    $datos_extra = $stmtExtra->fetch(PDO::FETCH_ASSOC);
} elseif ($rol === 'administrador' || $rol === 'admin') {
    $stmtExtra = $conn->prepare("SELECT * FROM administradores WHERE usuario_id = :id");
    $stmtExtra->execute([':id' => $user_id]);
    $datos_extra = $stmtExtra->fetch(PDO::FETCH_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mi Perfil - EcoBikeMess</title>
    <link rel="stylesheet" href="../../public/css/clienteSidebar.css">
    <link rel="stylesheet" href="../../public/css/clienteNavbar.css">
    <link rel="stylesheet" href="../../public/css/miPerfil.css">

</head>
<body>
    <?php 
    // Incluir navbar solo si es cliente (o adaptar según necesidad)
    // Para este ejemplo, mantenemos la estructura existente
    if ($rol === 'administrador' || $rol === 'admin') {
        include '../layouts/adminNavbar.php'; 
        include '../layouts/adminSidebar.php';
    } elseif ($rol === 'cliente' || $rol === 'colaborador') {
        include '../layouts/clienteNavbar.php'; 
        include '../layouts/clienteSidebar.php';
    } else {
        // Fallback para otros roles o mensajeros si no tienen navbar propio definido aún
        include '../layouts/clienteNavbar.php'; 
    }
    ?>

    <main class="main-content" style="<?php echo ($rol === 'mensajero') ? 'margin-left: 0;' : 'margin-left: 250px;'; ?> padding: 20px; margin-top: 60px;">
        <div class="profile-container">

            <form action="../../controller/perfilController.php" method="POST">
                <input type="hidden" name="action" value="update_profile">
                
                <!-- Tarjeta de Cabecera -->
                <div class="profile-card">
                    <div class="profile-bg"></div>
                    <div class="profile-header-content">
                        <img src="../../public/img/default-avatar.png" alt="Avatar" class="profile-avatar-large">
                        <h1 class="profile-name"><?php echo htmlspecialchars($usuario['nombres'] . ' ' . $usuario['apellidos']); ?></h1>
                        <div class="profile-role"><?php echo ucfirst($rol); ?></div>
                        <?php if (isset($_GET['mensaje'])): ?>
                            <div class="alert alert-success" style="margin-top: 15px; display: block; width: fit-content; margin-left: auto; margin-right: auto; padding: 10px 20px;"><?php echo htmlspecialchars($_GET['mensaje']); ?></div>
                        <?php endif; ?>
                        <?php if (isset($_GET['error'])): ?>
                            <div class="alert alert-error" style="margin-top: 15px; display: block; width: fit-content; margin-left: auto; margin-right: auto; padding: 10px 20px;"><?php echo htmlspecialchars($_GET['error']); ?></div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Sección 1: Datos Personales (Común) -->
                <div class="profile-card">
                    <h3 class="form-section-title">👤 Información Personal</h3>
                    <div class="form-grid">
                        <div class="form-group">
                            <label>Nombres</label>
                            <input type="text" name="nombres" value="<?php echo htmlspecialchars($usuario['nombres']); ?>" required>
                        </div>
                        <div class="form-group">
                            <label>Apellidos</label>
                            <input type="text" name="apellidos" value="<?php echo htmlspecialchars($usuario['apellidos']); ?>" required>
                        </div>
                        <div class="form-group">
                            <label>Teléfono</label>
                            <input type="tel" name="telefono" value="<?php echo htmlspecialchars($usuario['telefono']); ?>" required>
                        </div>
                        <div class="form-group">
                            <label>Correo Electrónico</label>
                            <input type="email" value="<?php echo htmlspecialchars($usuario['correo']); ?>" disabled title="El correo no se puede editar">
                        </div>
                    </div>

                    <!-- Sección de Cambio de Contraseña (Integrada) -->
                    <h4 style="margin-top: 2rem; margin-bottom: 1rem; color: #2c3e50; border-bottom: 1px solid #eee; padding-bottom: 0.5rem;">🔒 Cambiar Contraseña (Opcional)</h4>
                    <div class="form-grid">
                        <div class="form-group">
                            <label>Contraseña Actual</label>
                            <input type="password" name="current_password" placeholder="Solo si desea cambiarla">
                        </div>
                        <div class="form-group">
                            <label>Nueva Contraseña</label>
                            <input type="password" name="new_password">
                        </div>
                        <div class="form-group">
                            <label>Confirmar Nueva Contraseña</label>
                            <input type="password" name="confirm_password">
                        </div>
                    </div>
                </div>

                <!-- Sección 2: Datos Específicos por Rol -->
                
                <!-- CLIENTE -->
                <?php if ($rol === 'cliente' || $rol === 'colaborador'): ?>
                <?php $readonly = ($rol === 'colaborador') ? 'disabled' : ''; ?>
                <div class="profile-card">
                    <h3 class="form-section-title">🏢 Datos del Emprendimiento</h3>
                    <div class="form-grid">
                        <div class="form-group">
                            <label>Nombre del Emprendimiento</label>
                            <input type="text" name="nombre_emprendimiento" value="<?php echo htmlspecialchars($datos_extra['nombre_emprendimiento'] ?? ''); ?>" <?php echo $readonly; ?>>
                        </div>
                        <div class="form-group">
                            <label>Tipo de Producto</label>
                            <input type="text" name="tipo_producto" value="<?php echo htmlspecialchars($datos_extra['tipo_producto'] ?? ''); ?>" <?php echo $readonly; ?>>
                        </div>
                        <div class="form-group">
                            <label>Instagram</label>
                            <input type="text" name="instagram" value="<?php echo htmlspecialchars($datos_extra['instagram'] ?? ''); ?>" placeholder="@usuario" <?php echo $readonly; ?>>
                        </div>
                        <div class="form-group">
                            <label>Dirección Principal</label>
                            <input type="text" name="direccion_principal" value="<?php echo htmlspecialchars($datos_extra['direccion_principal'] ?? ''); ?>" <?php echo $readonly; ?>>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <!-- MENSAJERO -->
                <?php if ($rol === 'mensajero'): ?>
                <div class="profile-card">
                    <h3 class="form-section-title">🚴 Datos del Mensajero</h3>
                    <div class="form-grid">
                        <div class="form-group">
                            <label>Tipo de Documento</label>
                            <input type="text" name="tipo_documento" value="<?php echo htmlspecialchars($datos_extra['tipo_documento'] ?? ''); ?>">
                        </div>
                        <div class="form-group">
                            <label>Número de Documento</label>
                            <input type="text" name="numDocumento" value="<?php echo htmlspecialchars($datos_extra['numDocumento'] ?? ''); ?>">
                        </div>
                        <div class="form-group">
                            <label>Tipo de Sangre</label>
                            <input type="text" name="tipo_sangre" value="<?php echo htmlspecialchars($datos_extra['tipo_sangre'] ?? ''); ?>">
                        </div>
                        <div class="form-group">
                            <label>Dirección de Residencia</label>
                            <input type="text" name="direccion_residencia" value="<?php echo htmlspecialchars($datos_extra['direccion_residencia'] ?? ''); ?>">
                        </div>
                        <div class="form-group">
                            <label>Tipo de Transporte</label>
                            <input type="text" name="tipo_transporte" value="<?php echo htmlspecialchars($datos_extra['tipo_transporte'] ?? ''); ?>">
                        </div>
                        <div class="form-group">
                            <label>Placa Vehículo (Si aplica)</label>
                            <input type="text" name="placa_vehiculo" value="<?php echo htmlspecialchars($datos_extra['placa_vehiculo'] ?? ''); ?>">
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <!-- ADMINISTRADOR -->
                <?php if ($rol === 'administrador' || $rol === 'admin'): ?>
                <div class="profile-card">
                    <h3 class="form-section-title">🛡️ Datos de Administrador</h3>
                    <div class="form-grid">
                        <div class="form-group">
                            <label>Rol</label>
                            <input type="text" value="<?php echo htmlspecialchars($datos_extra['rol'] ?? ''); ?>" disabled>
                        </div>
                        <div class="form-group">
                            <label>Tipo de Documento</label>
                            <input type="text" value="<?php echo htmlspecialchars($datos_extra['tipo_documento'] ?? ''); ?>" disabled>
                        </div>
                        <div class="form-group">
                            <label>Número de Documento</label>
                            <input type="text" value="<?php echo htmlspecialchars($datos_extra['num_documento'] ?? ''); ?>" disabled>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <button type="submit" class="btn-save">Guardar Cambios</button>
            </form>

        </div>
    </main>
</body>
</html>
