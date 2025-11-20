<?php
/**
 * GESTIÓN DE PRODUCTOS
 * CRUD Completo: Crear, Leer, Actualizar, Eliminar
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
    
    // CREAR PRODUCTO
    if ($accion === 'crear') {
        $categoria_id = (int)$_POST['categoria_id'];
        $nombre = sanitize($_POST['nombre']);
        $descripcion = sanitize($_POST['descripcion']);
        $precio = (float)$_POST['precio'];
        $stock = (int)$_POST['stock'];
        $codigo_producto = sanitize($_POST['codigo_producto']);
        $unidad_medida = sanitize($_POST['unidad_medida']);
        $destacado = isset($_POST['destacado']) ? 1 : 0;
        
        $stmt = $db->prepare("INSERT INTO productos (categoria_id, nombre, descripcion, precio, stock, codigo_producto, unidad_medida, destacado) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("isssissi", $categoria_id, $nombre, $descripcion, $precio, $stock, $codigo_producto, $unidad_medida, $destacado);
        
        if ($stmt->execute()) {
            $mensaje = "Producto creado exitosamente";
            $tipo_mensaje = "success";
        } else {
            $mensaje = "Error al crear producto: " . $stmt->error;
            $tipo_mensaje = "danger";
        }
        $stmt->close();
    }
    
    // ACTUALIZAR PRODUCTO
    elseif ($accion === 'actualizar') {
        $id = (int)$_POST['id'];
        $categoria_id = (int)$_POST['categoria_id'];
        $nombre = sanitize($_POST['nombre']);
        $descripcion = sanitize($_POST['descripcion']);
        $precio = (float)$_POST['precio'];
        $stock = (int)$_POST['stock'];
        $codigo_producto = sanitize($_POST['codigo_producto']);
        $unidad_medida = sanitize($_POST['unidad_medida']);
        $estado = sanitize($_POST['estado']);
        $destacado = isset($_POST['destacado']) ? 1 : 0;
        
        $stmt = $db->prepare("UPDATE productos SET categoria_id=?, nombre=?, descripcion=?, precio=?, stock=?, codigo_producto=?, unidad_medida=?, estado=?, destacado=? WHERE id=?");
        $stmt->bind_param("isssisssii", $categoria_id, $nombre, $descripcion, $precio, $stock, $codigo_producto, $unidad_medida, $estado, $destacado, $id);
        
        if ($stmt->execute()) {
            $mensaje = "Producto actualizado exitosamente";
            $tipo_mensaje = "success";
        } else {
            $mensaje = "Error al actualizar producto: " . $stmt->error;
            $tipo_mensaje = "danger";
        }
        $stmt->close();
    }
    
    // ELIMINAR PRODUCTO
    elseif ($accion === 'eliminar') {
        $id = (int)$_POST['id'];
        $stmt = $db->prepare("DELETE FROM productos WHERE id = ?");
        $stmt->bind_param("i", $id);
        
        if ($stmt->execute()) {
            $mensaje = "Producto eliminado exitosamente";
            $tipo_mensaje = "success";
        } else {
            $mensaje = "Error al eliminar producto: " . $stmt->error;
            $tipo_mensaje = "danger";
        }
        $stmt->close();
    }
}

// OBTENER PRODUCTOS
$busqueda = $_GET['buscar'] ?? '';
$categoria_filtro = $_GET['categoria'] ?? '';
$orden = $_GET['orden'] ?? 'id DESC';

$sql = "SELECT p.*, c.nombre as categoria_nombre FROM productos p 
        LEFT JOIN categorias c ON p.categoria_id = c.id WHERE 1=1";

if (!empty($busqueda)) {
    $sql .= " AND (p.nombre LIKE '%" . $db->real_escape_string($busqueda) . "%' 
              OR p.codigo_producto LIKE '%" . $db->real_escape_string($busqueda) . "%')";
}

if (!empty($categoria_filtro)) {
    $sql .= " AND p.categoria_id = " . (int)$categoria_filtro;
}

$sql .= " ORDER BY " . $db->real_escape_string($orden);

$result = $db->query($sql);
$productos = $result->fetch_all(MYSQLI_ASSOC);

// OBTENER CATEGORÍAS
$categorias = $db->query("SELECT * FROM categorias WHERE estado = 'activo' ORDER BY nombre")->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Productos - Admin</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    
    <style>
        :root {
            --sidebar-width: 250px;
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
            background: #f8f9fa;
            min-height: 100vh;
        }
        
        .card-producto {
            transition: transform 0.3s, box-shadow 0.3s;
            border: none;
            border-radius: 10px;
        }
        
        .card-producto:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.15);
        }
        
        .producto-imagen {
            height: 150px;
            background: linear-gradient(135deg, #667eea33 0%, #764ba233 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 3rem;
            color: #667eea;
            border-radius: 10px 10px 0 0;
        }
        
        .btn-gradient {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
        }
        
        .btn-gradient:hover {
            background: linear-gradient(135deg, #764ba2 0%, #667eea 100%);
            color: white;
        }
        
        .stock-badge {
            position: absolute;
            top: 10px;
            right: 10px;
            z-index: 10;
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
            <li><a href="admin_productos.php" class="active"><i class="bi bi-box-seam"></i> Productos</a></li>
            <li><a href="admin_categorias.php"><i class="bi bi-tags"></i> Categorías</a></li>
            <li><a href="admin_ventas.php"><i class="bi bi-cart-check"></i> Ventas</a></li>
            <li><a href="admin_usuarios.php"><i class="bi bi-people"></i> Usuarios</a></li>
            <li style="margin-top: 50px;"><a href="index.php?ver_tienda=1"><i class="bi bi-house"></i> Ir a la Tienda</a></li>
            <li><a href="logout.php" class="text-danger"><i class="bi bi-box-arrow-right"></i> Cerrar Sesión</a></li>
        </ul>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2><i class="bi bi-box-seam"></i> Gestión de Productos</h2>
                <p class="text-muted">Administra tu catálogo de productos</p>
            </div>
            <button class="btn btn-gradient btn-lg" data-bs-toggle="modal" data-bs-target="#modalNuevoProducto">
                <i class="bi bi-plus-circle"></i> Nuevo Producto
            </button>
        </div>

        <?php if ($mensaje): ?>
        <div class="alert alert-<?php echo $tipo_mensaje; ?> alert-dismissible fade show" role="alert">
            <i class="bi bi-<?php echo $tipo_mensaje === 'success' ? 'check-circle' : 'exclamation-triangle'; ?>"></i>
            <?php echo $mensaje; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>

        <!-- Filtros -->
        <div class="card mb-4">
            <div class="card-body">
                <form method="GET" class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label">Buscar</label>
                        <input type="text" name="buscar" class="form-control" placeholder="Nombre o código..." value="<?php echo htmlspecialchars($busqueda); ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Categoría</label>
                        <select name="categoria" class="form-select">
                            <option value="">Todas</option>
                            <?php foreach ($categorias as $cat): ?>
                            <option value="<?php echo $cat['id']; ?>" <?php echo $categoria_filtro == $cat['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($cat['nombre']); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Ordenar por</label>
                        <select name="orden" class="form-select">
                            <option value="id DESC">Más recientes</option>
                            <option value="nombre ASC">Nombre A-Z</option>
                            <option value="precio ASC">Precio menor</option>
                            <option value="precio DESC">Precio mayor</option>
                            <option value="stock ASC">Stock menor</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">&nbsp;</label>
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="bi bi-search"></i> Filtrar
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Lista de Productos -->
        <div class="row g-4">
            <?php foreach ($productos as $producto): ?>
            <div class="col-md-4 col-lg-3">
                <div class="card card-producto h-100">
                    <?php if ($producto['stock'] < 20): ?>
                    <span class="stock-badge badge bg-danger">Stock Bajo</span>
                    <?php endif; ?>
                    
                    <div class="producto-imagen">
                        <i class="bi bi-box"></i>
                    </div>
                    
                    <div class="card-body">
                        <h6 class="card-title"><?php echo htmlspecialchars($producto['nombre']); ?></h6>
                        <p class="text-muted small mb-2">
                            <i class="bi bi-tag"></i> <?php echo htmlspecialchars($producto['categoria_nombre']); ?>
                        </p>
                        <p class="text-muted small mb-2">
                            Código: <?php echo htmlspecialchars($producto['codigo_producto']); ?>
                        </p>
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="h5 mb-0 text-primary">$<?php echo number_format($producto['precio'], 0); ?></span>
                            <span class="badge bg-<?php echo $producto['stock'] > 50 ? 'success' : ($producto['stock'] > 20 ? 'warning' : 'danger'); ?>">
                                Stock: <?php echo $producto['stock']; ?>
                            </span>
                        </div>
                        <div class="d-grid gap-2">
                            <button class="btn btn-sm btn-outline-primary" onclick="editarProducto(<?php echo htmlspecialchars(json_encode($producto)); ?>)">
                                <i class="bi bi-pencil"></i> Editar
                            </button>
                            <button class="btn btn-sm btn-outline-danger" onclick="eliminarProducto(<?php echo $producto['id']; ?>, '<?php echo htmlspecialchars($producto['nombre']); ?>')">
                                <i class="bi bi-trash"></i> Eliminar
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <?php if (empty($productos)): ?>
        <div class="text-center py-5">
            <i class="bi bi-inbox" style="font-size: 5rem; color: #ccc;"></i>
            <p class="text-muted">No se encontraron productos</p>
        </div>
        <?php endif; ?>
    </div>

    <!-- Modal Nuevo Producto -->
    <div class="modal fade" id="modalNuevoProducto" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form method="POST">
                    <input type="hidden" name="accion" value="crear">
                    <div class="modal-header">
                        <h5 class="modal-title"><i class="bi bi-plus-circle"></i> Nuevo Producto</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row g-3">
                            <div class="col-md-8">
                                <label class="form-label">Nombre del Producto *</label>
                                <input type="text" name="nombre" class="form-control" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Categoría *</label>
                                <select name="categoria_id" class="form-select" required>
                                    <?php foreach ($categorias as $cat): ?>
                                    <option value="<?php echo $cat['id']; ?>"><?php echo htmlspecialchars($cat['nombre']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Descripción</label>
                                <textarea name="descripcion" class="form-control" rows="3"></textarea>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Precio *</label>
                                <input type="number" name="precio" class="form-control" step="0.01" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Stock *</label>
                                <input type="number" name="stock" class="form-control" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Unidad</label>
                                <select name="unidad_medida" class="form-select">
                                    <option value="unidad">Unidad</option>
                                    <option value="paquete">Paquete</option>
                                    <option value="caja">Caja</option>
                                    <option value="docena">Docena</option>
                                </select>
                            </div>
                            <div class="col-md-8">
                                <label class="form-label">Código de Producto</label>
                                <input type="text" name="codigo_producto" class="form-control">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">&nbsp;</label>
                                <div class="form-check">
                                    <input type="checkbox" name="destacado" class="form-check-input" id="destacado">
                                    <label class="form-check-label" for="destacado">Producto Destacado</label>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-gradient">
                            <i class="bi bi-save"></i> Guardar Producto
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal Editar Producto -->
    <div class="modal fade" id="modalEditarProducto" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form method="POST" id="formEditar">
                    <input type="hidden" name="accion" value="actualizar">
                    <input type="hidden" name="id" id="edit_id">
                    <div class="modal-header">
                        <h5 class="modal-title"><i class="bi bi-pencil"></i> Editar Producto</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row g-3">
                            <div class="col-md-8">
                                <label class="form-label">Nombre del Producto *</label>
                                <input type="text" name="nombre" id="edit_nombre" class="form-control" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Categoría *</label>
                                <select name="categoria_id" id="edit_categoria" class="form-select" required>
                                    <?php foreach ($categorias as $cat): ?>
                                    <option value="<?php echo $cat['id']; ?>"><?php echo htmlspecialchars($cat['nombre']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Descripción</label>
                                <textarea name="descripcion" id="edit_descripcion" class="form-control" rows="3"></textarea>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Precio *</label>
                                <input type="number" name="precio" id="edit_precio" class="form-control" step="0.01" required>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Stock *</label>
                                <input type="number" name="stock" id="edit_stock" class="form-control" required>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Unidad</label>
                                <select name="unidad_medida" id="edit_unidad" class="form-select">
                                    <option value="unidad">Unidad</option>
                                    <option value="paquete">Paquete</option>
                                    <option value="caja">Caja</option>
                                    <option value="docena">Docena</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Estado</label>
                                <select name="estado" id="edit_estado" class="form-select">
                                    <option value="disponible">Disponible</option>
                                    <option value="agotado">Agotado</option>
                                    <option value="descontinuado">Descontinuado</option>
                                </select>
                            </div>
                            <div class="col-md-8">
                                <label class="form-label">Código</label>
                                <input type="text" name="codigo_producto" id="edit_codigo" class="form-control">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">&nbsp;</label>
                                <div class="form-check">
                                    <input type="checkbox" name="destacado" class="form-check-input" id="edit_destacado">
                                    <label class="form-check-label" for="edit_destacado">Destacado</label>
                                </div>
                            </div>
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
        function editarProducto(producto) {
            document.getElementById('edit_id').value = producto.id;
            document.getElementById('edit_nombre').value = producto.nombre;
            document.getElementById('edit_categoria').value = producto.categoria_id;
            document.getElementById('edit_descripcion').value = producto.descripcion || '';
            document.getElementById('edit_precio').value = producto.precio;
            document.getElementById('edit_stock').value = producto.stock;
            document.getElementById('edit_unidad').value = producto.unidad_medida;
            document.getElementById('edit_estado').value = producto.estado;
            document.getElementById('edit_codigo').value = producto.codigo_producto || '';
            document.getElementById('edit_destacado').checked = producto.destacado == 1;
            
            new bootstrap.Modal(document.getElementById('modalEditarProducto')).show();
        }
        
        function eliminarProducto(id, nombre) {
            if (confirm('¿Estás seguro de eliminar el producto "' + nombre + '"?')) {
                document.getElementById('delete_id').value = id;
                document.getElementById('formEliminar').submit();
            }
        }
    </script>
</body>
</html>