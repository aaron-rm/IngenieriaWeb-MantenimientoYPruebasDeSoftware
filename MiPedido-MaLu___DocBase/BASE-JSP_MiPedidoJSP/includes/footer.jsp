<%--
  ============================================================
  footer.jsp 
  
  ============================================================
--%>
<%
    String ctxFooter = request.getContextPath();
    boolean logueadoFooter = (session.getAttribute("rol") != null);
%>
<footer class="site-footer">
    <nav class="footer-nav">
        <a href="<%= ctxFooter %>/home.jsp">Home</a>
        <span class="footer-sep">·</span>
        <a href="<%= ctxFooter %>/sobre-nosotros.jsp">Sobre Nosotros</a>
        <% if (logueadoFooter) { %>
            <span class="footer-sep">·</span>
            <a href="<%= ctxFooter %>/pages/orders.jsp">Ordenes</a>
            <span class="footer-sep">·</span>
            <a href="<%= ctxFooter %>/pages/comandas.jsp">Comandas</a>
            <span class="footer-sep">·</span>
            <a href="<%= ctxFooter %>/logout.jsp" class="footer-logout">Cerrar sesion</a>
        <% } %>
    </nav>
    <p class="footer-copy">&copy; <%= java.time.Year.now() %> Restaurante MaLu · MiPedido. Todos los derechos reservados.</p>
</footer>
