<?php
/**
 * PANEL ADMINISTRATIVO
 * Desechables Punto Fijo
 */

require_once 'config/config.php';

// Verificar que esté logueado y sea admin
requerirLogin();
requerirAdmin();

$db = getDB();

// Obtener estadísticas
$stmt = $db->query("SELECT COUNT(*) as total FROM productos");
$total_productos = $stmt->fetch_assoc()['total'];

$stmt = $db->query("SELECT COUNT(*) as total FROM categorias");
$total_categorias = $stmt->fetch_assoc()['total'];

$stmt = $db->query("SELECT COUNT(*) as total FROM usuarios WHERE rol = 'cliente'");
$total_clientes = $stmt->fetch_assoc()['total'];

$stmt = $db->query("SELECT COUNT(*) as total FROM ventas");
$total_ventas = $stmt->fetch_assoc()['total'];

$stmt = $db->query("SELECT COALESCE(SUM(total), 0) as total FROM ventas WHERE estado = 'completada'");
$ingresos_totales = $stmt->fetch_assoc()['total'];

// Productos con bajo stock (menos de 50 unidades)
$stmt = $db->query("SELECT * FROM productos WHERE stock < 50 ORDER BY stock ASC LIMIT 5");
$productos_bajo_stock = $stmt->fetch_all(MYSQLI_ASSOC);

// Últimas ventas
$stmt = $db->query("SELECT v.*, u.nombre, u.apellido FROM ventas v 
                    INNER JOIN usuarios u ON v.usuario_id = u.id 
                    ORDER BY v.fecha_venta DESC LIMIT 5");
$ultimas_ventas = $stmt->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel Admin - Desechables Punto Fijo</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    
    <style>
        :root {
            --sidebar-width: 250px;
            --primary-gradient: linear-gradient(135deg, #4d4d4dff 0%, #000000ff 100%);
        }
        
        body {
            background: #f8f9fa;
        }
        
        .sidebar {
            position: fixed;
            top: 0;
            left: 0;
            height: 100vh;
            width: var(--sidebar-width);
            background: var(--primary-gradient);
            color: white;
            transition: all 0.3s;
            z-index: 1000;
            overflow-y: auto;
        }
        
        .sidebar-header {
            padding: 20px;
            text-align: center;
            border-bottom: 1px solid rgba(255,255,255,0.2);
        }
        
        .sidebar-menu {
            list-style: none;
            padding: 0;
            margin: 20px 0;
        }
        
        .sidebar-menu li {
            padding: 0;
        }
        
        .sidebar-menu a {
            display: block;
            padding: 15px 20px;
            color: white;
            text-decoration: none;
            transition: all 0.3s;
            border-left: 3px solid transparent;
        }
        
        .sidebar-menu a:hover,
        .sidebar-menu a.active {
            background: rgba(255,255,255,0.1);
            border-left-color: white;
        }
        
        .main-content {
            margin-left: var(--sidebar-width);
            padding: 30px;
            min-height: 100vh;
        }
        
        .stat-card {
            background: white;
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
            transition: transform 0.3s;
            border-left: 4px solid;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.15);
        }
        
        .stat-card.primary {
            border-left-color: #667eea;
        }
        
        .stat-card.success {
            border-left-color: #10b981;
        }
        
        .stat-card.warning {
            border-left-color: #f59e0b;
        }
        
        .stat-card.info {
            border-left-color: #3b82f6;
        }
        
        .stat-card.danger {
            border-left-color: #ef4444;
        }
        
        .stat-icon {
            width: 60px;
            height: 60px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.8rem;
        }
        
        .btn-gradient {
            background: var(--primary-gradient);
            color: white;
            border: none;
            transition: all 0.3s;
        }
        
        .btn-gradient:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.3);
            color: white;
        }
        
        .table-card {
            background: white;
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
            margin-top: 30px;
        }
        
        .badge-status {
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.85rem;
        }
        
        @media (max-width: 768px) {
            .sidebar {
                margin-left: calc(-1 * var(--sidebar-width));
            }
            
            .main-content {
                margin-left: 0;
            }
            
            .sidebar.active {
                margin-left: 0;
            }
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar">
        <div class="sidebar-header">
            <i class="bi bi-shop" style="font-size: 3rem;"></i>
            <h5 class="mt-2 mb-0">Panel Admin</h5>
            <small>Desechables Punto Fijo</small>
        </div>
        
        <ul class="sidebar-menu">
            <li>
                <a href="admin.php" class="active">
                    <i class="bi bi-speedometer2"></i> Dashboard
                </a>
            </li>
            <li>
                <a href="admin_productos.php">
                    <i class="bi bi-box-seam"></i> Productos
                </a>
            </li>
            <li>
                <a href="admin_categorias.php">
                    <i class="bi bi-tags"></i> Categorías
                </a>
            </li>
            <li>
                <a href="admin_ventas.php">
                    <i class="bi bi-cart-check"></i> Ventas
                </a>
            </li>
            <li>
                <a href="admin_usuarios.php">
                    <i class="bi bi-people"></i> Usuarios
                </a>
            </li>
            <li>
                <a href="admin_reportes.php">
                    <i class="bi bi-graph-up"></i> Reportes
                </a>
            </li>
            <li>
                <a href="admin_configuracion.php">
                    <i class="bi bi-gear"></i> Configuración
                </a>
            </li>
            <li style="margin-top: 50px;">
                <a href="index.php?ver_tienda=1">
                    <i class="bi bi-house"></i> Ir a la Tienda
                </a>
            </li>
            <li>
                <a href="logout.php" class="text-danger">
                    <i class="bi bi-box-arrow-right"></i> Cerrar Sesión
                </a>
            </li>
        </ul>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <!-- Header -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2 class="mb-0">Dashboard</h2>
                <p class="text-muted">Bienvenido, <?php echo $_SESSION['nombre']; ?> 👋</p>
            </div>
            <div>
                <button class="btn btn-gradient" data-bs-toggle="modal" data-bs-target="#modalAccionesRapidas">
                    <i class="bi bi-plus-circle"></i> Acciones Rápidas
                </button>
            </div>
        </div>

        <!-- Estadísticas -->
        <div class="row g-4 mb-4">
            <div class="col-md-3">
                <div class="stat-card primary">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <p class="text-muted mb-1">Total Productos</p>
                            <h3 class="mb-0"><?php echo number_format($total_productos); ?></h3>
                        </div>
                        <div class="stat-icon" style="background: rgba(102, 126, 234, 0.1); color: #667eea;">
                            <i class="bi bi-box-seam"></i>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-3">
                <div class="stat-card success">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <p class="text-muted mb-1">Total Ventas</p>
                            <h3 class="mb-0"><?php echo number_format($total_ventas); ?></h3>
                        </div>
                        <div class="stat-icon" style="background: rgba(16, 185, 129, 0.1); color: #10b981;">
                            <i class="bi bi-cart-check"></i>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-3">
                <div class="stat-card warning">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <p class="text-muted mb-1">Clientes</p>
                            <h3 class="mb-0"><?php echo number_format($total_clientes); ?></h3>
                        </div>
                        <div class="stat-icon" style="background: rgba(245, 158, 11, 0.1); color: #f59e0b;">
                            <i class="bi bi-people"></i>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-3">
                <div class="stat-card info">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <p class="text-muted mb-1">Ingresos Totales</p>
                            <h3 class="mb-0">$<?php echo number_format($ingresos_totales, 0, ',', '.'); ?></h3>
                        </div>
                        <div class="stat-icon" style="background: rgba(59, 130, 246, 0.1); color: #3b82f6;">
                            <i class="bi bi-currency-dollar"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Categorías -->
        <div class="row g-4 mb-4">
            <div class="col-md-3">
                <div class="stat-card danger">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <p class="text-muted mb-1">Categorías</p>
                            <h3 class="mb-0"><?php echo number_format($total_categorias); ?></h3>
                        </div>
                        <div class="stat-icon" style="background: rgba(239, 68, 68, 0.1); color: #ef4444;">
                            <i class="bi bi-tags"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Alertas y Tablas -->
        <div class="row">
            <!-- Productos con Bajo Stock -->
            <div class="col-md-6">
                <div class="table-card">
                    <h5 class="mb-3">
                        <i class="bi bi-exclamation-triangle text-warning"></i> 
                        Productos con Bajo Stock
                    </h5>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Producto</th>
                                    <th>Stock</th>
                                    <th>Estado</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (count($productos_bajo_stock) > 0): ?>
                                    <?php foreach ($productos_bajo_stock as $producto): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($producto['nombre']); ?></td>
                                        <td>
                                            <span class="badge bg-<?php echo $producto['stock'] < 20 ? 'danger' : 'warning'; ?>">
                                                <?php echo $producto['stock']; ?> unidades
                                            </span>
                                        </td>
                                        <td>
                                            <a href="admin_productos.php?id=<?php echo $producto['id']; ?>" class="btn btn-sm btn-outline-primary">
                                                Ver
                                            </a>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="3" class="text-center text-muted">
                                            <i class="bi bi-check-circle"></i> Todos los productos tienen stock suficiente
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Últimas Ventas -->
            <div class="col-md-6">
                <div class="table-card">
                    <h5 class="mb-3">
                        <i class="bi bi-clock-history text-primary"></i> 
                        Últimas Ventas
                    </h5>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Cliente</th>
                                    <th>Total</th>
                                    <th>Estado</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (count($ultimas_ventas) > 0): ?>
                                    <?php foreach ($ultimas_ventas as $venta): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($venta['nombre'] . ' ' . $venta['apellido']); ?></td>
                                        <td>$<?php echo number_format($venta['total'], 0, ',', '.'); ?></td>
                                        <td>
                                            <?php
                                            $estados = [
                                                'pendiente' => 'warning',
                                                'procesando' => 'info',
                                                'completada' => 'success',
                                                'cancelada' => 'danger'
                                            ];
                                            $color = $estados[$venta['estado']] ?? 'secondary';
                                            ?>
                                            <span class="badge bg-<?php echo $color; ?>">
                                                <?php echo ucfirst($venta['estado']); ?>
                                            </span>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="3" class="text-center text-muted">
                                            No hay ventas registradas
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Acciones Rápidas -->
    <div class="modal fade" id="modalAccionesRapidas" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Acciones Rápidas</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="d-grid gap-2">
                        <a href="admin_productos.php?accion=nuevo" class="btn btn-primary btn-lg">
                            <i class="bi bi-plus-circle"></i> Agregar Producto
                        </a>
                        <a href="admin_categorias.php?accion=nuevo" class="btn btn-success btn-lg">
                            <i class="bi bi-tag"></i> Nueva Categoría
                        </a>
                        <a href="admin_ventas.php" class="btn btn-info btn-lg">
                            <i class="bi bi-cart-plus"></i> Registrar Venta
                        </a>
                        <a href="admin_reportes.php" class="btn btn-warning btn-lg">
                            <i class="bi bi-file-earmark-bar-graph"></i> Ver Reportes
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Toggle sidebar en móvil
        document.addEventListener('DOMContentLoaded', function() {
            console.log('Panel Admin cargado correctamente');
        });
    </script>
</body>
</html>