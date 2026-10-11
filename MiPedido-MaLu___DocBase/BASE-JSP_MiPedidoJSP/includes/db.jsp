<%--
  ============================================================
  db.jsp — Conexion a la base de datos MySQL (XAMPP)
  ------------------------------------------------------------
  Este archivo se incluye con <%@ include file="../includes/db.jsp" %>
  al inicio de cada pagina que necesite hablar con la base de datos.

  Expone un metodo getConnection() que cualquier scriptlet de la
  pagina puede usar asi:
      Connection con = getConnection();
      ...
      con.close();

  Datos de conexion por defecto de XAMPP:
      host: localhost   puerto: 3306   usuario: root   password: (vacio)
  Si tu XAMPP tiene otra configuracion, cambia las 4 constantes de abajo.
  ============================================================
--%>
<%@ page import="java.sql.Connection" %>
<%@ page import="java.sql.DriverManager" %>
<%@ page import="java.sql.SQLException" %>
<%!
    // --- Datos de conexion a MySQL/XAMPP ---
    private static final String DB_HOST = "localhost";
    private static final String DB_PORT = "3306";
    private static final String DB_NAME = "mipedido_db";
    private static final String DB_USER = "root";
    private static final String DB_PASS = ""; // XAMPP no trae password por defecto

    private static final String DB_URL =
        "jdbc:mysql://" + DB_HOST + ":" + DB_PORT + "/" + DB_NAME +
        "?useSSL=false&serverTimezone=America/Panama&characterEncoding=UTF-8";

    // Metodo reutilizable para obtener una conexion nueva
    public Connection getConnection() throws SQLException {
        try {
            Class.forName("com.mysql.cj.jdbc.Driver");
        } catch (ClassNotFoundException e) {
            throw new SQLException("No se encontro el driver de MySQL (mysql-connector-j). " +
                "Verifica que el .jar este en WEB-INF/lib.", e);
        }
        return DriverManager.getConnection(DB_URL, DB_USER, DB_PASS);
    }
%>
