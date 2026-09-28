<?php
/**
 * ============================================================
 * ventas.php — Diario de ventas (CASHIER / SUPERVISOR / GERENTE)
 * Lee las facturas reales generadas en orders.php
 * ============================================================
 */
$rolesPermitidos = ['cashier', 'supervisor', 'gerente'];
require_once __DIR__ . '/../includes/auth-check.php';
require_once __DIR__ . '/../includes/db.php';

$filtroMetodo  = $_GET['metodo'] ?? null;
$filtroOrigen  = $_GET['origen'] ?? null;
$verFacturaId  = $_GET['verFactura'] ?? null;

// ---------------------------------------------------------
// Consulta principal de facturas del dia (con filtros opcionales)
// ---------------------------------------------------------
$sql = "SELECT f.id_factura, f.total, f.metodo_pago, f.fecha_hora,
               p.origen, p.id_mesa, p.comensal
        FROM facturas f JOIN pedidos p ON f.id_pedido = p.id_pedido
        WHERE f.anulada = 0 AND DATE(f.fecha_hora) = CURDATE() ";
$params = [];
if ($filtroMetodo !== null && $filtroMetodo !== '') { $sql .= 'AND f.metodo_pago = ? '; $params[] = $filtroMetodo; }
if ($filtroOrigen !== null && $filtroOrigen !== '') { $sql .= 'AND p.origen = ? '; $params[] = $filtroOrigen; }
$sql .= 'ORDER BY f.fecha_hora DESC';

$pdo = getConnection();
$ps = $pdo->prepare($sql);
$ps->execute($params);
$rows = $ps->fetchAll();

$cantFacturas = 0;
$totalDia = 0; $totalEfectivo = 0; $totalYappy = 0; $totalTarjeta = 0;
$filas = [];
foreach ($rows as $r) {
    $total = (float) $r['total'];
    $metodo = $r['metodo_pago'];
    $cantFacturas++;
    $totalDia += $total;
    if ($metodo === 'efectivo') $totalEfectivo += $total;
    elseif ($metodo === 'yappy') $totalYappy += $total;
    elseif ($metodo === 'tarjeta') $totalTarjeta += $total;

    $filas[] = [
        (int) $r['id_factura'], $r['fecha_hora'], $r['origen'],
        (int) $r['id_mesa'], $r['comensal'], $metodo, $total,
    ];
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Diario de Ventas · MiPedido</title>
    <link rel="stylesheet" href="<?= CSS_URL ?>main.css">
    <link rel="stylesheet" href="<?= CSS_URL ?>layout.css">
    <link rel="stylesheet" href="<?= CSS_URL ?>ventas.css">
</head>
<body>

<?php require __DIR__ . '/../includes/header.php'; ?>

<main class="main-content" style="min-height:auto">

    <div class="ventas-toprow">
        <div class="ventas-title-col"><h1 class="page-title">Diario de ventas</h1></div>
        <form method="get" action="ventas.php" class="ventas-filters">
            <span class="turno-pill">Turno: hoy</span>
            <select class="filter-select" name="metodo" onchange="this.form.submit()">
                <option value="">Metodo ▾</option>
                <option value="efectivo" <?= ($filtroMetodo === 'efectivo') ? 'selected' : '' ?>>Efectivo</option>
                <option value="yappy"    <?= ($filtroMetodo === 'yappy') ? 'selected' : '' ?>>Yappy</option>
                <option value="tarjeta"  <?= ($filtroMetodo === 'tarjeta') ? 'selected' : '' ?>>Tarjeta</option>
            </select>
            <select class="filter-select" name="origen" onchange="this.form.submit()">
                <option value="">Origen ▾</option>
                <option value="mesa"   <?= ($filtroOrigen === 'mesa') ? 'selected' : '' ?>>Mesa</option>
                <option value="rapido" <?= ($filtroOrigen === 'rapido') ? 'selected' : '' ?>>Rapido</option>
            </select>
        </form>
    </div>

    <div class="summary-row">
        <div class="summary-card">
            <div class="summary-label">Facturas turno</div>
            <div class="summary-val"><?= $cantFacturas ?></div>
        </div>
        <div class="summary-card">
            <div class="summary-label">Total turno</div>
            <div class="summary-val pink">$<?= number_format($totalDia, 2) ?></div>
        </div>
        <div class="summary-card">
            <div class="summary-label">Efectivo · Yappy · Tarjeta</div>
            <div class="breakdown-row">
                <div class="breakdown-item"><span class="method">Efectivo</span><span class="amount">$<?= number_format($totalEfectivo, 2) ?></span></div>
                <div class="breakdown-item"><span class="method">Yappy</span><span class="amount">$<?= number_format($totalYappy, 2) ?></span></div>
                <div class="breakdown-item"><span class="method">Tarjeta</span><span class="amount">$<?= number_format($totalTarjeta, 2) ?></span></div>
            </div>
        </div>
    </div>

    <div class="ventas-table-card">
        <table class="data-table">
            <thead>
                <tr><th>Fact.</th><th>Hora</th><th>Origen</th><th>Metodo Pago</th><th>Total</th><th>Acciones</th></tr>
            </thead>
            <tbody>
            <?php if (empty($filas)): ?>
                <tr><td colspan="6" style="text-align:center;color:var(--text-muted);padding:32px">Sin facturas</td></tr>
            <?php else: foreach ($filas as $f):
                    [$idFactura, $fecha, $origen, $idMesa, $comensal, $metodo, $total] = $f;
                    $origenLbl = ($origen === 'mesa') ? ('Mesa ' . $idMesa . ($comensal !== null ? ' · ' . $comensal : '')) : 'Rapido';
                    $pillClass = ($metodo === 'efectivo') ? 'pill-efectivo' : (($metodo === 'tarjeta') ? 'pill-tarjeta' : 'pill-yappy-rest');
            ?>
                <tr>
                    <td class="fact-num">#<?= $idFactura ?></td>
                    <td class="fact-hora"><?= date('H:i', strtotime($fecha)) ?></td>
                    <td><?= htmlspecialchars($origenLbl) ?></td>
                    <td><span class="<?= $pillClass ?>"><?= htmlspecialchars(ucfirst($metodo)) ?></span></td>
                    <td class="fact-total">$<?= number_format($total, 2) ?></td>
                    <td>
                        <div class="action-links">
                            <a class="action-link" href="ventas.php?verFactura=<?= $idFactura ?>&metodo=<?= urlencode($filtroMetodo ?? '') ?>&origen=<?= urlencode($filtroOrigen ?? '') ?>">Ver</a>
                        </div>
                    </td>
                </tr>
            <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>

    <?php /* ================= DETALLE DE FACTURA ================= */ ?>
    <?php if ($verFacturaId !== null):
        $ps2 = $pdo->prepare(
            "SELECT f.id_factura, f.total, f.metodo_pago, f.fecha_hora, p.id_pedido, p.origen, p.id_mesa, p.comensal
             FROM facturas f JOIN pedidos p ON f.id_pedido = p.id_pedido WHERE f.id_factura = ?"
        );
        $ps2->execute([(int) $verFacturaId]);
        $rf = $ps2->fetch();
        if ($rf):
            $idPedidoF = (int) $rf['id_pedido'];
    ?>
    <div class="modal-backdrop open">
        <div class="modal" style="max-width:520px">
            <div class="modal-header">
                <div class="modal-title">Factura #<?= (int) $rf['id_factura'] ?></div>
                <a href="ventas.php" class="modal-close">✕</a>
            </div>
            <div class="factura-detail">
                <div class="factura-meta-row">
                    <div class="factura-meta-item"><span class="factura-meta-label">Hora</span><span class="factura-meta-val"><?= date('H:i', strtotime($rf['fecha_hora'])) ?></span></div>
                    <div class="factura-meta-item"><span class="factura-meta-label">Origen</span><span class="factura-meta-val"><?= ($rf['origen'] === 'mesa') ? 'Mesa ' . $rf['id_mesa'] : 'Rapido' ?></span></div>
                    <div class="factura-meta-item"><span class="factura-meta-label">Metodo</span><span class="factura-meta-val"><?= htmlspecialchars($rf['metodo_pago']) ?></span></div>
                </div>
                <div class="factura-items">
                <?php
                    $ps3 = $pdo->prepare(
                        "SELECT pr.nombre, d.cantidad, pr.precio FROM pedido_detalle d
                         JOIN productos pr ON d.id_producto = pr.id_producto WHERE d.id_pedido = ?"
                    );
                    $ps3->execute([$idPedidoF]);
                    foreach ($ps3->fetchAll() as $ri):
                ?>
                    <div class="factura-item-row">
                        <span><?= (int) $ri['cantidad'] ?>× <?= htmlspecialchars($ri['nombre']) ?></span>
                        <span>$<?= number_format($ri['cantidad'] * $ri['precio'], 2) ?></span>
                    </div>
                <?php endforeach; ?>
                </div>
                <div class="factura-total-row"><span>Total</span><span class="total-amount">$<?= number_format($rf['total'], 2) ?></span></div>
            </div>
            <a href="ventas.php" class="btn-secondary btn-full" style="text-decoration:none;text-align:center;display:block">Cerrar</a>
        </div>
    </div>
    <?php endif; endif; ?>

</main>

<?php require __DIR__ . '/../includes/footer.php'; ?>
</body>
</html>
