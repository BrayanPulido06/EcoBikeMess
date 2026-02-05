<?php
require_once __DIR__ . '/conexionGlobal.php';

class EnvioModel {
    private $conn;

    public function __construct() {
        $this->conn = conexionDB();
    }

    // Verificar si el número de guía ya existe para evitar duplicados
    public function verificarGuia($numero_guia) {
        $sql = "SELECT COUNT(*) FROM paquetes WHERE numero_guia = :numero_guia";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([':numero_guia' => $numero_guia]);
        return $stmt->fetchColumn() > 0;
    }

    public function registrarEnvio($datos) {
        if (empty($datos['cliente_id'])) {
            throw new Exception("Error de validación: No se ha identificado el ID del cliente para este envío.");
        }

        // Determinar el tipo de servicio basado en si hay recaudo
        $tipo_servicio = (!empty($datos['tiene_recaudo']) && $datos['tiene_recaudo'] == 1) ? 'contraentrega' : 'entrega_simple';

        try {
            $sql = "INSERT INTO paquetes (
                        cliente_id, 
                        creado_por,
                        numero_guia, 
                        remitente_nombre, 
                        remitente_telefono, 
                        remitente_correo, 
                        direccion_origen,
                        destinatario_nombre, 
                        destinatario_telefono, 
                        direccion_destino, 
                        instrucciones_entrega,
                        descripcion_contenido, 
                        peso, 
                        tipo_paquete,
                        largo, 
                        ancho, 
                        alto,
                        tipo_servicio, 
                        recaudo_esperado, 
                        costo_envio, 
                        estado,
                        fecha_creacion
                    ) VALUES (
                        :cliente_id, 
                        :creado_por,
                        :numero_guia,
                        :remitente_nombre, :remitente_telefono, :remitente_email, :remitente_direccion,
                        :destinatario_nombre, :destinatario_telefono, :destinatario_direccion, :instrucciones_entrega,
                        :descripcion_contenido, :peso_paquete, :tipo_paquete,
                        :dimension_largo, :dimension_ancho, :dimension_alto,
                        :tipo_servicio, :valor_recaudo, :costo_total, 'pendiente', NOW()
                    )";
        
            $stmt = $this->conn->prepare($sql);
            
            return $stmt->execute([
                ':cliente_id' => $datos['cliente_id'],
                ':creado_por' => $datos['creado_por'],
                ':numero_guia' => $datos['numero_guia'],
                ':remitente_nombre' => $datos['remitente_nombre'],
                ':remitente_telefono' => $datos['remitente_telefono'],
                ':remitente_email' => $datos['remitente_email'],
                ':remitente_direccion' => $datos['remitente_direccion'],
                ':destinatario_nombre' => $datos['destinatario_nombre'],
                ':destinatario_telefono' => $datos['destinatario_telefono'],
                ':destinatario_direccion' => $datos['destinatario_direccion'],
                ':instrucciones_entrega' => $datos['instrucciones_entrega'],
                ':descripcion_contenido' => $datos['descripcion_contenido'],
                ':peso_paquete' => $datos['peso_paquete'],
                ':tipo_paquete' => $datos['tipo_paquete'],
                ':dimension_largo' => $datos['dimension_largo'],
                ':dimension_ancho' => $datos['dimension_ancho'],
                ':dimension_alto' => $datos['dimension_alto'],
                ':tipo_servicio' => $tipo_servicio,
                ':valor_recaudo' => $datos['valor_recaudo'],
                ':costo_total' => $datos['costo_total']
            ]);

        } catch (PDOException $e) {
            // Log del error para el desarrollador
            error_log("Error en EnvioModel::registrarEnvio: " . $e->getMessage());
            // Lanzar excepción genérica para el controlador
            throw new Exception("Error al guardar el envío en la base de datos: " . $e->getMessage());
        }
    }
}
?>
