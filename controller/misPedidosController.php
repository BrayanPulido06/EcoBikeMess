<?php
require_once __DIR__ . '/../includes/auth.php';
ensureSessionStarted();
header('Content-Type: application/json');

require_once __DIR__ . '/../models/misPedidosModels.php';

// Verificar sesión
if (!isset($_SESSION['user_id']) || !in_array(($_SESSION['user_role'] ?? ''), ['cliente', 'colaborador'], true)) {
    echo json_encode(['error' => 'No autorizado']);
    exit();
}

$model = new MisPedidosModel();
$usuario_id = $_SESSION['user_id'];
$rol = $_SESSION['user_role'] ?? 'cliente';

// Obtener ID del cliente asociado al usuario
$cliente_id = $model->obtenerIdCliente($usuario_id, $rol);

if (!$cliente_id) {
    echo json_encode(['error' => 'No se encontró un cliente asociado a este usuario']);
    exit();
}

$action = $_GET['action'] ?? '';

switch ($action) {
    case 'listar':
        $filtros = [
            'search' => $_GET['search'] ?? '',
            'fechaDesde' => $_GET['fechaDesde'] ?? '',
            'fechaHasta' => $_GET['fechaHasta'] ?? '',
            'estado' => $_GET['estado'] ?? '',
            'monto' => $_GET['monto'] ?? ''
        ];

        try {
            $data = $model->listarFacturas($cliente_id, $filtros);
            echo json_encode(['data' => $data]);
        } catch (Exception $e) {
            echo json_encode(['error' => 'Error al listar facturas: ' . $e->getMessage()]);
        }
        break;

    case 'detalle':
        $id = $_GET['id'] ?? 0;
        if (!$id) {
            echo json_encode(['error' => 'ID de factura inválido']);
            exit();
        }

        try {
            $detalle = $model->obtenerDetalleFactura($id, $cliente_id);
            if ($detalle) {
                echo json_encode(['success' => true, 'data' => $detalle]);
            } else {
                echo json_encode(['error' => 'Factura no encontrada']);
            }
        } catch (Exception $e) {
            echo json_encode(['error' => 'Error al obtener detalle: ' . $e->getMessage()]);
        }
        break;

    case 'estadisticas':
        // Capturar los filtros enviados desde el JS (igual que en 'listar')
        $filtros = [
            'search' => $_GET['search'] ?? '',
            'fechaDesde' => $_GET['fechaDesde'] ?? '',
            'fechaHasta' => $_GET['fechaHasta'] ?? '',
            'estado' => $_GET['estado'] ?? '',
            'monto' => $_GET['monto'] ?? ''
        ];

        try {
            // Pasamos los filtros al modelo para que calcule el saldo solo de esos días/criterios
            $stats = $model->obtenerEstadisticas($cliente_id, $filtros);
            echo json_encode(['success' => true, 'data' => $stats]);
        } catch (Exception $e) {
            echo json_encode(['error' => 'Error al obtener estadísticas']);
        }
        break;

    case 'cancelar':
        $raw = file_get_contents('php://input');
        $input = json_decode($raw, true);
        $id = (int) ($input['id'] ?? $_POST['id'] ?? 0);
        if ($id <= 0) {
            echo json_encode(['success' => false, 'message' => 'ID de pedido inválido']);
            exit();
        }

        try {
            $ok = $model->cancelarPedido($id, $cliente_id);
            if ($ok) {
                echo json_encode(['success' => true, 'message' => 'Pedido cancelado correctamente']);
            } else {
                echo json_encode(['success' => false, 'message' => 'No se pudo cancelar el pedido. Puede que ya esté entregado o cancelado.']);
            }
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Error al cancelar el pedido']);
        }
        break;

    default:
        echo json_encode(['error' => 'Acción no válida']);
        break;
}
?>
