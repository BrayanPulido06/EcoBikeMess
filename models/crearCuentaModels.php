<?php
require_once __DIR__ . '/conexionGlobal.php';

class UsuarioModel {
    private $conn;

    public function __construct() {
        $this->conn = conexionDB();
    }

    public function existeCorreo($correo) {
        $sql = "SELECT id FROM usuarios WHERE correo = :correo";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':correo', $correo);
        $stmt->execute();
        return $stmt->fetch();
    }

    public function registrarUsuario($datos) {
        try {
            // Iniciar transacción para asegurar que se guarden ambas partes (usuario + detalle)
            $this->conn->beginTransaction();

            // 1. Insertar en tabla usuarios (datos comunes)
            $sqlUser = "INSERT INTO usuarios (nombres, apellidos, correo, telefono, password, tipo_usuario, estado) 
                        VALUES (:nombres, :apellidos, :correo, :telefono, :password, :tipo_usuario, :estado)";
            
            $stmtUser = $this->conn->prepare($sqlUser);
            $stmtUser->execute([
                ':nombres' => $datos['nombres'],
                ':apellidos' => $datos['apellidos'],
                ':correo' => $datos['correo'],
                ':telefono' => $datos['telefono'],
                ':password' => password_hash($datos['password'], PASSWORD_DEFAULT), // Encriptar contraseña
                ':tipo_usuario' => $datos['tipo_usuario'],
                ':estado' => ($datos['tipo_usuario'] == 'mensajero') ? 'pendiente' : 'activo'
            ]);
            
            $usuarioId = $this->conn->lastInsertId();

            // 2. Insertar detalles según tipo
            if ($datos['tipo_usuario'] == 'cliente') {
                $sqlCliente = "INSERT INTO clientes (usuario_id, nombre_emprendimiento, tipo_producto, instagram, direccion_principal) 
                               VALUES (:uid, :emp, :prod, :insta, :dir)";
                $stmtCliente = $this->conn->prepare($sqlCliente);
                $stmtCliente->execute([
                    ':uid' => $usuarioId,
                    ':emp' => $datos['nombre_emprendimiento'],
                    ':prod' => $datos['tipo_producto'],
                    ':insta' => $datos['instagram'] ?? null,
                    ':dir' => $datos['direccion_principal'] ?? null
                ]);
            } elseif ($datos['tipo_usuario'] == 'mensajero') {
                // Se ajustaron los nombres de columnas para coincidir con ecobikemess.sql
                $sqlMensajero = "INSERT INTO mensajeros (
                    usuario_id, tipo_documento, numDocumento, tipo_sangre, direccion_residencia, 
                    foto, hoja_vida, 
                    nombre_emergencia1, apellido_emergencia1, telefono_emergencia1,
                    nombre_emergencia2, apellido_emergencia2, telefono_emergencia2,
                    tipo_transporte, placa_vehiculo, licencia_conducir, soat
                ) VALUES (
                    :uid, :tdoc, :ndoc, :tsangre, :dir, 
                    :foto, :hv, 
                    :nom1, :ape1, :tel1,
                    :nom2, :ape2, :tel2,
                    :transporte, :placa, :licencia, :soat
                )";

                $stmtMensajero = $this->conn->prepare($sqlMensajero);
                $stmtMensajero->execute([
                    ':uid' => $usuarioId,
                    ':tdoc' => $datos['tipo_documento'],
                    ':ndoc' => $datos['numDocumento'],
                    ':tsangre' => $datos['tipo_sangre'],
                    ':dir' => $datos['direccion_residencia'],
                    ':foto' => $datos['rutas_archivos']['foto'] ?? null,
                    ':hv' => $datos['rutas_archivos']['hoja_vida'] ?? null,
                    
                    // Contacto Emergencia 1
                    ':nom1' => $datos['emergencia']['contacto1']['nombre'],
                    ':ape1' => $datos['emergencia']['contacto1']['apellido'],
                    ':tel1' => $datos['emergencia']['contacto1']['telefono'],
                    
                    // Contacto Emergencia 2
                    ':nom2' => $datos['emergencia']['contacto2']['nombre'],
                    ':ape2' => $datos['emergencia']['contacto2']['apellido'],
                    ':tel2' => $datos['emergencia']['contacto2']['telefono'],
                    
                    // Transporte
                    ':transporte' => $datos['transporte']['tipo'],
                    ':placa' => $datos['transporte']['placa'] ?? null,
                    ':licencia' => $datos['rutas_archivos']['licencia_conducir'] ?? null,
                    ':soat' => $datos['rutas_archivos']['soat'] ?? null
                ]);
            }

            $this->conn->commit();
            return true;

        } catch (Exception $e) {
            $this->conn->rollBack();
            throw $e;
        }
    }
}
