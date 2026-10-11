<!DOCTYPE html>
<html lang="es">
    <head>
        <meta charset="UTF-8">
        <link rel="stylesheet" href="style.css">
        <title>Resultado de Rectangulo</title>
    </head>
    <body>
        <main>
            <?php
                $base = $_REQUEST['base'];
                $altura = $_REQUEST['altura'];

                $area = $base * $altura;
                $perimetro = 2 * ($base + $altura);

                echo "<h1>Resultado del Rectángulo</h1>";
                echo "<p><strong>Base:</strong> " . $base . "</p>";
                echo "<p><strong>Altura:</strong> " . $altura . "</p>";
                echo "<p><strong>Área:</strong> " . $area . "</p>";
                echo "<p><strong>Perímetro:</strong> " . $perimetro . "</p>";
            ?>
        </main>
    </body>
</html>
