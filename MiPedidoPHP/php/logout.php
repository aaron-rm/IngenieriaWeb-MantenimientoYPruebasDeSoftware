<?php
/**
 * ============================================================
 * logout.php — Cierra la sesion y regresa siempre a login.php
 * (requisito explicito de la guia del profesor)
 * ============================================================
 */
require_once __DIR__ . '/includes/config.php';

$_SESSION = [];
session_unset();
session_destroy(); // destruye toda la informacion de la sesion (rol, usuario, carrito, etc.)
header('Location: login.php');
exit;
