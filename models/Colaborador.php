<?php
require_once 'conexionGlobal.php';

class Colaborador {
    private $conn;

    public function __construct() {
        $this->conn = conexionDB();
    }

    // Obtener todos los colaboradores de un cliente
    public function listar($cliente_id) {
        try {
            $sql = "SELECT * FROM vista_colaboradores_completa WHERE cliente_id = ? ORDER BY fecha_creacion DESC";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([$cliente_id]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return [];
        }
    }

    // Crear un nuevo colaborador usando el Procedimiento Almacenado
    public function crear($datos) {
        try {
            // 1. Verificar manualmente si el correo ya existe antes de llamar al SP
            $checkSql = "SELECT id FROM usuarios WHERE correo = ?";
            $checkStmt = $this->conn->prepare($checkSql);
            $checkStmt->execute([$datos['correo']]);
            if ($checkStmt->fetch()) {
                return false; // El correo ya existe
            }

            // 2. Verificar que el cliente exista (Foreign Key Check)
            $checkCliente = $this->conn->prepare("SELECT id FROM clientes WHERE id = ?");
            $checkCliente->execute([$datos['cliente_id']]);
            if (!$checkCliente->fetch()) {
                return false; // El cliente no existe
            }

            $sql = "CALL crear_colaborador(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, @usuario_id, @colaborador_id)";
            $stmt = $this->conn->prepare($sql);
            
            // Definir permisos de forma segura para evitar errores si no vienen marcados
            $permisos = $datos['permisos'] ?? [];

            $stmt->execute([
                $datos['cliente_id'],
                $datos['nombres'],
                $datos['apellidos'],
                $datos['correo'],
                $datos['telefono'],
                $datos['password'], // En un caso real, esto debería hashearse si el SP no lo hace
                $datos['cargo'],
                $permisos['crear_paquetes'] ?? 0,
                $permisos['ver_facturas'] ?? 0,
                $permisos['ver_comprobantes'] ?? 0,
                $permisos['gestionar_recolecciones'] ?? 0,
                $permisos['ver_reportes'] ?? 0,
                $permisos['editar_perfil'] ?? 0,
                $permisos['agregar_colaboradores'] ?? 0,
                $datos['creado_por']
            ]);

            // IMPORTANTE: Liberar el cursor del SP para poder ejecutar la siguiente consulta
            $stmt->closeCursor();

            // Obtener los IDs generados
            $res = $this->conn->query("SELECT @usuario_id as uid, @colaborador_id as cid")->fetch(PDO::FETCH_ASSOC);

            // Validar si el SP devolvió IDs nulos (fallo silencioso, ej: correo duplicado)
            if (!$res || empty($res['uid'])) {
                return false;
            }

            return $res;

        } catch (PDOException $e) {
            error_log("Error en Colaborador::crear: " . $e->getMessage());
            return false;
        }
    }

    // Obtener historial de actividad (Auditoría)
    public function obtenerHistorial($cliente_id) {
        try {
            $sql = "SELECT ac.*, u.nombres, u.apellidos, cc.cargo 
                    FROM auditoria_colaboradores ac
                    JOIN colaboradores_cliente cc ON ac.colaborador_id = cc.id
                    JOIN usuarios u ON cc.usuario_id = u.id
                    WHERE ac.cliente_id = ? 
                    ORDER BY ac.fecha_accion DESC LIMIT 50";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([$cliente_id]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return [];
        }
    }

    // Cambiar estado (Activo/Inactivo)
    public function cambiarEstado($colaborador_id, $estado) {
        try {
            $sql = "UPDATE colaboradores_cliente SET estado = ? WHERE id = ?";
            $stmt = $this->conn->prepare($sql);
            return $stmt->execute([$estado, $colaborador_id]);
        } catch (PDOException $e) {
            return false;
        }
    }

    // Obtener el ID del cliente basado en el usuario logueado
    public function obtenerClienteId($usuario_id) {
        try {
            // 1. Intentar buscar como Cliente (Dueño)
            $sql = "SELECT id FROM clientes WHERE usuario_id = ?";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([$usuario_id]);
            $id = $stmt->fetchColumn();
            
            if ($id) return $id;

            // 2. Si no es dueño, buscar como Colaborador
            $sql = "SELECT cliente_id FROM colaboradores_cliente WHERE usuario_id = ?";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([$usuario_id]);
            return $stmt->fetchColumn();
        } catch (PDOException $e) {
            return null;
        }
    }
}
?>
