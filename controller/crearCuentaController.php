<?php
ob_start();
session_start();
require_once '../models/crearCuentamodels.php';
require_once __DIR__ . '/../includes/upload.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    try {
        $model = new UsuarioModel();
        
        // Recoger datos comunes
        $tipo_usuario = $_POST['tipo_usuario'];
        $nombres = trim($_POST['nombres']);
        $apellidos = trim($_POST['apellidos']);
        $correo = trim($_POST['correo']);
        $telefono = trim($_POST['telefono']);
        $password = $_POST['password'];
        $confirm_password = $_POST['confirm_password'];

        // Validaciones básicas
        if ($password !== $confirm_password) {
            throw new Exception("Las contraseñas no coinciden");
        }

        if ($model->existeCorreo($correo)) {
            throw new Exception("El correo ya está registrado");
        }

        $datos = [
            'nombres' => $nombres,
            'apellidos' => $apellidos,
            'correo' => $correo,
            'telefono' => $telefono,
            'password' => $password,
            'tipo_usuario' => $tipo_usuario
        ];

        if ($tipo_usuario == 'cliente') {
            $datos['nombre_emprendimiento'] = trim($_POST['nombre_emprendimiento']);
            $datos['tipo_producto'] = trim($_POST['tipo_producto']);
            $datos['instagram'] = trim($_POST['instagram']);
            $datos['direccion_principal'] = trim($_POST['direccion_principal']);

            if (empty($datos['direccion_principal'])) {
                throw new Exception("La dirección principal es obligatoria para completar el registro.");
            }
            
        } elseif ($tipo_usuario == 'mensajero') {
            // Datos específicos mensajero
            $datos['tipo_documento'] = $_POST['tipo_documento'];
            $datos['numDocumento'] = $_POST['numDocumento'];
            $datos['tipo_sangre'] = $_POST['tipo_sangre'];
            $datos['direccion_residencia'] = $_POST['direccion_residencia'];
            
            // Estructurar datos complejos
            $datos['emergencia'] = [
                'contacto1' => [
                    'nombre' => $_POST['nombre_emergencia1'],
                    'apellido' => $_POST['apellido_emergencia1'],
                    'telefono' => $_POST['telefono_emergencia1']
                ],
                'contacto2' => [
                    'nombre' => $_POST['nombre_emergencia2'],
                    'apellido' => $_POST['apellido_emergencia2'],
                    'telefono' => $_POST['telefono_emergencia2']
                ]
            ];
            
            $tipoTransporte = $_POST['tipo_transporte'] ?? '';
            if ($tipoTransporte === 'vehiculo') {
                $tipoTransporte = 'Carro';
            }

            $datos['transporte'] = [
                'tipo' => $tipoTransporte,
                'placa' => trim($_POST['placa_vehiculo'] ?? ''),
            ];

            // Manejo de Archivos (Fotos y PDFs)
            $uploadDir = dirname(__DIR__) . '/uploads/mensajeros/';

            $archivos = ['foto', 'licencia_conducir', 'soat', 'tecnomecanica'];
            $rutas = [];
            $allowedImages = ['image/jpeg', 'image/png', 'image/webp'];
            $allowedDocs = array_merge($allowedImages, ['application/pdf']);

            foreach ($archivos as $archivo) {
                if (isset($_FILES[$archivo]) && $_FILES[$archivo]['error'] == 0) {
                    $allowed = $archivo === 'foto' ? $allowedImages : $allowedDocs;
                    $fileName = saveUploadedFileSafe($_FILES[$archivo], $uploadDir, $allowed, 'ebm_' . $archivo, true);
                    if (!$fileName) {
                        throw new Exception("Archivo inválido: {$archivo}");
                    }
                    $rutas[$archivo] = $fileName;
                } else {
                    $rutas[$archivo] = null;
                }
            }
            $datos['rutas_archivos'] = $rutas;
        }

        // Intentar registrar
        if ($model->registrarUsuario($datos)) {
            header("Location: ../views/login.php?mensaje=Cuenta creada exitosamente. Por favor inicia sesión.");
            exit();
        } else {
            throw new Exception("Error al guardar en la base de datos");
        }

    } catch (Exception $e) {
        error_log('crearCuentaController: ' . $e->getMessage());
        // Redirigir con error
        $errorMsg = urlencode($e->getMessage());
        header("Location: ../views/crearCuenta.php?error=" . $errorMsg . "&tipo=" . ($_POST['tipo_usuario'] ?? 'cliente'));
        exit();
    }
} else {
    header("Location: ../views/crearCuenta.php");
    exit();
}
