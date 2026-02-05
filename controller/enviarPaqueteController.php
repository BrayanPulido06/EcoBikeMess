<?php
session_start();

// Incluir los modelos necesarios
require_once '../models/inicioClienteModel.php';
require_once '../models/enviarPaqueteModels.php';

// 1. Verificar seguridad de la sesión
if (!isset($_SESSION['user_id'])) {
    header("Location: ../views/login.php");
    exit();
}

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