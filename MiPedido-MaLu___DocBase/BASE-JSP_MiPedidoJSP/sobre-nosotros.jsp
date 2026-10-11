<%-- ============================================================
     sobre-nosotros.jsp — Pagina publica "Sobre Nosotros"
     Muestra la foto, nombre, cedula, carrera y resumen de
     experiencia de cada integrante, leyendo estos datos
     DIRECTAMENTE de la tabla `usuarios` en MySQL.
     ============================================================ --%>
<%@ page contentType="text/html; charset=UTF-8" pageEncoding="UTF-8" %>
<%@ page import="java.sql.*" %>
<%@ include file="includes/db.jsp" %>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- Titulo unico de esta pagina -->
    <title>Sobre Nosotros · Restaurante MaLu</title>
    <link rel="stylesheet" href="css/main.css">
    <link rel="stylesheet" href="css/layout.css">
    <link rel="stylesheet" href="css/sobre-nosotros.css">
</head>
<body class="public-page-body">

    <%@ include file="includes/header.jsp" %>

    <main class="public-main">
        <section class="team-intro">
            <h1>Sobre Nosotros</h1>
            <p>Somos el equipo de desarrollo detras de <strong>MiPedido</strong>, el sistema de toma de pedidos
               creado para el Restaurante MaLu como Proyecto Semestral de Programacion de Software II.</p>
        </section>

        <section class="team-grid">
            <%
                // Consultamos todos los usuarios activos para mostrarlos como equipo del proyecto
                Connection con = null;
                PreparedStatement ps = null;
                ResultSet rs = null;
                try {
                    con = getConnection();
                    ps = con.prepareStatement(
                        "SELECT nombre, cedula, carrera, rol, resumen_dev, foto_url " +
                        "FROM usuarios WHERE activo = 1 ORDER BY " +
                        "FIELD(rol,'gerente','supervisor','cashier','comandas')");
                    rs = ps.executeQuery();
                    while (rs.next()) {
            %>
                        <article class="team-card">
                            <img src="<%= rs.getString("foto_url") != null ? rs.getString("foto_url") : "img/team/placeholder.jpg" %>"
                                 alt="Foto de <%= rs.getString("nombre") %>"
                                 class="team-photo" 
                              <br>     
                            <h3><%= rs.getString("nombre") %></h3> 
                            <div class="team-role"><%= rs.getString("rol").toUpperCase() %></div> 
                            <div class="team-meta">Cedula: <%= rs.getString("cedula") %></div> 
                            <div class="team-meta">Carrera: <%= rs.getString("carrera") %></div> 
                            <p class="team-resumen"><%= rs.getString("resumen_dev") %></p>
                        </article>
            <%
                    }
                } catch (Exception e) {
            %>
                    <div class="alert-box alert-error">
                        ⚠ No se pudo conectar a la base de datos: <%= e.getMessage() %>.
                        Verifica que XAMPP (MySQL) este encendido y que la base "mipedido_db" exista.
                    </div>
            <%
                } finally {
                    if (rs != null) rs.close();
                    if (ps != null) ps.close();
                    if (con != null) con.close();
                }
            %>
        </section>
    </main>

    <%@ include file="includes/footer.jsp" %>
</body>
</html>
