<?php
/**
 * logout.php — Cierra la sesion y regresa siempre a login.php
 * (requisito explicito de la guia del profesor)
 */
require_once __DIR__ . '/includes/config.php';

// Los datos de ejemplo (pedidos, facturas, etc.) viven en la sesion; los
// conservamos para que al entrar con otro usuario (ej. cocina) se sigan viendo.
$datosEjemplo = $_SESSION['datos'] ?? null;

$_SESSION = [];
session_unset();
session_destroy(); // destruye toda la informacion del usuario (rol, nombre, carrito, etc.)

// Abrimos una sesion limpia y le devolvemos solo los datos de ejemplo
session_start();
session_regenerate_id(true);
if ($datosEjemplo !== null) {
    $_SESSION['datos'] = $datosEjemplo;
}

header('Location: login.php');
exit;
