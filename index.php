<?php
/**
 * CATÁLOGO PÚBLICO DE PRODUCTOS
 * Desechables Punto Fijo
 */

require_once 'config.php';

// Si no está logueado, redirigir al login
if (!estaLogueado()) {
    redirect('login.php');
}

// Si es admin y viene del login, redirigir a admin.php
// (excepto si viene específicamente a ver la tienda)
if (esAdmin() && !isset($_GET['ver_tienda']) && !isset($_SERVER['HTTP_REFERER'])) {
    // Solo redirigir si no hay parámetros de búsqueda/filtros
    if (empty($_GET)) {
        redirect('admin.php');
    }
}

$db = getDB();

// FILTROS Y BÚSQUEDA
$busqueda = $_GET['buscar'] ?? '';
$categoria_filtro = $_GET['categoria'] ?? '';
$orden = $_GET['orden'] ?? 'nombre ASC';
$destacados = isset($_GET['destacados']);

// OBTENER PRODUCTOS
$sql = "SELECT p.*, c.nombre as categoria_nombre FROM productos p 
        LEFT JOIN categorias c ON p.categoria_id = c.id 
        WHERE p.estado = 'disponible'";

if (!empty($busqueda)) {
    $sql .= " AND (p.nombre LIKE '%" . $db->real_escape_string($busqueda) . "%' 
              OR p.descripcion LIKE '%" . $db->real_escape_string($busqueda) . "%')";
}

if (!empty($categoria_filtro)) {
    $sql .= " AND p.categoria_id = " . (int)$categoria_filtro;
}

if ($destacados) {
    $sql .= " AND p.destacado = 1";
}

$sql .= " ORDER BY " . $db->real_escape_string($orden);

$result = $db->query($sql);
$productos = $result->fetch_all(MYSQLI_ASSOC);

// OBTENER CATEGORÍAS
$categorias = $db->query("SELECT * FROM categorias WHERE estado = 'activo' ORDER BY nombre")->fetch_all(MYSQLI_ASSOC);

// OBTENER PRODUCTOS DESTACADOS
$destacados_query = $db->query("SELECT * FROM productos WHERE destacado = 1 AND estado = 'disponible' LIMIT 4");
$productos_destacados = $destacados_query->fetch_all(MYSQLI_ASSOC);

// OBTENER ESTADÍSTICAS
$total_productos = count($productos);

// OBTENER CANTIDAD DE ITEMS EN EL CARRITO
$stmt = $db->prepare("SELECT COUNT(*) as total FROM carrito WHERE usuario_id = ?");
$stmt->bind_param("i", $_SESSION['usuario_id']);
$stmt->execute();
$result = $stmt->get_result();
$carrito_count = $result->fetch_assoc()['total'];
$stmt->close();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Catálogo - Desechables Punto Fijo</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    
    <style>
        :root {
            --primary-color: #667eea;
            --secondary-color: #764ba2;
            --success-color: #10b981;
            --warning-color: #f59e0b;
        }
        
        body {
            background: #f8f9fa;
        }
        
        .navbar {
            background: white !important;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
            padding: 15px 0;
        }
        
        .navbar-brand {
            font-weight: bold;
            font-size: 1.3rem;
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        
        .hero-banner {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            color: white;
            padding: 40px 0;
            margin-bottom: 30px;
            border-radius: 15px;
        }
        
        .search-box {
            max-width: 600px;
            margin: 0 auto;
        }
        
        .search-box input {
            border-radius: 25px;
            padding: 12px 20px;
            border: none;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        .search-box button {
            border-radius: 25px;
            padding: 12px 30px;
            background: white;
            color: var(--primary-color);
            border: none;
            font-weight: bold;
        }
        
        .category-pill {
            display: inline-block;
            padding: 10px 20px;
            background: white;
            border-radius: 25px;
            margin: 5px;
            text-decoration: none;
            color: #333;
            transition: all 0.3s;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
        }
        
        .category-pill:hover,
        .category-pill.active {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.15);
        }
        
        .product-card {
            background: white;
            border-radius: 15px;
            overflow: hidden;
            transition: all 0.3s;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
            height: 100%;
            display: flex;
            flex-direction: column;
        }
        
        .product-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.15);
        }
        
        .product-image {
            height: 200px;
            background: linear-gradient(135deg, #667eea22 0%, #764ba222 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 4rem;
            color: var(--primary-color);
            position: relative;
            overflow: hidden;
        }
        
        .product-badge {
            position: absolute;
            top: 10px;
            right: 10px;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: bold;
        }
        
        .badge-destacado {
            background: linear-gradient(135deg, #f59e0b 0%, #f97316 100%);
            color: white;
        }
        
        .badge-stock-bajo {
            background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
            color: white;
        }
        
        .product-body {
            padding: 20px;
            flex-grow: 1;
            display: flex;
            flex-direction: column;
        }
        
        .product-category {
            color: var(--primary-color);
            font-size: 0.85rem;
            font-weight: 600;
            margin-bottom: 5px;
        }
        
        .product-title {
            font-size: 1.1rem;
            font-weight: bold;
            margin-bottom: 10px;
            color: #333;
            flex-grow: 1;
        }
        
        .product-price {
            font-size: 1.8rem;
            font-weight: bold;
            color: var(--primary-color);
            margin-bottom: 15px;
        }
        
        .product-info {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
            padding-top: 15px;
            border-top: 1px solid #eee;
        }
        
        .btn-add-cart {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            color: white;
            border: none;
            padding: 12px 20px;
            border-radius: 10px;
            font-weight: bold;
            transition: all 0.3s;
            width: 100%;
        }
        
        .btn-add-cart:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
            color: white;
        }
        
        .filter-sidebar {
            background: white;
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
            position: sticky;
            top: 20px;
        }
        
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            background: white;
            border-radius: 15px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
        }
        
        .empty-state i {
            font-size: 5rem;
            color: #ddd;
            margin-bottom: 20px;
        }
        
        .destacados-section {
            background: white;
            border-radius: 15px;
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
        }
        
        .cart-badge {
            position: absolute;
            top: -8px;
            right: -8px;
            background: #ef4444;
            color: white;
            border-radius: 50%;
            width: 20px;
            height: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.75rem;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-light sticky-top">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                <i class="bi bi-shop"></i> Desechables Punto Fijo
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto align-items-center">
                    <li class="nav-item">
                        <a class="nav-link active" href="index.php">
                            <i class="bi bi-grid-fill"></i> Productos
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link position-relative" href="carrito.php">
                            <i class="bi bi-cart-fill"></i> Carrito
                            <?php if ($carrito_count > 0): ?>
                            <span class="cart-badge"><?php echo $carrito_count; ?></span>
                            <?php endif; ?>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#">
                            <i class="bi bi-clock-history"></i> Mis Pedidos
                        </a>
                    </li>
                    <?php if (esAdmin()): ?>
                    <li class="nav-item">
                        <a class="nav-link text-primary" href="admin.php">
                            <i class="bi bi-gear-fill"></i> Admin
                        </a>
                    </li>
                    <?php endif; ?>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown">
                            <i class="bi bi-person-circle"></i> <?php echo $_SESSION['nombre']; ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="#"><i class="bi bi-person"></i> Mi Perfil</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item text-danger" href="logout.php"><i class="bi bi-box-arrow-right"></i> Cerrar Sesión</a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Hero Banner -->
    <div class="container mt-4">
        <?php if (esAdmin()): ?>
        <!-- Banner especial para Admin -->
        <div class="alert alert-info alert-dismissible fade show d-flex align-items-center" role="alert">
            <i class="bi bi-shield-check" style="font-size: 2rem; margin-right: 15px;"></i>
            <div class="flex-grow-1">
                <strong>¡Hola Administrador!</strong> Estás viendo la tienda como cliente.
            </div>
            <a href="admin.php" class="btn btn-primary me-2">
                <i class="bi bi-gear-fill"></i> Ir al Panel Admin
            </a>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>
        
        <div class="hero-banner">
            <div class="container text-center">
                <h1 class="display-5 fw-bold mb-3">
                    <i class="bi bi-shop"></i> Desechables Punto Fijo
                </h1>
                <p class="lead mb-4">Tu tienda de confianza en Barahoja, Aguachica</p>
                
                <!-- Buscador -->
                <form method="GET" class="search-box">
                    <div class="input-group input-group-lg">
                        <input type="text" name="buscar" class="form-control" 
                               placeholder="¿Qué estás buscando?" 
                               value="<?php echo htmlspecialchars($busqueda); ?>">
                        <button class="btn" type="submit">
                            <i class="bi bi-search"></i> Buscar
                        </button>
                    </div>
                </form>

                <!-- Categorías Pills -->
                <div class="mt-4">
                    <a href="index.php" class="category-pill <?php echo empty($categoria_filtro) ? 'active' : ''; ?>">
                        <i class="bi bi-grid"></i> Todas
                    </a>
                    <?php foreach ($categorias as $cat): ?>
                    <a href="?categoria=<?php echo $cat['id']; ?>" 
                       class="category-pill <?php echo $categoria_filtro == $cat['id'] ? 'active' : ''; ?>">
                        <i class="bi bi-tag"></i> <?php echo htmlspecialchars($cat['nombre']); ?>
                    </a>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Productos Destacados -->
    <?php if (!empty($productos_destacados) && empty($busqueda) && empty($categoria_filtro)): ?>
    <div class="container mb-4">
        <div class="destacados-section">
            <h3 class="mb-4">
                <i class="bi bi-star-fill text-warning"></i> Productos Destacados
            </h3>
            <div class="row g-4">
                <?php foreach ($productos_destacados as $producto): ?>
                <div class="col-md-3">
                    <div class="product-card">
                        <div class="product-image">
                            <i class="bi bi-box"></i>
                            <span class="product-badge badge-destacado">
                                <i class="bi bi-star-fill"></i> Destacado
                            </span>
                        </div>
                        <div class="product-body">
                            <div class="product-category"><?php echo htmlspecialchars($producto['categoria_nombre'] ?? 'Sin categoría'); ?></div>
                            <div class="product-title"><?php echo htmlspecialchars($producto['nombre']); ?></div>
                            <div class="product-price">$<?php echo number_format($producto['precio'], 0); ?></div>
                            <button class="btn btn-add-cart" onclick="agregarAlCarrito(<?php echo $producto['id']; ?>)">
                                <i class="bi bi-cart-plus"></i> Agregar al Carrito
                            </button>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Productos -->
    <div class="container mb-5">
        <div class="row">
            <!-- Sidebar Filtros -->
            <div class="col-md-3 mb-4">
                <div class="filter-sidebar">
                    <h5 class="mb-3"><i class="bi bi-funnel"></i> Filtros</h5>
                    
                    <div class="mb-4">
                        <h6>Ordenar por</h6>
                        <form method="GET" id="formOrden">
                            <?php if ($busqueda): ?>
                            <input type="hidden" name="buscar" value="<?php echo htmlspecialchars($busqueda); ?>">
                            <?php endif; ?>
                            <?php if ($categoria_filtro): ?>
                            <input type="hidden" name="categoria" value="<?php echo $categoria_filtro; ?>">
                            <?php endif; ?>
                            <select name="orden" class="form-select" onchange="this.form.submit()">
                                <option value="nombre ASC">Nombre A-Z</option>
                                <option value="nombre DESC">Nombre Z-A</option>
                                <option value="precio ASC">Precio menor</option>
                                <option value="precio DESC">Precio mayor</option>
                                <option value="destacado DESC">Destacados</option>
                            </select>
                        </form>
                    </div>
                    
                    <div class="mb-4">
                        <h6>Categorías</h6>
                        <div class="list-group">
                            <a href="index.php" class="list-group-item list-group-item-action <?php echo empty($categoria_filtro) ? 'active' : ''; ?>">
                                Todas las categorías
                            </a>
                            <?php foreach ($categorias as $cat): ?>
                            <a href="?categoria=<?php echo $cat['id']; ?>" 
                               class="list-group-item list-group-item-action <?php echo $categoria_filtro == $cat['id'] ? 'active' : ''; ?>">
                                <?php echo htmlspecialchars($cat['nombre']); ?>
                            </a>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    
                    <div>
                        <a href="index.php" class="btn btn-outline-secondary w-100">
                            <i class="bi bi-x-circle"></i> Limpiar Filtros
                        </a>
                    </div>
                </div>
            </div>

            <!-- Lista de Productos -->
            <div class="col-md-9">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h4>
                        <?php if ($busqueda): ?>
                            Resultados para "<?php echo htmlspecialchars($busqueda); ?>"
                        <?php elseif ($categoria_filtro): ?>
                            <?php 
                            $cat_actual = array_filter($categorias, function($c) use ($categoria_filtro) { 
                                return $c['id'] == $categoria_filtro; 
                            });
                            $cat_actual = reset($cat_actual);
                            echo htmlspecialchars($cat_actual['nombre']);
                            ?>
                        <?php else: ?>
                            Todos los Productos
                        <?php endif; ?>
                    </h4>
                    <span class="text-muted"><?php echo $total_productos; ?> producto(s)</span>
                </div>

                <?php if (empty($productos)): ?>
                    <div class="empty-state">
                        <i class="bi bi-inbox"></i>
                        <h4>No se encontraron productos</h4>
                        <p class="text-muted">Intenta con otros filtros o búsqueda</p>
                        <a href="index.php" class="btn btn-primary mt-3">
                            <i class="bi bi-arrow-left"></i> Ver Todos los Productos
                        </a>
                    </div>
                <?php else: ?>
                    <div class="row g-4">
                        <?php foreach ($productos as $producto): ?>
                        <div class="col-md-4">
                            <div class="product-card">
                                <div class="product-image">
                                    <i class="bi bi-box"></i>
                                    <?php if ($producto['destacado']): ?>
                                    <span class="product-badge badge-destacado">
                                        <i class="bi bi-star-fill"></i>
                                    </span>
                                    <?php elseif ($producto['stock'] < 20): ?>
                                    <span class="product-badge badge-stock-bajo">
                                        ¡Últimas unidades!
                                    </span>
                                    <?php endif; ?>
                                </div>
                                <div class="product-body">
                                    <div class="product-category">
                                        <?php echo htmlspecialchars($producto['categoria_nombre'] ?? 'Sin categoría'); ?>
                                    </div>
                                    <div class="product-title">
                                        <?php echo htmlspecialchars($producto['nombre']); ?>
                                    </div>
                                    <div class="product-price">
                                        $<?php echo number_format($producto['precio'], 0); ?>
                                    </div>
                                    <div class="product-info">
                                        <small class="text-muted">
                                            <i class="bi bi-box-seam"></i> 
                                            Stock: <?php echo $producto['stock']; ?>
                                        </small>
                                        <small class="text-muted">
                                            <?php echo ucfirst($producto['unidad_medida']); ?>
                                        </small>
                                    </div>
                                    <?php if ($producto['stock'] > 0): ?>
                                    <button class="btn btn-add-cart" onclick="agregarAlCarrito(<?php echo $producto['id']; ?>, '<?php echo htmlspecialchars($producto['nombre']); ?>')">
                                        <i class="bi bi-cart-plus"></i> Agregar al Carrito
                                    </button>
                                    <?php else: ?>
                                    <button class="btn btn-secondary" disabled>
                                        <i class="bi bi-x-circle"></i> Agotado
                                    </button>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="bg-white py-4 mt-5 border-top">
        <div class="container text-center">
            <p class="mb-2">
                <i class="bi bi-geo-alt-fill text-primary"></i> 
                Calle 4ta #6-51, Barrio Barahoja, Aguachica - Cesar
            </p>
            <p class="mb-2">
                <i class="bi bi-telephone-fill text-success"></i> 
                317 726 8740 | 315 744 1535
            </p>
            <p class="text-muted mb-0">
                &copy; 2025 Desechables Punto Fijo. Todos los derechos reservados.
            </p>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function agregarAlCarrito(productoId, productoNombre) {
            // Crear formulario oculto para enviar por POST
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
    </script>
</body>
</html>