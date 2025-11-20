<?php
/**
 * GESTIÓN DE VENTAS
 * Panel Administrativo - Desechables Punto Fijo
 */

require_once 'config.php';
requerirLogin();
requerirAdmin();

$db = getDB();
$mensaje = '';
$tipo_mensaje = '';

// PROCESAR ACCIONES
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $accion = $_POST['accion'] ?? '';
    
    // CAMBIAR ESTADO DE VENTA
    if ($accion === 'cambiar_estado') {
        $venta_id = (int)$_POST['venta_id'];
        $nuevo_estado = sanitize($_POST['nuevo_estado']);
        
        $stmt = $db->prepare("UPDATE ventas SET estado = ? WHERE id = ?");
        $stmt->bind_param("si", $nuevo_estado, $venta_id);
        
        if ($stmt->execute()) {
            $mensaje = "Estado actualizado exitosamente";
            $tipo_mensaje = "success";
        } else {
            $mensaje = "Error al actualizar estado: " . $stmt->error;
            $tipo_mensaje = "danger";
        }
        $stmt->close();
    }
}

// FILTROS
$busqueda = $_GET['buscar'] ?? '';
$estado_filtro = $_GET['estado'] ?? '';
$fecha_inicio = $_GET['fecha_inicio'] ?? '';
$fecha_fin = $_GET['fecha_fin'] ?? '';
$orden = $_GET['orden'] ?? 'fecha_venta DESC';

// OBTENER VENTAS
$sql = "SELECT v.*, 
        u.nombre, u.apellido, u.email,
        COUNT(dv.id) as total_productos
        FROM ventas v
        INNER JOIN usuarios u ON v.usuario_id = u.id
        LEFT JOIN detalle_ventas dv ON v.id = dv.venta_id
        WHERE 1=1";

if (!empty($busqueda)) {
    $sql .= " AND (u.nombre LIKE '%" . $db->real_escape_string($busqueda) . "%' 
              OR u.apellido LIKE '%" . $db->real_escape_string($busqueda) . "%'
              OR u.email LIKE '%" . $db->real_escape_string($busqueda) . "%'
              OR v.id = '" . $db->real_escape_string($busqueda) . "')";
}

if (!empty($estado_filtro)) {
    $sql .= " AND v.estado = '" . $db->real_escape_string($estado_filtro) . "'";
}

if (!empty($fecha_inicio)) {
    $sql .= " AND DATE(v.fecha_venta) >= '" . $db->real_escape_string($fecha_inicio) . "'";
}

if (!empty($fecha_fin)) {
    $sql .= " AND DATE(v.fecha_venta) <= '" . $db->real_escape_string($fecha_fin) . "'";
}

$sql .= " GROUP BY v.id ORDER BY " . $db->real_escape_string($orden);

$result = $db->query($sql);
$ventas = $result->fetch_all(MYSQLI_ASSOC);

// ESTADÍSTICAS
$stats_total = $db->query("SELECT COUNT(*) as total FROM ventas")->fetch_assoc()['total'];
$stats_pendientes = $db->query("SELECT COUNT(*) as total FROM ventas WHERE estado = 'pendiente'")->fetch_assoc()['total'];
$stats_completadas = $db->query("SELECT COUNT(*) as total FROM ventas WHERE estado = 'completada'")->fetch_assoc()['total'];
$stats_ingresos = $db->query("SELECT COALESCE(SUM(total), 0) as total FROM ventas WHERE estado = 'completada'")->fetch_assoc()['total'];

// Ventas del mes actual
$stats_mes = $db->query("SELECT COUNT(*) as total, COALESCE(SUM(total), 0) as ingresos 
                         FROM ventas 
                         WHERE MONTH(fecha_venta) = MONTH(CURRENT_DATE()) 
                         AND YEAR(fecha_venta) = YEAR(CURRENT_DATE())")->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Ventas - Admin</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    
    <style>
        :root {
            --sidebar-width: 250px;
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
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            overflow-y: auto;
            z-index: 1000;
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
            height: 100%;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.15);
        }
        
        .stat-card.primary { border-left-color: #667eea; }
        .stat-card.success { border-left-color: #10b981; }
        .stat-card.warning { border-left-color: #f59e0b; }
        .stat-card.info { border-left-color: #3b82f6; }
        
        .venta-card {
            background: white;
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 15px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
            transition: all 0.3s;
        }
        
        .venta-card:hover {
            box-shadow: 0 5px 20px rgba(0,0,0,0.15);
            transform: translateX(5px);
        }
        
        .estado-badge {
            padding: 6px 15px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: bold;
        }
        
        .btn-gradient {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
        }
        
        .btn-gradient:hover {
            background: linear-gradient(135deg, #764ba2 0%, #667eea 100%);
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.3);
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
            <li><a href="admin.php"><i class="bi bi-speedometer2"></i> Dashboard</a></li>
            <li><a href="admin_productos.php"><i class="bi bi-box-seam"></i> Productos</a></li>
            <li><a href="admin_categorias.php"><i class="bi bi-tags"></i> Categorías</a></li>
            <li><a href="admin_ventas.php" class="active"><i class="bi bi-cart-check"></i> Ventas</a></li>
            <li><a href="admin_usuarios.php"><i class="bi bi-people"></i> Usuarios</a></li>
            <li style="margin-top: 50px;"><a href="index.php?ver_tienda=1"><i class="bi bi-house"></i> Ir a la Tienda</a></li>
            <li><a href="logout.php" class="text-danger"><i class="bi bi-box-arrow-right"></i> Cerrar Sesión</a></li>
        </ul>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2><i class="bi bi-cart-check"></i> Gestión de Ventas</h2>
                <p class="text-muted">Administra y monitorea todas las ventas</p>
            </div>
        </div>

        <?php if ($mensaje): ?>
        <div class="alert alert-<?php echo $tipo_mensaje; ?> alert-dismissible fade show" role="alert">
            <i class="bi bi-<?php echo $tipo_mensaje === 'success' ? 'check-circle' : 'exclamation-triangle'; ?>"></i>
            <?php echo $mensaje; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>

        <!-- Estadísticas -->
        <div class="row g-4 mb-4">
            <div class="col-md-3">
                <div class="stat-card primary">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <p class="text-muted mb-1">Total Ventas</p>
                            <h3 class="mb-0"><?php echo number_format($stats_total); ?></h3>
                        </div>
                        <i class="bi bi-receipt" style="font-size: 2.5rem; color: #667eea; opacity: 0.3;"></i>
                    </div>
                </div>
            </div>
            
            <div class="col-md-3">
                <div class="stat-card warning">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <p class="text-muted mb-1">Pendientes</p>
                            <h3 class="mb-0"><?php echo number_format($stats_pendientes); ?></h3>
                        </div>
                        <i class="bi bi-clock-history" style="font-size: 2.5rem; color: #f59e0b; opacity: 0.3;"></i>
                    </div>
                </div>
            </div>
            
            <div class="col-md-3">
                <div class="stat-card success">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <p class="text-muted mb-1">Completadas</p>
                            <h3 class="mb-0"><?php echo number_format($stats_completadas); ?></h3>
                        </div>
                        <i class="bi bi-check-circle" style="font-size: 2.5rem; color: #10b981; opacity: 0.3;"></i>
                    </div>
                </div>
            </div>
            
            <div class="col-md-3">
                <div class="stat-card info">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <p class="text-muted mb-1">Ingresos Totales</p>
                            <h3 class="mb-0">$<?php echo number_format($stats_ingresos, 0); ?></h3>
                        </div>
                        <i class="bi bi-currency-dollar" style="font-size: 2.5rem; color: #3b82f6; opacity: 0.3;"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Estadísticas del Mes -->
        <div class="row g-4 mb-4">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">
                            <i class="bi bi-calendar-month text-primary"></i> Ventas del Mes Actual
                        </h5>
                        <div class="row mt-3">
                            <div class="col-6">
                                <div class="text-center">
                                    <h2 class="text-primary"><?php echo $stats_mes['total']; ?></h2>
                                    <p class="text-muted mb-0">Ventas</p>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="text-center">
                                    <h2 class="text-success">$<?php echo number_format($stats_mes['ingresos'], 0); ?></h2>
                                    <p class="text-muted mb-0">Ingresos</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filtros -->
        <div class="card mb-4">
            <div class="card-body">
                <form method="GET" class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label">Buscar</label>
                        <input type="text" name="buscar" class="form-control" 
                               placeholder="Cliente, email o ID..." 
                               value="<?php echo htmlspecialchars($busqueda); ?>">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Estado</label>
                        <select name="estado" class="form-select">
                            <option value="">Todos</option>
                            <option value="pendiente" <?php echo $estado_filtro === 'pendiente' ? 'selected' : ''; ?>>Pendiente</option>
                            <option value="procesando" <?php echo $estado_filtro === 'procesando' ? 'selected' : ''; ?>>Procesando</option>
                            <option value="completada" <?php echo $estado_filtro === 'completada' ? 'selected' : ''; ?>>Completada</option>
                            <option value="cancelada" <?php echo $estado_filtro === 'cancelada' ? 'selected' : ''; ?>>Cancelada</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Desde</label>
                        <input type="date" name="fecha_inicio" class="form-control" value="<?php echo $fecha_inicio; ?>">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Hasta</label>
                        <input type="date" name="fecha_fin" class="form-control" value="<?php echo $fecha_fin; ?>">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Ordenar por</label>
                        <select name="orden" class="form-select">
                            <option value="fecha_venta DESC">Más recientes</option>
                            <option value="fecha_venta ASC">Más antiguas</option>
                            <option value="total DESC">Mayor monto</option>
                            <option value="total ASC">Menor monto</option>
                        </select>
                    </div>
                    <div class="col-md-1">
                        <label class="form-label">&nbsp;</label>
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="bi bi-search"></i>
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Lista de Ventas -->
        <div class="card">
            <div class="card-header bg-white">
                <h5 class="mb-0">Lista de Ventas (<?php echo count($ventas); ?>)</h5>
            </div>
            <div class="card-body p-0">
                <?php if (empty($ventas)): ?>
                    <div class="text-center py-5">
                        <i class="bi bi-inbox" style="font-size: 4rem; color: #ddd;"></i>
                        <p class="text-muted mt-3">No se encontraron ventas</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($ventas as $venta): ?>
                    <div class="venta-card">
                        <div class="row align-items-center">
                            <div class="col-md-2">
                                <div class="text-center">
                                    <div class="badge bg-light text-dark mb-2" style="font-size: 1rem;">
                                        #<?php echo str_pad($venta['id'], 6, '0', STR_PAD_LEFT); ?>
                                    </div>
                                    <br>
                                    <small class="text-muted">
                                        <i class="bi bi-calendar"></i> 
                                        <?php echo date('d/m/Y', strtotime($venta['fecha_venta'])); ?>
                                    </small>
                                    <br>
                                    <small class="text-muted">
                                        <i class="bi bi-clock"></i> 
                                        <?php echo date('H:i', strtotime($venta['fecha_venta'])); ?>
                                    </small>
                                </div>
                            </div>
                            
                            <div class="col-md-3">
                                <strong><?php echo htmlspecialchars($venta['nombre'] . ' ' . $venta['apellido']); ?></strong>
                                <br>
                                <small class="text-muted">
                                    <i class="bi bi-envelope"></i> <?php echo htmlspecialchars($venta['email']); ?>
                                </small>
                                <br>
                                <small class="text-muted">
                                    <i class="bi bi-box"></i> <?php echo $venta['total_productos']; ?> producto(s)
                                </small>
                            </div>
                            
                            <div class="col-md-2">
                                <strong class="text-primary" style="font-size: 1.3rem;">
                                    $<?php echo number_format($venta['total'], 0); ?>
                                </strong>
                                <br>
                                <small class="text-muted">
                                    <i class="bi bi-wallet2"></i> 
                                    <?php echo ucfirst($venta['metodo_pago']); ?>
                                </small>
                            </div>
                            
                            <div class="col-md-2">
                                <?php
                                $estados_colores = [
                                    'pendiente' => 'warning',
                                    'procesando' => 'info',
                                    'completada' => 'success',
                                    'cancelada' => 'danger'
                                ];
                                $color = $estados_colores[$venta['estado']] ?? 'secondary';
                                ?>
                                <span class="estado-badge bg-<?php echo $color; ?> text-white">
                                    <i class="bi bi-circle-fill" style="font-size: 0.5rem;"></i>
                                    <?php echo ucfirst($venta['estado']); ?>
                                </span>
                            </div>
                            
                            <div class="col-md-3 text-end">
                                <div class="btn-group" role="group">
                                    <button class="btn btn-sm btn-outline-primary" 
                                            onclick="verDetalle(<?php echo $venta['id']; ?>)"
                                            title="Ver detalle">
                                        <i class="bi bi-eye"></i>
                                    </button>
                                    <?php if ($venta['estado'] !== 'completada' && $venta['estado'] !== 'cancelada'): ?>
                                    <button class="btn btn-sm btn-outline-success" 
                                            onclick="cambiarEstado(<?php echo $venta['id']; ?>, 'completada')"
                                            title="Marcar como completada">
                                        <i class="bi bi-check-circle"></i>
                                    </button>
                                    <button class="btn btn-sm btn-outline-danger" 
                                            onclick="cambiarEstado(<?php echo $venta['id']; ?>, 'cancelada')"
                                            title="Cancelar">
                                        <i class="bi bi-x-circle"></i>
                                    </button>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        
                        <?php if (!empty($venta['direccion_entrega'])): ?>
                        <div class="mt-2 pt-2 border-top">
                            <small class="text-muted">
                                <i class="bi bi-geo-alt"></i> 
                                <strong>Dirección:</strong> <?php echo htmlspecialchars($venta['direccion_entrega']); ?>
                            </small>
                        </div>
                        <?php endif; ?>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Modal Detalle Venta -->
    <div class="modal fade" id="modalDetalle" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="bi bi-receipt"></i> Detalle de Venta
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="contenidoDetalle">
                    <div class="text-center py-5">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Cargando...</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Form Cambiar Estado (oculto) -->
    <form method="POST" id="formEstado" style="display:none;">
        <input type="hidden" name="accion" value="cambiar_estado">
        <input type="hidden" name="venta_id" id="estado_venta_id">
        <input type="hidden" name="nuevo_estado" id="estado_valor">
    </form>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function verDetalle(ventaId) {
            const modal = new bootstrap.Modal(document.getElementById('modalDetalle'));
            modal.show();
            
            // Cargar detalle con AJAX
            fetch('ajax_detalle_venta.php?id=' + ventaId)
                .then(response => response.text())
                .then(html => {
                    document.getElementById('contenidoDetalle').innerHTML = html;
                })
                .catch(error => {
                    document.getElementById('contenidoDetalle').innerHTML = 
                        '<div class="alert alert-danger">Error al cargar el detalle</div>';
                });
        }
        
        function cambiarEstado(ventaId, estado) {
            const mensajes = {
                'completada': '¿Marcar esta venta como completada?',
                'cancelada': '¿Estás seguro de cancelar esta venta?',
                'procesando': '¿Cambiar estado a procesando?'
            };
            
            if (confirm(mensajes[estado])) {
                document.getElementById('estado_venta_id').value = ventaId;
                document.getElementById('estado_valor').value = estado;
                document.getElementById('formEstado').submit();
            }
        }
    </script>
</body>
</html>