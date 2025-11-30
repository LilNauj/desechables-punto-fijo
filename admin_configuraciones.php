<?php
/**
 * ADMIN - CONFIGURACIONES / SLIDES DE INICIO
 * Desechables Punto Fijo
 */

require_once 'config/config.php';
requerirLogin();
requerirAdmin();

$db = getDB();

$mensaje = '';
$tipo_mensaje = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $accion = $_POST['accion'] ?? '';

    // -----------------------------------
    // NUEVO SLIDE
    // -----------------------------------
    if ($accion === 'nuevo_slide') {
        $titulo = sanitize($_POST['titulo'] ?? '');
        $descripcion = sanitize($_POST['descripcion'] ?? '');
        $boton_texto = sanitize($_POST['boton_texto'] ?? '');
        $boton_url = sanitize($_POST['boton_url'] ?? '');
        $activo = isset($_POST['activo']) ? 1 : 0;
        $orden = (int) ($_POST['orden'] ?? 0);

        $errores = [];
        $ruta_relativa = '';



        if (empty($_FILES['imagen']['name'])) {
            $errores[] = "Debes seleccionar una imagen para el slide.";
        } else {
            $file = $_FILES['imagen'];

            if ($file['error'] === UPLOAD_ERR_OK) {
                $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
                $ext_permitidas = ['jpg', 'jpeg', 'png', 'webp'];

                if (!in_array($ext, $ext_permitidas)) {
                    $errores[] = "Formato de imagen no permitido. Usa JPG, PNG o WEBP.";
                } else {
                    $dir_destino = __DIR__ . '/assets/img/slides/';
                    if (!is_dir($dir_destino)) {
                        mkdir($dir_destino, 0775, true);
                    }

                    $nombre_archivo = 'slide_' . time() . '_' . rand(1000, 9999) . '.' . $ext;
                    $ruta_completa = $dir_destino . $nombre_archivo;

                    if (move_uploaded_file($file['tmp_name'], $ruta_completa)) {
                        $ruta_relativa = 'assets/img/slides/' . $nombre_archivo;
                    } else {
                        $errores[] = "No se pudo guardar la imagen en el servidor.";
                    }
                }
            } else {
                $errores[] = "Error al subir la imagen (código {$file['error']}).";
            }
        }

        if (empty($errores)) {
            $stmt = $db->prepare("
                INSERT INTO slides_inicio 
                    (titulo, descripcion, imagen, boton_texto, boton_url, activo, orden)
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->bind_param(
                "ssssiii",
                $titulo,
                $descripcion,
                $ruta_relativa,
                $boton_texto,
                $boton_url,
                $activo,
                $orden
            );

            if ($stmt->execute()) {
                $mensaje = "Slide creado correctamente.";
                $tipo_mensaje = "success";
            } else {
                $mensaje = "Error al crear slide: " . $stmt->error;
                $tipo_mensaje = "danger";
            }
            $stmt->close();
        } else {
            $mensaje = implode("<br>", $errores);
            $tipo_mensaje = "danger";
        }
    }

    // -----------------------------------
    // EDITAR SLIDE
    // -----------------------------------
    if ($accion === 'editar_slide') {
        $id = (int) ($_POST['id'] ?? 0);
        $titulo = sanitize($_POST['titulo'] ?? '');
        $descripcion = sanitize($_POST['descripcion'] ?? '');
        $boton_texto = sanitize($_POST['boton_texto'] ?? '');
        $boton_url = sanitize($_POST['boton_url'] ?? '');
        $activo = isset($_POST['activo']) ? 1 : 0;
        $orden = (int) ($_POST['orden'] ?? 0);

        $errores = [];

        if ($id <= 0) {
            $errores[] = "ID de slide inválido.";
        }

        

        // Obtener slide actual
        if (empty($errores)) {
            $stmt = $db->prepare("SELECT * FROM slides_inicio WHERE id = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $slide_actual = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            if (!$slide_actual) {
                $errores[] = "El slide no existe.";
            }
        }

        $ruta_relativa = $slide_actual['imagen'] ?? '';

        // ¿Se subió una nueva imagen?
        if (empty($errores) && !empty($_FILES['imagen']['name'])) {
            $file = $_FILES['imagen'];

            if ($file['error'] === UPLOAD_ERR_OK) {
                $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
                $ext_permitidas = ['jpg', 'jpeg', 'png', 'webp'];

                if (!in_array($ext, $ext_permitidas)) {
                    $errores[] = "Formato de imagen no permitido. Usa JPG, PNG o WEBP.";
                } else {
                    $dir_destino = __DIR__ . '/assets/img/slides/';
                    if (!is_dir($dir_destino)) {
                        mkdir($dir_destino, 0775, true);
                    }

                    $nombre_archivo = 'slide_' . time() . '_' . rand(1000, 9999) . '.' . $ext;
                    $ruta_completa = $dir_destino . $nombre_archivo;

                    if (move_uploaded_file($file['tmp_name'], $ruta_completa)) {
                        // Borrar imagen anterior si existe
                        if (!empty($slide_actual['imagen'])) {
                            $ruta_anterior = __DIR__ . '/' . $slide_actual['imagen'];
                            if (file_exists($ruta_anterior)) {
                                @unlink($ruta_anterior);
                            }
                        }

                        $ruta_relativa = 'assets/img/slides/' . $nombre_archivo;
                    } else {
                        $errores[] = "No se pudo guardar la nueva imagen en el servidor.";
                    }
                }
            } else {
                $errores[] = "Error al subir la imagen (código {$file['error']}).";
            }
        }

        if (empty($errores)) {
            $stmt = $db->prepare("
                UPDATE slides_inicio
                   SET titulo = ?, descripcion = ?, imagen = ?, 
                       boton_texto = ?, boton_url = ?, activo = ?, orden = ?
                 WHERE id = ?
            ");
            $stmt->bind_param(
                "ssssiiii",
                $titulo,
                $descripcion,
                $ruta_relativa,
                $boton_texto,
                $boton_url,
                $activo,
                $orden,
                $id
            );

            if ($stmt->execute()) {
                $mensaje = "Slide actualizado correctamente.";
                $tipo_mensaje = "success";
            } else {
                $mensaje = "Error al actualizar slide: " . $stmt->error;
                $tipo_mensaje = "danger";
            }
            $stmt->close();
        } else {
            $mensaje = implode("<br>", $errores);
            $tipo_mensaje = "danger";
        }
    }

    // -----------------------------------
    // ELIMINAR SLIDE
    // -----------------------------------
    if ($accion === 'eliminar_slide') {
        $id = (int) ($_POST['id'] ?? 0);

        if ($id > 0) {
            // Buscar para borrar imagen
            $stmt = $db->prepare("SELECT imagen FROM slides_inicio WHERE id = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $res = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            if ($res) {
                if (!empty($res['imagen'])) {
                    $ruta = __DIR__ . '/' . $res['imagen'];
                    if (file_exists($ruta)) {
                        @unlink($ruta);
                    }
                }

                $stmt = $db->prepare("DELETE FROM slides_inicio WHERE id = ?");
                $stmt->bind_param("i", $id);
                if ($stmt->execute()) {
                    $mensaje = "Slide eliminado correctamente.";
                    $tipo_mensaje = "success";
                } else {
                    $mensaje = "Error al eliminar slide: " . $stmt->error;
                    $tipo_mensaje = "danger";
                }
                $stmt->close();
            } else {
                $mensaje = "El slide no existe.";
                $tipo_mensaje = "danger";
            }
        } else {
            $mensaje = "ID de slide inválido.";
            $tipo_mensaje = "danger";
        }
    }

    // -----------------------------------
    // ACTIVAR / DESACTIVAR
    // -----------------------------------
    if ($accion === 'toggle_activo') {
        $id = (int) ($_POST['id'] ?? 0);
        $activo = (int) ($_POST['nuevo_estado'] ?? 0);

        if ($id > 0) {
            $stmt = $db->prepare("UPDATE slides_inicio SET activo = ? WHERE id = ?");
            $stmt->bind_param("ii", $activo, $id);
            if ($stmt->execute()) {
                $mensaje = "Estado actualizado.";
                $tipo_mensaje = "success";
            } else {
                $mensaje = "Error al actualizar estado: " . $stmt->error;
                $tipo_mensaje = "danger";
            }
            $stmt->close();
        } else {
            $mensaje = "ID de slide inválido.";
            $tipo_mensaje = "danger";
        }
    }
}

// OBTENER TODOS LOS SLIDES
$slides = $db->query("
    SELECT * FROM slides_inicio
    ORDER BY orden ASC, id DESC
")->fetch_all(MYSQLI_ASSOC);

?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Configuración - Slides de Inicio</title>

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

        .badge-activo {
            background-color: #dcfce7;
            color: #166534;
        }

        .badge-inactivo {
            background-color: #fee2e2;
            color: #991b1b;
        }

        .slide-thumb {
            width: 120px;
            height: 70px;
            border-radius: 10px;
            overflow: hidden;
            background: #f3f4f6;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .slide-thumb img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .table td,
        .table th {
            vertical-align: middle;
        }

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
                <a href="admin_configuraciones.php" class="active">
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
                <h2><i class="bi bi-images"></i> Slides de Inicio</h2>
                <p class="text-muted mb-0">Configura los banners que aparecen en el inicio (index.php).</p>
            </div>
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalNuevoSlide">
                <i class="bi bi-plus-lg"></i> Nuevo Slide
            </button>
        </div>

        <?php if ($mensaje): ?>
            <div class="alert alert-<?php echo $tipo_mensaje; ?> alert-dismissible fade show" role="alert">
                <?php echo $mensaje; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <div class="card shadow-sm">
            <div class="card-body">
                <?php if (empty($slides)): ?>
                    <p class="text-muted mb-0">No hay slides configurados aún.</p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table align-middle">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Imagen</th>
                                    <th>Título</th>
                                    <th>Descripción</th>
                                    <th>Botón</th>
                                    <th>Orden</th>
                                    <th>Estado</th>
                                    <th class="text-end">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($slides as $slide): ?>
                                    <tr>
                                        <td><?php echo $slide['id']; ?></td>
                                        <td>
                                            <div class="slide-thumb">
                                                <?php if (!empty($slide['imagen'])): ?>
                                                    <img src="<?php echo htmlspecialchars($slide['imagen']); ?>"
                                                        alt="<?php echo htmlspecialchars($slide['titulo']); ?>">
                                                <?php else: ?>
                                                    <i class="bi bi-image text-muted"></i>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                        <td><?php echo htmlspecialchars($slide['titulo']); ?></td>
                                        <td class="text-muted">
                                            <?php echo htmlspecialchars($slide['descripcion']); ?>
                                        </td>
                                        <td>
                                            <?php if (!empty($slide['boton_url'])): ?>
                                                <span class="badge bg-primary">
                                                    <?php echo htmlspecialchars($slide['boton_texto'] ?: 'Ver más'); ?>
                                                </span>
                                            <?php else: ?>
                                                <span class="text-muted">Sin botón</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo (int) $slide['orden']; ?></td>
                                        <td>
                                            <?php if ($slide['activo']): ?>
                                                <span class="badge badge-activo">Activo</span>
                                            <?php else: ?>
                                                <span class="badge badge-inactivo">Inactivo</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-end">
                                            <!-- Editar -->
                                            <button type="button" class="btn btn-sm btn-outline-primary me-1"
                                                data-bs-toggle="modal" data-bs-target="#modalEditarSlide"
                                                data-id="<?php echo $slide['id']; ?>"
                                                data-titulo="<?php echo htmlspecialchars($slide['titulo'], ENT_QUOTES); ?>"
                                                data-descripcion="<?php echo htmlspecialchars($slide['descripcion'], ENT_QUOTES); ?>"
                                                data-boton_texto="<?php echo htmlspecialchars($slide['boton_texto'], ENT_QUOTES); ?>"
                                                data-boton_url="<?php echo htmlspecialchars($slide['boton_url'], ENT_QUOTES); ?>"
                                                data-orden="<?php echo (int) $slide['orden']; ?>"
                                                data-activo="<?php echo (int) $slide['activo']; ?>"
                                                data-imagen="<?php echo htmlspecialchars($slide['imagen']); ?>">
                                                <i class="bi bi-pencil-square"></i>
                                            </button>

                                            <!-- Activar / desactivar -->
                                            <form action="" method="POST" class="d-inline">
                                                <input type="hidden" name="accion" value="toggle_activo">
                                                <input type="hidden" name="id" value="<?php echo $slide['id']; ?>">
                                                <input type="hidden" name="nuevo_estado"
                                                    value="<?php echo $slide['activo'] ? 0 : 1; ?>">
                                                <button type="submit"
                                                    class="btn btn-sm <?php echo $slide['activo'] ? 'btn-outline-secondary' : 'btn-outline-success'; ?>"
                                                    title="<?php echo $slide['activo'] ? 'Desactivar' : 'Activar'; ?>">
                                                    <?php if ($slide['activo']): ?>
                                                        <i class="bi bi-eye-slash"></i>
                                                    <?php else: ?>
                                                        <i class="bi bi-eye"></i>
                                                    <?php endif; ?>
                                                </button>
                                            </form>

                                            <!-- Eliminar -->
                                            <form action="" method="POST" class="d-inline"
                                                onsubmit="return confirm('¿Seguro que deseas eliminar este slide?');">
                                                <input type="hidden" name="accion" value="eliminar_slide">
                                                <input type="hidden" name="id" value="<?php echo $slide['id']; ?>">
                                                <button type="submit" class="btn btn-sm btn-outline-danger" title="Eliminar">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Modal Nuevo Slide -->
    <div class="modal fade" id="modalNuevoSlide" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <div class="modal-content">
                <form method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="accion" value="nuevo_slide">
                    <div class="modal-header">
                        <h5 class="modal-title">Nuevo Slide</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row g-3">
                            <div class="col-md-8">
                                <label class="form-label">Título *</label>
                                <input type="text" name="titulo" class="form-control" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Orden</label>
                                <input type="number" name="orden" class="form-control" value="0">
                            </div>
                            <div class="col-12">
                                <label class="form-label">Descripción</label>
                                <textarea name="descripcion" class="form-control" rows="2"></textarea>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Texto del botón (opcional)</label>
                                <input type="text" name="boton_texto" class="form-control" placeholder="Ver oferta">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">URL del botón (opcional)</label>
                                <input type="text" name="boton_url" class="form-control" placeholder="productos.php">
                            </div>
                            <div class="col-md-8">
                                <label class="form-label">Imagen *</label>
                                <input type="file" name="imagen" class="form-control" accept="image/*" required>
                                <small class="text-muted">JPG, PNG o WEBP (recomendado: horizontal).</small>
                            </div>
                            <div class="col-md-4 d-flex align-items-end">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="nuevo_activo" name="activo"
                                        checked>
                                    <label class="form-check-label" for="nuevo_activo">
                                        Slide activo
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary"
                            data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary">Guardar Slide</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal Editar Slide -->
    <div class="modal fade" id="modalEditarSlide" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <div class="modal-content">
                <form method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="accion" value="editar_slide">
                    <input type="hidden" name="id" id="edit_id">
                    <div class="modal-header">
                        <h5 class="modal-title">Editar Slide</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row g-3">
                            <div class="col-md-8">
                                <label class="form-label">Título *</label>
                                <input type="text" name="titulo" id="edit_titulo" class="form-control" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Orden</label>
                                <input type="number" name="orden" id="edit_orden" class="form-control">
                            </div>
                            <div class="col-12">
                                <label class="form-label">Descripción</label>
                                <textarea name="descripcion" id="edit_descripcion" class="form-control"
                                    rows="2"></textarea>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Texto del botón (opcional)</label>
                                <input type="text" name="boton_texto" id="edit_boton_texto" class="form-control">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">URL del botón (opcional)</label>
                                <input type="text" name="boton_url" id="edit_boton_url" class="form-control">
                            </div>
                            <div class="col-md-8">
                                <label class="form-label">Cambiar imagen (opcional)</label>
                                <input type="file" name="imagen" class="form-control" accept="image/*">
                                <small class="text-muted">Si no seleccionas nada, se mantiene la imagen actual.</small>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label d-block">Imagen actual</label>
                                <div class="slide-thumb" id="edit_thumb_container">
                                    <img src="" alt="" id="edit_thumb">
                                </div>
                            </div>
                            <div class="col-12">
                                <div class="form-check mt-2">
                                    <input class="form-check-input" type="checkbox" id="edit_activo" name="activo">
                                    <label class="form-check-label" for="edit_activo">
                                        Slide activo
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary"
                            data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary">Guardar cambios</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        // Rellenar modal de edición con los datos del slide
        const modalEditar = document.getElementById('modalEditarSlide');
        modalEditar.addEventListener('show.bs.modal', function (event) {
            const button = event.relatedTarget;

            const id = button.getAttribute('data-id');
            const titulo = button.getAttribute('data-titulo');
            const descripcion = button.getAttribute('data-descripcion');
            const boton_texto = button.getAttribute('data-boton_texto');
            const boton_url = button.getAttribute('data-boton_url');
            const orden = button.getAttribute('data-orden');
            const activo = button.getAttribute('data-activo');
            const imagen = button.getAttribute('data-imagen');

            document.getElementById('edit_id').value = id;
            document.getElementById('edit_titulo').value = titulo || '';
            document.getElementById('edit_descripcion').value = descripcion || '';
            document.getElementById('edit_boton_texto').value = boton_texto || '';
            document.getElementById('edit_boton_url').value = boton_url || '';
            document.getElementById('edit_orden').value = orden || 0;
            document.getElementById('edit_activo').checked = (parseInt(activo, 10) === 1);

            const thumb = document.getElementById('edit_thumb');
            if (imagen) {
                thumb.src = imagen;
                thumb.alt = titulo;
            } else {
                thumb.src = '';
                thumb.alt = '';
            }
        });
    </script>

</body>

</html>