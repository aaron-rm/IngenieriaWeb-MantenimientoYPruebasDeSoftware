<%-- ============================================================
     login.jsp — Formulario de inicio de sesion
     Valida usuario y contrasena contra la tabla `usuarios` de MySQL.
     Al validar, guarda rol/nombre/seccion en la SESION y redirige
     segun el rol:
        gerente / supervisor / cashier -> pages/orders.jsp
        comandas                       -> pages/comandas.jsp
     ============================================================ --%>
<%@ page contentType="text/html; charset=UTF-8" pageEncoding="UTF-8" %>
<%@ page import="java.sql.*" %>
<%@ include file="includes/db.jsp" %>
<%
    String error = null;

    // Si el formulario fue enviado (POST), procesamos el login aqui mismo
    if ("POST".equalsIgnoreCase(request.getMethod())) {
        String email = request.getParameter("usuario");
        String pass  = request.getParameter("password");

        if (email == null || email.trim().isEmpty() || pass == null || pass.trim().isEmpty()) {
            error = "Debes ingresar usuario y contrasena.";
        } else {
            Connection con = null;
            PreparedStatement ps = null;
            ResultSet rs = null;
            try {
                con = getConnection();
                // Consulta parametrizada (evita inyeccion SQL)
                ps = con.prepareStatement(
                    "SELECT u.id_usuario, u.nombre, u.rol, u.id_seccion, s.nombre AS seccion_nombre " +
                    "FROM usuarios u LEFT JOIN secciones s ON u.id_seccion = s.id_seccion " +
                    "WHERE u.email = ? AND u.password = ? AND u.activo = 1");
                ps.setString(1, email.trim());
                ps.setString(2, pass);
                rs = ps.executeQuery();

                if (rs.next()) {
                    // Credenciales correctas -> creamos la sesion
                    session.setAttribute("idUsuario", rs.getInt("id_usuario"));
                    session.setAttribute("nombre", rs.getString("nombre"));
                    session.setAttribute("rol", rs.getString("rol"));
                    // IMPORTANTE: usamos getObject (no getInt) porque id_seccion puede
                    // ser NULL en la BD (gerente/supervisor/comandas no tienen seccion).
                    // rs.getInt() en una columna NULL devuelve 0 en vez de null, y ese 0
                    // no existe en la tabla `secciones` -> provocaba el error de foreign key
                    // "fk_pedido_seccion" al enviar pedidos a cocina o cobrar.
                    Integer idSeccionDb = (Integer) rs.getObject("id_seccion");
                    session.setAttribute("idSeccion", idSeccionDb); // null si no tiene seccion asignada
                    session.setAttribute("seccionNombre", rs.getString("seccion_nombre") != null ? rs.getString("seccion_nombre") : "");

                    String rolLogueado = rs.getString("rol");
                    if ("comandas".equals(rolLogueado)) {
                        response.sendRedirect("pages/comandas.jsp");
                    } else {
                        response.sendRedirect("pages/orders.jsp");
                    }
                    return;
                } else {
                    error = "Usuario o contrasena incorrectos.";
                }
            } catch (Exception e) {
                error = "Error de conexion con la base de datos: " + e.getMessage();
            } finally {
                if (rs != null) rs.close();
                if (ps != null) ps.close();
                if (con != null) con.close();
            }
        }
    }
%>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- Titulo unico de esta pagina -->
    <title>Iniciar Sesion · MiPedido</title>
    <link rel="stylesheet" href="css/main.css">
    <link rel="stylesheet" href="css/layout.css">
    <link rel="stylesheet" href="css/login.css">
</head>
<body class="login-body">



  <div class="login-wrapper" style="grid-template-areas:'brand brand' 'card card'; grid-template-columns:1fr;">

    <div class="login-brand">
      <span class="login-logo"><em>MiPedido</em><span class="dot">.</span></span>
      <p class="login-sub">Restaurante MaLu</p>
    </div>

    <div class="login-card" style="max-width:420px;margin:0 auto;">
      <div class="card-logo">
        <div class="card-logo-text"><em>MiPedido<span class="dot">.</span></em></div>
        <div class="card-logo-sub">Restaurante MaLu</div>
      </div>

      <% if (error != null) { %>
        <div class="alert-box alert-error">⚠ <%= error %></div>
      <% } %>

      <!-- Formulario con letra/color distinto al body (ver login.css .field-group) -->
      <form method="post" action="login.jsp">
        <div class="field-group">
          <label for="usuario">Usuario · <span class="label-en">User</span></label>
          <input type="email" id="usuario" name="usuario" placeholder="usuario@malu.com" required>
        </div>
        <div class="field-group">
          <label for="password">Contrasena · <span class="label-en">Password</span></label>
          <input type="password" id="password" name="password" placeholder="********" required>
        </div>
        <button type="submit" class="btn-signin">Entrar · Sign in</button>
      </form>

      <p class="text-xs text-muted" style="margin-top:14px;text-align:center">
        ¿Aun no tienes cuenta? Contacta al Gerente General o al Supervisor.
      </p>
    </div>

  </div>
</body>
</html>
