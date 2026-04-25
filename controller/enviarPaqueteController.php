<?php
require_once __DIR__ . '/../includes/auth.php';
requireWebAuth(['cliente', 'colaborador'], route_url('login', ['error' => 'Debes iniciar sesion.']));

require_once '../models/inicioClienteModel.php';
require_once '../models/enviarPaqueteModels.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $isAjax = isset($_POST['ajax']) && $_POST['ajax'] == '1';
        $inicioModel = new InicioClienteModel();
        $envioModel = new EnvioModel();

        $usuario_id = $_SESSION['user_id'];
        $rol = $_SESSION['user_role'] ?? 'cliente';

        $cliente_id = $inicioModel->obtenerIdCliente($usuario_id, $rol);

        if (!$cliente_id) {
            throw new Exception('Error crítico: No se pudo identificar la cuenta de la tienda asociada a este usuario.');
        }

        $datos = $_POST;
        $datos['cliente_id'] = $cliente_id;
        $datos['creado_por'] = $usuario_id;
        $datos['descripcion_contenido'] = trim((string) ($_POST['descripcion_contenido'] ?? ''));

        $datos['tiene_recaudo'] = isset($_POST['tiene_recaudo']) ? 1 : 0;
        $datos['tiene_cambios'] = isset($_POST['recoger_cambios']) ? 1 : 0;
        $datos['envio_destinatario'] = $_POST['envio_destinatario'] ?? 'no';

        $dimensionesMap = [
            '0' => 'Menor o igual a 20 x 20 cm',
            '2000' => 'Entre 21x21 y 30x30 cm',
            '4000' => 'Entre 31x31 y 35x35 cm',
            '7000' => 'Entre 36x36 y 40x40 cm',
            '10000' => 'Entre 41x41 y 45x45 cm',
            '12000' => 'Entre 46x46 y 49x49 cm',
            'notificar' => 'Igual o mayor a 50 x 50 cm'
        ];
        $dimKey = $_POST['dimensiones_paquete'] ?? '';
        $datos['dimensiones'] = $dimensionesMap[$dimKey] ?? null;

        $datos['valor_recaudo'] = str_replace(['$', '.', ','], '', $datos['valor_recaudo'] ?? '0');
        $datos['costo_total'] = str_replace(['$', '.', ','], '', $datos['costo_total'] ?? '0');

        if ($envioModel->verificarGuia($datos['numero_guia'])) {
            throw new Exception("El número de guía {$datos['numero_guia']} ya existe en el sistema.");
        }

        if ($envioModel->registrarEnvio($datos)) {
            if ($isAjax) {
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode(['success' => true, 'guia' => $datos['numero_guia']]);
                exit();
            }
            redirect_route('client.send-package', ['msg' => 'envio_creado', 'guia' => $datos['numero_guia']]);
        }

        throw new Exception('Error desconocido al intentar guardar el envío.');
    } catch (Exception $e) {
        if (isset($isAjax) && $isAjax) {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
            exit();
        }
        redirect_route('client.send-package', ['error' => $e->getMessage()]);
    }
}

redirect_route('client.send-package');
?>
