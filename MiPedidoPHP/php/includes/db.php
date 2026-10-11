<?php
/**
 * ============================================================
 * db.php — Conexion a la base de datos MySQL (XAMPP)
 * ------------------------------------------------------------
 * Se incluye con require_once __DIR__ . '/includes/db.php'
 * (o '../includes/db.php' dentro de php/pages/) en cada pagina
 * que necesite hablar con la base de datos.
 *
 * Expone la funcion getConnection() que cualquier pagina puede
 * usar asi:
 *      $pdo = getConnection();
 *      $stmt = $pdo->prepare("SELECT ...");
 *      $stmt->execute([...]);
 *
 * Datos de conexion por defecto de XAMPP:
 *      host: localhost   puerto: 3306   usuario: root   password: (vacio)
 * Si tu XAMPP tiene otra configuracion, cambia las 5 constantes de abajo.
 * ============================================================
 */

// --- Datos de conexion a MySQL/XAMPP ---
define('DB_HOST', 'localhost');
define('DB_PORT', '3306');
define('DB_NAME', 'mipedido_db');
define('DB_USER', 'root');
define('DB_PASS', ''); // XAMPP no trae password por defecto

/**
 * Devuelve una conexion PDO nueva a la base de datos.
 * Lanza una Exception si no se puede conectar (mismo comportamiento
 * que el metodo getConnection() del db.jsp original).
 */
function getConnection(): PDO {
    $dsn = 'mysql:host=' . DB_HOST . ';port=' . DB_PORT . ';dbname=' . DB_NAME . ';charset=utf8mb4';
    try {
        return new PDO($dsn, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]);
    } catch (PDOException $e) {
        throw new Exception(
            "No se encontro conexion con MySQL (verifica que XAMPP este encendido " .
            "y que la base 'mipedido_db' exista). Detalle: " . $e->getMessage()
        );
    }
}
