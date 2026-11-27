-- API de integracion de tiendas. Ejecutar una sola vez sobre la base existente.
CREATE TABLE IF NOT EXISTS api_client_keys (
    id INT PRIMARY KEY AUTO_INCREMENT,
    cliente_id INT NOT NULL,
    key_hash CHAR(64) NOT NULL UNIQUE,
    key_prefix VARCHAR(24) NOT NULL,
    nombre VARCHAR(100) NOT NULL,
    estado ENUM('activo', 'inactivo') NOT NULL DEFAULT 'activo',
    fecha_creacion TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    ultimo_uso TIMESTAMP NULL,
    FOREIGN KEY (cliente_id) REFERENCES clientes(id) ON DELETE CASCADE,
    INDEX idx_api_client_keys_cliente_estado (cliente_id, estado)
);

-- La clave completa solo se entrega al operador de Squishy una vez.
-- La API conserva exclusivamente el hash SHA-256.
INSERT IGNORE INTO api_client_keys (cliente_id, key_hash, key_prefix, nombre, estado)
SELECT id,
       'b7fb9ae8d21d97010b10451437fed8a39a6735169ed320476eb9586c6136d573',
       'ebm_live_0RjEPiTNA',
       'Squishy tienda online',
       'activo'
FROM clientes
WHERE LOWER(TRIM(nombre_emprendimiento)) = 'squishy'
ORDER BY id ASC
LIMIT 1;

ALTER TABLE paquetes ADD COLUMN external_order_id VARCHAR(100) NULL AFTER cliente_id;
CREATE UNIQUE INDEX uq_paquetes_cliente_external_order ON paquetes (cliente_id, external_order_id);

