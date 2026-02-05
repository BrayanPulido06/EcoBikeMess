<?php
require_once 'conexionGlobal.php';

class InicioAdminModel {
    private $conn;

    public function __construct() {
        $this->conn = conexionDB();
    }

    // Obtener contadores para las tarjetas superiores
    public function obtenerEstadisticasDia() {
        $hoy = date('Y-m-d');
        $ayer = date('Y-m-d', strtotime('-1 day'));
        
        $stats = [
            'paquetes_ingresados' => 0,
            'paquetes_ayer' => 0,
            'en_transito' => 0,
            'entregados' => 0,
            'entregados_ayer' => 0,
            'recolecciones_pendientes' => 0,
            'recolecciones_completadas' => 0,
            'mensajeros_activos' => 0,
            'ingresos_dia' => 0,
            'ingresos_ayer' => 0
        ];

        try {
            // 1. Paquetes Ingresados Hoy
            $sql = "SELECT COUNT(*) as total FROM paquetes WHERE DATE(fecha_creacion) = :hoy";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([':hoy' => $hoy]);
            $stats['paquetes_ingresados'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
            
            $stmt->execute([':hoy' => $ayer]);
            $stats['paquetes_ayer'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

            // 2. En Tránsito (Estado actual)
            $sql = "SELECT COUNT(*) as total FROM paquetes WHERE estado = 'en_transito'";
            $stmt = $this->conn->query($sql);
            $stats['en_transito'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

            // 3. Entregados Hoy (Asumiendo que fecha_entrega se actualiza al entregar)
            // Si no tienes columna fecha_entrega, usa fecha_creacion como fallback o ajusta según tu esquema
            $sql = "SELECT COUNT(*) as total FROM paquetes WHERE estado = 'entregado' AND (DATE(fecha_creacion) = :hoy)";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([':hoy' => $hoy]);
            $stats['entregados'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

            $stmt->execute([':hoy' => $ayer]);
            $stats['entregados_ayer'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

            // 4. Recolecciones (Si existe tabla recolecciones, sino usar paquetes con estado 'recoleccion')
            // Verificamos si la tabla existe para evitar errores fatales si aún no la has creado
            $checkTable = $this->conn->query("SHOW TABLES LIKE 'recolecciones'");
            if ($checkTable->rowCount() > 0) {
                $sql = "SELECT 
                            SUM(CASE WHEN estado = 'pendiente' THEN 1 ELSE 0 END) as pendientes,
                            SUM(CASE WHEN estado = 'completada' AND DATE(fecha_recoleccion) = :hoy THEN 1 ELSE 0 END) as completadas
                        FROM recolecciones";
                $stmt = $this->conn->prepare($sql);
                $stmt->execute([':hoy' => $hoy]);
                $recol = $stmt->fetch(PDO::FETCH_ASSOC);
                $stats['recolecciones_pendientes'] = $recol['pendientes'] ?? 0;
                $stats['recolecciones_completadas'] = $recol['completadas'] ?? 0;
            }

            // 5. Mensajeros Activos
            $sql = "SELECT COUNT(*) as total FROM mensajeros WHERE estado IN ('activo', 'en_ruta')";
            $stmt = $this->conn->query($sql);
            $stats['mensajeros_activos'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

            // 6. Ingresos del Día (Suma de costo_total de paquetes creados hoy)
            $sql = "SELECT SUM(costo_total) as total FROM paquetes WHERE DATE(fecha_creacion) = :hoy";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([':hoy' => $hoy]);
            $stats['ingresos_dia'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;

            $stmt->execute([':hoy' => $ayer]);
            $stats['ingresos_ayer'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;

        } catch (PDOException $e) {
            // En caso de error (ej. tabla no existe), retornamos los valores en 0
            error_log("Error en InicioAdminModel: " . $e->getMessage());
        }

        return $stats;
    }

    // Datos para el gráfico de Entregas por Hora
    public function obtenerEntregasPorHora() {
        $sql = "SELECT HOUR(fecha_creacion) as hora, COUNT(*) as cantidad 
                FROM paquetes 
                WHERE DATE(fecha_creacion) = CURDATE() 
                GROUP BY HOUR(fecha_creacion) 
                ORDER BY hora ASC";
        $stmt = $this->conn->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Datos para el gráfico de Estados
    public function obtenerEstadosPaquetes() {
        $sql = "SELECT estado, COUNT(*) as cantidad FROM paquetes GROUP BY estado";
        $stmt = $this->conn->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Datos para el gráfico de Rendimiento de Mensajeros (Top 5)
    public function obtenerRendimientoMensajeros() {
        $sql = "SELECT u.nombres, COUNT(p.id) as entregas 
                FROM paquetes p 
                JOIN mensajeros m ON p.mensajero_id = m.id 
                JOIN usuarios u ON m.usuario_id = u.id 
                WHERE p.estado = 'entregado' 
                GROUP BY p.mensajero_id 
                ORDER BY entregas DESC 
                LIMIT 5";
        $stmt = $this->conn->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Datos para el gráfico de Zonas
    public function obtenerZonasActividad() {
        // Verificar si la columna 'zona' existe para evitar errores SQL
        $checkCol = $this->conn->query("SHOW COLUMNS FROM `paquetes` LIKE 'zona'");
        if ($checkCol->rowCount() == 0) {
            // Si no existe columna zona, retornamos array vacío o datos dummy
            return [];
        }

        $sql = "SELECT zona, COUNT(*) as cantidad FROM paquetes GROUP BY zona";
        $stmt = $this->conn->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Datos para el gráfico de Movimientos (Filtrable)
    public function obtenerMovimientos($periodo) {
        $sql = "";
        switch($periodo) {
            case 'dia':
                // Agrupar por hora del día actual
                $sql = "SELECT HOUR(fecha_creacion) as label, COUNT(*) as cantidad 
                        FROM paquetes 
                        WHERE DATE(fecha_creacion) = CURDATE() 
                        GROUP BY HOUR(fecha_creacion) ORDER BY label";
                break;
            case 'semana':
                // Agrupar por día de la semana actual
                $sql = "SELECT DATE(fecha_creacion) as label, COUNT(*) as cantidad 
                        FROM paquetes 
                        WHERE YEARWEEK(fecha_creacion, 1) = YEARWEEK(CURDATE(), 1) 
                        GROUP BY DATE(fecha_creacion) ORDER BY label";
                break;
            case 'mes':
                // Agrupar por día del mes actual
                $sql = "SELECT DAY(fecha_creacion) as label, COUNT(*) as cantidad 
                        FROM paquetes 
                        WHERE MONTH(fecha_creacion) = MONTH(CURDATE()) AND YEAR(fecha_creacion) = YEAR(CURDATE())
                        GROUP BY DAY(fecha_creacion) ORDER BY label";
                break;
            case 'anio':
                // Agrupar por mes del año actual
                $sql = "SELECT MONTH(fecha_creacion) as label, COUNT(*) as cantidad 
                        FROM paquetes 
                        WHERE YEAR(fecha_creacion) = YEAR(CURDATE())
                        GROUP BY MONTH(fecha_creacion) ORDER BY label";
                break;
        }
        
        if ($sql) {
            $stmt = $this->conn->query($sql);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
        return [];
    }
}
?>
