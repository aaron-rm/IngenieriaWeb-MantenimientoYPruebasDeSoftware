<?php
/*
 * Control de acceso por sesion y por rol
 *
 * Declarar un arreglo con los roles permitidos para la pagina actual:
 *
 *      $rolesPermitidos = ['cashier', 'supervisor', 'gerente'];
 *      require_once __DIR__ . '/../includes/auth-check.php';
 *
 * Si no se declara $rolesPermitidos, solo se exige que haya sesion
 * iniciada (cualquier rol puede entrar).
 *
 * Si no hay sesion -> redirige a login.php
 * Si el rol no esta permitido -> redirige a orders.php con mensaje
 *   (o a comandas.php si el rol es "comandas", para evitar un bucle)
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
        // El rol "comandas" no tiene acceso a orders.php; si lo mandamos ahi
        // se genera un bucle de redireccion infinito. Va a su propia pantalla.
        $destino = ($rolSesion === 'comandas') ? 'pages/comandas.php' : 'pages/orders.php';
        header('Location: ' . APP_URL . $destino);
        exit;
    }
}
