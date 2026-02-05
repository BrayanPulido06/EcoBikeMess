<?php
session_start();
require_once '../models/Colaborador.php';

$colaboradorModel = new Colaborador();

// Verificar sesión
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'msg' => 'Sesión no iniciada']);
    exit;
}

$usuario_id = $_SESSION['user_id'];
$cliente_id = $colaboradorModel->obtenerClienteId($usuario_id);

if (!$cliente_id) {
    echo json_encode(['status' => 'error', 'msg' => 'No se encontró el perfil de cliente']);
    exit;
}

$op = $_GET['op'] ?? '';

switch ($op) {
    case 'listar':
        $data = $colaboradorModel->listar($cliente_id);
        echo json_encode(['status' => 'success', 'data' => $data]);
        break;

    case 'guardar':
        // Recoger datos del POST
        $nombres = trim($_POST['nombres'] ?? '');
        $apellidos = trim($_POST['apellidos'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $telefono = trim($_POST['telefono'] ?? '');
        $cargo = trim($_POST['cargo'] ?? '');
        $password = $_POST['password'] ?? '';

        // Permisos
        $permisos = [
            'crear_paquetes' => isset($_POST['perm_paquetes']) ? 1 : 0,
            'ver_facturas' => isset($_POST['perm_facturas']) ? 1 : 0,
            'ver_comprobantes' => isset($_POST['perm_comprobantes']) ? 1 : 0,
            'gestionar_recolecciones' => isset($_POST['perm_recolecciones']) ? 1 : 0,
            'ver_reportes' => isset($_POST['perm_reportes']) ? 1 : 0,
            'editar_perfil' => isset($_POST['perm_perfil']) ? 1 : 0,
            'agregar_colaboradores' => isset($_POST['perm_colaboradores']) ? 1 : 0
        ];

        $datos = [
            'cliente_id' => $cliente_id,
            'nombres' => $nombres,
            'apellidos' => $apellidos,
            'correo' => $email,
            'telefono' => $telefono,
            'password' => password_hash($password, PASSWORD_DEFAULT), // Encriptar contraseña
            'cargo' => $cargo,
            'permisos' => $permisos,
            'creado_por' => $usuario_id
        ];

        $res = $colaboradorModel->crear($datos);

        if ($res) {
            echo json_encode(['status' => 'success', 'msg' => 'Colaborador creado correctamente']);
        } else {
            echo json_encode(['status' => 'error', 'msg' => 'Error al crear colaborador. Verifique que el correo no exista.']);
        }
        break;

    case 'historial':
        $data = $colaboradorModel->obtenerHistorial($cliente_id);
        echo json_encode(['status' => 'success', 'data' => $data]);
        break;

    case 'cambiar_estado':
        $colab_id = $_POST['id'] ?? 0;
        $nuevo_estado = $_POST['estado'] ?? 'activo';
        
        if ($colaboradorModel->cambiarEstado($colab_id, $nuevo_estado)) {
            echo json_encode(['status' => 'success', 'msg' => 'Estado actualizado']);
        } else {
            echo json_encode(['status' => 'error', 'msg' => 'Error al actualizar estado']);
        }
        break;

    default:
        echo json_encode(['status' => 'error', 'msg' => 'Operación no válida']);
        break;
}
?>
