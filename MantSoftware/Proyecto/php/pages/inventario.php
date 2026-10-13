<?php
/**
 * ============================================================
 * inventario.php — Manejo de stock de platos (SUPERVISOR / GERENTE)
 * Permite agregar, editar y eliminar los platos que aparecen en
 * la vista de Ordenes, y controlar sus unidades disponibles.
 * El stock se descuenta solo cuando un cashier envia o cobra un pedido.
 * ============================================================
 */
$rolesPermitidos = ['supervisor', 'gerente'];
require_once __DIR__ . '/../includes/auth-check.php';
require_once __DIR__ . '/../includes/datos.php';

$accion = $_POST['accion'] ?? null;
$toast = null;

if ($accion !== null && $_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if ($accion === 'nuevoPlato') {
            crearProducto(
                $_POST['nombre'] ?? '',
                $_POST['precio'] ?? '',
                (int) ($_POST['idSubcategoria'] ?? 0),
                $_POST['emoji'] ?? '',
                (int) ($_POST['stock'] ?? 0),
                isset($_POST['masVendido'])
            );
            $toast = 'Plato "' . trim($_POST['nombre']) . '" agregado al inventario.';

        } elseif ($accion === 'guardarPlato') {
            $idProd = (int) $_POST['idProducto'];
            // Stock final = stock actual escrito + unidades que se agregan (reposicion)
            $stockFinal = (int) ($_POST['stock'] ?? 0) + (int) ($_POST['agregar'] ?? 0);
            actualizarProducto(
                $idProd,
                $_POST['nombre'] ?? '',
                $_POST['precio'] ?? '',
                (int) ($_POST['idSubcategoria'] ?? 0),
                $_POST['emoji'] ?? '',
                $stockFinal,
                isset($_POST['masVendido'])
            );
            $toast = 'Plato actualizado. Stock disponible: ' . $stockFinal . '.';

        } elseif ($accion === 'eliminarPlato') {
            $idProd = (int) $_POST['idProducto'];
            $p = productoPorId($idProd);
            eliminarProducto($idProd);
            $toast = 'Plato "' . ($p['nombre'] ?? '') . '" eliminado.';
        }
    } catch (Exception $ex) {
        $toast = 'Error: ' . $ex->getMessage();
    }
    $_SESSION['toastInventario'] = $toast;
    header('Location: inventario.php');
    exit;
}

if ($toast === null) {
    $toast = $_SESSION['toastInventario'] ?? null;
    unset($_SESSION['toastInventario']);
}

$platos       = listarProductosConStock();
$subcats      = listarSubcategorias();
$totalPlatos  = count($platos);
$totalUnidades = array_sum(array_column($platos, 'stock'));
$agotados     = count(array_filter($platos, fn($p) => $p['stock'] <= 0));
$stockBajo    = count(array_filter($platos, fn($p) => $p['stock'] > 0 && $p['stock'] <= 5));
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inventario · MiPedido</title>
    <link rel="stylesheet" href="<?= CSS_URL ?>main.css">
    <link rel="stylesheet" href="<?= CSS_URL ?>layout.css">
    <link rel="stylesheet" href="<?= CSS_URL ?>inventario.css">
</head>
<body>

<?php require __DIR__ . '/../includes/header.php'; ?>

<main class="main-content" style="min-height:auto">

    <?php if ($toast !== null): ?>
        <?php $esError = (strpos($toast, 'Error') === 0); ?>
        <div class="alert-box <?= $esError ? 'alert-error' : 'alert-success' ?>"><?= $esError ? '⚠' : '✅' ?> <?= htmlspecialchars($toast) ?></div>
    <?php endif; ?>

    <h1 class="page-title">Inventario de platos</h1>

    <!-- Resumen -->
    <div class="inv-resumen">
        <div class="inv-stat"><span class="inv-stat-num"><?= $totalPlatos ?></span><span class="inv-stat-lbl">Platos</span></div>
        <div class="inv-stat"><span class="inv-stat-num"><?= (int) $totalUnidades ?></span><span class="inv-stat-lbl">Unidades en stock</span></div>
        <div class="inv-stat <?= $stockBajo > 0 ? 'warn' : '' ?>"><span class="inv-stat-num"><?= $stockBajo ?></span><span class="inv-stat-lbl">Stock bajo (≤ 5)</span></div>
        <div class="inv-stat <?= $agotados > 0 ? 'danger' : '' ?>"><span class="inv-stat-num"><?= $agotados ?></span><span class="inv-stat-lbl">Agotados</span></div>
    </div>

    <!-- Agregar plato nuevo -->
    <div class="card inv-nuevo">
        <div class="inv-titulo">+ Agregar plato</div>
        <form method="post" action="inventario.php" class="inv-form-nuevo">
            <input type="hidden" name="accion" value="nuevoPlato">
            <input type="text" name="nombre" placeholder="Nombre del plato" required>
            <input type="number" name="precio" placeholder="Precio" step="0.01" min="0" required style="width:90px">
            <select name="idSubcategoria" required>
                <option value="">-- Subcategoria --</option>
                <?php foreach ($subcats as $sc): ?>
                    <option value="<?= (int) $sc['id_subcategoria'] ?>"><?= htmlspecialchars($sc['categoria'] . ' · ' . $sc['nombre']) ?></option>
                <?php endforeach; ?>
            </select>
            <input type="text" name="emoji" placeholder="Emoji" maxlength="8" style="width:70px">
            <input type="number" name="stock" placeholder="Stock" min="0" value="0" required style="width:80px">
            <label class="inv-check"><input type="checkbox" name="masVendido"> Mas vendido</label>
            <button type="submit" class="btn-primary">Agregar</button>
        </form>
    </div>

    <!-- Tabla de platos -->
    <div class="card inv-tabla-wrap">
        <div class="inv-titulo">Platos disponibles en Ordenes</div>
        <p class="text-xs text-muted" style="margin:0 0 10px">
            Edita los campos y pulsa <strong>Guardar</strong>. "Agregar" suma unidades al stock actual (ej: +15 de puerco).
            Cada plato que un cashier envia o cobra se resta automaticamente del stock.
        </p>
        <div style="overflow-x:auto">
        <table class="inv-tabla">
            <thead>
                <tr>
                    <th>Emoji</th><th>Plato</th><th>Precio ($)</th><th>Subcategoria</th>
                    <th>Stock actual</th><th>+ Agregar</th><th>Mas vendido</th><th>Estado</th><th>Acciones</th>
                </tr>
            </thead>
            <tbody>
            <?php if (empty($platos)): ?>
                <tr><td colspan="9" style="text-align:center;padding:20px" class="text-muted">No hay platos registrados.</td></tr>
            <?php else: foreach ($platos as $p):
                $id = (int) $p['id_producto'];
                $estadoCls = ($p['stock'] <= 0) ? 'stock-out' : (($p['stock'] <= 5) ? 'stock-low' : 'stock-ok');
                $estadoTxt = ($p['stock'] <= 0) ? 'Agotado' : (($p['stock'] <= 5) ? 'Stock bajo' : 'Disponible');
            ?>
                <tr>
                    <td><input form="g<?= $id ?>" type="text" name="emoji" value="<?= htmlspecialchars($p['emoji']) ?>" maxlength="8" style="width:60px"></td>
                    <td><input form="g<?= $id ?>" type="text" name="nombre" value="<?= htmlspecialchars($p['nombre']) ?>" required></td>
                    <td><input form="g<?= $id ?>" type="number" name="precio" value="<?= number_format($p['precio'], 2, '.', '') ?>" step="0.01" min="0" required style="width:80px"></td>
                    <td>
                        <select form="g<?= $id ?>" name="idSubcategoria" required>
                            <?php foreach ($subcats as $sc): ?>
                                <option value="<?= (int) $sc['id_subcategoria'] ?>" <?= ((int) $sc['id_subcategoria'] === (int) $p['id_subcategoria']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($sc['categoria'] . ' · ' . $sc['nombre']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                    <td><input form="g<?= $id ?>" type="number" name="stock" value="<?= (int) $p['stock'] ?>" min="0" required style="width:70px"></td>
                    <td><input form="g<?= $id ?>" type="number" name="agregar" value="0" min="0" style="width:70px"></td>
                    <td style="text-align:center"><input form="g<?= $id ?>" type="checkbox" name="masVendido" <?= $p['mas_vendido'] ? 'checked' : '' ?>></td>
                    <td><span class="inv-badge <?= $estadoCls ?>"><?= $estadoTxt ?></span></td>
                    <td style="white-space:nowrap">
                        <button form="g<?= $id ?>" type="submit" class="btn-primary" style="font-size:11px;padding:6px 10px">Guardar</button>
                        <button form="e<?= $id ?>" type="submit" class="btn-ghost inv-btn-del"
                                onclick="return confirm('¿Eliminar el plato &quot;<?= htmlspecialchars($p['nombre'], ENT_QUOTES) ?>&quot;?')">Eliminar</button>
                    </td>
                </tr>
            <?php endforeach; endif; ?>
            </tbody>
        </table>
        </div>

        <?php /* Formularios de cada fila (los campos de la tabla se enlazan con el atributo form="...") */ ?>
        <?php foreach ($platos as $p): $id = (int) $p['id_producto']; ?>
            <form id="g<?= $id ?>" method="post" action="inventario.php">
                <input type="hidden" name="accion" value="guardarPlato">
                <input type="hidden" name="idProducto" value="<?= $id ?>">
            </form>
            <form id="e<?= $id ?>" method="post" action="inventario.php">
                <input type="hidden" name="accion" value="eliminarPlato">
                <input type="hidden" name="idProducto" value="<?= $id ?>">
            </form>
        <?php endforeach; ?>
    </div>

</main>

<?php require __DIR__ . '/../includes/footer.php'; ?>
</body>
</html>
