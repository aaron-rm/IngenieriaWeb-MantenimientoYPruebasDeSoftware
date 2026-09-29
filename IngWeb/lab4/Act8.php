<?php
    $num = $_REQUEST["numero"];
?>

<!DOCTYPE html>
<html lang="es">
    <head>
        <meta charset="UTF-8">
        <link rel="stylesheet" href="style.css">
        <title>Tabla del <?php echo $num?>
        </title>
    </head>
    <body>
        <main>
            <?php
                $limite = 12;
                echo "<h1>Tabla del $num</h1>";
                for ($i = 1; $i <= $limite; $i++) {
                    $resultado = $num * $i;
                    echo "<p>$num x $i = $resultado</p>";
                }
            ?>
        </main>
    </body>
</html>
