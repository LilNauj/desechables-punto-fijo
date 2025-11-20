<?php
/**
 * CARRITO DE COMPRAS
 * Desechables Punto Fijo
 */

require_once 'config.php';

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
    if ($accion === 'agregar') {
        $producto_id = (int)$_POST['producto_id'];
        $cantidad = (int)($_POST['cantidad'] ?? 1);
        $usuario_id = $_SESSION['usuario_id'];
        
        // Verificar stock disponible
        $stmt = $db->prepare("SELECT stock, nombre FROM productos WHERE id = ?");
        $stmt->bind_param("i", $producto_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $producto = $result->fetch_assoc();
        
        if ($producto && $producto['stock'] >= $cantidad) {
            // Verificar si ya está en el carrito
            $stmt = $db->prepare("SELECT id, cantidad FROM carrito WHERE usuario_id = ? AND producto_id = ?");
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
    
    // ACTUALIZAR CANTIDAD
    elseif ($accion === 'actualizar') {
        $carrito_id = (int)$_POST['carrito_id'];
        $cantidad = (int)$_POST['cantidad'];
        
        if ($cantidad > 0) {
            // Verificar stock
            $stmt = $db->prepare("SELECT p.stock FROM carrito c 
                                  INNER JOIN productos p ON c.producto_id = p.id 
                                  WHERE c.id = ? AND c.usuario_id = ?");
            $stmt->bind_param("ii", $carrito_id, $_SESSION['usuario_id']);
            $stmt->execute();
            $result = $stmt->get_result();
            $item = $result->fetch_assoc();
            
            if ($item && $cantidad <= $item['stock']) {
                $stmt = $db->prepare("UPDATE carrito SET cantidad = ? WHERE id = ? AND usuario_id = ?");
                $stmt->bind_param("iii", $cantidad, $carrito_id, $_SESSION['usuario_id']);
                $stmt->execute();
                $mensaje = "Cantidad actualizada";
                $tipo_mensaje = "success";
            } else {
                $mensaje = "Cantidad no disponible en stock";
                $tipo_mensaje = "warning";
            }
            $stmt->close();
        }
    }
    
    // ELIMINAR DEL CARRITO
    elseif ($accion === 'eliminar') {
        $carrito_id = (int)$_POST['carrito_id'];
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
$sql = "SELECT c.id as carrito_id, c.cantidad, c.fecha_agregado,
        p.id as producto_id, p.nombre, p.precio, p.stock, p.unidad_medida,
        cat.nombre as categoria_nombre,
        (p.precio * c.cantidad) as subtotal
        FROM carrito c
        INNER JOIN productos p ON c.producto_id = p.id
        LEFT JOIN categorias cat ON p.categoria_id = cat.id
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
$total = $subtotal + $impuesto;
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Carrito de Compras - Desechables Punto Fijo</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    
    <style>
        :root {
            --primary-color: #667eea;
            --secondary-color: #764ba2;
        }
        
        body {
            background: #f8f9fa;
        }
        
        .navbar {
            background: white !important;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
        }
        
        .navbar-brand {
            font-weight: bold;
            font-size: 1.3rem;
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        
        .page-header {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            color: white;
            padding: 40px 0;
            margin-bottom: 30px;
            border-radius: 15px;
        }
        
        .cart-item {
            background: white;
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 15px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
            transition: all 0.3s;
        }
        
        .cart-item:hover {
            box-shadow: 0 5px 20px rgba(0,0,0,0.15);
        }
        
        .product-image-cart {
            width: 100px;
            height: 100px;
            background: linear-gradient(135deg, #667eea22 0%, #764ba222 100%);
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2.5rem;
            color: var(--primary-color);
        }
        
        .quantity-control {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .quantity-control button {
            width: 35px;
            height: 35px;
            border-radius: 50%;
            border: 2px solid var(--primary-color);
            background: white;
            color: var(--primary-color);
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .quantity-control button:hover {
            background: var(--primary-color);
            color: white;
        }
        
        .quantity-control input {
            width: 60px;
            text-align: center;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            padding: 5px;
        }
        
        .summary-card {
            background: white;
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
            position: sticky;
            top: 20px;
        }
        
        .summary-row {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            border-bottom: 1px solid #eee;
        }
        
        .summary-total {
            font-size: 1.5rem;
            font-weight: bold;
            color: var(--primary-color);
            padding-top: 15px;
            margin-top: 15px;
            border-top: 2px solid var(--primary-color);
        }
        
        .btn-checkout {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            color: white;
            border: none;
            padding: 15px;
            border-radius: 10px;
            font-weight: bold;
            font-size: 1.1rem;
            width: 100%;
            transition: all 0.3s;
        }
        
        .btn-checkout:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(102, 126, 234, 0.4);
            color: white;
        }
        
        .empty-cart {
            text-align: center;
            padding: 60px 20px;
            background: white;
            border-radius: 15px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
        }
        
        .empty-cart i {
            font-size: 6rem;
            color: #ddd;
            margin-bottom: 20px;
        }
        
        .price-highlight {
            font-size: 1.3rem;
            font-weight: bold;
            color: var(--primary-color);
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
                        <a class="nav-link" href="index.php">
                            <i class="bi bi-grid-fill"></i> Productos
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active position-relative" href="carrito.php">
                            <i class="bi bi-cart-fill"></i> Carrito
                            <span class="cart-badge"><?php echo count($items_carrito); ?></span>
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

    <!-- Page Header -->
    <div class="container mt-4">
        <div class="page-header">
            <div class="container text-center">
                <h1><i class="bi bi-cart-fill"></i> Mi Carrito de Compras</h1>
                <p class="mb-0">Revisa tus productos antes de finalizar la compra</p>
            </div>
        </div>

        <?php if ($mensaje): ?>
        <div class="alert alert-<?php echo $tipo_mensaje; ?> alert-dismissible fade show" role="alert">
            <i class="bi bi-<?php echo $tipo_mensaje === 'success' ? 'check-circle' : 'info-circle'; ?>"></i>
            <?php echo $mensaje; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>

        <?php if (empty($items_carrito)): ?>
            <!-- Carrito Vacío -->
            <div class="empty-cart">
                <i class="bi bi-cart-x"></i>
                <h3>Tu carrito está vacío</h3>
                <p class="text-muted">¡Agrega productos para comenzar tu compra!</p>
                <a href="index.php" class="btn btn-checkout mt-3">
                    <i class="bi bi-shop"></i> Ir a Comprar
                </a>
            </div>
        <?php else: ?>
            <!-- Carrito con Productos -->
            <div class="row">
                <!-- Lista de Productos -->
                <div class="col-lg-8">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5>Productos en tu carrito (<?php echo count($items_carrito); ?>)</h5>
                        <form method="POST" style="display: inline;">
                            <input type="hidden" name="accion" value="vaciar">
                            <button type="submit" class="btn btn-outline-danger btn-sm" 
                                    onclick="return confirm('¿Estás seguro de vaciar el carrito?')">
                                <i class="bi bi-trash"></i> Vaciar Carrito
                            </button>
                        </form>
                    </div>

                    <?php foreach ($items_carrito as $item): ?>
                    <div class="cart-item">
                        <div class="row align-items-center">
                            <div class="col-md-2">
                                <div class="product-image-cart">
                                    <i class="bi bi-box"></i>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <h6 class="mb-1"><?php echo htmlspecialchars($item['nombre']); ?></h6>
                                <small class="text-muted">
                                    <i class="bi bi-tag"></i> <?php echo htmlspecialchars($item['categoria_nombre']); ?>
                                </small>
                                <br>
                                <small class="text-muted">
                                    Precio unitario: $<?php echo number_format($item['precio'], 0); ?>
                                </small>
                                <br>
                                <small class="text-<?php echo $item['stock'] < 10 ? 'danger' : 'success'; ?>">
                                    <i class="bi bi-box-seam"></i> Stock: <?php echo $item['stock']; ?> disponibles
                                </small>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small">Cantidad</label>
                                <form method="POST" class="quantity-form">
                                    <input type="hidden" name="accion" value="actualizar">
                                    <input type="hidden" name="carrito_id" value="<?php echo $item['carrito_id']; ?>">
                                    <div class="quantity-control">
                                        <button type="button" onclick="decrementar(this)">
                                            <i class="bi bi-dash"></i>
                                        </button>
                                        <input type="number" name="cantidad" value="<?php echo $item['cantidad']; ?>" 
                                               min="1" max="<?php echo $item['stock']; ?>" 
                                               class="form-control" readonly>
                                        <button type="button" onclick="incrementar(this, <?php echo $item['stock']; ?>)">
                                            <i class="bi bi-plus"></i>
                                        </button>
                                    </div>
                                    <button type="submit" class="btn btn-sm btn-outline-primary mt-2 w-100">
                                        <i class="bi bi-check"></i> Actualizar
                                    </button>
                                </form>
                            </div>
                            <div class="col-md-2 text-end">
                                <div class="price-highlight">
                                    $<?php echo number_format($item['subtotal'], 0); ?>
                                </div>
                            </div>
                            <div class="col-md-1 text-end">
                                <form method="POST" style="display: inline;">
                                    <input type="hidden" name="accion" value="eliminar">
                                    <input type="hidden" name="carrito_id" value="<?php echo $item['carrito_id']; ?>">
                                    <button type="submit" class="btn btn-outline-danger btn-sm" 
                                            onclick="return confirm('¿Eliminar este producto?')">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>

                    <div class="mt-3">
                        <a href="index.php" class="btn btn-outline-primary">
                            <i class="bi bi-arrow-left"></i> Seguir Comprando
                        </a>
                    </div>
                </div>

                <!-- Resumen de Compra -->
                <div class="col-lg-4">
                    <div class="summary-card">
                        <h5 class="mb-4">
                            <i class="bi bi-receipt"></i> Resumen de Compra
                        </h5>
                        
                        <div class="summary-row">
                            <span>Subtotal (<?php echo count($items_carrito); ?> productos)</span>
                            <strong>$<?php echo number_format($subtotal, 0); ?></strong>
                        </div>
                        
                        <?php if ($impuesto > 0): ?>
                        <div class="summary-row">
                            <span>Impuestos (IVA 19%)</span>
                            <strong>$<?php echo number_format($impuesto, 0); ?></strong>
                        </div>
                        <?php endif; ?>
                        
                        <div class="summary-row">
                            <span>Envío</span>
                            <strong class="text-success">GRATIS</strong>
                        </div>
                        
                        <div class="summary-row summary-total">
                            <span>Total</span>
                            <span>$<?php echo number_format($total, 0); ?></span>
                        </div>
                        
                        <button class="btn btn-checkout mt-4" onclick="window.location.href='checkout.php'">
                            <i class="bi bi-credit-card"></i> Proceder al Pago
                        </button>
                        
                        <div class="mt-3 text-center">
                            <small class="text-muted">
                                <i class="bi bi-shield-check text-success"></i> 
                                Compra 100% segura
                            </small>
                        </div>
                        
                        <div class="alert alert-info mt-3 mb-0">
                            <small>
                                <i class="bi bi-info-circle"></i> 
                                Los precios incluyen todos los impuestos
                            </small>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <!-- Modal Checkout (Provisional) -->
    <div class="modal fade" id="modalCheckout" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="bi bi-credit-card"></i> Finalizar Compra
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-warning">
                        <i class="bi bi-exclamation-triangle"></i>
                        <strong>Funcionalidad en desarrollo</strong>
                        <p class="mb-0">El sistema de checkout completo se implementará próximamente.</p>
                    </div>
                    <h6>Resumen de tu pedido:</h6>
                    <ul class="list-group mb-3">
                        <?php foreach ($items_carrito as $item): ?>
                        <li class="list-group-item d-flex justify-content-between">
                            <span><?php echo htmlspecialchars($item['nombre']); ?> (x<?php echo $item['cantidad']; ?>)</span>
                            <strong>$<?php echo number_format($item['subtotal'], 0); ?></strong>
                        </li>
                        <?php endforeach; ?>
                        <li class="list-group-item d-flex justify-content-between bg-light">
                            <strong>Total</strong>
                            <strong class="text-primary">$<?php echo number_format($total, 0); ?></strong>
                        </li>
                    </ul>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <a href="checkout.php" class="btn btn-checkout">
                        Continuar con la Compra
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="bg-white py-4 mt-5 border-top">
        <div class="container text-center">
            <p class="mb-0 text-muted">
                &copy; 2025 Desechables Punto Fijo - Todos los derechos reservados
            </p>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function incrementar(btn, maxStock) {
            const input = btn.parentElement.querySelector('input[type="number"]');
            const currentValue = parseInt(input.value);
            if (currentValue < maxStock) {
                input.value = currentValue + 1;
            } else {
                alert('No hay más stock disponible');
            }
        }
        
        function decrementar(btn) {
            const input = btn.parentElement.querySelector('input[type="number"]');
            const currentValue = parseInt(input.value);
            if (currentValue > 1) {
                input.value = currentValue - 1;
            }
        }
    </script>
</body>
</html>