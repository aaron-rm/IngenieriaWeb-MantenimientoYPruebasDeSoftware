<?php
/**
 * ============================================================
 * config.php — Arranque comun de la aplicacion
 * ------------------------------------------------------------
 * - Inicia la sesion (equivalente al objeto "session" implicito
 *   de JSP).
 * - Calcula BASE_URL: la ruta del proyecto en el servidor,
 *   para poder generar enlaces validos sin importar en que
 *   carpeta (php/ o php/pages/) este el archivo actual.
 *   Es el equivalente a request.getContextPath() en JSP.
 *
 * Se debe incluir con: require_once __DIR__ . '/includes/config.php';
 * (o '../includes/config.php' dentro de php/pages/)
 * ============================================================
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

date_default_timezone_set('America/Panama');

// Detectamos la carpeta raiz del proyecto (la que contiene php/, css/, html/, img/)
// a partir de la URL del script actual, sin importar la profundidad.
$scriptName = $_SERVER['SCRIPT_NAME'];
$pos = strpos($scriptName, '/php/');
$BASE = ($pos !== false) ? substr($scriptName, 0, $pos) . '/' : '/';

define('BASE_URL', $BASE);          // ej: "/MiPedidoPHP/"
define('CSS_URL', $BASE . 'css/');  // ej: "/MiPedidoPHP/css/"
define('IMG_URL', $BASE . 'img/');  // ej: "/MiPedidoPHP/img/"
define('APP_URL', $BASE . 'php/');  // ej: "/MiPedidoPHP/php/"
