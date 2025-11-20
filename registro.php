<?php
/**
 * PROCESAR REGISTRO DE USUARIO
 * Desechables Punto Fijo
 */

require_once 'config.php';

// Si ya está logueado, redirigir al inicio
if (estaLogueado()) {
    redirect('index.php');
}

$errores = [];
$success = false;

// Procesar el formulario cuando se envía
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // Obtener y sanitizar datos
    $nombre = sanitize($_POST['nombre'] ?? '');
    $apellido = sanitize($_POST['apellido'] ?? '');
    $email = sanitize($_POST['email'] ?? '');
    $telefono = sanitize($_POST['telefono'] ?? '');
    $direccion = sanitize($_POST['direccion'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirmar_password = $_POST['confirmar_password'] ?? '';
    
    // Validaciones
    if (empty($nombre)) {
        $errores[] = "El nombre es obligatorio";
    } elseif (strlen($nombre) < 2) {
        $errores[] = "El nombre debe tener al menos 2 caracteres";
    }
    
    if (empty($apellido)) {
        $errores[] = "El apellido es obligatorio";
    } elseif (strlen($apellido) < 2) {
        $errores[] = "El apellido debe tener al menos 2 caracteres";
    }
    
    if (empty($email)) {
        $errores[] = "El email es obligatorio";
    } elseif (!validarEmail($email)) {
        $errores[] = "El email no es válido";
    } else {
        // Verificar si el email ya existe
        $db = getDB();
        $stmt = $db->prepare("SELECT id FROM usuarios WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $errores[] = "Este email ya está registrado";
        }
        $stmt->close();
    }
    
    if (empty($telefono)) {
        $errores[] = "El teléfono es obligatorio";
    } elseif (!preg_match("/^[0-9]{10}$/", $telefono)) {
        $errores[] = "El teléfono debe tener 10 dígitos";
    }
    
    if (empty($password)) {
        $errores[] = "La contraseña es obligatoria";
    } elseif (strlen($password) < 6) {
        $errores[] = "La contraseña debe tener al menos 6 caracteres";
    }
    
    if ($password !== $confirmar_password) {
        $errores[] = "Las contraseñas no coinciden";
    }
    
    // Si no hay errores, registrar usuario
    if (empty($errores)) {
        $db = getDB();
        
        // Hash de la contraseña
        $password_hash = password_hash($password, PASSWORD_DEFAULT);
        
        // Preparar la consulta
        $stmt = $db->prepare("INSERT INTO usuarios (nombre, apellido, email, telefono, direccion, password, rol) VALUES (?, ?, ?, ?, ?, ?, 'cliente')");
        $stmt->bind_param("ssssss", $nombre, $apellido, $email, $telefono, $direccion, $password_hash);
        
        if ($stmt->execute()) {
            $success = true;
            
            // Obtener el ID del usuario recién creado
            $usuario_id = $stmt->insert_id;
            
            // Iniciar sesión automáticamente
            $_SESSION['usuario_id'] = $usuario_id;
            $_SESSION['nombre'] = $nombre;
            $_SESSION['apellido'] = $apellido;
            $_SESSION['email'] = $email;
            $_SESSION['rol'] = 'cliente';
            
            // Redirigir al inicio después de 2 segundos
            header("refresh:2;url=index.php");
        } else {
            $errores[] = "Error al registrar usuario: " . $stmt->error;
        }
        
        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registro - Desechables Punto Fijo</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            padding: 20px 0;
        }
        .card-registro {
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
        }
        .card-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 15px 15px 0 0 !important;
        }
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
        }
        .btn-primary:hover {
            background: linear-gradient(135deg, #764ba2 0%, #667eea 100%);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }
        .logo-container {
            width: 100px;
            height: 100px;
            margin: 0 auto 20px;
            background: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        .logo-container i {
            font-size: 3rem;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        .form-control:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-8 col-lg-6">
                <div class="card card-registro">
                    <div class="card-header text-center py-4">
                        <div class="logo-container">
                            <i class="bi bi-shop"></i>
                        </div>
                        <h3 class="mb-0">Crear Cuenta</h3>
                        <p class="mb-0">Desechables Punto Fijo Barahoja</p>
                    </div>
                    <div class="card-body p-4">
                        
                        <?php if ($success): ?>
                            <div class="alert alert-success alert-dismissible fade show" role="alert">
                                <i class="bi bi-check-circle-fill me-2"></i>
                                <strong>¡Registro exitoso!</strong> Redirigiendo al inicio...
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        <?php endif; ?>
                        
                        <?php if (!empty($errores)): ?>
                            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                <i class="bi bi-exclamation-triangle-fill me-2"></i>
                                <strong>Errores:</strong>
                                <ul class="mb-0 mt-2">
                                    <?php foreach ($errores as $error): ?>
                                        <li><?php echo $error; ?></li>
                                    <?php endforeach; ?>
                                </ul>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        <?php endif; ?>
                        
                        <?php if (!$success): ?>
                        <form method="POST" action="" id="formRegistro">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="nombre" class="form-label">
                                        <i class="bi bi-person-fill text-primary"></i> Nombre *
                                    </label>
                                    <input type="text" class="form-control" id="nombre" name="nombre" 
                                           value="<?php echo htmlspecialchars($_POST['nombre'] ?? ''); ?>" 
                                           required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="apellido" class="form-label">
                                        <i class="bi bi-person-fill text-primary"></i> Apellido *
                                    </label>
                                    <input type="text" class="form-control" id="apellido" name="apellido" 
                                           value="<?php echo htmlspecialchars($_POST['apellido'] ?? ''); ?>" 
                                           required>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="email" class="form-label">
                                    <i class="bi bi-envelope-fill text-primary"></i> Correo Electrónico *
                                </label>
                                <input type="email" class="form-control" id="email" name="email" 
                                       value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" 
                                       required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="telefono" class="form-label">
                                    <i class="bi bi-telephone-fill text-primary"></i> Teléfono *
                                </label>
                                <input type="tel" class="form-control" id="telefono" name="telefono" 
                                       placeholder="3177268740" 
                                       value="<?php echo htmlspecialchars($_POST['telefono'] ?? ''); ?>" 
                                       pattern="[0-9]{10}" required>
                                <small class="text-muted">10 dígitos sin espacios</small>
                            </div>
                            
                            <div class="mb-3">
                                <label for="direccion" class="form-label">
                                    <i class="bi bi-geo-alt-fill text-primary"></i> Dirección
                                </label>
                                <textarea class="form-control" id="direccion" name="direccion" 
                                          rows="2" placeholder="Calle 4ta #6-51, Barrio Barahoja"><?php echo htmlspecialchars($_POST['direccion'] ?? ''); ?></textarea>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="password" class="form-label">
                                        <i class="bi bi-lock-fill text-primary"></i> Contraseña *
                                    </label>
                                    <input type="password" class="form-control" id="password" name="password" 
                                           minlength="6" required>
                                    <small class="text-muted">Mínimo 6 caracteres</small>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="confirmar_password" class="form-label">
                                        <i class="bi bi-lock-fill text-primary"></i> Confirmar Contraseña *
                                    </label>
                                    <input type="password" class="form-control" id="confirmar_password" 
                                           name="confirmar_password" minlength="6" required>
                                </div>
                            </div>
                            
                            <div class="d-grid gap-2 mt-4">
                                <button type="submit" class="btn btn-primary btn-lg">
                                    <i class="bi bi-person-plus-fill me-2"></i>Crear Cuenta
                                </button>
                            </div>
                        </form>
                        <?php endif; ?>
                        
                        <hr class="my-4">
                        
                        <div class="text-center">
                            <p class="mb-0">¿Ya tienes cuenta? 
                                <a href="login.php" class="text-decoration-none fw-bold">Inicia Sesión</a>
                            </p>
                        </div>
                    </div>
                </div>
                
                <div class="text-center mt-3 text-white">
                    <p class="mb-0">
                        <i class="bi bi-geo-alt-fill"></i> Calle 4ta #6-51, Barrio Barahoja, Aguachica - Cesar
                    </p>
                    <p>
                        <i class="bi bi-telephone-fill"></i> 317 726 8740 | 315 744 1535
                    </p>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Validación adicional en el cliente
        document.getElementById('formRegistro')?.addEventListener('submit', function(e) {
            const password = document.getElementById('password').value;
            const confirmar = document.getElementById('confirmar_password').value;
            
            if (password !== confirmar) {
                e.preventDefault();
                alert('Las contraseñas no coinciden');
                return false;
            }
        });
    </script>
</body>
</html>