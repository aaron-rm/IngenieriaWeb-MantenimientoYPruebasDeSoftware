<!DOCTYPE html>
<html lang="es">
    <head>
        <meta charset="UTF-8">
        <link rel="stylesheet" href="style.css">
        <title>Resultado de Comparacion</title>
    </head>
    <body>
        <main>
            <?php
                $numero1 = $_REQUEST['num1'];
                $numero2 = $_REQUEST['num2'];

                if ($numero1 > $numero2) {
                    $mensaje = "<p><strong>$numero1</strong> es mayor que <strong>$numero2</strong></p>";
                } elseif ($numero1 < $numero2) {
                    $mensaje = "<p><strong>$numero2</strong> es mayor que <strong>$numero1</strong></p>";
                } else {
                    $mensaje = "<p><strong>$numero1</strong> es igual a <strong>$numero2</strong></p>";
                }

                echo "<h1>Resultado de Comparacion</h1>";
                echo $mensaje;
            ?>
        </main>
    </body>
</html>