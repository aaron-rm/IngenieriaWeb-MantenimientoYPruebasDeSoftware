<?php
/**
 * ============================================================
 * comandas.php — Pantalla de cocina (rol COMANDAS, y visible
 * tambien para cashier/supervisor/gerente segun la guia)
 * ============================================================
 */
$rolesPermitidos = ['comandas', 'cashier', 'supervisor', 'gerente'];
require_once __DIR__ . '/../includes/auth-check.php';
require_once __DIR__ . '/../includes/datos.php';

// ---------------------------------------------------------
// Procesar acciones: tachar plato / cambiar estado de comanda
// ---------------------------------------------------------
$accion = $_POST['accion'] ?? null;
if ($accion !== null && $_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if ($accion === 'togglePreparado') {
            alternarPreparado((int) $_POST['idDetalle']);

        } elseif ($accion === 'setEstado') {
            $idPedido    = (int) $_POST['idPedido'];
            $nuevoEstado = $_POST['estado'];
            actualizarPedido($idPedido, ['estado' => $nuevoEstado]);

            // Si se marca "listo", tachamos automaticamente todos los platos de esa comanda
            if ($nuevoEstado === 'listo') {
                marcarTodoPreparado($idPedido);
            }
        }
    } catch (Exception $ex) {
        $_SESSION['toastComandas'] = 'Error: ' . $ex->getMessage();
    }
    header('Location: comandas.php');
    exit;
}

$toast = $_SESSION['toastComandas'] ?? null;
unset($_SESSION['toastComandas']);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Comandas · MiPedido</title>
    <!-- Auto-refresh cada 6s: asi la cocina "recibe" pedidos nuevos sin necesidad de JavaScript/websockets -->
    <meta http-equiv="refresh" content="6">
    <link rel="stylesheet" href="<?= CSS_URL ?>main.css">
    <link rel="stylesheet" href="<?= CSS_URL ?>layout.css">
    <link rel="stylesheet" href="<?= CSS_URL ?>comandas.css">
    <style> body[data-page="comandas"] { background:#2a2a2a; } </style>
</head>
<body data-page="comandas">

<?php require __DIR__ . '/../includes/header.php'; ?>

<main class="main-content" style="min-height:auto;background:#2a2a2a">

    <?php if ($toast !== null): ?>
        <div class="alert-box alert-error"><?= htmlspecialchars($toast) ?></div>
    <?php endif; ?>

    <div class="comandas-header">
        <div>
            <div class="page-title" style="color:#fff">Comandas</div>
            <div class="page-subtitle" style="color:rgba(255,255,255,.5)">Cocina · Kitchen display (se actualiza cada 6s)</div>
        </div>
    </div>

    <div class="rules-bar">
        <span>📌 Tachar plato = ya preparado</span>
        <span>·</span>
        <span>"Listo" saca la comanda de la fila</span>
        <span>·</span>
        <span>Orden de entrada ↑ arriba</span>
    </div>

    <?php
        // Comandas activas (pendientes o en preparacion), de la mas antigua a la mas nueva
        $pedidos = pedidosPorEstado(['pendiente', 'en_preparacion']);
    ?>
    <div class="comanda-grid">
    <?php
        $totalFila = 0;
        foreach ($pedidos as $rped):
            $totalFila++;
            $idPedido = (int) $rped['id_pedido'];
            $origenTxt = ($rped['origen'] === 'mesa')
                ? ('Mesa ' . $rped['id_mesa'] . ' · ' . $rped['comensal'])
                : 'Rapido';
            $estadoPedido = $rped['estado'];

            $detalles = detallesDePedido($idPedido);

            $todoListo = true;
            $itemsHtml = '';
            foreach ($detalles as $rdet) {
                $preparado = (bool) $rdet['preparado'];
                if (!$preparado) $todoListo = false;

                $itemsHtml .= "<form method='post' action='comandas.php' style='display:flex;align-items:center;gap:8px;padding:6px 8px' >"
                    . "<input type='hidden' name='accion' value='togglePreparado'>"
                    . "<input type='hidden' name='idDetalle' value='" . (int) $rdet['id_detalle'] . "'>"
                    . "<button type='submit' class='item-check' style='cursor:pointer'>" . ($preparado ? '✓' : '') . "</button>"
                    . "<span style='font-size:13px;" . ($preparado ? 'text-decoration:line-through;color:#999' : 'color:#1a1a1a') . "'>"
                    . (int) $rdet['cantidad'] . '× ' . htmlspecialchars($rdet['nombre']) . '</span>'
                    . '</form>';
            }
    ?>
        <div class="comanda-card <?= $todoListo ? 'all-done' : '' ?>">
            <div class="card-accent"></div>
            <div class="comanda-card-header">
                <div>
                    <div class="comanda-num">#<?= $idPedido ?></div>
                    <div class="comanda-origin"><?= htmlspecialchars($origenTxt) ?></div>
                </div>
                <div class="comanda-time"><?= date('H:i', strtotime($rped['fecha_hora'])) ?></div>
            </div>
            <div class="comanda-items">
                <?= $itemsHtml ?>
                <?php if ($rped['nota'] !== null): ?>
                    <div class="item-note" style="padding:0 8px">📝 <?= htmlspecialchars($rped['nota']) ?></div>
                <?php endif; ?>
            </div>
            <div class="comanda-footer">
                <?php if ($todoListo): ?>
                    <form method="post" action="comandas.php" style="width:100%">
                        <input type="hidden" name="accion" value="setEstado">
                        <input type="hidden" name="idPedido" value="<?= $idPedido ?>">
                        <input type="hidden" name="estado" value="listo">
                        <button type="submit" class="btn-listo-full">✓ Listo — sale de la fila</button>
                    </form>
                <?php else: ?>
                    <form method="post" action="comandas.php" style="flex:1">
                        <input type="hidden" name="accion" value="setEstado">
                        <input type="hidden" name="idPedido" value="<?= $idPedido ?>">
                        <input type="hidden" name="estado" value="en_preparacion">
                        <button type="submit" class="estado-btn proceso <?= ($estadoPedido === 'en_preparacion') ? '' : 'inactive' ?>">En proceso</button>
                    </form>
                    <form method="post" action="comandas.php" style="flex:1">
                        <input type="hidden" name="accion" value="setEstado">
                        <input type="hidden" name="idPedido" value="<?= $idPedido ?>">
                        <input type="hidden" name="estado" value="listo">
                        <button type="submit" class="estado-btn listo">Listo</button>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    <?php endforeach; ?>
    <?php if ($totalFila === 0): ?>
        <div class="empty-queue">
            <span class="empty-icon">✅</span>
            <span>Fila vacia · Queue clear</span>
        </div>
    <?php endif; ?>
    </div>

</main>

<?php require __DIR__ . '/../includes/footer.php'; ?>
</body>
</html>
