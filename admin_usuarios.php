<?php
/**
 * GESTIÓN DE USUARIOS
 * Ver, Editar, Activar/Desactivar usuarios
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
    
    // CAMBIAR ROL
    if ($accion === 'cambiar_rol') {
        $id = (int)$_POST['id'];
        $nuevo_rol = sanitize($_POST['rol']);
        
        // Verificar que no se cambie el propio rol
        if ($id == $_SESSION['usuario_id']) {
            $mensaje = "No puedes cambiar tu propio rol";
            $tipo_mensaje = "warning";
        } else {
            $stmt = $db->prepare("UPDATE usuarios SET rol = ? WHERE id = ?");
            $stmt->bind_param("si", $nuevo_rol, $id);
            
            if ($stmt->execute()) {
                $mensaje = "Rol actualizado exitosamente";
                $tipo_mensaje = "success";
            } else {
                $mensaje = "Error al actualizar rol: " . $stmt->error;
                $tipo_mensaje = "danger";
            }
            $stmt->close();
        }
    }
    
    // CAMBIAR ESTADO
    elseif ($accion === 'cambiar_estado') {
        $id = (int)$_POST['id'];
        $nuevo_estado = sanitize($_POST['estado']);
        
        // Verificar que no se desactive a sí mismo
        if ($id == $_SESSION['usuario_id']) {
            $mensaje = "No puedes cambiar tu propio estado";
            $tipo_mensaje = "warning";
        } else {
            $stmt = $db->prepare("UPDATE usuarios SET estado = ? WHERE id = ?");
            $stmt->bind_param("si", $nuevo_estado, $id);
            
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
    
    // ACTUALIZAR INFORMACIÓN
    elseif ($accion === 'actualizar') {
        $id = (int)$_POST['id'];
        $nombre = sanitize($_POST['nombre']);
        $apellido = sanitize($_POST['apellido']);
        $email = sanitize($_POST['email']);
        $telefono = sanitize($_POST['telefono']);
        $direccion = sanitize($_POST['direccion']);
        $rol = sanitize($_POST['rol']);
        $estado = sanitize($_POST['estado']);
        
        // Verificar email único
        $stmt = $db->prepare("SELECT id FROM usuarios WHERE email = ? AND id != ?");
        $stmt->bind_param("si", $email, $id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $mensaje = "El email ya está siendo usado por otro usuario";
            $tipo_mensaje = "warning";
        } else {
            $stmt = $db->prepare("UPDATE usuarios SET nombre=?, apellido=?, email=?, telefono=?, direccion=?, rol=?, estado=? WHERE id=?");
            $stmt->bind_param("sssssssi", $nombre, $apellido, $email, $telefono, $direccion, $rol, $estado, $id);
            
            if ($stmt->execute()) {
                $mensaje = "Usuario actualizado exitosamente";
                $tipo_mensaje = "success";
            } else {
                $mensaje = "Error al actualizar usuario: " . $stmt->error;
                $tipo_mensaje = "danger";
            }
        }
        $stmt->close();
    }
    
    // ELIMINAR USUARIO
    elseif ($accion === 'eliminar') {
        $id = (int)$_POST['id'];
        
        // Verificar que no se elimine a sí mismo
        if ($id == $_SESSION['usuario_id']) {
            $mensaje = "No puedes eliminarte a ti mismo";
            $tipo_mensaje = "warning";
        } else {
            // Verificar si tiene ventas
            $stmt = $db->prepare("SELECT COUNT(*) as total FROM ventas WHERE usuario_id = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $result = $stmt->get_result();
            $row = $result->fetch_assoc();
            
            if ($row['total'] > 0) {
                $mensaje = "No se puede eliminar: el usuario tiene " . $row['total'] . " venta(s) registrada(s)";
                $tipo_mensaje = "warning";
            } else {
                $stmt = $db->prepare("DELETE FROM usuarios WHERE id = ?");
                $stmt->bind_param("i", $id);
                
                if ($stmt->execute()) {
                    $mensaje = "Usuario eliminado exitosamente";
                    $tipo_mensaje = "success";
                } else {
                    $mensaje = "Error al eliminar usuario: " . $stmt->error;
                    $tipo_mensaje = "danger";
                }
            }
            $stmt->close();
        }
    }
}

// FILTROS
$busqueda = $_GET['buscar'] ?? '';
$rol_filtro = $_GET['rol'] ?? '';
$estado_filtro = $_GET['estado'] ?? '';
$orden = $_GET['orden'] ?? 'fecha_registro DESC';

// OBTENER USUARIOS
$sql = "SELECT u.*, 
        (SELECT COUNT(*) FROM ventas WHERE usuario_id = u.id) as total_ventas,
        (SELECT COALESCE(SUM(total), 0) FROM ventas WHERE usuario_id = u.id AND estado = 'completada') as total_gastado
        FROM usuarios u WHERE 1=1";

if (!empty($busqueda)) {
    $sql .= " AND (u.nombre LIKE '%" . $db->real_escape_string($busqueda) . "%' 
              OR u.apellido LIKE '%" . $db->real_escape_string($busqueda) . "%'
              OR u.email LIKE '%" . $db->real_escape_string($busqueda) . "%')";
}

if (!empty($rol_filtro)) {
    $sql .= " AND u.rol = '" . $db->real_escape_string($rol_filtro) . "'";
}

if (!empty($estado_filtro)) {
    $sql .= " AND u.estado = '" . $db->real_escape_string($estado_filtro) . "'";
}

$sql .= " ORDER BY " . $db->real_escape_string($orden);

$result = $db->query($sql);
$usuarios = $result->fetch_all(MYSQLI_ASSOC);

// ESTADÍSTICAS
$total_usuarios = count($usuarios);
$total_admins = count(array_filter($usuarios, function($u) { return $u['rol'] === 'admin'; }));
$total_clientes = count(array_filter($usuarios, function($u) { return $u['rol'] === 'cliente'; }));
$usuarios_activos = count(array_filter($usuarios, function($u) { return $u['estado'] === 'activo'; }));
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Usuarios - Admin</title>
    
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
        
        .stat-card.danger {
            border-left-color: #ef4444;
        }
        
        .user-avatar {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            object-fit: cover;
        }
        
        .table-card {
            background: white;
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
            margin-top: 30px;
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
        
        .badge-rol {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.85rem;
        }
        
        .usuario-actual {
            background: linear-gradient(135deg, #667eea11 0%, #764ba211 100%);
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
                <a href="admin_usuarios.php" class="active">
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
                <h2><i class="bi bi-people"></i> Gestión de Usuarios</h2>
                <p class="text-muted">Administra los usuarios del sistema</p>
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
                            <p class="text-muted mb-1">Total Usuarios</p>
                            <h3 class="mb-0"><?php echo $total_usuarios; ?></h3>
                        </div>
                        <i class="bi bi-people" style="font-size: 2.5rem; color: #667eea; opacity: 0.3;"></i>
                    </div>
                </div>
            </div>
            
            <div class="col-md-3">
                <div class="stat-card warning">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <p class="text-muted mb-1">Administradores</p>
                            <h3 class="mb-0"><?php echo $total_admins; ?></h3>
                        </div>
                        <i class="bi bi-shield-check" style="font-size: 2.5rem; color: #f59e0b; opacity: 0.3;"></i>
                    </div>
                </div>
            </div>
            
            <div class="col-md-3">
                <div class="stat-card success">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <p class="text-muted mb-1">Clientes</p>
                            <h3 class="mb-0"><?php echo $total_clientes; ?></h3>
                        </div>
                        <i class="bi bi-person-check" style="font-size: 2.5rem; color: #10b981; opacity: 0.3;"></i>
                    </div>
                </div>
            </div>
            
            <div class="col-md-3">
                <div class="stat-card danger">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <p class="text-muted mb-1">Usuarios Activos</p>
                            <h3 class="mb-0"><?php echo $usuarios_activos; ?></h3>
                        </div>
                        <i class="bi bi-check-circle" style="font-size: 2.5rem; color: #10b981; opacity: 0.3;"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filtros -->
        <div class="card mb-4">
            <div class="card-body">
                <form method="GET" class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label">Buscar</label>
                        <input type="text" name="buscar" class="form-control" 
                               placeholder="Nombre, apellido o email..." 
                               value="<?php echo htmlspecialchars($busqueda); ?>">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Rol</label>
                        <select name="rol" class="form-select">
                            <option value="">Todos</option>
                            <option value="admin" <?php echo $rol_filtro === 'admin' ? 'selected' : ''; ?>>Admin</option>
                            <option value="cliente" <?php echo $rol_filtro === 'cliente' ? 'selected' : ''; ?>>Cliente</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Estado</label>
                        <select name="estado" class="form-select">
                            <option value="">Todos</option>
                            <option value="activo" <?php echo $estado_filtro === 'activo' ? 'selected' : ''; ?>>Activo</option>
                            <option value="inactivo" <?php echo $estado_filtro === 'inactivo' ? 'selected' : ''; ?>>Inactivo</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Ordenar por</label>
                        <select name="orden" class="form-select">
                            <option value="fecha_registro DESC">Más recientes</option>
                            <option value="nombre ASC">Nombre A-Z</option>
                            <option value="total_ventas DESC">Más compras</option>
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

        <!-- Tabla de Usuarios -->
        <div class="table-card">
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead>
                        <tr>
                            <th>Usuario</th>
                            <th>Contacto</th>
                            <th>Rol</th>
                            <th>Estado</th>
                            <th>Compras</th>
                            <th>Registro</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($usuarios as $usuario): ?>
                        <tr class="<?php echo $usuario['id'] == $_SESSION['usuario_id'] ? 'usuario-actual' : ''; ?>">
                            <td>
                                <div class="d-flex align-items-center">
                                    <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($usuario['nombre'] . ' ' . $usuario['apellido']); ?>&size=50&background=667eea&color=fff" 
                                         class="user-avatar me-3" alt="Avatar">
                                    <div>
                                        <strong><?php echo htmlspecialchars($usuario['nombre'] . ' ' . $usuario['apellido']); ?></strong>
                                        <?php if ($usuario['id'] == $_SESSION['usuario_id']): ?>
                                            <span class="badge bg-info ms-2">Tú</span>
                                        <?php endif; ?>
                                        <br>
                                        <small class="text-muted">ID: <?php echo $usuario['id']; ?></small>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <i class="bi bi-envelope text-primary"></i> <?php echo htmlspecialchars($usuario['email']); ?><br>
                                <i class="bi bi-telephone text-success"></i> <?php echo htmlspecialchars($usuario['telefono'] ?: 'N/A'); ?>
                            </td>
                            <td>
                                <span class="badge badge-rol <?php echo $usuario['rol'] === 'admin' ? 'bg-warning text-dark' : 'bg-primary'; ?>">
                                    <i class="bi bi-<?php echo $usuario['rol'] === 'admin' ? 'shield-check' : 'person'; ?>"></i>
                                    <?php echo ucfirst($usuario['rol']); ?>
                                </span>
                            </td>
                            <td>
                                <span class="badge bg-<?php echo $usuario['estado'] === 'activo' ? 'success' : 'danger'; ?>">
                                    <?php echo ucfirst($usuario['estado']); ?>
                                </span>
                            </td>
                            <td>
                                <strong><?php echo $usuario['total_ventas']; ?></strong> venta(s)<br>
                                <small class="text-muted">$<?php echo number_format($usuario['total_gastado'], 0); ?></small>
                            </td>
                            <td>
                                <small><?php echo date('d/m/Y', strtotime($usuario['fecha_registro'])); ?></small><br>
                                <?php if ($usuario['ultima_sesion']): ?>
                                    <small class="text-muted">Últ: <?php echo date('d/m/Y', strtotime($usuario['ultima_sesion'])); ?></small>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="btn-group" role="group">
                                    <button class="btn btn-sm btn-outline-primary" 
                                            onclick="editarUsuario(<?php echo htmlspecialchars(json_encode($usuario)); ?>)"
                                            title="Editar">
                                        <i class="bi bi-pencil"></i>
                                    </button>
                                    <?php if ($usuario['id'] != $_SESSION['usuario_id']): ?>
                                    <button class="btn btn-sm btn-outline-<?php echo $usuario['estado'] === 'activo' ? 'warning' : 'success'; ?>" 
                                            onclick="cambiarEstado(<?php echo $usuario['id']; ?>, '<?php echo $usuario['estado'] === 'activo' ? 'inactivo' : 'activo'; ?>')"
                                            title="<?php echo $usuario['estado'] === 'activo' ? 'Desactivar' : 'Activar'; ?>">
                                        <i class="bi bi-<?php echo $usuario['estado'] === 'activo' ? 'x-circle' : 'check-circle'; ?>"></i>
                                    </button>
                                    <?php if ($usuario['total_ventas'] == 0): ?>

                                    <?php endif; ?>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Modal Editar Usuario -->
    <div class="modal fade" id="modalEditarUsuario" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form method="POST">
                    <input type="hidden" name="accion" value="actualizar">
                    <input type="hidden" name="id" id="edit_id">
                    <div class="modal-header">
                        <h5 class="modal-title"><i class="bi bi-pencil"></i> Editar Usuario</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Nombre *</label>
                                <input type="text" name="nombre" id="edit_nombre" class="form-control" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Apellido *</label>
                                <input type="text" name="apellido" id="edit_apellido" class="form-control" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Email *</label>
                                <input type="email" name="email" id="edit_email" class="form-control" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Teléfono</label>
                                <input type="tel" name="telefono" id="edit_telefono" class="form-control">
                            </div>
                            <div class="col-12">
                                <label class="form-label">Dirección</label>
                                <textarea name="direccion" id="edit_direccion" class="form-control" rows="2"></textarea>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Rol</label>
                                <select name="rol" id="edit_rol" class="form-select">
                                    <option value="cliente">Cliente</option>
                                    <option value="admin">Administrador</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Estado</label>
                                <select name="estado" id="edit_estado" class="form-select">
                                    <option value="activo">Activo</option>
                                    <option value="inactivo">Inactivo</option>
                                </select>
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

    <!-- Forms ocultos -->
    <form method="POST" id="formEstado" style="display:none;">
        <input type="hidden" name="accion" value="cambiar_estado">
        <input type="hidden" name="id" id="estado_id">
        <input type="hidden" name="estado" id="estado_valor">
    </form>

    <form method="POST" id="formEliminar" style="display:none;">
        <input type="hidden" name="accion" value="eliminar">
        <input type="hidden" name="id" id="delete_id">
    </form>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function editarUsuario(usuario) {
            document.getElementById('edit_id').value = usuario.id;
            document.getElementById('edit_nombre').value = usuario.nombre;
            document.getElementById('edit_apellido').value = usuario.apellido;
            document.getElementById('edit_email').value = usuario.email;
            document.getElementById('edit_telefono').value = usuario.telefono || '';
            document.getElementById('edit_direccion').value = usuario.direccion || '';
            document.getElementById('edit_rol').value = usuario.rol;
            document.getElementById('edit_estado').value = usuario.estado;
            
            new bootstrap.Modal(document.getElementById('modalEditarUsuario')).show();
        }
        
        function cambiarEstado(id, estado) {
            const accion = estado === 'activo' ? 'activar' : 'desactivar';
            if (confirm('¿Estás seguro de ' + accion + ' este usuario?')) {
                document.getElementById('estado_id').value = id;
                document.getElementById('estado_valor').value = estado;
                document.getElementById('formEstado').submit();
            }
        }
        
        function eliminarUsuario(id, nombre) {
            if (confirm('¿Estás seguro de eliminar a "' + nombre + '"?\n\nEsta acción no se puede deshacer.')) {
                document.getElementById('delete_id').value = id;
                document.getElementById('formEliminar').submit();
            }
        }
    </script>
</body>
</html>