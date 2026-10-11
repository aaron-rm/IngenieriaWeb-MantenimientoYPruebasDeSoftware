<?php
/**
 * ============================================================
 * auth-check.php — Control de acceso por sesion y por rol
 * ------------------------------------------------------------
 * Como usarlo: ANTES de incluir este archivo, declarar (opcional)
 * un arreglo con los roles permitidos para la pagina actual:
 *
 *      $rolesPermitidos = ['cashier', 'supervisor', 'gerente'];
 *      require_once __DIR__ . '/../includes/auth-check.php';
 *
 * Si no se declara $rolesPermitidos, solo se exige que haya sesion
 * iniciada (cualquier rol puede entrar).
 *
 * Si no hay sesion -> redirige a login.php
 * Si el rol no esta permitido -> redirige a orders.php con mensaje
 * ============================================================
 */

require_once __DIR__ . '/config.php';

// Verificamos que exista una sesion activa con usuario logueado
$rolSesion = $_SESSION['rol'] ?? null;

if ($rolSesion === null) {
    // No hay sesion -> lo mandamos al login
    header('Location: ' . APP_URL . 'login.php');
    exit;
}

// Si la pagina definio una lista de roles permitidos, validamos
if (isset($rolesPermitidos) && is_array($rolesPermitidos)) {
    if (!in_array($rolSesion, $rolesPermitidos, true)) {
        $_SESSION['mensajeError'] = 'No tienes permiso para acceder a esa seccion.';
        header('Location: ' . APP_URL . 'pages/orders.php');
        exit;
    }
}
