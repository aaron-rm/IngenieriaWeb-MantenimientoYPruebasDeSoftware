
<?php
session_start();

require_once __DIR__ . '/datos.php';

// Restaurar los datos iniciales
$_SESSION['datos'] = datosIniciales();

echo 'Datos reiniciados correctamente.';
