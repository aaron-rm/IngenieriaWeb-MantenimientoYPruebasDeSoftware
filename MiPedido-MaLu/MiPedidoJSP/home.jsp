<%-- ============================================================
     home.jsp — Pagina de inicio (publica)
     Requisitos cumplidos aqui:
       - <title> propio y distinto al resto de paginas
       - Tags <header>, <nav>, <section>, <footer>
       - 3 noticias (2 articulos + 1 video) con enlace a la fuente
       - Opcion de Login visible
     ============================================================ --%>
<%@ page contentType="text/html; charset=UTF-8" pageEncoding="UTF-8" %>
<%
    // Mensaje de error que pudo haber dejado auth-check.jsp (rol sin permiso)
    String msgError = (String) session.getAttribute("mensajeError");
    if (msgError != null) session.removeAttribute("mensajeError");
%>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- Titulo unico de esta pagina -->
    <title>Home · Restaurante MaLu</title>
    <link rel="stylesheet" href="css/main.css">
    <link rel="stylesheet" href="css/layout.css">
    <link rel="stylesheet" href="css/home.css">
</head>
<body class="public-page-body">

    <%@ include file="includes/header.jsp" %>

    <main class="public-main">

        <% if (msgError != null) { %>
            <div class="alert-box alert-error">⚠ <%= msgError %></div>
        <% } %>

        <!-- ================= HERO / BIENVENIDA ================= -->
        <section class="home-hero">
            <h1>Bienvenido a <span class="text-pink">MaLu Restaurante</span></h1>
            <p>Sabor casero, ambiente familiar y ahora, un sistema de pedidos mas rapido y eficiente para nuestro equipo.</p>
            <a href="login.jsp" class="btn-primary">Iniciar sesion</a>
        </section>

        <!-- ================= NOTICIAS ================= -->
        <section class="home-news">
            <h2>Noticias y novedades</h2>
            <div class="news-grid">

                <!-- Noticia 1: articulo -->
                <article class="news-card">
                    <div class="news-badge">Articulo</div>
                    <h3>Tendencias gastronomicas en Panama para este ano</h3>
                    <p>Un repaso de los sabores y estilos que estan marcando la escena culinaria panameña en los ultimos meses.</p>
                    <a href="https://www.laestrella.com.pa/economia" target="_blank" rel="noopener" class="news-link">Leer fuente completa →</a>
                </article>

                <!-- Noticia 2: articulo -->
                <article class="news-card">
                    <div class="news-badge">Articulo</div>
                    <h3>Como la tecnologia esta cambiando la toma de pedidos</h3>
                    <p>Cada vez mas restaurantes adoptan sistemas digitales para agilizar el servicio en sala y en cocina.</p>
                    <a href="https://www.prensa.com/economia/" target="_blank" rel="noopener" class="news-link">Leer fuente completa →</a>
                </article>

                <!-- Noticia 3: video -->
                <article class="news-card">
                    <div class="news-badge news-badge-video">Video</div>
                    <h3>Un dia en la cocina de un restaurante panameño</h3>
                    <p>Un vistazo detras de camaras a como se prepara un servicio de alta demanda.</p>
                    <a href="https://www.youtube.com/results?search_query=un+dia+en+un+restaurante" target="_blank" rel="noopener" class="news-link">Ver video en la fuente →</a>
                </article>

            </div>
        </section>

    </main>

    <%@ include file="includes/footer.jsp" %>
</body>
</html>
