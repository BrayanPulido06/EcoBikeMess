CREATE DATABASE IF NOT EXISTS ecobikemess;
use ecobikemess;

CREATE TABLE IF NOT EXISTS tp_registro (id INT AUTO_INCREMENT NOT NULL,
                                        correo VARCHAR(100) NOT NULL,
                                        password VARCHAR(100) NOT NULL,
                                        telefono VARCHAR(15) NOT NULL,
                                        nombre VARCHAR(50) NOT NULL,
                                        estado INT(1) NOT NULL,

                                        PRIMARY KEY (id)
);



CREATE TABLE IF NOT EXISTS tp_pedidos (id INT AUTO_INCREMENT NOT NULL,
                                        id_usuario INT NOT NULL,
                                        nombres VARCHAR(100) NOT NULL,
                                        telefono VARCHAR(15) NOT NULL,
                                        direccion VARCHAR(50) NOT NULL,
                                        cobro VARCHAR(30) NOT NULL,
                                        observacion varchar(100) NOT NULL,

                                        PRIMARY KEY (id),
                                        FOREIGN KEY (id_usuario) REFERENCES tp_registro(id) 
)ENGINE=INNODB;



CREATE TABLE IF NOT EXISTS tp_comprobante (id INT AUTO_INCREMENT NOT NULL,
                                        observacion VARCHAR(100),
                                        foto VARCHAR(500) NOT NULL,
                                        foto2 VARCHAR(500) NOT NULL,
                                        fecha DATETIME DEFAULT CURRENT_TIMESTAMP,
                                        

                                        PRIMARY KEY (id),
                                        FOREIGN KEY (id) REFERENCES tp_pedidos(id)

)ENGINE=INNODB;


/*inserts*/
INSERT INTO tp_registro VALUES 
(NULL, 'brayan06.pulido@gmail.com', '123456789', '3172509298', 'Brayan', '1'), 
(NULL, 'brayan@gmail.com', '987654321', '3187844160', 'Felipe', '0');


INSERT INTO tp_servicio VALUES 
(NULL, '1', 'Marlon Andres Pulido Lopez', '1234567899', 'calle 47 sur numero 1 f 21 este', '$100.000', 'recoger prenda');




/*Posible codigo*/

CREATE TABLE IF NOT EXISTS clientes (id INT AUTO_INCREMENT PRIMARY KEY,
                                    nombre_emprendimiento VARCHAR(200) NOT NULL,
                                    tipo_producto VARCHAR(200) NOT NULL,
                                    cuenta_bancaria VARCHAR (300), 
                                    nombre VARCHAR(200) NOT NULL,
                                    correo VARCHAR(100) UNIQUE NOT NULL,
                                    telefono VARCHAR(15) NOT NULL,
                                    instagram VARCHAR(100) NOT NULL,
                                    password VARCHAR(255) NOT NULL,
                                    fecha_registro DATETIME DEFAULT CURRENT_TIMESTAMP,
                                    estado INT (1) DEFAULT 1
);

CREATE TABLE IF NOT EXISTS mensajeros (id INT AUTO_INCREMENT PRIMARY KEY,
                                    nombres VARCHAR(200) NOT NULL,
                                    apellidos VARCHAR (200) NOT NULL,
                                    correo VARCHAR(100) UNIQUE NOT NULL,
                                    password VARCHAR(255) NOT NULL,
                                    telefono VARCHAR(15) NOT NULL,
                                    tipo_documento ENUM('cedula', 'dni', 'pasaporte', 'ruc', 'otro') NOT NULL,
                                    tipo_vehiculo ENUM('bicicleta', 'motocicleta', 'vehiculo') NOT NULL,
                                    numero_vehiculo VARCHAR(20) NOT NULL,  -- Placa o número de serie
                                    tipo_sangre VARCHAR (11) NOT NULL,
                                    numero_documento VARCHAR(20) NOT NULL,
                                    direccion_residencia VARCHAR (200) NOT NULL,
                                    foto VARCHAR (300) NOT NULL,
                                    hoja_vida VARCHAR (300) NOT NULL,
                                    telefono_emergencia VARCHAR (15) NOT NULL,
                                    nombre_emergencia VARCHAR (200) NOT NULL,
                                    apellido_emergencia VARCHAR (200) NOT NULL,
                                    telefono_emergencia2 VARCHAR (15) NOT NULL,
                                    nombre_emergencia2 VARCHAR (200) NOT NULL,
                                    apellido_emergencia2 VARCHAR (200) NOT NULL,
                                    fecha_registro DATETIME DEFAULT CURRENT_TIMESTAMP,
                                    estado INT (1) DEFAULT 1
);

CREATE TABLE IF NOT EXISTS pedidos (id INT AUTO_INCREMENT PRIMARY KEY,
                                    cliente_id INT NOT NULL,
                                    codigo_qr VARCHAR(50) UNIQUE NOT NULL,  -- Ej: "DELIV-ABC123"
                                    direccion_origen VARCHAR(255) NOT NULL,
                                    nombre_origen VARCHAR(200) NOT NULL,
                                    telefono_origen VARCHAR(15) NOT NULL,
                                    direccion_destino VARCHAR(255) NOT NULL,
                                    destinatario VARCHAR(100) NOT NULL,
                                    telefono_destinatario VARCHAR(15) NOT NULL,
                                    descripcion_paquete TEXT,
                                    instrucciones_entrega TEXT,
                                    estado ENUM(
                                        'pendiente',
                                        'asignado',
                                        'en_camino',
                                        'entregado',
                                        'fallido',
                                        'cancelado'
                                    ) DEFAULT 'pendiente',
                                    fecha_creacion DATETIME DEFAULT CURRENT_TIMESTAMP,
                                    FOREIGN KEY (cliente_id) REFERENCES clientes(id)
);

CREATE TABLE IF NOT EXISTS asignaciones (id INT AUTO_INCREMENT PRIMARY KEY,
                                    pedido_id INT NOT NULL,
                                    mensajero_id INT NOT NULL,
                                    fecha_asignacion DATETIME DEFAULT CURRENT_TIMESTAMP,
                                    FOREIGN KEY (pedido_id) REFERENCES pedidos(id),
                                    FOREIGN KEY (mensajero_id) REFERENCES mensajeros(id),
                                    UNIQUE (pedido_id)  -- Evita asignar un pedido a múltiples mensajeros
);

CREATE TABLE IF NOT EXISTS entregas (id INT AUTO_INCREMENT PRIMARY KEY,
                                    pedido_id INT NOT NULL,
                                    mensajero_id INT NOT NULL,
                                    foto_url VARCHAR(255) NOT NULL,  -- URL de Firebase/Cloud Storage
                                    nombre_receptor VARCHAR(100) NOT NULL,  -- Quién recibió
                                    id_receptor VARCHAR(20),  -- Cédula/DNI (opcional)
                                    monto_cobrado DECIMAL(10, 2) DEFAULT 0.00,  -- Si aplica pago en destino
                                    metodo_pago ENUM('efectivo', 'tarjeta', 'transferencia', 'no_aplica') DEFAULT 'no_aplica',
                                    observacion TEXT,  -- Notas adicionales
                                    fecha_entrega DATETIME DEFAULT CURRENT_TIMESTAMP,
                                    FOREIGN KEY (pedido_id) REFERENCES pedidos(id),
                                    FOREIGN KEY (mensajero_id) REFERENCES mensajeros(id)
);

/*casilla obsional para notificar*/

CREATE TABLE IF NOT EXISTS historial_pedidos (id INT AUTO_INCREMENT PRIMARY KEY,
                                    pedido_id INT NOT NULL,
                                    estado VARCHAR(50) NOT NULL,  -- Ej: "en_camino"
                                    mensaje TEXT,  -- Detalle adicional
                                    fecha_registro DATETIME DEFAULT CURRENT_TIMESTAMP,
                                    FOREIGN KEY (pedido_id) REFERENCES pedidos(id)
);

CREATE TABLE IF NOT EXISTS pagos (id INT AUTO_INCREMENT PRIMARY KEY,
                                    pedido_id INT NOT NULL,
                                    monto DECIMAL(10, 2) NOT NULL,
                                    metodo ENUM('efectivo', 'tarjeta', 'transferencia', 'app') NOT NULL,
                                    estado ENUM('pendiente', 'completado', 'rechazado') DEFAULT 'pendiente',
                                    comprobante_url VARCHAR(255),  -- URL del voucher (opcional)
                                    fecha_pago DATETIME,
                                    FOREIGN KEY (pedido_id) REFERENCES pedidos(id)
);

CREATE TABLE IF NOT EXISTS notificaciones (id INT AUTO_INCREMENT PRIMARY KEY,
                                    usuario_id INT NOT NULL,  -- ID de cliente o mensajero
                                    tipo ENUM('cliente', 'mensajero') NOT NULL,
                                    mensaje TEXT NOT NULL,
                                    leida BOOLEAN DEFAULT FALSE,
                                    fecha_envio DATETIME DEFAULT CURRENT_TIMESTAMP
);


INSERT INTO `clientes` (`id`, `nombre`, `correo`, `telefono`, `password`, `fecha_registro`, `estado`) VALUES (NULL, 'brayan Pulido', 'wool@gmail.com', '1234567899', '987654321', current_timestamp(), '1');
(`id`, `nombre`, `correo`, `telefono`, `password`, `fecha_registro`, `estado`) VALUES (NULL, 'brayan Pulido', 'brayan@gmail.com', '1234567899', '987654321', current_timestamp(), '1');

INSERT INTO `mensajeros` (`id`, `nombres`, `apellidos`, `correo`, `password`, `telefono`, `tipo_documento`, `numero_documento`, `sangre`, `direccion_residencia`, `foto`, `telefono_emergencia`, `nombre_emergencia`, `apellido_emergencia`, `fecha_registro`, `estado`) VALUES (NULL, 'brayan ', 'pulido', 'brayan06.pulido@gmail.com', '123456789', '9876543211', 'cedula', '1014596349', 'O+', 'calle 47 sur numero 1 f20 este', 'hola', '1234567899', 'marisol', 'lopez', current_timestamp(), '1');

INSERT INTO `pedidos` (`id`, `cliente_id`, `codigo_qr`, `direccion_origen`, `direccion_destino`, `destinatario`, `telefono_destinatario`, `descripcion_paquete`, `instrucciones_entrega`, `estado`, `fecha_creacion`) VALUES (NULL, '1', 'tuyf', 'ñklggasdfghjklñ', 'serdfghhjkl', 'marlon', '8897654321', 'dyttgkhujklñ', 'rdgfhjklkñ', 'pendiente', current_timestamp());

INSERT INTO `historial_pedidos` (`id`, `pedido_id`, `estado`, `mensaje`, `fecha_registro`) VALUES (NULL, '1', '1', 'dfghjklñ', current_timestamp());
INSERT INTO `entregas` (`id`, `pedido_id`, `mensajero_id`, `foto_url`, `nombre_receptor`, `id_receptor`, `monto_cobrado`, `metodo_pago`, `observacion`, `fecha_entrega`) VALUES (NULL, '1', '1', 'sdfggf', 'lorena', '132465789987', '100.000', 'no_aplica', 'hgasasad', current_timestamp());

INSERT INTO `asignaciones` (`id`, `pedido_id`, `mensajero_id`, `fecha_asignacion`) VALUES (NULL, '1', '1', current_timestamp());