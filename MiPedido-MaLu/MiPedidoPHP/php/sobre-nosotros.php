<?php
/**
 * ============================================================
 * sobre-nosotros.php — Pagina publica "Sobre Nosotros"
 * Muestra la foto, nombre, cedula, carrera y resumen de
 * experiencia de cada integrante, leyendo estos datos
 * DIRECTAMENTE de la tabla `usuarios` en MySQL.
 * ============================================================
 */
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- Titulo unico de esta pagina -->
    <title>Sobre Nosotros · Restaurante MaLu</title>
    <link rel="stylesheet" href="<?= CSS_URL ?>main.css">
    <link rel="stylesheet" href="<?= CSS_URL ?>layout.css">
    <link rel="stylesheet" href="<?= CSS_URL ?>sobre-nosotros.css">
</head>
<body class="public-page-body">

    <?php require __DIR__ . '/includes/header.php'; ?>

    <main class="public-main">
        <section class="team-intro">
            <h1>Sobre Nosotros</h1>
            <p>Somos el equipo de desarrollo detras de <strong>MiPedido</strong>, el sistema de toma de pedidos
               creado para el Restaurante MaLu como Proyecto Semestral de Programacion de Software II.</p>
        </section>

        <section class="team-grid">
            <?php
                // Consultamos todos los usuarios activos para mostrarlos como equipo del proyecto
                try {
                    $pdo = getConnection();
                    $stmt = $pdo->query(
                        "SELECT nombre, cedula, carrera, rol, resumen_dev, foto_url
                         FROM usuarios WHERE activo = 1 ORDER BY
                         FIELD(rol,'gerente','supervisor','cashier','comandas')"
                    );
                    while ($u = $stmt->fetch()) {
                        $foto = $u['foto_url'] ?: 'img/team/placeholder.jpg';
            ?>
                        <article class="team-card">
                            <img src="<?= htmlspecialchars($foto) ?>"
                                 alt="Foto de <?= htmlspecialchars($u['nombre']) ?>"
                                 class="team-photo">
                            <h3><?= htmlspecialchars($u['nombre']) ?></h3>
                            <div class="team-role"><?= htmlspecialchars(strtoupper($u['rol'])) ?></div>
                            <div class="team-meta">Cedula: <?= htmlspecialchars($u['cedula']) ?></div>
                            <div class="team-meta">Carrera: <?= htmlspecialchars($u['carrera']) ?></div>
                            <p class="team-resumen"><?= htmlspecialchars($u['resumen_dev']) ?></p>
                        </article>
            <?php
                    }
                } catch (Exception $e) {
            ?>
                    <div class="alert-box alert-error">
                        ⚠ No se pudo conectar a la base de datos: <?= htmlspecialchars($e->getMessage()) ?>.
                        Verifica que XAMPP (MySQL) este encendido y que la base "mipedido_db" exista.
                    </div>
            <?php
                }
            ?>
        </section>
    </main>

    <?php require __DIR__ . '/includes/footer.php'; ?>
</body>
</html>
