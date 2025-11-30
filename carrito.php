<?php
/**
 * CARRITO DE COMPRAS
 * Desechables Punto Fijo
 */

require_once 'config/config.php';
require_once 'config/upload_config.php';

// Si no está logueado, redirigir al login
if (!estaLogueado()) {
    redirect('login.php');
}

$db = getDB();
$mensaje = '';
$tipo_mensaje = '';

// PROCESAR ACCIONES DEL CARRITO
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $accion = $_POST['accion'] ?? '';

    // AGREGAR AL CARRITO
    // AGREGAR AL CARRITO
    if ($accion === 'agregar') {
        $producto_id = (int) $_POST['producto_id'];
        $variante_id = isset($_POST['variante_id']) ? (int) $_POST['variante_id'] : null;
        $cantidad = (int) ($_POST['cantidad'] ?? 1);
        $usuario_id = $_SESSION['usuario_id'];

        // Verificar si el producto tiene variantes
        $stmt = $db->prepare("SELECT tiene_variantes FROM productos WHERE id = ?");
        $stmt->bind_param("i", $producto_id);
        $stmt->execute();
        $producto_info = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if ($producto_info['tiene_variantes'] == 1 && $variante_id) {
            // Verificar stock de la variante
            $stmt = $db->prepare("SELECT stock, nombre_variante FROM producto_variantes WHERE id = ? AND producto_id = ?");
            $stmt->bind_param("ii", $variante_id, $producto_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $variante = $result->fetch_assoc();
            $stmt->close();

            if ($variante && $variante['stock'] >= $cantidad) {
                // Verificar si ya está en el carrito
                $stmt = $db->prepare("SELECT id, cantidad FROM carrito WHERE usuario_id = ? AND producto_id = ? AND variante_id = ?");
                $stmt->bind_param("iii", $usuario_id, $producto_id, $variante_id);
                $stmt->execute();
                $result = $stmt->get_result();

                if ($result->num_rows > 0) {
                    // Actualizar cantidad
                    $carrito_item = $result->fetch_assoc();
                    $nueva_cantidad = $carrito_item['cantidad'] + $cantidad;

                    if ($nueva_cantidad <= $variante['stock']) {
                        $stmt = $db->prepare("UPDATE carrito SET cantidad = ? WHERE id = ?");
                        $stmt->bind_param("ii", $nueva_cantidad, $carrito_item['id']);
                        $stmt->execute();
                        $mensaje = "Cantidad actualizada en el carrito";
                        $tipo_mensaje = "success";
                    } else {
                        $mensaje = "No hay suficiente stock disponible";
                        $tipo_mensaje = "warning";
                    }
                } else {
                    // Insertar nuevo
                    $stmt = $db->prepare("INSERT INTO carrito (usuario_id, producto_id, variante_id, cantidad) VALUES (?, ?, ?, ?)");
                    $stmt->bind_param("iiii", $usuario_id, $producto_id, $variante_id, $cantidad);
                    $stmt->execute();
                    $mensaje = "Producto agregado al carrito";
                    $tipo_mensaje = "success";
                }
                $stmt->close();
            } else {
                $mensaje = "Stock insuficiente para esta variante";
                $tipo_mensaje = "danger";
            }
        } else {
            // Producto sin variantes (código original)
            $stmt = $db->prepare("SELECT stock, nombre FROM productos WHERE id = ?");
            $stmt->bind_param("i", $producto_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $producto = $result->fetch_assoc();

            if ($producto && $producto['stock'] >= $cantidad) {
                // Verificar si ya está en el carrito
                $stmt = $db->prepare("SELECT id, cantidad FROM carrito WHERE usuario_id = ? AND producto_id = ? AND variante_id IS NULL");
                $stmt->bind_param("ii", $usuario_id, $producto_id);
                $stmt->execute();
                $result = $stmt->get_result();

                if ($result->num_rows > 0) {
                    // Actualizar cantidad
                    $carrito_item = $result->fetch_assoc();
                    $nueva_cantidad = $carrito_item['cantidad'] + $cantidad;

                    if ($nueva_cantidad <= $producto['stock']) {
                        $stmt = $db->prepare("UPDATE carrito SET cantidad = ? WHERE id = ?");
                        $stmt->bind_param("ii", $nueva_cantidad, $carrito_item['id']);
                        $stmt->execute();
                        $mensaje = "Cantidad actualizada en el carrito";
                        $tipo_mensaje = "success";
                    } else {
                        $mensaje = "No hay suficiente stock disponible";
                        $tipo_mensaje = "warning";
                    }
                } else {
                    // Insertar nuevo
                    $stmt = $db->prepare("INSERT INTO carrito (usuario_id, producto_id, cantidad) VALUES (?, ?, ?)");
                    $stmt->bind_param("iii", $usuario_id, $producto_id, $cantidad);
                    $stmt->execute();
                    $mensaje = "Producto agregado al carrito";
                    $tipo_mensaje = "success";
                }
            } else {
                $mensaje = "Stock insuficiente";
                $tipo_mensaje = "danger";
            }
            $stmt->close();
        }
    }

    // ACTUALIZAR CANTIDAD
    // ACTUALIZAR CANTIDAD
    elseif ($accion === 'actualizar') {
        $carrito_id = (int) $_POST['carrito_id'];
        $cantidad = (int) $_POST['cantidad'];

        if ($cantidad > 0) {
            // Verificar si tiene variante
            $stmt = $db->prepare("SELECT c.variante_id, p.tiene_variantes FROM carrito c
                              INNER JOIN productos p ON c.producto_id = p.id
                              WHERE c.id = ? AND c.usuario_id = ?");
            $stmt->bind_param("ii", $carrito_id, $_SESSION['usuario_id']);
            $stmt->execute();
            $carrito_info = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            if ($carrito_info['tiene_variantes'] == 1 && $carrito_info['variante_id']) {
                // Verificar stock de variante
                $stmt = $db->prepare("SELECT pv.stock FROM carrito c
                                  INNER JOIN producto_variantes pv ON c.variante_id = pv.id
                                  WHERE c.id = ? AND c.usuario_id = ?");
                $stmt->bind_param("ii", $carrito_id, $_SESSION['usuario_id']);
                $stmt->execute();
                $result = $stmt->get_result();
                $item = $result->fetch_assoc();
                $stmt->close();
            } else {
                // Verificar stock del producto base
                $stmt = $db->prepare("SELECT p.stock FROM carrito c 
                                  INNER JOIN productos p ON c.producto_id = p.id 
                                  WHERE c.id = ? AND c.usuario_id = ?");
                $stmt->bind_param("ii", $carrito_id, $_SESSION['usuario_id']);
                $stmt->execute();
                $result = $stmt->get_result();
                $item = $result->fetch_assoc();
                $stmt->close();
            }

            if ($item && $cantidad <= $item['stock']) {
                $stmt = $db->prepare("UPDATE carrito SET cantidad = ? WHERE id = ? AND usuario_id = ?");
                $stmt->bind_param("iii", $cantidad, $carrito_id, $_SESSION['usuario_id']);
                $stmt->execute();
                $mensaje = "Cantidad actualizada";
                $tipo_mensaje = "success";
                $stmt->close();
            } else {
                $mensaje = "Cantidad no disponible en stock";
                $tipo_mensaje = "warning";
            }
        }
    }

    // ELIMINAR DEL CARRITO
    elseif ($accion === 'eliminar') {
        $carrito_id = (int) $_POST['carrito_id'];
        $stmt = $db->prepare("DELETE FROM carrito WHERE id = ? AND usuario_id = ?");
        $stmt->bind_param("ii", $carrito_id, $_SESSION['usuario_id']);
        $stmt->execute();
        $stmt->close();
        $mensaje = "Producto eliminado del carrito";
        $tipo_mensaje = "info";
    }

    // VACIAR CARRITO
    elseif ($accion === 'vaciar') {
        $stmt = $db->prepare("DELETE FROM carrito WHERE usuario_id = ?");
        $stmt->bind_param("i", $_SESSION['usuario_id']);
        $stmt->execute();
        $stmt->close();
        $mensaje = "Carrito vaciado";
        $tipo_mensaje = "info";
    }
}

// OBTENER ITEMS DEL CARRITO
$usuario_id = $_SESSION['usuario_id'];
$sql = "SELECT c.id as carrito_id, c.cantidad, c.fecha_agregado, c.variante_id,
        p.id as producto_id, p.nombre, p.precio, p.stock, p.unidad_medida, p.imagen, p.tiene_variantes,
        cat.nombre as categoria_nombre,
        pv.nombre_variante, pv.precio as variante_precio, pv.stock as variante_stock, pv.sku,
        CASE 
            WHEN c.variante_id IS NOT NULL THEN pv.precio
            ELSE p.precio
        END as precio_final,
        CASE 
            WHEN c.variante_id IS NOT NULL THEN pv.stock
            ELSE p.stock
        END as stock_final,
        CASE 
            WHEN c.variante_id IS NOT NULL THEN (pv.precio * c.cantidad)
            ELSE (p.precio * c.cantidad)
        END as subtotal
        FROM carrito c
        INNER JOIN productos p ON c.producto_id = p.id
        LEFT JOIN categorias cat ON p.categoria_id = cat.id
        LEFT JOIN producto_variantes pv ON c.variante_id = pv.id
        WHERE c.usuario_id = ?
        ORDER BY c.fecha_agregado DESC";


$stmt = $db->prepare($sql);
$stmt->bind_param("i", $usuario_id);
$stmt->execute();
$result = $stmt->get_result();
$items_carrito = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// CALCULAR TOTALES
$subtotal = 0;
foreach ($items_carrito as $item) {
    $subtotal += $item['subtotal'];
}
$impuesto = 0; // Si quieres agregar IVA: $subtotal * 0.19
$envio = $subtotal >= 50000 ? 0 : 5000;
$total = $subtotal + $impuesto + $envio;
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Carrito de Compras - Desechables Punto Fijo</title>

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

    <!-- Main CSS File -->
    <link href="assets/css/main.css" rel="stylesheet">
</head>

<body class="cart-page">
    <header id="header" class="header sticky-top">
        <!-- Top Bar -->
        <div class="top-bar py-2">
            <div class="container-fluid container-xl">
                <div class="row align-items-center">
                    <div class="col-lg-4 d-none d-lg-flex">
                        <div class="top-bar-item">
                            <i class="bi bi-telephone-fill me-2"></i>
                            <span>¿Necesitas ayuda? Llámanos: </span>
                            <a href="tel:+573177268740">317 726 8740</a>
                        </div>
                    </div>

                    <div class="col-lg-4 col-md-12 text-center">
                        <div class="announcement-slider">
                            <div class="swiper-wrapper">
                                <div class="swiper-slide">🚚 Envío gratis en pedidos mayores a $50.000</div>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-4 d-none d-lg-block">
                        <div class="d-flex justify-content-end">
                            <div class="top-bar-item">
                                <i class="bi bi-currency-dollar me-2"></i>COP
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Main Header -->
        <div class="main-header">
            <div class="container-fluid container-xl">
                <div class="d-flex py-3 align-items-center justify-content-between">
                    <!-- Logo -->
                    <a href="index.php" class="logo d-flex align-items-center">
                        <h1 class="sitename">Desechables Punto Fijo</h1>
                    </a>



                    <!-- Actions -->
                    <div class="header-actions d-flex align-items-center justify-content-end">
                        <!-- Account -->
                        <div class="dropdown account-dropdown">
                            <button class="header-action-btn" data-bs-toggle="dropdown">
                                <i class="bi bi-person"></i>
                            </button>
                            <div class="dropdown-menu">
                                <div class="dropdown-header">
                                    <h6>Bienvenido</h6>
                                    <p class="mb-0"><?php echo $_SESSION['nombre']; ?></p>
                                </div>
                                <div class="dropdown-body">
                                    <a class="dropdown-item d-flex align-items-center" href="#">
                                        <i class="bi bi-person-circle me-2"></i>
                                        <span>Mi Perfil</span>
                                    </a>
                                    <a class="dropdown-item d-flex align-items-center" href="#">
                                        <i class="bi bi-bag-check me-2"></i>
                                        <span>Mis Pedidos</span>
                                    </a>
                                    <?php if (esAdmin()): ?>
                                        <a class="dropdown-item d-flex align-items-center" href="admin.php">
                                            <i class="bi bi-gear me-2"></i>
                                            <span>Admin</span>
                                        </a>
                                    <?php endif; ?>
                                </div>
                                <div class="dropdown-footer">
                                    <a href="logout.php" class="btn btn-outline-primary w-100">Cerrar Sesión</a>
                                </div>
                            </div>
                        </div>

                        <!-- Cart -->
                        <a href="carrito.php" class="header-action-btn">
                            <i class="bi bi-cart3"></i>
                            <span class="badge"><?php echo count($items_carrito); ?></span>
                        </a>

                        <!-- Mobile Navigation Toggle -->
                        <i class="mobile-nav-toggle d-xl-none bi bi-list me-0"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Navigation -->
        <div class="header-nav">
            <div class="container-fluid container-xl position-relative">
                <nav id="navmenu" class="navmenu">
                    <ul>
                        <li><a href="index.php">Inicio</a></li>
                        <li><a href="productos.php">Productos</a></li>
                        <li><a href="#">Mis Pedidos</a></li>
                        <?php if (esAdmin()): ?>
                            <li><a href="admin.php">Admin</a></li>
                        <?php endif; ?>
                    </ul>
                </nav>
            </div>
        </div>
    </header>

    <main class="main">
        <!-- Page Title -->
        <div class="page-title light-background">
            <div class="container d-lg-flex justify-content-between align-items-center">
                <h1 class="mb-2 mb-lg-0">Carrito de Compras</h1>
                <nav class="breadcrumbs">
                    <ol>
                        <li><a href="index.php">Inicio</a></li>
                        <li class="current">Carrito</li>
                    </ol>
                </nav>
            </div>
        </div>

        <!-- Cart Section -->
        <section id="cart" class="cart section">
            <div class="container" data-aos="fade-up" data-aos-delay="100">

                <?php if ($mensaje): ?>
                    <div class="alert alert-<?php echo $tipo_mensaje; ?> alert-dismissible fade show" role="alert">
                        <i class="bi bi-<?php echo $tipo_mensaje === 'success' ? 'check-circle' : 'info-circle'; ?>"></i>
                        <?php echo $mensaje; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <?php if (empty($items_carrito)): ?>
                    <!-- Empty Cart -->
                    <div class="text-center py-5">
                        <i class="bi bi-cart-x" style="font-size: 6rem; color: #ddd;"></i>
                        <h3 class="mt-4">Tu carrito está vacío</h3>
                        <p class="text-muted">¡Agrega productos para comenzar tu compra!</p>
                        <a href="productos.php" class="btn btn-accent mt-3">
                            <i class="bi bi-shop"></i> Ir a Comprar
                        </a>
                    </div>
                <?php else: ?>
                    <div class="row">
                        <div class="col-lg-8" data-aos="fade-up" data-aos-delay="200">
                            <div class="cart-items">
                                <div class="cart-header d-none d-lg-block">
                                    <div class="row align-items-center">
                                        <div class="col-lg-6">
                                            <h5>Producto</h5>
                                        </div>
                                        <div class="col-lg-2 text-center">
                                            <h5>Precio</h5>
                                        </div>
                                        <div class="col-lg-2 text-center">
                                            <h5>Cantidad</h5>
                                        </div>
                                        <div class="col-lg-2 text-center">
                                            <h5>Total</h5>
                                        </div>
                                    </div>
                                </div>

                                <?php foreach ($items_carrito as $item): ?>
                                    <!-- Cart Item -->
                                    <div class="cart-item">
                                        <div class="row align-items-center">
                                            <div class="col-lg-6 col-12 mt-3 mt-lg-0 mb-lg-0 mb-3">
                                                <div class="product-info d-flex align-items-center">
                                                    <div class="product-image">
                                                        <?php if ($item['imagen']): ?>
                                                            <img src="<?php echo UPLOAD_URL . htmlspecialchars($item['imagen']); ?>"
                                                                alt="<?php echo htmlspecialchars($item['nombre']); ?>"
                                                                style="width: 100px; height: 100px; object-fit: cover; border-radius: 10px;">
                                                        <?php else: ?>
                                                            <div
                                                                style="width: 100px; height: 100px; background: linear-gradient(135deg, #667eea22 0%, #764ba222 100%); border-radius: 10px; display: flex; align-items: center; justify-content: center;">
                                                                <i class="bi bi-box" style="font-size: 2.5rem; color: #667eea;"></i>
                                                            </div>
                                                        <?php endif; ?>
                                                    </div>
                                                    <div class="product-details">
                                                        <h6 class="product-title">
                                                            <?php echo htmlspecialchars($item['nombre']); ?>
                                                            <?php if ($item['variante_id']): ?>
                                                                <br><small class="text-muted">
                                                                    <i class="bi bi-collection"></i>
                                                                    <?php echo htmlspecialchars($item['nombre_variante']); ?>
                                                                </small>
                                                            <?php endif; ?>
                                                        </h6>
                                                        <div class="product-meta">
                                                            <span class="product-color">Categoría:
                                                                <?php echo htmlspecialchars($item['categoria_nombre']); ?></span>
                                                            <span class="product-size">Stock:
                                                                <?php echo $item['stock_final']; ?></span>
                                                            <?php if ($item['variante_id'] && $item['sku']): ?>
                                                                <span class="product-size">SKU:
                                                                    <?php echo htmlspecialchars($item['sku']); ?></span>
                                                            <?php endif; ?>
                                                        </div>
                                                        <form method="POST" style="display: inline;">
                                                            <input type="hidden" name="accion" value="eliminar">
                                                            <input type="hidden" name="carrito_id"
                                                                value="<?php echo $item['carrito_id']; ?>">
                                                            <button class="remove-item" type="submit">
                                                                <i class="bi bi-trash"></i> Eliminar
                                                            </button>
                                                        </form>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-lg-2 col-12 mt-3 mt-lg-0 text-center">
                                                <div class="price-tag">
                                                    <span
                                                        class="current-price">$<?php echo number_format($item['precio_final'], 0); ?></span>
                                                </div>
                                            </div>
                                            <div class="col-lg-2 col-12 mt-3 mt-lg-0 text-center">
                                                <form method="POST" class="quantity-form d-inline-block">
                                                    <input type="hidden" name="accion" value="actualizar">
                                                    <input type="hidden" name="carrito_id"
                                                        value="<?php echo $item['carrito_id']; ?>">
                                                    <div class="quantity-selector">
                                                        <button class="quantity-btn decrease" type="button"
                                                            onclick="cambiarCantidad(this, -1, <?php echo $item['stock_final']; ?>)">
                                                            <i class="bi bi-dash"></i>
                                                        </button>
                                                        <input type="number" name="cantidad" class="quantity-input"
                                                            value="<?php echo $item['cantidad']; ?>" min="1"
                                                            max="<?php echo $item['stock_final']; ?>">
                                                        <button class="quantity-btn increase" type="button"
                                                            onclick="cambiarCantidad(this, 1, <?php echo $item['stock_final']; ?>)">
                                                            <i class="bi bi-plus"></i>
                                                        </button>
                                                    </div>
                                                    <button type="submit" class="btn btn-sm btn-outline-primary mt-2 w-100">
                                                        <i class="bi bi-check"></i> Actualizar
                                                    </button>
                                                </form>
                                            </div>
                                            <div class="col-lg-2 col-12 mt-3 mt-lg-0 text-center">
                                                <div class="item-total">
                                                    <span>$<?php echo number_format($item['subtotal'], 0); ?></span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>

                                <div class="cart-actions">
                                    <div class="row">
                                        <div class="col-lg-6 mb-3 mb-lg-0">
                                            <a href="productos.php" class="btn btn-outline-heading">
                                                <i class="bi bi-arrow-left"></i> Seguir Comprando
                                            </a>
                                        </div>
                                        <div class="col-lg-6 text-md-end">
                                            <form method="POST" style="display: inline;">
                                                <input type="hidden" name="accion" value="vaciar">
                                                <button type="submit" class="btn btn-outline-remove"
                                                    onclick="return confirm('¿Estás seguro de vaciar el carrito?')">
                                                    <i class="bi bi-trash"></i> Vaciar Carrito
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-lg-4 mt-4 mt-lg-0" data-aos="fade-up" data-aos-delay="300">
                            <div class="cart-summary">
                                <h4 class="summary-title">Resumen del Pedido</h4>

                                <div class="summary-item">
                                    <span class="summary-label">Subtotal (<?php echo count($items_carrito); ?>
                                        productos)</span>
                                    <span class="summary-value">$<?php echo number_format($subtotal, 0); ?></span>
                                </div>

                                <?php if ($impuesto > 0): ?>
                                    <div class="summary-item">
                                        <span class="summary-label">Impuestos</span>
                                        <span class="summary-value">$<?php echo number_format($impuesto, 0); ?></span>
                                    </div>
                                <?php endif; ?>

                                <div class="summary-item">
                                    <span class="summary-label">Envío</span>
                                    <span class="summary-value <?php echo $envio == 0 ? 'text-success' : ''; ?>">
                                        <?php echo $envio == 0 ? 'GRATIS' : '$' . number_format($envio, 0); ?>
                                    </span>
                                </div>

                                <?php if ($subtotal < 50000 && $subtotal > 0): ?>
                                    <div class="alert alert-info p-2 mt-2">
                                        <small><i class="bi bi-info-circle"></i> Agrega
                                            $<?php echo number_format(50000 - $subtotal, 0); ?> más para envío gratis</small>
                                    </div>
                                <?php endif; ?>

                                <div class="summary-total">
                                    <span class="summary-label">Total</span>
                                    <span class="summary-value">$<?php echo number_format($total, 0); ?></span>
                                </div>

                                <div class="checkout-button">
                                    <a href="checkout.php" class="btn btn-accent w-100">
                                        Proceder al Pago <i class="bi bi-arrow-right"></i>
                                    </a>
                                </div>

                                <div class="continue-shopping">
                                    <a href="index.php" class="btn btn-link w-100">
                                        <i class="bi bi-arrow-left"></i> Continuar Comprando
                                    </a>
                                </div>

                                <div class="payment-methods">
                                    <p class="payment-title">Aceptamos</p>
                                    <div class="payment-icons">
                                        <i class="bi bi-cash-coin"></i>
                                        <i class="bi bi-bank"></i>
                                        <i class="bi bi-phone"></i>
                                        <i class="bi bi-wallet2"></i>
                                    </div>
                                </div>

                                <div class="alert alert-success mt-3 mb-0">
                                    <small>
                                        <i class="bi bi-shield-check"></i>
                                        Compra 100% segura
                                    </small>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </section>
    </main>

    <footer id="footer" class="footer dark-background">
        <div class="footer-bottom">
            <div class="container">
                <div class="row gy-3 align-items-center">
                    <div class="col-lg-12 text-center">
                        <div class="copyright">
                            <p>&copy; <span>Copyright</span> <strong class="sitename">Desechables Punto Fijo</strong>.
                                Todos los derechos reservados.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </footer>

    <!-- Scroll Top -->
    <a href="#" id="scroll-top" class="scroll-top d-flex align-items-center justify-content-center">
        <i class="bi bi-arrow-up-short"></i>
    </a>

    <!-- Vendor JS Files -->
    <script src="assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="assets/vendor/aos/aos.js"></script>

    <!-- Main JS File -->
    <script src="assets/js/main.js"></script>

    <script>
        function cambiarCantidad(btn, cambio, maxStock) {
            const input = btn.parentElement.querySelector('input[type="number"]');
            let currentValue = parseInt(input.value);
            let newValue = currentValue + cambio;

            if (newValue >= 1 && newValue <= maxStock) {
                input.value = newValue;
            } else if (newValue > maxStock) {
                alert('No hay más stock disponible');
            }
        }

        // Initialize AOS
        AOS.init({
            duration: 1000,
            easing: 'ease-in-out',
            once: true,
            mirror: false
        });
    </script>
</body>

</html>