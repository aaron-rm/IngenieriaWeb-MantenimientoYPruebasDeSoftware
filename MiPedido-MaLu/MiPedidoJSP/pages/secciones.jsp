<%-- ============================================================
     secciones.jsp — Administracion de Secciones (SUPERVISOR / GERENTE)
     Permite crear secciones, agregar categorias y asignar usuarios
     ============================================================ --%>
<%@ page contentType="text/html; charset=UTF-8" pageEncoding="UTF-8" %>
<%@ page import="java.sql.*" %>
<%
    String[] rolesPermitidos = {"supervisor","gerente"};
%>
<%@ include file="../includes/auth-check.jsp" %>
<%@ include file="../includes/db.jsp" %>
<%
    String accion = request.getParameter("accion");
    String toast = null;

    if (accion != null && "POST".equalsIgnoreCase(request.getMethod())) {
        Connection con = getConnection();
        try {
            if (accion.equals("nuevaSeccion")) {
                String nombre = request.getParameter("nombre");
                PreparedStatement ps = con.prepareStatement("INSERT INTO secciones (nombre, estado) VALUES (?, 'activa')");
                ps.setString(1, nombre);
                ps.executeUpdate();
                ps.close();
                toast = "Seccion \"" + nombre + "\" creada.";

            } else if (accion.equals("nuevaCategoria")) {
                int idSeccion = Integer.parseInt(request.getParameter("idSeccion"));
                String nombre = request.getParameter("nombre");
                PreparedStatement ps = con.prepareStatement("INSERT INTO categorias (nombre, id_seccion) VALUES (?, ?)");
                ps.setString(1, nombre);
                ps.setInt(2, idSeccion);
                ps.executeUpdate();
                ps.close();
                toast = "Categoria \"" + nombre + "\" agregada.";

            } else if (accion.equals("eliminarCategoria")) {
                int idCategoria = Integer.parseInt(request.getParameter("idCategoria"));
                PreparedStatement ps = con.prepareStatement("DELETE FROM categorias WHERE id_categoria = ?");
                ps.setInt(1, idCategoria);
                ps.executeUpdate();
                ps.close();
                toast = "Categoria eliminada.";

            } else if (accion.equals("asignarUsuario")) {
                int idUsuario = Integer.parseInt(request.getParameter("idUsuario"));
                int idSeccion = Integer.parseInt(request.getParameter("idSeccion"));
                PreparedStatement ps = con.prepareStatement("UPDATE usuarios SET id_seccion = ? WHERE id_usuario = ?");
                ps.setInt(1, idSeccion);
                ps.setInt(2, idUsuario);
                ps.executeUpdate();
                ps.close();
                toast = "Usuario asignado a la seccion.";

            } else if (accion.equals("quitarUsuario")) {
                int idUsuario = Integer.parseInt(request.getParameter("idUsuario"));
                PreparedStatement ps = con.prepareStatement("UPDATE usuarios SET id_seccion = NULL WHERE id_usuario = ?");
                ps.setInt(1, idUsuario);
                ps.executeUpdate();
                ps.close();
                toast = "Usuario removido de la seccion.";
            }
        } catch (Exception ex) {
            toast = "Error: " + ex.getMessage();
        } finally {
            con.close();
        }
        session.setAttribute("toastSecciones", toast);
        response.sendRedirect(request.getContextPath() + "/pages/secciones.jsp");
        return;
    }

    if (toast == null) { toast = (String) session.getAttribute("toastSecciones"); session.removeAttribute("toastSecciones"); }
%>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Secciones · MiPedido</title>
    <link rel="stylesheet" href="../css/main.css">
    <link rel="stylesheet" href="../css/layout.css">
    <link rel="stylesheet" href="../css/secciones.css">
</head>
<body>

<%@ include file="../includes/header.jsp" %>

<main class="main-content" style="min-height:auto">

    <% if (toast != null) { %>
        <div class="alert-box alert-success"><%= toast %></div>
    <% } %>

    <div class="secciones-toprow">
        <h1 class="page-title">Secciones</h1>
        <form method="post" action="secciones.jsp" style="display:flex;gap:8px">
            <input type="hidden" name="accion" value="nuevaSeccion">
            <input type="text" name="nombre" placeholder="Nombre de nueva seccion" required
                   style="padding:8px 12px;border-radius:6px;border:1px solid var(--input-border)">
            <button type="submit" class="btn-primary">+ Nueva seccion</button>
        </form>
    </div>

    <div class="secciones-grid">
    <%
        Connection con = getConnection();
        PreparedStatement psSec = con.prepareStatement("SELECT * FROM secciones ORDER BY id_seccion");
        ResultSet rsSec = psSec.executeQuery();
        while (rsSec.next()) {
            int idSeccion = rsSec.getInt("id_seccion");
            String claseHeader = idSeccion == 1 ? "restaurante" : (idSeccion == 2 ? "bar" : "default");
    %>
        <div class="seccion-card">
            <div class="seccion-card-header <%= claseHeader %>">
                <span class="seccion-name"><%= rsSec.getString("nombre") %></span>
                <span class="seccion-estado"><%= rsSec.getString("estado") %></span>
            </div>
            <div class="seccion-body">

                <div>
                    <div class="sec-sub-label">Categorias</div>
                    <div class="cat-chips">
                    <%
                        PreparedStatement psCat = con.prepareStatement("SELECT * FROM categorias WHERE id_seccion = ?");
                        psCat.setInt(1, idSeccion);
                        ResultSet rsCat = psCat.executeQuery();
                        while (rsCat.next()) {
                    %>
                        <div class="cat-chip">
                            <%= rsCat.getString("nombre") %>
                            <form method="post" action="secciones.jsp" style="display:inline">
                                <input type="hidden" name="accion" value="eliminarCategoria">
                                <input type="hidden" name="idCategoria" value="<%= rsCat.getInt("id_categoria") %>">
                                <button type="submit" class="btn-chip-remove">×</button>
                            </form>
                        </div>
                    <%
                        }
                        rsCat.close(); psCat.close();
                    %>
                    </div>
                    <form method="post" action="secciones.jsp" style="display:flex;gap:6px;margin-top:10px">
                        <input type="hidden" name="accion" value="nuevaCategoria">
                        <input type="hidden" name="idSeccion" value="<%= idSeccion %>">
                        <input type="text" name="nombre" placeholder="Nueva categoria" required
                               style="font-size:12px;padding:6px 10px;border-radius:20px;border:1px solid var(--card-border)">
                        <button type="submit" class="btn-add-cat">+ Agregar</button>
                    </form>
                </div>

                <hr class="sec-divider">

                <div>
                    <div class="sec-sub-label">Usuarios asignados</div>
                    <div class="user-list">
                    <%
                        PreparedStatement psUsr = con.prepareStatement(
                            "SELECT id_usuario, nombre, rol FROM usuarios WHERE id_seccion = ? AND activo = 1");
                        psUsr.setInt(1, idSeccion);
                        ResultSet rsUsr = psUsr.executeQuery();
                        while (rsUsr.next()) {
                    %>
                        <div class="user-item">
                            <div class="user-avatar color-1"><%= rsUsr.getString("nombre").substring(0,1).toUpperCase() %></div>
                            <div class="user-info">
                                <div class="user-name"><%= rsUsr.getString("nombre") %></div>
                                <div class="user-role"><%= rsUsr.getString("rol") %></div>
                            </div>
                            <form method="post" action="secciones.jsp">
                                <input type="hidden" name="accion" value="quitarUsuario">
                                <input type="hidden" name="idUsuario" value="<%= rsUsr.getInt("id_usuario") %>">
                                <button type="submit" class="btn-user-remove">✕</button>
                            </form>
                        </div>
                    <%
                        }
                        rsUsr.close(); psUsr.close();
                    %>
                    </div>

                    <!-- Asignar un usuario libre (sin seccion) a esta seccion -->
                    <form method="post" action="secciones.jsp" style="display:flex;gap:6px;margin-top:10px">
                        <input type="hidden" name="accion" value="asignarUsuario">
                        <input type="hidden" name="idSeccion" value="<%= idSeccion %>">
                        <select name="idUsuario" required style="font-size:12px;padding:6px;border-radius:6px;border:1px solid var(--card-border);flex:1">
                            <option value="">-- Elegir usuario --</option>
                            <%
                                PreparedStatement psLibres = con.prepareStatement(
                                    "SELECT id_usuario, nombre FROM usuarios WHERE (id_seccion IS NULL OR id_seccion != ?) AND rol='cashier' AND activo=1");
                                psLibres.setInt(1, idSeccion);
                                ResultSet rsLibres = psLibres.executeQuery();
                                while (rsLibres.next()) {
                            %>
                                <option value="<%= rsLibres.getInt("id_usuario") %>"><%= rsLibres.getString("nombre") %></option>
                            <%
                                }
                                rsLibres.close(); psLibres.close();
                            %>
                        </select>
                        <button type="submit" class="btn-asignar">+ Asignar</button>
                    </form>
                </div>

            </div>
        </div>
    <%
        }
        rsSec.close(); psSec.close(); con.close();
    %>
    </div>

</main>

<%@ include file="../includes/footer.jsp" %>
</body>
</html>
