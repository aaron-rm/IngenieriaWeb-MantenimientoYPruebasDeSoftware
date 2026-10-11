<!DOCTYPE html>
<html lang="es">
    <head>
        <meta charset="UTF-8">
        <link rel="stylesheet" href="style.css">
        <title>Resultado de Calculadora</title>
    </head>
    <body>
        <main>
            <?php
                $nombre = $_REQUEST['nombre'];
                $nota1 = $_REQUEST['nota1'];
                $nota2 = $_REQUEST['nota2'];
                $nota3 = $_REQUEST['nota3'];
                $nota4 = $_REQUEST['nota4'];
                $nota5 = $_REQUEST['nota5'];

                $suma = $nota1 + $nota2 + $nota3 + $nota4 + $nota5;
                $promedio = $suma / 5;
                $promedio_formateado = number_format($promedio, 2);

                echo "<h1>Resultado del Semestre</h1>";
                echo "<p><strong>Estudiante:</strong> " . $nombre . "</p>";
                echo "<p><strong>Promedio Final:</strong> " . $promedio_formateado . "</p>";
            ?>
        </main>
    </body>
</html>
