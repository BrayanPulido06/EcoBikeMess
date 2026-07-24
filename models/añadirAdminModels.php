<?php
require_once 'conexionGlobal.php';

class AñadirAdminModel {
    private $conn;

    public function __construct() {
        $this->conn = conexionDB();
        $this->ensureClienteBankColumns();
    }

    private function ensureColumn(string $table, string $column, string $alterSql): void {
        $stmt = $this->conn->prepare("SHOW COLUMNS FROM {$table} LIKE :column_name");
        $stmt->execute([':column_name' => $column]);
        if (!$stmt->fetch(PDO::FETCH_ASSOC)) {
            $this->conn->exec($alterSql);
        }
    }

    private function ensureClienteBankColumns(): void {
        $this->ensureColumn('clientes', 'cuenta_bancaria_principal', "ALTER TABLE clientes ADD COLUMN cuenta_bancaria_principal TEXT NULL AFTER direccion_principal");
        $this->ensureColumn('clientes', 'cuenta_bancaria_opcional_1', "ALTER TABLE clientes ADD COLUMN cuenta_bancaria_opcional_1 TEXT NULL AFTER cuenta_bancaria_principal");
        $this->ensureColumn('clientes', 'cuenta_bancaria_opcional_2', "ALTER TABLE clientes ADD COLUMN cuenta_bancaria_opcional_2 TEXT NULL AFTER cuenta_bancaria_opcional_1");
        $this->ensureColumn('clientes', 'cuenta_bancaria_opcional_3', "ALTER TABLE clientes ADD COLUMN cuenta_bancaria_opcional_3 TEXT NULL AFTER cuenta_bancaria_opcional_2");
    }

    // --- ADMINISTRADORES ---

    public function getAdministradores() {
        $sql = "SELECT a.id as admin_id, u.id as usuario_id, 
                       CONCAT(u.nombres, ' ', u.apellidos) as nombre, 
                       u.correo as email, u.telefono, 
                       u.estado, u.fecha_creacion, u.ultimo_acceso, 
                       a.rol, a.foto, a.permisos_especiales
                FROM administradores a
                INNER JOIN usuarios u ON a.usuario_id = u.id
                ORDER BY u.fecha_creacion DESC";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function crearAdministrador($datos) {
        try {
            $this->conn->beginTransaction();

            // 1. Crear Usuario
            $sqlUser = "INSERT INTO usuarios (nombres, apellidos, correo, telefono, password, tipo_usuario, estado, correo_verificado) 
                        VALUES (:nombres, '', :correo, :telefono, :password, 'administrador', :estado, 1)";
            $stmtUser = $this->conn->prepare($sqlUser);
            
            // Hashear contraseña
            $hashedPassword = password_hash($datos['password'], PASSWORD_DEFAULT);
            
            $stmtUser->execute([
                ':nombres' => $datos['nombre'], // Asumiendo que el form envía nombre completo, se podría separar si se requiere
                ':correo' => $datos['email'],
                ':telefono' => $datos['telefono'],
                ':password' => $hashedPassword,
                ':estado' => $datos['estado']
            ]);
            
            $usuarioId = $this->conn->lastInsertId();

            // 2. Crear registro en Administradores
            $sqlAdmin = "INSERT INTO administradores (usuario_id, tipo_documento, num_documento, rol, foto, permisos_especiales) 
                         VALUES (:usuario_id, 'cedula', :num_documento, :rol, '', :permisos)";
            $stmtAdmin = $this->conn->prepare($sqlAdmin);
            
            // Generar un número de documento temporal si no se pide en el formulario, o usar uno real
            $numDoc = 'ADM-' . time(); 
            
            $stmtAdmin->execute([
                ':usuario_id' => $usuarioId,
                ':num_documento' => $numDoc,
                ':rol' => $datos['rol'],
                ':permisos' => json_encode($datos['permisos'])
            ]);

            $this->conn->commit();
            return true;
        } catch (Exception $e) {
            $this->conn->rollBack();
            throw $e;
        }
    }

    public function actualizarAdministrador($id, $datos) {
        try {
            $this->conn->beginTransaction();

            // Obtener usuario_id desde la tabla administradores
            $stmtGet = $this->conn->prepare("SELECT usuario_id FROM administradores WHERE id = :id");
            $stmtGet->execute([':id' => $id]);
            $admin = $stmtGet->fetch(PDO::FETCH_ASSOC);
            
            if (!$admin) throw new Exception("Administrador no encontrado");

            // Actualizar Usuario
            $sqlUser = "UPDATE usuarios SET nombres = :nombres, correo = :correo, telefono = :telefono, estado = :estado WHERE id = :uid";
            $stmtUser = $this->conn->prepare($sqlUser);
            $stmtUser->execute([
                ':nombres' => $datos['nombre'],
                ':correo' => $datos['email'],
                ':telefono' => $datos['telefono'],
                ':estado' => $datos['estado'],
                ':uid' => $admin['usuario_id']
            ]);

            // Actualizar Rol y Permisos
            $sqlAdmin = "UPDATE administradores SET rol = :rol, permisos_especiales = :permisos WHERE id = :id";
            $stmtAdmin = $this->conn->prepare($sqlAdmin);
            $stmtAdmin->execute([
                ':rol' => $datos['rol'],
                ':permisos' => json_encode($datos['permisos']),
                ':id' => $id
            ]);

            $this->conn->commit();
            return true;
        } catch (Exception $e) {
            $this->conn->rollBack();
            throw $e;
        }
    }

    public function eliminarAdministrador($id) {
        // Al borrar el usuario, el ON DELETE CASCADE de la BD debería borrar el administrador
        $stmtGet = $this->conn->prepare("SELECT usuario_id FROM administradores WHERE id = :id");
        $stmtGet->execute([':id' => $id]);
        $admin = $stmtGet->fetch(PDO::FETCH_ASSOC);

        if ($admin) {
            $stmtDel = $this->conn->prepare("DELETE FROM usuarios WHERE id = :uid");
            return $stmtDel->execute([':uid' => $admin['usuario_id']]);
        }
        return false;
    }

    public function cambiarEstadoUsuario($adminId, $nuevoEstado) {
        $stmtGet = $this->conn->prepare("SELECT usuario_id FROM administradores WHERE id = :id");
        $stmtGet->execute([':id' => $adminId]);
        $admin = $stmtGet->fetch(PDO::FETCH_ASSOC);

        if ($admin) {
            $stmt = $this->conn->prepare("UPDATE usuarios SET estado = :estado WHERE id = :uid");
            return $stmt->execute([':estado' => $nuevoEstado, ':uid' => $admin['usuario_id']]);
        }
        return false;
    }

    // --- MENSAJEROS ---

    public function getMensajeros() {
        $sql = "SELECT m.id, m.usuario_id,
                       CONCAT(u.nombres, ' ', u.apellidos) as nombre,
                       u.nombres, u.apellidos,
                       u.telefono, u.correo as email, u.estado,
                       m.tipo_documento, m.numDocumento, m.foto,
                       m.tipo_sangre, m.direccion_residencia,
                       m.tipo_transporte, m.placa_vehiculo,
                       m.licencia_conducir, m.soat, m.tecnomecanica,
                       m.estado_aprobacion, m.total_entregas,
                       m.nombre_emergencia1, m.apellido_emergencia1, m.telefono_emergencia1,
                       m.nombre_emergencia2, m.apellido_emergencia2, m.telefono_emergencia2,
                       m.ubicacion_actual_lat, m.ubicacion_actual_lng,
                       m.calificacion_promedio as rendimiento,
                       u.fecha_creacion as fechaRegistro,
                       (SELECT COUNT(*) FROM paquetes p WHERE p.mensajero_id = m.id AND p.estado IN ('asignado', 'en_transito', 'en_ruta')) as paquetesAsignados,
                       (SELECT COUNT(*) FROM entregas e WHERE e.mensajero_id = m.id AND DATE(e.fecha_entrega) = CURDATE()) as entregasHoy
                FROM mensajeros m
                INNER JOIN usuarios u ON m.usuario_id = u.id
                ORDER BY u.nombres ASC";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // --- CLIENTES ---

    public function getClientes() {
        $sql = "SELECT c.id, c.usuario_id, c.nombre_emprendimiento as emprendimiento,
                       CONCAT(u.nombres, ' ', u.apellidos) as nombreContacto,
                       u.nombres, u.apellidos,
                       u.telefono, u.correo as email, c.direccion_principal as direccion,
                       c.tipo_producto as tipoProducto, c.instagram,
                       c.cuenta_bancaria_principal as cuentaBancariaPrincipal,
                       c.cuenta_bancaria_opcional_1 as cuentaBancariaOpcional1,
                       c.cuenta_bancaria_opcional_2 as cuentaBancariaOpcional2,
                       c.cuenta_bancaria_opcional_3 as cuentaBancariaOpcional3,
                       c.saldo_pendiente as saldoPendiente,
                       c.limite_credito as limiteCredito,
                       u.estado, c.fecha_registro as fechaRegistro
                FROM clientes c
                INNER JOIN usuarios u ON c.usuario_id = u.id
                ORDER BY c.fecha_registro DESC";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getClienteById($id) {
        $sql = "SELECT c.id, c.usuario_id,
                       c.nombre_emprendimiento as emprendimiento,
                       CONCAT(u.nombres, ' ', u.apellidos) as nombreContacto,
                       u.nombres, u.apellidos,
                       u.telefono, u.correo as email,
                       c.direccion_principal as direccion,
                       c.tipo_producto as tipoProducto,
                       c.instagram,
                       c.cuenta_bancaria_principal as cuentaBancariaPrincipal,
                       c.cuenta_bancaria_opcional_1 as cuentaBancariaOpcional1,
                       c.cuenta_bancaria_opcional_2 as cuentaBancariaOpcional2,
                       c.cuenta_bancaria_opcional_3 as cuentaBancariaOpcional3,
                       c.saldo_pendiente as saldoPendiente,
                       c.limite_credito as limiteCredito,
                       u.estado,
                       c.fecha_registro as fechaRegistro
                FROM clientes c
                INNER JOIN usuarios u ON c.usuario_id = u.id
                WHERE c.id = :id
                LIMIT 1";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([':id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function actualizarClienteDatosPersonales($clienteId, $datos) {
        $sql = "UPDATE usuarios u
                INNER JOIN clientes c ON c.usuario_id = u.id
                SET u.nombres = :nombres,
                    u.apellidos = :apellidos,
                    u.telefono = :telefono,
                    c.cuenta_bancaria_principal = :cuenta_principal,
                    c.cuenta_bancaria_opcional_1 = :cuenta_opcional_1,
                    c.cuenta_bancaria_opcional_2 = :cuenta_opcional_2,
                    c.cuenta_bancaria_opcional_3 = :cuenta_opcional_3
                WHERE c.id = :cliente_id";
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute([
            ':nombres' => $datos['nombres'],
            ':apellidos' => $datos['apellidos'],
            ':telefono' => $datos['telefono'],
            ':cuenta_principal' => $datos['cuenta_bancaria_principal'],
            ':cuenta_opcional_1' => $datos['cuenta_bancaria_opcional_1'],
            ':cuenta_opcional_2' => $datos['cuenta_bancaria_opcional_2'],
            ':cuenta_opcional_3' => $datos['cuenta_bancaria_opcional_3'],
            ':cliente_id' => $clienteId
        ]);
    }
}
?>
