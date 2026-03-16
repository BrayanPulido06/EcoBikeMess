<?php
function ensureDir($dir)
{
    if (!is_dir($dir)) {
        mkdir($dir, 0777, true);
    }
}

function detectMimeFromFile($tmpPath)
{
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    return $finfo->file($tmpPath) ?: null;
}

function detectImageMimeFromString($binary)
{
    $info = @getimagesizefromstring($binary);
    if (!$info || empty($info['mime'])) {
        return null;
    }
    return $info['mime'];
}

function extensionFromMime($mime)
{
    $map = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
        'application/pdf' => 'pdf'
    ];
    return $map[$mime] ?? null;
}

function generateSafeName($prefix, $ext)
{
    return sprintf('%s_%s_%s.%s', $prefix, date('Ymd_His'), bin2hex(random_bytes(4)), $ext);
}

function saveUploadedFileSafe($file, $destDir, array $allowedMimes, $prefix = 'ebm', $returnBasename = true)
{
    if (!isset($file['tmp_name']) || $file['error'] !== UPLOAD_ERR_OK) {
        return null;
    }

    if (!is_uploaded_file($file['tmp_name'])) {
        return null;
    }

    $mime = detectMimeFromFile($file['tmp_name']);
    if (!$mime || !in_array($mime, $allowedMimes, true)) {
        return null;
    }

    $ext = extensionFromMime($mime);
    if (!$ext) {
        return null;
    }

    ensureDir($destDir);
    $fileName = generateSafeName($prefix, $ext);
    $targetPath = rtrim($destDir, '/\\') . DIRECTORY_SEPARATOR . $fileName;

    if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
        return null;
    }

    return $returnBasename ? $fileName : $targetPath;
}

function saveBase64ImageSafe($base64, $subdir, $prefix = 'ebm')
{
    if (!$base64 || strpos($base64, 'base64,') === false) {
        return null;
    }

    [$meta, $contenido] = explode('base64,', $base64, 2);
    $binario = base64_decode($contenido);
    if ($binario === false) {
        return null;
    }

    $mime = detectImageMimeFromString($binario);
    if (!$mime || !in_array($mime, ['image/jpeg', 'image/png', 'image/webp'], true)) {
        return null;
    }

    $ext = extensionFromMime($mime);
    if (!$ext) {
        return null;
    }

    $dirFisico = dirname(__DIR__) . '/uploads/' . $subdir;
    ensureDir($dirFisico);

    $nombre = generateSafeName($prefix, $ext);
    $rutaFisica = $dirFisico . '/' . $nombre;
    if (file_put_contents($rutaFisica, $binario) === false) {
        return null;
    }

    return '/uploads/' . $subdir . '/' . $nombre;
}
?>