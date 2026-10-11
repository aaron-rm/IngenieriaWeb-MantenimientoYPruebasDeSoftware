<%--
  ============================================================
  auth-check.jsp — Control de acceso por sesion y por rol
  ------------------------------------------------------------
  Como usarlo: ANTES de incluir este archivo, declarar (opcional)
  un arreglo con los roles permitidos para la pagina actual:

      <% String[] rolesPermitidos = {"cashier","supervisor","gerente"}; %>
      <%@ include file="../includes/auth-check.jsp" %>

  Si no se declara "rolesPermitidos", solo se exige que haya sesion
  iniciada (cualquier rol puede entrar).

  Si no hay sesion -> redirige a login.jsp
  Si el rol no esta permitido -> redirige a orders.jsp con mensaje
  ============================================================
--%>
<%
    // Verificamos que exista una sesion activa con usuario logueado
    String rolSesion = (session.getAttribute("rol") != null) ? (String) session.getAttribute("rol") : null;

    if (rolSesion == null) {
        // No hay sesion -> lo mandamos al login
        response.sendRedirect(request.getContextPath() + "/login.jsp");
        return;
    }

    // Si la pagina definio una lista de roles permitidos, validamos
    if (rolesPermitidos != null) {
        boolean autorizado = false;
        for (String r : rolesPermitidos) {
            if (r.equals(rolSesion)) { autorizado = true; break; }
        }
        if (!autorizado) {
            session.setAttribute("mensajeError", "No tienes permiso para acceder a esa seccion.");
            response.sendRedirect(request.getContextPath() + "/pages/orders.jsp");
            return;
        }
    }
%>
