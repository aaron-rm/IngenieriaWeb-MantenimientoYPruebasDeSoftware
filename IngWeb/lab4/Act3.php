<!DOCTYPE html>
<html lang="es">
    <head>
        <meta charset="UTF-8">
        <link rel="stylesheet" href="style.css">
        <title>Resultado de Operaciones</title>
    </head>
    <body>
        <main>
            <?php
                $numero1 = $_REQUEST['numero1'];
                $numero2 = $_REQUEST['numero2'];

                $suma = $numero1 + $numero2;
                $resta = $numero1 - $numero2;
                $multiplicacion = $numero1 * $numero2;
                if ($numero2 == 0) {
                    $division = "Error: No se puede dividir por cero";
                } else {
                    $division = $numero1 / $numero2;
                }

                echo "<h1>Resultado de Operaciones</h1>";
                echo "<p><strong>Suma:</strong>". $suma ."</p>";
                echo "<p><strong>Resta:</strong>". $resta ."</p>";
                echo "<p><strong>Multiplicación:</strong>". $multiplicacion ."</p>";
                echo "<p><strong>División:</strong>". $division ."</p>";
            ?>
        </main>
    </body>
</html>