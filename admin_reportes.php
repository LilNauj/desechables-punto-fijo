<?php
/**
 * ADMIN - REPORTES
 * Desechables Punto Fijo
 */

require_once 'config/config.php';
requerirLogin();
requerirAdmin();

$db = getDB();

// -------------------------
// Filtros de fecha
// -------------------------
$hoy = date('Y-m-d');
$fecha_desde = $_GET['desde'] ?? date('Y-m-01'); // inicio de mes actual
$fecha_hasta = $_GET['hasta'] ?? $hoy;

$desde_sql = $fecha_desde . ' 00:00:00';
$hasta_sql = $fecha_hasta . ' 23:59:59';

// -------------------------
// EXPORTAR A "EXCEL" (CSV)
// -------------------------
if (isset($_GET['export']) && $_GET['export'] === 'ventas') {
    $stmt = $db->prepare("
        SELECT v.id,
               v.fecha_venta,
               CONCAT(u.nombre, ' ', u.apellido) AS cliente,
               v.total,
               v.metodo_pago,
               v.estado
        FROM ventas v
        INNER JOIN usuarios u ON v.usuario_id = u.id
        WHERE v.fecha_venta BETWEEN ? AND ?
        ORDER BY v.fecha_venta DESC
    ");
    $stmt->bind_param("ss", $desde_sql, $hasta_sql);
    $stmt->execute();
    $result = $stmt->get_result();

    header('Content-Type: text/csv; charset=utf-8');
    $nombre_archivo = 'reporte_ventas_' . str_replace('-', '', $fecha_desde) . '_a_' . str_replace('-', '', $fecha_hasta) . '.csv';
    header('Content-Disposition: attachment; filename="'.$nombre_archivo.'"');

    $output = fopen('php://output', 'w');

    // Cabeceras
    fputcsv($output, ['ID Venta', 'Fecha', 'Cliente', 'Total', 'Método de pago', 'Estado'], ';');

    while ($row = $result->fetch_assoc()) {
        fputcsv($output, [
            $row['id'],
            $row['fecha_venta'],
            $row['cliente'],
            $row['total'],
            $row['metodo_pago'],
            $row['estado']
        ], ';');
    }

    fclose($output);
    exit;
}

// -------------------------
// KPIs generales del período
// -------------------------
$stmt = $db->prepare("
    SELECT 
        COUNT(*) AS total_ventas,
        COALESCE(SUM(total), 0) AS total_ingresos,
        COALESCE(SUM(subtotal), 0) AS total_subtotal,
        COALESCE(SUM(impuesto), 0) AS total_impuesto
    FROM ventas
    WHERE fecha_venta BETWEEN ? AND ?
      AND estado <> 'cancelada'
");
$stmt->bind_param("ss", $desde_sql, $hasta_sql);
$stmt->execute();
$kpis = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Ventas por día
$stmt = $db->prepare("
    SELECT DATE(fecha_venta) AS fecha,
           COUNT(*) AS num_ventas,
           COALESCE(SUM(total), 0) AS total
    FROM ventas
    WHERE fecha_venta BETWEEN ? AND ?
      AND estado <> 'cancelada'
    GROUP BY DATE(fecha_venta)
    ORDER BY fecha ASC
");
$stmt->bind_param("ss", $desde_sql, $hasta_sql);
$stmt->execute();
$ventas_por_dia = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Ventas por método de pago
$stmt = $db->prepare("
    SELECT metodo_pago,
           COUNT(*) AS num_ventas,
           COALESCE(SUM(total), 0) AS total
    FROM ventas
    WHERE fecha_venta BETWEEN ? AND ?
      AND estado <> 'cancelada'
    GROUP BY metodo_pago
    ORDER BY total DESC
");
$stmt->bind_param("ss", $desde_sql, $hasta_sql);
$stmt->execute();
$ventas_por_pago = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Ventas por estado
$stmt = $db->prepare("
    SELECT estado,
           COUNT(*) AS num_ventas
    FROM ventas
    WHERE fecha_venta BETWEEN ? AND ?
    GROUP BY estado
");
$stmt->bind_param("ss", $desde_sql, $hasta_sql);
$stmt->execute();
$ventas_por_estado = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Top productos
$stmt = $db->prepare("
    SELECT p.nombre,
           SUM(d.cantidad) AS unidades,
           COALESCE(SUM(d.subtotal), 0) AS total
    FROM detalle_ventas d
    INNER JOIN productos p ON d.producto_id = p.id
    INNER JOIN ventas v ON d.venta_id = v.id
    WHERE v.fecha_venta BETWEEN ? AND ?
      AND v.estado <> 'cancelada'
    GROUP BY d.producto_id
    ORDER BY total DESC
    LIMIT 10
");
$stmt->bind_param("ss", $desde_sql, $hasta_sql);
$stmt->execute();
$top_productos = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reportes - Desechables Punto Fijo</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">

    <style>
        :root {
            --sidebar-width: 250px;
        }

        body {
            font-family: system-ui, -apple-system, "Segoe UI", sans-serif;
            background: #f8f9fa;
        }

        .sidebar {
            position: fixed;
            top: 0;
            left: 0;
            width: var(--sidebar-width);
            height: 100vh;
            background: #111827;
            color: #e5e7eb;
            padding-top: 20px;
        }

        .sidebar-header {
            padding: 20px;
            text-align: center;
            border-bottom: 1px solid rgba(255, 255, 255, 0.2);
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
            background: rgba(255, 255, 255, 0.1);
            border-left-color: white;
        }


        .main-content {
            margin-left: var(--sidebar-width);
            padding: 30px;
            min-height: 100vh;
            background: #f8f9fa;
        }

        .stat-card {
            background: #ffffff;
            border-radius: 16px;
            padding: 18px 20px;
            box-shadow: 0 6px 18px rgba(15, 23, 42, 0.08);
            border: 1px solid #e5e7eb;
        }

        .stat-label {
            font-size: .9rem;
            color: #6b7280;
        }

        .stat-value {
            font-size: 1.4rem;
            font-weight: 600;
        }

        .table-card {
            background: #ffffff;
            border-radius: 16px;
            padding: 18px 20px;
            box-shadow: 0 6px 18px rgba(15, 23, 42, 0.08);
            border: 1px solid #e5e7eb;
        }

        .badge-estado-pendiente  { background:#fef3c7; color:#92400e; }
        .badge-estado-procesando { background:#e0f2fe; color:#075985; }
        .badge-estado-completada { background:#dcfce7; color:#166534; }
        .badge-estado-cancelada  { background:#fee2e2; color:#991b1b; }

        @media (max-width: 768px) {
            .sidebar {
                position: static;
                width: 100%;
                height: auto;
            }

            .main-content {
                margin-left: 0;
                padding: 15px;
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
            <li><a href="admin.php"><i class="bi bi-speedometer2"></i> Dashboard</a></li>
            <li><a href="admin_productos.php"><i class="bi bi-box-seam"></i> Productos</a></li>
            <li><a href="admin_categorias.php"><i class="bi bi-tags"></i> Categorías</a></li>
            <li><a href="admin_ventas.php"><i class="bi bi-cart-check"></i> Ventas</a></li>
            <li><a href="admin_usuarios.php"><i class="bi bi-people"></i> Usuarios</a></li>
            <li><a href="admin_reportes.php" class="active"><i class="bi bi-graph-up-arrow"></i> Reportes</a></li>
            <li><a href="admin_configuraciones.php"><i class="bi bi-sliders"></i> Configuración</a></li>
            <li style="margin-top: 50px;"><a href="index.php?ver_tienda=1"><i class="bi bi-house"></i> Ir a la Tienda</a></li>
            <li><a href="logout.php" class="text-danger"><i class="bi bi-box-arrow-right"></i> Cerrar Sesión</a></li>
        </ul>
    </div>

    <!-- Contenido principal -->
    <div class="main-content">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2 class="mb-0"><i class="bi bi-graph-up-arrow"></i> Reportes</h2>
                <p class="text-muted mb-0">Resumen económico del negocio con filtros por fechas.</p>
            </div>
        </div>

        <!-- Filtro de fechas + export -->
        <form method="GET" class="row g-3 mb-4 align-items-end">
            <div class="col-md-3">
                <label class="form-label">Desde</label>
                <input type="date" name="desde" class="form-control"
                       value="<?php echo htmlspecialchars($fecha_desde); ?>">
            </div>
            <div class="col-md-3">
                <label class="form-label">Hasta</label>
                <input type="date" name="hasta" class="form-control"
                       value="<?php echo htmlspecialchars($fecha_hasta); ?>">
            </div>
            <div class="col-md-6 d-flex gap-2">
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-funnel"></i> Aplicar filtros
                </button>
                <button type="submit" name="export" value="ventas" class="btn btn-success">
                    <i class="bi bi-file-earmark-excel"></i> Exportar a Excel
                </button>
            </div>
        </form>

        <!-- KPIs -->
        <div class="row g-3 mb-4">
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="stat-label">Total de ventas</div>
                    <div class="stat-value"><?php echo number_format($kpis['total_ventas']); ?></div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="stat-label">Ingresos (total)</div>
                    <div class="stat-value">$<?php echo number_format($kpis['total_ingresos'], 0, ',', '.'); ?></div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="stat-label">Subtotal acumulado</div>
                    <div class="stat-value">$<?php echo number_format($kpis['total_subtotal'], 0, ',', '.'); ?></div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="stat-label">Impuestos cobrados</div>
                    <div class="stat-value">$<?php echo number_format($kpis['total_impuesto'], 0, ',', '.'); ?></div>
                </div>
            </div>
        </div>

        <div class="row g-4">
            <!-- Ventas por día -->
            <div class="col-lg-6">
                <div class="table-card">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <h5 class="mb-0"><i class="bi bi-calendar2-week"></i> Ventas por día</h5>
                    </div>
                    <div class="table-responsive" style="max-height: 350px;">
                        <table class="table table-sm align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>Fecha</th>
                                    <th># Ventas</th>
                                    <th>Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($ventas_por_dia)): ?>
                                    <?php foreach ($ventas_por_dia as $fila): ?>
                                        <tr>
                                            <td><?php echo date('d/m/Y', strtotime($fila['fecha'])); ?></td>
                                            <td><?php echo $fila['num_ventas']; ?></td>
                                            <td>$<?php echo number_format($fila['total'], 0, ',', '.'); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="3" class="text-center text-muted">
                                            No hay ventas en el rango seleccionado.
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Ventas por método de pago -->
            <div class="col-lg-6">
                <div class="table-card">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <h5 class="mb-0"><i class="bi bi-wallet2"></i> Ventas por método de pago</h5>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-sm align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>Método</th>
                                    <th># Ventas</th>
                                    <th>Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($ventas_por_pago)): ?>
                                    <?php foreach ($ventas_por_pago as $fila): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($fila['metodo_pago'] ?: 'Sin especificar'); ?></td>
                                            <td><?php echo $fila['num_ventas']; ?></td>
                                            <td>$<?php echo number_format($fila['total'], 0, ',', '.'); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="3" class="text-center text-muted">
                                            No hay ventas en el rango seleccionado.
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Segunda fila: estados y top productos -->
        <div class="row g-4 mt-1">
            <!-- Ventas por estado -->
            <div class="col-lg-4">
                <div class="table-card">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <h5 class="mb-0"><i class="bi bi-flag"></i> Ventas por estado</h5>
                    </div>
                    <ul class="list-group list-group-flush">
                        <?php if (!empty($ventas_por_estado)): ?>
                            <?php foreach ($ventas_por_estado as $fila): ?>
                                <?php
                                    $estado = $fila['estado'];
                                    $map = [
                                        'pendiente'  => 'badge-estado-pendiente',
                                        'procesando' => 'badge-estado-procesando',
                                        'completada' => 'badge-estado-completada',
                                        'cancelada'  => 'badge-estado-cancelada',
                                    ];
                                    $clase = $map[$estado] ?? 'badge bg-secondary';
                                ?>
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    <span><?php echo ucfirst($estado); ?></span>
                                    <span class="badge <?php echo $clase; ?>">
                                        <?php echo $fila['num_ventas']; ?>
                                    </span>
                                </li>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <li class="list-group-item text-center text-muted">
                                No hay datos en el rango seleccionado.
                            </li>
                        <?php endif; ?>
                    </ul>
                </div>
            </div>

            <!-- Top productos -->
            <div class="col-lg-8">
                <div class="table-card">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <h5 class="mb-0"><i class="bi bi-star"></i> Top 10 productos</h5>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-sm align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>Producto</th>
                                    <th>Unidades vendidas</th>
                                    <th>Total vendido</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($top_productos)): ?>
                                    <?php foreach ($top_productos as $prod): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($prod['nombre']); ?></td>
                                            <td><?php echo (int)$prod['unidades']; ?></td>
                                            <td>$<?php echo number_format($prod['total'], 0, ',', '.'); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="3" class="text-center text-muted">
                                            No hay ventas de productos en el rango seleccionado.
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

    </div><!-- /.main-content -->

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
