<%-- ============================================================
     ventas.jsp — Diario de ventas (CASHIER / SUPERVISOR / GERENTE)
     Lee las facturas reales generadas en orders.jsp
     ============================================================ --%>
<%@ page contentType="text/html; charset=UTF-8" pageEncoding="UTF-8" %>
<%@ page import="java.sql.*" %>
<%
    String[] rolesPermitidos = {"cashier","supervisor","gerente"};
%>
<%@ include file="../includes/auth-check.jsp" %>
<%@ include file="../includes/db.jsp" %>
<%
    String filtroMetodo = request.getParameter("metodo");
    String filtroOrigen = request.getParameter("origen");
    String verFacturaId  = request.getParameter("verFactura");

    // ---------------------------------------------------------
    // Consulta principal de facturas del dia (con filtros opcionales)
    // ---------------------------------------------------------
    StringBuilder sql = new StringBuilder(
        "SELECT f.id_factura, f.total, f.metodo_pago, f.fecha_hora, " +
        "       p.origen, p.id_mesa, p.comensal " +
        "FROM facturas f JOIN pedidos p ON f.id_pedido = p.id_pedido " +
        "WHERE f.anulada = 0 AND DATE(f.fecha_hora) = CURDATE() ");
    if (filtroMetodo != null && !filtroMetodo.isEmpty()) sql.append("AND f.metodo_pago = ? ");
    if (filtroOrigen != null && !filtroOrigen.isEmpty()) sql.append("AND p.origen = ? ");
    sql.append("ORDER BY f.fecha_hora DESC");

    Connection con = getConnection();
    PreparedStatement ps = con.prepareStatement(sql.toString());
    int idx = 1;
    if (filtroMetodo != null && !filtroMetodo.isEmpty()) ps.setString(idx++, filtroMetodo);
    if (filtroOrigen != null && !filtroOrigen.isEmpty()) ps.setString(idx++, filtroOrigen);
    ResultSet rs = ps.executeQuery();

    int cantFacturas = 0;
    double totalDia = 0, totalEfectivo = 0, totalYappy = 0, totalTarjeta = 0;
    // Guardamos las filas en memoria para poder recorrerlas dos veces (resumen + tabla)
    java.util.List<Object[]> filas = new java.util.ArrayList<Object[]>();
    while (rs.next()) {
        double total = rs.getDouble("total");
        String metodo = rs.getString("metodo_pago");
        cantFacturas++;
        totalDia += total;
        if ("efectivo".equals(metodo)) totalEfectivo += total;
        else if ("yappy".equals(metodo)) totalYappy += total;
        else if ("tarjeta".equals(metodo)) totalTarjeta += total;

        filas.add(new Object[]{
            rs.getInt("id_factura"), rs.getTimestamp("fecha_hora"), rs.getString("origen"),
            rs.getInt("id_mesa"), rs.getString("comensal"), metodo, total
        });
    }
    rs.close(); ps.close();
%>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Diario de Ventas · MiPedido</title>
    <link rel="stylesheet" href="../css/main.css">
    <link rel="stylesheet" href="../css/layout.css">
    <link rel="stylesheet" href="../css/ventas.css">
</head>
<body>

<%@ include file="../includes/header.jsp" %>

<main class="main-content" style="min-height:auto">

    <div class="ventas-toprow">
        <div class="ventas-title-col"><h1 class="page-title">Diario de ventas</h1></div>
        <form method="get" action="ventas.jsp" class="ventas-filters">
            <span class="turno-pill">Turno: hoy</span>
            <select class="filter-select" name="metodo" onchange="this.form.submit()">
                <option value="">Metodo ▾</option>
                <option value="efectivo" <%= "efectivo".equals(filtroMetodo)?"selected":"" %>>Efectivo</option>
                <option value="yappy"    <%= "yappy".equals(filtroMetodo)?"selected":"" %>>Yappy</option>
                <option value="tarjeta"  <%= "tarjeta".equals(filtroMetodo)?"selected":"" %>>Tarjeta</option>
            </select>
            <select class="filter-select" name="origen" onchange="this.form.submit()">
                <option value="">Origen ▾</option>
                <option value="mesa"   <%= "mesa".equals(filtroOrigen)?"selected":"" %>>Mesa</option>
                <option value="rapido" <%= "rapido".equals(filtroOrigen)?"selected":"" %>>Rapido</option>
            </select>
        </form>
    </div>

    <div class="summary-row">
        <div class="summary-card">
            <div class="summary-label">Facturas turno</div>
            <div class="summary-val"><%= cantFacturas %></div>
        </div>
        <div class="summary-card">
            <div class="summary-label">Total turno</div>
            <div class="summary-val pink">$<%= String.format("%.2f", totalDia) %></div>
        </div>
        <div class="summary-card">
            <div class="summary-label">Efectivo · Yappy · Tarjeta</div>
            <div class="breakdown-row">
                <div class="breakdown-item"><span class="method">Efectivo</span><span class="amount">$<%= String.format("%.2f", totalEfectivo) %></span></div>
                <div class="breakdown-item"><span class="method">Yappy</span><span class="amount">$<%= String.format("%.2f", totalYappy) %></span></div>
                <div class="breakdown-item"><span class="method">Tarjeta</span><span class="amount">$<%= String.format("%.2f", totalTarjeta) %></span></div>
            </div>
        </div>
    </div>

    <div class="ventas-table-card">
        <table class="data-table">
            <thead>
                <tr><th>Fact.</th><th>Hora</th><th>Origen</th><th>Metodo Pago</th><th>Total</th><th>Acciones</th></tr>
            </thead>
            <tbody>
            <% if (filas.isEmpty()) { %>
                <tr><td colspan="6" style="text-align:center;color:var(--text-muted);padding:32px">Sin facturas</td></tr>
            <% } else {
                for (Object[] f : filas) {
                    int idFactura = (Integer) f[0];
                    java.sql.Timestamp fecha = (java.sql.Timestamp) f[1];
                    String origen = (String) f[2];
                    int idMesa = (Integer) f[3];
                    String comensal = (String) f[4];
                    String metodo = (String) f[5];
                    double total = (Double) f[6];
                    String origenLbl = "mesa".equals(origen) ? ("Mesa " + idMesa + (comensal!=null?" · "+comensal:"")) : "Rapido";
                    String pillClass = "efectivo".equals(metodo) ? "pill-efectivo" : ("tarjeta".equals(metodo) ? "pill-tarjeta" : "pill-yappy-rest");
            %>
                <tr>
                    <td class="fact-num">#<%= idFactura %></td>
                    <td class="fact-hora"><%= new java.text.SimpleDateFormat("HH:mm").format(fecha) %></td>
                    <td><%= origenLbl %></td>
                    <td><span class="<%= pillClass %>"><%= metodo.substring(0,1).toUpperCase() + metodo.substring(1) %></span></td>
                    <td class="fact-total">$<%= String.format("%.2f", total) %></td>
                    <td>
                        <div class="action-links">
                            <a class="action-link" href="ventas.jsp?verFactura=<%= idFactura %>&metodo=<%= filtroMetodo==null?"":filtroMetodo %>&origen=<%= filtroOrigen==null?"":filtroOrigen %>">Ver</a>
                        </div>
                    </td>
                </tr>
            <%
                }
            } %>
            </tbody>
        </table>
    </div>

    <%-- ================= DETALLE DE FACTURA ================= --%>
    <% if (verFacturaId != null) {
        Connection con2 = getConnection();
        PreparedStatement ps2 = con2.prepareStatement(
            "SELECT f.id_factura, f.total, f.metodo_pago, f.fecha_hora, p.id_pedido, p.origen, p.id_mesa, p.comensal " +
            "FROM facturas f JOIN pedidos p ON f.id_pedido = p.id_pedido WHERE f.id_factura = ?");
        ps2.setInt(1, Integer.parseInt(verFacturaId));
        ResultSet rs2 = ps2.executeQuery();
        if (rs2.next()) {
            int idPedidoF = rs2.getInt("id_pedido");
    %>
    <div class="modal-backdrop open">
        <div class="modal" style="max-width:520px">
            <div class="modal-header">
                <div class="modal-title">Factura #<%= rs2.getInt("id_factura") %></div>
                <a href="ventas.jsp" class="modal-close">✕</a>
            </div>
            <div class="factura-detail">
                <div class="factura-meta-row">
                    <div class="factura-meta-item"><span class="factura-meta-label">Hora</span><span class="factura-meta-val"><%= new java.text.SimpleDateFormat("HH:mm").format(rs2.getTimestamp("fecha_hora")) %></span></div>
                    <div class="factura-meta-item"><span class="factura-meta-label">Origen</span><span class="factura-meta-val"><%= "mesa".equals(rs2.getString("origen")) ? "Mesa "+rs2.getInt("id_mesa") : "Rapido" %></span></div>
                    <div class="factura-meta-item"><span class="factura-meta-label">Metodo</span><span class="factura-meta-val"><%= rs2.getString("metodo_pago") %></span></div>
                </div>
                <div class="factura-items">
                <%
                    PreparedStatement ps3 = con2.prepareStatement(
                        "SELECT pr.nombre, d.cantidad, pr.precio FROM pedido_detalle d " +
                        "JOIN productos pr ON d.id_producto = pr.id_producto WHERE d.id_pedido = ?");
                    ps3.setInt(1, idPedidoF);
                    ResultSet rs3 = ps3.executeQuery();
                    while (rs3.next()) {
                %>
                    <div class="factura-item-row">
                        <span><%= rs3.getInt("cantidad") %>× <%= rs3.getString("nombre") %></span>
                        <span>$<%= String.format("%.2f", rs3.getInt("cantidad") * rs3.getDouble("precio")) %></span>
                    </div>
                <%
                    }
                    rs3.close(); ps3.close();
                %>
                </div>
                <div class="factura-total-row"><span>Total</span><span class="total-amount">$<%= String.format("%.2f", rs2.getDouble("total")) %></span></div>
            </div>
            <a href="ventas.jsp" class="btn-secondary btn-full" style="text-decoration:none;text-align:center;display:block">Cerrar</a>
        </div>
    </div>
    <%
        }
        rs2.close(); ps2.close(); con2.close();
    } %>

</main>

<%@ include file="../includes/footer.jsp" %>
</body>
</html>
