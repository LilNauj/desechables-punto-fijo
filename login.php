<?php
/**
 * SISTEMA DE LOGIN
 * Desechables Punto Fijo
 */

require_once 'config/config.php';

// Si ya estÃ¡ logueado, redirigir segÃºn el rol
if (estaLogueado()) {
  if (esAdmin()) {
    redirect('admin.php');
  } else {
    redirect('index.php');
  }
}

$error = '';
$success = '';

// Procesar el formulario cuando se envÃ­a
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

  // Obtener y sanitizar datos
  $email = sanitize($_POST['email'] ?? '');
  $password = $_POST['password'] ?? '';
  $recordar = isset($_POST['recordar']);

  // Validaciones bÃ¡sicas
  if (empty($email) || empty($password)) {
    $error = "Por favor complete todos los campos";
  } elseif (!validarEmail($email)) {
    $error = "Email no vÃ¡lido";
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

        // Regenerar ID de sesion para prevenir session fixation
        session_regenerate_id(true);

        // Guardar datos en la sesion
        $_SESSION['usuario_id'] = $usuario['id'];
        $_SESSION['nombre'] = $usuario['nombre'];
        $_SESSION['apellido'] = $usuario['apellido'];
        $_SESSION['email'] = $usuario['email'];
        $_SESSION['rol'] = $usuario['rol'];

        // Actualizar Ãºltima sesion
        $update_stmt = $db->prepare("UPDATE usuarios SET ultima_sesion = NOW() WHERE id = ?");
        $update_stmt->bind_param("i", $usuario['id']);
        $update_stmt->execute();
        $update_stmt->close();

        // Redirigir segÃºn el rol
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
  <meta charset="utf-8">
  <meta content="width=device-width, initial-scale=1.0" name="viewport">
  <title>Iniciar Sesion - Desechables Punto Fijo</title>
  <meta name="description" content="Inicia sesion en Desechables Punto Fijo Barahoja">
  <meta name="keywords" content="login, desechables, punto fijo">

  <!-- Favicons -->
  <link href="assets/img/favicon.png" rel="icon">
  <link href="assets/img/apple-touch-icon.png" rel="apple-touch-icon">

  <!-- Fonts -->
  <link href="https://fonts.googleapis.com" rel="preconnect">
  <link href="https://fonts.gstatic.com" rel="preconnect" crossorigin>
  <link
    href="https://fonts.googleapis.com/css2?family=Roboto:ital,wght@0,100;0,300;0,400;0,500;0,700;0,900&family=Montserrat:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900&family=Poppins:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900&display=swap"
    rel="stylesheet">

  <!-- Vendor CSS Files -->
  <link href="assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
  <link href="assets/vendor/bootstrap-icons/bootstrap-icons.css" rel="stylesheet">
  <link href="assets/vendor/aos/aos.css" rel="stylesheet">

  <!-- Main CSS File -->
  <link href="assets/css/main.css" rel="stylesheet">
</head>

<body class="login-page">

  <?php include 'includes/header.php'; ?>

  <main class="main">

    <!-- Page Title -->
    <div class="page-title light-background position-relative">
      <div class="container">
        <nav class="breadcrumbs">
          <ol>
            <li><a href="index.php">Inicio</a></li>
            <li class="current">Iniciar Sesión</li>
          </ol>
        </nav>
        <h1>Iniciar Sesión</h1>
      </div>
    </div><!-- End Page Title -->

    <!-- Login Section -->
    <section id="login" class="login section">

      <div class="container" data-aos="fade-up" data-aos-delay="100">

        <div class="row justify-content-center">
          <div class="col-lg-8 col-md-10">
            <div class="auth-container" data-aos="fade-in" data-aos-delay="200">

              <!-- Mensajes de error/Ã©xito -->
              <?php if (!empty($error)): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                  <i class="bi bi-exclamation-triangle-fill me-2"></i>
                  <?php echo $error; ?>
                  <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
              <?php endif; ?>

              <?php if (!empty($success)): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                  <i class="bi bi-check-circle-fill me-2"></i>
                  <?php echo $success; ?>
                  <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
              <?php endif; ?>

              <!-- Login Form -->
              <div class="auth-form login-form active">
                <div class="form-header">
                  <h3>Bienvenido de Nuevo</h3>
                  <p>Inicia sesion en tu cuenta</p>
                </div>

                <form class="auth-form-content" method="POST" action="">
                  <div class="input-group mb-3">
                    <span class="input-icon">
                      <i class="bi bi-envelope"></i>
                    </span>
                    <input type="email" class="form-control" name="email" placeholder="Correo electrónico"
                      value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" required autocomplete="email">
                  </div>

                  <div class="input-group mb-3">
                    <span class="input-icon">
                      <i class="bi bi-lock"></i>
                    </span>
                    <input type="password" class="form-control" name="password" placeholder="Contraseña" required
                      autocomplete="current-password">
                    <span class="password-toggle" onclick="togglePassword(this)">
                      <i class="bi bi-eye"></i>
                    </span>
                  </div>

                  <div class="form-options mb-4">
                    <div class="remember-me">
                      <input type="checkbox" id="rememberLogin" name="recordar">
                      <label for="rememberLogin">Recordarme</label>
                    </div>
                    <a href="recuperar_password.php" class="forgot-password">¿Olvidaste tu contraseña?</a>
                  </div>

                  <button type="submit" class="auth-btn primary-btn mb-3">
                    Iniciar sesion
                    <i class="bi bi-arrow-right"></i>
                  </button>

                  <div class="switch-form">
                    <span>¿No tienes una cuenta?</span>
                    <a href="registro.php" class="switch-btn">Crear cuenta</a>
                  </div>
                </form>
              </div>

              <!-- InformaciÃ³n adicional -->
              <div class="mt-4 p-3 bg-light rounded">

              </div>

            </div>
          </div>
        </div>

      </div>

    </section><!-- /Login Section -->

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
    function togglePassword(element) {
      const input = element.previousElementSibling;
      const icon = element.querySelector('i');

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
  </script>

</body>

</html>