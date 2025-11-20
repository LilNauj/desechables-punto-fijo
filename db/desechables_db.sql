-- ============================================
-- BASE DE DATOS: DESECHABLES PUNTO FIJO
-- Proyecto: Sistema de Ventas de Desechables
-- ============================================

-- Crear la base de datos
CREATE DATABASE IF NOT EXISTS desechables_punto_fijo;
USE desechables_punto_fijo;

-- ============================================
-- TABLA: usuarios
-- Almacena información de clientes y administradores
-- ============================================
CREATE TABLE usuarios (
    id INT PRIMARY KEY AUTO_INCREMENT,
    nombre VARCHAR(100) NOT NULL,
    apellido VARCHAR(100) NOT NULL,
    email VARCHAR(150) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    telefono VARCHAR(20),
    direccion TEXT,
    rol ENUM('cliente', 'admin') DEFAULT 'cliente',
    estado ENUM('activo', 'inactivo') DEFAULT 'activo',
    fecha_registro TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    ultima_sesion TIMESTAMP NULL,
    INDEX idx_email (email),
    INDEX idx_rol (rol)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- TABLA: categorias
-- Categorías de productos (icopor, contenedores, etc.)
-- ============================================
CREATE TABLE categorias (
    id INT PRIMARY KEY AUTO_INCREMENT,
    nombre VARCHAR(100) NOT NULL,
    descripcion TEXT,
    imagen VARCHAR(255),
    estado ENUM('activo', 'inactivo') DEFAULT 'activo',
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_nombre (nombre)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- TABLA: productos
-- Catálogo completo de productos
-- ============================================
CREATE TABLE productos (
    id INT PRIMARY KEY AUTO_INCREMENT,
    categoria_id INT NOT NULL,
    nombre VARCHAR(200) NOT NULL,
    descripcion TEXT,
    precio DECIMAL(10, 2) NOT NULL,
    stock INT NOT NULL DEFAULT 0,
    imagen VARCHAR(255),
    codigo_producto VARCHAR(50) UNIQUE,
    unidad_medida ENUM('unidad', 'paquete', 'caja', 'docena') DEFAULT 'unidad',
    estado ENUM('disponible', 'agotado', 'descontinuado') DEFAULT 'disponible',
    destacado BOOLEAN DEFAULT FALSE,
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    fecha_actualizacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (categoria_id) REFERENCES categorias(id) ON DELETE CASCADE,
    INDEX idx_categoria (categoria_id),
    INDEX idx_nombre (nombre),
    INDEX idx_precio (precio),
    INDEX idx_destacado (destacado)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- TABLA: carrito
-- Carrito de compras temporal de cada usuario
-- ============================================
CREATE TABLE carrito (
    id INT PRIMARY KEY AUTO_INCREMENT,
    usuario_id INT NOT NULL,
    producto_id INT NOT NULL,
    cantidad INT NOT NULL DEFAULT 1,
    fecha_agregado TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    FOREIGN KEY (producto_id) REFERENCES productos(id) ON DELETE CASCADE,
    UNIQUE KEY unique_usuario_producto (usuario_id, producto_id),
    INDEX idx_usuario (usuario_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- TABLA: ventas
-- Registro de todas las ventas realizadas
-- ============================================
CREATE TABLE ventas (
    id INT PRIMARY KEY AUTO_INCREMENT,
    usuario_id INT NOT NULL,
    total DECIMAL(10, 2) NOT NULL,
    subtotal DECIMAL(10, 2) NOT NULL,
    impuesto DECIMAL(10, 2) DEFAULT 0,
    metodo_pago ENUM('efectivo', 'tarjeta', 'transferencia', 'nequi', 'daviplata') NOT NULL,
    estado ENUM('pendiente', 'procesando', 'completada', 'cancelada') DEFAULT 'pendiente',
    direccion_entrega TEXT,
    notas TEXT,
    fecha_venta TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    fecha_entrega TIMESTAMP NULL,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    INDEX idx_usuario (usuario_id),
    INDEX idx_fecha (fecha_venta),
    INDEX idx_estado (estado)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- TABLA: detalle_ventas
-- Detalle de productos por cada venta
-- ============================================
CREATE TABLE detalle_ventas (
    id INT PRIMARY KEY AUTO_INCREMENT,
    venta_id INT NOT NULL,
    producto_id INT NOT NULL,
    cantidad INT NOT NULL,
    precio_unitario DECIMAL(10, 2) NOT NULL,
    subtotal DECIMAL(10, 2) NOT NULL,
    FOREIGN KEY (venta_id) REFERENCES ventas(id) ON DELETE CASCADE,
    FOREIGN KEY (producto_id) REFERENCES productos(id) ON DELETE CASCADE,
    INDEX idx_venta (venta_id),
    INDEX idx_producto (producto_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- TABLA: historial_stock
-- Registro de cambios en el inventario
-- ============================================
CREATE TABLE historial_stock (
    id INT PRIMARY KEY AUTO_INCREMENT,
    producto_id INT NOT NULL,
    cantidad_anterior INT NOT NULL,
    cantidad_nueva INT NOT NULL,
    tipo_movimiento ENUM('entrada', 'salida', 'ajuste', 'venta') NOT NULL,
    usuario_id INT,
    observaciones TEXT,
    fecha_movimiento TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (producto_id) REFERENCES productos(id) ON DELETE CASCADE,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE SET NULL,
    INDEX idx_producto (producto_id),
    INDEX idx_fecha (fecha_movimiento)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- INSERTAR DATOS DE EJEMPLO
-- ============================================

-- Usuario administrador por defecto
-- Password: admin123 (en producción usar hash bcrypt)
INSERT INTO usuarios (nombre, apellido, email, password, telefono, rol) VALUES
('Admin', 'Sistema', 'admin@puntofijo.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '3177268740', 'admin'),
('Cliente', 'Demo', 'cliente@demo.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '3157441535', 'cliente');

-- Categorías principales
INSERT INTO categorias (nombre, descripcion) VALUES
('Icopor', 'Productos de icopor para alimentos y bebidas'),
('Contenedores', 'Contenedores plásticos para alimentos'),
('Vasos y Jarras', 'Vasos desechables de diferentes tamaños'),
('Platos y Bandejas', 'Platos y bandejas desechables'),
('Cubiertos', 'Cubiertos plásticos desechables'),
('Bolsas', 'Bolsas plásticas de diferentes tamaños'),
('Empaques', 'Empaques y envolturas diversas'),
('Servilletas', 'Servilletas de papel y decoración');

-- Productos de ejemplo
INSERT INTO productos (categoria_id, nombre, descripcion, precio, stock, codigo_producto, unidad_medida, destacado) VALUES
(1, 'Vaso Térmico Icopor 8oz', 'Vaso térmico de icopor ideal para bebidas calientes', 150.00, 500, 'ICO-VAS-8OZ', 'paquete', TRUE),
(1, 'Vaso Térmico Icopor 12oz', 'Vaso térmico de icopor de 12 onzas', 180.00, 400, 'ICO-VAS-12OZ', 'paquete', TRUE),
(1, 'Bandeja Icopor Grande', 'Bandeja de icopor para alimentos, tamaño grande', 250.00, 300, 'ICO-BAN-GDE', 'paquete', FALSE),
(2, 'Contenedor Plástico 500ml', 'Contenedor transparente con tapa 500ml', 300.00, 250, 'CON-PLA-500', 'paquete', TRUE),
(2, 'Contenedor Plástico 1000ml', 'Contenedor transparente con tapa 1000ml', 450.00, 200, 'CON-PLA-1000', 'paquete', FALSE),
(3, 'Vasos Plásticos 7oz x50', 'Paquete de 50 vasos plásticos transparentes', 120.00, 600, 'VAS-PLA-7OZ', 'paquete', TRUE),
(3, 'Vasos Plásticos 9oz x50', 'Paquete de 50 vasos plásticos de 9oz', 150.00, 500, 'VAS-PLA-9OZ', 'paquete', FALSE),
(4, 'Platos Plásticos Pequeños x50', 'Platos desechables blancos pequeños', 180.00, 400, 'PLA-PLA-PEQ', 'paquete', FALSE),
(4, 'Platos Plásticos Grandes x50', 'Platos desechables blancos grandes', 250.00, 350, 'PLA-PLA-GDE', 'paquete', TRUE),
(5, 'Cubiertos Plásticos x100', 'Set completo: tenedores, cuchillos y cucharas', 200.00, 450, 'CUB-SET-100', 'paquete', FALSE),
(6, 'Bolsas Camiseta Pequeñas x100', 'Bolsas tipo camiseta biodegradables', 80.00, 800, 'BOL-CAM-PEQ', 'paquete', FALSE),
(6, 'Bolsas Camiseta Grandes x100', 'Bolsas tipo camiseta grandes', 120.00, 600, 'BOL-CAM-GDE', 'paquete', TRUE),
(7, 'Papel Aluminio Rollo', 'Rollo de papel aluminio para alimentos', 350.00, 150, 'PAP-ALU-ROL', 'unidad', FALSE),
(8, 'Servilletas Blancas x100', 'Servilletas de papel blancas', 50.00, 1000, 'SER-BLA-100', 'paquete', FALSE);

-- ============================================
-- VISTAS ÚTILES
-- ============================================

-- Vista de productos con información de categoría
CREATE VIEW vista_productos_completa AS
SELECT 
    p.id,
    p.nombre AS producto,
    p.descripcion,
    p.precio,
    p.stock,
    p.codigo_producto,
    p.unidad_medida,
    p.estado,
    p.destacado,
    c.nombre AS categoria,
    c.id AS categoria_id
FROM productos p
INNER JOIN categorias c ON p.categoria_id = c.id;

-- Vista de ventas con información del cliente
CREATE VIEW vista_ventas_completa AS
SELECT 
    v.id,
    v.total,
    v.metodo_pago,
    v.estado,
    v.fecha_venta,
    u.nombre AS cliente_nombre,
    u.apellido AS cliente_apellido,
    u.email AS cliente_email,
    u.telefono AS cliente_telefono
FROM ventas v
INNER JOIN usuarios u ON v.usuario_id = u.id;

-- ============================================
-- TRIGGERS
-- ============================================

-- Trigger para registrar cambios en el stock cuando hay una venta
DELIMITER //
CREATE TRIGGER after_detalle_venta_insert
AFTER INSERT ON detalle_ventas
FOR EACH ROW
BEGIN
    DECLARE stock_actual INT;
    
    -- Obtener stock actual
    SELECT stock INTO stock_actual FROM productos WHERE id = NEW.producto_id;
    
    -- Actualizar stock del producto
    UPDATE productos 
    SET stock = stock - NEW.cantidad 
    WHERE id = NEW.producto_id;
    
    -- Registrar en historial de stock
    INSERT INTO historial_stock (producto_id, cantidad_anterior, cantidad_nueva, tipo_movimiento, observaciones)
    VALUES (NEW.producto_id, stock_actual, stock_actual - NEW.cantidad, 'venta', CONCAT('Venta #', NEW.venta_id));
END//
DELIMITER ;

-- ============================================
-- PROCEDIMIENTOS ALMACENADOS
-- ============================================

-- Procedimiento para obtener productos más vendidos
DELIMITER //
CREATE PROCEDURE sp_productos_mas_vendidos(IN limite INT)
BEGIN
    SELECT 
        p.id,
        p.nombre,
        p.precio,
        SUM(dv.cantidad) AS total_vendido,
        SUM(dv.subtotal) AS ingresos_generados
    FROM productos p
    INNER JOIN detalle_ventas dv ON p.id = dv.producto_id
    GROUP BY p.id, p.nombre, p.precio
    ORDER BY total_vendido DESC
    LIMIT limite;
END//
DELIMITER ;

-- Procedimiento para obtener ventas por período
DELIMITER //
CREATE PROCEDURE sp_ventas_por_periodo(IN fecha_inicio DATE, IN fecha_fin DATE)
BEGIN
    SELECT 
        DATE(fecha_venta) AS fecha,
        COUNT(*) AS total_ventas,
        SUM(total) AS ingresos_totales,
        AVG(total) AS ticket_promedio
    FROM ventas
    WHERE DATE(fecha_venta) BETWEEN fecha_inicio AND fecha_fin
    AND estado = 'completada'
    GROUP BY DATE(fecha_venta)
    ORDER BY fecha;
END//
DELIMITER ;

-- ============================================
-- ÍNDICES ADICIONALES PARA OPTIMIZACIÓN
-- ============================================

-- Índices compuestos para consultas frecuentes
CREATE INDEX idx_producto_categoria_estado ON productos(categoria_id, estado);
CREATE INDEX idx_venta_usuario_fecha ON ventas(usuario_id, fecha_venta);
CREATE INDEX idx_venta_estado_fecha ON ventas(estado, fecha_venta);

-- ============================================
-- SCRIPT COMPLETADO
-- ============================================
-- Para importar este script:
-- 1. Abre phpMyAdmin
-- 2. Ve a la pestaña "SQL"
-- 3. Copia y pega todo este código
-- 4. Haz clic en "Continuar"
-- ============================================