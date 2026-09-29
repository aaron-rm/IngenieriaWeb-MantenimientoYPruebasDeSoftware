<?php
/**
 * ============================================================
 * login.php — Formulario de inicio de sesion
 * Valida usuario y contrasena contra los usuarios de ejemplo
 * definidos en includes/datos.php (ya no se usa base de datos).
 * Al validar, guarda rol/nombre/seccion en la SESION y redirige
 * segun el rol:
 *    gerente / supervisor / cashier -> pages/orders.php
 *    comandas                       -> pages/comandas.php
 * ============================================================
 */
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/datos.php';

$error = null;

// Si el formulario fue enviado (POST), procesamos el login aqui mismo
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['usuario'] ?? '');
    $pass  = $_POST['password'] ?? '';

    if ($email === '' || $pass === '') {
        $error = 'Debes ingresar usuario y contrasena.';
    } else {
        // Buscamos el usuario en los datos de ejemplo
        $row = autenticarUsuario($email, $pass);

        if ($row) {
            // Credenciales correctas -> creamos la sesion
            $_SESSION['idUsuario'] = (int) $row['id_usuario'];
            $_SESSION['nombre']    = $row['nombre'];
            $_SESSION['rol']       = $row['rol'];
            // id_seccion es null para gerente/supervisor/comandas (no tienen seccion)
            $_SESSION['idSeccion']     = $row['id_seccion'] !== null ? (int) $row['id_seccion'] : null;
            $_SESSION['seccionNombre'] = $row['seccion_nombre'] ?? '';

            if ($row['rol'] === 'comandas') {
                header('Location: pages/comandas.php');
            } else {
                header('Location: pages/orders.php');
            }
            exit;
        } else {
            $error = 'Usuario o contrasena incorrectos.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- Titulo unico de esta pagina -->
    <title>Iniciar Sesion · MiPedido</title>
    <link rel="stylesheet" href="<?= CSS_URL ?>main.css">
    <link rel="stylesheet" href="<?= CSS_URL ?>layout.css">
    <link rel="stylesheet" href="<?= CSS_URL ?>login.css">
</head>
<body class="login-body">
  <?php require __DIR__ . '/includes/header.php'; ?>


  <div class="login-wrapper" style="grid-template-areas:'brand brand' 'card card'; grid-template-columns:1fr;">

    <div class="login-brand">
      <br>
    </div>

    <div class="login-card" style="max-width:420px;margin:0 auto;">
      <div class="card-logo">
        <div class="card-logo-text"><em>MiPedido<span class="dot">.</span></em></div>
        <div class="card-logo-sub">Restaurante MaLu</div>
      </div>

      <?php if ($error !== null): ?>
        <div class="alert-box alert-error">⚠ <?= htmlspecialchars($error) ?></div>
      <?php endif; ?>

      <!-- Formulario con letra/color distinto al body (ver login.css .field-group) -->
      <form method="post" action="login.php">
        <div class="field-group">
          <label for="usuario">Usuario · <span class="label-en">User</span></label>
          <input type="email" id="usuario" name="usuario" placeholder="usuario@malu.com" required>
        </div>
        <div class="field-group">
          <label for="password">Contrasena · <span class="label-en">Password</span></label>
          <input type="password" id="password" name="password" placeholder="********" required>
        </div>
        <button type="submit" class="btn-signin">Entrar · Sign in</button>
      </form>

      <p class="text-xs text-muted" style="margin-top:14px;text-align:center">
        ¿Aun no tienes cuenta? Contacta al Gerente General o al Supervisor.
      </p>
    </div>

  </div>
</body>
</html>
