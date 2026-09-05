<?php
require_once __DIR__ . '/conexionGlobal.php';

class NotasAdminModels
{
    private PDO $conn;

    public function __construct()
    {
        $this->conn = conexionDB();
        $this->ensureTables();
    }

    private function ensureTables(): void
    {
        $this->conn->exec("CREATE TABLE IF NOT EXISTS notas_admin_listas (
            id INT PRIMARY KEY AUTO_INCREMENT,
            titulo VARCHAR(160) NOT NULL,
            posicion INT NOT NULL DEFAULT 0,
            creado_por INT NULL,
            fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            fecha_actualizacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (creado_por) REFERENCES usuarios(id) ON DELETE SET NULL,
            INDEX idx_notas_admin_listas_posicion (posicion, id)
        )");

        $this->conn->exec("CREATE TABLE IF NOT EXISTS notas_admin_tarjetas (
            id INT PRIMARY KEY AUTO_INCREMENT,
            lista_id INT NOT NULL,
            titulo VARCHAR(180) NOT NULL,
            descripcion TEXT NULL,
            completada TINYINT(1) NOT NULL DEFAULT 0,
            posicion INT NOT NULL DEFAULT 0,
            creado_por INT NULL,
            fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            fecha_actualizacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (lista_id) REFERENCES notas_admin_listas(id) ON DELETE CASCADE,
            FOREIGN KEY (creado_por) REFERENCES usuarios(id) ON DELETE SET NULL,
            INDEX idx_notas_admin_tarjetas_lista (lista_id, posicion, id)
        )");
    }

    public function obtenerTablero(): array
    {
        $listasStmt = $this->conn->query("SELECT id, titulo, posicion, fecha_creacion, fecha_actualizacion
            FROM notas_admin_listas
            ORDER BY posicion ASC, id ASC");
        $listas = $listasStmt ? $listasStmt->fetchAll(PDO::FETCH_ASSOC) : [];

        $tarjetasStmt = $this->conn->query("SELECT id, lista_id, titulo, descripcion, completada, posicion, fecha_creacion, fecha_actualizacion
            FROM notas_admin_tarjetas
            ORDER BY posicion ASC, id ASC");
        $tarjetas = $tarjetasStmt ? $tarjetasStmt->fetchAll(PDO::FETCH_ASSOC) : [];

        $tarjetasPorLista = [];
        foreach ($tarjetas as $tarjeta) {
            $listaId = (int) $tarjeta['lista_id'];
            if (!isset($tarjetasPorLista[$listaId])) {
                $tarjetasPorLista[$listaId] = [];
            }
            $tarjetasPorLista[$listaId][] = [
                'id' => (int) $tarjeta['id'],
                'lista_id' => $listaId,
                'titulo' => (string) $tarjeta['titulo'],
                'descripcion' => (string) ($tarjeta['descripcion'] ?? ''),
                'completada' => (bool) $tarjeta['completada'],
                'posicion' => (int) $tarjeta['posicion'],
                'fecha_creacion' => (string) $tarjeta['fecha_creacion'],
                'fecha_actualizacion' => (string) $tarjeta['fecha_actualizacion'],
            ];
        }

        return [
            'listas' => array_map(function ($lista) use ($tarjetasPorLista) {
                $id = (int) $lista['id'];
                return [
                    'id' => $id,
                    'titulo' => (string) $lista['titulo'],
                    'posicion' => (int) $lista['posicion'],
                    'tarjetas' => $tarjetasPorLista[$id] ?? [],
                    'fecha_creacion' => (string) $lista['fecha_creacion'],
                    'fecha_actualizacion' => (string) $lista['fecha_actualizacion'],
                ];
            }, $listas),
        ];
    }

    public function crearLista(string $titulo, ?int $creadoPor): int
    {
        $posicion = $this->siguientePosicionLista();
        $stmt = $this->conn->prepare("INSERT INTO notas_admin_listas (titulo, posicion, creado_por)
            VALUES (:titulo, :posicion, :creado_por)");
        $stmt->execute([
            ':titulo' => $titulo,
            ':posicion' => $posicion,
            ':creado_por' => $creadoPor,
        ]);

        return (int) $this->conn->lastInsertId();
    }

    public function actualizarLista(int $listaId, string $titulo): bool
    {
        $stmt = $this->conn->prepare("UPDATE notas_admin_listas SET titulo = :titulo WHERE id = :id");
        return $stmt->execute([
            ':titulo' => $titulo,
            ':id' => $listaId,
        ]);
    }

    public function eliminarLista(int $listaId): bool
    {
        $stmt = $this->conn->prepare("DELETE FROM notas_admin_listas WHERE id = :id");
        return $stmt->execute([':id' => $listaId]);
    }

    public function crearTarjeta(int $listaId, string $titulo, string $descripcion, ?int $creadoPor): int
    {
        $this->asegurarListaExiste($listaId);
        $posicion = $this->siguientePosicionTarjeta($listaId);
        $stmt = $this->conn->prepare("INSERT INTO notas_admin_tarjetas (lista_id, titulo, descripcion, posicion, creado_por)
            VALUES (:lista_id, :titulo, :descripcion, :posicion, :creado_por)");
        $stmt->execute([
            ':lista_id' => $listaId,
            ':titulo' => $titulo,
            ':descripcion' => $descripcion !== '' ? $descripcion : null,
            ':posicion' => $posicion,
            ':creado_por' => $creadoPor,
        ]);

        return (int) $this->conn->lastInsertId();
    }

    public function actualizarTarjeta(int $tarjetaId, string $titulo, string $descripcion): bool
    {
        $stmt = $this->conn->prepare("UPDATE notas_admin_tarjetas
            SET titulo = :titulo, descripcion = :descripcion
            WHERE id = :id");
        return $stmt->execute([
            ':titulo' => $titulo,
            ':descripcion' => $descripcion !== '' ? $descripcion : null,
            ':id' => $tarjetaId,
        ]);
    }

    public function cambiarEstadoTarjeta(int $tarjetaId, bool $completada): bool
    {
        $stmt = $this->conn->prepare("UPDATE notas_admin_tarjetas SET completada = :completada WHERE id = :id");
        return $stmt->execute([
            ':completada' => $completada ? 1 : 0,
            ':id' => $tarjetaId,
        ]);
    }

    public function eliminarTarjeta(int $tarjetaId): bool
    {
        $stmt = $this->conn->prepare("DELETE FROM notas_admin_tarjetas WHERE id = :id");
        return $stmt->execute([':id' => $tarjetaId]);
    }

    private function siguientePosicionLista(): int
    {
        $stmt = $this->conn->query("SELECT COALESCE(MAX(posicion), 0) + 1 AS siguiente FROM notas_admin_listas");
        $row = $stmt ? $stmt->fetch(PDO::FETCH_ASSOC) : null;
        return (int) ($row['siguiente'] ?? 1);
    }

    private function siguientePosicionTarjeta(int $listaId): int
    {
        $stmt = $this->conn->prepare("SELECT COALESCE(MAX(posicion), 0) + 1 AS siguiente
            FROM notas_admin_tarjetas
            WHERE lista_id = :lista_id");
        $stmt->execute([':lista_id' => $listaId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return (int) ($row['siguiente'] ?? 1);
    }

    private function asegurarListaExiste(int $listaId): void
    {
        $stmt = $this->conn->prepare("SELECT id FROM notas_admin_listas WHERE id = :id LIMIT 1");
        $stmt->execute([':id' => $listaId]);
        if (!$stmt->fetch(PDO::FETCH_ASSOC)) {
            throw new InvalidArgumentException('La lista no existe.');
        }
    }
}
