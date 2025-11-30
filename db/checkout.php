<?php
/**
 * CHECKOUT - FINALIZAR COMPRA
 * Desechables Punto Fijo
 */

require_once 'config/config.php';

// Definir sanitize si no existe aún
if (!function_exists('sanitize')) {
    function sanitize($value) {
        if ($value === null) return '';
        return htmlspecialchars(trim($value), ENT_QUOTES, 'UTF-8');
    }
}

// Si no está logueado, redirigir al login
if (!estaLogueado()) {
    redirect('login.php');
}

$db = getDB();
$usuario_id = $_SESSION['usuario_id'];

// OBTENER ITEMS DEL CARRITO
$sql = "SELECT c.id as carrito_id, c.cantidad,
        p.id as producto_id, p.nombre, p.precio, p.stock,
        (p.precio * c.cantidad) as subtotal
        FROM carrito c
        INNER JOIN productos p ON c.producto_id = p.id
        WHERE c.usuario_id = ? AND p.estado = 'disponible'";

$stmt = $db->prepare($sql);
$stmt->bind_param("i", $usuario_id);
$stmt->execute();
$result = $stmt->get_result();
$items_carrito = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Si el carrito está vacío, redirigir
if (empty($items_carrito)) {
    redirect('carrito.php');
}

// CALCULAR TOTALES
$subtotal = 0;
foreach ($items_carrito as $item) {
    $subtotal += $item['subtotal'];
}
$impuesto = 0; // Puedes agregar IVA: $subtotal * 0.19
$envio = $subtotal >= 50000 ? 0 : 5000;
$total = $subtotal + $impuesto + $envio;

// OBTENER DATOS DEL USUARIO
$stmt = $db->prepare("SELECT * FROM usuarios WHERE id = ?");
$stmt->bind_param("i", $usuario_id);
$stmt->execute();
$result = $stmt->get_result();
$usuario = $result->fetch_assoc();
$stmt->close();

$mensaje = '';
$tipo_mensaje = '';
$orden_id = null;
$metodo_pago = ''; // para usarlo luego en la vista de confirmación

// PROCESAR LA COMPRA
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirmar_compra'])) {

    // Obtener datos del formulario
    $nombre = sanitize(($_POST['first_name'] ?? '') . ' ' . ($_POST['last_name'] ?? ''));
    $email = sanitize($_POST['email'] ?? '');
    $telefono = sanitize($_POST['phone'] ?? '');
    $direccion_entrega = sanitize($_POST['address'] ?? '');
    $apartamento = sanitize($_POST['apartment'] ?? '');
    $ciudad = sanitize($_POST['city'] ?? '');
    $estado = sanitize($_POST['state'] ?? '');
    $codigo_postal = sanitize($_POST['zip'] ?? '');
    $pais = sanitize($_POST['country'] ?? '');
    $metodo_pago = sanitize($_POST['payment_method'] ?? '');
    $notas = '';

    // Construir dirección completa
    $direccion_completa = $direccion_entrega;
    if ($apartamento) {
        $direccion_completa .= ', ' . $apartamento;
    }
    $direccion_completa .= ', ' . $ciudad . ', ' . $estado . ' ' . $codigo_postal . ', ' . $pais;

    // Validaciones
    $errores = [];

    if (empty(trim($nombre)) || empty($email) || empty($telefono)) {
        $errores[] = "Todos los campos de información del cliente son obligatorios";
    }

    if (empty($direccion_entrega) || empty($ciudad) || empty($estado) || empty($codigo_postal) || empty($pais)) {
        $errores[] = "Todos los campos de dirección son obligatorios";
    }

    if (empty($metodo_pago)) {
        $errores[] = "Debes seleccionar un método de pago";
    }

    if (!isset($_POST['terms'])) {
        $errores[] = "Debes aceptar los términos y condiciones";
    }

    // Verificar stock nuevamente
    foreach ($items_carrito as $item) {
        if ($item['cantidad'] > $item['stock']) {
            $errores[] = "Stock insuficiente para: " . $item['nombre'];
        }
    }

    if (empty($errores)) {
        // Iniciar transacción
        $db->begin_transaction();

        try {
            // 1. CREAR LA VENTA
            $stmt = $db->prepare("INSERT INTO ventas 
                (usuario_id, total, subtotal, impuesto, metodo_pago, estado, direccion_entrega, notas) 
                VALUES (?, ?, ?, ?, ?, 'pendiente', ?, ?)");
            $stmt->bind_param("idddsss", $usuario_id, $total, $subtotal, $impuesto, $metodo_pago, $direccion_completa, $notas);
            $stmt->execute();
            $venta_id = $stmt->insert_id;
            $stmt->close();

            // 2. CREAR DETALLE DE VENTA Y ACTUALIZAR STOCK
            foreach ($items_carrito as $item) {
                // Insertar detalle
                $stmt = $db->prepare("INSERT INTO detalle_ventas 
                    (venta_id, producto_id, cantidad, precio_unitario, subtotal) 
                    VALUES (?, ?, ?, ?, ?)");
                $stmt->bind_param("iiidd", $venta_id, $item['producto_id'], $item['cantidad'], $item['precio'], $item['subtotal']);
                $stmt->execute();
                $stmt->close();

                // Actualizar stock
                $stmt = $db->prepare("UPDATE productos SET stock = stock - ? WHERE id = ?");
                $stmt->bind_param("ii", $item['cantidad'], $item['producto_id']);
                $stmt->execute();
                $stmt->close();
            }

            // 3. VACIAR EL CARRITO
            $stmt = $db->prepare("DELETE FROM carrito WHERE usuario_id = ?");
            $stmt->bind_param("i", $usuario_id);
            $stmt->execute();
            $stmt->close();

            // Confirmar transacción
            $db->commit();

            // Mostrar pantalla de confirmación
            $orden_id = $venta_id;

        } catch (Exception $e) {
            // Revertir cambios
            $db->rollback();
            $errores[] = "Error al procesar la compra: " . $e->getMessage();
        }
    }

    if (!empty($errores)) {
        $mensaje = implode("<br>", $errores);
        $tipo_mensaje = "danger";
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Finalizar Compra - Desechables Punto Fijo</title>

    <!-- Fonts -->
    <link href="https://fonts.googleapis.com" rel="preconnect">
    <link href="https://fonts.gstatic.com" rel="preconnect" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:ital,wght@0,100;0,300;0,400;0,500;0,700;0,900&family=Montserrat:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900&family=Poppins:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900&display=swap" rel="stylesheet">

    <!-- Vendor CSS Files -->
    <link href="assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <link href="assets/vendor/bootstrap-icons/bootstrap-icons.css" rel="stylesheet">
    <link href="assets/vendor/aos/aos.css" rel="stylesheet">

    <!-- Main CSS File -->
    <link href="assets/css/main.css" rel="stylesheet">
</head>

<body class="checkout-page">
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
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</header>

<main class="main">
    <?php if ($orden_id): ?>
        <!-- PÁGINA DE CONFIRMACIÓN -->
        <div class="page-title light-background">
            <div class="container d-lg-flex justify-content-between align-items-center">
                <h1 class="mb-2 mb-lg-0">¡Pedido Confirmado!</h1>
                <nav class="breadcrumbs">
                    <ol>
                        <li><a href="index.php">Inicio</a></li>
                        <li><a href="carrito.php">Carrito</a></li>
                        <li class="current">Confirmación</li>
                    </ol>
                </nav>
            </div>
        </div>

        <section class="checkout section">
            <div class="container" data-aos="fade-up">
                <div class="checkout-container">
                    <div class="text-center py-5">
                        <div style="width: 120px; height: 120px; border-radius: 50%; background: linear-gradient(135deg, #10b981 0%, #059669 100%); display: flex; align-items: center; justify-content: center; margin: 0 auto 30px;">
                            <i class="bi bi-check-lg" style="font-size: 4rem; color: white;"></i>
                        </div>
                        <h1 class="text-success mb-3">¡Compra Exitosa!</h1>
                        <p class="lead">Tu pedido ha sido registrado correctamente</p>

                        <div class="alert alert-info mt-4 text-start" style="max-width: 600px; margin: 0 auto;">
                            <h5><i class="bi bi-info-circle"></i> Detalles de tu Pedido</h5>
                            <div class="row mt-3">
                                <div class="col-md-6 mb-2">
                                    <strong>Número de Orden:</strong><br>
                                    #<?php echo str_pad($orden_id, 6, '0', STR_PAD_LEFT); ?>
                                </div>
                                <div class="col-md-6 mb-2">
                                    <strong>Total:</strong><br>
                                    $<?php echo number_format($total, 0); ?>
                                </div>
                                <div class="col-md-6 mb-2">
                                    <strong>Método de Pago:</strong><br>
                                    <?php
                                    $metodos = [
                                        'credit-card'   => 'Tarjeta de Crédito/Débito',
                                        'efectivo'      => 'Efectivo (Contra Entrega)',
                                        'transferencia' => 'Transferencia Bancaria',
                                        'nequi'         => 'Nequi / Daviplata'
                                    ];
                                    echo $metodos[$metodo_pago] ?? 'No especificado';
                                    ?>
                                </div>
                                <div class="col-md-6 mb-2">
                                    <strong>Estado:</strong><br>
                                    <span class="badge bg-warning">Pendiente</span>
                                </div>
                            </div>
                        </div>

                        <div class="mt-4" style="max-width: 600px; margin: 0 auto;">
                            <h5>¿Qué sigue?</h5>
                            <p class="text-muted">
                                <i class="bi bi-1-circle text-primary"></i> Procesaremos tu pedido<br>
                                <i class="bi bi-2-circle text-primary"></i> Te contactaremos para confirmar la entrega<br>
                                <i class="bi bi-3-circle text-primary"></i> Recibirás tu pedido en la dirección indicada
                            </p>
                        </div>

                        <div class="mt-4 d-flex gap-2 justify-content-center">
                            <a href="index.php" class="btn btn-primary btn-lg">
                                <i class="bi bi-shop"></i> Seguir Comprando
                            </a>
                            <a href="#" class="btn btn-outline-primary btn-lg">
                                <i class="bi bi-clock-history"></i> Ver Mis Pedidos
                            </a>
                        </div>

                        <div class="mt-4">
                            <small class="text-muted">
                                <i class="bi bi-telephone"></i> ¿Necesitas ayuda? Llámanos: 317 726 8740 | 315 744 1535
                            </small>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    <?php else: ?>
        <!-- FORMULARIO DE CHECKOUT -->
        <div class="page-title light-background">
            <div class="container d-lg-flex justify-content-between align-items-center">
                <h1 class="mb-2 mb-lg-0">Finalizar Compra</h1>
                <nav class="breadcrumbs">
                    <ol>
                        <li><a href="index.php">Inicio</a></li>
                        <li><a href="carrito.php">Carrito</a></li>
                        <li class="current">Checkout</li>
                    </ol>
                </nav>
            </div>
        </div>

        <section id="checkout" class="checkout section">
            <div class="container" data-aos="fade-up" data-aos-delay="100">

                <?php if ($mensaje): ?>
                    <div class="alert alert-<?php echo $tipo_mensaje; ?> alert-dismissible fade show" role="alert">
                        <i class="bi bi-exclamation-triangle"></i>
                        <?php echo $mensaje; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <div class="row">
                    <div class="col-lg-7">
                        <!-- Checkout Form -->
                        <div class="checkout-container" data-aos="fade-up">
                            <form class="checkout-form" method="POST" id="checkoutForm">
                                <input type="hidden" name="confirmar_compra" value="1">

                                <!-- Customer Information -->
                                <div class="checkout-section" id="customer-info">
                                    <div class="section-header">
                                        <div class="section-number">1</div>
                                        <h3>Información del Cliente</h3>
                                    </div>
                                    <div class="section-content">
                                        <div class="row">
                                            <div class="col-md-6 form-group">
                                                <label for="first-name">Nombre</label>
                                                <input type="text" name="first_name" class="form-control" id="first-name"
                                                       value="<?php echo htmlspecialchars(explode(' ', $usuario['nombre'])[0] ?? ''); ?>" required>
                                            </div>
                                            <div class="col-md-6 form-group">
                                                <label for="last-name">Apellido</label>
                                                <input type="text" name="last_name" class="form-control" id="last-name"
                                                       value="<?php echo htmlspecialchars($usuario['apellido'] ?? ''); ?>" required>
                                            </div>
                                        </div>
                                        <div class="form-group">
                                            <label for="email">Email</label>
                                            <input type="email" class="form-control" name="email" id="email"
                                                   value="<?php echo htmlspecialchars($usuario['email']); ?>" required>
                                        </div>
                                        <div class="form-group">
                                            <label for="phone">Teléfono</label>
                                            <input type="tel" class="form-control" name="phone" id="phone"
                                                   value="<?php echo htmlspecialchars($usuario['telefono']); ?>" required>
                                        </div>
                                    </div>
                                </div>

                                <!-- Shipping Address -->
                                <div class="checkout-section" id="shipping-address">
                                    <div class="section-header">
                                        <div class="section-number">2</div>
                                        <h3>Dirección de Envío</h3>
                                    </div>
                                    <div class="section-content">
                                        <div class="form-group">
                                            <label for="address">Dirección</label>
                                            <input type="text" class="form-control" name="address" id="address"
                                                   value="<?php echo htmlspecialchars($usuario['direccion'] ?? ''); ?>"
                                                   placeholder="Calle, número" required>
                                        </div>
                                        <div class="form-group">
                                            <label for="apartment">Apartamento, Casa, etc. (opcional)</label>
                                            <input type="text" class="form-control" name="apartment" id="apartment"
                                                   placeholder="Apartamento, Casa, Oficina">
                                        </div>
                                        <div class="row">
                                            <div class="col-md-4 form-group">
                                                <label for="city">Ciudad</label>
                                                <input type="text" name="city" class="form-control" id="city"
                                                       placeholder="Bogotá" required>
                                            </div>
                                            <div class="col-md-4 form-group">
                                                <label for="state">Departamento</label>
                                                <input type="text" name="state" class="form-control" id="state"
                                                       placeholder="Cundinamarca" required>
                                            </div>
                                            <div class="col-md-4 form-group">
                                                <label for="zip">Código Postal</label>
                                                <input type="text" name="zip" class="form-control" id="zip"
                                                       placeholder="110111" required>
                                            </div>
                                        </div>
                                        <div class="form-group">
                                            <label for="country">País</label>
                                            <select class="form-select" id="country" name="country" required>
                                                <option value="Colombia" selected>Colombia</option>
                                                <option value="Venezuela">Venezuela</option>
                                                <option value="Ecuador">Ecuador</option>
                                                <option value="Perú">Perú</option>
                                            </select>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" id="save-address" name="save-address">
                                            <label class="form-check-label" for="save-address">
                                                Guardar esta dirección para futuros pedidos
                                            </label>
                                        </div>
                                    </div>
                                </div>

                                <!-- Payment Method -->
                                <div class="checkout-section" id="payment-method">
                                    <div class="section-header">
                                        <div class="section-number">3</div>
                                        <h3>Método de Pago</h3>
                                    </div>
                                    <div class="section-content">
                                        <div class="payment-options">
                                            <div class="payment-option active">
                                                <input type="radio" name="payment_method" id="credit-card" value="credit-card" checked>
                                                <label for="credit-card">
                                                    <span class="payment-icon"><i class="bi bi-credit-card-2-front"></i></span>
                                                    <span class="payment-label">Tarjeta de Crédito / Débito</span>
                                                </label>
                                            </div>
                                            <div class="payment-option">
                                                <input type="radio" name="payment_method" id="efectivo" value="efectivo">
                                                <label for="efectivo">
                                                    <span class="payment-icon"><i class="bi bi-cash-coin"></i></span>
                                                    <span class="payment-label">Efectivo (Contra Entrega)</span>
                                                </label>
                                            </div>
                                            <div class="payment-option">
                                                <input type="radio" name="payment_method" id="transferencia" value="transferencia">
                                                <label for="transferencia">
                                                    <span class="payment-icon"><i class="bi bi-bank"></i></span>
                                                    <span class="payment-label">Transferencia Bancaria</span>
                                                </label>
                                            </div>
                                            <div class="payment-option">
                                                <input type="radio" name="payment_method" id="nequi" value="nequi">
                                                <label for="nequi">
                                                    <span class="payment-icon"><i class="bi bi-phone"></i></span>
                                                    <span class="payment-label">Nequi / Daviplata</span>
                                                </label>
                                            </div>
                                        </div>

                                        <div class="payment-details" id="credit-card-details">
                                            <div class="alert alert-info">
                                                <i class="bi bi-info-circle"></i> Los datos de la tarjeta se solicitarán en la siguiente página de forma segura.
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Order Review -->
                                <div class="checkout-section" id="order-review">
                                    <div class="section-header">
                                        <div class="section-number">4</div>
                                        <h3>Revisar y Realizar Pedido</h3>
                                    </div>
                                    <div class="section-content">
                                        <div class="form-check terms-check">
                                            <input class="form-check-input" type="checkbox" id="terms" name="terms" required>
                                            <label class="form-check-label" for="terms">
                                                Acepto los <a href="#" data-bs-toggle="modal" data-bs-target="#termsModal">Términos y Condiciones</a> y la <a href="#" data-bs-toggle="modal" data-bs-target="#privacyModal">Política de Privacidad</a>
                                            </label>
                                        </div>
                                        <div class="place-order-container">
                                            <button type="submit" class="btn btn-primary place-order-btn">
                                                <span class="btn-text">Realizar Pedido</span>
                                                <span class="btn-price">$<?php echo number_format($total, 0); ?></span>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>

                    <div class="col-lg-5">
                        <!-- Order Summary -->
                        <div class="order-summary" data-aos="fade-left" data-aos-delay="200">
                            <div class="order-summary-header">
                                <h3>Resumen del Pedido</h3>
                                <span class="item-count"><?php echo count($items_carrito); ?> Productos</span>
                            </div>

                            <div class="order-summary-content">
                                <div class="order-items">
                                    <?php foreach ($items_carrito as $item): ?>
                                        <div class="order-item">
                                            <div class="order-item-image">
                                                <div style="width: 80px; height: 80px; background: linear-gradient(135deg, #667eea22 0%, #764ba222 100%); border-radius: 10px; display: flex; align-items: center; justify-content: center;">
                                                    <i class="bi bi-box" style="font-size: 2rem; color: #667eea;"></i>
                                                </div>
                                            </div>
                                            <div class="order-item-details">
                                                <h4><?php echo htmlspecialchars($item['nombre']); ?></h4>
                                                <div class="order-item-price">
                                                    <span class="quantity"><?php echo $item['cantidad']; ?> ×</span>
                                                    <span class="price">$<?php echo number_format($item['precio'], 0); ?></span>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>

                                <div class="order-totals">
                                    <div class="order-subtotal d-flex justify-content-between">
                                        <span>Subtotal</span>
                                        <span>$<?php echo number_format($subtotal, 0); ?></span>
                                    </div>
                                    <div class="order-shipping d-flex justify-content-between">
                                        <span>Envío</span>
                                        <span class="<?php echo $envio == 0 ? 'text-success' : ''; ?>">
                                            <?php echo $envio == 0 ? 'GRATIS' : '$' . number_format($envio, 0); ?>
                                        </span>
                                    </div>
                                    <?php if ($impuesto > 0): ?>
                                        <div class="order-tax d-flex justify-content-between">
                                            <span>Impuestos</span>
                                            <span>$<?php echo number_format($impuesto, 0); ?></span>
                                        </div>
                                    <?php endif; ?>
                                    <div class="order-total d-flex justify-content-between">
                                        <span>Total</span>
                                        <span>$<?php echo number_format($total, 0); ?></span>
                                    </div>
                                </div>

                                <div class="secure-checkout">
                                    <div class="secure-checkout-header">
                                        <i class="bi bi-shield-lock"></i>
                                        <span>Pago Seguro</span>
                                    </div>
                                    <div class="payment-icons">
                                        <i class="bi bi-credit-card-2-front"></i>
                                        <i class="bi bi-credit-card"></i>
                                        <i class="bi bi-cash-coin"></i>
                                        <i class="bi bi-bank"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Terms Modal -->
                <div class="modal fade" id="termsModal" tabindex="-1" aria-labelledby="termsModalLabel" aria-hidden="true">
                    <div class="modal-dialog modal-dialog-scrollable">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title" id="termsModalLabel">Términos y Condiciones</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                <p>Al realizar un pedido en Desechables Punto Fijo, aceptas nuestros términos y condiciones de servicio.</p>
                                <p>Nos comprometemos a procesar tu pedido de manera rápida y eficiente, garantizando productos de calidad.</p>
                                <p>
                                    Los tiempos de entrega pueden variar según la zona de envío y la disponibilidad de los productos.
                                    En caso de no contar con algún producto, nos comunicaremos contigo para ofrecerte una alternativa o el reembolso correspondiente.
                                </p>
                                <p>
                                    El cliente es responsable de verificar que los datos de contacto y de envío sean correctos.
                                    Cualquier cambio debe ser notificado oportunamente a nuestros canales de atención.
                                </p>
                                <p>
                                    Para más información sobre garantías, cambios y devoluciones, puedes comunicarte con nuestro servicio al cliente.
                                </p>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Privacy Modal -->
                <div class="modal fade" id="privacyModal" tabindex="-1" aria-labelledby="privacyModalLabel" aria-hidden="true">
                    <div class="modal-dialog modal-dialog-scrollable">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title" id="privacyModalLabel">Política de Privacidad</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                <p>
                                    En Desechables Punto Fijo protegemos tus datos personales y los utilizamos únicamente para gestionar tus pedidos,
                                    realizar la facturación correspondiente y mantener la comunicación contigo.
                                </p>
                                <p>
                                    No compartimos tu información con terceros no autorizados. Solo trabajamos con proveedores de pago y logística
                                    que cumplen con estándares de seguridad y confidencialidad.
                                </p>
                                <p>
                                    Puedes solicitar la actualización o eliminación de tus datos personales a través de nuestros canales de atención.
                                    Al aceptar esta política, autorizas el tratamiento de tus datos de acuerdo con la normatividad vigente.
                                </p>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                            </div>
                        </div>
                    </div>
                </div>

            </div> <!-- /.container -->
        </section>
    <?php endif; ?>
</main>

<footer id="footer" class="footer">
    <div class="container text-center py-4">
        <p class="mb-1">&copy; <?php echo date('Y'); ?> <strong>Desechables Punto Fijo</strong>. Todos los derechos reservados.</p>
        <small class="text-muted">Productos desechables para tu negocio y hogar.</small>
    </div>
</footer>

<!-- Vendor JS Files -->
<script src="assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="assets/vendor/aos/aos.js"></script>

<!-- Main JS File -->
<script src="assets/js/main.js"></script>

<script>
// Interacción visual de métodos de pago y detalle según selección
document.addEventListener('DOMContentLoaded', function () {
    const paymentOptions = document.querySelectorAll('.payment-option');
    const detailsContainer = document.getElementById('credit-card-details');

    if (!detailsContainer) return;

    const defaultHtml = detailsContainer.innerHTML;

    function updateDetails(method) {
        if (method === 'credit-card') {
            detailsContainer.innerHTML = defaultHtml;
            return;
        }

        let html = '';

        if (method === 'efectivo') {
            html = `
                <div class="alert alert-success">
                    <i class="bi bi-cash-coin"></i>
                    Pagarás en efectivo al recibir tu pedido. Nuestro repartidor llevará el comprobante correspondiente.
                </div>
            `;
        } else if (method === 'transferencia') {
            html = `
                <div class="alert alert-info">
                    <i class="bi bi-bank"></i>
                    Te enviaremos los datos bancarios y el valor exacto a tu WhatsApp o correo para que realices la transferencia.
                </div>
            `;
        } else if (method === 'nequi') {
            html = `
                <div class="alert alert-info">
                    <i class="bi bi-phone"></i>
                    Te contactaremos para enviarte el número de Nequi o Daviplata y completar el pago.
                </div>
            `;
        } else {
            detailsContainer.innerHTML = defaultHtml;
            return;
        }

        detailsContainer.innerHTML = html;
    }

    paymentOptions.forEach(function (option) {
        const input = option.querySelector('input[type="radio"]');
        if (!input) return;

        input.addEventListener('change', function () {
            paymentOptions.forEach(o => o.classList.remove('active'));
            option.classList.add('active');
            updateDetails(this.value);
        });
    });
});
</script>
</body>
</html>
