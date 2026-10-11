<?php
/**
 * ============================================================
 * secciones.php — Administracion de Secciones (SUPERVISOR / GERENTE)
 * Permite crear secciones, agregar categorias y asignar usuarios
 * ============================================================
 */
$rolesPermitidos = ['supervisor', 'gerente'];
require_once __DIR__ . '/../includes/auth-check.php';
require_once __DIR__ . '/../includes/db.php';

$accion = $_POST['accion'] ?? null;
$toast = null;

if ($accion !== null && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $pdo = getConnection();
    try {
        if ($accion === 'nuevaSeccion') {
            $nombre = $_POST['nombre'];
            $pdo->prepare("INSERT INTO secciones (nombre, estado) VALUES (?, 'activa')")->execute([$nombre]);
            $toast = "Seccion \"$nombre\" creada.";

        } elseif ($accion === 'nuevaCategoria') {
            $idSeccion = (int) $_POST['idSeccion'];
            $nombre = $_POST['nombre'];
            $pdo->prepare('INSERT INTO categorias (nombre, id_seccion) VALUES (?, ?)')->execute([$nombre, $idSeccion]);
            $toast = "Categoria \"$nombre\" agregada.";

        } elseif ($accion === 'eliminarCategoria') {
            $idCategoria = (int) $_POST['idCategoria'];
            $pdo->prepare('DELETE FROM categorias WHERE id_categoria = ?')->execute([$idCategoria]);
            $toast = 'Categoria eliminada.';

        } elseif ($accion === 'asignarUsuario') {
            $idUsuario = (int) $_POST['idUsuario'];
            $idSeccion = (int) $_POST['idSeccion'];
            $pdo->prepare('UPDATE usuarios SET id_seccion = ? WHERE id_usuario = ?')->execute([$idSeccion, $idUsuario]);
            $toast = 'Usuario asignado a la seccion.';

        } elseif ($accion === 'quitarUsuario') {
            $idUsuario = (int) $_POST['idUsuario'];
            $pdo->prepare('UPDATE usuarios SET id_seccion = NULL WHERE id_usuario = ?')->execute([$idUsuario]);
            $toast = 'Usuario removido de la seccion.';
        }
    } catch (Exception $ex) {
        $toast = 'Error: ' . $ex->getMessage();
    }
    $_SESSION['toastSecciones'] = $toast;
    header('Location: secciones.php');
    exit;
}

if ($toast === null) {
    $toast = $_SESSION['toastSecciones'] ?? null;
    unset($_SESSION['toastSecciones']);
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Secciones · MiPedido</title>
    <link rel="stylesheet" href="<?= CSS_URL ?>main.css">
    <link rel="stylesheet" href="<?= CSS_URL ?>layout.css">
    <link rel="stylesheet" href="<?= CSS_URL ?>secciones.css">
</head>
<body>

<?php require __DIR__ . '/../includes/header.php'; ?>

<main class="main-content" style="min-height:auto">

    <?php if ($toast !== null): ?>
        <div class="alert-box alert-success"><?= htmlspecialchars($toast) ?></div>
    <?php endif; ?>

    <div class="secciones-toprow">
        <h1 class="page-title">Secciones</h1>
        <form method="post" action="secciones.php" style="display:flex;gap:8px">
            <input type="hidden" name="accion" value="nuevaSeccion">
            <input type="text" name="nombre" placeholder="Nombre de nueva seccion" required
                   style="padding:8px 12px;border-radius:6px;border:1px solid var(--input-border)">
            <button type="submit" class="btn-primary">+ Nueva seccion</button>
        </form>
    </div>

    <div class="secciones-grid">
    <?php
        $pdo = getConnection();
        $rsSec = $pdo->query('SELECT * FROM secciones ORDER BY id_seccion');
        foreach ($rsSec->fetchAll() as $rsec):
            $idSeccion = (int) $rsec['id_seccion'];
            $claseHeader = $idSeccion === 1 ? 'restaurante' : ($idSeccion === 2 ? 'bar' : 'default');
    ?>
        <div class="seccion-card">
            <div class="seccion-card-header <?= $claseHeader ?>">
                <span class="seccion-name"><?= htmlspecialchars($rsec['nombre']) ?></span>
                <span class="seccion-estado"><?= htmlspecialchars($rsec['estado']) ?></span>
            </div>
            <div class="seccion-body">

                <div>
                    <div class="sec-sub-label">Categorias</div>
                    <div class="cat-chips">
                    <?php
                        $psCat = $pdo->prepare('SELECT * FROM categorias WHERE id_seccion = ?');
                        $psCat->execute([$idSeccion]);
                        foreach ($psCat->fetchAll() as $rcat):
                    ?>
                        <div class="cat-chip">
                            <?= htmlspecialchars($rcat['nombre']) ?>
                            <form method="post" action="secciones.php" style="display:inline">
                                <input type="hidden" name="accion" value="eliminarCategoria">
                                <input type="hidden" name="idCategoria" value="<?= (int) $rcat['id_categoria'] ?>">
                                <button type="submit" class="btn-chip-remove">×</button>
                            </form>
                        </div>
                    <?php endforeach; ?>
                    </div>
                    <form method="post" action="secciones.php" style="display:flex;gap:6px;margin-top:10px">
                        <input type="hidden" name="accion" value="nuevaCategoria">
                        <input type="hidden" name="idSeccion" value="<?= $idSeccion ?>">
                        <input type="text" name="nombre" placeholder="Nueva categoria" required
                               style="font-size:12px;padding:6px 10px;border-radius:20px;border:1px solid var(--card-border)">
                        <button type="submit" class="btn-add-cat">+ Agregar</button>
                    </form>
                </div>

                <hr class="sec-divider">

                <div>
                    <div class="sec-sub-label">Usuarios asignados</div>
                    <div class="user-list">
                    <?php
                        $psUsr = $pdo->prepare("SELECT id_usuario, nombre, rol FROM usuarios WHERE id_seccion = ? AND activo = 1");
                        $psUsr->execute([$idSeccion]);
                        foreach ($psUsr->fetchAll() as $rusr):
                    ?>
                        <div class="user-item">
                            <div class="user-avatar color-1"><?= htmlspecialchars(strtoupper(mb_substr($rusr['nombre'], 0, 1))) ?></div>
                            <div class="user-info">
                                <div class="user-name"><?= htmlspecialchars($rusr['nombre']) ?></div>
                                <div class="user-role"><?= htmlspecialchars($rusr['rol']) ?></div>
                            </div>
                            <form method="post" action="secciones.php">
                                <input type="hidden" name="accion" value="quitarUsuario">
                                <input type="hidden" name="idUsuario" value="<?= (int) $rusr['id_usuario'] ?>">
                                <button type="submit" class="btn-user-remove">✕</button>
                            </form>
                        </div>
                    <?php endforeach; ?>
                    </div>

                    <!-- Asignar un usuario libre (sin seccion) a esta seccion -->
                    <form method="post" action="secciones.php" style="display:flex;gap:6px;margin-top:10px">
                        <input type="hidden" name="accion" value="asignarUsuario">
                        <input type="hidden" name="idSeccion" value="<?= $idSeccion ?>">
                        <select name="idUsuario" required style="font-size:12px;padding:6px;border-radius:6px;border:1px solid var(--card-border);flex:1">
                            <option value="">-- Elegir usuario --</option>
                            <?php
                                $psLibres = $pdo->prepare(
                                    "SELECT id_usuario, nombre FROM usuarios WHERE (id_seccion IS NULL OR id_seccion != ?) AND rol='cashier' AND activo=1"
                                );
                                $psLibres->execute([$idSeccion]);
                                foreach ($psLibres->fetchAll() as $rlib):
                            ?>
                                <option value="<?= (int) $rlib['id_usuario'] ?>"><?= htmlspecialchars($rlib['nombre']) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <button type="submit" class="btn-asignar">+ Asignar</button>
                    </form>
                </div>

            </div>
        </div>
    <?php endforeach; ?>
    </div>

</main>

<?php require __DIR__ . '/../includes/footer.php'; ?>
</body>
</html>
