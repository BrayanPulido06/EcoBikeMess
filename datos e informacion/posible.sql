-- ============================================
-- SISTEMA DE LOGÍSTICA CON BODEGA - BASE DE DATOS COMPLETA
-- Modelo: Recogida → Bodega → Clasificación → Distribución
-- ============================================

-- TABLAS PRINCIPALES DE USUARIOS
-- ============================================

CREATE TABLE IF NOT EXISTS administradores (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tipo_documento ENUM('cedula', 'dni', 'pasaporte', 'ruc', 'otro') NOT NULL,
    cedula VARCHAR(20) NOT NULL,
    nombre VARCHAR(200) NOT NULL,
    correo VARCHAR(255) UNIQUE NOT NULL,
    telefono VARCHAR(15) NOT NULL,
    password VARCHAR(255) NOT NULL,
    rol ENUM('super_admin', 'admin', 'operador_bodega', 'supervisor_rutas', 'soporte', 'finanzas') DEFAULT 'admin',
    bodega_asignada INT, -- Bodega donde puede trabajar
    permisos JSON,
    ultimo_acceso DATETIME,
    intentos_login INT DEFAULT 0,
    bloqueado_hasta DATETIME NULL,
    fecha_registro DATETIME DEFAULT CURRENT_TIMESTAMP,
    created_by INT,
    updated_at DATETIME ON UPDATE CURRENT_TIMESTAMP,
    updated_by INT,
    estado INT(1) DEFAULT 1,
    INDEX idx_cedula (cedula),
    INDEX idx_correo (correo),
    INDEX idx_rol (rol)
) ENGINE=INNODB;

CREATE TABLE IF NOT EXISTS clientes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre_emprendimiento VARCHAR(255) NOT NULL,
    tipo_producto VARCHAR(255) NOT NULL,
    cuenta_bancaria VARCHAR(300), 
    nombres VARCHAR(200) NOT NULL,
    apellidos VARCHAR(200) NOT NULL,
    correo VARCHAR(255) UNIQUE NOT NULL,
    telefono VARCHAR(15) NOT NULL,
    instagram VARCHAR(255) NOT NULL,
    password VARCHAR(255) NOT NULL,
    direccion_principal VARCHAR(300),
    lat_direccion_principal DECIMAL(10, 8),
    lng_direccion_principal DECIMAL(11, 8),
    zona_cobertura VARCHAR(200),
    credito_disponible DECIMAL(10,2) DEFAULT 0.00,
    limite_credito DECIMAL(10,2) DEFAULT 0.00,
    total_pedidos INT DEFAULT 0,
    pedidos_completados INT DEFAULT 0,
    cliente_frecuente BOOLEAN DEFAULT FALSE, -- Para prioridades especiales
    verificado BOOLEAN DEFAULT FALSE,
    fecha_verificacion DATETIME,
    ultimo_acceso DATETIME,
    intentos_login INT DEFAULT 0,
    bloqueado_hasta DATETIME NULL,
    fecha_registro DATETIME DEFAULT CURRENT_TIMESTAMP,
    created_by INT,
    updated_at DATETIME ON UPDATE CURRENT_TIMESTAMP,
    updated_by INT,
    estado INT(1) DEFAULT 1,
    INDEX idx_correo (correo),
    INDEX idx_verificado (verificado),
    INDEX idx_zona (zona_cobertura),
    INDEX idx_emprendimiento (nombre_emprendimiento)
) ENGINE=INNODB;

-- Separamos roles de personal operativo
CREATE TABLE IF NOT EXISTS personal_operativo (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tipo_documento ENUM('cedula', 'dni', 'pasaporte', 'ruc', 'otro') NOT NULL,
    numero_documento VARCHAR(20) NOT NULL,
    nombres VARCHAR(200) NOT NULL,
    apellidos VARCHAR(200) NOT NULL,
    telefono VARCHAR(15) NOT NULL,
    correo VARCHAR(200) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    tipo_sangre VARCHAR(11),
    direccion_residencia VARCHAR(200) NOT NULL,
    foto VARCHAR(300) NOT NULL,
    -- Especialización del personal
    tipo_personal ENUM('recolector', 'distribuidor', 'operador_bodega', 'conductor_ruta') NOT NULL,
    bodega_asignada INT, -- Bodega donde trabaja
    zona_trabajo VARCHAR(200),
    -- Información del vehículo (solo para recolectores y distribuidores)
    tipo_vehiculo ENUM('bicicleta', 'motocicleta', 'vehiculo', 'camion', 'ninguno'),
    numero_vehiculo VARCHAR(20),
    licencia_numero VARCHAR(50),
    licencia_vencimiento DATE,
    seguro_vehiculo VARCHAR(100),
    -- Contactos de emergencia
    telefono_emergencia1 VARCHAR(15),
    nombre_emergencia1 VARCHAR(200),
    telefono_emergencia2 VARCHAR(15),
    nombre_emergencia2 VARCHAR(200),
    -- Estadísticas
    total_operaciones INT DEFAULT 0,
    operaciones_exitosas INT DEFAULT 0,
    ganancia_total DECIMAL(10,2) DEFAULT 0.00,
    -- Estado y ubicación
    activo BOOLEAN DEFAULT TRUE,
    disponible BOOLEAN DEFAULT FALSE,
    ultima_conexion DATETIME,
    ultima_ubicacion_lat DECIMAL(10, 8),
    ultima_ubicacion_lng DECIMAL(11, 8),
    verificado BOOLEAN DEFAULT FALSE,
    fecha_verificacion DATETIME,
    fecha_registro DATETIME DEFAULT CURRENT_TIMESTAMP,
    created_by INT,
    updated_at DATETIME ON UPDATE CURRENT_TIMESTAMP,
    updated_by INT,
    estado TINYINT(1) DEFAULT 1,
    INDEX idx_documento (numero_documento),
    INDEX idx_tipo_personal (tipo_personal),
    INDEX idx_bodega (bodega_asignada),
    INDEX idx_zona (zona_trabajo),
    INDEX idx_disponible (disponible, activo)
) ENGINE=INNODB;

-- TABLAS DE INFRAESTRUCTURA - BODEGAS
-- ============================================

CREATE TABLE IF NOT EXISTS bodegas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    codigo VARCHAR(20) UNIQUE NOT NULL, -- BOG01, MED02, etc.
    nombre VARCHAR(200) NOT NULL,
    tipo ENUM('principal', 'secundaria', 'punto_entrega') DEFAULT 'secundaria',
    direccion VARCHAR(300) NOT NULL,
    latitud DECIMAL(10, 8) NOT NULL,
    longitud DECIMAL(11, 8) NOT NULL,
    zona_cobertura VARCHAR(200),
    capacidad_maxima INT DEFAULT 1000, -- Número máximo de paquetes
    capacidad_actual INT DEFAULT 0,
    telefono VARCHAR(15),
    responsable_id INT, -- Personal a cargo
    horario_apertura TIME,
    horario_cierre TIME,
    dias_operacion JSON, -- [1,2,3,4,5,6] donde 1=lunes
    activa BOOLEAN DEFAULT TRUE,
    fecha_creacion DATETIME DEFAULT CURRENT_TIMESTAMP,
    created_by INT,
    updated_at DATETIME ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (responsable_id) REFERENCES personal_operativo(id),
    INDEX idx_codigo (codigo),
    INDEX idx_zona (zona_cobertura),
    INDEX idx_activa (activa),
    INDEX idx_tipo (tipo)
) ENGINE=INNODB;

-- Estantes/secciones dentro de las bodegas
CREATE TABLE IF NOT EXISTS secciones_bodega (
    id INT AUTO_INCREMENT PRIMARY KEY,
    bodega_id INT NOT NULL,
    codigo_seccion VARCHAR(20) NOT NULL, -- A1, B2, C3, etc.
    descripcion VARCHAR(200),
    capacidad_maxima INT DEFAULT 100,
    capacidad_actual INT DEFAULT 0,
    tipo_paquetes ENUM('general', 'fragil', 'urgente', 'grande', 'refrigerado') DEFAULT 'general',
    activa BOOLEAN DEFAULT TRUE,
    FOREIGN KEY (bodega_id) REFERENCES bodegas(id),
    INDEX idx_bodega_codigo (bodega_id, codigo_seccion),
    INDEX idx_tipo (tipo_paquetes),
    UNIQUE KEY unique_bodega_seccion (bodega_id, codigo_seccion)
) ENGINE=INNODB;

-- TABLAS DE PEDIDOS Y PROCESAMIENTO
-- ============================================

CREATE TABLE IF NOT EXISTS pedidos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    cliente_id INT NOT NULL,
    codigo_pedido VARCHAR(30) UNIQUE NOT NULL, -- Código visible para el cliente
    codigo_interno VARCHAR(30) UNIQUE NOT NULL, -- Código interno para operaciones
    
    -- Información de origen
    direccion_origen VARCHAR(255) NOT NULL,
    nombre_remitente VARCHAR(200) NOT NULL,
    telefono_remitente VARCHAR(15) NOT NULL,
    lat_origen DECIMAL(10, 8),
    lng_origen DECIMAL(11, 8),
    zona_origen VARCHAR(100),
    
    -- Información de destino
    direccion_destino VARCHAR(255) NOT NULL,
    nombre_destinatario VARCHAR(100) NOT NULL,
    telefono_destinatario VARCHAR(15) NOT NULL,
    documento_destinatario VARCHAR(20),
    lat_destino DECIMAL(10, 8),
    lng_destino DECIMAL(11, 8),
    zona_destino VARCHAR(100),
    
    -- Características del paquete
    descripcion_paquete TEXT,
    peso_kg DECIMAL(8,3),
    alto_cm DECIMAL(8,2),
    ancho_cm DECIMAL(8,2),
    largo_cm DECIMAL(8,2),
    volumen_m3 DECIMAL(8,4),
    valor_declarado DECIMAL(12,2),
    fragil BOOLEAN DEFAULT FALSE,
    requiere_refrigeracion BOOLEAN DEFAULT FALSE,
    
    -- Configuración de servicio
    tipo_servicio ENUM('estandar', 'express', 'mismo_dia', 'programado') DEFAULT 'estandar',
    prioridad ENUM('normal', 'alta', 'critica') DEFAULT 'normal',
    instrucciones_especiales TEXT,
    fecha_programada DATETIME, -- Para servicios programados
    fecha_limite_entrega DATETIME,
    
    -- Costos
    costo_calculado DECIMAL(10,2),
    costo_final DECIMAL(10,2),
    descuentos_aplicados DECIMAL(10,2) DEFAULT 0,
    impuestos DECIMAL(10,2) DEFAULT 0,
    metodo_pago ENUM('efectivo', 'tarjeta', 'transferencia', 'credito', 'contraentrega') DEFAULT 'efectivo',
    valor_contraentrega DECIMAL(10,2) DEFAULT 0, -- Si es contraentrega
    
    -- Estados del proceso logístico
    estado ENUM(
        'pendiente',           -- Pedido creado, esperando recolección
        'programado_recoleccion', -- Programado para recoger
        'en_recoleccion',      -- Recolector en camino
        'recolectado',         -- Paquete recogido
        'en_transito_bodega',  -- Camino a bodega
        'en_bodega',           -- En bodega, pendiente clasificación
        'clasificado',         -- Clasificado y ubicado en bodega
        'programado_distribucion', -- Programado para distribución
        'en_ruta_distribucion', -- En ruta de entrega
        'en_destino',          -- Mensajero en ubicación de entrega
        'entregado',           -- Entregado exitosamente
        'fallido',             -- Intento fallido de entrega
        'devuelto',            -- Devuelto al remitente
        'cancelado'            -- Cancelado
    ) DEFAULT 'pendiente',
    
    -- Bodegas y asignaciones
    bodega_actual INT, -- Bodega donde está actualmente
    seccion_actual INT, -- Sección específica en bodega
    
    -- Control de intentos
    intentos_recoleccion INT DEFAULT 0,
    intentos_entrega INT DEFAULT 0,
    max_intentos_entrega INT DEFAULT 3,
    
    -- Fechas del proceso
    fecha_creacion DATETIME DEFAULT CURRENT_TIMESTAMP,
    fecha_programacion_recoleccion DATETIME,
    fecha_recoleccion DATETIME,
    fecha_ingreso_bodega DATETIME,
    fecha_clasificacion DATETIME,
    fecha_programacion_distribucion DATETIME,
    fecha_inicio_distribucion DATETIME,
    fecha_entrega DATETIME,
    fecha_limite_bodega DATETIME, -- Límite para estar en bodega
    
    -- Auditoría
    created_by INT,
    updated_at DATETIME ON UPDATE CURRENT_TIMESTAMP,
    updated_by INT,
    
    FOREIGN KEY (cliente_id) REFERENCES clientes(id),
    FOREIGN KEY (bodega_actual) REFERENCES bodegas(id),
    FOREIGN KEY (seccion_actual) REFERENCES secciones_bodega(id),
    
    INDEX idx_cliente (cliente_id),
    INDEX idx_estado (estado),
    INDEX idx_codigo_pedido (codigo_pedido),
    INDEX idx_codigo_interno (codigo_interno),
    INDEX idx_fecha_creacion (fecha_creacion),
    INDEX idx_zona_origen (zona_origen),
    INDEX idx_zona_destino (zona_destino),
    INDEX idx_bodega_actual (bodega_actual),
    INDEX idx_tipo_servicio (tipo_servicio),
    INDEX idx_prioridad (prioridad)
) ENGINE=INNODB;

-- TABLAS DE INCIDENCIAS Y CONTROL DE CALIDAD
-- ============================================

CREATE TABLE IF NOT EXISTS incidencias (
    id INT AUTO_INCREMENT PRIMARY KEY,
    pedido_id INT NOT NULL,
    ruta_id INT, -- Puede ser ruta de recolección o distribución
    personal_involucrado_id INT,
    bodega_id INT,
    
    -- Clasificación de la incidencia
    categoria ENUM('recoleccion', 'transporte', 'bodega', 'distribucion', 'cliente', 'sistema') NOT NULL,
    tipo ENUM('retraso', 'paquete_danado', 'paquete_perdido', 'direccion_incorrecta', 
              'cliente_ausente', 'accidente', 'robo', 'vehiculo_averiado', 
              'error_clasificacion', 'error_sistema', 'fraude', 'otro') NOT NULL,
    
    gravedad ENUM('baja', 'media', 'alta', 'critica') DEFAULT 'media',
    impacto ENUM('ninguno', 'cliente', 'operacion', 'financiero', 'reputacional', 'multiple') DEFAULT 'cliente',
    
    -- Detalles de la incidencia
    descripcion TEXT NOT NULL,
    ubicacion VARCHAR(300),
    evidencias JSON, -- URLs de fotos/videos/documentos
    
    -- Impacto financiero
    costo_estimado DECIMAL(10,2) DEFAULT 0,
    compensacion_cliente DECIMAL(10,2) DEFAULT 0,
    penalizacion_personal DECIMAL(10,2) DEFAULT 0,
    
    -- Gestión de la incidencia
    estado ENUM('reportada', 'en_investigacion', 'en_proceso', 'resuelta', 'cerrada', 'escalada') DEFAULT 'reportada',
    reportado_por ENUM('cliente', 'personal_operativo', 'sistema', 'administrador') NOT NULL,
    reportado_por_id INT,
    asignado_a INT, -- Administrador responsable
    
    -- Resolución
    resolucion TEXT,
    acciones_correctivas TEXT,
    acciones_preventivas TEXT,
    
    -- Tiempos
    fecha_reporte DATETIME DEFAULT CURRENT_TIMESTAMP,
    fecha_asignacion DATETIME,
    fecha_resolucion DATETIME,
    fecha_cierre DATETIME,
    tiempo_resolucion_horas INT,
    
    FOREIGN KEY (pedido_id) REFERENCES pedidos(id),
    FOREIGN KEY (personal_involucrado_id) REFERENCES personal_operativo(id),
    FOREIGN KEY (bodega_id) REFERENCES bodegas(id),
    
    INDEX idx_pedido (pedido_id),
    INDEX idx_categoria_tipo (categoria, tipo),
    INDEX idx_estado (estado),
    INDEX idx_gravedad (gravedad),
    INDEX idx_fecha_reporte (fecha_reporte),
    INDEX idx_asignado (asignado_a)
) ENGINE=INNODB;

-- TABLAS FINANCIERAS
-- ============================================

CREATE TABLE IF NOT EXISTS pagos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    pedido_id INT NOT NULL,
    cliente_id INT NOT NULL,
    
    -- Desglose de costos
    subtotal DECIMAL(12,2) NOT NULL,
    descuentos DECIMAL(12,2) DEFAULT 0,
    recargos DECIMAL(12,2) DEFAULT 0,
    impuestos DECIMAL(12,2) DEFAULT 0,
    monto_total DECIMAL(12,2) NOT NULL,
    
    -- Contraentrega
    valor_contraentrega DECIMAL(12,2) DEFAULT 0,
    comision_contraentrega DECIMAL(12,2) DEFAULT 0,
    
    -- Distribución de ingresos
    comision_plataforma DECIMAL(12,2) NOT NULL,
    comision_recolector DECIMAL(12,2) DEFAULT 0,
    comision_distribuidor DECIMAL(12,2) DEFAULT 0,
    comision_bodega DECIMAL(12,2) DEFAULT 0,
    
    -- Información de pago
    metodo_pago ENUM('efectivo', 'tarjeta_credito', 'tarjeta_debito', 'transferencia', 
                     'pse', 'contraentrega', 'credito_empresarial') NOT NULL,
    estado_pago ENUM('pendiente', 'procesando', 'completado', 'fallido', 
                     'reembolsado', 'parcial', 'en_disputa') DEFAULT 'pendiente',
    
    -- Referencias externas
    referencia_pago_externa VARCHAR(100),
    referencia_contraentrega VARCHAR(100),
    gateway_utilizado VARCHAR(50),
    
    -- Comprobantes y evidencias
    comprobante_pago_url VARCHAR(300),
    comprobante_contraentrega_url VARCHAR(300),
    
    -- Control de fechas
    fecha_creacion DATETIME DEFAULT CURRENT_TIMESTAMP,
    fecha_pago DATETIME,
    fecha_liquidacion_personal DATETIME,
    fecha_vencimiento DATETIME,
    
    -- Conciliación
    conciliado BOOLEAN DEFAULT FALSE,
    fecha_conciliacion DATETIME,
    conciliado_por INT,
    
    notas TEXT,
    
    FOREIGN KEY (pedido_id) REFERENCES pedidos(id),
    FOREIGN KEY (cliente_id) REFERENCES clientes(id),
    
    INDEX idx_pedido (pedido_id),
    INDEX idx_cliente (cliente_id),
    INDEX idx_estado (estado_pago),
    INDEX idx_fecha_pago (fecha_pago),
    INDEX idx_metodo (metodo_pago),
    INDEX idx_conciliado (conciliado)
) ENGINE=INNODB;

-- Liquidaciones al personal
CREATE TABLE IF NOT EXISTS liquidaciones_personal (
    id INT AUTO_INCREMENT PRIMARY KEY,
    personal_id INT NOT NULL,
    periodo_inicio DATE NOT NULL,
    periodo_fin DATE NOT NULL,
    
    -- Totales del periodo
    total_operaciones INT DEFAULT 0,
    operaciones_exitosas INT DEFAULT 0,
    total_ganado DECIMAL(12,2) DEFAULT 0,
    
    -- Deducciones
    deducciones_incidencias DECIMAL(10,2) DEFAULT 0,
    deducciones_combustible DECIMAL(10,2) DEFAULT 0,
    deducciones_mantenimiento DECIMAL(10,2) DEFAULT 0,
    otras_deducciones DECIMAL(10,2) DEFAULT 0,
    
    -- Bonificaciones
    bonificacion_puntualidad DECIMAL(10,2) DEFAULT 0,
    bonificacion_calificacion DECIMAL(10,2) DEFAULT 0,
    bonificacion_volumen DECIMAL(10,2) DEFAULT 0,
    otras_bonificaciones DECIMAL(10,2) DEFAULT 0,
    
    -- Total final
    total_neto DECIMAL(12,2) NOT NULL,
    
    -- Control de pago
    estado ENUM('calculada', 'aprobada', 'pagada', 'anulada') DEFAULT 'calculada',
    metodo_pago ENUM('transferencia', 'efectivo', 'cheque') DEFAULT 'transferencia',
    referencia_pago VARCHAR(100),
    fecha_pago DATETIME,
    
    -- Auditoría
    calculada_por INT,
    aprobada_por INT,
    fecha_calculo DATETIME DEFAULT CURRENT_TIMESTAMP,
    fecha_aprobacion DATETIME,
    
    notas TEXT,
    
    FOREIGN KEY (personal_id) REFERENCES personal_operativo(id),
    
    INDEX idx_personal (personal_id),
    INDEX idx_periodo (periodo_inicio, periodo_fin),
    INDEX idx_estado (estado),
    INDEX idx_fecha_calculo (fecha_calculo),
    UNIQUE KEY unique_personal_periodo (personal_id, periodo_inicio, periodo_fin)
) ENGINE=INNODB;

-- TABLAS DE PROMOCIONES Y DESCUENTOS
-- ============================================

CREATE TABLE IF NOT EXISTS promociones (
    id INT AUTO_INCREMENT PRIMARY KEY,
    codigo VARCHAR(50) UNIQUE NOT NULL,
    nombre VARCHAR(200) NOT NULL,
    descripcion TEXT,
    
    -- Tipo de descuento
    tipo ENUM('descuento_porcentaje', 'descuento_fijo', 'envio_gratis', 
              'descuento_contraentrega', 'upgrade_servicio') NOT NULL,
    valor DECIMAL(10,2) NOT NULL,
    
    -- Restricciones de aplicación
    monto_minimo_pedido DECIMAL(10,2),
    peso_maximo_kg DECIMAL(8,2),
    zonas_aplicables JSON, -- IDs de zonas
    servicios_aplicables JSON, -- Tipos de servicio
    
    -- Límites de uso
    uso_maximo_por_cliente INT DEFAULT 1,
    uso_maximo_total INT,
    usos_actuales INT DEFAULT 0,
    
    -- Vigencia
    fecha_inicio DATETIME NOT NULL,
    fecha_fin DATETIME NOT NULL,
    dias_semana_aplicables JSON, -- [1,2,3,4,5] lunes a viernes
    horas_aplicables JSON, -- {"inicio": "09:00", "fin": "18:00"}
    
    -- Segmentación
    solo_nuevos_clientes BOOLEAN DEFAULT FALSE,
    solo_clientes_frecuentes BOOLEAN DEFAULT FALSE,
    clientes_especificos JSON, -- IDs de clientes específicos
    
    activa BOOLEAN DEFAULT TRUE,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    created_by INT,
    updated_at DATETIME ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_codigo (codigo),
    INDEX idx_activa (activa),
    INDEX idx_fechas (fecha_inicio, fecha_fin),
    INDEX idx_tipo (tipo)
) ENGINE=INNODB;

CREATE TABLE IF NOT EXISTS uso_promociones (
    id INT AUTO_INCREMENT PRIMARY KEY,
    promocion_id INT NOT NULL,
    cliente_id INT NOT NULL,
    pedido_id INT NOT NULL,
    
    descuento_aplicado DECIMAL(10,2) NOT NULL,
    fecha_uso DATETIME DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (promocion_id) REFERENCES promociones(id),
    FOREIGN KEY (cliente_id) REFERENCES clientes(id),
    FOREIGN KEY (pedido_id) REFERENCES pedidos(id),
    
    INDEX idx_promocion (promocion_id),
    INDEX idx_cliente (cliente_id),
    INDEX idx_pedido (pedido_id),
    INDEX idx_fecha (fecha_uso),
    UNIQUE KEY unique_promocion_pedido (promocion_id, pedido_id)
) ENGINE=INNODB;

-- TABLAS DE SEGUIMIENTO Y UBICACIONES
-- ============================================

CREATE TABLE IF NOT EXISTS ubicaciones_personal (
    id INT AUTO_INCREMENT PRIMARY KEY,
    personal_id INT NOT NULL,
    latitud DECIMAL(10, 8) NOT NULL,
    longitud DECIMAL(11, 8) NOT NULL,
    
    -- Información de movimiento
    velocidad_kmh DECIMAL(5,2),
    direccion_grados DECIMAL(5,2),
    altitud_metros INT,
    precision_metros INT,
    
    -- Estado operativo
    en_servicio BOOLEAN DEFAULT FALSE,
    disponible BOOLEAN DEFAULT FALSE,
    ruta_activa_id INT, -- ID de ruta que está ejecutando
    ruta_tipo ENUM('recoleccion', 'distribucion'),
    
    -- Información adicional
    bateria_dispositivo INT, -- Porcentaje de batería
    conexion_tipo ENUM('wifi', '3g', '4g', '5g'),
    timestamp DATETIME DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (personal_id) REFERENCES personal_operativo(id),
    
    INDEX idx_personal_timestamp (personal_id, timestamp DESC),
    INDEX idx_disponible (disponible, en_servicio),
    INDEX idx_ruta_activa (ruta_activa_id, ruta_tipo),
    INDEX idx_timestamp (timestamp)
) ENGINE=INNODB;

-- Historial de ubicaciones de paquetes (tracking)
CREATE TABLE IF NOT EXISTS tracking_paquetes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    pedido_id INT NOT NULL,
    ubicacion VARCHAR(300) NOT NULL,
    latitud DECIMAL(10, 8),
    longitud DECIMAL(11, 8),
    
    -- Descripción del evento
    evento ENUM('creado', 'programado_recoleccion', 'en_recoleccion', 'recolectado', 
                'en_transito_bodega', 'ingreso_bodega', 'clasificado', 'almacenado',
                'preparado_despacho', 'despachado', 'en_ruta_entrega', 'en_destino',
                'entregado', 'fallido', 'devuelto') NOT NULL,
    
    descripcion TEXT,
    personal_responsable_id INT,
    bodega_id INT,
    ruta_id INT,
    
    timestamp DATETIME DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (pedido_id) REFERENCES pedidos(id),
    FOREIGN KEY (personal_responsable_id) REFERENCES personal_operativo(id),
    FOREIGN KEY (bodega_id) REFERENCES bodegas(id),
    
    INDEX idx_pedido_timestamp (pedido_id, timestamp),
    INDEX idx_evento (evento),
    INDEX idx_timestamp (timestamp)
) ENGINE=INNODB;

-- TABLA DE AUDITORÍA MEJORADA
-- ============================================

CREATE TABLE IF NOT EXISTS audit_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tabla VARCHAR(50) NOT NULL,
    registro_id INT NOT NULL,
    accion ENUM('INSERT', 'UPDATE', 'DELETE') NOT NULL,
    
    -- Datos del cambio
    valores_anteriores JSON,
    valores_nuevos JSON,
    campos_modificados JSON, -- Solo los campos que cambiaron
    
    -- Usuario responsable
    usuario_id INT,
    usuario_tipo ENUM('administrador', 'cliente', 'personal_operativo', 'sistema') NOT NULL,
    
    -- Información técnica
    ip_address VARCHAR(45),
    user_agent TEXT,
    endpoint VARCHAR(200),
    metodo_http ENUM('GET', 'POST', 'PUT', 'DELETE', 'PATCH'),
    
    -- Contexto del negocio
    contexto_operativo ENUM('recoleccion', 'bodega', 'distribucion', 'admin', 'cliente', 'sistema'),
    motivo VARCHAR(500), -- Razón del cambio
    
    timestamp DATETIME DEFAULT CURRENT_TIMESTAMP,
    
    INDEX idx_tabla_registro (tabla, registro_id),
    INDEX idx_usuario (usuario_tipo, usuario_id),
    INDEX idx_timestamp (timestamp),
    INDEX idx_accion (accion),
    INDEX idx_contexto (contexto_operativo)
) ENGINE=INNODB;

-- VISTAS ÚTILES PARA REPORTES Y DASHBOARDS
-- ============================================

-- Vista de estado actual de pedidos por bodega
CREATE VIEW vista_pedidos_por_bodega AS
SELECT 
    b.codigo as codigo_bodega,
    b.nombre as nombre_bodega,
    p.estado,
    COUNT(*) as cantidad,
    SUM(p.peso_kg) as peso_total_kg,
    AVG(DATEDIFF(NOW(), p.fecha_ingreso_bodega)) as dias_promedio_bodega
FROM pedidos p
JOIN bodegas b ON p.bodega_actual = b.id
WHERE p.estado IN ('en_bodega', 'clasificado', 'programado_distribucion')
GROUP BY b.id, p.estado;

-- Vista de rendimiento del personal operativo
CREATE VIEW vista_rendimiento_personal AS
SELECT 
    po.id,
    CONCAT(po.nombres, ' ', po.apellidos) as nombre_completo,
    po.tipo_personal,
    po.total_operaciones,
    po.operaciones_exitosas,
    CASE 
        WHEN po.total_operaciones > 0 
        THEN ROUND((po.operaciones_exitosas * 100.0) / po.total_operaciones, 2)
        ELSE 0 
    END as porcentaje_exito,
    po.ganancia_total,
    AVG(cal.puntuacion) as calificacion_promedio
FROM personal_operativo po
LEFT JOIN calificaciones cal ON po.id = cal.calificado_id AND cal.calificado_tipo = 'personal'
WHERE po.estado = 1
GROUP BY po.id;

-- Vista de estadísticas de rutas
CREATE VIEW vista_estadisticas_rutas AS
SELECT 
    'recoleccion' as tipo_ruta,
    DATE(fecha_programada) as fecha,
    COUNT(*) as total_rutas,
    SUM(total_pedidos) as total_pedidos,
    SUM(pedidos_completados) as pedidos_completados,
    AVG(tiempo_estimado_minutos) as tiempo_promedio,
    SUM(CASE WHEN estado = 'completada' THEN 1 ELSE 0 END) as rutas_completadas
FROM rutas_recoleccion
GROUP BY DATE(fecha_programada)

UNION ALL

SELECT 
    'distribucion' as tipo_ruta,
    DATE(fecha_programada) as fecha,
    COUNT(*) as total_rutas,
    SUM(total_pedidos) as total_pedidos,
    SUM(pedidos_entregados) as pedidos_completados,
    AVG(tiempo_total_minutos) as tiempo_promedio,
    SUM(CASE WHEN estado = 'completada' THEN 1 ELSE 0 END) as rutas_completadas
FROM rutas_distribucion
GROUP BY DATE(fecha_programada);

-- DATOS INICIALES Y CONFIGURACIÓN
-- ============================================

-- Configuraciones básicas del sistema
INSERT INTO configuraciones (clave, valor, descripcion, tipo, categoria) VALUES
-- Recolección
('tiempo_ventana_recoleccion_horas', '4', 'Ventana de tiempo para recolección', 'number', 'recoleccion'),
('max_pedidos_por_ruta_recoleccion', '15', 'Máximo pedidos por ruta de recolección', 'number', 'recoleccion'),
('max_peso_ruta_recoleccion_kg', '50', 'Peso máximo por ruta de recolección en kg', 'number', 'recoleccion'),

-- Bodega
('tiempo_maximo_bodega_dias', '7', 'Días máximo que un paquete puede estar en bodega', 'number', 'bodega'),
('capacidad_alerta_bodega_porcentaje', '80', 'Porcentaje de ocupación para alertas', 'number', 'bodega'),
('requiere_clasificacion_obligatoria', 'true', 'Si requiere clasificación obligatoria', 'boolean', 'bodega'),

-- Distribución
('max_pedidos_por_ruta_distribucion', '25', 'Máximo pedidos por ruta de distribución', 'number', 'distribucion'),
('max_intentos_entrega', '3', 'Máximo intentos de entrega', 'number', 'distribucion'),
('tiempo_ventana_entrega_horas', '8', 'Ventana de tiempo para entrega', 'number', 'distribucion'),

-- Costos
('comision_recoleccion_porcentaje', '5', 'Comisión para recolectores', 'number', 'financiero'),
('comision_distribucion_porcentaje', '8', 'Comisión para distribuidores', 'number', 'financiero'),
('comision_contraentrega_porcentaje', '2', 'Comisión por contraentrega', 'number', 'financiero'),

-- Notificaciones
('notificar_cliente_recoleccion', 'true', 'Notificar cliente al recolectar', 'boolean', 'notificaciones'),
('notificar_cliente_bodega', 'true', 'Notificar cliente ingreso a bodega', 'boolean', 'notificaciones'),
('notificar_cliente_distribucion', 'true', 'Notificar cliente en distribución', 'boolean', 'notificaciones'),

-- Seguimiento
('tracking_tiempo_real', 'true', 'Activar tracking en tiempo real', 'boolean', 'tracking'),
('intervalo_ubicacion_segundos', '30', 'Intervalo de envío de ubicación', 'number', 'tracking'),

-- Contacto
('telefono_soporte', '+57-1-234-5678', 'Teléfono de soporte', 'string', 'contacto'),
('email_soporte', 'soporte@logistica.com', 'Email de soporte', 'string', 'contacto'),
('whatsapp_soporte', '+57-300-123-4567', 'WhatsApp de soporte', 'string', 'contacto');

-- TRIGGERS PARA AUTOMATIZACIÓN
-- ============================================

DELIMITER //

-- Trigger para historial de pedidos
CREATE TRIGGER pedidos_historial_auto
    AFTER UPDATE ON pedidos
    FOR EACH ROW
BEGIN
    IF OLD.estado != NEW.estado THEN
        INSERT INTO pedidos_historial (
            pedido_id, estado_anterior, estado_nuevo, 
            fecha_cambio, personal_tipo
        ) VALUES (
            NEW.id, OLD.estado, NEW.estado, 
            NOW(), 'sistema'
        );
        
        -- Insertar evento de tracking
        INSERT INTO tracking_paquetes (
            pedido_id, ubicacion, evento, descripcion
        ) VALUES (
            NEW.id, 'Sistema', NEW.estado, 
            CONCAT('Cambio de estado: ', OLD.estado, ' → ', NEW.estado)
        );
    END IF;
END//

-- Trigger para actualizar capacidad de bodegas
CREATE TRIGGER actualizar_capacidad_bodega
    AFTER INSERT ON inventario_bodega
    FOR EACH ROW
BEGIN
    UPDATE bodegas 
    SET capacidad_actual = capacidad_actual + 1
    WHERE id = NEW.bodega_id;
    
    UPDATE secciones_bodega 
    SET capacidad_actual = capacidad_actual + 1
    WHERE id = NEW.seccion_id;
END//

-- Trigger para cuando sale paquete de bodega
CREATE TRIGGER salida_bodega_trigger
    AFTER UPDATE ON inventario_bodega
    FOR EACH ROW
BEGIN
    IF OLD.fecha_salida IS NULL AND NEW.fecha_salida IS NOT NULL THEN
        UPDATE bodegas 
        SET capacidad_actual = capacidad_actual - 1
        WHERE id = NEW.bodega_id;
        
        UPDATE secciones_bodega 
        SET capacidad_actual = capacidad_actual - 1
        WHERE id = NEW.seccion_id;
    END IF;
END//

-- Trigger para actualizar estadísticas de personal
CREATE TRIGGER actualizar_stats_personal
    AFTER UPDATE ON pedidos_ruta_distribucion
    FOR EACH ROW
BEGIN
    IF OLD.estado != NEW.estado AND NEW.estado = 'entregado' THEN
        -- Obtener el distribuidor de la ruta
        UPDATE personal_operativo po
        JOIN rutas_distribucion rd ON po.id = rd.distribuidor_id
        SET po.operaciones_exitosas = po.operaciones_exitosas + 1,
            po.total_operaciones = po.total_operaciones + 1
        WHERE rd.id = NEW.ruta_id;
    END IF;
END//

DELIMITER ;

-- ÍNDICES ADICIONALES PARA OPTIMIZACIÓN
-- ============================================

-- Índices compuestos para consultas frecuentes de reportes
CREATE INDEX idx_pedidos_estado_fecha_zona ON pedidos (estado, fecha_creacion, zona_destino);
CREATE INDEX idx_pedidos_bodega_estado ON pedidos (bodega_actual, estado, fecha_ingreso_bodega);
CREATE INDEX idx_rutas_fecha_estado ON rutas_recoleccion (fecha_programada, estado, zona);
CREATE INDEX idx_rutas_dist_fecha_estado ON rutas_distribucion (fecha_programada, estado, zona_destino);
CREATE INDEX idx_inventario_bodega_activo ON inventario_bodega (bodega_id, estado, fecha_salida);

-- Índices para tracking y ubicaciones
CREATE INDEX idx_tracking_pedido_evento ON tracking_paquetes (pedido_id, evento, timestamp);
CREATE INDEX idx_ubicaciones_personal_reciente ON ubicaciones_personal (personal_id, timestamp DESC, en_servicio);

-- COMENTARIOS Y DOCUMENTACIÓN
-- ============================================

/*
FLUJO COMPLETO DEL SISTEMA:

1. CREACIÓN DE PEDIDO
   - Cliente crea pedido → Estado: 'pendiente'
   - Sistema calcula tarifas y asigna códigos
   - Se programa recolección automática o manual

2. PROCESO DE RECOLECCIÓN
   - Se crean rutas de recolección optimizadas
   - Recolector recibe asignación → Estado: 'programado_recoleccion'
   - Recolector va por paquete → Estado: 'en_recoleccion'
   - Paquete recogido → Estado: 'recolectado'
   - Transporte a bodega → Estado: 'en_transito_bodega'

3. PROCESO DE BODEGA
   - Ingreso a bodega → Estado: 'en_bodega'
   - Clasificación y ubicación → Estado: 'clasificado'
   - Almacenamiento en sección específica
   - Preparación para despacho → Estado: 'programado_distribucion'

4. PROCESO DE DISTRIBUCIÓN
   - Creación de rutas optimizadas de distribución
   - Carga de paquetes en vehículo → Estado: 'en_ruta_distribucion'
   - Llegada a destino → Estado: 'en_destino'
   - Entrega exitosa → Estado: 'entregado'
   - O fallo en entrega → Estado: 'fallido'

5. CASOS ESPECIALES
   - Devolución → Estado: 'devuelto'
   - Cancelación → Estado: 'cancelado'

VENTAJAS DE ESTE DISEÑO:
✅ Separación clara entre recolección y distribución
✅ Control granular de inventario en bodega
✅ Optimización de rutas por separado
✅ Trazabilidad completa del paquete
✅ Flexibilidad para diferentes tipos de servicio
✅ Control de costos y comisiones detallado
✅ Sistema robusto de notificaciones
✅ Auditoría completa de operaciones
✅ Reportes y estadísticas avanzadas
✅ Escalable para múltiples bodegas
*/,
    INDEX idx_fecha_programada (fecha_programada)
) ENGINE=INNODB;

-- Historial detallado de estados
CREATE TABLE IF NOT EXISTS pedidos_historial (
    id INT AUTO_INCREMENT PRIMARY KEY,
    pedido_id INT NOT NULL,
    estado_anterior ENUM('pendiente','programado_recoleccion','en_recoleccion','recolectado','en_transito_bodega','en_bodega','clasificado','programado_distribucion','en_ruta_distribucion','en_destino','entregado','fallido','devuelto','cancelado'),
    estado_nuevo ENUM('pendiente','programado_recoleccion','en_recoleccion','recolectado','en_transito_bodega','en_bodega','clasificado','programado_distribucion','en_ruta_distribucion','en_destino','entregado','fallido','devuelto','cancelado') NOT NULL,
    fecha_cambio DATETIME DEFAULT CURRENT_TIMESTAMP,
    ubicacion VARCHAR(300), -- Donde ocurrió el cambio
    observaciones TEXT,
    personal_id INT, -- Quien realizó el cambio
    personal_tipo ENUM('administrador', 'recolector', 'operador_bodega', 'distribuidor', 'sistema') DEFAULT 'sistema',
    FOREIGN KEY (pedido_id) REFERENCES pedidos(id),
    INDEX idx_pedido (pedido_id),
    INDEX idx_fecha (fecha_cambio),
    INDEX idx_estado_nuevo (estado_nuevo)
) ENGINE=INNODB;

-- TABLAS DE OPERACIONES - RECOLECCIÓN
-- ============================================

-- Rutas de recolección programadas
CREATE TABLE IF NOT EXISTS rutas_recoleccion (
    id INT AUTO_INCREMENT PRIMARY KEY,
    codigo_ruta VARCHAR(20) NOT NULL, -- REC-BOG-001-20231201
    fecha_programada DATE NOT NULL,
    turno ENUM('manana', 'tarde', 'noche') DEFAULT 'manana',
    recolector_id INT NOT NULL,
    bodega_destino_id INT NOT NULL,
    zona VARCHAR(100),
    estado ENUM('programada', 'en_proceso', 'completada', 'cancelada') DEFAULT 'programada',
    
    -- Estadísticas de la ruta
    total_pedidos INT DEFAULT 0,
    pedidos_completados INT DEFAULT 0,
    pedidos_fallidos INT DEFAULT 0,
    distancia_estimada_km DECIMAL(8,2),
    tiempo_estimado_minutos INT,
    
    -- Control de tiempos
    hora_inicio_programada TIME,
    hora_fin_programada TIME,
    hora_inicio_real DATETIME,
    hora_fin_real DATETIME,
    
    -- Ubicación y seguimiento
    ruta_gps JSON, -- Coordenadas de la ruta planificada
    ruta_real_gps JSON, -- Coordenadas reales recorridas
    
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    created_by INT,
    updated_at DATETIME ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (recolector_id) REFERENCES personal_operativo(id),
    FOREIGN KEY (bodega_destino_id) REFERENCES bodegas(id),
    
    INDEX idx_fecha (fecha_programada),
    INDEX idx_recolector (recolector_id),
    INDEX idx_estado (estado),
    INDEX idx_zona (zona),
    UNIQUE KEY unique_codigo_ruta (codigo_ruta)
) ENGINE=INNODB;

-- Pedidos asignados a rutas de recolección
CREATE TABLE IF NOT EXISTS pedidos_ruta_recoleccion (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ruta_id INT NOT NULL,
    pedido_id INT NOT NULL,
    orden_visita INT, -- Orden en la ruta
    tiempo_estimado_minutos INT,
    estado ENUM('pendiente', 'en_proceso', 'completado', 'fallido') DEFAULT 'pendiente',
    hora_llegada DATETIME,
    hora_recoleccion DATETIME,
    observaciones TEXT,
    motivo_falla TEXT,
    evidencia_recoleccion VARCHAR(300), -- URL de foto
    
    FOREIGN KEY (ruta_id) REFERENCES rutas_recoleccion(id),
    FOREIGN KEY (pedido_id) REFERENCES pedidos(id),
    
    INDEX idx_ruta (ruta_id),
    INDEX idx_pedido (pedido_id),
    INDEX idx_orden (orden_visita),
    UNIQUE KEY unique_ruta_pedido (ruta_id, pedido_id)
) ENGINE=INNODB;

-- TABLAS DE OPERACIONES - BODEGA
-- ============================================

-- Registro de ingreso a bodega
CREATE TABLE IF NOT EXISTS ingresos_bodega (
    id INT AUTO_INCREMENT PRIMARY KEY,
    pedido_id INT NOT NULL,
    bodega_id INT NOT NULL,
    recolector_id INT NOT NULL,
    operador_recepcion_id INT, -- Quien recibe en bodega
    
    -- Detalles del ingreso
    fecha_ingreso DATETIME DEFAULT CURRENT_TIMESTAMP,
    peso_recibido_kg DECIMAL(8,3),
    dimensiones_recibidas JSON, -- {"alto": 10, "ancho": 20, "largo": 30}
    estado_paquete ENUM('perfecto', 'danado_leve', 'danado_grave', 'mojado', 'abierto') DEFAULT 'perfecto',
    observaciones_ingreso TEXT,
    fotos_ingreso JSON, -- URLs de fotos del paquete
    
    -- Clasificación
    seccion_asignada_id INT,
    fecha_clasificacion DATETIME,
    operador_clasificacion_id INT,
    prioridad_asignada ENUM('normal', 'urgente', 'express') DEFAULT 'normal',
    
    FOREIGN KEY (pedido_id) REFERENCES pedidos(id),
    FOREIGN KEY (bodega_id) REFERENCES bodegas(id),
    FOREIGN KEY (recolector_id) REFERENCES personal_operativo(id),
    FOREIGN KEY (operador_recepcion_id) REFERENCES personal_operativo(id),
    FOREIGN KEY (seccion_asignada_id) REFERENCES secciones_bodega(id),
    FOREIGN KEY (operador_clasificacion_id) REFERENCES personal_operativo(id),
    
    INDEX idx_pedido (pedido_id),
    INDEX idx_bodega (bodega_id),
    INDEX idx_fecha_ingreso (fecha_ingreso),
    INDEX idx_seccion (seccion_asignada_id)
) ENGINE=INNODB;

-- Inventario en tiempo real de bodegas
CREATE TABLE IF NOT EXISTS inventario_bodega (
    id INT AUTO_INCREMENT PRIMARY KEY,
    pedido_id INT NOT NULL,
    bodega_id INT NOT NULL,
    seccion_id INT NOT NULL,
    posicion_especifica VARCHAR(50), -- A1-15, B2-08, etc.
    
    fecha_ingreso DATETIME DEFAULT CURRENT_TIMESTAMP,
    fecha_salida DATETIME,
    estado ENUM('almacenado', 'en_preparacion', 'despachado') DEFAULT 'almacenado',
    
    -- Auditoría de inventario
    ultimo_conteo DATETIME,
    contado_por INT, -- Personal que hizo el conteo
    observaciones TEXT,
    
    FOREIGN KEY (pedido_id) REFERENCES pedidos(id),
    FOREIGN KEY (bodega_id) REFERENCES bodegas(id),
    FOREIGN KEY (seccion_id) REFERENCES secciones_bodega(id),
    FOREIGN KEY (contado_por) REFERENCES personal_operativo(id),
    
    INDEX idx_pedido (pedido_id),
    INDEX idx_bodega_seccion (bodega_id, seccion_id),
    INDEX idx_estado (estado),
    INDEX idx_posicion (posicion_especifica),
    UNIQUE KEY unique_pedido_activo (pedido_id, fecha_salida) -- Solo un registro activo por pedido
) ENGINE=INNODB;

-- TABLAS DE OPERACIONES - DISTRIBUCIÓN
-- ============================================

-- Rutas de distribución
CREATE TABLE IF NOT EXISTS rutas_distribucion (
    id INT AUTO_INCREMENT PRIMARY KEY,
    codigo_ruta VARCHAR(20) NOT NULL, -- DIST-BOG-001-20231201
    fecha_programada DATE NOT NULL,
    turno ENUM('manana', 'tarde', 'noche') DEFAULT 'manana',
    distribuidor_id INT NOT NULL,
    bodega_origen_id INT NOT NULL,
    zona_destino VARCHAR(100),
    tipo_vehiculo ENUM('bicicleta', 'motocicleta', 'vehiculo', 'camion') NOT NULL,
    
    -- Capacidades
    capacidad_maxima_paquetes INT,
    capacidad_maxima_peso_kg DECIMAL(8,2),
    paquetes_asignados INT DEFAULT 0,
    peso_total_kg DECIMAL(8,2) DEFAULT 0,
    
    estado ENUM('programada', 'cargando', 'en_ruta', 'completada', 'parcialmente_completada', 'cancelada') DEFAULT 'programada',
    
    -- Estadísticas
    total_pedidos INT DEFAULT 0,
    pedidos_entregados INT DEFAULT 0,
    pedidos_fallidos INT DEFAULT 0,
    pedidos_reprogramados INT DEFAULT 0,
    
    -- Tiempos
    hora_salida_programada TIME,
    hora_retorno_programada TIME,
    hora_salida_real DATETIME,
    hora_retorno_real DATETIME,
    
    -- Ruta y seguimiento
    ruta_planificada JSON, -- Coordenadas optimizadas
    ruta_real JSON, -- Ruta real seguida
    distancia_total_km DECIMAL(8,2),
    tiempo_total_minutos INT,
    
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    created_by INT,
    updated_at DATETIME ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (distribuidor_id) REFERENCES personal_operativo(id),
    FOREIGN KEY (bodega_origen_id) REFERENCES bodegas(id),
    
    INDEX idx_fecha (fecha_programada),
    INDEX idx_distribuidor (distribuidor_id),
    INDEX idx_estado (estado),
    INDEX idx_zona (zona_destino),
    UNIQUE KEY unique_codigo_ruta_dist (codigo_ruta)
) ENGINE=INNODB;

-- Pedidos asignados a rutas de distribución
CREATE TABLE IF NOT EXISTS pedidos_ruta_distribucion (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ruta_id INT NOT NULL,
    pedido_id INT NOT NULL,
    orden_entrega INT, -- Orden optimizado en la ruta
    tiempo_estimado_llegada TIME,
    
    estado ENUM('pendiente', 'en_camino', 'en_destino', 'entregado', 'fallido', 'reprogramado') DEFAULT 'pendiente',
    
    -- Control de entregas
    intentos_entrega INT DEFAULT 0,
    hora_llegada DATETIME,
    hora_entrega DATETIME,
    
    -- Información de entrega
    nombre_receptor VARCHAR(100),
    documento_receptor VARCHAR(20),
    parentesco_receptor VARCHAR(50),
    firma_receptor TEXT, -- Firma digitalizada en base64
    foto_entrega VARCHAR(300),
    observaciones_entrega TEXT,
    
    -- En caso de fallo
    motivo_fallo ENUM('destinatario_ausente', 'direccion_incorrecta', 'paquete_danado', 'negativa_recibo', 'zona_insegura', 'otro'),
    descripcion_fallo TEXT,
    evidencia_fallo VARCHAR(300),
    
    -- Reprogramación
    reprogramado_para DATE,
    motivo_reprogramacion TEXT,
    
    FOREIGN KEY (ruta_id) REFERENCES rutas_distribucion(id),
    FOREIGN KEY (pedido_id) REFERENCES pedidos(id),
    
    INDEX idx_ruta (ruta_id),
    INDEX idx_pedido (pedido_id),
    INDEX idx_orden (orden_entrega),
    INDEX idx_estado (estado),
    UNIQUE KEY unique_ruta_pedido_dist (ruta_id, pedido_id)
) ENGINE=INNODB;

-- TABLAS DE SOPORTE Y CONFIGURACIÓN
-- ============================================

CREATE TABLE IF NOT EXISTS zonas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    codigo VARCHAR(20) UNIQUE NOT NULL,
    nombre VARCHAR(100) NOT NULL UNIQUE,
    descripcion TEXT,
    poligono JSON, -- Coordenadas del polígono
    centro_lat DECIMAL(10, 8),
    centro_lng DECIMAL(11, 8),
    radio_km DECIMAL(5,2),
    
    -- Configuración operativa
    bodega_principal_id INT, -- Bodega que atiende esta zona
    tiempo_recoleccion_promedio_minutos INT DEFAULT 30,
    tiempo_entrega_promedio_minutos INT DEFAULT 45,
    
    -- Tarifas por zona
    tarifa_base DECIMAL(8,2),
    tarifa_por_kg DECIMAL(8,2),
    tarifa_por_km DECIMAL(8,2),
    recargo_zona_dificil DECIMAL(8,2) DEFAULT 0,
    
    activa BOOLEAN DEFAULT TRUE,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (bodega_principal_id) REFERENCES bodegas(id),
    
    INDEX idx_codigo (codigo),
    INDEX idx_nombre (nombre),
    INDEX idx_activa (activa),
    INDEX idx_bodega (bodega_principal_id)
) ENGINE=INNODB;

-- Tabla de tarifas más específica
CREATE TABLE IF NOT EXISTS tarifas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    zona_origen_id INT NOT NULL,
    zona_destino_id INT NOT NULL,
    tipo_servicio ENUM('estandar', 'express', 'mismo_dia', 'programado') NOT NULL,
    
    -- Rangos de peso y volumen
    peso_min_kg DECIMAL(8,3) DEFAULT 0,
    peso_max_kg DECIMAL(8,3),
    volumen_min_m3 DECIMAL(8,4) DEFAULT 0,
    volumen_max_m3 DECIMAL(8,4),
    
    -- Precios
    precio_base DECIMAL(10,2) NOT NULL,
    precio_por_kg_adicional DECIMAL(8,2) DEFAULT 0,
    precio_por_km DECIMAL(8,2) DEFAULT 0,
    
    -- Recargos
    recargo_fragil DECIMAL(8,2) DEFAULT 0,
    recargo_refrigeracion DECIMAL(8,2) DEFAULT 0,
    recargo_contraentrega DECIMAL(8,2) DEFAULT 0,
    recargo_declarado_porcentaje DECIMAL(5,2) DEFAULT 0, -- % del valor declarado
    
    -- Tiempo de entrega prometido
    tiempo_entrega_horas INT NOT NULL,
    
    -- Validez
    activa BOOLEAN DEFAULT TRUE,
    fecha_inicio DATE,
    fecha_fin DATE,
    
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (zona_origen_id) REFERENCES zonas(id),
    FOREIGN KEY (zona_destino_id) REFERENCES zonas(id),
    
    INDEX idx_zonas_servicio (zona_origen_id, zona_destino_id, tipo_servicio),
    INDEX idx_activa (activa),
    INDEX idx_peso (peso_min_kg, peso_max_kg),
    INDEX idx_fechas (fecha_inicio, fecha_fin)
) ENGINE=INNODB;

-- Configuraciones del sistema
CREATE TABLE IF NOT EXISTS configuraciones (
    id INT AUTO_INCREMENT PRIMARY KEY,
    clave VARCHAR(50) UNIQUE NOT NULL,
    valor TEXT NOT NULL,
    descripcion TEXT,
    tipo ENUM('string', 'number', 'boolean', 'json') DEFAULT 'string',
    categoria VARCHAR(50) DEFAULT 'general',
    editable BOOLEAN DEFAULT TRUE,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_clave (clave),
    INDEX idx_categoria (categoria)
) ENGINE=INNODB;

-- TABLAS DE COMUNICACIÓN Y SEGUIMIENTO
-- ============================================

CREATE TABLE IF NOT EXISTS notificaciones (
    id INT AUTO_INCREMENT PRIMARY KEY,
    destinatario_tipo ENUM('administrador', 'cliente', 'personal_operativo') NOT NULL,
    destinatario_id INT NOT NULL,
    
    titulo VARCHAR(200) NOT NULL,
    mensaje TEXT NOT NULL,
    tipo ENUM('pedido_creado', 'recoleccion_programada', 'paquete_recolectado', 'ingreso_bodega', 
              'listo_distribucion', 'en_ruta_entrega', 'paquete_entregado', 'entrega_fallida', 
              'pago', 'sistema', 'promocion', 'incidencia') NOT NULL,
    
    prioridad ENUM('baja', 'normal', 'alta', 'urgente') DEFAULT 'normal',
    
    -- Datos específicos según el tipo
    pedido_id INT,
    ruta_id INT,
    datos_adicionales JSON,
    
    -- Control de envío
    leida BOOLEAN DEFAULT FALSE,
    enviada BOOLEAN DEFAULT FALSE,
    push_enviado BOOLEAN DEFAULT FALSE,
    email_enviado BOOLEAN DEFAULT FALSE,
    sms_enviado BOOLEAN DEFAULT FALSE,
    whatsapp_enviado BOOLEAN DEFAULT FALSE,
    
    fecha_creacion DATETIME DEFAULT CURRENT_TIMESTAMP,
    fecha_lectura DATETIME NULL,
    fecha_envio DATETIME NULL,
    expira_en DATETIME NULL,
    
    INDEX idx_destinatario (destinatario_tipo, destinatario_id),
    INDEX idx_pedido (pedido_id),
    INDEX idx_leida (leida),
    INDEX idx_tipo (tipo),
    INDEX idx_prioridad (prioridad)