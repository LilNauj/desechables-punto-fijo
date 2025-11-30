<?php
/**
 * GESTIÓN DE PRODUCTOS - CON IMÁGENES
 * CRUD Completo: Crear, Leer, Actualizar, Eliminar
 */

require_once 'config/config.php';
require_once 'config/upload_config.php';
requerirLogin();
requerirAdmin();

// Función para obtener variantes de un producto
function obtenerVariantesProducto($db, $producto_id)
{
    $stmt = $db->prepare("SELECT pv.*, 
        GROUP_CONCAT(CONCAT(a.nombre, ': ', av.valor) SEPARATOR ', ') AS atributos_texto
        FROM producto_variantes pv
        LEFT JOIN variante_atributos va ON pv.id = va.variante_id
        LEFT JOIN atributos a ON va.atributo_id = a.id
        LEFT JOIN atributo_valores av ON va.atributo_valor_id = av.id
        WHERE pv.producto_id = ?
        GROUP BY pv.id
        ORDER BY pv.es_variante_principal DESC, pv.precio ASC");
    $stmt->bind_param("i", $producto_id);
    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

// Función para actualizar el stock de un producto a partir de sus variantes
function actualizarStockDesdeVariantes($db, $producto_id)
{
    $producto_id = (int) $producto_id;

    $db->query("
        UPDATE productos p
        SET p.stock = IFNULL((
            SELECT SUM(v.stock)
            FROM producto_variantes v
            WHERE v.producto_id = $producto_id
        ), 0)
        WHERE p.id = $producto_id
    ");
}


$db = getDB();
$mensaje = '';
$tipo_mensaje = '';

// PROCESAR ACCIONES
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $accion = $_POST['accion'] ?? '';

    // CREAR PRODUCTO
    if ($accion === 'crear') {
        $categoria_id = (int) $_POST['categoria_id'];
        $nombre = sanitize($_POST['nombre']);
        $descripcion = sanitize($_POST['descripcion']);


        $precio = (float) $_POST['precio'];
        $stock = (int) $_POST['stock'];
        $codigo_producto = sanitize($_POST['codigo_producto']);
        $unidad_medida = sanitize($_POST['unidad_medida']);
        $destacado = isset($_POST['destacado']) ? 1 : 0;

        $tiene_variantes = isset($_POST['tiene_variantes']) ? 1 : 0;

        // Si tiene variantes, el stock del producto se inicializa en 0
        if ($tiene_variantes) {
            $stock = 0;
        }
        $nombre_imagen = NULL;

        // Procesar imagen si se subió
        if (isset($_FILES['imagen']) && $_FILES['imagen']['error'] !== UPLOAD_ERR_NO_FILE) {
            $resultado_upload = subirImagenProducto($_FILES['imagen']);

            if ($resultado_upload['success']) {
                $nombre_imagen = $resultado_upload['nombre_archivo'];
                // Redimensionar imagen (opcional)
                redimensionarImagen($resultado_upload['ruta_completa'], 800, 800);
            } else {
                $mensaje = "Error al subir imagen: " . $resultado_upload['error'];
                $tipo_mensaje = "danger";
            }
        }

        if (empty($mensaje)) {
            $stmt = $db->prepare("INSERT INTO productos (categoria_id, nombre, descripcion, precio, stock, codigo_producto, unidad_medida, destacado, imagen) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("isssissis", $categoria_id, $nombre, $descripcion, $precio, $stock, $codigo_producto, $unidad_medida, $destacado, $nombre_imagen);

            if ($stmt->execute()) {
                $producto_id = $stmt->insert_id;

                // Marcar si tiene variantes
                $tiene_variantes = isset($_POST['tiene_variantes']) ? 1 : 0;
                $db->query("UPDATE productos SET tiene_variantes = $tiene_variantes WHERE id = $producto_id");

                $mensaje = "Producto creado exitosamente";
                $tipo_mensaje = "success";
            } else {
                $mensaje = "Error al crear producto: " . $stmt->error;
                $tipo_mensaje = "danger";
                // Si hubo error, eliminar la imagen subida
                if ($nombre_imagen) {
                    eliminarImagenProducto($nombre_imagen);
                }
            }
            $stmt->close();
        }
    }

    // ACTUALIZAR PRODUCTO
    elseif ($accion === 'actualizar') {
        $id = (int) $_POST['id'];
        $categoria_id = (int) $_POST['categoria_id'];
        $nombre = sanitize($_POST['nombre']);
        $descripcion = sanitize($_POST['descripcion']);
        $precio = (float) $_POST['precio'];
        $stock = (int) $_POST['stock'];
        $codigo_producto = sanitize($_POST['codigo_producto']);
        $unidad_medida = sanitize($_POST['unidad_medida']);
        $estado = sanitize($_POST['estado']);

        $tiene_variantes = isset($_POST['tiene_variantes']) ? 1 : 0;

        if ($tiene_variantes) {
            $stock = 0;
        }

        $destacado = isset($_POST['destacado']) ? 1 : 0;

        // Obtener imagen actual
        $stmt = $db->prepare("SELECT imagen FROM productos WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        $producto_actual = $result->fetch_assoc();
        $stmt->close();

        $nombre_imagen = $producto_actual['imagen'];

        // Procesar nueva imagen si se subió
        if (isset($_FILES['imagen']) && $_FILES['imagen']['error'] !== UPLOAD_ERR_NO_FILE) {
            $resultado_upload = subirImagenProducto($_FILES['imagen'], $id);

            if ($resultado_upload['success']) {
                // Eliminar imagen anterior si existe
                if ($nombre_imagen) {
                    eliminarImagenProducto($nombre_imagen);
                }
                $nombre_imagen = $resultado_upload['nombre_archivo'];
                // Redimensionar imagen
                redimensionarImagen($resultado_upload['ruta_completa'], 800, 800);
            } else {
                $mensaje = "Error al subir imagen: " . $resultado_upload['error'];
                $tipo_mensaje = "danger";
            }
        }

        // Verificar si se debe eliminar la imagen
        if (isset($_POST['eliminar_imagen']) && $_POST['eliminar_imagen'] == '1') {
            if ($nombre_imagen) {
                eliminarImagenProducto($nombre_imagen);
                $nombre_imagen = NULL;
            }
        }

        if (empty($mensaje)) {
            $stmt = $db->prepare("UPDATE productos SET categoria_id=?, nombre=?, descripcion=?, precio=?, stock=?, codigo_producto=?, unidad_medida=?, estado=?, destacado=?, imagen=? WHERE id=?");
            $stmt->bind_param("isssisssisi", $categoria_id, $nombre, $descripcion, $precio, $stock, $codigo_producto, $unidad_medida, $estado, $destacado, $nombre_imagen, $id);

            if ($stmt->execute()) {
                // Actualizar si tiene variantes
                $tiene_variantes = isset($_POST['tiene_variantes']) ? 1 : 0;
                $db->query("UPDATE productos SET tiene_variantes = $tiene_variantes WHERE id = $id");

                if ($tiene_variantes) {
                    actualizarStockDesdeVariantes($db, $id);
                }

                $mensaje = "Producto actualizado exitosamente";
                $tipo_mensaje = "success";
            } else {
                $mensaje = "Error al actualizar producto: " . $stmt->error;
                $tipo_mensaje = "danger";
            }
            $stmt->close();
        }
    }

    // ELIMINAR PRODUCTO
    elseif ($accion === 'eliminar') {
        $id = (int) $_POST['id'];

        // Obtener imagen antes de eliminar
        $stmt = $db->prepare("SELECT imagen FROM productos WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        $producto = $result->fetch_assoc();
        $stmt->close();

        $stmt = $db->prepare("DELETE FROM productos WHERE id = ?");
        $stmt->bind_param("i", $id);

        if ($stmt->execute()) {
            // Eliminar imagen si existe
            if ($producto && $producto['imagen']) {
                eliminarImagenProducto($producto['imagen']);
            }
            $mensaje = "Producto eliminado exitosamente";
            $tipo_mensaje = "success";
        } else {
            $mensaje = "Error al eliminar producto: " . $stmt->error;
            $tipo_mensaje = "danger";
        }
        $stmt->close();
    }

    // GESTIONAR VARIANTES
    elseif ($accion === 'agregar_variante') {
        $producto_id = (int) $_POST['producto_id'];
        $nombre_variante = sanitize($_POST['nombre_variante']);
        $sku = sanitize($_POST['sku']);
        $precio = (float) $_POST['precio'];
        $stock = (int) $_POST['stock'];
        $es_principal = isset($_POST['es_variante_principal']) ? 1 : 0;

        // Si es principal, desmarcar otras
        if ($es_principal) {
            $db->query("UPDATE producto_variantes SET es_variante_principal = 0 WHERE producto_id = $producto_id");
        }

        $stmt = $db->prepare("INSERT INTO producto_variantes (producto_id, nombre_variante, sku, precio, stock, es_variante_principal) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("issdii", $producto_id, $nombre_variante, $sku, $precio, $stock, $es_principal);

        if ($stmt->execute()) {
            // Actualizar precio_desde del producto
            $db->query("CALL sp_actualizar_precio_desde($producto_id)");

            // actualizar stock del producto desde sus variantes
            actualizarStockDesdeVariantes($db, $producto_id);

            // Marcar producto como que tiene variantes
            $db->query("UPDATE productos SET tiene_variantes = 1 WHERE id = $producto_id");

            $mensaje = "Variante agregada exitosamente";
            $tipo_mensaje = "success";
        } else {
            $mensaje = "Error al agregar variante: " . $stmt->error;
            $tipo_mensaje = "danger";
        }
        $stmt->close();
    } elseif ($accion === 'editar_variante') {
        $id = (int) $_POST['variante_id'];
        $producto_id = (int) $_POST['producto_id'];
        $nombre_variante = sanitize($_POST['nombre_variante']);
        $sku = sanitize($_POST['sku']);
        $precio = (float) $_POST['precio'];
        $stock = (int) $_POST['stock'];
        $estado = sanitize($_POST['estado']);
        $es_principal = isset($_POST['es_variante_principal']) ? 1 : 0;

        if ($es_principal) {
            $db->query("UPDATE producto_variantes SET es_variante_principal = 0 WHERE producto_id = $producto_id");
        }

        $stmt = $db->prepare("UPDATE producto_variantes SET nombre_variante=?, sku=?, precio=?, stock=?, estado=?, es_variante_principal=? WHERE id=?");
        $stmt->bind_param("ssdisii", $nombre_variante, $sku, $precio, $stock, $estado, $es_principal, $id);

        if ($stmt->execute()) {
            $db->query("CALL sp_actualizar_precio_desde($producto_id)");

            actualizarStockDesdeVariantes($db, $producto_id);


            $mensaje = "Variante actualizada exitosamente";
            $tipo_mensaje = "success";
        } else {
            $mensaje = "Error al actualizar variante: " . $stmt->error;
            $tipo_mensaje = "danger";
        }
        $stmt->close();
    } elseif ($accion === 'eliminar_variante') {
        $id = (int) $_POST['variante_id'];
        $producto_id = (int) $_POST['producto_id'];

        $stmt = $db->prepare("DELETE FROM producto_variantes WHERE id = ?");
        $stmt->bind_param("i", $id);

        if ($stmt->execute()) {
            // Actualizar precio_desde
            $db->query("CALL sp_actualizar_precio_desde($producto_id)");

            actualizarStockDesdeVariantes($db, $producto_id);


            // Si no quedan variantes, desmarcar tiene_variantes
            $result = $db->query("SELECT COUNT(*) as total FROM producto_variantes WHERE producto_id = $producto_id");
            $row = $result->fetch_assoc();
            if ($row['total'] == 0) {
                $db->query("UPDATE productos SET tiene_variantes = 0 WHERE id = $producto_id");
            }

            $mensaje = "Variante eliminada exitosamente";
            $tipo_mensaje = "success";
        } else {
            $mensaje = "Error al eliminar variante: " . $stmt->error;
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
    $sql .= " AND p.categoria_id = " . (int) $categoria_filtro;
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
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15);
        }

        .producto-imagen {
            height: 200px;
            background: linear-gradient(135deg, #667eea33 0%, #764ba233 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 3rem;
            color: #667eea;
            border-radius: 10px 10px 0 0;
            overflow: hidden;
            position: relative;
        }

        .producto-imagen img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .producto-imagen .sin-imagen {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 10px;
        }

        .btn-gradient {
            background: linear-gradient(135deg, #7b7b7bff 0%, #000000ff 100%);
            color: white;
            border: none;
        }

        .btn-gradient:hover {
            background: linear-gradient(135deg, #6a6a6aff 0%, #1a1a1aff 100%);
            color: white;
        }

        .stock-badge {
            position: absolute;
            top: 10px;
            right: 10px;
            z-index: 10;
        }

        .image-preview {
            width: 100%;
            max-height: 200px;
            object-fit: contain;
            margin-top: 10px;
            border-radius: 8px;
            border: 2px solid #e0e0e0;
            display: none;
        }

        .image-preview.active {
            display: block;
        }

        .current-image {
            width: 100%;
            max-height: 200px;
            object-fit: contain;
            border-radius: 8px;
            border: 2px solid #e0e0e0;
        }

        .btn-remove-image {
            position: absolute;
            top: 5px;
            right: 5px;
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
            <li>
                <a href="admin.php">
                    <i class="bi bi-speedometer2"></i> Dashboard
                </a>
            </li>
            <li>
                <a href="admin_productos.php" class="active">
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
                        <input type="text" name="buscar" class="form-control" placeholder="Nombre o código..."
                            value="<?php echo htmlspecialchars($busqueda); ?>">
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
                            <?php if ($producto['imagen']): ?>
                                <img src="<?php echo UPLOAD_URL . htmlspecialchars($producto['imagen']); ?>"
                                    alt="<?php echo htmlspecialchars($producto['nombre']); ?>">
                            <?php else: ?>
                                <div class="sin-imagen">
                                    <i class="bi bi-image"></i>
                                    <small class="text-muted">Sin imagen</small>
                                </div>
                            <?php endif; ?>
                        </div>

                        <div class="card-body">
                            <?php if ($producto['tiene_variantes']): ?>
                                <span class="badge bg-info mb-2">
                                    <i class="bi bi-collection"></i> Tiene Variantes
                                </span>
                            <?php endif; ?>

                            <h6 class="card-title"><?php echo htmlspecialchars($producto['nombre']); ?></h6>
                            <p class="text-muted small mb-2">
                                <i class="bi bi-tag"></i> <?php echo htmlspecialchars($producto['categoria_nombre']); ?>
                            </p>
                            <p class="text-muted small mb-2">
                                Código: <?php echo htmlspecialchars($producto['codigo_producto']); ?>
                            </p>
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <span
                                    class="h5 mb-0 text-primary">$<?php echo number_format($producto['precio'], 0); ?></span>
                                <span
                                    class="badge bg-<?php echo $producto['stock'] > 50 ? 'success' : ($producto['stock'] > 20 ? 'warning' : 'danger'); ?>">
                                    Stock: <?php echo $producto['stock']; ?>
                                </span>
                            </div>
                            <div class="d-grid gap-2">
                                <button class="btn btn-sm btn-outline-primary"
                                    onclick='editarProducto(<?php echo json_encode($producto); ?>)'>
                                    <i class="bi bi-pencil"></i> Editar
                                </button>
                                <button class="btn btn-sm btn-outline-danger"
                                    onclick="eliminarProducto(<?php echo $producto['id']; ?>, '<?php echo htmlspecialchars($producto['nombre']); ?>')">
                                    <i class="bi bi-trash"></i> Eliminar
                                </button>
                                <?php if ($producto['tiene_variantes']): ?>
                                    <button class="btn btn-sm btn-outline-info"
                                        onclick="gestionarVariantes(<?php echo $producto['id']; ?>, '<?php echo htmlspecialchars($producto['nombre']); ?>')">
                                        <i class="bi bi-collection"></i> Variantes
                                    </button>
                                <?php endif; ?>
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
                <form method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="accion" value="crear">
                    <div class="modal-header">
                        <h5 class="modal-title"><i class="bi bi-plus-circle"></i> Nuevo Producto</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row g-3">
                            <div class="col-12">
                                <label class="form-label">Imagen del Producto</label>
                                <input type="file" name="imagen" class="form-control" accept="image/*"
                                    onchange="previewImage(event, 'preview-nuevo')">
                                <small class="text-muted">Formatos: JPG, PNG, GIF, WEBP. Tamaño máximo: 5MB</small>
                                <img id="preview-nuevo" class="image-preview" alt="Vista previa">
                            </div>
                            <div class="col-md-8">
                                <label class="form-label">Nombre del Producto *</label>
                                <input type="text" name="nombre" class="form-control" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Categoría *</label>
                                <select name="categoria_id" class="form-select" required>
                                    <?php foreach ($categorias as $cat): ?>
                                        <option value="<?php echo $cat['id']; ?>">
                                            <?php echo htmlspecialchars($cat['nombre']); ?>
                                        </option>
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
                            <div class="col-md-4" id="grupo_stock_nuevo">
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
                            <div class="col-12">
                                <div class="form-check">
                                    <input type="checkbox" name="tiene_variantes" class="form-check-input"
                                        id="tiene_variantes">
                                    <label class="form-check-label" for="tiene_variantes">
                                        <i class="bi bi-collection"></i> Este producto tendrá variantes (tamaños,
                                        capacidades, etc.)
                                    </label>
                                    <small class="text-muted d-block">Si activas esto, podrás agregar múltiples
                                        variantes después de crear el producto</small>
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
                <form method="POST" enctype="multipart/form-data" id="formEditar">
                    <input type="hidden" name="accion" value="actualizar">
                    <input type="hidden" name="id" id="edit_id">
                    <input type="hidden" name="eliminar_imagen" id="eliminar_imagen" value="0">
                    <div class="modal-header">
                        <h5 class="modal-title"><i class="bi bi-pencil"></i> Editar Producto</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row g-3">
                            <div class="col-12">
                                <label class="form-label">Imagen Actual</label>
                                <div id="current-image-container" style="position: relative; display: none;">
                                    <img id="current-image" class="current-image" alt="Imagen actual">
                                    <button type="button" class="btn btn-danger btn-sm btn-remove-image"
                                        onclick="removeCurrentImage()">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </div>
                                <div id="no-image-container" style="display: none;">
                                    <p class="text-muted"><i class="bi bi-image"></i> Sin imagen</p>
                                </div>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Cambiar Imagen</label>
                                <input type="file" name="imagen" class="form-control" accept="image/*"
                                    onchange="previewImage(event, 'preview-edit')">
                                <small class="text-muted">Formatos: JPG, PNG, GIF, WEBP. Tamaño máximo: 5MB</small>
                                <img id="preview-edit" class="image-preview" alt="Vista previa">
                            </div>
                            <div class="col-md-8">
                                <label class="form-label">Nombre del Producto *</label>
                                <input type="text" name="nombre" id="edit_nombre" class="form-control" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Categoría *</label>
                                <select name="categoria_id" id="edit_categoria" class="form-select" required>
                                    <?php foreach ($categorias as $cat): ?>
                                        <option value="<?php echo $cat['id']; ?>">
                                            <?php echo htmlspecialchars($cat['nombre']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Descripción</label>
                                <textarea name="descripcion" id="edit_descripcion" class="form-control"
                                    rows="3"></textarea>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Precio *</label>
                                <input type="number" name="precio" id="edit_precio" class="form-control" step="0.01"
                                    required>
                            </div>
                            <div class="col-md-3" id="grupo_stock_editar">
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
                                    <input type="checkbox" name="destacado" class="form-check-input"
                                        id="edit_destacado">
                                    <label class="form-check-label" for="edit_destacado">Destacado</label>
                                </div>
                            </div>
                            <div class="col-12">
                                <div class="form-check">
                                    <input type="checkbox" name="tiene_variantes" class="form-check-input"
                                        id="edit_tiene_variantes">
                                    <label class="form-check-label" for="edit_tiene_variantes">
                                        <i class="bi bi-collection"></i> Este producto tiene variantes
                                    </label>
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

    <!-- Modal Gestionar Variantes -->
    <div class="modal fade" id="modalVariantes" tabindex="-1">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="bi bi-collection"></i> Variantes de: <span id="variantes_producto_nombre"></span>
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="variantes_producto_id">

                    <button class="btn btn-sm btn-success mb-3" onclick="mostrarFormNuevaVariante()">
                        <i class="bi bi-plus-circle"></i> Agregar Variante
                    </button>

                    <div id="lista-variantes" class="table-responsive">
                        <!-- Se carga dinámicamente -->
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Nueva/Editar Variante -->
    <div class="modal fade" id="modalFormVariante" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST" id="formVariante">
                    <input type="hidden" name="accion" id="variante_accion" value="agregar_variante">
                    <input type="hidden" name="producto_id" id="variante_producto_id">
                    <input type="hidden" name="variante_id" id="variante_id">

                    <div class="modal-header">
                        <h5 class="modal-title" id="titulo-form-variante">Nueva Variante</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Nombre de la Variante *</label>
                            <input type="text" name="nombre_variante" id="form_nombre_variante" class="form-control"
                                placeholder="Ej: Vaso 250ml" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">SKU/Código</label>
                            <input type="text" name="sku" id="form_sku" class="form-control"
                                placeholder="Ej: VASO-250ML">
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Precio *</label>
                                <input type="number" name="precio" id="form_precio_variante" class="form-control"
                                    step="0.01" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Stock *</label>
                                <input type="number" name="stock" id="form_stock_variante" class="form-control"
                                    required>
                            </div>
                        </div>
                        <div class="mb-3" id="campo_estado" style="display: none;">
                            <label class="form-label">Estado</label>
                            <select name="estado" id="form_estado_variante" class="form-select">
                                <option value="disponible">Disponible</option>
                                <option value="agotado">Agotado</option>
                                <option value="descontinuado">Descontinuado</option>
                            </select>
                        </div>
                        <div class="form-check">
                            <input type="checkbox" name="es_variante_principal" id="form_es_principal"
                                class="form-check-input">
                            <label class="form-check-label" for="form_es_principal">
                                Variante Principal (se muestra por defecto)
                            </label>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-save"></i> Guardar
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
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Función para previsualizar imagen antes de subir
        function previewImage(event, previewId) {
            const file = event.target.files[0];
            const preview = document.getElementById(previewId);

            if (file) {
                const reader = new FileReader();

                reader.onload = function (e) {
                    preview.src = e.target.result;
                    preview.classList.add('active');
                }

                reader.readAsDataURL(file);
            } else {
                preview.classList.remove('active');
            }
        }

        // Función para editar producto
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
            document.getElementById('edit_tiene_variantes').checked = producto.tiene_variantes == 1;
            document.getElementById('eliminar_imagen').value = '0';

            // Mostrar imagen actual si existe
            const currentImageContainer = document.getElementById('current-image-container');
            const noImageContainer = document.getElementById('no-image-container');
            const currentImage = document.getElementById('current-image');

            if (producto.imagen) {
                currentImage.src = '<?php echo UPLOAD_URL; ?>' + producto.imagen;
                currentImageContainer.style.display = 'block';
                noImageContainer.style.display = 'none';
            } else {
                currentImageContainer.style.display = 'none';
                noImageContainer.style.display = 'block';
            }

            // Limpiar preview
            document.getElementById('preview-edit').classList.remove('active');

            // Ajustar visibilidad del campo stock según tiene_variantes
            const group = document.getElementById('grupo_stock_editar');
            const input = document.getElementById('edit_stock');
            if (producto.tiene_variantes == 1) {
                group.style.display = 'none';
                input.removeAttribute('required');
            } else {
                group.style.display = '';
                input.setAttribute('required', 'required');
            }

            new bootstrap.Modal(document.getElementById('modalEditarProducto')).show();
        }

        // Función para eliminar imagen actual
        function removeCurrentImage() {
            if (confirm('¿Estás seguro de eliminar la imagen actual?')) {
                document.getElementById('eliminar_imagen').value = '1';
                document.getElementById('current-image-container').style.display = 'none';
                document.getElementById('no-image-container').style.display = 'block';
            }
        }

        // Función para eliminar producto
        function eliminarProducto(id, nombre) {
            if (confirm('¿Estás seguro de eliminar el producto "' + nombre + '"?\n\nEsta acción también eliminará su imagen si tiene una.')) {
                document.getElementById('delete_id').value = id;
                document.getElementById('formEliminar').submit();
            }
        }

        // Limpiar preview al cerrar modal
        document.getElementById('modalNuevoProducto').addEventListener('hidden.bs.modal', function () {
            document.getElementById('preview-nuevo').classList.remove('active');
            this.querySelector('form').reset();
        });

        document.getElementById('modalEditarProducto').addEventListener('hidden.bs.modal', function () {
            document.getElementById('preview-edit').classList.remove('active');
        });
        // Gestionar variantes
        function gestionarVariantes(productoId, productoNombre) {
            document.getElementById('variantes_producto_id').value = productoId;
            document.getElementById('variantes_producto_nombre').textContent = productoNombre;
            cargarVariantes(productoId);
            new bootstrap.Modal(document.getElementById('modalVariantes')).show();
        }

        function cargarVariantes(productoId) {
            fetch(`ajax/cargar_variantes.php?producto_id=${productoId}`)
                .then(response => response.text())
                .then(html => {
                    document.getElementById('lista-variantes').innerHTML = html;
                })
                .catch(error => {
                    document.getElementById('lista-variantes').innerHTML =
                        '<div class="alert alert-danger">Error al cargar variantes</div>';
                });
        }

        function mostrarFormNuevaVariante() {
            document.getElementById('variante_accion').value = 'agregar_variante';
            document.getElementById('titulo-form-variante').textContent = 'Nueva Variante';
            document.getElementById('formVariante').reset();
            document.getElementById('variante_producto_id').value = document.getElementById('variantes_producto_id').value;
            document.getElementById('campo_estado').style.display = 'none';
            new bootstrap.Modal(document.getElementById('modalFormVariante')).show();
        }

        function editarVariante(variante) {
            document.getElementById('variante_accion').value = 'editar_variante';
            document.getElementById('titulo-form-variante').textContent = 'Editar Variante';
            document.getElementById('variante_id').value = variante.id;
            document.getElementById('variante_producto_id').value = variante.producto_id;
            document.getElementById('form_nombre_variante').value = variante.nombre_variante;
            document.getElementById('form_sku').value = variante.sku || '';
            document.getElementById('form_precio_variante').value = variante.precio;
            document.getElementById('form_stock_variante').value = variante.stock;
            document.getElementById('form_estado_variante').value = variante.estado;
            document.getElementById('form_es_principal').checked = variante.es_variante_principal == 1;
            document.getElementById('campo_estado').style.display = 'block';
            new bootstrap.Modal(document.getElementById('modalFormVariante')).show();
        }

        function eliminarVariante(varianteId, productoId, nombreVariante) {
            if (confirm('¿Eliminar la variante "' + nombreVariante + '"?')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
            <input type="hidden" name="accion" value="eliminar_variante">
            <input type="hidden" name="variante_id" value="${varianteId}">
            <input type="hidden" name="producto_id" value="${productoId}">
        `;
                document.body.appendChild(form);
                form.submit();
            }
        }

        // Actualizar función editarProducto para incluir tiene_variantes
        //        const editarProductoOriginal = editarProducto;
        //        function editarProducto(producto) {
        //            editarProductoOriginal(producto);
        //            document.getElementById('edit_tiene_variantes').checked = producto.tiene_variantes == 1;
        //        }


        // En la sección <script>, reemplaza o asegúrate de tener esto:

        document.addEventListener('DOMContentLoaded', function () {
            const chkNuevo = document.getElementById('tiene_variantes');
            const chkEditar = document.getElementById('edit_tiene_variantes');

            function toggleStockNuevo() {
                const group = document.getElementById('grupo_stock_nuevo');
                if (!group) return;
                const input = group.querySelector('input[name="stock"]');
                if (chkNuevo.checked) {
                    group.style.display = 'none';
                    input.value = 0;
                    input.removeAttribute('required');
                } else {
                    group.style.display = '';
                    input.setAttribute('required', 'required');
                }
            }

            function toggleStockEditar() {
                const group = document.getElementById('grupo_stock_editar');
                if (!group) return;
                const input = document.getElementById('edit_stock');
                if (chkEditar.checked) {
                    group.style.display = 'none';
                    input.value = 0;
                    input.removeAttribute('required');
                } else {
                    group.style.display = '';
                    input.setAttribute('required', 'required');
                }
            }

            if (chkNuevo) {
                chkNuevo.addEventListener('change', toggleStockNuevo);
                toggleStockNuevo(); // Estado inicial
            }

            if (chkEditar) {
                chkEditar.addEventListener('change', toggleStockEditar);
            }
        });



    </script>
</body>

</html>