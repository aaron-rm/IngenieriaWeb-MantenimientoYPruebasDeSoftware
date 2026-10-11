-- ============================================================
-- MiPedido · Restaurante MaLu
-- Script de creacion de base de datos para XAMPP (MySQL/MariaDB)
-- Como usarlo:
--   1. Abrir phpMyAdmin (http://localhost/phpmyadmin)
--   2. Pestana "SQL" -> pegar todo este archivo -> Ejecutar
--   Esto crea la base "mipedido_db" con todas sus tablas y datos de prueba.
-- ============================================================

-- Creamos la base de datos si no existe y la seleccionamos
CREATE DATABASE IF NOT EXISTS mipedido_db
  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE mipedido_db;

-- ------------------------------------------------------------
-- Tabla: secciones (Restaurante, Bar, etc.)
-- ------------------------------------------------------------
CREATE TABLE secciones (
  id_seccion   INT AUTO_INCREMENT PRIMARY KEY,
  nombre       VARCHAR(60) NOT NULL,
  estado       ENUM('activa','inactiva') NOT NULL DEFAULT 'activa'
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- Tabla: usuarios (login + roles + datos para "Sobre Nosotros")
-- rol: gerente | supervisor | cashier | comandas
-- ------------------------------------------------------------
CREATE TABLE usuarios (
  id_usuario    INT AUTO_INCREMENT PRIMARY KEY,
  nombre        VARCHAR(100) NOT NULL,
  cedula        VARCHAR(30)  NOT NULL,
  carrera       VARCHAR(100) DEFAULT NULL,
  email         VARCHAR(100) NOT NULL UNIQUE,
  password      VARCHAR(100) NOT NULL,      -- texto plano solo para fines academicos
  rol           ENUM('gerente','supervisor','cashier','comandas') NOT NULL,
  id_seccion    INT DEFAULT NULL,           -- seccion asignada (solo aplica a cashier)
  resumen_dev   VARCHAR(500) DEFAULT NULL,  -- resumen de experiencia (pagina Sobre Nosotros)
  foto_url      VARCHAR(255) DEFAULT NULL,
  activo        TINYINT(1) NOT NULL DEFAULT 1,
  CONSTRAINT fk_usuario_seccion FOREIGN KEY (id_seccion) REFERENCES secciones(id_seccion)
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- Tabla: categorias (Alimentos, Bebidas Alc., Bebidas No Alc., Otros)
-- pertenecen a una seccion
-- ------------------------------------------------------------
CREATE TABLE categorias (
  id_categoria  INT AUTO_INCREMENT PRIMARY KEY,
  nombre        VARCHAR(60) NOT NULL,
  id_seccion    INT NOT NULL,
  CONSTRAINT fk_categoria_seccion FOREIGN KEY (id_seccion) REFERENCES secciones(id_seccion)
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- Tabla: subcategorias (Entradas, Asados, Mariscos, Postres, etc.)
-- ------------------------------------------------------------
CREATE TABLE subcategorias (
  id_subcategoria INT AUTO_INCREMENT PRIMARY KEY,
  nombre          VARCHAR(60) NOT NULL,
  id_categoria    INT NOT NULL,
  CONSTRAINT fk_subcat_categoria FOREIGN KEY (id_categoria) REFERENCES categorias(id_categoria)
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- Tabla: productos
-- ------------------------------------------------------------
CREATE TABLE productos (
  id_producto     INT AUTO_INCREMENT PRIMARY KEY,
  nombre          VARCHAR(100) NOT NULL,
  precio          DECIMAL(8,2) NOT NULL,
  id_subcategoria INT NOT NULL,
  emoji           VARCHAR(10) DEFAULT '🍽️',
  mas_vendido     TINYINT(1) NOT NULL DEFAULT 0,
  activo          TINYINT(1) NOT NULL DEFAULT 1,
  CONSTRAINT fk_producto_subcat FOREIGN KEY (id_subcategoria) REFERENCES subcategorias(id_subcategoria)
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- Tabla: mesas
-- ------------------------------------------------------------
CREATE TABLE mesas (
  id_mesa    INT AUTO_INCREMENT PRIMARY KEY,
  numero     INT NOT NULL,
  capacidad  INT NOT NULL DEFAULT 4,
  estado     ENUM('libre','ocupada') NOT NULL DEFAULT 'libre'
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- Tabla: pedidos (comanda)
-- origen: rapido | mesa
-- estado: pendiente | en_preparacion | listo | entregado
-- ------------------------------------------------------------
CREATE TABLE pedidos (
  id_pedido     INT AUTO_INCREMENT PRIMARY KEY,
  numero_pedido INT NOT NULL,
  origen        ENUM('rapido','mesa') NOT NULL,
  id_mesa       INT DEFAULT NULL,
  comensal      VARCHAR(10) DEFAULT NULL,     -- ej: C1, C2
  id_usuario    INT NOT NULL,                 -- cashier que tomo el pedido
  id_seccion    INT NOT NULL,
  estado        ENUM('pendiente','en_preparacion','listo','entregado') NOT NULL DEFAULT 'pendiente',
  nota          VARCHAR(255) DEFAULT NULL,
  metodo_pago   ENUM('efectivo','yappy','tarjeta') DEFAULT NULL,
  total         DECIMAL(10,2) NOT NULL DEFAULT 0,
  cobrado       TINYINT(1) NOT NULL DEFAULT 0,
  fecha_hora    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_pedido_mesa    FOREIGN KEY (id_mesa) REFERENCES mesas(id_mesa),
  CONSTRAINT fk_pedido_usuario FOREIGN KEY (id_usuario) REFERENCES usuarios(id_usuario),
  CONSTRAINT fk_pedido_seccion FOREIGN KEY (id_seccion) REFERENCES secciones(id_seccion)
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- Tabla: pedido_detalle (platos dentro de cada comanda)
-- ------------------------------------------------------------
CREATE TABLE pedido_detalle (
  id_detalle   INT AUTO_INCREMENT PRIMARY KEY,
  id_pedido    INT NOT NULL,
  id_producto  INT NOT NULL,
  cantidad     INT NOT NULL DEFAULT 1,
  nota         VARCHAR(255) DEFAULT NULL,
  preparado    TINYINT(1) NOT NULL DEFAULT 0,  -- "tachado" en cocina
  CONSTRAINT fk_detalle_pedido   FOREIGN KEY (id_pedido) REFERENCES pedidos(id_pedido) ON DELETE CASCADE,
  CONSTRAINT fk_detalle_producto FOREIGN KEY (id_producto) REFERENCES productos(id_producto)
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- Tabla: facturas (se genera al cobrar un pedido)
-- ------------------------------------------------------------
CREATE TABLE facturas (
  id_factura   INT AUTO_INCREMENT PRIMARY KEY,
  id_pedido    INT NOT NULL,
  total        DECIMAL(10,2) NOT NULL,
  metodo_pago  ENUM('efectivo','yappy','tarjeta') NOT NULL,
  id_seccion   INT NOT NULL,
  anulada      TINYINT(1) NOT NULL DEFAULT 0,
  fecha_hora   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_factura_pedido  FOREIGN KEY (id_pedido) REFERENCES pedidos(id_pedido),
  CONSTRAINT fk_factura_seccion FOREIGN KEY (id_seccion) REFERENCES secciones(id_seccion)
) ENGINE=InnoDB;

-- ============================================================
-- DATOS DE PRUEBA (seed data)
-- ============================================================

-- Secciones
INSERT INTO secciones (nombre, estado) VALUES
 ('Restaurante','activa'),
 ('Bar','activa');

-- Usuarios (password en texto plano solo para fines academicos del curso)
-- IMPORTANTE: reemplacen nombre/cedula/carrera/resumen por los datos REALES del equipo
-- para la pagina "Sobre Nosotros".
INSERT INTO usuarios (nombre, cedula, carrera, email, password, rol, id_seccion, resumen_dev, foto_url) VALUES
 ('Nombre Gerente',    '8-000-0001','Ing. de Sistemas','gerente@malu.com',   'gerente123',   'gerente',    NULL, 'Encargado de la administracion general del restaurante y del analisis de reportes.', 'img/team/gerente.jpg'),
 ('Nombre Supervisor', '8-000-0002','Ing. de Sistemas','supervisor@malu.com','super123',     'supervisor', NULL, 'Supervisa la operacion diaria, el personal y las secciones del restaurante.', 'img/team/supervisor.jpg'),
 ('Ana Martinez',      '8-000-0003','Ing. de Sistemas','ana@malu.com',       'cashier123',   'cashier',    1,    'Encargada de la toma de pedidos en el area de Restaurante.', 'img/team/ana.jpg'),
 ('Jose Rodriguez',    '8-000-0004','Ing. de Sistemas','jose@malu.com',      'cashier123',   'cashier',    1,    'Encargado de la toma de pedidos en el area de Restaurante.', 'img/team/jose.jpg'),
 ('Luis Fernandez',    '8-000-0005','Ing. de Sistemas','luis@malu.com',      'cashier123',   'cashier',    2,    'Encargado de la toma de pedidos en el area de Bar.', 'img/team/luis.jpg'),
 ('Nombre Cocina',     '8-000-0006','Ing. de Sistemas','cocina@malu.com',    'cocina123',    'comandas',   NULL, 'Encargado de preparar y marcar el estado de las comandas en cocina.', 'img/team/cocina.jpg');

-- Categorias por seccion
INSERT INTO categorias (nombre, id_seccion) VALUES
 ('Alimentos','1'),        -- id 1  -> Restaurante
 ('Bebidas No Alc.','1'),  -- id 2  -> Restaurante
 ('Otros','1'),            -- id 3  -> Restaurante
 ('Bebidas Alc.','2'),     -- id 4  -> Bar
 ('Cocteles','2');         -- id 5  -> Bar

-- Subcategorias
INSERT INTO subcategorias (nombre, id_categoria) VALUES
 ('Entradas','1'), ('Asados','1'), ('Mariscos','1'), ('Postres','1'),   -- Alimentos (1-4)
 ('Gaseosas','2'), ('Aguas','2'), ('Jugos','2'),                        -- Bebidas No Alc. (5-7)
 ('Cafes','3'), ('Postres Extra','3'),                                  -- Otros (8-9)
 ('Cervezas','4'), ('Vinos','4'),                                       -- Bebidas Alc. (10-11)
 ('Cocteles Clasicos','5');                                             -- Cocteles (12)

-- Productos
INSERT INTO productos (nombre, precio, id_subcategoria, emoji, mas_vendido) VALUES
 ('Ceviche',          8.50, 3, '🐟', 1),
 ('Churrasco',        14.00,2, '🥩', 1),
 ('Corvina frita',    12.00,3, '🐠', 0),
 ('Patacones',        4.00, 1, '🫓', 0),
 ('Arroz mariscos',   11.50,3, '🍚', 0),
 ('Flan',             3.50, 4, '🍮', 0),
 ('Hamburguesa',      8.00, 2, '🍔', 0),
 ('Papas fritas',     3.00, 1, '🍟', 0),
 ('Ensalada',         5.50, 1, '🥗', 0),
 ('Coca-Cola',        1.50, 5, '🥤', 1),
 ('Agua mineral',     1.00, 6, '💧', 0),
 ('Jugo natural',     2.50, 7, '🍊', 0),
 ('Cafe',             1.50, 8, '☕', 0),
 ('Postre especial',  4.50, 9, '🍰', 0),
 ('Cerveza nacional', 2.50, 10,'🍺', 1),
 ('Vino tinto',       5.50, 11,'🍷', 0),
 ('Coctel Malu',      6.00, 12,'🍹', 0),
 ('Ron con cola',     5.00, 12,'🥃', 0);

-- Mesas
INSERT INTO mesas (numero, capacidad, estado) VALUES
 (1,4,'libre'), (2,4,'ocupada'), (3,2,'ocupada'), (4,2,'libre'),
 (5,6,'libre'), (6,4,'ocupada'), (7,4,'libre'), (8,2,'libre');

-- ============================================================
-- Fin del script
-- ============================================================
