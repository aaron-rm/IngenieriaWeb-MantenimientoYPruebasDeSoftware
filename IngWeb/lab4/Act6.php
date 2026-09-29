<!DOCTYPE html>
<html lang="es">
    <head>
        <meta charset="UTF-8">
        <link rel="stylesheet" href="style.css">
        <title>Resultado de Analisis</title>
    </head>
    <body>
        <main>
            <?php
                $num = $_REQUEST["numero"];
                if ($num > 0) {
                    echo "<h1>El numero $num es positivo</h1>";
                } elseif ($num < 0) {
                    echo "<h1>El numero $num es negativo</h1>";
                } else {
                    echo "<h1>El numero es cero</h1>";
                }
            ?>
        </main>
    </body>
</html>