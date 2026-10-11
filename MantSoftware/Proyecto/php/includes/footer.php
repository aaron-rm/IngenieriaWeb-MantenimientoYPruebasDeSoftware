<?php
require_once __DIR__ . '/config.php';
$logueadoFooter = isset($_SESSION['rol']);
?>
<footer class="site-footer">
    <nav class="footer-nav">
        <a href="<?= APP_URL ?>home.php">Home</a>
        <span class="footer-sep">·</span>
        <a href="<?= APP_URL ?>sobre-nosotros.php">Sobre Nosotros</a>
        <?php if ($logueadoFooter): ?>
            <span class="footer-sep">·</span>
            <a href="<?= APP_URL ?>pages/orders.php">Ordenes</a>
            <span class="footer-sep">·</span>
            <a href="<?= APP_URL ?>pages/comandas.php">Comandas</a>
            <span class="footer-sep">·</span>
            <a href="<?= APP_URL ?>logout.php" class="footer-logout">Cerrar sesion</a>
        <?php endif; ?>
    </nav>
    <p class="footer-copy">&copy; <?= date('Y') ?> Restaurante MaLu · MiPedido. Todos los derechos reservados.</p>
</footer>
