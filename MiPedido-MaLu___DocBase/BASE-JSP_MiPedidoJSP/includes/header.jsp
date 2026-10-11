<%--
  ============================================================
  header.jsp — Banner + menu de navegacion superior
  ------------------------------------------------------------
  Incluye: logo de la organizacion, icono de busqueda (enlaza a
  Google.com), iconos de redes sociales, y el menu de navegacion
  que cambia segun el ROL guardado en la sesion.

  Se debe incluir justo despues de abrir <body>:
      <%@ include file="../includes/header.jsp" %>
  ============================================================
--%>
<%
    // Datos de la sesion actual (pueden venir nulos si es una pagina publica como Home)
    String rolActual   = (String) session.getAttribute("rol");
    String nombreUser  = (String) session.getAttribute("nombre");
    String seccionUser = (session.getAttribute("seccionNombre") != null) ? (String) session.getAttribute("seccionNombre") : "";
    boolean logueado   = (rolActual != null);
    String ctx = request.getContextPath();
%>
<!-- ===================== BANNER SUPERIOR ===================== -->
<header class="site-banner">
    <a href="<%= ctx %>/home.jsp" class="brand-logo-link">
        <img src="<%= ctx %>/img/logo-malu.png" alt="Logo Restaurante MaLu" class="brand-logo-img">
        <span class="brand-logo-text">Mi<span class="dot-pink">Pedido</span></span>
    </a>

    <div class="banner-right">
        <!-- Icono de busqueda: enlaza a Google -->
        <a href="https://www.google.com" target="_blank" rel="noopener" class="banner-icon" title="Buscar en Google"><p>Google</p></a>
        <!-- Iconos de redes sociales  -->
        <a href="https://www.facebook.com" target="_blank" rel="noopener" class="banner-icon" title="Facebook">Facebook</a>
        <a href="https://www.instagram.com" target="_blank" rel="noopener" class="banner-icon" title="Instagram">Instagram</a>

        <% if (logueado) { %>
            <span class="banner-user">
                <strong><%= nombreUser %></strong> · <%= rolActual.toUpperCase() %>
                <% if (!seccionUser.isEmpty()) { %> · <%= seccionUser %><% } %>
            </span>
            <a href="<%= ctx %>/logout.jsp" class="btn-logout-top">Salir</a>
        <% } else { %>
            <a href="<%= ctx %>/login.jsp" class="btn-logout-top">Iniciar sesion</a>
        <% } %>
    </div>
</header>

<!-- ===================== MENU DE NAVEGACION ===================== -->
<nav class="main-nav">
    <% if (!logueado) { %>
        <!-- Menu publico: Home / Sobre Nosotros -->
        <a href="<%= ctx %>/home.jsp" class="main-nav-item">Home</a>
        <a href="<%= ctx %>/sobre-nosotros.jsp" class="main-nav-item">Sobre Nosotros</a>
        <a href="<%= ctx %>/login.jsp" class="main-nav-item">Iniciar sesion</a>
    <% } else { %>
        <a href="<%= ctx %>/home.jsp" class="main-nav-item">Home</a>
        <a href="<%= ctx %>/sobre-nosotros.jsp" class="main-nav-item">Sobre Nosotros</a>
        <a href="<%= ctx %>/pages/orders.jsp" class="main-nav-item">Ordenes</a>
        <a href="<%= ctx %>/pages/comandas.jsp" class="main-nav-item">Comandas</a>

        <% if (!"comandas".equals(rolActual)) { %>
            <a href="<%= ctx %>/pages/ventas.jsp" class="main-nav-item">Diario de Ventas</a>
        <% } %>

        <% if ("supervisor".equals(rolActual) || "gerente".equals(rolActual)) { %>
            <a href="<%= ctx %>/pages/secciones.jsp" class="main-nav-item">Secciones</a>
        <% } %>

        <% if ("gerente".equals(rolActual)) { %>
            <span class="main-nav-item beta" title="Disponible en una version mas avanzada">Reportes β</span>
        <% } %>
    <% } %>
</nav>
