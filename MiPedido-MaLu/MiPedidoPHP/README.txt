MiPedido · Restaurante MaLu — Version PHP (sin base de datos)
=============================================================

Sistema de pedidos para restaurante: login por rol, pedido rapido, mesas por
comensal, comandas de cocina, diario de ventas y administracion de secciones.

Esta version YA NO USA BASE DE DATOS (ni MySQL, ni PDO, ni scripts SQL).
Todo lo que antes venia de tablas ahora son datos de ejemplo definidos en
php/includes/datos.php.

ESTRUCTURA DE CARPETAS
-----------------------
/html/   -> index.html (pagina estatica de entrada; redirige a php/home.php)
/php/    -> paginas .php + includes/ (config, datos de ejemplo, header, footer)
/css/    -> hojas de estilo
/img/    -> imagenes (logo, fotos del equipo)

COMO EJECUTARLO
----------------
Opcion A — XAMPP (solo Apache, MySQL ya no hace falta):
  1. Copia la carpeta "MiPedidoPHP" dentro de htdocs (C:\xampp\htdocs\MiPedidoPHP).
  2. Enciende Apache desde el Panel de Control de XAMPP.
  3. Abre http://localhost/MiPedidoPHP/html/index.html
     (o directo a http://localhost/MiPedidoPHP/php/home.php)

Opcion B — Servidor integrado de PHP (7.4 o superior):
  1. Abre una terminal dentro de la carpeta MiPedidoPHP.
  2. Ejecuta:  php -S localhost:8000
  3. Abre http://localhost:8000/html/index.html

USUARIOS DE EJEMPLO
--------------------
gerente@malu.com    / gerente123   (rol: gerente)
supervisor@malu.com / super123     (rol: supervisor)
ana@malu.com        / cashier123   (rol: cashier, seccion Restaurante)
jose@malu.com       / cashier123   (rol: cashier, seccion Restaurante)
luis@malu.com       / cashier123   (rol: cashier, seccion Bar)
cocina@malu.com     / cocina123    (rol: comandas)

DATOS DE EJEMPLO
-----------------
Se crean automaticamente la primera vez que se abre la aplicacion:
  - 2 secciones (Restaurante y Bar), 5 categorias, 12 subcategorias y
    18 productos con precio y emoji.
  - 8 mesas (algunas ocupadas).
  - 10 pedidos de hoy: 2 en cocina (uno pendiente y uno en preparacion),
    1 "listo" esperando cobro y 7 ya cobrados (con su factura), para que el
    Diario de Ventas y las Comandas tengan contenido desde el inicio.
  - El equipo de "Sobre Nosotros" (php/includes/datos.php -> listarEquipo()).
    Cambia nombres, cedulas, carrera y resumen por los datos reales.

Para cambiar productos, precios, usuarios, mesas o el equipo, edita las
listas dentro de datosIniciales() y listarEquipo() en php/includes/datos.php.

COMO FUNCIONA LA PERSISTENCIA
------------------------------
Los datos viven en la SESION de PHP ($_SESSION['datos']). Por eso:
  - Todo sigue funcionando: enviar comandas, tachar platos en cocina, cobrar,
    crear secciones/categorias, asignar cajeros, etc.
  - Los cambios duran mientras la sesion del navegador siga abierta. Al cerrar
    sesion (logout) se conservan, asi puedes entrar como cajero, enviar una
    comanda y luego entrar como cocina para verla.
  - Cada navegador tiene sus propios datos: dos navegadores/dispositivos
    distintos NO comparten pedidos. Para probar el ciclo caja -> cocina usa el
    mismo navegador (cerrando sesion y entrando con el otro usuario).
  - Para volver a los datos de ejemplo originales, cierra el navegador (o borra
    las cookies del sitio).

NOTAS
------
- La contrasena de los usuarios esta en texto plano en datos.php solo con fines
  academicos; en produccion usa password_hash()/password_verify().
- El "contextPath" de JSP se resuelve con la constante BASE_URL calculada en
  php/includes/config.php, asi los enlaces funcionan en cualquier subcarpeta.
