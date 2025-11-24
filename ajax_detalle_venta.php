<?php
/**
 * AJAX - DETALLE DE VENTA
 * Carga los detalles de una venta específica
 */

require_once 'config/config.php';

// Verificar que sea admin
if (!estaLogueado() || !esAdmin()) {
    die('<div class="alert alert-danger">Acceso denegado</div>');
}

$venta_id = (int)($_GET['id'] ?? 0);

if ($venta_id <= 0) {
    die('<div class="alert alert-danger">ID de venta inválido</div>');
}

$db = getDB();

// OBTENER INFORMACIÓN DE LA VENTA
$stmt = $db->prepare("SELECT v.*, u.nombre, u.apellido, u.email, u.telefono 
                      FROM ventas v
                      INNER JOIN usuarios u ON v.usuario_id = u.id
                      WHERE v.id = ?");
$stmt->bind_param("i", $venta_id);
$stmt->execute();
$result = $stmt->get_result();
$venta = $result->fetch_assoc();
$stmt->close();

if (!$venta) {
    die('<div class="alert alert-danger">Venta no encontrada</div>');
}

// OBTENER DETALLE DE PRODUCTOS
$stmt = $db->prepare("SELECT dv.*, p.nombre as producto_nombre, p.codigo_producto
                      FROM detalle_ventas dv
                      INNER JOIN productos p ON dv.producto_id = p.id
                      WHERE dv.venta_id = ?");
$stmt->bind_param("i", $venta_id);
$stmt->execute();
$result = $stmt->get_result();
$detalles = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Estados con colores
$estados_colores = [
    'pendiente' => 'warning',
    'procesando' => 'info',
    'completada' => 'success',
    'cancelada' => 'danger'
];
$color_estado = $estados_colores[$venta['estado']] ?? 'secondary';
?>

<div class="row">
    <!-- Información del Cliente -->
    <div class="col-md-6 mb-3">
        <div class="card">
            <div class="card-header bg-light">
                <strong><i class="bi bi-person"></i> Información del Cliente</strong>
            </div>
            <div class="card-body">
                <p class="mb-2">
                    <strong>Nombre:</strong> 
                    <?php echo htmlspecialchars($venta['nombre'] . ' ' . $venta['apellido']); ?>
                </p>
                <p class="mb-2">
                    <strong>Email:</strong> 
                    <?php echo htmlspecialchars($venta['email']); ?>
                </p>
                <p class="mb-2">
                    <strong>Teléfono:</strong> 
                    <?php echo htmlspecialchars($venta['telefono'] ?? 'N/A'); ?>
                </p>
            </div>
        </div>
    </div>

    <!-- Información de la Venta -->
    <div class="col-md-6 mb-3">
        <div class="card">
            <div class="card-header bg-light">
                <strong><i class="bi bi-receipt"></i> Información de la Venta</strong>
            </div>
            <div class="card-body">
                <p class="mb-2">
                    <strong>Orden:</strong> 
                    #<?php echo str_pad($venta['id'], 6, '0', STR_PAD_LEFT); ?>
                </p>
                <p class="mb-2">
                    <strong>Fecha:</strong> 
                    <?php echo date('d/m/Y H:i', strtotime($venta['fecha_venta'])); ?>
                </p>
                <p class="mb-2">
                    <strong>Estado:</strong> 
                    <span class="badge bg-<?php echo $color_estado; ?>">
                        <?php echo ucfirst($venta['estado']); ?>
                    </span>
                </p>
                <p class="mb-2">
                    <strong>Método de Pago:</strong> 
                    <?php echo ucfirst($venta['metodo_pago']); ?>
                </p>
            </div>
        </div>
    </div>

    <!-- Dirección de Entrega -->
    <?php if (!empty($venta['direccion_entrega'])): ?>
    <div class="col-12 mb-3">
        <div class="card">
            <div class="card-header bg-light">
                <strong><i class="bi bi-geo-alt"></i> Dirección de Entrega</strong>
            </div>
            <div class="card-body">
                <p class="mb-0"><?php echo nl2br(htmlspecialchars($venta['direccion_entrega'])); ?></p>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Notas -->
    <?php if (!empty($venta['notas'])): ?>
    <div class="col-12 mb-3">
        <div class="card">
            <div class="card-header bg-light">
                <strong><i class="bi bi-sticky"></i> Notas Adicionales</strong>
            </div>
            <div class="card-body">
                <p class="mb-0"><?php echo nl2br(htmlspecialchars($venta['notas'])); ?></p>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Productos -->
    <div class="col-12 mb-3">
        <div class="card">
            <div class="card-header bg-light">
                <strong><i class="bi bi-box-seam"></i> Productos</strong>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Producto</th>
                                <th>Código</th>
                                <th class="text-center">Cantidad</th>
                                <th class="text-end">Precio Unit.</th>
                                <th class="text-end">Subtotal</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($detalles as $detalle): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($detalle['producto_nombre']); ?></td>
                                <td><code><?php echo htmlspecialchars($detalle['codigo_producto'] ?? 'N/A'); ?></code></td>
                                <td class="text-center">
                                    <span class="badge bg-primary"><?php echo $detalle['cantidad']; ?></span>
                                </td>
                                <td class="text-end">$<?php echo number_format($detalle['precio_unitario'], 0); ?></td>
                                <td class="text-end">
                                    <strong>$<?php echo number_format($detalle['subtotal'], 0); ?></strong>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Resumen de Totales -->
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <div class="row">
                    <div class="col-md-8 offset-md-4">
                        <div class="d-flex justify-content-between mb-2">
                            <span>Subtotal:</span>
                            <strong>$<?php echo number_format($venta['subtotal'], 0); ?></strong>
                        </div>
                        
                        <?php if ($venta['impuesto'] > 0): ?>
                        <div class="d-flex justify-content-between mb-2">
                            <span>Impuestos:</span>
                            <strong>$<?php echo number_format($venta['impuesto'], 0); ?></strong>
                        </div>
                        <?php endif; ?>
                        
                        <div class="d-flex justify-content-between mb-2">
                            <span>Envío:</span>
                            <strong class="text-success">GRATIS</strong>
                        </div>
                        
                        <hr>
                        
                        <div class="d-flex justify-content-between" style="font-size: 1.3rem;">
                            <strong>TOTAL:</strong>
                            <strong style="color: #667eea;">$<?php echo number_format($venta['total'], 0); ?></strong>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .card-header {
        border-bottom: 2px solid #667eea;
    }
</style>