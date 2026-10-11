<%-- ============================================================
     comandas.jsp — Pantalla de cocina (rol COMANDAS, y visible
     tambien para cashier/supervisor/gerente segun la guia)
     ============================================================ --%>
<%@ page contentType="text/html; charset=UTF-8" pageEncoding="UTF-8" %>
<%@ page import="java.sql.*" %>
<%@ page import="java.util.*" %>
<%
    String[] rolesPermitidos = {"comandas","cashier","supervisor","gerente"};
%>
<%@ include file="../includes/auth-check.jsp" %>
<%@ include file="../includes/db.jsp" %>
<%
    // ---------------------------------------------------------
    // Procesar acciones: tachar plato / cambiar estado de comanda
    // ---------------------------------------------------------
    String accion = request.getParameter("accion");
    if (accion != null && "POST".equalsIgnoreCase(request.getMethod())) {
        Connection con = getConnection();
        try {
            if (accion.equals("togglePreparado")) {
                int idDetalle = Integer.parseInt(request.getParameter("idDetalle"));
                PreparedStatement ps = con.prepareStatement(
                    "UPDATE pedido_detalle SET preparado = NOT preparado WHERE id_detalle = ?");
                ps.setInt(1, idDetalle);
                ps.executeUpdate();
                ps.close();

            } else if (accion.equals("setEstado")) {
                int idPedido = Integer.parseInt(request.getParameter("idPedido"));
                String nuevoEstado = request.getParameter("estado");
                PreparedStatement ps = con.prepareStatement("UPDATE pedidos SET estado = ? WHERE id_pedido = ?");
                ps.setString(1, nuevoEstado);
                ps.setInt(2, idPedido);
                ps.executeUpdate();
                ps.close();

                // Si se marca "listo", tachamos automaticamente todos los platos de esa comanda
                if ("listo".equals(nuevoEstado)) {
                    PreparedStatement ps2 = con.prepareStatement(
                        "UPDATE pedido_detalle SET preparado = 1 WHERE id_pedido = ?");
                    ps2.setInt(1, idPedido);
                    ps2.executeUpdate();
                    ps2.close();
                }
            }
        } catch (Exception ex) {
            session.setAttribute("toastComandas", "Error: " + ex.getMessage());
        } finally {
            con.close();
        }
        response.sendRedirect(request.getContextPath() + "/pages/comandas.jsp");
        return;
    }

    String toast = (String) session.getAttribute("toastComandas");
    session.removeAttribute("toastComandas");
%>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Comandas · MiPedido</title>
    <!-- Auto-refresh cada 6s: asi la cocina "recibe" pedidos nuevos sin necesidad de JavaScript/websockets -->
    <meta http-equiv="refresh" content="6">
    <link rel="stylesheet" href="../css/main.css">
    <link rel="stylesheet" href="../css/layout.css">
    <link rel="stylesheet" href="../css/comandas.css">
    <style> body[data-page="comandas"] { background:#2a2a2a; } </style>
</head>
<body data-page="comandas">

<%@ include file="../includes/header.jsp" %>

<main class="main-content" style="min-height:auto;background:#2a2a2a">

    <% if (toast != null) { %>
        <div class="alert-box alert-error"><%= toast %></div>
    <% } %>

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

    <%
        Connection con = getConnection();
        PreparedStatement psPed = con.prepareStatement(
            "SELECT p.id_pedido, p.numero_pedido, p.origen, p.id_mesa, p.comensal, p.estado, p.nota, p.fecha_hora " +
            "FROM pedidos p WHERE p.estado IN ('pendiente','en_preparacion') ORDER BY p.fecha_hora ASC");
        ResultSet rsPed = psPed.executeQuery();

        java.util.List<Integer> idsPendientes = new ArrayList<Integer>();
    %>
    <div class="comanda-grid">
    <%
        int totalFila = 0;
        while (rsPed.next()) {
            totalFila++;
            int idPedido = rsPed.getInt("id_pedido");
            String origenTxt = "mesa".equals(rsPed.getString("origen"))
                ? ("Mesa " + rsPed.getInt("id_mesa") + " · " + rsPed.getString("comensal"))
                : "Rapido";
            String estadoPedido = rsPed.getString("estado");

            PreparedStatement psDet = con.prepareStatement(
                "SELECT d.id_detalle, d.cantidad, d.preparado, pr.nombre " +
                "FROM pedido_detalle d JOIN productos pr ON d.id_producto = pr.id_producto " +
                "WHERE d.id_pedido = ?");
            psDet.setInt(1, idPedido);
            ResultSet rsDet = psDet.executeQuery();

            boolean todoListo = true;
            StringBuilder itemsHtml = new StringBuilder();
            while (rsDet.next()) {
                boolean preparado = rsDet.getBoolean("preparado");
                if (!preparado) todoListo = false;
    %>
        <!-- item individual: el checkbox es un mini-formulario POST -->
    <%
                itemsHtml.append("<form method='post' action='comandas.jsp' style='display:flex;align-items:center;gap:8px;padding:6px 8px' >")
                         .append("<input type='hidden' name='accion' value='togglePreparado'>")
                         .append("<input type='hidden' name='idDetalle' value='").append(rsDet.getInt("id_detalle")).append("'>")
                         .append("<button type='submit' class='item-check' style='cursor:pointer'>").append(preparado ? "✓" : "").append("</button>")
                         .append("<span style='font-size:13px;").append(preparado ? "text-decoration:line-through;color:#999" : "color:#1a1a1a").append("'>")
                         .append(rsDet.getInt("cantidad")).append("× ").append(rsDet.getString("nombre")).append("</span>")
                         .append("</form>");
            }
            rsDet.close(); psDet.close();
    %>
        <div class="comanda-card <%= todoListo ? "all-done" : "" %>">
            <div class="card-accent"></div>
            <div class="comanda-card-header">
                <div>
                    <div class="comanda-num">#<%= idPedido %></div>
                    <div class="comanda-origin"><%= origenTxt %></div>
                </div>
                <div class="comanda-time"><%= new java.text.SimpleDateFormat("HH:mm").format(rsPed.getTimestamp("fecha_hora")) %></div>
            </div>
            <div class="comanda-items">
                <%= itemsHtml.toString() %>
                <% if (rsPed.getString("nota") != null) { %>
                    <div class="item-note" style="padding:0 8px">📝 <%= rsPed.getString("nota") %></div>
                <% } %>
            </div>
            <div class="comanda-footer">
                <% if (todoListo) { %>
                    <form method="post" action="comandas.jsp" style="width:100%">
                        <input type="hidden" name="accion" value="setEstado">
                        <input type="hidden" name="idPedido" value="<%= idPedido %>">
                        <input type="hidden" name="estado" value="listo">
                        <button type="submit" class="btn-listo-full">✓ Listo — sale de la fila</button>
                    </form>
                <% } else { %>
                    <form method="post" action="comandas.jsp" style="flex:1">
                        <input type="hidden" name="accion" value="setEstado">
                        <input type="hidden" name="idPedido" value="<%= idPedido %>">
                        <input type="hidden" name="estado" value="en_preparacion">
                        <button type="submit" class="estado-btn proceso <%= "en_preparacion".equals(estadoPedido) ? "" : "inactive" %>">En proceso</button>
                    </form>
                    <form method="post" action="comandas.jsp" style="flex:1">
                        <input type="hidden" name="accion" value="setEstado">
                        <input type="hidden" name="idPedido" value="<%= idPedido %>">
                        <input type="hidden" name="estado" value="listo">
                        <button type="submit" class="estado-btn listo">Listo</button>
                    </form>
                <% } %>
            </div>
        </div>
    <%
        }
        rsPed.close(); psPed.close();

        if (totalFila == 0) {
    %>
        <div class="empty-queue">
            <span class="empty-icon">✅</span>
            <span>Fila vacia · Queue clear</span>
        </div>
    <%
        }
        con.close();
    %>
    </div>

</main>

<%@ include file="../includes/footer.jsp" %>
</body>
</html>
