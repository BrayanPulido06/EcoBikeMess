<?php
require_once __DIR__ . '/../includes/auth.php';
ensureSessionStarted();
require_once '../models/conexionGlobal.php';

function perfilRedirectPath(string $role): string
{
    if ($role === 'mensajero') {
        return '../views/mensajeros/miPerfilMensajeros.php';
    }

    if ($role === 'administrador' || $role === 'admin') {
        return '../views/layouts/miPerfilAdmin.php';
    }

    return '../views/layouts/miPerfilCliente.php';
}

function redirectPerfil(string $role, string $query = ''): void
{
    $path = perfilRedirectPath($role);
    header('Location: ' . $path . $query);
    exit();
}

function columnExists(PDO $conn, string $table, string $column): bool
{
    static $cache = [];
    $key = $table . '.' . $column;

    if (array_key_exists($key, $cache)) {
        return $cache[$key];
    }

    $stmt = $conn->prepare("SHOW COLUMNS FROM `{$table}` LIKE :column");
    $stmt->execute([':column' => $column]);
    $cache[$key] = (bool) $stmt->fetch(PDO::FETCH_ASSOC);
    return $cache[$key];
}

function guardarArchivoPerfil(array $file, string $subfolder, array $allowedExtensions): ?string
{
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return null;
    }

    if (($file['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
        throw new Exception('No se pudo subir uno de los archivos del perfil.');
    }

    $extension = strtolower(pathinfo($file['name'] ?? '', PATHINFO_EXTENSION));
    if ($extension === '' || !in_array($extension, $allowedExtensions, true)) {
        throw new Exception('Formato de archivo no permitido en el perfil.');
    }

    $baseDir = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . $subfolder;
    if (!is_dir($baseDir) && !mkdir($baseDir, 0775, true) && !is_dir($baseDir)) {
        throw new Exception('No se pudo preparar la carpeta de archivos del perfil.');
    }

    $filename = 'perfil_' . date('Ymd_His') . '_' . bin2hex(random_bytes(4)) . '.' . $extension;
    $targetPath = $baseDir . DIRECTORY_SEPARATOR . $filename;

    if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
        throw new Exception('No se pudo guardar el archivo del perfil.');
    }

    return '/uploads/' . $subfolder . '/' . $filename;
}

function eliminarArchivoPerfilAnterior(?string $storedPath): void
{
    $storedPath = trim((string) $storedPath);
    if ($storedPath === '') {
        return;
    }

    if (preg_match('#^https?://#i', $storedPath) || str_starts_with($storedPath, 'data:')) {
        return;
    }

    $normalized = str_replace(['\\', '/'], DIRECTORY_SEPARATOR, ltrim($storedPath, '/\\'));
    $projectRoot = dirname(__DIR__);
    $candidates = [
        $projectRoot . DIRECTORY_SEPARATOR . $normalized,
    ];

    if (strpos($normalized, 'uploads' . DIRECTORY_SEPARATOR) !== 0) {
        $candidates[] = $projectRoot . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'mensajeros' . DIRECTORY_SEPARATOR . basename($normalized);
    }

    foreach ($candidates as $filePath) {
        if (is_file($filePath) && is_writable($filePath)) {
            @unlink($filePath);
            return;
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['action'])) {
    $role = $_SESSION['user_role'] ?? 'cliente';
    redirectPerfil($role);
}

requireWebAuth();

$role = $_SESSION['user_role'] ?? 'cliente';
$allowedActions = ['update_profile', 'update_profile_mensajero'];

if (!in_array($_POST['action'], $allowedActions, true)) {
    redirectPerfil($role, '?error=' . urlencode('Acción de perfil no válida.'));
}

$user_id = (int) ($_SESSION['user_id'] ?? 0);
if ($user_id <= 0) {
    redirectPerfil($role, '?error=' . urlencode('No se pudo identificar el usuario de la sesión.'));
}

$conn = null;

try {
    $conn = conexionDB();
    $conn->beginTransaction();

    $nombres = trim((string) ($_POST['nombres'] ?? ''));
    $apellidos = trim((string) ($_POST['apellidos'] ?? ''));
    $telefono = trim((string) ($_POST['telefono'] ?? ''));

    if ($nombres === '' || $apellidos === '' || $telefono === '') {
        throw new Exception('Debes completar nombres, apellidos y teléfono.');
    }

    $sqlUser = "UPDATE usuarios
                SET nombres = :nombres, apellidos = :apellidos, telefono = :telefono
                WHERE id = :id";
    $stmtUser = $conn->prepare($sqlUser);
    $stmtUser->execute([
        ':nombres' => $nombres,
        ':apellidos' => $apellidos,
        ':telefono' => $telefono,
        ':id' => $user_id
    ]);

    if ($role === 'cliente') {
        $sqlCliente = "UPDATE clientes
                       SET nombre_emprendimiento = :emp,
                           tipo_producto = :prod,
                           instagram = :insta,
                           direccion_principal = :dir
                       WHERE usuario_id = :uid";
        $stmtCliente = $conn->prepare($sqlCliente);
        $stmtCliente->execute([
            ':emp' => trim((string) ($_POST['nombre_emprendimiento'] ?? '')),
            ':prod' => trim((string) ($_POST['tipo_producto'] ?? '')),
            ':insta' => trim((string) ($_POST['instagram'] ?? '')),
            ':dir' => trim((string) ($_POST['direccion_principal'] ?? '')),
            ':uid' => $user_id
        ]);
    } elseif ($role === 'mensajero') {
        $stmtFotoActual = $conn->prepare("SELECT foto FROM mensajeros WHERE usuario_id = :uid LIMIT 1");
        $stmtFotoActual->execute([':uid' => $user_id]);
        $fotoAnteriorMensajero = trim((string) ($stmtFotoActual->fetchColumn() ?: ''));

        $updateFields = [
            'tipo_documento' => trim((string) ($_POST['tipo_documento'] ?? '')),
            'numDocumento' => trim((string) ($_POST['numDocumento'] ?? '')),
            'tipo_sangre' => trim((string) ($_POST['tipo_sangre'] ?? '')),
            'direccion_residencia' => trim((string) ($_POST['direccion_residencia'] ?? '')),
            'tipo_transporte' => trim((string) ($_POST['tipo_transporte'] ?? '')),
            'placa_vehiculo' => trim((string) ($_POST['placa_vehiculo'] ?? '')),
            'licencia_conducir' => trim((string) ($_POST['licencia_conducir'] ?? '')),
            'soat' => trim((string) ($_POST['soat'] ?? '')),
            'nombre_emergencia1' => trim((string) ($_POST['nombre_emergencia1'] ?? '')),
            'apellido_emergencia1' => trim((string) ($_POST['apellido_emergencia1'] ?? '')),
            'telefono_emergencia1' => trim((string) ($_POST['telefono_emergencia1'] ?? '')),
            'nombre_emergencia2' => trim((string) ($_POST['nombre_emergencia2'] ?? '')),
            'apellido_emergencia2' => trim((string) ($_POST['apellido_emergencia2'] ?? '')),
            'telefono_emergencia2' => trim((string) ($_POST['telefono_emergencia2'] ?? ''))
        ];

        if (!empty($_FILES['foto_perfil']['name'] ?? '')) {
            $updateFields['foto'] = guardarArchivoPerfil($_FILES['foto_perfil'], 'mensajeros', ['jpg', 'jpeg', 'png', 'webp']);
        }

        if (!empty($_FILES['hoja_vida']['name'] ?? '') && columnExists($conn, 'mensajeros', 'hoja_vida')) {
            $updateFields['hoja_vida'] = guardarArchivoPerfil($_FILES['hoja_vida'], 'mensajeros', ['pdf', 'doc', 'docx']);
        }

        $existingColumns = [];
        foreach (array_keys($updateFields) as $column) {
            if (columnExists($conn, 'mensajeros', $column)) {
                $existingColumns[] = $column;
            }
        }

        if (!empty($existingColumns)) {
            $assignments = [];
            $params = [':uid' => $user_id];

            foreach ($existingColumns as $column) {
                $assignments[] = "`{$column}` = :{$column}";
                $params[":{$column}"] = $updateFields[$column];
            }

            $sqlMensajero = "UPDATE mensajeros SET " . implode(', ', $assignments) . " WHERE usuario_id = :uid";
            $stmtMensajero = $conn->prepare($sqlMensajero);
            $stmtMensajero->execute($params);
        }

        if (!empty($updateFields['foto'] ?? '')) {
            eliminarArchivoPerfilAnterior($fotoAnteriorMensajero);
        }
    }

    if (!empty($_POST['new_password'])) {
        $current_password = (string) ($_POST['current_password'] ?? '');
        $new_password = (string) ($_POST['new_password'] ?? '');
        $confirm_password = (string) ($_POST['confirm_password'] ?? '');

        if ($current_password === '') {
            throw new Exception('Debe ingresar su contraseña actual para realizar el cambio.');
        }

        if ($new_password !== $confirm_password) {
            throw new Exception('Las nuevas contraseñas no coinciden.');
        }

        if (mb_strlen($new_password) < 8) {
            throw new Exception('La nueva contraseña debe tener al menos 8 caracteres.');
        }

        $stmtPass = $conn->prepare("SELECT password FROM usuarios WHERE id = :id");
        $stmtPass->execute([':id' => $user_id]);
        $userPass = $stmtPass->fetch(PDO::FETCH_ASSOC);

        if (!$userPass || !(password_verify($current_password, $userPass['password']) || $current_password === $userPass['password'])) {
            throw new Exception('La contraseña actual es incorrecta.');
        }

        $newHash = password_hash($new_password, PASSWORD_DEFAULT);
        $updPass = $conn->prepare("UPDATE usuarios SET password = :pass WHERE id = :id");
        $updPass->execute([':pass' => $newHash, ':id' => $user_id]);
    }

    $conn->commit();

    $_SESSION['user_name'] = $nombres;
    $_SESSION['user_lastname'] = $apellidos;
    $_SESSION['user_phone'] = $telefono;
    if ($role === 'mensajero' && isset($updateFields['foto']) && $updateFields['foto'] !== '') {
        $_SESSION['user_photo'] = $updateFields['foto'];
    }

    redirectPerfil($role, '?mensaje=' . urlencode('Perfil actualizado correctamente'));
} catch (Exception $e) {
    if ($conn instanceof PDO && $conn->inTransaction()) {
        $conn->rollBack();
    }

    redirectPerfil($role, '?error=' . urlencode('Error al actualizar: ' . $e->getMessage()));
}
?>
