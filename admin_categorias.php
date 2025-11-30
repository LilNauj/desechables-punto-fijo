<?php
/**
 * GESTIÓN DE CATEGORÍAS
 * CRUD Completo: Crear, Leer, Actualizar, Eliminar
 */

require_once 'config/config.php';
requerirLogin();
requerirAdmin();

$db = getDB();
$mensaje = '';
$tipo_mensaje = '';

// PROCESAR ACCIONES
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $accion = $_POST['accion'] ?? '';

    // CREAR CATEGORÍA
    if ($accion === 'crear') {
        $nombre = sanitize($_POST['nombre']);
        $descripcion = sanitize($_POST['descripcion']);

        // Verificar si ya existe
        $stmt = $db->prepare("SELECT id FROM categorias WHERE nombre = ?");
        $stmt->bind_param("s", $nombre);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $mensaje = "Ya existe una categoría con ese nombre";
            $tipo_mensaje = "warning";
        } else {
            $stmt = $db->prepare("INSERT INTO categorias (nombre, descripcion) VALUES (?, ?)");
            $stmt->bind_param("ss", $nombre, $descripcion);

            if ($stmt->execute()) {
                $mensaje = "Categoría creada exitosamente";
                $tipo_mensaje = "success";
            } else {
                $mensaje = "Error al crear categoría: " . $stmt->error;
                $tipo_mensaje = "danger";
            }
        }
        $stmt->close();
    }

    // ACTUALIZAR CATEGORÍA
    elseif ($accion === 'actualizar') {
        $id = (int) $_POST['id'];
        $nombre = sanitize($_POST['nombre']);
        $descripcion = sanitize($_POST['descripcion']);
        $estado = sanitize($_POST['estado']);

        // Verificar si otro registro ya tiene ese nombre
        $stmt = $db->prepare("SELECT id FROM categorias WHERE nombre = ? AND id != ?");
        $stmt->bind_param("si", $nombre, $id);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $mensaje = "Ya existe otra categoría con ese nombre";
            $tipo_mensaje = "warning";
        } else {
            $stmt = $db->prepare("UPDATE categorias SET nombre=?, descripcion=?, estado=? WHERE id=?");
            $stmt->bind_param("sssi", $nombre, $descripcion, $estado, $id);

            if ($stmt->execute()) {
                $mensaje = "Categoría actualizada exitosamente";
                $tipo_mensaje = "success";
            } else {
                $mensaje = "Error al actualizar categoría: " . $stmt->error;
                $tipo_mensaje = "danger";
            }
        }
        $stmt->close();
    }

    // ELIMINAR CATEGORÍA
    elseif ($accion === 'eliminar') {
        $id = (int) $_POST['id'];

        // Verificar si tiene productos
        $stmt = $db->prepare("SELECT COUNT(*) as total FROM productos WHERE categoria_id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();

        if ($row['total'] > 0) {
            $mensaje = "No se puede eliminar: la categoría tiene " . $row['total'] . " producto(s) asociado(s)";
            $tipo_mensaje = "warning";
        } else {
            $stmt = $db->prepare("DELETE FROM categorias WHERE id = ?");
            $stmt->bind_param("i", $id);

            if ($stmt->execute()) {
                $mensaje = "Categoría eliminada exitosamente";
                $tipo_mensaje = "success";
            } else {
                $mensaje = "Error al eliminar categoría: " . $stmt->error;
                $tipo_mensaje = "danger";
            }
        }
        $stmt->close();
    }
}

// OBTENER CATEGORÍAS CON CONTEO DE PRODUCTOS
$sql = "SELECT c.*, COUNT(p.id) as total_productos 
        FROM categorias c 
        LEFT JOIN productos p ON c.id = p.categoria_id 
        GROUP BY c.id 
        ORDER BY c.nombre ASC";
$result = $db->query($sql);
$categorias = $result->fetch_all(MYSQLI_ASSOC);

// OBTENER ESTADÍSTICAS
$total_categorias = count($categorias);
$categorias_activas = count(array_filter($categorias, function ($c) {
    return $c['estado'] === 'activo'; }));
$total_productos = array_sum(array_column($categorias, 'total_productos'));
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Categorías - Admin</title>

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
        }

        .stat-card {
            background: white;
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
            transition: transform 0.3s;
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
        }

        .categoria-card {
            background: white;
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
            transition: all 0.3s;
            border-left: 4px solid;
            margin-bottom: 20px;
        }

        .categoria-card:hover {
            transform: translateX(5px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
        }

        .categoria-card.activo {
            border-left-color: #10b981;
        }

        .categoria-card.inactivo {
            border-left-color: #ef4444;
            opacity: 0.7;
        }

        .categoria-icon {
            width: 60px;
            height: 60px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2rem;
            background: linear-gradient(135deg, #667eea22 0%, #764ba222 100%);
            color: #667eea;
        }

        .btn-gradient {
            background: linear-gradient(135deg, #7b7b7bff 0%, #000000ff 100%);
            color: white;
            border: none;
        }

        .btn-gradient:hover {
            background: linear-gradient(135deg, #7b7b7bff 0%, #000000ff 100%);
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.3);
        }

        .empty-state {
            text-align: center;
            padding: 60px 20px;
            background: white;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
        }

        .empty-state i {
            font-size: 5rem;
            color: #ddd;
            margin-bottom: 20px;
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
                <a href="admin.php">
                    <i class="bi bi-speedometer2"></i> Dashboard
                </a>
            </li>
            <li>
                <a href="admin_productos.php">
                    <i class="bi bi-box-seam"></i> Productos
                </a>
            </li>
            <li>
                <a href="admin_categorias.php" class="active">
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
                <a href="admin_configuraciones.php">
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
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2><i class="bi bi-tags"></i> Gestión de Categorías</h2>
                <p class="text-muted">Organiza tu catálogo de productos</p>
            </div>
            <button class="btn btn-gradient btn-lg" data-bs-toggle="modal" data-bs-target="#modalNuevaCategoria">
                <i class="bi bi-plus-circle"></i> Nueva Categoría
            </button>
        </div>

        <?php if ($mensaje): ?>
            <div class="alert alert-<?php echo $tipo_mensaje; ?> alert-dismissible fade show" role="alert">
                <i
                    class="bi bi-<?php echo $tipo_mensaje === 'success' ? 'check-circle' : ($tipo_mensaje === 'warning' ? 'exclamation-triangle' : 'x-circle'); ?>"></i>
                <?php echo $mensaje; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Estadísticas -->
        <div class="row g-4 mb-4">
            <div class="col-md-4">
                <div class="stat-card" style="border-left: 4px solid #667eea;">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <p class="text-muted mb-1">Total Categorías</p>
                            <h3 class="mb-0"><?php echo $total_categorias; ?></h3>
                        </div>
                        <div class="categoria-icon">
                            <i class="bi bi-tags"></i>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="stat-card" style="border-left: 4px solid #10b981;">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <p class="text-muted mb-1">Categorías Activas</p>
                            <h3 class="mb-0"><?php echo $categorias_activas; ?></h3>
                        </div>
                        <div class="categoria-icon" style="background: rgba(16, 185, 129, 0.1); color: #10b981;">
                            <i class="bi bi-check-circle"></i>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="stat-card" style="border-left: 4px solid #3b82f6;">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <p class="text-muted mb-1">Total Productos</p>
                            <h3 class="mb-0"><?php echo $total_productos; ?></h3>
                        </div>
                        <div class="categoria-icon" style="background: rgba(59, 130, 246, 0.1); color: #3b82f6;">
                            <i class="bi bi-box-seam"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Lista de Categorías -->
        <?php if (empty($categorias)): ?>
            <div class="empty-state">
                <i class="bi bi-tags"></i>
                <h4>No hay categorías creadas</h4>
                <p class="text-muted">Comienza creando tu primera categoría para organizar tus productos</p>
                <button class="btn btn-gradient btn-lg mt-3" data-bs-toggle="modal" data-bs-target="#modalNuevaCategoria">
                    <i class="bi bi-plus-circle"></i> Crear Primera Categoría
                </button>
            </div>
        <?php else: ?>
            <div class="row">
                <?php foreach ($categorias as $categoria): ?>
                    <div class="col-md-6">
                        <div class="categoria-card <?php echo $categoria['estado']; ?>">
                            <div class="d-flex align-items-start">
                                <div class="categoria-icon me-3">
                                    <i class="bi bi-tag"></i>
                                </div>

                                <div class="flex-grow-1">
                                    <div class="d-flex justify-content-between align-items-start mb-2">
                                        <div>
                                            <h5 class="mb-1"><?php echo htmlspecialchars($categoria['nombre']); ?></h5>
                                            <p class="text-muted mb-0 small">
                                                <?php echo htmlspecialchars($categoria['descripcion'] ?: 'Sin descripción'); ?>
                                            </p>
                                        </div>
                                        <span
                                            class="badge bg-<?php echo $categoria['estado'] === 'activo' ? 'success' : 'danger'; ?>">
                                            <?php echo ucfirst($categoria['estado']); ?>
                                        </span>
                                    </div>

                                    <div class="d-flex justify-content-between align-items-center mt-3">
                                        <div>
                                            <span class="badge bg-primary">
                                                <i class="bi bi-box"></i> <?php echo $categoria['total_productos']; ?>
                                                producto(s)
                                            </span>
                                            <small class="text-muted ms-2">
                                                <i class="bi bi-calendar"></i>
                                                <?php echo date('d/m/Y', strtotime($categoria['fecha_creacion'])); ?>
                                            </small>
                                        </div>

                                        <div class="btn-group" role="group">
                                            <button class="btn btn-sm btn-outline-primary"
                                                onclick="editarCategoria(<?php echo htmlspecialchars(json_encode($categoria)); ?>)"
                                                title="Editar">
                                                <i class="bi bi-pencil"></i>
                                            </button>
                                            <a href="admin_productos.php?categoria=<?php echo $categoria['id']; ?>"
                                                class="btn btn-sm btn-outline-info" title="Ver productos">
                                                <i class="bi bi-eye"></i>
                                            </a>
                                            <?php if ($categoria['total_productos'] == 0): ?>
                                                <button class="btn btn-sm btn-outline-danger"
                                                    onclick="eliminarCategoria(<?php echo $categoria['id']; ?>, '<?php echo htmlspecialchars($categoria['nombre']); ?>')"
                                                    title="Eliminar">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Modal Nueva Categoría -->
    <div class="modal fade" id="modalNuevaCategoria" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST">
                    <input type="hidden" name="accion" value="crear">
                    <div class="modal-header">
                        <h5 class="modal-title">
                            <i class="bi bi-plus-circle text-primary"></i> Nueva Categoría
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Nombre de la Categoría *</label>
                            <input type="text" name="nombre" class="form-control"
                                placeholder="Ej: Icopor, Contenedores..." required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Descripción</label>
                            <textarea name="descripcion" class="form-control" rows="3"
                                placeholder="Describe brevemente esta categoría..."></textarea>
                        </div>
                        <div class="alert alert-info mb-0">
                            <i class="bi bi-info-circle"></i>
                            <small>La categoría se creará como <strong>activa</strong> por defecto</small>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-gradient">
                            <i class="bi bi-save"></i> Crear Categoría
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal Editar Categoría -->
    <div class="modal fade" id="modalEditarCategoria" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST">
                    <input type="hidden" name="accion" value="actualizar">
                    <input type="hidden" name="id" id="edit_id">
                    <div class="modal-header">
                        <h5 class="modal-title">
                            <i class="bi bi-pencil text-warning"></i> Editar Categoría
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Nombre de la Categoría *</label>
                            <input type="text" name="nombre" id="edit_nombre" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Descripción</label>
                            <textarea name="descripcion" id="edit_descripcion" class="form-control" rows="3"></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Estado</label>
                            <select name="estado" id="edit_estado" class="form-select">
                                <option value="activo">Activo</option>
                                <option value="inactivo">Inactivo</option>
                            </select>
                            <small class="text-muted">
                                <i class="bi bi-info-circle"></i>
                                Las categorías inactivas no se mostrarán al crear productos
                            </small>
                        </div>
                        <div id="edit_productos_info" class="alert alert-warning" style="display:none;">
                            <i class="bi bi-exclamation-triangle"></i>
                            Esta categoría tiene <strong><span id="edit_total_productos"></span> producto(s)</strong>
                            asociado(s)
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-gradient">
                            <i class="bi bi-save"></i> Actualizar
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Form Eliminar (oculto) -->
    <form method="POST" id="formEliminar" style="display:none;">
        <input type="hidden" name="accion" value="eliminar">
        <input type="hidden" name="id" id="delete_id">
    </form>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function editarCategoria(categoria) {
            document.getElementById('edit_id').value = categoria.id;
            document.getElementById('edit_nombre').value = categoria.nombre;
            document.getElementById('edit_descripcion').value = categoria.descripcion || '';
            document.getElementById('edit_estado').value = categoria.estado;

            // Mostrar información de productos
            if (categoria.total_productos > 0) {
                document.getElementById('edit_total_productos').textContent = categoria.total_productos;
                document.getElementById('edit_productos_info').style.display = 'block';
            } else {
                document.getElementById('edit_productos_info').style.display = 'none';
            }

            new bootstrap.Modal(document.getElementById('modalEditarCategoria')).show();
        }

        function eliminarCategoria(id, nombre) {
            if (confirm('¿Estás seguro de eliminar la categoría "' + nombre + '"?\n\nEsta acción no se puede deshacer.')) {
                document.getElementById('delete_id').value = id;
                document.getElementById('formEliminar').submit();
            }
        }
    </script>
</body>

</html>