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
            permisos_configurados TINYINT(1) NOT NULL DEFAULT 0,
            fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            fecha_actualizacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (creado_por) REFERENCES usuarios(id) ON DELETE SET NULL,
            INDEX idx_notas_admin_listas_posicion (posicion, id)
        )");
        $this->ensureColumn('notas_admin_listas', 'permisos_configurados', "ALTER TABLE notas_admin_listas ADD COLUMN permisos_configurados TINYINT(1) NOT NULL DEFAULT 0 AFTER creado_por");

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

        $this->conn->exec("CREATE TABLE IF NOT EXISTS notas_admin_lista_permisos (
            id INT PRIMARY KEY AUTO_INCREMENT,
            lista_id INT NOT NULL,
            usuario_id INT NOT NULL,
            asignado_por INT NULL,
            fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY uniq_notas_lista_usuario (lista_id, usuario_id),
            FOREIGN KEY (lista_id) REFERENCES notas_admin_listas(id) ON DELETE CASCADE,
            FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
            FOREIGN KEY (asignado_por) REFERENCES usuarios(id) ON DELETE SET NULL,
            INDEX idx_notas_lista_permisos_usuario (usuario_id, lista_id)
        )");
    }

    private function ensureColumn(string $table, string $column, string $alterSql): void
    {
        $stmt = $this->conn->prepare("SHOW COLUMNS FROM {$table} LIKE :column_name");
        $stmt->execute([':column_name' => $column]);
        if (!$stmt->fetch(PDO::FETCH_ASSOC)) {
            $this->conn->exec($alterSql);
        }
    }

    public function obtenerTablero(?int $usuarioId = null): array
    {
        $params = [];
        $where = '';
        if ($usuarioId !== null && $usuarioId > 0) {
            $where = "WHERE l.permisos_configurados = 0
                OR l.creado_por = :usuario_id
                OR EXISTS (
                    SELECT 1
                    FROM notas_admin_lista_permisos nlp
                    WHERE nlp.lista_id = l.id
                      AND nlp.usuario_id = :usuario_id
                )";
            $params[':usuario_id'] = $usuarioId;
        }

        $listasStmt = $this->conn->prepare("SELECT l.id, l.titulo, l.posicion, l.creado_por, l.permisos_configurados, l.fecha_creacion, l.fecha_actualizacion
            FROM notas_admin_listas l
            {$where}
            ORDER BY l.posicion ASC, l.id ASC");
        $listasStmt->execute($params);
        $listas = $listasStmt ? $listasStmt->fetchAll(PDO::FETCH_ASSOC) : [];
        $listaIds = array_map(static fn($lista) => (int) $lista['id'], $listas);

        $tarjetas = [];
        if ($listaIds !== []) {
            $tarjetasPlaceholders = implode(',', array_fill(0, count($listaIds), '?'));
            $tarjetasStmt = $this->conn->prepare("SELECT id, lista_id, titulo, descripcion, completada, posicion, fecha_creacion, fecha_actualizacion
                FROM notas_admin_tarjetas
                WHERE lista_id IN ($tarjetasPlaceholders)
                ORDER BY posicion ASC, id ASC");
            $tarjetasStmt->execute($listaIds);
            $tarjetas = $tarjetasStmt->fetchAll(PDO::FETCH_ASSOC);
        }
        $permisosPorLista = $this->obtenerPermisosPorLista($listaIds);

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
            'current_user_id' => $usuarioId,
            'admins' => $this->obtenerAdministradores(),
            'listas' => array_map(function ($lista) use ($tarjetasPorLista, $permisosPorLista) {
                $id = (int) $lista['id'];
                return [
                    'id' => $id,
                    'titulo' => (string) $lista['titulo'],
                    'posicion' => (int) $lista['posicion'],
                    'creado_por' => $lista['creado_por'] !== null ? (int) $lista['creado_por'] : null,
                    'permisos_configurados' => (bool) $lista['permisos_configurados'],
                    'permisos' => $permisosPorLista[$id] ?? [],
                    'tarjetas' => $tarjetasPorLista[$id] ?? [],
                    'fecha_creacion' => (string) $lista['fecha_creacion'],
                    'fecha_actualizacion' => (string) $lista['fecha_actualizacion'],
                ];
            }, $listas),
        ];
    }

    public function actualizarPermisosLista(int $listaId, array $usuarioIds, ?int $asignadoPor): void
    {
        $this->asegurarListaExiste($listaId);
        $usuarioIds = array_values(array_unique(array_filter(array_map('intval', $usuarioIds), fn($id) => $id > 0)));

        if ($usuarioIds !== []) {
            $placeholders = implode(',', array_fill(0, count($usuarioIds), '?'));
            $stmtAdmins = $this->conn->prepare("SELECT u.id
                FROM usuarios u
                INNER JOIN administradores a ON a.usuario_id = u.id
                WHERE u.id IN ($placeholders)
                  AND u.estado = 'activo'
                  AND u.tipo_usuario IN ('admin', 'administrador')");
            $stmtAdmins->execute($usuarioIds);
            $idsValidos = array_map('intval', $stmtAdmins->fetchAll(PDO::FETCH_COLUMN));
            if (count($idsValidos) !== count($usuarioIds)) {
                throw new InvalidArgumentException('Selecciona solo administradores activos.');
            }
        }

        $this->conn->beginTransaction();
        try {
            $deleteStmt = $this->conn->prepare("DELETE FROM notas_admin_lista_permisos WHERE lista_id = :lista_id");
            $deleteStmt->execute([':lista_id' => $listaId]);

            $insertStmt = $this->conn->prepare("INSERT INTO notas_admin_lista_permisos (lista_id, usuario_id, asignado_por)
                VALUES (:lista_id, :usuario_id, :asignado_por)");
            foreach ($usuarioIds as $usuarioId) {
                $insertStmt->execute([
                    ':lista_id' => $listaId,
                    ':usuario_id' => $usuarioId,
                    ':asignado_por' => $asignadoPor,
                ]);
            }

            $updateStmt = $this->conn->prepare("UPDATE notas_admin_listas SET permisos_configurados = 1 WHERE id = :id");
            $updateStmt->execute([':id' => $listaId]);
            $this->conn->commit();
        } catch (Throwable $e) {
            $this->conn->rollBack();
            throw $e;
        }
    }

    public function asegurarListaVisible(int $listaId, ?int $usuarioId): void
    {
        if ($usuarioId === null || $usuarioId <= 0) {
            $this->asegurarListaExiste($listaId);
            return;
        }

        $stmt = $this->conn->prepare("SELECT l.id
            FROM notas_admin_listas l
            WHERE l.id = :lista_id
              AND (
                  l.permisos_configurados = 0
                  OR l.creado_por = :usuario_id
                  OR EXISTS (
                      SELECT 1
                      FROM notas_admin_lista_permisos nlp
                      WHERE nlp.lista_id = l.id
                        AND nlp.usuario_id = :usuario_id
                  )
              )
            LIMIT 1");
        $stmt->execute([
            ':lista_id' => $listaId,
            ':usuario_id' => $usuarioId,
        ]);

        if (!$stmt->fetch(PDO::FETCH_ASSOC)) {
            throw new InvalidArgumentException('No tienes permiso para esta lista.');
        }
    }

    public function asegurarTarjetaVisible(int $tarjetaId, ?int $usuarioId): void
    {
        if ($usuarioId === null || $usuarioId <= 0) {
            $stmt = $this->conn->prepare("SELECT id FROM notas_admin_tarjetas WHERE id = :id LIMIT 1");
            $stmt->execute([':id' => $tarjetaId]);
            if (!$stmt->fetch(PDO::FETCH_ASSOC)) {
                throw new InvalidArgumentException('La tarjeta no existe.');
            }
            return;
        }

        $stmt = $this->conn->prepare("SELECT t.id
            FROM notas_admin_tarjetas t
            INNER JOIN notas_admin_listas l ON l.id = t.lista_id
            WHERE t.id = :tarjeta_id
              AND (
                  l.permisos_configurados = 0
                  OR l.creado_por = :usuario_id
                  OR EXISTS (
                      SELECT 1
                      FROM notas_admin_lista_permisos nlp
                      WHERE nlp.lista_id = l.id
                        AND nlp.usuario_id = :usuario_id
                  )
              )
            LIMIT 1");
        $stmt->execute([
            ':tarjeta_id' => $tarjetaId,
            ':usuario_id' => $usuarioId,
        ]);

        if (!$stmt->fetch(PDO::FETCH_ASSOC)) {
            throw new InvalidArgumentException('No tienes permiso para esta tarjeta.');
        }
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

    public function reordenarListas(array $listaIds): void
    {
        $listaIds = array_values(array_unique(array_filter(array_map('intval', $listaIds), fn($id) => $id > 0)));
        if ($listaIds === []) {
            throw new InvalidArgumentException('Orden de listas invalido.');
        }

        $placeholders = implode(',', array_fill(0, count($listaIds), '?'));
        $stmtExiste = $this->conn->prepare("SELECT id FROM notas_admin_listas WHERE id IN ($placeholders)");
        $stmtExiste->execute($listaIds);
        $idsExistentes = array_map('intval', $stmtExiste->fetchAll(PDO::FETCH_COLUMN));

        if (count($idsExistentes) !== count($listaIds)) {
            throw new InvalidArgumentException('Una de las listas no existe.');
        }

        $this->conn->beginTransaction();
        try {
            $stmt = $this->conn->prepare("UPDATE notas_admin_listas SET posicion = :posicion WHERE id = :id");
            foreach ($listaIds as $index => $listaId) {
                $stmt->execute([
                    ':posicion' => $index + 1,
                    ':id' => $listaId,
                ]);
            }
            $this->conn->commit();
        } catch (Throwable $e) {
            $this->conn->rollBack();
            throw $e;
        }
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

    private function obtenerAdministradores(): array
    {
        $stmt = $this->conn->query("SELECT u.id, CONCAT(COALESCE(u.nombres, ''), ' ', COALESCE(u.apellidos, '')) AS nombre, u.correo
            FROM usuarios u
            INNER JOIN administradores a ON a.usuario_id = u.id
            WHERE u.estado = 'activo'
              AND u.tipo_usuario IN ('admin', 'administrador')
            ORDER BY nombre ASC, u.correo ASC");
        $rows = $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];

        return array_map(static function ($row) {
            $nombre = trim((string) ($row['nombre'] ?? ''));
            return [
                'id' => (int) $row['id'],
                'nombre' => $nombre !== '' ? $nombre : (string) ($row['correo'] ?? 'Administrador'),
                'correo' => (string) ($row['correo'] ?? ''),
            ];
        }, $rows);
    }

    private function obtenerPermisosPorLista(array $listaIds): array
    {
        $listaIds = array_values(array_unique(array_filter(array_map('intval', $listaIds), fn($id) => $id > 0)));
        if ($listaIds === []) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($listaIds), '?'));
        $stmt = $this->conn->prepare("SELECT lista_id, usuario_id
            FROM notas_admin_lista_permisos
            WHERE lista_id IN ($placeholders)
            ORDER BY lista_id ASC, usuario_id ASC");
        $stmt->execute($listaIds);

        $permisos = [];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $listaId = (int) $row['lista_id'];
            if (!isset($permisos[$listaId])) {
                $permisos[$listaId] = [];
            }
            $permisos[$listaId][] = (int) $row['usuario_id'];
        }

        return $permisos;
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
