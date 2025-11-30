<?php
/**
 * CONFIGURACIÓN DE SUBIDA DE IMÁGENES
 */

// Directorio donde se guardarán las imágenes
define('UPLOAD_DIR', __DIR__ . '/../uploads/productos/');
define('UPLOAD_URL', '/uploads/productos/');

// Tamaño máximo de archivo (5MB)
define('MAX_FILE_SIZE', 5 * 1024 * 1024);

// Tipos de archivo permitidos
define('ALLOWED_TYPES', ['image/jpeg', 'image/png', 'image/gif', 'image/webp']);
define('ALLOWED_EXTENSIONS', ['jpg', 'jpeg', 'png', 'gif', 'webp']);

/**
 * Función para subir imagen de producto
 */
function subirImagenProducto($file, $producto_id = null) {
    $errores = [];
    
    // Verificar que se haya subido un archivo
    if (!isset($file) || $file['error'] === UPLOAD_ERR_NO_FILE) {
        return ['success' => false, 'error' => 'No se seleccionó ningún archivo'];
    }
    
    // Verificar errores de subida
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return ['success' => false, 'error' => 'Error al subir el archivo'];
    }
    
    // Verificar tamaño
    if ($file['size'] > MAX_FILE_SIZE) {
        return ['success' => false, 'error' => 'El archivo es demasiado grande (máx. 5MB)'];
    }
    
    // Verificar tipo MIME
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
    
    if (!in_array($mime, ALLOWED_TYPES)) {
        return ['success' => false, 'error' => 'Tipo de archivo no permitido. Solo JPG, PNG, GIF o WEBP'];
    }
    
    // Verificar extensión
    $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($extension, ALLOWED_EXTENSIONS)) {
        return ['success' => false, 'error' => 'Extensión de archivo no permitida'];
    }
    
    // Crear directorio si no existe
    if (!file_exists(UPLOAD_DIR)) {
        mkdir(UPLOAD_DIR, 0755, true);
    }
    
    // Generar nombre único para el archivo
    $nombre_archivo = uniqid('producto_') . '_' . time() . '.' . $extension;
    $ruta_destino = UPLOAD_DIR . $nombre_archivo;
    
    // Mover archivo
    if (move_uploaded_file($file['tmp_name'], $ruta_destino)) {
        return [
            'success' => true,
            'nombre_archivo' => $nombre_archivo,
            'ruta_completa' => $ruta_destino,
            'url' => UPLOAD_URL . $nombre_archivo
        ];
    } else {
        return ['success' => false, 'error' => 'Error al mover el archivo al servidor'];
    }
}

/**
 * Función para eliminar imagen
 */
function eliminarImagenProducto($nombre_archivo) {
    if (empty($nombre_archivo)) {
        return true;
    }
    
    $ruta = UPLOAD_DIR . $nombre_archivo;
    if (file_exists($ruta)) {
        return unlink($ruta);
    }
    return true;
}

/**
 * Función para redimensionar imagen (opcional)
 */
function redimensionarImagen($ruta_origen, $ancho_max = 800, $alto_max = 800) {
    list($ancho_orig, $alto_orig, $tipo) = getimagesize($ruta_origen);
    
    // Si la imagen ya es más pequeña, no hacer nada
    if ($ancho_orig <= $ancho_max && $alto_orig <= $alto_max) {
        return true;
    }
    
    // Calcular nuevas dimensiones manteniendo proporción
    $ratio = min($ancho_max / $ancho_orig, $alto_max / $alto_orig);
    $nuevo_ancho = round($ancho_orig * $ratio);
    $nuevo_alto = round($alto_orig * $ratio);
    
    // Crear imagen según tipo
    switch ($tipo) {
        case IMAGETYPE_JPEG:
            $imagen_orig = imagecreatefromjpeg($ruta_origen);
            break;
        case IMAGETYPE_PNG:
            $imagen_orig = imagecreatefrompng($ruta_origen);
            break;
        case IMAGETYPE_GIF:
            $imagen_orig = imagecreatefromgif($ruta_origen);
            break;
        default:
            return false;
    }
    
    // Crear nueva imagen
    $imagen_nueva = imagecreatetruecolor($nuevo_ancho, $nuevo_alto);
    
    // Preservar transparencia para PNG y GIF
    if ($tipo == IMAGETYPE_PNG || $tipo == IMAGETYPE_GIF) {
        imagealphablending($imagen_nueva, false);
        imagesavealpha($imagen_nueva, true);
    }
    
    // Redimensionar
    imagecopyresampled($imagen_nueva, $imagen_orig, 0, 0, 0, 0, 
                       $nuevo_ancho, $nuevo_alto, $ancho_orig, $alto_orig);
    
    // Guardar según tipo
    switch ($tipo) {
        case IMAGETYPE_JPEG:
            imagejpeg($imagen_nueva, $ruta_origen, 85);
            break;
        case IMAGETYPE_PNG:
            imagepng($imagen_nueva, $ruta_origen, 8);
            break;
        case IMAGETYPE_GIF:
            imagegif($imagen_nueva, $ruta_origen);
            break;
    }
    
    imagedestroy($imagen_orig);
    imagedestroy($imagen_nueva);
    
    return true;
}