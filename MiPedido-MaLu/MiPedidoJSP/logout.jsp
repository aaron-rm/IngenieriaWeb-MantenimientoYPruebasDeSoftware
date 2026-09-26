<%-- ============================================================
     logout.jsp — Cierra la sesion y regresa siempre a login.jsp
     (requisito explicito de la guia del profesor)
     ============================================================ --%>
<%@ page contentType="text/html; charset=UTF-8" pageEncoding="UTF-8" %>
<%
    session.invalidate(); // destruye toda la informacion de la sesion (rol, usuario, carrito, etc.)
    response.sendRedirect("login.jsp");
%>
