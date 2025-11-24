<?php
/**
 * PROCESAR REGISTRO DE USUARIO
 * Desechables Punto Fijo
 */

require_once 'config/config.php';

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
            session_regenerate_id(true);
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
  <meta charset="utf-8">
  <meta content="width=device-width, initial-scale=1.0" name="viewport">
  <title>Registro - Desechables Punto Fijo</title>
  <meta name="description" content="Crea tu cuenta en Desechables Punto Fijo Barahoja">
  <meta name="keywords" content="registro, desechables, punto fijo">

  <!-- Favicons -->
  <link href="assets/img/favicon.png" rel="icon">
  <link href="assets/img/apple-touch-icon.png" rel="apple-touch-icon">

  <!-- Fonts -->
  <link href="https://fonts.googleapis.com" rel="preconnect">
  <link href="https://fonts.gstatic.com" rel="preconnect" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Roboto:ital,wght@0,100;0,300;0,400;0,500;0,700;0,900&family=Montserrat:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900&family=Poppins:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900&display=swap" rel="stylesheet">

  <!-- Vendor CSS Files -->
  <link href="assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
  <link href="assets/vendor/bootstrap-icons/bootstrap-icons.css" rel="stylesheet">
  <link href="assets/vendor/aos/aos.css" rel="stylesheet">

  <!-- Main CSS File -->
  <link href="assets/css/main.css" rel="stylesheet">
</head>

<body class="register-page">

  <?php include 'includes/header.php'; ?>

  <main class="main">

    <!-- Page Title -->
    <div class="page-title light-background">
      <div class="container d-lg-flex justify-content-between align-items-center">
        <h1 class="mb-2 mb-lg-0">Crear Cuenta</h1>
        <nav class="breadcrumbs">
          <ol>
            <li><a href="index.php">Inicio</a></li>
            <li class="current">Registro</li>
          </ol>
        </nav>
      </div>
    </div><!-- End Page Title -->

    <!-- Register Section -->
    <section id="register" class="register section">

      <div class="container" data-aos="fade-up" data-aos-delay="100">

        <div class="row justify-content-center">
          <div class="col-lg-10">
            <div class="registration-form-wrapper">
              <div class="form-header text-center">
                <h2>Crear Tu Cuenta</h2>
                <p>Únete a nosotros y empieza a disfrutar de nuestros productos</p>
              </div>

              <!-- Mensajes de error/éxito -->
              <?php if ($success): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert" data-aos="fade-in">
                  <i class="bi bi-check-circle-fill me-2"></i>
                  <strong>¡Registro exitoso!</strong> Tu cuenta ha sido creada. Redirigiendo al inicio...
                  <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
              <?php endif; ?>

              <?php if (!empty($errores)): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert" data-aos="fade-in">
                  <i class="bi bi-exclamation-triangle-fill me-2"></i>
                  <strong>Por favor corrige los siguientes errores:</strong>
                  <ul class="mb-0 mt-2">
                    <?php foreach ($errores as $error): ?>
                      <li><?php echo htmlspecialchars($error); ?></li>
                    <?php endforeach; ?>
                  </ul>
                  <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
              <?php endif; ?>

              <?php if (!$success): ?>
              <div class="row">
                <div class="col-lg-8 mx-auto">
                  <form action="" method="POST" id="formRegistro">
                    
                    <!-- Nombre y Apellido -->
                    <div class="row mb-3">
                      <div class="col-md-6">
                        <div class="form-floating">
                          <input type="text" 
                                 class="form-control" 
                                 id="nombre" 
                                 name="nombre" 
                                 placeholder="Nombre" 
                                 value="<?php echo htmlspecialchars($_POST['nombre'] ?? ''); ?>"
                                 required 
                                 autocomplete="given-name">
                          <label for="nombre">
                            <i class="bi bi-person me-2"></i>Nombre
                          </label>
                        </div>
                      </div>
                      <div class="col-md-6">
                        <div class="form-floating">
                          <input type="text" 
                                 class="form-control" 
                                 id="apellido" 
                                 name="apellido" 
                                 placeholder="Apellido" 
                                 value="<?php echo htmlspecialchars($_POST['apellido'] ?? ''); ?>"
                                 required 
                                 autocomplete="family-name">
                          <label for="apellido">
                            <i class="bi bi-person me-2"></i>Apellido
                          </label>
                        </div>
                      </div>
                    </div>

                    <!-- Email -->
                    <div class="form-floating mb-3">
                      <input type="email" 
                             class="form-control" 
                             id="email" 
                             name="email" 
                             placeholder="Email" 
                             value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>"
                             required 
                             autocomplete="email">
                      <label for="email">
                        <i class="bi bi-envelope me-2"></i>Correo Electrónico
                      </label>
                    </div>

                    <!-- Teléfono -->
                    <div class="form-floating mb-3">
                      <input type="tel" 
                             class="form-control" 
                             id="telefono" 
                             name="telefono" 
                             placeholder="Teléfono" 
                             value="<?php echo htmlspecialchars($_POST['telefono'] ?? ''); ?>"
                             pattern="[0-9]{10}"
                             title="Ingresa 10 dígitos sin espacios"
                             required 
                             autocomplete="tel">
                      <label for="telefono">
                        <i class="bi bi-telephone me-2"></i>Teléfono (10 dígitos)
                      </label>
                    </div>

                    <!-- Dirección -->
                    <div class="form-floating mb-3">
                      <textarea class="form-control" 
                                id="direccion" 
                                name="direccion" 
                                placeholder="Dirección" 
                                style="height: 100px"
                                autocomplete="street-address"><?php echo htmlspecialchars($_POST['direccion'] ?? ''); ?></textarea>
                      <label for="direccion">
                        <i class="bi bi-geo-alt me-2"></i>Dirección (Opcional)
                      </label>
                    </div>

                    <!-- Contraseñas -->
                    <div class="row mb-3">
                      <div class="col-md-6">
                        <div class="form-floating position-relative">
                          <input type="password" 
                                 class="form-control" 
                                 id="password" 
                                 name="password" 
                                 placeholder="Contraseña" 
                                 minlength="6"
                                 required 
                                 autocomplete="new-password">
                          <label for="password">
                            <i class="bi bi-lock me-2"></i>Contraseña (mín. 6 caracteres)
                          </label>
                          <span class="password-toggle" onclick="togglePassword('password')" style="position: absolute; right: 15px; top: 50%; transform: translateY(-50%); cursor: pointer; z-index: 10;">
                            <i class="bi bi-eye"></i>
                          </span>
                        </div>
                      </div>
                      <div class="col-md-6">
                        <div class="form-floating position-relative">
                          <input type="password" 
                                 class="form-control" 
                                 id="confirmar_password" 
                                 name="confirmar_password" 
                                 placeholder="Confirmar Contraseña" 
                                 minlength="6"
                                 required 
                                 autocomplete="new-password">
                          <label for="confirmar_password">
                            <i class="bi bi-lock me-2"></i>Confirmar Contraseña
                          </label>
                          <span class="password-toggle" onclick="togglePassword('confirmar_password')" style="position: absolute; right: 15px; top: 50%; transform: translateY(-50%); cursor: pointer; z-index: 10;">
                            <i class="bi bi-eye"></i>
                          </span>
                        </div>
                      </div>
                    </div>

                    <!-- Términos y Condiciones -->
                    <div class="form-check mb-4">
                      <input class="form-check-input" 
                             type="checkbox" 
                             id="termsCheck" 
                             name="termsCheck" 
                             required>
                      <label class="form-check-label" for="termsCheck">
                        Acepto los <a href="terminos.php" target="_blank">Términos y Condiciones</a> y la <a href="privacidad.php" target="_blank">Política de Privacidad</a>
                      </label>
                    </div>

                    <!-- Botón de Registro -->
                    <div class="d-grid mb-4">
                      <button type="submit" class="btn btn-register">
                        <i class="bi bi-person-plus-fill me-2"></i>
                        Crear Cuenta
                      </button>
                    </div>

                    <!-- Link a Login -->
                    <div class="login-link text-center">
                      <p>¿Ya tienes una cuenta? <a href="login.php">Inicia Sesión</a></p>
                    </div>
                  </form>
                </div>
              </div>

              <!-- Social Login -->
              <div class="social-login">
                <div class="row">
                  <div class="col-lg-8 mx-auto">
                    <div class="divider">
                      <span></span>
                    </div>
                  </div>
                </div>
              </div>
              <?php endif; ?>

              <!-- Elementos Decorativos -->
              <div class="decorative-elements">
                <div class="circle circle-1"></div>
                <div class="circle circle-2"></div>
                <div class="circle circle-3"></div>
                <div class="square square-1"></div>
                <div class="square square-2"></div>
              </div>
            </div>
          </div>
        </div>

      </div>

    </section><!-- /Register Section -->

  </main>

  <?php include 'includes/footer.php'; ?>

  <!-- Scroll Top -->
  <a href="#" id="scroll-top" class="scroll-top d-flex align-items-center justify-content-center">
    <i class="bi bi-arrow-up-short"></i>
  </a>

  <!-- Preloader -->
  <div id="preloader"></div>

  <!-- Vendor JS Files -->
  <script src="assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
  <script src="assets/vendor/aos/aos.js"></script>

  <!-- Main JS File -->
  <script src="assets/js/main.js"></script>

  <script>
    // Función para mostrar/ocultar contraseña
    function togglePassword(inputId) {
      const input = document.getElementById(inputId);
      const icon = event.currentTarget.querySelector('i');
      
      if (input.type === 'password') {
        input.type = 'text';
        icon.classList.remove('bi-eye');
        icon.classList.add('bi-eye-slash');
      } else {
        input.type = 'password';
        icon.classList.remove('bi-eye-slash');
        icon.classList.add('bi-eye');
      }
    }

    // Validación adicional en el cliente
    document.getElementById('formRegistro')?.addEventListener('submit', function(e) {
      const password = document.getElementById('password').value;
      const confirmar = document.getElementById('confirmar_password').value;
      
      if (password !== confirmar) {
        e.preventDefault();
        alert('Las contraseñas no coinciden');
        document.getElementById('confirmar_password').focus();
        return false;
      }

      // Validar teléfono
      const telefono = document.getElementById('telefono').value;
      if (!/^[0-9]{10}$/.test(telefono)) {
        e.preventDefault();
        alert('El teléfono debe tener exactamente 10 dígitos');
        document.getElementById('telefono').focus();
        return false;
      }
    });

    // Validación en tiempo real de contraseñas
    document.getElementById('confirmar_password')?.addEventListener('input', function() {
      const password = document.getElementById('password').value;
      const confirmar = this.value;
      
      if (confirmar && password !== confirmar) {
        this.setCustomValidity('Las contraseñas no coinciden');
      } else {
        this.setCustomValidity('');
      }
    });

    // Validación en tiempo real de teléfono
    document.getElementById('telefono')?.addEventListener('input', function(e) {
      // Solo permitir números
      this.value = this.value.replace(/\D/g, '');
      
      // Limitar a 10 dígitos
      if (this.value.length > 10) {
        this.value = this.value.slice(0, 10);
      }
    });
  </script>

</body>
</html>