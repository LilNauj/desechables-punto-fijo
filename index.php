<?php
/**
 * PÁGINA PRINCIPAL
 * Desechables Punto Fijo
 */

require_once 'config.php';

// Si no está logueado, redirigir al login
if (!estaLogueado()) {
    redirect('login.php');
}

// Obtener información del usuario
$nombre_completo = $_SESSION['nombre'] . ' ' . $_SESSION['apellido'];
$email = $_SESSION['email'];
$rol = $_SESSION['rol'];
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inicio - Desechables Punto Fijo</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
        }
        .navbar {
            background: rgba(255,255,255,0.95) !important;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .hero-section {
            padding: 80px 0;
            color: white;
            text-align: center;
        }
        .welcome-card {
            background: rgba(255,255,255,0.95);
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            margin: 30px 0;
        }
        .feature-card {
            background: white;
            border-radius: 15px;
            padding: 30px;
            text-align: center;
            transition: transform 0.3s;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            height: 100%;
        }
        .feature-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
        }
        .feature-icon {
            font-size: 3rem;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 20px;
        }
        .badge-rol {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 0.9rem;
        }
        .btn-gradient {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            padding: 12px 30px;
            border-radius: 25px;
            transition: all 0.3s;
        }
        .btn-gradient:hover {
            background: linear-gradient(135deg, #764ba2 0%, #667eea 100%);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
            color: white;
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-light sticky-top">
        <div class="container">
            <a class="navbar-brand fw-bold" href="index.php">
                <i class="bi bi-shop text-primary"></i> Desechables Punto Fijo
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link active" href="index.php">
                            <i class="bi bi-house-fill"></i> Inicio
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#productos">
                            <i class="bi bi-grid-fill"></i> Productos
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#carrito">
                            <i class="bi bi-cart-fill"></i> Carrito
                            <span class="badge bg-danger">0</span>
                        </a>
                    </li>
                    <?php if (esAdmin()): ?>
                    <li class="nav-item">
                        <a class="nav-link" href="admin.php">
                            <i class="bi bi-gear-fill"></i> Admin
                        </a>
                    </li>
                    <?php endif; ?>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown">
                            <i class="bi bi-person-circle"></i> <?php echo $_SESSION['nombre']; ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="#"><i class="bi bi-person"></i> Mi Perfil</a></li>
                            <li><a class="dropdown-item" href="#"><i class="bi bi-clock-history"></i> Mis Pedidos</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item text-danger" href="logout.php"><i class="bi bi-box-arrow-right"></i> Cerrar Sesión</a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <div class="hero-section">
        <div class="container">
            <h1 class="display-4 fw-bold mb-3">¡Bienvenido a Desechables Punto Fijo! 🎉</h1>
            <p class="lead">Tu tienda de confianza en Barahoja, Aguachica - Cesar</p>
        </div>
    </div>

    <!-- Welcome Card -->
    <div class="container">
        <div class="welcome-card">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h2>¡Hola, <?php echo $nombre_completo; ?>! 👋</h2>
                    <p class="text-muted mb-2">
                        <i class="bi bi-envelope"></i> <?php echo $email; ?>
                    </p>
                    <span class="badge-rol">
                        <i class="bi bi-star-fill"></i> 
                        <?php echo $rol === 'admin' ? 'Administrador' : 'Cliente'; ?>
                    </span>
                </div>
                <div class="col-md-4 text-end">
                    <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($nombre_completo); ?>&size=150&background=667eea&color=fff" 
                         alt="Avatar" class="rounded-circle" width="150">
                </div>
            </div>
        </div>
    </div>

    <!-- Features -->
    <div class="container py-5">
        <h2 class="text-center text-white mb-5">¿Qué deseas hacer hoy?</h2>
        <div class="row g-4">
            <div class="col-md-4">
                <div class="feature-card">
                    <i class="bi bi-grid-fill feature-icon"></i>
                    <h4>Ver Productos</h4>
                    <p class="text-muted">Explora nuestro catálogo completo de desechables</p>
                    <a href="#productos" class="btn btn-gradient">
                        <i class="bi bi-arrow-right"></i> Explorar
                    </a>
                </div>
            </div>
            <div class="col-md-4">
                <div class="feature-card">
                    <i class="bi bi-cart-fill feature-icon"></i>
                    <h4>Mi Carrito</h4>
                    <p class="text-muted">Revisa y completa tu pedido</p>
                    <a href="#carrito" class="btn btn-gradient">
                        <i class="bi bi-arrow-right"></i> Ver Carrito
                    </a>
                </div>
            </div>
            <div class="col-md-4">
                <div class="feature-card">
                    <i class="bi bi-clock-history feature-icon"></i>
                    <h4>Mis Pedidos</h4>
                    <p class="text-muted">Historial de compras realizadas</p>
                    <a href="#pedidos" class="btn btn-gradient">
                        <i class="bi bi-arrow-right"></i> Ver Historial
                    </a>
                </div>
            </div>
        </div>

        <?php if (esAdmin()): ?>
        <div class="row g-4 mt-4">
            <div class="col-md-12">
                <div class="alert alert-info" role="alert">
                    <h5 class="alert-heading">
                        <i class="bi bi-shield-check"></i> Panel de Administración
                    </h5>
                    <p>Como administrador, tienes acceso a funciones especiales:</p>
                    <hr>
                    <div class="row text-center">
                        <div class="col-md-3">
                            <i class="bi bi-box-seam text-primary" style="font-size: 2rem;"></i>
                            <p class="mb-0 mt-2"><strong>Gestionar Productos</strong></p>
                        </div>
                        <div class="col-md-3">
                            <i class="bi bi-people text-success" style="font-size: 2rem;"></i>
                            <p class="mb-0 mt-2"><strong>Ver Usuarios</strong></p>
                        </div>
                        <div class="col-md-3">
                            <i class="bi bi-graph-up text-warning" style="font-size: 2rem;"></i>
                            <p class="mb-0 mt-2"><strong>Reportes de Ventas</strong></p>
                        </div>
                        <div class="col-md-3">
                            <i class="bi bi-tags text-danger" style="font-size: 2rem;"></i>
                            <p class="mb-0 mt-2"><strong>Categorías</strong></p>
                        </div>
                    </div>
                    <hr>
                    <a href="admin.php" class="btn btn-primary btn-lg">
                        <i class="bi bi-gear-fill"></i> Ir al Panel Admin
                    </a>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <!-- Info Footer -->
    <div class="container pb-5">
        <div class="welcome-card text-center">
            <h4><i class="bi bi-info-circle text-primary"></i> Información de Contacto</h4>
            <div class="row mt-4">
                <div class="col-md-4">
                    <i class="bi bi-geo-alt-fill text-primary" style="font-size: 2rem;"></i>
                    <p class="mt-2 mb-0"><strong>Ubicación</strong></p>
                    <p class="text-muted">Calle 4ta #6-51, Barrio Barahoja<br>Aguachica - Cesar</p>
                </div>
                <div class="col-md-4">
                    <i class="bi bi-telephone-fill text-success" style="font-size: 2rem;"></i>
                    <p class="mt-2 mb-0"><strong>Teléfonos</strong></p>
                    <p class="text-muted">317 726 8740<br>315 744 1535</p>
                </div>
                <div class="col-md-4">
                    <i class="bi bi-clock-fill text-warning" style="font-size: 2rem;"></i>
                    <p class="mt-2 mb-0"><strong>Horarios</strong></p>
                    <p class="text-muted">Lunes a Sábado<br>8:00 AM - 6:00 PM</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Mensaje de bienvenida
        console.log('¡Bienvenido a Desechables Punto Fijo! 🎉');
        console.log('Usuario:', '<?php echo $nombre_completo; ?>');
        console.log('Rol:', '<?php echo $rol; ?>');
    </script>
</body>
</html>