<?php
/**
 * datos.php — Datos de ejemplo 
 *
 * Se incluye con require_once __DIR__ . '/includes/datos.php'
 * (o '../includes/datos.php' dentro de php/pages/).
 */

require_once __DIR__ . '/config.php';

function datosIniciales(): array
{
    // ---- Secciones ----
    $secciones = [
        1 => ['id_seccion' => 1, 'nombre' => 'Restaurante', 'estado' => 'activa'],
        2 => ['id_seccion' => 2, 'nombre' => 'Bar',         'estado' => 'activa'],
    ];

    // [id, nombre, email, password, rol, id_seccion]
    $usuarios = [];
    foreach ([
        [1, 'Carlos Mendoza',  'gerente@malu.com',    'gerente123', 'gerente',    null],
        [2, 'Laura Castillo',  'supervisor@malu.com', 'super123',   'supervisor', null],
        [3, 'Ana Martinez',    'ana@malu.com',        'cashier123', 'cashier',    1],
        [4, 'Jose Rodriguez',  'jose@malu.com',       'cashier123', 'cashier',    1],
        [5, 'Luis Fernandez',  'luis@malu.com',       'cashier123', 'cashier',    2],
        [6, 'Marta Gonzalez',  'cocina@malu.com',     'cocina123',  'comandas',   null],
    ] as [$id, $nombre, $email, $pass, $rol, $idSeccion]) {
        $usuarios[$id] = [
            'id_usuario' => $id,
            'nombre'     => $nombre,
            'email'      => $email,
            'password'   => $pass,
            'rol'        => $rol,
            'id_seccion' => $idSeccion,
            'activo'     => 1,
        ];
    }

    // ---- Categorias por seccion ----
    $categorias = [];
    foreach ([
        [1, 'Alimentos',       1],
        [2, 'Bebidas No Alc.', 1],
        [3, 'Otros',           1],
        [4, 'Bebidas Alc.',    2],
        [5, 'Cocteles',        2],
    ] as [$id, $nombre, $idSeccion]) {
        $categorias[$id] = ['id_categoria' => $id, 'nombre' => $nombre, 'id_seccion' => $idSeccion];
    }

    // ---- Subcategorias ----
    $subcategorias = [];
    foreach ([
        [1, 'Entradas', 1], [2, 'Asados', 1], [3, 'Mariscos', 1], [4, 'Postres', 1],
        [5, 'Gaseosas', 2], [6, 'Aguas', 2],  [7, 'Jugos', 2],
        [8, 'Cafes', 3],    [9, 'Postres Extra', 3],
        [10, 'Cervezas', 4], [11, 'Vinos', 4],
        [12, 'Cocteles Clasicos', 5],
    ] as [$id, $nombre, $idCategoria]) {
        $subcategorias[$id] = ['id_subcategoria' => $id, 'nombre' => $nombre, 'id_categoria' => $idCategoria];
    }

    // ---- Productos ----
    // [id, nombre, precio, id_subcategoria, emoji, mas_vendido]
    // El stock inicial de cada producto esta en $stockInicial (por id).
    $stockInicial = [
        1 => 15, 2 => 15, 3 => 12, 4 => 20, 5 => 12, 6 => 15, 7 => 20, 8 => 25, 9 => 15,
        10 => 50, 11 => 50, 12 => 30, 13 => 40, 14 => 15, 15 => 60, 16 => 20, 17 => 25, 18 => 25,
    ];
    $productos = [];
    foreach ([
        [1,  'Ceviche',          8.50,  3,  '🐟', 1],
        [2,  'Churrasco',        14.00, 2,  '🥩', 1],
        [3,  'Corvina frita',    12.00, 3,  '🐠', 0],
        [4,  'Patacones',        4.00,  1,  '🫓', 0],
        [5,  'Arroz mariscos',   11.50, 3,  '🍚', 0],
        [6,  'Flan',             3.50,  4,  '🍮', 0],
        [7,  'Hamburguesa',      8.00,  2,  '🍔', 0],
        [8,  'Papas fritas',     3.00,  1,  '🍟', 0],
        [9,  'Ensalada',         5.50,  1,  '🥗', 0],
        [10, 'Coca-Cola',        1.50,  5,  '🥤', 1],
        [11, 'Agua mineral',     1.00,  6,  '💧', 0],
        [12, 'Jugo natural',     2.50,  7,  '🍊', 0],
        [13, 'Cafe',             1.50,  8,  '☕', 0],
        [14, 'Postre especial',  4.50,  9,  '🍰', 0],
        [15, 'Cerveza nacional', 2.50,  10, '🍺', 1],
        [16, 'Vino tinto',       5.50,  11, '🍷', 0],
        [17, 'Coctel Malu',      6.00,  12, '🍹', 0],
        [18, 'Ron con cola',     5.00,  12, '🥃', 0],
    ] as [$id, $nombre, $precio, $idSubcat, $emoji, $masVendido]) {
        $productos[$id] = [
            'id_producto'     => $id,
            'nombre'          => $nombre,
            'precio'          => $precio,
            'id_subcategoria' => $idSubcat,
            'emoji'           => $emoji,
            'mas_vendido'     => $masVendido,
            'stock'           => $stockInicial[$id] ?? 20,
            'activo'          => 1,
        ];
    }

    // ---- Mesas ----
    $mesas = [];
    foreach ([
        [1, 4, 'libre'], [2, 4, 'libre'],
        [3, 2, 'libre'], [4, 2, 'libre'],
        [5, 6, 'libre'], [6, 4, 'libre'],
        [7, 4, 'libre'], [8, 2, 'libre'],
    ] as [$id, $capacidad, $estado]) {
        $mesas[$id] = [
            'id_mesa' => $id,
            'numero' => $id,
            'capacidad' => $capacidad,
            'estado' => $estado
        ];
    }

    
    // ---- Pedidos, detalles y facturas ----
    // La aplicación comienza sin operaciones previas.
    $pedidos = [];
    $detalles = [];
    $facturas = [];

    // Los contadores comienzan desde cero.
    $idPedido = 0;
    $idDetalle = 0;
    $idFactura = 0;


    return [
        'secciones'     => $secciones,
        'usuarios'      => $usuarios,
        'categorias'    => $categorias,
        'subcategorias' => $subcategorias,
        'productos'     => $productos,
        'mesas'         => $mesas,
        'pedidos'       => $pedidos,
        'detalles'      => $detalles,
        'facturas'      => $facturas,
        // Contadores para generar ids nuevos (equivale al AUTO_INCREMENT)
        'seq' => [
            'secciones'  => 2,
            'categorias' => 5,
            'productos'  => 18,
            'pedidos'    => $idPedido,
            'detalles'   => $idDetalle,
            'facturas'   => $idFactura,
        ],
    ];
}

/**
 * Equipo de desarrollo que se muestra en "Sobre Nosotros".
 * Las fotos son las de la carpeta img/. 
 */
function listarEquipo(): array
{
    return [
        ['nombre' => 'Aaron',  'cedula' => '8-000-0001', 'carrera' => 'Ing. de Sistemas', 'rol' => 'Lider del proyecto',
         'resumen' => 'Coordina al equipo y define la estructura general de MiPedido. Interesado en desarrollo web con PHP.',
         'foto' => 'aaron.png'],
        ['nombre' => 'Jaziel', 'cedula' => '8-000-0002', 'carrera' => 'Ing. de Sistemas', 'rol' => 'Desarrollo backend',
         'resumen' => 'Programa la logica de pedidos, cobros y control de acceso por rol.',
         'foto' => 'jaziel.jpeg'],
        ['nombre' => 'Kaki',   'cedula' => '8-000-0003', 'carrera' => 'Ing. de Sistemas', 'rol' => 'Desarrollo frontend',
         'resumen' => 'Encargado de las pantallas de Ordenes, Mesas y del diseno responsive con HTML y CSS.',
         'foto' => 'kaki.png'],
        ['nombre' => 'Rogel',  'cedula' => '8-000-0004', 'carrera' => 'Ing. de Sistemas', 'rol' => 'Diseno de interfaz',
         'resumen' => 'Disena la identidad visual del restaurante: logo, colores y tipografia.',
         'foto' => 'rogel.jpeg'],
        ['nombre' => 'Ruth',   'cedula' => '8-000-0005', 'carrera' => 'Ing. de Sistemas', 'rol' => 'Pruebas y documentacion',
         'resumen' => 'Verifica cada flujo del sistema y redacta la documentacion del proyecto.',
         'foto' => 'ruth.jpeg'],
    ];
}


/**
 * Devuelve una REFERENCIA al arreglo de datos guardado en sesion.
 * Si es la primera vez, lo crea con los datos de ejemplo.
 * Uso:  $d = &datosRef();   (los cambios se guardan solos en la sesion)
 */
function &datosRef(): array
{
    if (!isset($_SESSION['datos']) || !is_array($_SESSION['datos'])) {
        $_SESSION['datos'] = datosIniciales();
    }
    return $_SESSION['datos'];
}

/** Siguiente id para una "tabla" (equivale al AUTO_INCREMENT). */
function siguienteId(string $tabla): int
{
    $d = &datosRef();
    $d['seq'][$tabla] = ($d['seq'][$tabla] ?? 0) + 1;
    return $d['seq'][$tabla];
}

/** Metodos de pago validos. */
function validarMetodoPago($metodo): void
{
    if (!in_array($metodo, ['efectivo', 'yappy', 'tarjeta'], true)) {
        throw new Exception('Metodo de pago invalido.');
    }
}

/* 
 * Usuarios / login
*/

/** Devuelve el usuario si email/password coinciden (y esta activo), o null. */
function autenticarUsuario(string $email, string $password): ?array
{
    $d = datosRef();
    foreach ($d['usuarios'] as $u) {
        if ($u['activo'] && strcasecmp($u['email'], $email) === 0 && $u['password'] === $password) {
            $u['seccion_nombre'] = ($u['id_seccion'] !== null)
                ? ($d['secciones'][$u['id_seccion']]['nombre'] ?? '')
                : null;
            return $u;
        }
    }
    return null;
}

/* 
 * Secciones, categorias y asignacion de usuarios
*/

function listarSecciones(): array
{
    $d = datosRef();
    return array_values($d['secciones']);
}

function crearSeccion(string $nombre): int
{
    $nombre = trim($nombre);
    if ($nombre === '') {
        throw new Exception('El nombre de la seccion no puede estar vacio.');
    }
    $id = siguienteId('secciones');
    $d = &datosRef();
    $d['secciones'][$id] = ['id_seccion' => $id, 'nombre' => $nombre, 'estado' => 'activa'];
    return $id;
}

function categoriasDeSeccion(int $idSeccion): array
{
    $d = datosRef();
    return array_values(array_filter(
        $d['categorias'],
        fn($c) => (int) $c['id_seccion'] === $idSeccion
    ));
}

/** Todas las categorias; las de la seccion indicada van primero. */
function categoriasOrdenadas(int $idSeccionPrimero): array
{
    $d = datosRef();
    $todas = array_values($d['categorias']);
    usort($todas, function ($a, $b) use ($idSeccionPrimero) {
        $pa = ((int) $a['id_seccion'] === $idSeccionPrimero) ? 0 : 1;
        $pb = ((int) $b['id_seccion'] === $idSeccionPrimero) ? 0 : 1;
        return ($pa <=> $pb) ?: ($a['id_categoria'] <=> $b['id_categoria']);
    });
    return $todas;
}

function crearCategoria(string $nombre, int $idSeccion): int
{
    $nombre = trim($nombre);
    if ($nombre === '') {
        throw new Exception('El nombre de la categoria no puede estar vacio.');
    }
    $d = &datosRef();
    if (!isset($d['secciones'][$idSeccion])) {
        throw new Exception('La seccion indicada no existe.');
    }
    $id = siguienteId('categorias');
    $d['categorias'][$id] = ['id_categoria' => $id, 'nombre' => $nombre, 'id_seccion' => $idSeccion];
    return $id;
}

function eliminarCategoria(int $idCategoria): void
{
    $d = &datosRef();
    foreach ($d['subcategorias'] as $sc) {
        if ((int) $sc['id_categoria'] === $idCategoria) {
            throw new Exception('No se puede eliminar una categoria que tiene subcategorias.');
        }
    }
    unset($d['categorias'][$idCategoria]);
}

function usuariosDeSeccion(int $idSeccion): array
{
    $d = datosRef();
    return array_values(array_filter(
        $d['usuarios'],
        fn($u) => $u['activo'] && $u['id_seccion'] !== null && (int) $u['id_seccion'] === $idSeccion
    ));
}

/** Cashiers activos que NO estan asignados a la seccion indicada. */
function cashiersFueraDeSeccion(int $idSeccion): array
{
    $d = datosRef();
    return array_values(array_filter(
        $d['usuarios'],
        fn($u) => $u['activo'] && $u['rol'] === 'cashier'
            && ($u['id_seccion'] === null || (int) $u['id_seccion'] !== $idSeccion)
    ));
}

function asignarSeccion(int $idUsuario, ?int $idSeccion): void
{
    $d = &datosRef();
    if (isset($d['usuarios'][$idUsuario])) {
        $d['usuarios'][$idUsuario]['id_seccion'] = $idSeccion;
    }
}

/*
 * Productos
*/

function productoPorId(int $idProducto): ?array
{
    $d = datosRef();
    return $d['productos'][$idProducto] ?? null;
}

function productosMasVendidos(): array
{
    $d = datosRef();
    return array_values(array_filter(
        $d['productos'],
        fn($p) => $p['mas_vendido'] && $p['activo']
    ));
}

/** Productos activos, filtrados por categoria y/o texto, ordenados por nombre. */
function buscarProductos(?int $idCategoria = null, ?string $texto = null): array
{
    $d = datosRef();
    $texto = ($texto !== null) ? trim($texto) : '';
    $res = [];
    foreach ($d['productos'] as $p) {
        if (!$p['activo']) continue;
        $sc = $d['subcategorias'][$p['id_subcategoria']] ?? null;
        if ($sc === null) continue;
        if ($idCategoria !== null && (int) $sc['id_categoria'] !== $idCategoria) continue;
        if ($texto !== '' && mb_stripos($p['nombre'], $texto) === false) continue;
        $res[] = [
            'id_producto' => $p['id_producto'],
            'nombre'      => $p['nombre'],
            'precio'      => $p['precio'],
            'emoji'       => $p['emoji'],
            'subcat'      => $sc['nombre'],
            'stock'       => (int) ($p['stock'] ?? 0),
        ];
    }
    usort($res, fn($a, $b) => strcasecmp($a['nombre'], $b['nombre']));
    return $res;
}

/** Todas las subcategorias con el nombre de su categoria (para los formularios). */
function listarSubcategorias(): array
{
    $d = datosRef();
    $res = [];
    foreach ($d['subcategorias'] as $sc) {
        $cat = $d['categorias'][$sc['id_categoria']] ?? null;
        $res[] = [
            'id_subcategoria' => $sc['id_subcategoria'],
            'nombre'          => $sc['nombre'],
            'categoria'       => $cat['nombre'] ?? '',
        ];
    }
    return $res;
}

/** Productos activos con su subcategoria y stock, ordenados por nombre (vista de inventario). */
function listarProductosConStock(): array
{
    $d = datosRef();
    $res = [];
    foreach ($d['productos'] as $p) {
        if (!$p['activo']) continue;
        $sc  = $d['subcategorias'][$p['id_subcategoria']] ?? null;
        $cat = $sc ? ($d['categorias'][$sc['id_categoria']] ?? null) : null;
        $p['stock']     = (int) ($p['stock'] ?? 0);
        $p['subcat']    = $sc['nombre'] ?? '';
        $p['categoria'] = $cat['nombre'] ?? '';
        $res[] = $p;
    }
    usort($res, fn($a, $b) => strcasecmp($a['nombre'], $b['nombre']));
    return $res;
}

/** Valida los datos de un producto; lanza Exception si algo no es valido. */
function validarDatosProducto(string $nombre, $precio, int $idSubcategoria, int $stock): void
{
    $d = datosRef();
    if (trim($nombre) === '') {
        throw new Exception('El nombre del plato no puede estar vacio.');
    }
    if (!is_numeric($precio) || (float) $precio < 0) {
        throw new Exception('El precio debe ser un numero mayor o igual a 0.');
    }
    if (!isset($d['subcategorias'][$idSubcategoria])) {
        throw new Exception('La subcategoria indicada no existe.');
    }
    if ($stock < 0) {
        throw new Exception('El stock no puede ser negativo.');
    }
}

function crearProducto(string $nombre, $precio, int $idSubcategoria, string $emoji, int $stock, bool $masVendido = false): int
{
    $nombre = trim($nombre);
    validarDatosProducto($nombre, $precio, $idSubcategoria, $stock);
    $id = siguienteId('productos');
    $d = &datosRef();
    $d['productos'][$id] = [
        'id_producto'     => $id,
        'nombre'          => $nombre,
        'precio'          => round((float) $precio, 2),
        'id_subcategoria' => $idSubcategoria,
        'emoji'           => trim($emoji) !== '' ? trim($emoji) : '🍽️',
        'mas_vendido'     => $masVendido ? 1 : 0,
        'stock'           => $stock,
        'activo'          => 1,
    ];
    return $id;
}

function actualizarProducto(int $idProducto, string $nombre, $precio, int $idSubcategoria, string $emoji, int $stock, bool $masVendido): void
{
    $nombre = trim($nombre);
    validarDatosProducto($nombre, $precio, $idSubcategoria, $stock);
    $d = &datosRef();
    if (!isset($d['productos'][$idProducto]) || !$d['productos'][$idProducto]['activo']) {
        throw new Exception('El plato indicado no existe.');
    }
    $d['productos'][$idProducto]['nombre']          = $nombre;
    $d['productos'][$idProducto]['precio']          = round((float) $precio, 2);
    $d['productos'][$idProducto]['id_subcategoria'] = $idSubcategoria;
    $d['productos'][$idProducto]['emoji']           = trim($emoji) !== '' ? trim($emoji) : '🍽️';
    $d['productos'][$idProducto]['stock']           = $stock;
    $d['productos'][$idProducto]['mas_vendido']     = $masVendido ? 1 : 0;
}

/**
 * Elimina un plato del catalogo. Es un borrado logico (activo = 0) para no
 * romper los pedidos y facturas que ya lo usan.
 */
function eliminarProducto(int $idProducto): void
{
    $d = &datosRef();
    if (!isset($d['productos'][$idProducto])) {
        throw new Exception('El plato indicado no existe.');
    }
    $d['productos'][$idProducto]['activo'] = 0;
}

/** Unidades disponibles de un producto (0 si no existe). */
function stockDisponible(int $idProducto): int
{
    $d = datosRef();
    return (int) ($d['productos'][$idProducto]['stock'] ?? 0);
}

/**
 * Verifica que haya stock suficiente para sumar $cantidadExtra unidades a
 * lo que ya hay en un carrito [idProducto => cantidad]. Lanza Exception si no.
 */
function validarStockAgregar(int $idProducto, int $cantidadEnCarrito, int $cantidadExtra = 1): void
{
    $p = productoPorId($idProducto);
    if (!$p || !$p['activo']) {
        throw new Exception('Ese plato ya no esta disponible.');
    }
    $stock = (int) ($p['stock'] ?? 0);
    if ($cantidadEnCarrito + $cantidadExtra > $stock) {
        throw new Exception($stock <= 0
            ? $p['nombre'] . ' esta agotado.'
            : 'Stock insuficiente de ' . $p['nombre'] . ': solo hay ' . $stock . ' disponible(s).');
    }
}

/** Verifica el stock de todo un carrito antes de crear el pedido. */
function validarStockCarrito(array $carrito): void
{
    foreach ($carrito as $idProd => $cant) {
        validarStockAgregar((int) $idProd, 0, (int) $cant);
    }
}

/** Resta del stock las unidades de un carrito (se llama al crear el pedido). */
function descontarStock(array $carrito): void
{
    $d = &datosRef();
    foreach ($carrito as $idProd => $cant) {
        if (isset($d['productos'][(int) $idProd])) {
            $d['productos'][(int) $idProd]['stock'] = max(0, (int) ($d['productos'][(int) $idProd]['stock'] ?? 0) - (int) $cant);
        }
    }
}

/** Total de un carrito [idProducto => cantidad] usando los precios del catalogo. */
function calcularTotal(array $carrito): float
{
    $total = 0.0;
    foreach ($carrito as $idProd => $cant) {
        $p = productoPorId((int) $idProd);
        if ($p) $total += $p['precio'] * (int) $cant;
    }
    return round($total, 2);
}

/* 
 * Mesas 
*/

function listarMesas(): array
{
    $d = datosRef();
    $mesas = array_values($d['mesas']);
    usort($mesas, fn($a, $b) => $a['numero'] <=> $b['numero']);
    return $mesas;
}

function cambiarEstadoMesa(int $idMesa, string $estado): void
{
    $d = &datosRef();
    if (isset($d['mesas'][$idMesa]) && in_array($estado, ['libre', 'ocupada'], true)) {
        $d['mesas'][$idMesa]['estado'] = $estado;
    }
}

/* 
 * Pedidos (comandas), detalle y facturas
*/

/**
 * Crea un pedido con sus platos. $c: origen, id_mesa, comensal, id_usuario,
 * id_seccion, estado, nota, metodo_pago, total, cobrado.
 * $carrito: [idProducto => cantidad]. Devuelve el id del pedido nuevo.
 */
function crearPedido(array $c, array $carrito): int
{
    // Primero validamos el stock: si falta alguno, no se crea nada
    validarStockCarrito($carrito);

    $id = siguienteId('pedidos');
    $d = &datosRef();
    $d['pedidos'][$id] = [
        'id_pedido'     => $id,
        'numero_pedido' => $id, // el numero visible = id (orden de entrada)
        'origen'        => $c['origen'],
        'id_mesa'       => $c['id_mesa'] ?? null,
        'comensal'      => $c['comensal'] ?? null,
        'id_usuario'    => $c['id_usuario'] ?? null,
        'id_seccion'    => $c['id_seccion'],
        'estado'        => $c['estado'] ?? 'pendiente',
        'nota'          => $c['nota'] ?? null,
        'metodo_pago'   => $c['metodo_pago'] ?? null,
        'total'         => $c['total'] ?? 0,
        'cobrado'       => $c['cobrado'] ?? 0,
        'fecha_hora'    => date('Y-m-d H:i:s'),
    ];
    foreach ($carrito as $idProd => $cant) {
        if (!isset($d['productos'][(int) $idProd])) continue;
        $idDet = siguienteId('detalles');
        $d['detalles'][$idDet] = [
            'id_detalle'  => $idDet,
            'id_pedido'   => $id,
            'id_producto' => (int) $idProd,
            'cantidad'    => (int) $cant,
            'nota'        => null,
            'preparado'   => 0,
        ];
    }
    // Cada plato pedido se resta del stock disponible
    descontarStock($carrito);
    return $id;
}

function pedidoPorId(int $idPedido): ?array
{
    $d = datosRef();
    return $d['pedidos'][$idPedido] ?? null;
}

/** Pedidos cuyo estado esta en la lista, del mas antiguo al mas nuevo. */
function pedidosPorEstado(array $estados): array
{
    $d = datosRef();
    $res = array_values(array_filter($d['pedidos'], fn($p) => in_array($p['estado'], $estados, true)));
    usort($res, fn($a, $b) => strcmp($a['fecha_hora'], $b['fecha_hora']) ?: ($a['id_pedido'] <=> $b['id_pedido']));
    return $res;
}

/** Pedidos "listo" en cocina que aun no se han cobrado, de una seccion. */
function pedidosListosSinCobrar(int $idSeccion): array
{
    $d = datosRef();
    $res = array_values(array_filter(
        $d['pedidos'],
        fn($p) => $p['estado'] === 'listo' && !$p['cobrado'] && (int) $p['id_seccion'] === $idSeccion
    ));
    usort($res, fn($a, $b) => strcmp($a['fecha_hora'], $b['fecha_hora']) ?: ($a['id_pedido'] <=> $b['id_pedido']));
    return $res;
}

function actualizarPedido(int $idPedido, array $campos): void
{
    $d = &datosRef();
    if (!isset($d['pedidos'][$idPedido])) return;
    if (isset($campos['estado']) && !in_array($campos['estado'], ['pendiente', 'en_preparacion', 'listo', 'entregado'], true)) {
        throw new Exception('Estado de pedido invalido.');
    }
    foreach ($campos as $clave => $valor) {
        $d['pedidos'][$idPedido][$clave] = $valor;
    }
}

/** Platos de un pedido con nombre y precio del producto. */
function detallesDePedido(int $idPedido): array
{
    $d = datosRef();
    $res = [];
    foreach ($d['detalles'] as $det) {
        if ((int) $det['id_pedido'] !== $idPedido) continue;
        $p = $d['productos'][$det['id_producto']] ?? null;
        if ($p === null) continue;
        $res[] = [
            'id_detalle' => $det['id_detalle'],
            'cantidad'   => $det['cantidad'],
            'preparado'  => $det['preparado'],
            'nota'       => $det['nota'],
            'nombre'     => $p['nombre'],
            'precio'     => $p['precio'],
        ];
    }
    return $res;
}

/** Tacha / destacha un plato en cocina. */
function alternarPreparado(int $idDetalle): void
{
    $d = &datosRef();
    if (isset($d['detalles'][$idDetalle])) {
        $d['detalles'][$idDetalle]['preparado'] = $d['detalles'][$idDetalle]['preparado'] ? 0 : 1;
    }
}

function marcarTodoPreparado(int $idPedido): void
{
    $d = &datosRef();
    foreach ($d['detalles'] as $idDet => $det) {
        if ((int) $det['id_pedido'] === $idPedido) {
            $d['detalles'][$idDet]['preparado'] = 1;
        }
    }
}

/** Genera la factura de un pedido cobrado. Devuelve el id de la factura. */
function crearFactura(int $idPedido, float $total, $metodo, int $idSeccion): int
{
    validarMetodoPago($metodo);
    $id = siguienteId('facturas');
    $d = &datosRef();
    $d['facturas'][$id] = [
        'id_factura'  => $id,
        'id_pedido'   => $idPedido,
        'total'       => $total,
        'metodo_pago' => $metodo,
        'id_seccion'  => $idSeccion,
        'anulada'     => 0,
        'fecha_hora'  => date('Y-m-d H:i:s'),
    ];
    return $id;
}

/** Facturas de hoy (no anuladas), con filtros opcionales, mas recientes primero. */
function facturasDeHoy(?string $metodo = null, ?string $origen = null): array
{
    $d = datosRef();
    $hoy = date('Y-m-d');
    $res = [];
    foreach ($d['facturas'] as $f) {
        if ($f['anulada']) continue;
        if (date('Y-m-d', strtotime($f['fecha_hora'])) !== $hoy) continue;
        $p = $d['pedidos'][$f['id_pedido']] ?? null;
        if ($p === null) continue;
        if ($metodo !== null && $metodo !== '' && $f['metodo_pago'] !== $metodo) continue;
        if ($origen !== null && $origen !== '' && $p['origen'] !== $origen) continue;
        $res[] = [
            'id_factura'  => $f['id_factura'],
            'total'       => $f['total'],
            'metodo_pago' => $f['metodo_pago'],
            'fecha_hora'  => $f['fecha_hora'],
            'origen'      => $p['origen'],
            'id_mesa'     => $p['id_mesa'],
            'comensal'    => $p['comensal'],
        ];
    }
    usort($res, fn($a, $b) => strcmp($b['fecha_hora'], $a['fecha_hora']) ?: ($b['id_factura'] <=> $a['id_factura']));
    return $res;
}

function facturaPorId(int $idFactura): ?array
{
    $d = datosRef();
    $f = $d['facturas'][$idFactura] ?? null;
    if ($f === null) return null;
    $p = $d['pedidos'][$f['id_pedido']] ?? null;
    if ($p === null) return null;
    return [
        'id_factura'  => $f['id_factura'],
        'total'       => $f['total'],
        'metodo_pago' => $f['metodo_pago'],
        'fecha_hora'  => $f['fecha_hora'],
        'id_pedido'   => $p['id_pedido'],
        'origen'      => $p['origen'],
        'id_mesa'     => $p['id_mesa'],
        'comensal'    => $p['comensal'],
    ];
}
