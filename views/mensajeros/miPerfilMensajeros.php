<?php
session_start();
require_once '../../models/conexionGlobal.php';
require_once __DIR__ . '/../../includes/paths.php';

if (!isset($_SESSION['user_id']) || ($_SESSION['user_role'] ?? '') !== 'mensajero') {
    header('Location: ' . route_url('login', ['error' => 'Debes iniciar sesión.']));
    exit();
}

$user_id = $_SESSION['user_id'];
$conn = conexionDB();

$stmt = $conn->prepare("SELECT * FROM usuarios WHERE id = :id");
$stmt->execute([':id' => $user_id]);
$usuario = $stmt->fetch(PDO::FETCH_ASSOC);

$stmtM = $conn->prepare("SELECT * FROM mensajeros WHERE usuario_id = :id");
$stmtM->execute([':id' => $user_id]);
$mensajero = $stmtM->fetch(PDO::FETCH_ASSOC);

$resolverFotoPerfil = static function (?string $ruta): string {
    $ruta = trim((string) $ruta);
    if ($ruta === '') {
        return '../../public/img/default-avatar.png';
    }

    if (preg_match('#^https?://#i', $ruta) || str_starts_with($ruta, 'data:image/')) {
        return $ruta;
    }

    $projectRoot = dirname(__DIR__, 2);
    $candidatas = [];

    $rutaNormalizada = str_replace(['\\', '/'], DIRECTORY_SEPARATOR, ltrim($ruta, '/\\'));
    $candidatas[] = $projectRoot . DIRECTORY_SEPARATOR . $rutaNormalizada;

    if (strpos($rutaNormalizada, 'uploads' . DIRECTORY_SEPARATOR) !== 0) {
        $candidatas[] = $projectRoot . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'mensajeros' . DIRECTORY_SEPARATOR . basename($rutaNormalizada);
    }

    $rutaFisica = null;
    foreach ($candidatas as $candidata) {
        if (is_file($candidata) && is_readable($candidata)) {
            $rutaFisica = $candidata;
            break;
        }
    }

    if ($rutaFisica === null) {
        return '../../public/img/default-avatar.png';
    }

    $extension = strtolower(pathinfo($rutaFisica, PATHINFO_EXTENSION));
    $mime = match ($extension) {
        'jpg', 'jpeg' => 'image/jpeg',
        'png' => 'image/png',
        'webp' => 'image/webp',
        'gif' => 'image/gif',
        default => 'application/octet-stream',
    };

    $contenido = @file_get_contents($rutaFisica);
    if ($contenido === false) {
        return '../../public/img/default-avatar.png';
    }

    return 'data:' . $mime . ';base64,' . base64_encode($contenido);
};

$fotoMensajero = trim((string) ($mensajero['foto'] ?? ''));
$fotoMensajeroCache = $_SESSION['user_photo_resolved'] ?? '';
if ($fotoMensajero !== '' && ($_SESSION['user_photo'] ?? '') === $fotoMensajero && is_string($fotoMensajeroCache) && $fotoMensajeroCache !== '') {
    $fotoMensajero = $fotoMensajeroCache;
} else {
    $fotoMensajero = $resolverFotoPerfil($fotoMensajero);
    if (!empty($mensajero['foto'])) {
        $_SESSION['user_photo'] = $mensajero['foto'];
        $_SESSION['user_photo_resolved'] = $fotoMensajero;
    }
}

$opcionesDocumento = [
    'cedula' => 'Cedula de Ciudadania',
    'cedula_extranjeria' => 'Cedula de Extranjeria',
    'pasaporte' => 'Pasaporte',
];
$opcionesSangre = ['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'];
$opcionesTransporte = [
    'bicicleta' => 'Bicicleta',
    'moto' => 'Moto',
    'vehiculo' => 'Carro',
];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Mi Perfil - EcoBikeMess</title>
    <link rel="icon" href="../../public/img/Logo_Negro_Transparente.png" type="image/png">
    <link rel="stylesheet" href="../../public/css/inicioMensajero.css">
    <link rel="stylesheet" href="../../public/css/mensajeroSidebar.css">
    <link rel="stylesheet" href="../../public/css/miPerfil.css?v=20260420-2">
    <link rel="stylesheet" href="../../public/css/responsive.css">
</head>
<body>
    <header class="mobile-header">
        <button class="menu-btn" id="menuBtn">
            <span class="menu-icon" aria-hidden="true">☰</span>
        </button>
        <div class="header-info">
            <h1><img src="../../public/img/Logo_Circulo_Fondoblanco.png" alt="EcoBikeMess" style="width:35px;height:35px;vertical-align:middle;margin-right:6px;">EcoBikeMess</h1>
            <p class="user-name">Mi Perfil</p>
        </div>
    </header>

    <?php include '../layouts/mensajeroSidebar.php'; ?>

    <main class="main-content">
        <div class="session-status">
            <div class="status-indicator online">
                <span class="status-dot"></span>
                <span class="status-text">Perfil Activo</span>
            </div>
            <div class="session-time">
                <span class="time-icon">Perfil</span>
                <span>Datos del mensajero</span>
            </div>
        </div>

        <div class="profile-container">
            <form action="../../controller/perfilController.php" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="action" value="update_profile">

                <div class="profile-card">
                    <div class="profile-bg"></div>
                    <div class="profile-header-content">
                        <div class="avatar-container" style="position: relative; width: 100px; margin: 0 auto;">
                            <img src="<?php echo htmlspecialchars($fotoMensajero, ENT_QUOTES, 'UTF-8'); ?>"
                                 alt="Avatar" class="profile-avatar-large"
                                 id="previewFotoPerfilMensajero"
                                 onerror="this.src='data:image/svg+xml,%3Csvg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 24 24%22%3E%3Ccircle cx=%2212%22 cy=%228%22 r=%224%22 fill=%22%235cb85c%22/%3E%3Cpath d=%22M12 14c-4 0-8 2-8 4v2h16v-2c0-2-4-4-8-4z%22 fill=%22%235cb85c%22/%3E%3C/svg%3E'"
                                 style="width: 100px; height: 100px; border-radius: 50%; object-fit: cover; border: 4px solid white; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
                            <label for="foto_perfil" style="position: absolute; bottom: 0; right: 0; background: #2563eb; color: white; width: 30px; height: 30px; border-radius: 50%; display: flex; align-items: center; justify-content: center; cursor: pointer;">
                                Foto
                            </label>
                            <input type="file" id="foto_perfil" name="foto_perfil" style="display: none;" accept="image/*">
                        </div>
                        <h1 class="profile-name" style="text-align: center; margin-top: 10px;"><?php echo htmlspecialchars(($usuario['nombres'] ?? '') . ' ' . ($usuario['apellidos'] ?? '')); ?></h1>
                        <?php if (isset($_GET['mensaje'])): ?>
                            <div class="alert alert-success" style="margin-top: 15px; display: block; width: fit-content; margin-left: auto; margin-right: auto; padding: 10px 20px;"><?php echo htmlspecialchars($_GET['mensaje']); ?></div>
                        <?php endif; ?>
                        <?php if (isset($_GET['error'])): ?>
                            <div class="alert alert-error" style="margin-top: 15px; display: block; width: fit-content; margin-left: auto; margin-right: auto; padding: 10px 20px;"><?php echo htmlspecialchars($_GET['error']); ?></div>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="profile-card">
                    <h3 class="form-section-title">Informacion Personal</h3>
                    <div class="form-grid">
                        <div class="form-group">
                            <label>Nombres</label>
                            <input type="text" name="nombres" value="<?php echo htmlspecialchars($usuario['nombres'] ?? ''); ?>" required>
                        </div>
                        <div class="form-group">
                            <label>Apellidos</label>
                            <input type="text" name="apellidos" value="<?php echo htmlspecialchars($usuario['apellidos'] ?? ''); ?>" required>
                        </div>
                        <div class="form-group">
                            <label>Telefono</label>
                            <input type="tel" name="telefono" value="<?php echo htmlspecialchars($usuario['telefono'] ?? ''); ?>" required>
                        </div>
                        <div class="form-group">
                            <label>Correo Electronico</label>
                            <input type="email" value="<?php echo htmlspecialchars($usuario['correo'] ?? ''); ?>" disabled>
                        </div>
                    </div>

                    <h4 style="margin-top: 2rem; margin-bottom: 1rem; color: #2c3e50; border-bottom: 1px solid #eee; padding-bottom: 0.5rem;">Cambiar Contrasena (Opcional)</h4>
                    <div class="form-grid">
                        <div class="form-group">
                            <label>Contrasena Actual</label>
                            <input type="password" name="current_password" placeholder="Solo si desea cambiarla">
                        </div>
                        <div class="form-group">
                            <label>Nueva Contrasena</label>
                            <input type="password" name="new_password" placeholder="Minimo 8 caracteres">
                        </div>
                        <div class="form-group">
                            <label>Confirmar Nueva Contrasena</label>
                            <input type="password" name="confirm_password">
                        </div>
                    </div>
                </div>

                <div class="profile-card">
                    <h3 class="form-section-title">Documentacion</h3>
                    <div class="form-grid">
                        <div class="form-group featured-field">
                            <label>Tipo de Documento</label>
                            <div class="select-shell">
                                <span class="select-badge">ID</span>
                                <div class="custom-select" data-select-wrapper>
                                    <select name="tipo_documento" class="native-select">
                                        <?php foreach ($opcionesDocumento as $valor => $label): ?>
                                            <option value="<?php echo htmlspecialchars($valor); ?>" <?php echo (($mensajero['tipo_documento'] ?? '') === $valor) ? 'selected' : ''; ?>><?php echo htmlspecialchars($label); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                    <button type="button" class="custom-select-trigger" data-select-trigger>
                                        <span data-select-value><?php echo htmlspecialchars($opcionesDocumento[$mensajero['tipo_documento'] ?? 'cedula'] ?? 'Cedula de Ciudadania'); ?></span>
                                    </button>
                                    <div class="custom-select-menu" data-select-menu>
                                        <?php foreach ($opcionesDocumento as $valor => $label): ?>
                                            <button type="button" class="custom-select-option<?php echo (($mensajero['tipo_documento'] ?? '') === $valor) ? ' active' : ''; ?>" data-select-option data-value="<?php echo htmlspecialchars($valor); ?>"><?php echo htmlspecialchars($label); ?></button>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="form-group">
                            <label>Numero de Documento</label>
                            <input type="text" name="numDocumento" value="<?php echo htmlspecialchars($mensajero['numDocumento'] ?? ''); ?>">
                        </div>
                        <div class="form-group featured-field">
                            <label>Tipo de Sangre</label>
                            <div class="select-shell">
                                <span class="select-badge">RH</span>
                                <div class="custom-select" data-select-wrapper>
                                    <select name="tipo_sangre" class="native-select">
                                        <?php foreach ($opcionesSangre as $tipo): ?>
                                            <option value="<?php echo htmlspecialchars($tipo); ?>" <?php echo (($mensajero['tipo_sangre'] ?? '') === $tipo) ? 'selected' : ''; ?>><?php echo htmlspecialchars($tipo); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                    <button type="button" class="custom-select-trigger" data-select-trigger>
                                        <span data-select-value><?php echo htmlspecialchars($mensajero['tipo_sangre'] ?? 'O+'); ?></span>
                                    </button>
                                    <div class="custom-select-menu" data-select-menu>
                                        <?php foreach ($opcionesSangre as $tipo): ?>
                                            <button type="button" class="custom-select-option<?php echo (($mensajero['tipo_sangre'] ?? '') === $tipo) ? ' active' : ''; ?>" data-select-option data-value="<?php echo htmlspecialchars($tipo); ?>"><?php echo htmlspecialchars($tipo); ?></button>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="form-group">
                            <label>Direccion de Residencia</label>
                            <input type="text" name="direccion_residencia" value="<?php echo htmlspecialchars($mensajero['direccion_residencia'] ?? ''); ?>">
                        </div>
                    </div>
                </div>

                <div class="profile-card">
                    <h3 class="form-section-title">Vehiculo</h3>
                    <div class="form-grid">
                        <div class="form-group featured-field">
                            <label>Tipo de Transporte</label>
                            <div class="select-shell">
                                <span class="select-badge">MOV</span>
                                <div class="custom-select" data-select-wrapper>
                                    <select name="tipo_transporte" class="native-select">
                                        <?php foreach ($opcionesTransporte as $valor => $label): ?>
                                            <option value="<?php echo htmlspecialchars($valor); ?>" <?php echo (($mensajero['tipo_transporte'] ?? '') === $valor) ? 'selected' : ''; ?>><?php echo htmlspecialchars($label); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                    <button type="button" class="custom-select-trigger" data-select-trigger>
                                        <span data-select-value><?php echo htmlspecialchars($opcionesTransporte[$mensajero['tipo_transporte'] ?? 'bicicleta'] ?? 'Bicicleta'); ?></span>
                                    </button>
                                    <div class="custom-select-menu" data-select-menu>
                                        <?php foreach ($opcionesTransporte as $valor => $label): ?>
                                            <button type="button" class="custom-select-option<?php echo (($mensajero['tipo_transporte'] ?? '') === $valor) ? ' active' : ''; ?>" data-select-option data-value="<?php echo htmlspecialchars($valor); ?>"><?php echo htmlspecialchars($label); ?></button>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="form-group">
                            <label>Placa (Si aplica)</label>
                            <input type="text" name="placa_vehiculo" value="<?php echo htmlspecialchars($mensajero['placa_vehiculo'] ?? ''); ?>">
                        </div>
                        <div class="form-group">
                            <label>Licencia de Conducir</label>
                            <input type="text" name="licencia_conducir" value="<?php echo htmlspecialchars($mensajero['licencia_conducir'] ?? ''); ?>">
                        </div>
                        <div class="form-group">
                            <label>SOAT</label>
                            <input type="text" name="soat" value="<?php echo htmlspecialchars($mensajero['soat'] ?? ''); ?>">
                        </div>
                    </div>
                </div>

                <div class="profile-card">
                    <h3 class="form-section-title">Contactos de Emergencia</h3>
                    <h4 style="margin: 10px 0; color: #64748b; font-size: 0.9rem;">Contacto Principal</h4>
                    <div class="form-grid">
                        <div class="form-group"><label>Nombre</label><input type="text" name="nombre_emergencia1" value="<?php echo htmlspecialchars($mensajero['nombre_emergencia1'] ?? ''); ?>"></div>
                        <div class="form-group"><label>Apellido</label><input type="text" name="apellido_emergencia1" value="<?php echo htmlspecialchars($mensajero['apellido_emergencia1'] ?? ''); ?>"></div>
                        <div class="form-group"><label>Telefono</label><input type="tel" name="telefono_emergencia1" value="<?php echo htmlspecialchars($mensajero['telefono_emergencia1'] ?? ''); ?>"></div>
                    </div>

                    <h4 style="margin: 15px 0 10px; color: #64748b; font-size: 0.9rem; border-top: 1px solid #eee; padding-top: 10px;">Contacto Secundario</h4>
                    <div class="form-grid">
                        <div class="form-group"><label>Nombre</label><input type="text" name="nombre_emergencia2" value="<?php echo htmlspecialchars($mensajero['nombre_emergencia2'] ?? ''); ?>"></div>
                        <div class="form-group"><label>Apellido</label><input type="text" name="apellido_emergencia2" value="<?php echo htmlspecialchars($mensajero['apellido_emergencia2'] ?? ''); ?>"></div>
                        <div class="form-group"><label>Telefono</label><input type="tel" name="telefono_emergencia2" value="<?php echo htmlspecialchars($mensajero['telefono_emergencia2'] ?? ''); ?>"></div>
                    </div>
                </div>

                <button type="submit" class="btn-save" style="margin-bottom: 2rem;">Guardar Cambios</button>
            </form>
        </div>
    </main>

    <script src="../../public/js/mensajeroLayout.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const fotoInput = document.getElementById('foto_perfil');
            const fotoPreview = document.getElementById('previewFotoPerfilMensajero');

            if (!fotoInput || !fotoPreview) {
                return;
            }

            fotoInput.addEventListener('change', function (event) {
                const file = event.target.files && event.target.files[0];
                if (!file) {
                    return;
                }

                if (!file.type.startsWith('image/')) {
                    return;
                }

                const reader = new FileReader();
                reader.onload = function (loadEvent) {
                    if (loadEvent.target && typeof loadEvent.target.result === 'string') {
                        fotoPreview.src = loadEvent.target.result;
                    }
                };
                reader.readAsDataURL(file);
            });

            document.querySelectorAll('[data-select-wrapper]').forEach(function (wrapper) {
                const nativeSelect = wrapper.querySelector('.native-select');
                const trigger = wrapper.querySelector('[data-select-trigger]');
                const menu = wrapper.querySelector('[data-select-menu]');
                const valueLabel = wrapper.querySelector('[data-select-value]');
                const options = wrapper.querySelectorAll('[data-select-option]');

                if (!nativeSelect || !trigger || !menu || !valueLabel) {
                    return;
                }

                const closeAll = function () {
                    document.querySelectorAll('[data-select-wrapper].open').forEach(function (opened) {
                        if (opened !== wrapper) {
                            opened.classList.remove('open');
                        }
                    });
                };

                trigger.addEventListener('click', function () {
                    const willOpen = !wrapper.classList.contains('open');
                    closeAll();
                    wrapper.classList.toggle('open', willOpen);
                });

                options.forEach(function (option) {
                    option.addEventListener('click', function () {
                        const newValue = option.getAttribute('data-value') || '';
                        nativeSelect.value = newValue;
                        valueLabel.textContent = option.textContent || '';
                        options.forEach(function (item) {
                            item.classList.remove('active');
                        });
                        option.classList.add('active');
                        wrapper.classList.remove('open');
                        nativeSelect.dispatchEvent(new Event('change', { bubbles: true }));
                    });
                });
            });

            document.addEventListener('click', function (event) {
                if (!event.target.closest('[data-select-wrapper]')) {
                    document.querySelectorAll('[data-select-wrapper].open').forEach(function (opened) {
                        opened.classList.remove('open');
                    });
                }
            });
        });
    </script>
</body>
</html>
