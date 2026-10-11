<!DOCTYPE html>
<html lang="es">
    <head>
        <meta charset="UTF-8">
        <link rel="stylesheet" href="style.css">
        <title>Resultado de Venta</title>
    </head>
    <body>
        <main>
            <?php
                $precio = $_REQUEST["precio"];
                $pago = $_REQUEST["pago"];
                $vuelto = $pago - $precio;

                echo"<h1>Calculadora de vuelto</h1>";
                if ($vuelto < 0) {
                    echo "<h2>El pago es insuficiente, faltan: " . abs($vuelto)."$</h2>";
                } else {
                    echo "<h2>El vuelto es: $vuelto</h2>";
                }
            ?>
        </main>
    </body>
</html>
