<?php
require_once __DIR__ . '/../includes/auth.php';
requireWebAuth(['cliente', 'colaborador'], '../views/login.php?error=Debes iniciar sesión.');

// Incluir los modelos necesarios
require_once '../models/inicioClienteModel.php';
require_once '../models/enviarPaqueteModels.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Instanciar modelos
        $inicioModel = new InicioClienteModel();
        $envioModel = new EnvioModel();

        // 2. Obtener datos de la sesión actual
        // Usamos las variables de sesión que definiste en tu vista (user_id, user_role)
        $usuario_id = $_SESSION['user_id'];
        $rol = $_SESSION['user_role'] ?? 'cliente';

        // 3. Obtener el ID del Cliente (Tienda)
        // Esta función del modelo ya maneja la lógica:
        // - Si es 'cliente', busca su ID en la tabla clientes.
        // - Si es 'colaborador', busca a qué cliente pertenece en la tabla colaboradores_cliente.
        $cliente_id = $inicioModel->obtenerIdCliente($usuario_id, $rol);

        if (!$cliente_id) {
            throw new Exception("Error crítico: No se pudo identificar la cuenta de la tienda asociada a este usuario.");
        }

        // 4. Preparar los datos para el modelo
        $datos = $_POST;
        $datos['cliente_id'] = $cliente_id; // <--- AQUÍ SE SOLUCIONA EL ERROR NULL
        $datos['creado_por'] = $usuario_id; // Agregamos el ID del usuario creador para cumplir la FK
        
        // Asegurar manejo correcto de checkboxes y booleanos
        $datos['tiene_recaudo'] = isset($_POST['tiene_recaudo']) ? 1 : 0;
        $datos['tiene_cambios'] = isset($_POST['recoger_cambios']) ? 1 : 0;

        // Si el envío tiene recaudo, se espera el valor de 'envio_destinatario'. Si no, se asume 'no'.
        // Esto corresponde a la pregunta de si el costo del envío se suma al recaudo.
        $datos['envio_destinatario'] = $_POST['envio_destinatario'] ?? 'no';

        // Guardar dimensiones en formato legible según la opción seleccionada
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

        // Limpieza de moneda (por si el JS envía '$ 10.000')
        $datos['valor_recaudo'] = str_replace(['$', '.', ','], '', $datos['valor_recaudo'] ?? '0');
        $datos['costo_total'] = str_replace(['$', '.', ','], '', $datos['costo_total'] ?? '0');

        // 5. Validar que la guía no exista ya (evitar duplicados)
        if ($envioModel->verificarGuia($datos['numero_guia'])) {
            throw new Exception("El número de guía {$datos['numero_guia']} ya existe en el sistema.");
        }

        // 6. Guardar en base de datos
        if ($envioModel->registrarEnvio($datos)) {
            // Redirigir con éxito
            header("Location: ../views/Clientes/enviarPaquete.php?msg=envio_creado&guia=" . urlencode($datos['numero_guia']));
            exit();
        } else {
            throw new Exception("Error desconocido al intentar guardar el envío.");
        }

    } catch (Exception $e) {
        // Redirigir con el mensaje de error para mostrarlo en la alerta roja
        header("Location: ../views/Clientes/enviarPaquete.php?error=" . urlencode($e->getMessage()));
        exit();
    }
} else {
    // Si intentan acceder directo al archivo sin enviar formulario
    header("Location: ../views/Clientes/enviarPaquete.php");
    exit();
}
?>
