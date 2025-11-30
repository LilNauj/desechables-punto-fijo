<?php
/**
 * CATÁLOGO PÚBLICO DE PRODUCTOS
 * Desechables Punto Fijo
 */


error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'config/config.php';
require_once 'config/upload_config.php';

// Si no está logueado, redirigir al login
//if (!estaLogueado()) {
//redirect('login.php');
//}

// Si es admin y viene del login, redirigir a admin.php
if (esAdmin() && !isset($_GET['ver_tienda']) && !isset($_SERVER['HTTP_REFERER'])) {
    if (empty($_GET)) {
        redirect('admin.php');
    }
}

$db = getDB();

// OBTENER TODOS LOS PRODUCTOS (para filtrar con JS)
$sql = "SELECT p.*, c.nombre as categoria_nombre, c.id as categoria_id FROM productos p 
            LEFT JOIN categorias c ON p.categoria_id = c.id 
            WHERE p.estado = 'disponible'
            ORDER BY p.nombre ASC";

$result = $db->query($sql);
$productos = $result->fetch_all(MYSQLI_ASSOC);

// OBTENER CATEGORÍAS
$categorias = $db->query("SELECT * FROM categorias WHERE estado = 'activo' ORDER BY nombre")->fetch_all(MYSQLI_ASSOC);



// OBTENER CANTIDAD DE ITEMS EN EL CARRITO
$carrito_count = 0;
if (estaLogueado()) {
    $stmt = $db->prepare("SELECT COUNT(*) as total FROM carrito WHERE usuario_id = ?");
    $stmt->bind_param("i", $_SESSION['usuario_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    $carrito_count = $result->fetch_assoc()['total'];
    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="utf-8">
    <meta content="width=device-width, initial-scale=1.0" name="viewport">
    <title>Catálogo - Desechables Punto Fijo</title>
    <meta name="description" content="Catálogo de productos - Desechables Punto Fijo Barahoja">
    <meta name="keywords" content="desechables, productos, punto fijo, barahoja">

    <!-- Favicons -->
    <link href="assets/img/favicon.png" rel="icon">
    <link href="assets/img/apple-touch-icon.png" rel="apple-touch-icon">

    <!-- Fonts -->
    <link href="https://fonts.googleapis.com" rel="preconnect">
    <link href="https://fonts.gstatic.com" rel="preconnect" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=Roboto:ital,wght@0,100;0,300;0,400;0,500;0,700;0,900&family=Montserrat:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900&family=Poppins:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900&display=swap"
        rel="stylesheet">

    <!-- Vendor CSS Files -->
    <link href="assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <link href="assets/vendor/bootstrap-icons/bootstrap-icons.css" rel="stylesheet">
    <link href="assets/vendor/aos/aos.css" rel="stylesheet">
    <link href="assets/vendor/swiper/swiper-bundle.min.css" rel="stylesheet">

    <!-- Main CSS File -->
    <link href="assets/css/main.css" rel="stylesheet">

    <style>
        .category-pill:hover,
        .category-pill.active {
            background: var(--accent-color) !important;
            color: var(--contrast-color) !important;
            border-color: var(--accent-color) !important;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.15);
        }

        /* Fix para las imágenes de productos */
        .product-card .product-image {
            position: relative;
            width: 100%;
            padding-top: 100%;
            /* Ratio 1:1 */
            overflow: hidden;
            border-radius: 15px 15px 0 0;
            background: var(--surface-color);
        }

        .product-card .product-image>div {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #667eea22 0%, #764ba222 100%);
        }

        /* Animación suave para filtros */
        .product-card {
            transition: all 0.3s ease;
        }

        .product-card.hidden {
            display: none;
        }

        .fade-in {
            animation: fadeIn 0.3s ease;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(10px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Banner del hero (derecha) */
        .hero-product-image {
            position: relative;
            width: 100%;
            padding-top: 60%;
            /* relación de aspecto aprox 16:9 */
            border-radius: 20px;
            overflow: hidden;
            background: linear-gradient(135deg, #667eea22 0%, #764ba222 100%);
            box-shadow: 0 15px 40px rgba(15, 23, 42, 0.15);
            margin-bottom: 10px;
        }

        .hero-product-image img {
            position: absolute;
            inset: 0;
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
        }
    </style>
</head>

<body class="index-page">

    <?php include 'includes/headeri.php'; ?>

    <main class="main">

        <!-- Hero Section -->
        <section id="hero" class="hero section">
            <div class="container">

                <?php if (esAdmin()): ?>
                    <!-- Banner especial para Admin -->
                    <div class="alert alert-info alert-dismissible fade show d-flex align-items-center mb-4" role="alert"
                        data-aos="fade-down">
                        <i class="bi bi-shield-check" style="font-size: 2rem; margin-right: 15px;"></i>
                        <div class="flex-grow-1">
                            <strong>¡Hola Administrador!</strong> Estás viendo la tienda como cliente.
                        </div>
                        <a href="admin.php" class="btn btn-primary me-2">
                            <i class="bi bi-gear-fill"></i> Panel Admin
                        </a>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <div class="hero-container">
                    <div class="hero-content" data-aos="fade-up" data-aos-delay="100">
                        <div class="content-wrapper">
                            <h1 class="hero-title">Desechables Punto Fijo</h1>
                            <p class="hero-description">
                                Tu tienda de confianza en Barahoja, Aguachica.
                                Encuentra todo lo que necesitas en productos desechables de calidad.
                            </p>

                            <!-- Buscador -->
                            <div class="mb-4">
                                <div class="input-group input-group-lg">
                                    <input type="text" id="searchInput" class="form-control"
                                        placeholder="¿Qué estás buscando?">
                                    <button class="btn btn-primary" type="button" onclick="filtrarProductos()">
                                        <i class="bi bi-search"></i> Buscar
                                    </button>
                                </div>
                            </div>

                            <div class="hero-actions">
                                <a href="#productos" class="btn-primary">
                                    <i class="bi bi-grid-fill"></i> Ver Catálogo
                                </a>
                                <a href="carrito.php" class="btn-secondary">
                                    <i class="bi bi-cart-fill"></i> Mi Carrito (<?php echo $carrito_count; ?>)
                                </a>
                            </div>

                            <div class="features-list">
                                <div class="feature-item">
                                    <i class="bi bi-truck"></i>
                                    <span>Envío Rápido</span>
                                </div>
                                <div class="feature-item">
                                    <i class="bi bi-shield-check"></i>
                                    <span>Compra Segura</span>
                                </div>
                                <div class="feature-item">
                                    <i class="bi bi-percent"></i>
                                    <span>Mejores Precios</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="hero-visuals" data-aos="fade-left" data-aos-delay="200">
                        <div class="product-showcase">
                            <div class="product-card">
                                <div class="product-badge">Destacado</div>

                                <!-- Banner / Imagen en la parte derecha -->
                                <div class="hero-product-image">
                                    <img src="assets/img/banner-productos.jpg"
                                        alt="Banner productos desechables Desechables Punto Fijo">
                                </div>

                                <div class="product-info" style="padding: 20px;">
                                    <h4>Productos de Calidad</h4>
                                    <div class="price">
                                        <span class="sale-price">Desde $1.000</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </section><!-- /Hero Section -->



        <!-- Catálogo de Productos -->
        <section id="productos" class="category-product-list section">
            <div class="container">
                <div class="row">

                    <!-- Sidebar Filtros -->
                    <div class="col-lg-3 mb-4">
                        <div class="filter-sidebar" data-aos="fade-right">
                            <h5 class="mb-4"><i class="bi bi-funnel-fill"></i> Filtros</h5>

                            <!-- Ordenar -->
                            <div class="mb-4">
                                <h6>Ordenar por</h6>
                                <select id="ordenSelect" class="form-select" onchange="ordenarProductos()">
                                    <option value="nombre-asc">Nombre A-Z</option>
                                    <option value="nombre-desc">Nombre Z-A</option>
                                    <option value="precio-asc">Precio menor</option>
                                    <option value="precio-desc">Precio mayor</option>
                                    <option value="destacado">Destacados</option>
                                </select>
                            </div>

                            <!-- Categorías -->
                            <div class="mb-4">
                                <h6>Categorías</h6>
                                <div class="list-group">
                                    <a href="javascript:void(0)" onclick="filtrarPorCategoria('')"
                                        class="list-group-item list-group-item-action active" id="cat-todas">
                                        <i class="bi bi-grid"></i> Todas las categorías
                                    </a>
                                    <?php foreach ($categorias as $cat): ?>
                                        <a href="javascript:void(0)"
                                            onclick="filtrarPorCategoria('<?php echo $cat['id']; ?>')"
                                            class="list-group-item list-group-item-action"
                                            id="cat-<?php echo $cat['id']; ?>">
                                            <i class="bi bi-tag"></i> <?php echo htmlspecialchars($cat['nombre']); ?>
                                        </a>
                                    <?php endforeach; ?>
                                </div>
                            </div>

                            <!-- Limpiar Filtros -->
                            <button onclick="limpiarFiltros()" class="btn btn-outline-secondary w-100">
                                <i class="bi bi-x-circle"></i> Limpiar Filtros
                            </button>
                        </div>
                    </div>

                    <!-- Lista de Productos -->
                    <div class="col-lg-9">

                        <!-- Header -->
                        <div class="d-flex justify-content-between align-items-center mb-4" data-aos="fade-left">
                            <h4 id="tituloSeccion">Todos los Productos</h4>
                            <span class="badge bg-primary fs-6" id="contadorProductos"><?php echo count($productos); ?>
                                producto(s)</span>
                        </div>

                        <!-- Estado Vacío -->
                        <div id="estadoVacio" class="text-center py-5" style="display: none;">
                            <div class="empty-state"
                                style="background: var(--surface-color); border-radius: 20px; padding: 60px 20px;">
                                <i class="bi bi-inbox" style="font-size: 5rem; color: #ddd;"></i>
                                <h4 class="mt-3">No se encontraron productos</h4>
                                <p class="text-muted">Intenta con otros filtros o búsqueda</p>
                                <button onclick="limpiarFiltros()" class="btn btn-primary mt-3">
                                    <i class="bi bi-arrow-left"></i> Ver Todos los Productos
                                </button>
                            </div>
                        </div>

                        <!-- Grid de Productos -->
                        <div class="row gy-4" id="gridProductos">
                            <?php foreach ($productos as $producto): ?>
                                <div class="col-lg-4 col-md-6 product-card"
                                    data-producto-id="<?php echo $producto['id']; ?>"
                                    data-categoria="<?php echo $producto['categoria_id']; ?>"
                                    data-nombre="<?php echo strtolower(htmlspecialchars($producto['nombre'])); ?>"
                                    data-precio="<?php echo $producto['precio']; ?>"
                                    data-destacado="<?php echo $producto['destacado']; ?>" data-aos="fade-up"
                                    data-aos-delay="100">
                                    <div class="product-card">
                                        <div class="product-image">
                                            <div>
                                                <?php if ($producto['imagen']): ?>
                                                    <img src="<?php echo UPLOAD_URL . htmlspecialchars($producto['imagen']); ?>"
                                                        alt="<?php echo htmlspecialchars($producto['nombre']); ?>"
                                                        style="width: 100%; height: 100%; object-fit: cover; position: absolute; top: 0; left: 0;">
                                                <?php else: ?>
                                                    <i class="bi bi-box"
                                                        style="font-size: 4rem; color: var(--accent-color);"></i>
                                                <?php endif; ?>
                                            </div>

                                            <?php if ($producto['destacado']): ?>
                                                <span class="product-badge new">
                                                    <i class="bi bi-star-fill"></i>
                                                </span>
                                            <?php elseif ($producto['stock'] < 20): ?>
                                                <span class="product-badge sale">
                                                    ¡Últimas unidades!
                                                </span>
                                            <?php endif; ?>

                                            <div class="product-overlay"></div>
                                            <div class="product-actions">
                                                <button class="action-btn" title="Ver detalles">
                                                    <i class="bi bi-eye"></i>
                                                </button>
                                                <button class="action-btn" title="Agregar a favoritos">
                                                    <i class="bi bi-heart"></i>
                                                </button>
                                            </div>
                                        </div>

                                        <div class="product-details">
                                            <div class="product-category">
                                                <?php echo htmlspecialchars($producto['categoria_nombre'] ?? 'Sin categoría'); ?>
                                            </div>
                                            <h5 class="product-title">
                                                <a href="#"><?php echo htmlspecialchars($producto['nombre']); ?></a>
                                            </h5>
                                            <div class="product-meta">
                                                <div class="product-price">
                                                    $<?php echo number_format($producto['precio'], 0); ?>
                                                </div>
                                                <div class="product-rating">
                                                    <i class="bi bi-star-fill"></i>
                                                    <span>(<?php echo $producto['stock']; ?>)</span>
                                                </div>
                                            </div>

                                            <?php if ($producto['stock'] > 0): ?>
                                                <button class="btn btn-primary w-100 mt-3"
                                                    onclick="agregarAlCarrito(<?php echo $producto['id']; ?>, '<?php echo htmlspecialchars($producto['nombre']); ?>')">
                                                    <i class="bi bi-cart-plus"></i> Agregar al Carrito
                                                </button>
                                            <?php else: ?>
                                                <button class="btn btn-secondary w-100 mt-3" disabled>
                                                    <i class="bi bi-x-circle"></i> Agotado
                                                </button>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
        </section><!-- /Category Product List Section -->

    </main>

    <?php include 'includes/footer.php'; ?>

    <!-- Scroll Top -->
    <a href="#" id="scroll-top" class="scroll-top d-flex align-items-center justify-content-center">
        <i class="bi bi-arrow-up-short"></i>
    </a>

    <!-- Preloader -->
    <div id="preloader"></div>

    <!-- Vendor JS Files -->
    <script src="assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="assets/vendor/aos/aos.js"></script>
    <script src="assets/vendor/swiper/swiper-bundle.min.js"></script>

    <!-- Main JS File -->
    <script src="assets/js/main.js"></script>

    <script>
        // Variables globales para filtros
        let categoriaActual = '';
        let busquedaActual = '';
        let ordenActual = 'nombre-asc';

        // Agregar al carrito
        function agregarAlCarrito(productoId, productoNombre) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = 'carrito.php';

            const inputAccion = document.createElement('input');
            inputAccion.type = 'hidden';
            inputAccion.name = 'accion';
            inputAccion.value = 'agregar';

            const inputProducto = document.createElement('input');
            inputProducto.type = 'hidden';
            inputProducto.name = 'producto_id';
            inputProducto.value = productoId;

            const inputCantidad = document.createElement('input');
            inputCantidad.type = 'hidden';
            inputCantidad.name = 'cantidad';
            inputCantidad.value = '1';

            form.appendChild(inputAccion);
            form.appendChild(inputProducto);
            form.appendChild(inputCantidad);

            document.body.appendChild(form);
            form.submit();
        }

        // Filtrar por categoría
        function filtrarPorCategoria(categoriaId) {
            categoriaActual = categoriaId;

            // Actualizar clases activas
            document.querySelectorAll('.list-group-item').forEach(item => {
                item.classList.remove('active');
            });

            if (categoriaId === '') {
                document.getElementById('cat-todas').classList.add('active');
            } else {
                document.getElementById('cat-' + categoriaId).classList.add('active');
            }

            filtrarProductos();
        }

        // Filtrar productos - CORREGIDO COMPLETAMENTE
        function filtrarProductos() {
            busquedaActual = document.getElementById('searchInput').value.toLowerCase().trim();

            // Seleccionar SOLO los divs col-* que tienen data-producto-id
            const productos = document.querySelectorAll('#gridProductos > div[data-producto-id]');
            let visibles = 0;

            console.log('Total productos encontrados:', productos.length); // Debug

            productos.forEach(producto => {
                const categoria = producto.getAttribute('data-categoria');
                const nombre = producto.getAttribute('data-nombre');

                let mostrar = true;

                // Filtro de categoría
                if (categoriaActual !== '' && categoria !== categoriaActual) {
                    mostrar = false;
                }

                // Filtro de búsqueda
                if (busquedaActual !== '' && !nombre.includes(busquedaActual)) {
                    mostrar = false;
                }

                // Mostrar u ocultar
                if (mostrar) {
                    producto.style.display = '';
                    visibles++;
                    console.log('Mostrando:', nombre); // Debug
                } else {
                    producto.style.display = 'none';
                    console.log('Ocultando:', nombre); // Debug
                }
            });

            console.log('Productos visibles:', visibles); // Debug

            // Actualizar contador y estado vacío
            document.getElementById('contadorProductos').textContent = visibles + ' producto(s)';

            if (visibles === 0) {
                document.getElementById('estadoVacio').style.display = 'block';
                document.getElementById('gridProductos').style.display = 'none';
            } else {
                document.getElementById('estadoVacio').style.display = 'none';
                document.getElementById('gridProductos').style.display = '';
            }

            // Actualizar título
            actualizarTitulo();
        }

        // Ordenar productos - CORREGIDO
        function ordenarProductos() {
            ordenActual = document.getElementById('ordenSelect').value;

            const grid = document.getElementById('gridProductos');
            const productos = Array.from(grid.querySelectorAll('div[data-producto-id]'));

            productos.sort((a, b) => {
                const nombreA = a.getAttribute('data-nombre');
                const nombreB = b.getAttribute('data-nombre');
                const precioA = parseFloat(a.getAttribute('data-precio'));
                const precioB = parseFloat(a.getAttribute('data-precio'));
                const destacadoA = parseInt(a.getAttribute('data-destacado'));
                const destacadoB = parseInt(b.getAttribute('data-destacado'));

                switch (ordenActual) {
                    case 'nombre-asc':
                        return nombreA.localeCompare(nombreB);
                    case 'nombre-desc':
                        return nombreB.localeCompare(nombreA);
                    case 'precio-asc':
                        return precioA - precioB;
                    case 'precio-desc':
                        return precioB - precioA;
                    case 'destacado':
                        return destacadoB - destacadoA;
                    default:
                        return 0;
                }
            });

            // Reordenar en el DOM
            productos.forEach(producto => grid.appendChild(producto));

            // Re-aplicar filtros después de ordenar
            filtrarProductos();
        }

        // Limpiar filtros
        function limpiarFiltros() {
            categoriaActual = '';
            busquedaActual = '';
            document.getElementById('searchInput').value = '';
            document.getElementById('ordenSelect').value = 'nombre-asc';

            // Resetear categoría activa
            document.querySelectorAll('.list-group-item').forEach(item => {
                item.classList.remove('active');
            });
            document.getElementById('cat-todas').classList.add('active');

            filtrarProductos();
        }

        // Actualizar título de sección
        function actualizarTitulo() {
            let titulo = 'Todos los Productos';

            if (busquedaActual !== '') {
                titulo = 'Resultados para "' + document.getElementById('searchInput').value + '"';
            } else if (categoriaActual !== '') {
                const catElement = document.getElementById('cat-' + categoriaActual);
                if (catElement) {
                    const textoCompleto = catElement.textContent.trim();
                    // Remover el icono del inicio
                    titulo = textoCompleto.replace(/^\s*\S+\s*/, '').trim();
                }
            }

            document.getElementById('tituloSeccion').textContent = titulo;
        }

        // Permitir buscar con Enter
        document.getElementById('searchInput').addEventListener('keypress', function (e) {
            if (e.key === 'Enter') {
                filtrarProductos();
            }
        });

        // Inicializar - mostrar todos al cargar
        document.addEventListener('DOMContentLoaded', function () {
            console.log('Página cargada, inicializando filtros...');
            filtrarProductos();
        });
    </script>

</body>

</html>