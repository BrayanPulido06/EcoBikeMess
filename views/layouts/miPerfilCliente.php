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

function asegurarColumnasBancariasCliente(PDO $conn): void
{
    $columns = [
        'cuenta_bancaria_principal' => "ALTER TABLE clientes ADD COLUMN cuenta_bancaria_principal TEXT NULL AFTER direccion_principal",
        'cuenta_bancaria_opcional_1' => "ALTER TABLE clientes ADD COLUMN cuenta_bancaria_opcional_1 TEXT NULL AFTER cuenta_bancaria_principal",
        'cuenta_bancaria_opcional_2' => "ALTER TABLE clientes ADD COLUMN cuenta_bancaria_opcional_2 TEXT NULL AFTER cuenta_bancaria_opcional_1",
        'cuenta_bancaria_opcional_3' => "ALTER TABLE clientes ADD COLUMN cuenta_bancaria_opcional_3 TEXT NULL AFTER cuenta_bancaria_opcional_2",
    ];

    foreach ($columns as $column => $alterSql) {
        $stmtColumn = $conn->prepare("SHOW COLUMNS FROM clientes LIKE :column_name");
        $stmtColumn->execute([':column_name' => $column]);
        if (!$stmtColumn->fetch(PDO::FETCH_ASSOC)) {
            $conn->exec($alterSql);
        }
    }
}

asegurarColumnasBancariasCliente($conn);

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
}

$apiKeyActive = null;
$apiKeyCreatedOnce = null;
$apiKeysCsrf = '';
if ($rol === 'cliente') {
    $conn->exec("CREATE TABLE IF NOT EXISTS api_client_keys (
        id INT PRIMARY KEY AUTO_INCREMENT,
        cliente_id INT NOT NULL,
        key_hash CHAR(64) NOT NULL UNIQUE,
        key_prefix VARCHAR(24) NOT NULL,
        nombre VARCHAR(100) NOT NULL,
        estado ENUM('activo', 'inactivo') NOT NULL DEFAULT 'activo',
        fecha_creacion TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        ultimo_uso TIMESTAMP NULL,
        FOREIGN KEY (cliente_id) REFERENCES clientes(id) ON DELETE CASCADE,
        INDEX idx_api_client_keys_cliente_estado (cliente_id, estado)
    )");
    $apiClientId = (int) ($datos_extra['id'] ?? 0);
    $apiKeyStmt = $conn->prepare("SELECT key_prefix, fecha_creacion, ultimo_uso FROM api_client_keys
        WHERE cliente_id = :cliente_id AND estado = 'activo' ORDER BY id DESC LIMIT 1");
    $apiKeyStmt->execute([':cliente_id' => $apiClientId]);
    $apiKeyActive = $apiKeyStmt->fetch(PDO::FETCH_ASSOC) ?: null;
    $apiKeysCsrf = $_SESSION['api_keys_csrf'] ??= bin2hex(random_bytes(32));
    $apiKeyCreatedOnce = $_SESSION['api_key_created_once'] ?? null;
    unset($_SESSION['api_key_created_once']);
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mi Perfil - EcoBikeMess</title>
    <link rel="icon" href="../../public/img/Logo_Negro_Transparente.png" type="image/png">
    <link rel="stylesheet" href="../../public/css/clienteSidebar.css">
    <link rel="stylesheet" href="../../public/css/clienteNavbar.css">
    <link rel="stylesheet" href="../../public/css/miPerfil.css">
    <link rel="stylesheet" href="../../public/css/responsive.css">

    <style>
        /* Ocultar el botón de WhatsApp solo en esta vista para no interferir con el formulario */
        .whatsapp-container {
            display: none !important;
        }
    </style>
</head>
<body>
    <?php 
    // Incluir navbar solo si es cliente (o adaptar según necesidad)
    // Para este ejemplo, mantenemos la estructura existente
    if ($rol === 'cliente' || $rol === 'colaborador') {
        include '../layouts/clienteNavbar.php'; 
        include '../layouts/clienteSidebar.php';
    } else {
        // Si es admin o mensajero, podrías incluir sus propios navbars aquí
        // Por ahora incluimos el de cliente para mantener el estilo, aunque los enlaces no sean los suyos
        include '../layouts/clienteNavbar.php'; 
        // Ocultamos el sidebar si no es cliente para no confundir, o lo incluimos si es deseado
        // include '../layouts/clienteSidebar.php'; 
    }
    ?>

    <main class="main-content profile-main-content<?php echo ($rol !== 'cliente' && $rol !== 'colaborador') ? ' profile-no-sidebar' : ''; ?>">
        <div class="profile-container">

            <form action="../../controller/perfilController.php" method="POST">
                <input type="hidden" name="action" value="update_profile">
                
                <!-- Tarjeta de Cabecera -->
                <div class="profile-card">
                    <div class="profile-bg"></div>
                    <div class="profile-header-content" style="padding-top: 60px;">
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
                            <input type="text" name="nombres" value="<?php echo htmlspecialchars($usuario['nombres']); ?>" autocomplete="given-name" required>
                        </div>
                        <div class="form-group">
                            <label>Apellidos</label>
                            <input type="text" name="apellidos" value="<?php echo htmlspecialchars($usuario['apellidos']); ?>" autocomplete="family-name" required>
                        </div>
                        <div class="form-group">
                            <label>Teléfono</label>
                            <input type="tel" name="telefono" value="<?php echo htmlspecialchars($usuario['telefono']); ?>" autocomplete="tel" required>
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
                <div class="profile-card">
                    <h3 class="form-section-title">Datos bancarios</h3>
                    <div class="form-grid">
                        <div class="form-group">
                            <label>Cuenta principal / Nequi / llave bancaria *</label>
                            <input type="text" name="cuenta_bancaria_principal" value="<?php echo htmlspecialchars($datos_extra['cuenta_bancaria_principal'] ?? ''); ?>" placeholder="Ej: Nequi 3001234567 o llave principal" <?php echo $readonly; ?> <?php echo $rol === 'cliente' ? 'required' : ''; ?>>
                        </div>
                        <div class="form-group">
                            <label>Cuenta adicional 1</label>
                            <input type="text" name="cuenta_bancaria_opcional_1" value="<?php echo htmlspecialchars($datos_extra['cuenta_bancaria_opcional_1'] ?? ''); ?>" placeholder="Opcional" <?php echo $readonly; ?>>
                        </div>
                        <div class="form-group">
                            <label>Cuenta adicional 2</label>
                            <input type="text" name="cuenta_bancaria_opcional_2" value="<?php echo htmlspecialchars($datos_extra['cuenta_bancaria_opcional_2'] ?? ''); ?>" placeholder="Opcional" <?php echo $readonly; ?>>
                        </div>
                        <div class="form-group">
                            <label>Cuenta adicional 3</label>
                            <input type="text" name="cuenta_bancaria_opcional_3" value="<?php echo htmlspecialchars($datos_extra['cuenta_bancaria_opcional_3'] ?? ''); ?>" placeholder="Opcional" <?php echo $readonly; ?>>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <!-- MENSAJERO -->
                <?php if ($rol === 'mensajero'): ?>
                <div class="profile-card">
                    <h3 class="form-section-title"><img src="../../public/img/Logo_Circulo_Fondoblanco.png" alt="EcoBikeMess" style="width:18px;height:18px;vertical-align:middle;margin-right:6px;">Datos del Mensajero</h3>
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

                <button type="submit" class="btn-save">Guardar Cambios</button>
            </form>

            <?php if ($rol === 'cliente'): ?>
            <section class="profile-card" aria-labelledby="api-access-title">
                <h3 class="form-section-title" id="api-access-title">Integración con la API</h3>
                <p>Conecta tu tienda para que los pedidos se registren automáticamente en EcoBike. El remitente se toma del nombre, teléfono y dirección principal de este perfil.</p>
                <p><strong>Endpoint:</strong> <code>POST /api/v1/shipments.php</code></p>
                <?php if ($apiKeyActive): ?>
                    <p>Clave activa: <code><?php echo htmlspecialchars($apiKeyActive['key_prefix'], ENT_QUOTES, 'UTF-8'); ?>…</code></p>
                    <p>Último uso: <?php echo htmlspecialchars($apiKeyActive['ultimo_uso'] ?: 'Aún no se ha usado', ENT_QUOTES, 'UTF-8'); ?></p>
                <?php else: ?>
                    <p>No tienes una clave API activa.</p>
                <?php endif; ?>
                <?php if (is_string($apiKeyCreatedOnce) && $apiKeyCreatedOnce !== ''): ?>
                    <div class="alert alert-success" role="status">
                        <strong>Copia la clave ahora; solo se muestra esta vez.</strong><br>
                        <code><?php echo htmlspecialchars($apiKeyCreatedOnce, ENT_QUOTES, 'UTF-8'); ?></code>
                    </div>
                <?php endif; ?>
                <form action="../../controller/apiClienteController.php" method="POST" style="display:inline-block;">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($apiKeysCsrf, ENT_QUOTES, 'UTF-8'); ?>">
                    <input type="hidden" name="action" value="create">
                    <button type="submit" class="btn-save">Generar / renovar clave</button>
                </form>
                <?php if ($apiKeyActive): ?>
                    <form action="../../controller/apiClienteController.php" method="POST" style="display:inline-block; margin-left:10px;">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($apiKeysCsrf, ENT_QUOTES, 'UTF-8'); ?>">
                        <input type="hidden" name="action" value="revoke">
                        <button type="submit" class="btn-save" style="background:#b42318;">Revocar clave</button>
                    </form>
                <?php endif; ?>
                <p>Al renovar la clave, la anterior deja de funcionar. Envíala solo desde el servidor de tu tienda, nunca desde el navegador.</p>
            </section>
            <?php endif; ?>

        </div>
    </main>
</body>
</html>
