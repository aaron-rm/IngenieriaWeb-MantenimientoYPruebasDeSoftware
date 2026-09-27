<?php
/**
 * ============================================================
 * orders.php — Pedido rapido + Mesas (CASHIER / SUPERVISOR / GERENTE)
 * Toda la logica de negocio esta en PHP embebido, tal como se
 * pedia en JSP con scriptlets (sin frameworks, sin AJAX).
 * ============================================================
 */
$rolesPermitidos = ['cashier', 'supervisor', 'gerente'];
require_once __DIR__ . '/../includes/auth-check.php';
require_once __DIR__ . '/../includes/db.php';

// ---------------------------------------------------------
// Datos de la sesion
// ---------------------------------------------------------
$idUsuario      = $_SESSION['idUsuario'] ?? null;
$idSeccion      = $_SESSION['idSeccion'] ?? 1; // gerente/supervisor sin seccion -> Restaurante por defecto
$nombreUsuario  = $_SESSION['nombre'] ?? null;
$toast          = null; // mensaje que se muestra despues de una accion

// ---------------------------------------------------------
// Carrito de "Pedido rapido" guardado en SESION
// [idProducto => cantidad]
// ---------------------------------------------------------
$carritoRapido = $_SESSION['carritoRapido'] ?? [];

// ---------------------------------------------------------
// Carritos por Mesa+Comensal guardados en SESION
// ["idMesa_comensal" => [idProducto => cantidad]]
// ---------------------------------------------------------
$carritosMesa = $_SESSION['carritosMesa'] ?? [];

// Lista de comensales activos por mesa: [idMesa => [C1, C2, ...]]
$comensalesPorMesa = $_SESSION['comensalesPorMesa'] ?? [];

// ---------------------------------------------------------
// Procesar ACCIONES enviadas por formularios (POST) - patron
// Post/Redirect/Get para evitar reenvios al refrescar la pagina
// ---------------------------------------------------------
$accion = $_POST['accion'] ?? ($_GET['accion'] ?? null);

if ($accion !== null && $_SERVER['REQUEST_METHOD'] === 'POST') {

    $pdo = null;
    // Contexto (mesa/comensal) para reconstruir la redireccion sin perder
    // la pantalla en la que estaba el usuario (patron Post/Redirect/Get)
    $mesaIdCtx   = $_POST['idMesa'] ?? null;
    $comensalCtx = $_POST['comensal'] ?? null;

    try {
        if ($accion === 'addRapido') {
            $idProd = (int) $_POST['idProducto'];
            $carritoRapido[$idProd] = ($carritoRapido[$idProd] ?? 0) + 1;

        } elseif ($accion === 'qtyRapido') {
            $idProd = (int) $_POST['idProducto'];
            $delta  = (int) $_POST['delta'];
            if (isset($carritoRapido[$idProd])) {
                $nueva = $carritoRapido[$idProd] + $delta;
                if ($nueva <= 0) unset($carritoRapido[$idProd]);
                else $carritoRapido[$idProd] = $nueva;
            }

        } elseif ($accion === 'clearRapido') {
            $carritoRapido = [];

        } elseif ($accion === 'enviarRapido' || $accion === 'cobrarRapido') {
            if (!empty($carritoRapido)) {
                $pdo = getConnection();
                $pdo->beginTransaction();

                $nota         = $_POST['nota'] ?? null;
                $esCobro      = ($accion === 'cobrarRapido');
                $metodoPago   = $_POST['metodoPago'] ?? null;
                $estadoInicial = $esCobro ? 'entregado' : 'pendiente';

                // Calculamos el total con precios reales de la BD (nunca confiar en el cliente)
                $total = 0;
                $psPrecio = $pdo->prepare('SELECT precio FROM productos WHERE id_producto = ?');
                foreach ($carritoRapido as $idProd => $cant) {
                    $psPrecio->execute([$idProd]);
                    $rowP = $psPrecio->fetch();
                    if ($rowP) $total += $rowP['precio'] * $cant;
                }

                $psPedido = $pdo->prepare(
                    "INSERT INTO pedidos (numero_pedido, origen, id_usuario, id_seccion, estado, nota, metodo_pago, total, cobrado)
                     VALUES (0, 'rapido', ?, ?, ?, ?, ?, ?, ?)"
                );
                $psPedido->execute([
                    $idUsuario,
                    $idSeccion,
                    $estadoInicial,
                    ($nota !== null && trim($nota) !== '') ? trim($nota) : null,
                    $esCobro ? $metodoPago : null,
                    $total,
                    $esCobro ? 1 : 0,
                ]);
                $idPedidoNuevo = (int) $pdo->lastInsertId();

                // El numero de pedido visible = mismo id (orden de entrada)
                $pdo->prepare('UPDATE pedidos SET numero_pedido = ? WHERE id_pedido = ?')
                    ->execute([$idPedidoNuevo, $idPedidoNuevo]);

                $psDetalle = $pdo->prepare(
                    'INSERT INTO pedido_detalle (id_pedido, id_producto, cantidad, preparado) VALUES (?, ?, ?, 0)'
                );
                foreach ($carritoRapido as $idProd => $cant) {
                    $psDetalle->execute([$idPedidoNuevo, $idProd, $cant]);
                }

                if ($esCobro) {
                    $pdo->prepare(
                        'INSERT INTO facturas (id_pedido, total, metodo_pago, id_seccion) VALUES (?, ?, ?, ?)'
                    )->execute([$idPedidoNuevo, $total, $metodoPago, $idSeccion]);
                }

                $pdo->commit();
                $carritoRapido = [];
                $toast = $esCobro
                    ? "Cobrado correctamente. Factura #$idPedidoNuevo"
                    : "Comanda #$idPedidoNuevo enviada a cocina";
            }

        } elseif ($accion === 'cobrarListo') {
            // Cobra un pedido que la cocina ya marco como "listo" (cierra el ciclo cocina -> caja)
            $idPedidoListo   = (int) $_POST['idPedido'];
            $metodoPagoListo = $_POST['metodoPago'] ?? null;
            $pdo = getConnection();
            $pdo->beginTransaction();

            $psTotal = $pdo->prepare(
                "SELECT total, id_seccion FROM pedidos WHERE id_pedido = ? AND estado='listo' AND cobrado = 0"
            );
            $psTotal->execute([$idPedidoListo]);
            $rowTotal = $psTotal->fetch();

            if ($rowTotal) {
                $totalListo   = $rowTotal['total'];
                $seccionListo = $rowTotal['id_seccion'];

                $pdo->prepare(
                    "UPDATE pedidos SET estado='entregado', cobrado=1, metodo_pago=? WHERE id_pedido=?"
                )->execute([$metodoPagoListo, $idPedidoListo]);

                $pdo->prepare(
                    'INSERT INTO facturas (id_pedido, total, metodo_pago, id_seccion) VALUES (?,?,?,?)'
                )->execute([$idPedidoListo, $totalListo, $metodoPagoListo, $seccionListo]);

                $pdo->commit();
                $toast = "Pedido #$idPedidoListo cobrado. Factura generada.";
            } else {
                $pdo->rollBack();
            }

        } elseif ($accion === 'addComensal') {
            $idMesa = (int) $_POST['idMesa'];
            $lista = $comensalesPorMesa[$idMesa] ?? null;
            if ($lista === null) { $lista = ['C1']; }
            $nuevoComensal = 'C' . (count($lista) + 1);
            $lista[] = $nuevoComensal;
            $comensalesPorMesa[$idMesa] = $lista;
            $comensalCtx = $nuevoComensal; // al agregar comensal saltamos directo a su cuenta

        } elseif ($accion === 'addMesaProducto') {
            $idMesa   = (int) $_POST['idMesa'];
            $comensal = $_POST['comensal'];
            $idProd   = (int) $_POST['idProducto'];
            $clave    = $idMesa . '_' . $comensal;
            $carritoC = $carritosMesa[$clave] ?? [];
            $carritoC[$idProd] = ($carritoC[$idProd] ?? 0) + 1;
            $carritosMesa[$clave] = $carritoC;
            // marcar mesa como ocupada
            $pdo = getConnection();
            $pdo->prepare("UPDATE mesas SET estado='ocupada' WHERE id_mesa=?")->execute([$idMesa]);

        } elseif ($accion === 'qtyMesa') {
            $idMesa   = (int) $_POST['idMesa'];
            $comensal = $_POST['comensal'];
            $idProd   = (int) $_POST['idProducto'];
            $delta    = (int) $_POST['delta'];
            $clave    = $idMesa . '_' . $comensal;
            if (isset($carritosMesa[$clave][$idProd])) {
                $nueva = $carritosMesa[$clave][$idProd] + $delta;
                if ($nueva <= 0) unset($carritosMesa[$clave][$idProd]);
                else $carritosMesa[$clave][$idProd] = $nueva;
            }

        } elseif ($accion === 'enviarMesa' || $accion === 'cobrarMesa') {
            $idMesa   = (int) $_POST['idMesa'];
            $comensal = $_POST['comensal'];
            $clave    = $idMesa . '_' . $comensal;
            $carritoC = $carritosMesa[$clave] ?? [];

            if (!empty($carritoC)) {
                $pdo = getConnection();
                $pdo->beginTransaction();

                $esCobro       = ($accion === 'cobrarMesa');
                $metodoPago    = $_POST['metodoPago'] ?? null;
                $estadoInicial = $esCobro ? 'entregado' : 'pendiente';

                $total = 0;
                $psPrecio = $pdo->prepare('SELECT precio FROM productos WHERE id_producto = ?');
                foreach ($carritoC as $idProd => $cant) {
                    $psPrecio->execute([$idProd]);
                    $rowP = $psPrecio->fetch();
                    if ($rowP) $total += $rowP['precio'] * $cant;
                }

                $psPedido = $pdo->prepare(
                    "INSERT INTO pedidos (numero_pedido, origen, id_mesa, comensal, id_usuario, id_seccion, estado, metodo_pago, total, cobrado)
                     VALUES (0, 'mesa', ?, ?, ?, ?, ?, ?, ?, ?)"
                );
                $psPedido->execute([
                    $idMesa, $comensal, $idUsuario, $idSeccion, $estadoInicial,
                    $esCobro ? $metodoPago : null, $total, $esCobro ? 1 : 0,
                ]);
                $idPedidoNuevo = (int) $pdo->lastInsertId();

                $pdo->prepare('UPDATE pedidos SET numero_pedido = ? WHERE id_pedido = ?')
                    ->execute([$idPedidoNuevo, $idPedidoNuevo]);

                $psDetalle = $pdo->prepare(
                    'INSERT INTO pedido_detalle (id_pedido, id_producto, cantidad, preparado) VALUES (?, ?, ?, 0)'
                );
                foreach ($carritoC as $idProd => $cant) {
                    $psDetalle->execute([$idPedidoNuevo, $idProd, $cant]);
                }

                if ($esCobro) {
                    $pdo->prepare(
                        'INSERT INTO facturas (id_pedido, total, metodo_pago, id_seccion) VALUES (?, ?, ?, ?)'
                    )->execute([$idPedidoNuevo, $total, $metodoPago, $idSeccion]);

                    // liberamos al comensal de la sesion
                    unset($carritosMesa[$clave]);
                    $lista = $comensalesPorMesa[$idMesa] ?? null;
                    if ($lista !== null) {
                        $lista = array_values(array_diff($lista, [$comensal]));
                        $comensalesPorMesa[$idMesa] = $lista;
                    }

                    // si ya no quedan comensales activos para esa mesa, la liberamos
                    $mesaVacia = ($lista === null || empty($lista));
                    if ($mesaVacia) {
                        $pdo->prepare("UPDATE mesas SET estado='libre' WHERE id_mesa=?")->execute([$idMesa]);
                        // Mesa liberada (vuelve a su color original) -> al redirigir
                        // regresamos al salon de mesas en vez de quedarnos en esta mesa
                        $mesaIdCtx = null;
                        $comensalCtx = null;
                    } else {
                        // Siguen quedando comensales en la mesa: saltamos al primero activo
                        $comensalCtx = $lista[0];
                    }
                } else {
                    $carritosMesa[$clave] = [];
                }

                $pdo->commit();
                $toast = $esCobro
                    ? "Comensal $comensal cobrado. Factura #$idPedidoNuevo"
                    : "Comanda #$idPedidoNuevo (Mesa $idMesa · $comensal) enviada a cocina";
            }
        }

    } catch (Exception $ex) {
        if ($pdo !== null && $pdo->inTransaction()) {
            try { $pdo->rollBack(); } catch (Exception $ignore) {}
        }
        $toast = 'Error al procesar la accion: ' . $ex->getMessage();
    }

    $_SESSION['carritoRapido']      = $carritoRapido;
    $_SESSION['carritosMesa']       = $carritosMesa;
    $_SESSION['comensalesPorMesa']  = $comensalesPorMesa;
    $_SESSION['toastOrders']        = $toast;

    // Redirigimos preservando mesa/comensal (si aplica) para no perder la
    // pantalla en la que estaba el usuario (patron Post/Redirect/Get).
    $redirectUrl = 'orders.php';
    $vistaParam = $_POST['vista'] ?? null;
    if ($vistaParam !== null) {
        $redirectUrl .= '?vista=' . urlencode($vistaParam);
        if ($vistaParam === 'mesas' && $mesaIdCtx !== null) {
            $redirectUrl .= '&mesaId=' . urlencode($mesaIdCtx);
            if ($comensalCtx !== null) $redirectUrl .= '&comensal=' . urlencode($comensalCtx);
        }
    }
    header('Location: ' . $redirectUrl);
    exit;
}

// Recuperamos el toast dejado por una redireccion anterior
if ($toast === null) {
    $toast = $_SESSION['toastOrders'] ?? null;
    unset($_SESSION['toastOrders']);
}

$vista     = $_GET['vista'] ?? 'rapido';
$catFiltro = $_GET['cat'] ?? null;
$q         = $_GET['q'] ?? null;
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ordenes · MiPedido</title>
    <link rel="stylesheet" href="<?= CSS_URL ?>main.css">
    <link rel="stylesheet" href="<?= CSS_URL ?>layout.css">
    <link rel="stylesheet" href="<?= CSS_URL ?>orders.css">
</head>
<body>

<?php require __DIR__ . '/../includes/header.php'; ?>

<main class="main-content" style="min-height:auto">

    <?php if ($toast !== null): ?>
        <div class="alert-box alert-success">✅ <?= htmlspecialchars($toast) ?></div>
    <?php endif; ?>

    <!-- Tabs Pedido rapido / Mesas (navegacion simple por enlace, sin JS) -->
    <div class="view-tabs">
        <a class="view-tab <?= ($vista === 'rapido') ? 'active' : '' ?>" href="orders.php?vista=rapido">⚡ Pedido rapido</a>
        <a class="view-tab <?= ($vista === 'mesas') ? 'active' : '' ?>" href="orders.php?vista=mesas">⬛ Mesas · Tables</a>
    </div>

    <?php if ($vista === 'rapido'): ?>
    <?php /* ============================================================
             VISTA: PEDIDO RAPIDO
             ============================================================ */ ?>

    <?php /* Pedidos que la cocina ya marco "Listo" y esperan cobro (cierra el ciclo cocina -> caja) */
        $pdoL = getConnection();
        $psL = $pdoL->prepare(
            "SELECT id_pedido, origen, id_mesa, comensal, total, fecha_hora FROM pedidos
             WHERE estado='listo' AND cobrado=0 AND id_seccion=? ORDER BY fecha_hora"
        );
        $psL->execute([$idSeccion]);
        $listos = $psL->fetchAll();
    ?>
    <div class="card" style="margin-bottom:16px">
        <div class="page-subtitle" style="margin-bottom:8px;font-weight:700">🔔 Listos en cocina · esperando cobro</div>
        <div style="display:flex;flex-wrap:wrap;gap:10px">
        <?php if (empty($listos)): ?>
            <span class="text-muted text-xs">No hay pedidos listos pendientes de cobro.</span>
        <?php else: foreach ($listos as $rl):
            $origenLbl = ($rl['origen'] === 'mesa') ? ('Mesa ' . $rl['id_mesa'] . ' · ' . $rl['comensal']) : 'Pedido rapido';
        ?>
            <form method="post" action="orders.php" style="display:flex;align-items:center;gap:8px;background:var(--content-bg);border:1px solid var(--card-border);border-radius:8px;padding:8px 12px">
                <input type="hidden" name="accion" value="cobrarListo">
                <input type="hidden" name="vista" value="rapido">
                <input type="hidden" name="idPedido" value="<?= (int) $rl['id_pedido'] ?>">
                <span style="font-size:12px;font-weight:700">#<?= (int) $rl['id_pedido'] ?> · <?= htmlspecialchars($origenLbl) ?> · $<?= number_format($rl['total'], 2) ?></span>
                <select name="metodoPago" style="font-size:11px;padding:4px;border-radius:4px">
                    <option value="efectivo">Efectivo</option>
                    <option value="yappy">Yappy</option>
                    <option value="tarjeta">Tarjeta</option>
                </select>
                <button type="submit" class="btn-primary" style="font-size:11px;padding:6px 10px">Cobrar</button>
            </form>
        <?php endforeach; endif; ?>
        </div>
    </div>

    <div class="orders-layout">
        <section class="products-panel">

            <div class="orders-toprow">
                <form method="get" action="orders.php" class="search-bar" style="flex:1">
                    <input type="hidden" name="vista" value="rapido">
                    <span class="search-icon">🔍</span>
                    <input type="text" name="q" value="<?= htmlspecialchars($q ?? '') ?>" placeholder="Buscar producto...">
                </form>
                <span class="section-badge">Seccion: <?= htmlspecialchars($_SESSION['seccionNombre'] ?? '') ?></span>
            </div>

            <?php /* ---- Mas vendidos ---- */ ?>
            <div class="bestsellers-row">
                <div class="row-label">Mas vendidos · <span style="font-weight:400;text-transform:none">tap para agregar</span></div>
                <div class="bestsellers-chips">
                <?php
                    $rsB = $pdoL->query("SELECT id_producto, nombre, precio FROM productos WHERE mas_vendido=1 AND activo=1");
                    foreach ($rsB->fetchAll() as $rb):
                ?>
                    <form method="post" action="orders.php" style="display:inline">
                        <input type="hidden" name="accion" value="addRapido">
                        <input type="hidden" name="vista" value="rapido">
                        <input type="hidden" name="idProducto" value="<?= (int) $rb['id_producto'] ?>">
                        <button type="submit" class="bestseller-chip" style="border:1px solid var(--card-border)">
                            <span><?= htmlspecialchars($rb['nombre']) ?></span>
                            <span class="chip-price">$<?= number_format($rb['precio'], 2) ?></span>
                        </button>
                    </form>
                <?php endforeach; ?>
                </div>
            </div>

            <?php /* ---- Categorias (propia seccion primero) ---- */ ?>
            <div class="cat-tabs">
                <a class="cat-tab <?= ($catFiltro === null) ? 'active' : '' ?>" href="orders.php?vista=rapido">Todas</a>
                <?php
                    $psC = $pdoL->prepare("SELECT id_categoria, nombre FROM categorias ORDER BY (id_seccion = ?) DESC, id_categoria");
                    $psC->execute([$idSeccion]);
                    foreach ($psC->fetchAll() as $rc):
                        $activeCls = ((string) $rc['id_categoria'] === (string) $catFiltro) ? 'active' : '';
                ?>
                    <a class="cat-tab <?= $activeCls ?>" href="orders.php?vista=rapido&cat=<?= (int) $rc['id_categoria'] ?>"><?= htmlspecialchars($rc['nombre']) ?></a>
                <?php endforeach; ?>
            </div>

            <?php /* ---- Grid de productos segun filtro/busqueda ---- */ ?>
            <div class="product-grid">
                <?php
                    $sql = "SELECT p.id_producto, p.nombre, p.precio, p.emoji, sc.nombre AS subcat
                            FROM productos p
                            JOIN subcategorias sc ON p.id_subcategoria = sc.id_subcategoria
                            JOIN categorias c ON sc.id_categoria = c.id_categoria
                            WHERE p.activo = 1 ";
                    $params = [];
                    if ($catFiltro !== null && $catFiltro !== '') { $sql .= 'AND c.id_categoria = ? '; $params[] = (int) $catFiltro; }
                    if ($q !== null && trim($q) !== '')          { $sql .= 'AND p.nombre LIKE ? '; $params[] = '%' . trim($q) . '%'; }
                    $sql .= 'ORDER BY p.nombre';

                    $psP = $pdoL->prepare($sql);
                    $psP->execute($params);
                    $productos = $psP->fetchAll();

                    if (empty($productos)):
                ?>
                        <div style="grid-column:1/-1;color:var(--text-muted);padding:24px;text-align:center">Sin resultados</div>
                <?php else: foreach ($productos as $rp2): ?>
                    <form method="post" action="orders.php" style="display:contents">
                        <input type="hidden" name="accion" value="addRapido">
                        <input type="hidden" name="vista" value="rapido">
                        <input type="hidden" name="idProducto" value="<?= (int) $rp2['id_producto'] ?>">
                        <button type="submit" class="product-card" style="border:none;text-align:left;cursor:pointer">
                            <div class="product-img"><span style="font-size:32px"><?= $rp2['emoji'] ?></span></div>
                            <div class="product-body">
                                <div class="product-name"><?= htmlspecialchars($rp2['nombre']) ?></div>
                                <div class="product-price">$<?= number_format($rp2['precio'], 2) ?></div>
                                <div class="product-cat-badge"><?= htmlspecialchars($rp2['subcat']) ?></div>
                            </div>
                        </button>
                    </form>
                <?php endforeach; endif; ?>
            </div>
        </section>

        <?php /* ============== TICKET / CARRITO ============== */ ?>
        <aside class="ticket-panel">
            <div class="ticket-header">
                <div>
                    <div class="ticket-title">Comanda · Ticket</div>
                    <div class="ticket-num">Pedido rapido</div>
                </div>
                <form method="post" action="orders.php">
                    <input type="hidden" name="accion" value="clearRapido">
                    <input type="hidden" name="vista" value="rapido">
                    <button type="submit" class="btn-ghost" style="font-size:11px">Limpiar</button>
                </form>
            </div>

            <div class="ticket-items">
            <?php
                $totalRapido = 0;
                if (empty($carritoRapido)):
            ?>
                <div class="ticket-empty"><span>Sin productos</span><span class="text-muted text-xs">Toca un producto para agregar</span></div>
            <?php
                else:
                    $psT = $pdoL->prepare('SELECT nombre, precio FROM productos WHERE id_producto = ?');
                    foreach ($carritoRapido as $idProd => $cant):
                        $psT->execute([$idProd]);
                        $rt = $psT->fetch();
                        if ($rt):
                            $subtotal = $rt['precio'] * $cant;
                            $totalRapido += $subtotal;
            ?>
                <div class="ticket-item">
                    <div class="qty-control">
                        <form method="post" action="orders.php">
                            <input type="hidden" name="accion" value="qtyRapido">
                            <input type="hidden" name="vista" value="rapido">
                            <input type="hidden" name="idProducto" value="<?= (int) $idProd ?>">
                            <input type="hidden" name="delta" value="-1">
                            <button type="submit" class="qty-btn">−</button>
                        </form>
                        <span class="qty-val"><?= (int) $cant ?></span>
                        <form method="post" action="orders.php">
                            <input type="hidden" name="accion" value="qtyRapido">
                            <input type="hidden" name="vista" value="rapido">
                            <input type="hidden" name="idProducto" value="<?= (int) $idProd ?>">
                            <input type="hidden" name="delta" value="1">
                            <button type="submit" class="qty-btn">+</button>
                        </form>
                    </div>
                    <div class="ticket-item-info">
                        <div class="ticket-item-name"><?= htmlspecialchars($rt['nombre']) ?></div>
                        <div class="ticket-item-price">$<?= number_format($subtotal, 2) ?></div>
                    </div>
                </div>
            <?php
                        endif;
                    endforeach;
                endif;
            ?>
            </div>

            <form method="post" action="orders.php">
            <div class="ticket-note">
                <textarea name="nota" placeholder="Nota: termino medio, sin sal..." rows="2"></textarea>
            </div>

            <div class="ticket-total">
                <span>Total</span>
                <span class="total-amount">$<?= number_format($totalRapido, 2) ?></span>
            </div>

            <div class="payment-row">
                <span class="pay-label">Metodo de pago (solo para Cobrar)</span>
                <div class="pay-options">
                    <label class="pay-opt"><input type="radio" name="metodoPago" value="efectivo" checked><span>Efectivo</span></label>
                    <label class="pay-opt"><input type="radio" name="metodoPago" value="yappy"><span>Yappy</span></label>
                    <label class="pay-opt"><input type="radio" name="metodoPago" value="tarjeta"><span>Tarjeta</span></label>
                </div>
            </div>

            <input type="hidden" name="vista" value="rapido">
            <div class="ticket-actions">
                <button type="submit" name="accion" value="enviarRapido" class="btn-kitchen">Enviar a cocina</button>
                <button type="submit" name="accion" value="cobrarRapido" class="btn-charge">Cobrar · Charge</button>
            </div>
            </form>
        </aside>
    </div>

    <?php else: ?>
    <?php /* ============================================================
             VISTA: MESAS
             ============================================================ */
        $mesaSel     = isset($_GET['mesaId']) ? (int) $_GET['mesaId'] : -1;
        $comensalSel = $_GET['comensal'] ?? 'C1';
        $catMesa     = $_GET['catMesa'] ?? null;
        $qMesa       = $_GET['qMesa'] ?? null;
        $pdoM = getConnection();
    ?>

    <?php if ($mesaSel === -1): ?>
    <?php /* ---------- SALON: elegir una mesa ---------- */ ?>
    <div class="tables-layout">
        <section class="tables-section">
            <div class="tables-container">
                <div class="tables-toprow">
                    <span class="tables-title">Salon · Mesas</span>
                    <div class="legend-row">
                        <span class="legend-item"><span class="table-dot free"></span> Libre</span>
                        <span class="legend-item"><span class="table-dot busy"></span> Ocupada</span>
                    </div>
                </div>
                <div class="tables-grid">
                <?php
                    $rsM = $pdoM->query('SELECT * FROM mesas ORDER BY numero');
                    foreach ($rsM->fetchAll() as $rm):
                        $ocupada = ($rm['estado'] === 'ocupada');
                ?>
                    <a href="orders.php?vista=mesas&mesaId=<?= (int) $rm['id_mesa'] ?>&comensal=C1" class="table-card <?= $ocupada ? 'busy' : '' ?>" style="text-decoration:none">
                        <div class="table-num"><?= (int) $rm['numero'] ?></div>
                        <div class="table-status"><?= $ocupada ? 'Ocupada' : 'Libre' ?></div>
                    </a>
                <?php endforeach; ?>
                </div>
            </div>
        </section>

        <aside class="mesa-detail">
            <div class="mesa-placeholder">Selecciona una mesa</div>
        </aside>
    </div>

    <?php else:
        // Mesa seleccionada: pantalla de toma de pedido igual a "Pedido rapido"
        // (busqueda + mas vendidos + categorias + grid), pero acotada a esta
        // Mesa + Comensal. Los tabs de comensal permiten manejar varios
        // clientes en la misma mesa, cada uno con su propio carrito/cuenta.
        $comensales = $comensalesPorMesa[$mesaSel] ?? ['C1'];
    ?>
    <div class="orders-layout">
        <section class="products-panel">

            <div class="orders-toprow">
                <form method="get" action="orders.php" class="search-bar" style="flex:1">
                    <input type="hidden" name="vista" value="mesas">
                    <input type="hidden" name="mesaId" value="<?= $mesaSel ?>">
                    <input type="hidden" name="comensal" value="<?= htmlspecialchars($comensalSel) ?>">
                    <span class="search-icon">🔍</span>
                    <input type="text" name="qMesa" value="<?= htmlspecialchars($qMesa ?? '') ?>" placeholder="Buscar producto...">
                </form>
                <span class="section-badge">Mesa <?= $mesaSel ?> · Comensal <?= htmlspecialchars(str_replace('C', '', $comensalSel)) ?></span>
                <a href="orders.php?vista=mesas" class="goto-mesas-btn" style="text-decoration:none">← Volver al salon</a>
            </div>

            <?php /* ---- Mas vendidos ---- */ ?>
            <div class="bestsellers-row">
                <div class="row-label">Mas vendidos · <span style="font-weight:400;text-transform:none">tap para agregar</span></div>
                <div class="bestsellers-chips">
                <?php
                    $rsBM = $pdoM->query("SELECT id_producto, nombre, precio FROM productos WHERE mas_vendido=1 AND activo=1");
                    foreach ($rsBM->fetchAll() as $rbm):
                ?>
                    <form method="post" action="orders.php" style="display:inline">
                        <input type="hidden" name="accion" value="addMesaProducto">
                        <input type="hidden" name="vista" value="mesas">
                        <input type="hidden" name="idMesa" value="<?= $mesaSel ?>">
                        <input type="hidden" name="comensal" value="<?= htmlspecialchars($comensalSel) ?>">
                        <input type="hidden" name="idProducto" value="<?= (int) $rbm['id_producto'] ?>">
                        <button type="submit" class="bestseller-chip" style="border:1px solid var(--card-border)">
                            <span><?= htmlspecialchars($rbm['nombre']) ?></span>
                            <span class="chip-price">$<?= number_format($rbm['precio'], 2) ?></span>
                        </button>
                    </form>
                <?php endforeach; ?>
                </div>
            </div>

            <?php /* ---- Categorias (propia seccion primero) ---- */ ?>
            <div class="cat-tabs">
                <a class="cat-tab <?= ($catMesa === null) ? 'active' : '' ?>" href="orders.php?vista=mesas&mesaId=<?= $mesaSel ?>&comensal=<?= htmlspecialchars($comensalSel) ?>">Todas</a>
                <?php
                    $psCM = $pdoM->prepare("SELECT id_categoria, nombre FROM categorias ORDER BY (id_seccion = ?) DESC, id_categoria");
                    $psCM->execute([$idSeccion]);
                    foreach ($psCM->fetchAll() as $rcm):
                        $activeClsM = ((string) $rcm['id_categoria'] === (string) $catMesa) ? 'active' : '';
                ?>
                    <a class="cat-tab <?= $activeClsM ?>" href="orders.php?vista=mesas&mesaId=<?= $mesaSel ?>&comensal=<?= htmlspecialchars($comensalSel) ?>&catMesa=<?= (int) $rcm['id_categoria'] ?>"><?= htmlspecialchars($rcm['nombre']) ?></a>
                <?php endforeach; ?>
            </div>

            <?php /* ---- Grid de productos segun filtro/busqueda ---- */ ?>
            <div class="product-grid">
                <?php
                    $sqlM = "SELECT p.id_producto, p.nombre, p.precio, p.emoji, sc.nombre AS subcat
                             FROM productos p
                             JOIN subcategorias sc ON p.id_subcategoria = sc.id_subcategoria
                             JOIN categorias c ON sc.id_categoria = c.id_categoria
                             WHERE p.activo = 1 ";
                    $paramsM = [];
                    if ($catMesa !== null && $catMesa !== '') { $sqlM .= 'AND c.id_categoria = ? '; $paramsM[] = (int) $catMesa; }
                    if ($qMesa !== null && trim($qMesa) !== '') { $sqlM .= 'AND p.nombre LIKE ? '; $paramsM[] = '%' . trim($qMesa) . '%'; }
                    $sqlM .= 'ORDER BY p.nombre';

                    $psPM = $pdoM->prepare($sqlM);
                    $psPM->execute($paramsM);
                    $productosM = $psPM->fetchAll();

                    if (empty($productosM)):
                ?>
                        <div style="grid-column:1/-1;color:var(--text-muted);padding:24px;text-align:center">Sin resultados</div>
                <?php else: foreach ($productosM as $rpm): ?>
                    <form method="post" action="orders.php" style="display:contents">
                        <input type="hidden" name="accion" value="addMesaProducto">
                        <input type="hidden" name="vista" value="mesas">
                        <input type="hidden" name="idMesa" value="<?= $mesaSel ?>">
                        <input type="hidden" name="comensal" value="<?= htmlspecialchars($comensalSel) ?>">
                        <input type="hidden" name="idProducto" value="<?= (int) $rpm['id_producto'] ?>">
                        <button type="submit" class="product-card" style="border:none;text-align:left;cursor:pointer">
                            <div class="product-img"><span style="font-size:32px"><?= $rpm['emoji'] ?></span></div>
                            <div class="product-body">
                                <div class="product-name"><?= htmlspecialchars($rpm['nombre']) ?></div>
                                <div class="product-price">$<?= number_format($rpm['precio'], 2) ?></div>
                                <div class="product-cat-badge"><?= htmlspecialchars($rpm['subcat']) ?></div>
                            </div>
                        </button>
                    </form>
                <?php endforeach; endif; ?>
            </div>
        </section>

        <?php /* ============== TICKET DE LA MESA / COMENSAL ============== */ ?>
        <aside class="ticket-panel">
            <div class="ticket-header">
                <div>
                    <div class="ticket-title">Mesa <?= $mesaSel ?></div>
                    <div class="ticket-num">Cuenta por comensal</div>
                </div>
                <a href="orders.php?vista=mesas" class="btn-ghost" style="font-size:11px;text-decoration:none">✕</a>
            </div>

            <div style="padding:0 var(--sp-5)">
                <div class="comensal-tabs">
                    <?php foreach ($comensales as $c): ?>
                        <a href="orders.php?vista=mesas&mesaId=<?= $mesaSel ?>&comensal=<?= htmlspecialchars($c) ?>"
                           class="comensal-tab <?= ($c === $comensalSel) ? 'active' : '' ?>" style="text-decoration:none"><?= htmlspecialchars($c) ?></a>
                    <?php endforeach; ?>
                    <form method="post" action="orders.php" style="display:inline">
                        <input type="hidden" name="accion" value="addComensal">
                        <input type="hidden" name="vista" value="mesas">
                        <input type="hidden" name="idMesa" value="<?= $mesaSel ?>">
                        <button type="submit" class="comensal-tab add-btn">+</button>
                    </form>
                </div>
            </div>

            <div class="ticket-items">
            <?php
                $claveSel   = $mesaSel . '_' . $comensalSel;
                $carritoSel = $carritosMesa[$claveSel] ?? [];
                $totalMesa  = 0;
                if (empty($carritoSel)):
            ?>
                <div class="ticket-empty"><span>Sin productos</span><span class="text-muted text-xs">Toca un producto para agregar</span></div>
            <?php
                else:
                    $psTM = $pdoM->prepare('SELECT nombre, precio FROM productos WHERE id_producto = ?');
                    foreach ($carritoSel as $idProd => $cant):
                        $psTM->execute([$idProd]);
                        $rtm = $psTM->fetch();
                        if ($rtm):
                            $subM = $rtm['precio'] * $cant;
                            $totalMesa += $subM;
            ?>
                <div class="ticket-item">
                    <div class="qty-control">
                        <form method="post" action="orders.php">
                            <input type="hidden" name="accion" value="qtyMesa">
                            <input type="hidden" name="vista" value="mesas">
                            <input type="hidden" name="idMesa" value="<?= $mesaSel ?>">
                            <input type="hidden" name="comensal" value="<?= htmlspecialchars($comensalSel) ?>">
                            <input type="hidden" name="idProducto" value="<?= (int) $idProd ?>">
                            <input type="hidden" name="delta" value="-1">
                            <button type="submit" class="qty-btn">−</button>
                        </form>
                        <span class="qty-val"><?= (int) $cant ?></span>
                        <form method="post" action="orders.php">
                            <input type="hidden" name="accion" value="qtyMesa">
                            <input type="hidden" name="vista" value="mesas">
                            <input type="hidden" name="idMesa" value="<?= $mesaSel ?>">
                            <input type="hidden" name="comensal" value="<?= htmlspecialchars($comensalSel) ?>">
                            <input type="hidden" name="idProducto" value="<?= (int) $idProd ?>">
                            <input type="hidden" name="delta" value="1">
                            <button type="submit" class="qty-btn">+</button>
                        </form>
                    </div>
                    <div class="ticket-item-info">
                        <div class="ticket-item-name"><?= htmlspecialchars($rtm['nombre']) ?></div>
                        <div class="ticket-item-price">$<?= number_format($subM, 2) ?></div>
                    </div>
                </div>
            <?php
                        endif;
                    endforeach;
                endif;
            ?>
            </div>

            <form method="post" action="orders.php">
            <div class="ticket-total">
                <span>Subtotal <?= htmlspecialchars($comensalSel) ?></span>
                <span class="total-amount">$<?= number_format($totalMesa, 2) ?></span>
            </div>

            <div class="payment-row">
                <span class="pay-label">Metodo de pago (solo para Cobrar)</span>
                <div class="pay-options">
                    <label class="pay-opt"><input type="radio" name="metodoPago" value="efectivo" checked><span>Efectivo</span></label>
                    <label class="pay-opt"><input type="radio" name="metodoPago" value="yappy"><span>Yappy</span></label>
                    <label class="pay-opt"><input type="radio" name="metodoPago" value="tarjeta"><span>Tarjeta</span></label>
                </div>
            </div>

            <input type="hidden" name="vista" value="mesas">
            <input type="hidden" name="idMesa" value="<?= $mesaSel ?>">
            <input type="hidden" name="comensal" value="<?= htmlspecialchars($comensalSel) ?>">
            <div class="ticket-actions">
                <button type="submit" name="accion" value="enviarMesa" class="btn-kitchen">Enviar a cocina</button>
                <button type="submit" name="accion" value="cobrarMesa" class="btn-charge">Cobrar comensal</button>
            </div>
            </form>
        </aside>
    </div>
    <?php endif; ?>

    <?php endif; ?>

</main>

<?php require __DIR__ . '/../includes/footer.php'; ?>
</body>
</html>
