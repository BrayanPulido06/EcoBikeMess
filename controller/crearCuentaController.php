<?php
ob_start();
session_start();
require_once '../models/crearCuentamodels.php';

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
            
            $datos['transporte'] = [
                'tipo' => $_POST['tipo_transporte'],
                'placa' => $_POST['placa_vehiculo'] ?? '',
            ];

            // Manejo de Archivos (Fotos y PDFs)
            $uploadDir = '../uploads/mensajeros/';
            if (!file_exists($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }

            $archivos = ['foto', 'hoja_vida', 'licencia_conducir', 'soat'];
            $rutas = [];

            foreach ($archivos as $archivo) {
                if (isset($_FILES[$archivo]) && $_FILES[$archivo]['error'] == 0) {
                    $fileName = time() . '_' . $archivo . '_' . basename($_FILES[$archivo]['name']);
                    $targetPath = $uploadDir . $fileName;
                    if (move_uploaded_file($_FILES[$archivo]['tmp_name'], $targetPath)) {
                        $rutas[$archivo] = $fileName;
                    }
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
        // Redirigir con error
        $errorMsg = urlencode($e->getMessage());
        header("Location: ../views/crearCuenta.php?error=" . $errorMsg . "&tipo=" . ($_POST['tipo_usuario'] ?? 'cliente'));
        exit();
    }
} else {
    header("Location: ../views/crearCuenta.php");
    exit();
}
