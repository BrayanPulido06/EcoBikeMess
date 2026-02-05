<?php
session_start();
require_once '../models/misPedidosModels.php';
require_once '../models/Colaborador.php';

header('Content-Type: application/json');

// Verificar sesión
if (!isset($_SESSION['user_id']) || ($_SESSION['user_role'] !== 'cliente' && $_SESSION['user_role'] !== 'colaborador')) {
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit();
}

try {
    // Obtener el ID real del cliente (tienda)
    $colabHelper = new Colaborador();
    $cliente_id = $colabHelper->obtenerClienteId($_SESSION['user_id']);

    $model = new MisPedidosModel();
    $pedidosDB = $model->obtenerPedidosPorCliente($cliente_id);
    
    // Formatear datos para que coincidan con la estructura que espera el JS
    $pedidos = array_map(function($p) {
        // Construir timeline básico basado en el estado
        $timeline = [];
        $timeline[] = ['estado' => 'Pedido creado', 'fecha' => $p['fecha_creacion'], 'activo' => true];
        
        if ($p['estado'] != 'pendiente') {
            // Aquí podrías agregar más lógica si tuvieras fechas de actualización en la BD
            $timeline[] = ['estado' => ucfirst(str_replace('_', ' ', $p['estado'])), 'fecha' => $p['fecha_creacion'], 'activo' => true];
        }

        return [
            'id' => $p['id'],
            'guia' => $p['numero_guia'],
            'fecha' => $p['fecha_creacion'],
            'estado' => $p['estado'],
            'remitente' => [
                'nombre' => $p['remitente_nombre'],
                'telefono' => $p['remitente_telefono'],
                'direccion' => $p['direccion_origen']
            ],
            'destinatario' => [
                'nombre' => $p['destinatario_nombre'],
                'telefono' => $p['destinatario_telefono'],
                'direccion' => $p['direccion_destino']
            ],
            'paquete' => [
                'descripcion' => $p['descripcion_contenido'],
                'peso' => $p['peso'] . ' kg',
                'tipo' => ucfirst($p['tipo_paquete']),
                'costo' => (float)$p['costo_envio']
            ],
            'timeline' => $timeline,
            // Datos de comprobante (simulados o vacíos si no existen en BD aún)
            'comprobante' => ($p['estado'] === 'entregado') ? [
                'quienRecibio' => $p['destinatario_nombre'], // Asumimos destinatario por ahora
                'parentesco' => 'Destinatario',
                'fechaEntrega' => $p['fecha_creacion'], // Debería ser fecha_entrega real
                'recaudo' => (float)$p['recaudo_esperado'],
                'observaciones' => 'Entrega finalizada',
                'foto' => '../../public/img/default-placeholder.png'
            ] : null
        ];
    }, $pedidosDB);

    echo json_encode(['success' => true, 'data' => $pedidos]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
