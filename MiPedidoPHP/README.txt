MiPedido · Restaurante MaLu — Version PHP
==========================================

Conversion de JSP a PHP + HTML + CSS, con la misma logica de negocio
(login por rol, pedido rapido, mesas por comensal, comandas de cocina,
diario de ventas y administracion de secciones).

ESTRUCTURA DE CARPETAS
-----------------------
/html/   -> index.html (pagina 100% estatica de entrada; redirige a php/home.php)
/php/    -> toda la logica dinamica (paginas .php + includes/)
/css/    -> hojas de estilo (idénticas a las originales, sin cambios)
/img/    -> imagenes (logo, fotos del equipo)
/sql/    -> schema.sql (mismo script para crear la base "mipedido_db")

INSTALACION EN XAMPP
---------------------
1. Copia toda la carpeta "MiPedidoPHP" dentro de htdocs, por ejemplo:
   C:\xampp\htdocs\MiPedidoPHP

2. Enciende Apache y MySQL desde el Panel de Control de XAMPP.

3. Abre http://localhost/phpmyadmin, pestaña "SQL", pega el contenido
   de sql/schema.sql y ejecutalo (crea la base "mipedido_db" con
   datos de prueba, igual que en la version JSP).

4. Verifica los datos de conexion en php/includes/db.php si tu XAMPP
   no usa el usuario/host por defecto (root, sin password).

5. Abre en el navegador:
   http://localhost/MiPedidoPHP/html/index.html
   (o directo a http://localhost/MiPedidoPHP/php/home.php)

USUARIOS DE PRUEBA (mismos que en el schema.sql)
--------------------------------------------------
gerente@malu.com    / gerente123   (rol: gerente)
supervisor@malu.com / super123     (rol: supervisor)
ana@malu.com        / cashier123   (rol: cashier, seccion Restaurante)
jose@malu.com       / cashier123   (rol: cashier, seccion Restaurante)
luis@malu.com       / cashier123   (rol: cashier, seccion Bar)
cocina@malu.com     / cocina123    (rol: comandas)

NOTAS DE LA CONVERSION
------------------------
- Cada scriptlet Java (<% ... %>) se tradujo a PHP puro, manteniendo
  la misma logica: sesiones ($_SESSION en vez de session.getAttribute),
  patron Post/Redirect/Get, transacciones (PDO beginTransaction /
  commit / rollBack en vez de con.setAutoCommit(false)).
- Las consultas SQL usan PDO con sentencias preparadas (mismo nivel
  de proteccion contra inyeccion SQL que los PreparedStatement de JDBC).
- El "contextPath" de JSP se resolvio con una constante BASE_URL
  calculada dinamicamente en php/includes/config.php, para que los
  enlaces del header/footer funcionen sin importar en que subcarpeta
  quede instalado el proyecto.
- La tabla `usuarios` guarda password en texto plano, tal como en el
  original (asi lo dejo el proyecto JSP, marcado como "solo con fines
  academicos"); si vas a usarlo en produccion, cambia esto a
  password_hash()/password_verify().
