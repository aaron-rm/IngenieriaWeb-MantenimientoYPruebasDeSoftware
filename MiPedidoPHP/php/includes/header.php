<?php
/**
 * ============================================================
 * header.php — Banner + menu de navegacion superior
 * ------------------------------------------------------------
 * Incluye: logo de la organizacion, icono de busqueda (enlaza a
 * Google.com), iconos de redes sociales, y el menu de navegacion
 * que cambia segun el ROL guardado en la sesion.
 *
 * Se debe incluir justo despues de abrir <body>:
 *      require __DIR__ . '/includes/header.php';
 * ============================================================
 */
require_once __DIR__ . '/config.php';

// Datos de la sesion actual (pueden venir nulos si es una pagina publica como Home)
$rolActual   = $_SESSION['rol'] ?? null;
$nombreUser  = $_SESSION['nombre'] ?? null;
$seccionUser = $_SESSION['seccionNombre'] ?? '';
$logueado    = ($rolActual !== null);
?>
<!-- ===================== BANNER SUPERIOR ===================== -->
<header class="site-banner">
    <a href="<?= APP_URL ?>home.php" class="brand-logo-link">
        <img src="<?= IMG_URL ?>logo-malu.png" alt="Logo Restaurante MaLu" class="brand-logo-img">
        <span class="brand-logo-text">Mi<span class="dot-pink">Pedido</span></span>
    </a>

    <div class="banner-right">
        <!-- Icono de busqueda: enlaza a Google -->
        <a href="https://www.google.com" target="_blank" rel="noopener" class="banner-icon" title="Buscar en Google"><p>Google</p></a>
        <!-- Iconos de redes sociales  -->
        <a href="https://www.facebook.com" target="_blank" rel="noopener" class="banner-icon" title="Facebook">Facebook</a>
        <a href="https://www.instagram.com" target="_blank" rel="noopener" class="banner-icon" title="Instagram">Instagram</a>

        <?php if ($logueado): ?>
            <span class="banner-user">
                <strong><?= htmlspecialchars($nombreUser) ?></strong> · <?= htmlspecialchars(strtoupper($rolActual)) ?>
                <?php if ($seccionUser !== ''): ?> · <?= htmlspecialchars($seccionUser) ?><?php endif; ?>
            </span>
            <a href="<?= APP_URL ?>logout.php" class="btn-logout-top">Salir</a>
        <?php else: ?>
            <a href="<?= APP_URL ?>login.php" class="btn-logout-top">Iniciar sesion</a>
        <?php endif; ?>
    </div>
</header>

<!-- ===================== MENU DE NAVEGACION ===================== -->
<nav class="main-nav">
    <?php if (!$logueado): ?>
        <!-- Menu publico: Home / Sobre Nosotros -->
        <a href="<?= APP_URL ?>home.php" class="main-nav-item">Home</a>
        <a href="<?= APP_URL ?>sobre-nosotros.php" class="main-nav-item">Sobre Nosotros</a>
        <a href="<?= APP_URL ?>login.php" class="main-nav-item">Iniciar sesion</a>
    <?php else: ?>
        <a href="<?= APP_URL ?>home.php" class="main-nav-item">Home</a>
        <a href="<?= APP_URL ?>sobre-nosotros.php" class="main-nav-item">Sobre Nosotros</a>
        <a href="<?= APP_URL ?>pages/orders.php" class="main-nav-item">Ordenes</a>
        <a href="<?= APP_URL ?>pages/comandas.php" class="main-nav-item">Comandas</a>

        <?php if ($rolActual !== 'comandas'): ?>
            <a href="<?= APP_URL ?>pages/ventas.php" class="main-nav-item">Diario de Ventas</a>
        <?php endif; ?>

        <?php if ($rolActual === 'supervisor' || $rolActual === 'gerente'): ?>
            <a href="<?= APP_URL ?>pages/secciones.php" class="main-nav-item">Secciones</a>
        <?php endif; ?>

        <?php if ($rolActual === 'gerente'): ?>
            <span class="main-nav-item beta" title="Disponible en una version mas avanzada">Reportes β</span>
        <?php endif; ?>
    <?php endif; ?>
</nav>
