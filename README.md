# Ingeniería Web · Mantenimiento y Pruebas de Software

Repositorio académico que reúne laboratorios, actividades y proyectos de las asignaturas **Ingeniería Web** y **Mantenimiento y Pruebas de Software**. Su propósito es organizar los trabajos realizados y ofrecer un punto de entrada común para consultarlos desde el navegador.

El contenido principal incluye un sitio de noticias, ejercicios con formularios HTML y procesamiento en PHP, y versiones del proyecto **MiPedido**, un sistema de gestión de pedidos para el Restaurante MaLu.

## Navegación

El [índice principal](index.html) permite elegir una asignatura. Cada asignatura tiene su propio índice con enlaces a sus actividades y proyectos:

- [Ingeniería Web](IngWeb/index.html): laboratorio 3, las ocho actividades del laboratorio 4 y Proyecto 1.
- [Mantenimiento y Pruebas de Software](MantSoftware/index.html): proyecto MiPedido y hoja de grupos y temas del proyecto final.

Los índices de ambas asignaturas incluyen enlaces para volver al inicio. Comparten la hoja de estilos `indices.css` y utilizan rutas relativas.

## Estructura del repositorio

```text
IngenieriaWeb-MantenimientoYPruebasDeSoftware/
├── index.html                      # Entrada principal del portafolio
├── indices.css                     # Estilos de los tres índices
├── IngWeb/                         # Trabajos de Ingeniería Web
│   ├── index.html                   # Índice de la asignatura
│   ├── lab3/                       # Sitio de noticias y análisis de sitios web
│   ├── lab4/                       # Ocho actividades con HTML y PHP
│   └── Proyecto 1/                 # Proyecto MiPedido de Ingeniería Web
│       ├── html/                   # Página de entrada al proyecto
│       ├── php/                    # Páginas y lógica del sistema
│       ├── css/                    # Hojas de estilo del proyecto
│       └── img/                    # Imágenes y recursos gráficos
├── MantSoftware/                   # Trabajos de Mantenimiento de Software
│   ├── index.html                   # Índice de la asignatura
│   ├── Proyecto/                   # Proyecto MiPedido de esta asignatura
│   │   ├── html/
│   │   ├── php/
│   │   ├── css/
│   │   └── img/
│   └── Grupos y Temas - Proyecto Final 1SF134.xlsx
├── MiPedido-MaLu___DocBase/          # Documentación y base del proyecto en JSP
├── MiPedidoPHP/                     # Versión PHP de referencia del proyecto
└── .github/                        # Configuración de flujos de trabajo
```

## Ingeniería Web

### Laboratorio 3

La carpeta `IngWeb/lab3/` contiene **Noticias SA Nacional**, un sitio con páginas de noticias nacionales, deportivas e internacionales, contacto y suscripción. Incluye sus estilos, imágenes y un documento PDF de análisis de cinco sitios web.

Su archivo `index.html` redirige a `Home.html`, la página de inicio del sitio.

### Laboratorio 4

La carpeta `IngWeb/lab4/` contiene ocho ejercicios. Cada actividad tiene un formulario `ActN.html` y un archivo `ActN.php` que procesa los datos enviados; todas comparten `style.css`.

| Actividad | Contenido |
| --- | --- |
| 1 | Registro de calificaciones y cálculo del promedio |
| 2 | Cálculo del área y perímetro de un rectángulo |
| 3 | Operaciones con dos números |
| 4 | Cálculo del vuelto de una compra |
| 5 | Comparación de dos números |
| 6 | Identificación de un número como positivo, negativo o cero |
| 7 | Generación de potencias con valores elegidos o aleatorios |
| 8 | Generación de una tabla de multiplicar |

### Proyecto 1

La carpeta `IngWeb/Proyecto 1/` contiene el proyecto **MiPedido · Restaurante MaLu**. El acceso se realiza mediante `html/index.html`, que redirige a `php/home.php`.

## Mantenimiento y Pruebas de Software

La carpeta `MantSoftware/Proyecto/` contiene el proyecto **MiPedido · Restaurante MaLu** correspondiente a esta asignatura. Su página de entrada también es `html/index.html` y redirige a `php/home.php`.

El índice de la asignatura ofrece además la descarga de `Grupos y Temas - Proyecto Final 1SF134.xlsx`, como material de referencia del proyecto final.

## Organización de los proyectos MiPedido

Los proyectos dentro de las dos asignaturas siguen la misma organización:

- `html/`: página de entrada al sistema.
- `php/`: inicio, inicio y cierre de sesión, página sobre el equipo y lógica de la aplicación.
- `php/pages/`: módulos de pedidos, comandas, secciones y ventas.
- `php/includes/`: configuración, datos de ejemplo, comprobación de sesión y elementos compartidos como encabezado y pie de página.
- `css/`: estilos generales y de los distintos módulos.
- `img/`: logotipo e imágenes del equipo.

## Material de referencia fuera del portafolio

Las siguientes carpetas se conservan en el repositorio, pero **no están enlazadas desde los índices del portafolio**:

- `MiPedido-MaLu___DocBase/`: contiene la base del proyecto en `BASE-JSP_MiPedidoJSP/`, documentación, modelado de base de datos, wireframes y una referencia a los requisitos.
- `MiPedidoPHP/`: contiene una versión PHP de referencia, con sus recursos, scripts SQL y un `README.txt` propio.

## Tecnologías y archivos

Las actividades y los proyectos de las asignaturas utilizan **HTML, CSS y PHP**. El material de referencia también incluye **JSP y SQL**, junto con documentos **PDF** y una hoja de cálculo **XLSX**.
