<!DOCTYPE html>
<html lang="es">
    <head>
        <meta charset="UTF-8">
        <link rel="stylesheet" href="style.css">
        <title>Potencias del numero</title>
    </head>
    <body>
        <main>
            <?php
                $num = $_REQUEST["num_elegido"];
                $potencia = $_REQUEST["exp_elegido"];
                $limite = 2000;

                if ($num < 0) {
                    $num = random_int(1, 9);
                }

                if ($potencia < 0) {
                    $potencia = random_int(1, 9);
                }

                $resultado = 1;
                echo "<h1>Potencias del numero $num</h1>";
                echo "<p>(Resultados menores a 2000)</p>";
                for ($i = 1; $i <= $potencia; $i++) {
                    $resultado *= $num;
                    if ($resultado > $limite) {
                        break;
                    }
                    echo "<p>$num ^ $i = $resultado</p>";
                }
            ?>
        </main>
    </body>
</html>
