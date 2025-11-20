<?php
/**
 * CHECKOUT - FINALIZAR COMPRA
 * Desechables Punto Fijo
 */

require_once 'config.php';

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
$total = $subtotal + $impuesto;

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

// PROCESAR LA COMPRA
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirmar_compra'])) {
    
    // Obtener datos del formulario
    $direccion_entrega = sanitize($_POST['direccion_entrega']);
    $metodo_pago = sanitize($_POST['metodo_pago']);
    $notas = sanitize($_POST['notas'] ?? '');
    
    // Validaciones
    $errores = [];
    
    if (empty($direccion_entrega)) {
        $errores[] = "La dirección de entrega es obligatoria";
    }
    
    if (empty($metodo_pago)) {
        $errores[] = "Debes seleccionar un método de pago";
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
            $stmt = $db->prepare("INSERT INTO ventas (usuario_id, total, subtotal, impuesto, metodo_pago, estado, direccion_entrega, notas) VALUES (?, ?, ?, ?, ?, 'pendiente', ?, ?)");
            $stmt->bind_param("idddsss", $usuario_id, $total, $subtotal, $impuesto, $metodo_pago, $direccion_entrega, $notas);
            $stmt->execute();
            $venta_id = $stmt->insert_id;
            $stmt->close();
            
            // 2. CREAR DETALLE DE VENTA Y ACTUALIZAR STOCK
            foreach ($items_carrito as $item) {
                // Insertar detalle
                $stmt = $db->prepare("INSERT INTO detalle_ventas (venta_id, producto_id, cantidad, precio_unitario, subtotal) VALUES (?, ?, ?, ?, ?)");
                $stmt->bind_param("iiidd", $venta_id, $item['producto_id'], $item['cantidad'], $item['precio'], $item['subtotal']);
                $stmt->execute();
                $stmt->close();
                
                // Actualizar stock (el trigger lo hace automáticamente, pero por si acaso)
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
            
            // Redirigir a página de confirmación
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
        
        .checkout-header {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            color: white;
            padding: 30px 0;
            margin-bottom: 30px;
        }
        
        .checkout-steps {
            display: flex;
            justify-content: center;
            margin: 20px 0;
        }
        
        .step {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 10px 20px;
            background: rgba(255,255,255,0.2);
            border-radius: 25px;
            margin: 0 5px;
        }
        
        .step.active {
            background: white;
            color: var(--primary-color);
        }
        
        .checkout-card {
            background: white;
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
            margin-bottom: 20px;
        }
        
        .summary-card {
            background: white;
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
            position: sticky;
            top: 20px;
        }
        
        .product-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px;
            border-bottom: 1px solid #eee;
        }
        
        .product-item:last-child {
            border-bottom: none;
        }
        
        .payment-option {
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 15px;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .payment-option:hover {
            border-color: var(--primary-color);
            background: rgba(102, 126, 234, 0.05);
        }
        
        .payment-option.selected {
            border-color: var(--primary-color);
            background: rgba(102, 126, 234, 0.1);
        }
        
        .payment-option input[type="radio"] {
            width: 20px;
            height: 20px;
            accent-color: var(--primary-color);
        }
        
        .btn-finalizar {
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
        
        .btn-finalizar:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(102, 126, 234, 0.4);
            color: white;
        }
        
        .success-animation {
            text-align: center;
            padding: 60px 20px;
        }
        
        .success-icon {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 30px;
            animation: scaleIn 0.5s ease-out;
        }
        
        .success-icon i {
            font-size: 4rem;
            color: white;
        }
        
        @keyframes scaleIn {
            from { transform: scale(0); }
            to { transform: scale(1); }
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-light">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                <i class="bi bi-shop"></i> Desechables Punto Fijo
            </a>
        </div>
    </nav>

    <?php if ($orden_id): ?>
        <!-- PÁGINA DE CONFIRMACIÓN -->
        <div class="container mt-5 mb-5">
            <div class="checkout-card">
                <div class="success-animation">
                    <div class="success-icon">
                        <i class="bi bi-check-lg"></i>
                    </div>
                    <h1 class="text-success mb-3">¡Compra Exitosa!</h1>
                    <p class="lead">Tu pedido ha sido registrado correctamente</p>
                    
                    <div class="alert alert-info mt-4">
                        <h5><i class="bi bi-info-circle"></i> Detalles de tu Pedido</h5>
                        <div class="row text-start mt-3">
                            <div class="col-md-6">
                                <strong>Número de Orden:</strong> #<?php echo str_pad($orden_id, 6, '0', STR_PAD_LEFT); ?>
                            </div>
                            <div class="col-md-6">
                                <strong>Total:</strong> $<?php echo number_format($total, 0); ?>
                            </div>
                            <div class="col-md-6 mt-2">
                                <strong>Método de Pago:</strong> <?php echo ucfirst($_POST['metodo_pago']); ?>
                            </div>
                            <div class="col-md-6 mt-2">
                                <strong>Estado:</strong> <span class="badge bg-warning">Pendiente</span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mt-4">
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
    <?php else: ?>
        <!-- FORMULARIO DE CHECKOUT -->
        <div class="checkout-header">
            <div class="container text-center">
                <h2><i class="bi bi-credit-card"></i> Finalizar Compra</h2>
                <div class="checkout-steps">
                    <div class="step">
                        <i class="bi bi-cart-check"></i> Carrito
                    </div>
                    <div class="step active">
                        <i class="bi bi-credit-card"></i> Pago
                    </div>
                    <div class="step">
                        <i class="bi bi-check-circle"></i> Confirmación
                    </div>
                </div>
            </div>
        </div>

        <div class="container mb-5">
            <?php if ($mensaje): ?>
            <div class="alert alert-<?php echo $tipo_mensaje; ?> alert-dismissible fade show" role="alert">
                <i class="bi bi-exclamation-triangle"></i>
                <?php echo $mensaje; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php endif; ?>

            <form method="POST" id="formCheckout">
                <input type="hidden" name="confirmar_compra" value="1">
                
                <div class="row">
                    <!-- Formulario -->
                    <div class="col-lg-7">
                        <!-- Información de Entrega -->
                        <div class="checkout-card">
                            <h4 class="mb-4">
                                <i class="bi bi-geo-alt"></i> Información de Entrega
                            </h4>
                            
                            <div class="mb-3">
                                <label class="form-label">Nombre Completo</label>
                                <input type="text" class="form-control" 
                                       value="<?php echo htmlspecialchars($usuario['nombre'] . ' ' . $usuario['apellido']); ?>" 
                                       readonly>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Email</label>
                                    <input type="email" class="form-control" 
                                           value="<?php echo htmlspecialchars($usuario['email']); ?>" 
                                           readonly>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Teléfono</label>
                                    <input type="tel" class="form-control" 
                                           value="<?php echo htmlspecialchars($usuario['telefono']); ?>" 
                                           readonly>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Dirección de Entrega *</label>
                                <textarea name="direccion_entrega" class="form-control" rows="3" 
                                          placeholder="Calle, número, barrio, ciudad..." required><?php echo htmlspecialchars($usuario['direccion'] ?? ''); ?></textarea>
                                <small class="text-muted">
                                    <i class="bi bi-info-circle"></i> Asegúrate de que la dirección sea correcta
                                </small>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Notas Adicionales (Opcional)</label>
                                <textarea name="notas" class="form-control" rows="2" 
                                          placeholder="Indicaciones especiales, horario preferido, etc."></textarea>
                            </div>
                        </div>

                        <!-- Método de Pago -->
                        <div class="checkout-card">
                            <h4 class="mb-4">
                                <i class="bi bi-wallet2"></i> Método de Pago
                            </h4>
                            
                            <div class="payment-option" onclick="selectPayment(this, 'efectivo')">
                                <div class="d-flex align-items-center">
                                    <input type="radio" name="metodo_pago" value="efectivo" id="pago_efectivo" required>
                                    <label for="pago_efectivo" class="ms-3 flex-grow-1" style="cursor: pointer;">
                                        <div class="d-flex align-items-center">
                                            <i class="bi bi-cash-coin" style="font-size: 2rem; color: #10b981;"></i>
                                            <div class="ms-3">
                                                <strong>Efectivo</strong>
                                                <p class="mb-0 text-muted small">Pago contra entrega</p>
                                            </div>
                                        </div>
                                    </label>
                                </div>
                            </div>
                            
                            <div class="payment-option" onclick="selectPayment(this, 'transferencia')">
                                <div class="d-flex align-items-center">
                                    <input type="radio" name="metodo_pago" value="transferencia" id="pago_transferencia">
                                    <label for="pago_transferencia" class="ms-3 flex-grow-1" style="cursor: pointer;">
                                        <div class="d-flex align-items-center">
                                            <i class="bi bi-bank" style="font-size: 2rem; color: #3b82f6;"></i>
                                            <div class="ms-3">
                                                <strong>Transferencia Bancaria</strong>
                                                <p class="mb-0 text-muted small">Enviaremos los datos bancarios</p>
                                            </div>
                                        </div>
                                    </label>
                                </div>
                            </div>
                            
                            <div class="payment-option" onclick="selectPayment(this, 'nequi')">
                                <div class="d-flex align-items-center">
                                    <input type="radio" name="metodo_pago" value="nequi" id="pago_nequi">
                                    <label for="pago_nequi" class="ms-3 flex-grow-1" style="cursor: pointer;">
                                        <div class="d-flex align-items-center">
                                            <i class="bi bi-phone" style="font-size: 2rem; color: #a855f7;"></i>
                                            <div class="ms-3">
                                                <strong>Nequi</strong>
                                                <p class="mb-0 text-muted small">Pago por Nequi</p>
                                            </div>
                                        </div>
                                    </label>
                                </div>
                            </div>
                            
                            <div class="payment-option" onclick="selectPayment(this, 'daviplata')">
                                <div class="d-flex align-items-center">
                                    <input type="radio" name="metodo_pago" value="daviplata" id="pago_daviplata">
                                    <label for="pago_daviplata" class="ms-3 flex-grow-1" style="cursor: pointer;">
                                        <div class="d-flex align-items-center">
                                            <i class="bi bi-phone-fill" style="font-size: 2rem; color: #ef4444;"></i>
                                            <div class="ms-3">
                                                <strong>Daviplata</strong>
                                                <p class="mb-0 text-muted small">Pago por Daviplata</p>
                                            </div>
                                        </div>
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Resumen -->
                    <div class="col-lg-5">
                        <div class="summary-card">
                            <h5 class="mb-4">
                                <i class="bi bi-receipt"></i> Resumen del Pedido
                            </h5>
                            
                            <div style="max-height: 300px; overflow-y: auto; margin-bottom: 20px;">
                                <?php foreach ($items_carrito as $item): ?>
                                <div class="product-item">
                                    <div>
                                        <strong><?php echo htmlspecialchars($item['nombre']); ?></strong>
                                        <br>
                                        <small class="text-muted">
                                            Cantidad: <?php echo $item['cantidad']; ?> × $<?php echo number_format($item['precio'], 0); ?>
                                        </small>
                                    </div>
                                    <strong>$<?php echo number_format($item['subtotal'], 0); ?></strong>
                                </div>
                                <?php endforeach; ?>
                            </div>
                            
                            <hr>
                            
                            <div class="d-flex justify-content-between mb-2">
                                <span>Subtotal:</span>
                                <strong>$<?php echo number_format($subtotal, 0); ?></strong>
                            </div>
                            
                            <?php if ($impuesto > 0): ?>
                            <div class="d-flex justify-content-between mb-2">
                                <span>Impuestos:</span>
                                <strong>$<?php echo number_format($impuesto, 0); ?></strong>
                            </div>
                            <?php endif; ?>
                            
                            <div class="d-flex justify-content-between mb-2">
                                <span>Envío:</span>
                                <strong class="text-success">GRATIS</strong>
                            </div>
                            
                            <hr>
                            
                            <div class="d-flex justify-content-between mb-4" style="font-size: 1.3rem;">
                                <strong>Total:</strong>
                                <strong style="color: var(--primary-color);">$<?php echo number_format($total, 0); ?></strong>
                            </div>
                            
                            <button type="submit" class="btn btn-finalizar">
                                <i class="bi bi-check-circle"></i> Confirmar Pedido
                            </button>
                            
                            <a href="carrito.php" class="btn btn-outline-secondary w-100 mt-2">
                                <i class="bi bi-arrow-left"></i> Volver al Carrito
                            </a>
                            
                            <div class="text-center mt-3">
                                <small class="text-muted">
                                    <i class="bi bi-shield-check text-success"></i> 
                                    Compra 100% segura
                                </small>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    <?php endif; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function selectPayment(element, value) {
            // Remover selección anterior
            document.querySelectorAll('.payment-option').forEach(opt => {
                opt.classList.remove('selected');
            });
            
            // Marcar como seleccionado
            element.classList.add('selected');
            document.getElementById('pago_' + value).checked = true;
        }
        
        // Validar formulario
        document.getElementById('formCheckout')?.addEventListener('submit', function(e) {
            const metodoPago = document.querySelector('input[name="metodo_pago"]:checked');
            const direccion = document.querySelector('textarea[name="direccion_entrega"]').value;
            
            if (!metodoPago) {
                e.preventDefault();
                alert('Por favor selecciona un método de pago');
                return false;
            }
            
            if (!direccion.trim()) {
                e.preventDefault();
                alert('Por favor ingresa una dirección de entrega');
                return false;
            }
            
            // Confirmar antes de procesar
            if (!confirm('¿Confirmas que todos los datos son correctos?')) {
                e.preventDefault();
                return false;
            }
        });
    </script>
</body>
</html>