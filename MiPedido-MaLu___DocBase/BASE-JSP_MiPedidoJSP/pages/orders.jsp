<%-- ============================================================
     orders.jsp — Pedido rapido + Mesas (CASHIER / SUPERVISOR / GERENTE)
     Toda la logica de negocio esta en scriptlets Java embebidos,
     tal como se pidio (HTML + CSS + JSP, sin Servlets separados).
     ============================================================ --%>
<%@ page contentType="text/html; charset=UTF-8" pageEncoding="UTF-8" %>
<%@ page import="java.sql.*" %>
<%@ page import="java.util.*" %>
<%
    // Solo estos roles pueden ver esta pagina
    String[] rolesPermitidos = {"cashier","supervisor","gerente"};
%>
<%@ include file="../includes/auth-check.jsp" %>
<%@ include file="../includes/db.jsp" %>
<%
    // ---------------------------------------------------------
    // Datos de la sesion
    // ---------------------------------------------------------
    Integer idUsuario   = (Integer) session.getAttribute("idUsuario");
    Object  idSeccionObj= session.getAttribute("idSeccion");
    int     idSeccion   = (idSeccionObj != null) ? (Integer) idSeccionObj : 1; // gerente/supervisor sin seccion -> Restaurante por defecto
    String  nombreUsuario = (String) session.getAttribute("nombre");
    String  toast = null; // mensaje que se muestra despues de una accion

    // ---------------------------------------------------------
    // Carrito de "Pedido rapido" guardado en SESION
    // Map<idProducto, cantidad>
    // ---------------------------------------------------------
    LinkedHashMap<Integer,Integer> carritoRapido =
        (LinkedHashMap<Integer,Integer>) session.getAttribute("carritoRapido");
    if (carritoRapido == null) { carritoRapido = new LinkedHashMap<Integer,Integer>(); }

    // ---------------------------------------------------------
    // Carritos por Mesa+Comensal guardados en SESION
    // Map<"idMesa_comensal", Map<idProducto,cantidad>>
    // ---------------------------------------------------------
    LinkedHashMap<String,LinkedHashMap<Integer,Integer>> carritosMesa =
        (LinkedHashMap<String,LinkedHashMap<Integer,Integer>>) session.getAttribute("carritosMesa");
    if (carritosMesa == null) { carritosMesa = new LinkedHashMap<String,LinkedHashMap<Integer,Integer>>(); }

    // Lista de comensales activos por mesa: Map<idMesa, List<String>> ej. 2 -> [C1,C2]
    LinkedHashMap<Integer,List<String>> comensalesPorMesa =
        (LinkedHashMap<Integer,List<String>>) session.getAttribute("comensalesPorMesa");
    if (comensalesPorMesa == null) { comensalesPorMesa = new LinkedHashMap<Integer,List<String>>(); }

    // ---------------------------------------------------------
    // Procesar ACCIONES enviadas por formularios (POST) - patron
    // Post/Redirect/Get para evitar reenvios al refrescar la pagina
    // ---------------------------------------------------------
    String accion = request.getParameter("accion");
    if (accion != null && "POST".equalsIgnoreCase(request.getMethod())) {

        Connection con = null;
        // Contexto (mesa/comensal) para reconstruir la redireccion sin perder
        // la pantalla en la que estaba el usuario (patron Post/Redirect/Get)
        String mesaIdCtx   = request.getParameter("idMesa");
        String comensalCtx = request.getParameter("comensal");
        try {
            if (accion.equals("addRapido")) {
                int idProd = Integer.parseInt(request.getParameter("idProducto"));
                carritoRapido.merge(idProd, 1, Integer::sum);

            } else if (accion.equals("qtyRapido")) {
                int idProd = Integer.parseInt(request.getParameter("idProducto"));
                int delta  = Integer.parseInt(request.getParameter("delta"));
                Integer actual = carritoRapido.get(idProd);
                if (actual != null) {
                    int nueva = actual + delta;
                    if (nueva <= 0) carritoRapido.remove(idProd);
                    else carritoRapido.put(idProd, nueva);
                }

            } else if (accion.equals("clearRapido")) {
                carritoRapido.clear();

            } else if (accion.equals("enviarRapido") || accion.equals("cobrarRapido")) {
                if (!carritoRapido.isEmpty()) {
                    con = getConnection();
                    con.setAutoCommit(false);

                    String nota = request.getParameter("nota");
                    boolean esCobro = accion.equals("cobrarRapido");
                    String metodoPago = request.getParameter("metodoPago");
                    String estadoInicial = esCobro ? "entregado" : "pendiente";

                    // Calculamos el total con precios reales de la BD (nunca confiar en el cliente)
                    double total = 0;
                    PreparedStatement psPrecio = con.prepareStatement("SELECT precio FROM productos WHERE id_producto = ?");
                    for (Map.Entry<Integer,Integer> item : carritoRapido.entrySet()) {
                        psPrecio.setInt(1, item.getKey());
                        ResultSet rsP = psPrecio.executeQuery();
                        if (rsP.next()) total += rsP.getDouble("precio") * item.getValue();
                        rsP.close();
                    }
                    psPrecio.close();

                    PreparedStatement psPedido = con.prepareStatement(
                        "INSERT INTO pedidos (numero_pedido, origen, id_usuario, id_seccion, estado, nota, metodo_pago, total, cobrado) " +
                        "VALUES (0, 'rapido', ?, ?, ?, ?, ?, ?, ?)", Statement.RETURN_GENERATED_KEYS);
                    psPedido.setInt(1, idUsuario);
                    psPedido.setInt(2, idSeccion);
                    psPedido.setString(3, estadoInicial);
                    psPedido.setString(4, (nota != null && !nota.trim().isEmpty()) ? nota.trim() : null);
                    psPedido.setString(5, esCobro ? metodoPago : null);
                    psPedido.setDouble(6, total);
                    psPedido.setInt(7, esCobro ? 1 : 0);
                    psPedido.executeUpdate();

                    int idPedidoNuevo = -1;
                    ResultSet keys = psPedido.getGeneratedKeys();
                    if (keys.next()) idPedidoNuevo = keys.getInt(1);
                    keys.close();
                    psPedido.close();

                    // El numero de pedido visible = mismo id (orden de entrada)
                    PreparedStatement psNum = con.prepareStatement("UPDATE pedidos SET numero_pedido = ? WHERE id_pedido = ?");
                    psNum.setInt(1, idPedidoNuevo);
                    psNum.setInt(2, idPedidoNuevo);
                    psNum.executeUpdate();
                    psNum.close();

                    PreparedStatement psDetalle = con.prepareStatement(
                        "INSERT INTO pedido_detalle (id_pedido, id_producto, cantidad, preparado) VALUES (?, ?, ?, 0)");
                    for (Map.Entry<Integer,Integer> item : carritoRapido.entrySet()) {
                        psDetalle.setInt(1, idPedidoNuevo);
                        psDetalle.setInt(2, item.getKey());
                        psDetalle.setInt(3, item.getValue());
                        psDetalle.addBatch();
                    }
                    psDetalle.executeBatch();
                    psDetalle.close();

                    if (esCobro) {
                        PreparedStatement psFactura = con.prepareStatement(
                            "INSERT INTO facturas (id_pedido, total, metodo_pago, id_seccion) VALUES (?, ?, ?, ?)");
                        psFactura.setInt(1, idPedidoNuevo);
                        psFactura.setDouble(2, total);
                        psFactura.setString(3, metodoPago);
                        psFactura.setInt(4, idSeccion);
                        psFactura.executeUpdate();
                        psFactura.close();
                    }

                    con.commit();
                    carritoRapido.clear();
                    toast = esCobro ? "Cobrado correctamente. Factura #" + idPedidoNuevo
                                    : "Comanda #" + idPedidoNuevo + " enviada a cocina";
                }

            } else if (accion.equals("cobrarListo")) {
                // Cobra un pedido que la cocina ya marco como "listo" (cierra el ciclo cocina -> caja)
                int idPedidoListo = Integer.parseInt(request.getParameter("idPedido"));
                String metodoPagoListo = request.getParameter("metodoPago");
                con = getConnection();
                con.setAutoCommit(false);

                PreparedStatement psTotal = con.prepareStatement("SELECT total, id_seccion FROM pedidos WHERE id_pedido = ? AND estado='listo' AND cobrado = 0");
                psTotal.setInt(1, idPedidoListo);
                ResultSet rsTotal = psTotal.executeQuery();
                if (rsTotal.next()) {
                    double totalListo = rsTotal.getDouble("total");
                    int seccionListo = rsTotal.getInt("id_seccion");
                    rsTotal.close(); psTotal.close();

                    PreparedStatement psUpd = con.prepareStatement(
                        "UPDATE pedidos SET estado='entregado', cobrado=1, metodo_pago=? WHERE id_pedido=?");
                    psUpd.setString(1, metodoPagoListo);
                    psUpd.setInt(2, idPedidoListo);
                    psUpd.executeUpdate();
                    psUpd.close();

                    PreparedStatement psFac = con.prepareStatement(
                        "INSERT INTO facturas (id_pedido, total, metodo_pago, id_seccion) VALUES (?,?,?,?)");
                    psFac.setInt(1, idPedidoListo);
                    psFac.setDouble(2, totalListo);
                    psFac.setString(3, metodoPagoListo);
                    psFac.setInt(4, seccionListo);
                    psFac.executeUpdate();
                    psFac.close();

                    con.commit();
                    toast = "Pedido #" + idPedidoListo + " cobrado. Factura generada.";
                } else {
                    rsTotal.close(); psTotal.close();
                }

            } else if (accion.equals("addComensal")) {
                int idMesa = Integer.parseInt(request.getParameter("idMesa"));
                List<String> lista = comensalesPorMesa.get(idMesa);
                if (lista == null) { lista = new ArrayList<String>(); lista.add("C1"); }
                String nuevoComensal = "C" + (lista.size() + 1);
                lista.add(nuevoComensal);
                comensalesPorMesa.put(idMesa, lista);
                comensalCtx = nuevoComensal; // al agregar comensal saltamos directo a su cuenta

            } else if (accion.equals("addMesaProducto")) {
                int idMesa = Integer.parseInt(request.getParameter("idMesa"));
                String comensal = request.getParameter("comensal");
                int idProd = Integer.parseInt(request.getParameter("idProducto"));
                String clave = idMesa + "_" + comensal;
                LinkedHashMap<Integer,Integer> carritoC = carritosMesa.get(clave);
                if (carritoC == null) { carritoC = new LinkedHashMap<Integer,Integer>(); }
                carritoC.merge(idProd, 1, Integer::sum);
                carritosMesa.put(clave, carritoC);
                // marcar mesa como ocupada
                con = getConnection();
                PreparedStatement psMesa = con.prepareStatement("UPDATE mesas SET estado='ocupada' WHERE id_mesa=?");
                psMesa.setInt(1, idMesa);
                psMesa.executeUpdate();
                psMesa.close();

            } else if (accion.equals("qtyMesa")) {
                int idMesa = Integer.parseInt(request.getParameter("idMesa"));
                String comensal = request.getParameter("comensal");
                int idProd = Integer.parseInt(request.getParameter("idProducto"));
                int delta  = Integer.parseInt(request.getParameter("delta"));
                String clave = idMesa + "_" + comensal;
                LinkedHashMap<Integer,Integer> carritoC = carritosMesa.get(clave);
                if (carritoC != null) {
                    Integer actual = carritoC.get(idProd);
                    if (actual != null) {
                        int nueva = actual + delta;
                        if (nueva <= 0) carritoC.remove(idProd);
                        else carritoC.put(idProd, nueva);
                    }
                }

            } else if (accion.equals("enviarMesa") || accion.equals("cobrarMesa")) {
                int idMesa = Integer.parseInt(request.getParameter("idMesa"));
                String comensal = request.getParameter("comensal");
                String clave = idMesa + "_" + comensal;
                LinkedHashMap<Integer,Integer> carritoC = carritosMesa.get(clave);

                if (carritoC != null && !carritoC.isEmpty()) {
                    con = getConnection();
                    con.setAutoCommit(false);

                    boolean esCobro = accion.equals("cobrarMesa");
                    String metodoPago = request.getParameter("metodoPago");
                    String estadoInicial = esCobro ? "entregado" : "pendiente";

                    double total = 0;
                    PreparedStatement psPrecio = con.prepareStatement("SELECT precio FROM productos WHERE id_producto = ?");
                    for (Map.Entry<Integer,Integer> item : carritoC.entrySet()) {
                        psPrecio.setInt(1, item.getKey());
                        ResultSet rsP = psPrecio.executeQuery();
                        if (rsP.next()) total += rsP.getDouble("precio") * item.getValue();
                        rsP.close();
                    }
                    psPrecio.close();

                    PreparedStatement psPedido = con.prepareStatement(
                        "INSERT INTO pedidos (numero_pedido, origen, id_mesa, comensal, id_usuario, id_seccion, estado, metodo_pago, total, cobrado) " +
                        "VALUES (0, 'mesa', ?, ?, ?, ?, ?, ?, ?, ?)", Statement.RETURN_GENERATED_KEYS);
                    psPedido.setInt(1, idMesa);
                    psPedido.setString(2, comensal);
                    psPedido.setInt(3, idUsuario);
                    psPedido.setInt(4, idSeccion);
                    psPedido.setString(5, estadoInicial);
                    psPedido.setString(6, esCobro ? metodoPago : null);
                    psPedido.setDouble(7, total);
                    psPedido.setInt(8, esCobro ? 1 : 0);
                    psPedido.executeUpdate();

                    int idPedidoNuevo = -1;
                    ResultSet keys = psPedido.getGeneratedKeys();
                    if (keys.next()) idPedidoNuevo = keys.getInt(1);
                    keys.close();
                    psPedido.close();

                    PreparedStatement psNum = con.prepareStatement("UPDATE pedidos SET numero_pedido = ? WHERE id_pedido = ?");
                    psNum.setInt(1, idPedidoNuevo);
                    psNum.setInt(2, idPedidoNuevo);
                    psNum.executeUpdate();
                    psNum.close();

                    PreparedStatement psDetalle = con.prepareStatement(
                        "INSERT INTO pedido_detalle (id_pedido, id_producto, cantidad, preparado) VALUES (?, ?, ?, 0)");
                    for (Map.Entry<Integer,Integer> item : carritoC.entrySet()) {
                        psDetalle.setInt(1, idPedidoNuevo);
                        psDetalle.setInt(2, item.getKey());
                        psDetalle.setInt(3, item.getValue());
                        psDetalle.addBatch();
                    }
                    psDetalle.executeBatch();
                    psDetalle.close();

                    if (esCobro) {
                        PreparedStatement psFactura = con.prepareStatement(
                            "INSERT INTO facturas (id_pedido, total, metodo_pago, id_seccion) VALUES (?, ?, ?, ?)");
                        psFactura.setInt(1, idPedidoNuevo);
                        psFactura.setDouble(2, total);
                        psFactura.setString(3, metodoPago);
                        psFactura.setInt(4, idSeccion);
                        psFactura.executeUpdate();
                        psFactura.close();

                        // liberamos al comensal de la sesion
                        carritosMesa.remove(clave);
                        List<String> lista = comensalesPorMesa.get(idMesa);
                        if (lista != null) lista.remove(comensal);

                        // si ya no quedan comensales activos para esa mesa, la liberamos
                        boolean mesaVacia = (lista == null || lista.isEmpty());
                        if (mesaVacia) {
                            PreparedStatement psLibre = con.prepareStatement("UPDATE mesas SET estado='libre' WHERE id_mesa=?");
                            psLibre.setInt(1, idMesa);
                            psLibre.executeUpdate();
                            psLibre.close();
                            // Mesa liberada (vuelve a su color original) -> al redirigir
                            // regresamos al salon de mesas en vez de quedarnos en esta mesa
                            mesaIdCtx = null;
                            comensalCtx = null;
                        } else {
                            // Siguen quedando comensales en la mesa: saltamos al primero activo
                            comensalCtx = lista.get(0);
                        }
                    } else {
                        carritoC.clear();
                    }

                    con.commit();
                    toast = esCobro ? "Comensal " + comensal + " cobrado. Factura #" + idPedidoNuevo
                                    : "Comanda #" + idPedidoNuevo + " (Mesa " + idMesa + " · " + comensal + ") enviada a cocina";
                }
            }

        } catch (Exception ex) {
            if (con != null) { try { con.rollback(); } catch (Exception ignore) {} }
            toast = "Error al procesar la accion: " + ex.getMessage();
        } finally {
            if (con != null) { try { con.setAutoCommit(true); con.close(); } catch (Exception ignore) {} }
        }

        session.setAttribute("carritoRapido", carritoRapido);
        session.setAttribute("carritosMesa", carritosMesa);
        session.setAttribute("comensalesPorMesa", comensalesPorMesa);
        session.setAttribute("toastOrders", toast);

        // Redirigimos preservando mesa/comensal (si aplica) para no perder la
        // pantalla en la que estaba el usuario (patron Post/Redirect/Get).
        // Antes este redirect solo conservaba "vista", por lo que cada vez que
        // se agregaba un producto a una mesa se perdia la mesa/comensal
        // seleccionados y el usuario volvia siempre al salon de mesas.
        StringBuilder redirectUrl = new StringBuilder(request.getContextPath() + "/pages/orders.jsp");
        String vistaParam = request.getParameter("vista");
        if (vistaParam != null) {
            redirectUrl.append("?vista=").append(vistaParam);
            if ("mesas".equals(vistaParam) && mesaIdCtx != null) {
                redirectUrl.append("&mesaId=").append(mesaIdCtx);
                if (comensalCtx != null) redirectUrl.append("&comensal=").append(comensalCtx);
            }
        }
        response.sendRedirect(redirectUrl.toString());
        return;
    }

    // Recuperamos el toast dejado por una redireccion anterior
    if (toast == null) {
        toast = (String) session.getAttribute("toastOrders");
        session.removeAttribute("toastOrders");
    }

    String vista = request.getParameter("vista") != null ? request.getParameter("vista") : "rapido";
    String catFiltro = request.getParameter("cat");
    String q = request.getParameter("q");
%>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ordenes · MiPedido</title>
    <link rel="stylesheet" href="../css/main.css">
    <link rel="stylesheet" href="../css/layout.css">
    <link rel="stylesheet" href="../css/orders.css">
</head>
<body>

<%@ include file="../includes/header.jsp" %>

<main class="main-content" style="min-height:auto">

    <% if (toast != null) { %>
        <div class="alert-box alert-success">✅ <%= toast %></div>
    <% } %>

    <!-- Tabs Pedido rapido / Mesas (navegacion simple por enlace, sin JS) -->
    <div class="view-tabs">
        <a class="view-tab <%= "rapido".equals(vista) ? "active" : "" %>" href="orders.jsp?vista=rapido">⚡ Pedido rapido</a>
        <a class="view-tab <%= "mesas".equals(vista) ? "active" : "" %>" href="orders.jsp?vista=mesas">⬛ Mesas · Tables</a>
    </div>

    <% if ("rapido".equals(vista)) { %>
    <%-- ============================================================
         VISTA: PEDIDO RAPIDO
         ============================================================ --%>

    <%-- Pedidos que la cocina ya marco "Listo" y esperan cobro (cierra el ciclo cocina -> caja) --%>
    <%
        Connection conL = getConnection();
        PreparedStatement psL = conL.prepareStatement(
            "SELECT id_pedido, origen, id_mesa, comensal, total, fecha_hora FROM pedidos " +
            "WHERE estado='listo' AND cobrado=0 AND id_seccion=? ORDER BY fecha_hora");
        psL.setInt(1, idSeccion);
        ResultSet rsL = psL.executeQuery();
        boolean hayListos = false;
    %>
    <div class="card" style="margin-bottom:16px">
        <div class="page-subtitle" style="margin-bottom:8px;font-weight:700">🔔 Listos en cocina · esperando cobro</div>
        <div style="display:flex;flex-wrap:wrap;gap:10px">
        <% while (rsL.next()) { hayListos = true;
             String origenLbl = "mesa".equals(rsL.getString("origen")) ? ("Mesa " + rsL.getInt("id_mesa") + " · " + rsL.getString("comensal")) : "Pedido rapido";
        %>
            <form method="post" action="orders.jsp" style="display:flex;align-items:center;gap:8px;background:var(--content-bg);border:1px solid var(--card-border);border-radius:8px;padding:8px 12px">
                <input type="hidden" name="accion" value="cobrarListo">
                <input type="hidden" name="vista" value="rapido">
                <input type="hidden" name="idPedido" value="<%= rsL.getInt("id_pedido") %>">
                <span style="font-size:12px;font-weight:700">#<%= rsL.getInt("id_pedido") %> · <%= origenLbl %> · $<%= String.format("%.2f", rsL.getDouble("total")) %></span>
                <select name="metodoPago" style="font-size:11px;padding:4px;border-radius:4px">
                    <option value="efectivo">Efectivo</option>
                    <option value="yappy">Yappy</option>
                    <option value="tarjeta">Tarjeta</option>
                </select>
                <button type="submit" class="btn-primary" style="font-size:11px;padding:6px 10px">Cobrar</button>
            </form>
        <% }
           rsL.close(); psL.close(); conL.close();
           if (!hayListos) { %>
            <span class="text-muted text-xs">No hay pedidos listos pendientes de cobro.</span>
        <% } %>
        </div>
    </div>

    <div class="orders-layout">
        <section class="products-panel">

            <div class="orders-toprow">
                <form method="get" action="orders.jsp" class="search-bar" style="flex:1">
                    <input type="hidden" name="vista" value="rapido">
                    <span class="search-icon">🔍</span>
                    <input type="text" name="q" value="<%= q != null ? q : "" %>" placeholder="Buscar producto...">
                </form>
                <span class="section-badge">Seccion: <%= session.getAttribute("seccionNombre") %></span>
            </div>

            <%
                // ---- Mas vendidos ----
            %>
            <div class="bestsellers-row">
                <div class="row-label">Mas vendidos · <span style="font-weight:400;text-transform:none">tap para agregar</span></div>
                <div class="bestsellers-chips">
                <%
                    Connection conB = getConnection();
                    PreparedStatement psB = conB.prepareStatement("SELECT id_producto, nombre, precio FROM productos WHERE mas_vendido=1 AND activo=1");
                    ResultSet rsB = psB.executeQuery();
                    while (rsB.next()) {
                %>
                    <form method="post" action="orders.jsp" style="display:inline">
                        <input type="hidden" name="accion" value="addRapido">
                        <input type="hidden" name="vista" value="rapido">
                        <input type="hidden" name="idProducto" value="<%= rsB.getInt("id_producto") %>">
                        <button type="submit" class="bestseller-chip" style="border:1px solid var(--card-border)">
                            <span><%= rsB.getString("nombre") %></span>
                            <span class="chip-price">$<%= String.format("%.2f", rsB.getDouble("precio")) %></span>
                        </button>
                    </form>
                <%
                    }
                    rsB.close(); psB.close(); conB.close();
                %>
                </div>
            </div>

            <%
                // ---- Categorias (propia seccion primero) ----
            %>
            <div class="cat-tabs">
                <a class="cat-tab <%= (catFiltro == null) ? "active" : "" %>" href="orders.jsp?vista=rapido">Todas</a>
                <%
                    Connection conC = getConnection();
                    PreparedStatement psC = conC.prepareStatement(
                        "SELECT id_categoria, nombre FROM categorias ORDER BY (id_seccion = ?) DESC, id_categoria");
                    psC.setInt(1, idSeccion);
                    ResultSet rsC = psC.executeQuery();
                    while (rsC.next()) {
                        String activeCls = String.valueOf(rsC.getInt("id_categoria")).equals(catFiltro) ? "active" : "";
                %>
                    <a class="cat-tab <%= activeCls %>" href="orders.jsp?vista=rapido&cat=<%= rsC.getInt("id_categoria") %>"><%= rsC.getString("nombre") %></a>
                <%
                    }
                    rsC.close(); psC.close(); conC.close();
                %>
            </div>

            <%
                // ---- Grid de productos segun filtro/busqueda ----
            %>
            <div class="product-grid">
                <%
                    StringBuilder sql = new StringBuilder(
                        "SELECT p.id_producto, p.nombre, p.precio, p.emoji, sc.nombre AS subcat " +
                        "FROM productos p " +
                        "JOIN subcategorias sc ON p.id_subcategoria = sc.id_subcategoria " +
                        "JOIN categorias c ON sc.id_categoria = c.id_categoria " +
                        "WHERE p.activo = 1 ");
                    if (catFiltro != null && !catFiltro.isEmpty()) sql.append("AND c.id_categoria = ? ");
                    if (q != null && !q.trim().isEmpty()) sql.append("AND p.nombre LIKE ? ");
                    sql.append("ORDER BY p.nombre");

                    Connection conP = getConnection();
                    PreparedStatement psP = conP.prepareStatement(sql.toString());
                    int paramIdx = 1;
                    if (catFiltro != null && !catFiltro.isEmpty()) psP.setInt(paramIdx++, Integer.parseInt(catFiltro));
                    if (q != null && !q.trim().isEmpty()) psP.setString(paramIdx++, "%" + q.trim() + "%");
                    ResultSet rsP2 = psP.executeQuery();
                    boolean hayProductos = false;
                    while (rsP2.next()) {
                        hayProductos = true;
                %>
                    <form method="post" action="orders.jsp" style="display:contents">
                        <input type="hidden" name="accion" value="addRapido">
                        <input type="hidden" name="vista" value="rapido">
                        <input type="hidden" name="idProducto" value="<%= rsP2.getInt("id_producto") %>">
                        <button type="submit" class="product-card" style="border:none;text-align:left;cursor:pointer">
                            <div class="product-img"><span style="font-size:32px"><%= rsP2.getString("emoji") %></span></div>
                            <div class="product-body">
                                <div class="product-name"><%= rsP2.getString("nombre") %></div>
                                <div class="product-price">$<%= String.format("%.2f", rsP2.getDouble("precio")) %></div>
                                <div class="product-cat-badge"><%= rsP2.getString("subcat") %></div>
                            </div>
                        </button>
                    </form>
                <%
                    }
                    if (!hayProductos) { %>
                        <div style="grid-column:1/-1;color:var(--text-muted);padding:24px;text-align:center">Sin resultados</div>
                    <% }
                    rsP2.close(); psP.close(); conP.close();
                %>
            </div>
        </section>

        <%-- ============== TICKET / CARRITO ============== --%>
        <aside class="ticket-panel">
            <div class="ticket-header">
                <div>
                    <div class="ticket-title">Comanda · Ticket</div>
                    <div class="ticket-num">Pedido rapido</div>
                </div>
                <form method="post" action="orders.jsp">
                    <input type="hidden" name="accion" value="clearRapido">
                    <input type="hidden" name="vista" value="rapido">
                    <button type="submit" class="btn-ghost" style="font-size:11px">Limpiar</button>
                </form>
            </div>

            <div class="ticket-items">
            <%
                double totalRapido = 0;
                if (carritoRapido.isEmpty()) {
            %>
                <div class="ticket-empty"><span>Sin productos</span><span class="text-muted text-xs">Toca un producto para agregar</span></div>
            <%
                } else {
                    Connection conT = getConnection();
                    for (Map.Entry<Integer,Integer> item : carritoRapido.entrySet()) {
                        PreparedStatement psT = conT.prepareStatement("SELECT nombre, precio FROM productos WHERE id_producto = ?");
                        psT.setInt(1, item.getKey());
                        ResultSet rsT = psT.executeQuery();
                        if (rsT.next()) {
                            double subtotal = rsT.getDouble("precio") * item.getValue();
                            totalRapido += subtotal;
            %>
                <div class="ticket-item">
                    <div class="qty-control">
                        <form method="post" action="orders.jsp">
                            <input type="hidden" name="accion" value="qtyRapido">
                            <input type="hidden" name="vista" value="rapido">
                            <input type="hidden" name="idProducto" value="<%= item.getKey() %>">
                            <input type="hidden" name="delta" value="-1">
                            <button type="submit" class="qty-btn">−</button>
                        </form>
                        <span class="qty-val"><%= item.getValue() %></span>
                        <form method="post" action="orders.jsp">
                            <input type="hidden" name="accion" value="qtyRapido">
                            <input type="hidden" name="vista" value="rapido">
                            <input type="hidden" name="idProducto" value="<%= item.getKey() %>">
                            <input type="hidden" name="delta" value="1">
                            <button type="submit" class="qty-btn">+</button>
                        </form>
                    </div>
                    <div class="ticket-item-info">
                        <div class="ticket-item-name"><%= rsT.getString("nombre") %></div>
                        <div class="ticket-item-price">$<%= String.format("%.2f", subtotal) %></div>
                    </div>
                </div>
            <%
                        }
                        rsT.close(); psT.close();
                    }
                    conT.close();
                }
            %>
            </div>

            <form method="post" action="orders.jsp">
            <div class="ticket-note">
                <textarea name="nota" placeholder="Nota: termino medio, sin sal..." rows="2"></textarea>
            </div>

            <div class="ticket-total">
                <span>Total</span>
                <span class="total-amount">$<%= String.format("%.2f", totalRapido) %></span>
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

    <% } else { %>
    <%-- ============================================================
         VISTA: MESAS
         ============================================================ --%>
    <%
        int mesaSel = request.getParameter("mesaId") != null ? Integer.parseInt(request.getParameter("mesaId")) : -1;
        String comensalSel = request.getParameter("comensal") != null ? request.getParameter("comensal") : "C1";
        String catMesa = request.getParameter("catMesa");
        String qMesa = request.getParameter("qMesa");
    %>

    <% if (mesaSel == -1) { %>
    <%-- ---------- SALON: elegir una mesa ---------- --%>
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
                <%
                    Connection conM = getConnection();
                    PreparedStatement psM = conM.prepareStatement("SELECT * FROM mesas ORDER BY numero");
                    ResultSet rsM = psM.executeQuery();
                    while (rsM.next()) {
                        boolean ocupada = "ocupada".equals(rsM.getString("estado"));
                %>
                    <a href="orders.jsp?vista=mesas&mesaId=<%= rsM.getInt("id_mesa") %>&comensal=C1" class="table-card <%= ocupada ? "busy" : "" %>" style="text-decoration:none">
                        <div class="table-num"><%= rsM.getInt("numero") %></div>
                        <div class="table-status"><%= ocupada ? "Ocupada" : "Libre" %></div>
                    </a>
                <%
                    }
                    rsM.close(); psM.close(); conM.close();
                %>
                </div>
            </div>
        </section>

        <aside class="mesa-detail">
            <div class="mesa-placeholder">Selecciona una mesa</div>
        </aside>
    </div>

    <% } else {
        // Mesa seleccionada: pantalla de toma de pedido igual a "Pedido rapido"
        // (busqueda + mas vendidos + categorias + grid), pero acotada a esta
        // Mesa + Comensal. Los tabs de comensal permiten manejar varios
        // clientes en la misma mesa, cada uno con su propio carrito/cuenta.
        List<String> comensales = comensalesPorMesa.get(mesaSel);
        if (comensales == null) { comensales = new ArrayList<String>(); comensales.add("C1"); }
    %>
    <div class="orders-layout">
        <section class="products-panel">

            <div class="orders-toprow">
                <form method="get" action="orders.jsp" class="search-bar" style="flex:1">
                    <input type="hidden" name="vista" value="mesas">
                    <input type="hidden" name="mesaId" value="<%= mesaSel %>">
                    <input type="hidden" name="comensal" value="<%= comensalSel %>">
                    <span class="search-icon">🔍</span>
                    <input type="text" name="qMesa" value="<%= qMesa != null ? qMesa : "" %>" placeholder="Buscar producto...">
                </form>
                <span class="section-badge">Mesa <%= mesaSel %> · Comensal <%= comensalSel.replace("C","") %></span>
                <a href="orders.jsp?vista=mesas" class="goto-mesas-btn" style="text-decoration:none">← Volver al salon</a>
            </div>

            <%
                // ---- Mas vendidos ----
            %>
            <div class="bestsellers-row">
                <div class="row-label">Mas vendidos · <span style="font-weight:400;text-transform:none">tap para agregar</span></div>
                <div class="bestsellers-chips">
                <%
                    Connection conBM = getConnection();
                    PreparedStatement psBM = conBM.prepareStatement("SELECT id_producto, nombre, precio FROM productos WHERE mas_vendido=1 AND activo=1");
                    ResultSet rsBM = psBM.executeQuery();
                    while (rsBM.next()) {
                %>
                    <form method="post" action="orders.jsp" style="display:inline">
                        <input type="hidden" name="accion" value="addMesaProducto">
                        <input type="hidden" name="vista" value="mesas">
                        <input type="hidden" name="idMesa" value="<%= mesaSel %>">
                        <input type="hidden" name="comensal" value="<%= comensalSel %>">
                        <input type="hidden" name="idProducto" value="<%= rsBM.getInt("id_producto") %>">
                        <button type="submit" class="bestseller-chip" style="border:1px solid var(--card-border)">
                            <span><%= rsBM.getString("nombre") %></span>
                            <span class="chip-price">$<%= String.format("%.2f", rsBM.getDouble("precio")) %></span>
                        </button>
                    </form>
                <%
                    }
                    rsBM.close(); psBM.close(); conBM.close();
                %>
                </div>
            </div>

            <%
                // ---- Categorias (propia seccion primero) ----
            %>
            <div class="cat-tabs">
                <a class="cat-tab <%= (catMesa == null) ? "active" : "" %>" href="orders.jsp?vista=mesas&mesaId=<%= mesaSel %>&comensal=<%= comensalSel %>">Todas</a>
                <%
                    Connection conCM = getConnection();
                    PreparedStatement psCM = conCM.prepareStatement(
                        "SELECT id_categoria, nombre FROM categorias ORDER BY (id_seccion = ?) DESC, id_categoria");
                    psCM.setInt(1, idSeccion);
                    ResultSet rsCM = psCM.executeQuery();
                    while (rsCM.next()) {
                        String activeClsM = String.valueOf(rsCM.getInt("id_categoria")).equals(catMesa) ? "active" : "";
                %>
                    <a class="cat-tab <%= activeClsM %>" href="orders.jsp?vista=mesas&mesaId=<%= mesaSel %>&comensal=<%= comensalSel %>&catMesa=<%= rsCM.getInt("id_categoria") %>"><%= rsCM.getString("nombre") %></a>
                <%
                    }
                    rsCM.close(); psCM.close(); conCM.close();
                %>
            </div>

            <%
                // ---- Grid de productos segun filtro/busqueda ----
            %>
            <div class="product-grid">
                <%
                    StringBuilder sqlM = new StringBuilder(
                        "SELECT p.id_producto, p.nombre, p.precio, p.emoji, sc.nombre AS subcat " +
                        "FROM productos p " +
                        "JOIN subcategorias sc ON p.id_subcategoria = sc.id_subcategoria " +
                        "JOIN categorias c ON sc.id_categoria = c.id_categoria " +
                        "WHERE p.activo = 1 ");
                    if (catMesa != null && !catMesa.isEmpty()) sqlM.append("AND c.id_categoria = ? ");
                    if (qMesa != null && !qMesa.trim().isEmpty()) sqlM.append("AND p.nombre LIKE ? ");
                    sqlM.append("ORDER BY p.nombre");

                    Connection conPM = getConnection();
                    PreparedStatement psPM = conPM.prepareStatement(sqlM.toString());
                    int paramIdxM = 1;
                    if (catMesa != null && !catMesa.isEmpty()) psPM.setInt(paramIdxM++, Integer.parseInt(catMesa));
                    if (qMesa != null && !qMesa.trim().isEmpty()) psPM.setString(paramIdxM++, "%" + qMesa.trim() + "%");
                    ResultSet rsPM = psPM.executeQuery();
                    boolean hayProductosM = false;
                    while (rsPM.next()) {
                        hayProductosM = true;
                %>
                    <form method="post" action="orders.jsp" style="display:contents">
                        <input type="hidden" name="accion" value="addMesaProducto">
                        <input type="hidden" name="vista" value="mesas">
                        <input type="hidden" name="idMesa" value="<%= mesaSel %>">
                        <input type="hidden" name="comensal" value="<%= comensalSel %>">
                        <input type="hidden" name="idProducto" value="<%= rsPM.getInt("id_producto") %>">
                        <button type="submit" class="product-card" style="border:none;text-align:left;cursor:pointer">
                            <div class="product-img"><span style="font-size:32px"><%= rsPM.getString("emoji") %></span></div>
                            <div class="product-body">
                                <div class="product-name"><%= rsPM.getString("nombre") %></div>
                                <div class="product-price">$<%= String.format("%.2f", rsPM.getDouble("precio")) %></div>
                                <div class="product-cat-badge"><%= rsPM.getString("subcat") %></div>
                            </div>
                        </button>
                    </form>
                <%
                    }
                    if (!hayProductosM) { %>
                        <div style="grid-column:1/-1;color:var(--text-muted);padding:24px;text-align:center">Sin resultados</div>
                    <% }
                    rsPM.close(); psPM.close(); conPM.close();
                %>
            </div>
        </section>

        <%-- ============== TICKET DE LA MESA / COMENSAL ============== --%>
        <aside class="ticket-panel">
            <div class="ticket-header">
                <div>
                    <div class="ticket-title">Mesa <%= mesaSel %></div>
                    <div class="ticket-num">Cuenta por comensal</div>
                </div>
                <a href="orders.jsp?vista=mesas" class="btn-ghost" style="font-size:11px;text-decoration:none">✕</a>
            </div>

            <div style="padding:0 var(--sp-5)">
                <div class="comensal-tabs">
                    <% for (String c : comensales) { %>
                        <a href="orders.jsp?vista=mesas&mesaId=<%= mesaSel %>&comensal=<%= c %>"
                           class="comensal-tab <%= c.equals(comensalSel) ? "active" : "" %>" style="text-decoration:none"><%= c %></a>
                    <% } %>
                    <form method="post" action="orders.jsp" style="display:inline">
                        <input type="hidden" name="accion" value="addComensal">
                        <input type="hidden" name="vista" value="mesas">
                        <input type="hidden" name="idMesa" value="<%= mesaSel %>">
                        <button type="submit" class="comensal-tab add-btn">+</button>
                    </form>
                </div>
            </div>

            <div class="ticket-items">
            <%
                String claveSel = mesaSel + "_" + comensalSel;
                LinkedHashMap<Integer,Integer> carritoSel = carritosMesa.get(claveSel);
                double totalMesa = 0;
                if (carritoSel == null || carritoSel.isEmpty()) {
            %>
                <div class="ticket-empty"><span>Sin productos</span><span class="text-muted text-xs">Toca un producto para agregar</span></div>
            <%
                } else {
                    Connection conTM = getConnection();
                    for (Map.Entry<Integer,Integer> item : carritoSel.entrySet()) { 
                        PreparedStatement psTM = conTM.prepareStatement("SELECT nombre, precio FROM productos WHERE id_producto = ?");
                        psTM.setInt(1, item.getKey());
                        ResultSet rsTM = psTM.executeQuery();
                        if (rsTM.next()) {
                            double subM = rsTM.getDouble("precio") * item.getValue();
                            totalMesa += subM;
            %>
                <div class="ticket-item">
                    <div class="qty-control">
                        <form method="post" action="orders.jsp">
                            <input type="hidden" name="accion" value="qtyMesa">
                            <input type="hidden" name="vista" value="mesas">
                            <input type="hidden" name="idMesa" value="<%= mesaSel %>">
                            <input type="hidden" name="comensal" value="<%= comensalSel %>">
                            <input type="hidden" name="idProducto" value="<%= item.getKey() %>">
                            <input type="hidden" name="delta" value="-1">
                            <button type="submit" class="qty-btn">−</button>
                        </form>
                        <span class="qty-val"><%= item.getValue() %></span>
                        <form method="post" action="orders.jsp">
                            <input type="hidden" name="accion" value="qtyMesa">
                            <input type="hidden" name="vista" value="mesas">
                            <input type="hidden" name="idMesa" value="<%= mesaSel %>">
                            <input type="hidden" name="comensal" value="<%= comensalSel %>">
                            <input type="hidden" name="idProducto" value="<%= item.getKey() %>">
                            <input type="hidden" name="delta" value="1">
                            <button type="submit" class="qty-btn">+</button>
                        </form>
                    </div>
                    <div class="ticket-item-info">
                        <div class="ticket-item-name"><%= rsTM.getString("nombre") %></div>
                        <div class="ticket-item-price">$<%= String.format("%.2f", subM) %></div>
                    </div>
                </div>
            <%
                        }
                        rsTM.close(); psTM.close();
                    }
                    conTM.close();
                }
            %>
            </div>

            <form method="post" action="orders.jsp">
            <div class="ticket-total">
                <span>Subtotal <%= comensalSel %></span>
                <span class="total-amount">$<%= String.format("%.2f", totalMesa) %></span>
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
            <input type="hidden" name="idMesa" value="<%= mesaSel %>">
            <input type="hidden" name="comensal" value="<%= comensalSel %>">
            <div class="ticket-actions">
                <button type="submit" name="accion" value="enviarMesa" class="btn-kitchen">Enviar a cocina</button>
                <button type="submit" name="accion" value="cobrarMesa" class="btn-charge">Cobrar comensal</button>
            </div>
            </form>
        </aside>
    </div>
    <% } %>

    <% } %>

</main>

<%@ include file="../includes/footer.jsp" %>
</body>
</html>
