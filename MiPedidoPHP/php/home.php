<?php
/**
 * ============================================================
 * home.php — Pagina de inicio (publica)
 * Requisitos cumplidos aqui:
 *   - <title> propio y distinto al resto de paginas
 *   - Tags <header>, <nav>, <section>, <footer>
 *   - 3 noticias (2 articulos + 1 video) con enlace a la fuente
 *   - Opcion de Login visible
 * ============================================================
 */
require_once __DIR__ . '/includes/config.php';

// Mensaje de error que pudo haber dejado auth-check.php (rol sin permiso)
$msgError = $_SESSION['mensajeError'] ?? null;
if ($msgError !== null) unset($_SESSION['mensajeError']);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- Titulo unico de esta pagina -->
    <title>Home · Restaurante MaLu</title>
    <link rel="stylesheet" href="<?= CSS_URL ?>main.css">
    <link rel="stylesheet" href="<?= CSS_URL ?>layout.css">
    <link rel="stylesheet" href="<?= CSS_URL ?>home.css">
</head>
<body class="public-page-body">

    <?php require __DIR__ . '/includes/header.php'; ?>

    <main class="public-main">

        <?php if ($msgError !== null): ?>
            <div class="alert-box alert-error">⚠ <?= htmlspecialchars($msgError) ?></div>
        <?php endif; ?>

        <!-- ================= HERO / BIENVENIDA ================= -->
        <section class="home-hero">
            <h1>Bienvenido a <span class="text-pink">MaLu Restaurante</span></h1>
            <p>Sabor casero, ambiente familiar y ahora, un sistema de pedidos mas rapido y eficiente para nuestro equipo.</p>
            <a href="login.php" class="btn-primary">Iniciar sesion</a>
        </section>

        <!-- ================= NOTICIAS ================= -->
        <section class="home-news">
            <h2>Noticias y novedades</h2>
            <div class="news-grid">

                <!-- Noticia 1: articulo -->
                <article class="news-card">
                    <div class="news-badge">Articulo</div>
                    <h3>Tendencias gastronomicas en Panama para este ano</h3>
                    <p>Un repaso de los sabores y estilos que estan marcando la escena culinaria panameña en los ultimos meses.</p>
                    <a href="https://www.laestrella.com.pa/economia" target="_blank" rel="noopener" class="news-link">Leer fuente completa →</a>
                </article>

                <!-- Noticia 2: articulo -->
                <article class="news-card">
                    <div class="news-badge">Articulo</div>
                    <h3>Como la tecnologia esta cambiando la toma de pedidos</h3>
                    <p>Cada vez mas restaurantes adoptan sistemas digitales para agilizar el servicio en sala y en cocina.</p>
                    <a href="https://www.prensa.com/economia/" target="_blank" rel="noopener" class="news-link">Leer fuente completa →</a>
                </article>

                <!-- Noticia 3: video -->
                <article class="news-card">
                    <div class="news-badge news-badge-video">Video</div>
                    <h3>Un dia en la cocina de un restaurante panameño</h3>
                    <p>Un vistazo detras de camaras a como se prepara un servicio de alta demanda.</p>
                    <a href="https://www.youtube.com/results?search_query=un+dia+en+un+restaurante" target="_blank" rel="noopener" class="news-link">Ver video en la fuente →</a>
                </article>

            </div>
        </section>

    </main>

    <?php require __DIR__ . '/includes/footer.php'; ?>
</body>
</html>
