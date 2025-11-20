<?php
/**
 * SISTEMA DE LOGIN
 * Desechables Punto Fijo
 */

require_once 'config.php';

// Si ya está logueado, redirigir según el rol
if (estaLogueado()) {
    if (esAdmin()) {
        redirect('admin.php');
    } else {
        redirect('index.php');
    }
}

$error = '';

// Procesar el formulario cuando se envía
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // Obtener y sanitizar datos
    $email = sanitize($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $recordar = isset($_POST['recordar']);
    
    // Validaciones básicas
    if (empty($email) || empty($password)) {
        $error = "Por favor complete todos los campos";
    } elseif (!validarEmail($email)) {
        $error = "Email no válido";
    } else {
        // Buscar usuario en la base de datos
        $db = getDB();
        $stmt = $db->prepare("SELECT id, nombre, apellido, email, password, rol, estado FROM usuarios WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 1) {
            $usuario = $result->fetch_assoc();
            
            // Verificar si el usuario está activo
            if ($usuario['estado'] === 'inactivo') {
                $error = "Tu cuenta está inactiva. Contacta al administrador.";
            } 
            // Verificar la contraseña
            elseif (password_verify($password, $usuario['password'])) {
                
                // Regenerar ID de sesión para prevenir session fixation
                session_regenerate_id(true);
                
                // Guardar datos en la sesión
                $_SESSION['usuario_id'] = $usuario['id'];
                $_SESSION['nombre'] = $usuario['nombre'];
                $_SESSION['apellido'] = $usuario['apellido'];
                $_SESSION['email'] = $usuario['email'];
                $_SESSION['rol'] = $usuario['rol'];
                
                // Actualizar última sesión
                $update_stmt = $db->prepare("UPDATE usuarios SET ultima_sesion = NOW() WHERE id = ?");
                $update_stmt->bind_param("i", $usuario['id']);
                $update_stmt->execute();
                $update_stmt->close();
                
                // Redirigir según el rol
                if ($usuario['rol'] === 'admin') {
                    header("Location: admin.php");
                    exit();
                } else {
                    header("Location: index.php");
                    exit();
                }
            } else {
                $error = "Email o contraseña incorrectos";
            }
        } else {
            $error = "Email o contraseña incorrectos";
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
    <title>Iniciar Sesión - Desechables Punto Fijo</title>
    
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
        }
        .card-login {
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
            transition: all 0.3s;
        }
        .btn-primary:hover {
            background: linear-gradient(135deg, #764ba2 0%, #667eea 100%);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }
        .logo-container {
            width: 120px;
            height: 120px;
            margin: 0 auto 20px;
            background: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        .logo-container i {
            font-size: 4rem;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        .form-control:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
        }
        .divider {
            display: flex;
            align-items: center;
            text-align: center;
            margin: 20px 0;
        }
        .divider::before,
        .divider::after {
            content: '';
            flex: 1;
            border-bottom: 1px solid #dee2e6;
        }
        .divider span {
            padding: 0 10px;
            color: #6c757d;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-6 col-lg-5">
                <div class="card card-login">
                    <div class="card-header text-center py-4">
                        <div class="logo-container">
                            <i class="bi bi-shop"></i>
                        </div>
                        <h3 class="mb-0">Iniciar Sesión</h3>
                        <p class="mb-0">Desechables Punto Fijo Barahoja</p>
                    </div>
                    <div class="card-body p-4">
                        
                        <?php if (!empty($error)): ?>
                            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                <i class="bi bi-exclamation-triangle-fill me-2"></i>
                                <?php echo $error; ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        <?php endif; ?>
                        
                        <form method="POST" action="">
                            <div class="mb-3">
                                <label for="email" class="form-label">
                                    <i class="bi bi-envelope-fill text-primary"></i> Correo Electrónico
                                </label>
                                <input type="email" class="form-control form-control-lg" id="email" 
                                       name="email" placeholder="tu@email.com" 
                                       value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" 
                                       required autofocus>
                            </div>
                            
                            <div class="mb-3">
                                <label for="password" class="form-label">
                                    <i class="bi bi-lock-fill text-primary"></i> Contraseña
                                </label>
                                <input type="password" class="form-control form-control-lg" 
                                       id="password" name="password" placeholder="••••••••" required>
                            </div>
                            
                            <div class="mb-3 form-check">
                                <input type="checkbox" class="form-check-input" id="recordar" name="recordar">
                                <label class="form-check-label" for="recordar">
                                    Recordarme
                                </label>
                            </div>
                            
                            <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-primary btn-lg">
                                    <i class="bi bi-box-arrow-in-right me-2"></i>Iniciar Sesión
                                </button>
                            </div>
                        </form>
                        
                        <div class="divider">
                            <span>o</span>
                        </div>
                        
                        <div class="text-center">
                            <p class="mb-3">¿No tienes una cuenta?</p>
                            <a href="registro.php" class="btn btn-outline-primary btn-lg w-100">
                                <i class="bi bi-person-plus-fill me-2"></i>Crear Cuenta Nueva
                            </a>
                        </div>
                        
                        <hr class="my-4">
                        
                        <div class="text-center">
                            <small class="text-muted">
                                <i class="bi bi-shield-check text-success"></i> 
                                Tus datos están protegidos
                            </small>
                        </div>
                    </div>
                </div>
                
                <!-- Credenciales de prueba -->
                <div class="card mt-3" style="background: rgba(255,255,255,0.9);">
                    <div class="card-body">
                        <h6 class="card-title text-center mb-3">
                            <i class="bi bi-info-circle text-primary"></i> Credenciales de Prueba
                        </h6>
                        <div class="row text-center">
                            <div class="col-6">
                                <small class="text-muted d-block">Admin</small>
                                <code>admin@puntofijo.com</code><br>
                                <code>admin123</code>
                            </div>
                            <div class="col-6">
                                <small class="text-muted d-block">Cliente</small>
                                <code>cliente@demo.com</code><br>
                                <code>admin123</code>
                            </div>
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
</body>
</html>