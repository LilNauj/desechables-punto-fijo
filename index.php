<?php
/**
 * CATÁLOGO PÚBLICO DE PRODUCTOS
 * Desechables Punto Fijo
 */

require_once 'config/config.php';

// Si no está logueado, redirigir al login
//if (!estaLogueado()) {
//redirect('login.php');
//}

// Si es admin y viene del login, redirigir a admin.php
if (esAdmin() && !isset($_GET['ver_tienda']) && !isset($_SERVER['HTTP_REFERER'])) {
  if (empty($_GET)) {
    redirect('admin.php');
  }
}

$db = getDB();

// SLIDES DEL INICIO
$slides = $db->query("
  SELECT * FROM slides_inicio
  WHERE activo = 1
  ORDER BY orden ASC, id DESC
")->fetch_all(MYSQLI_ASSOC);

?>
<!DOCTYPE html>
<html lang="es">

<head>
  <meta charset="utf-8">
  <meta content="width=device-width, initial-scale=1.0" name="viewport">
  <title>Index - Desechables Punto Fijo</title>
  <meta name="description" content="Catálogo de productos - Desechables Punto Fijo Barahoja">
  <meta name="keywords" content="desechables, productos, punto fijo, barahoja">

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
  <link href="assets/vendor/swiper/swiper-bundle.min.css" rel="stylesheet">

  <!-- Main CSS File -->
  <link href="assets/css/main.css" rel="stylesheet">

  <style>
    {}

    .heroSwiper {
      width: 100%;
      height: auto !important;
      --swiper-navigation-color: #ffffff;
      --swiper-pagination-color: #000000ff;
      /* bolita activa */
      --swiper-pagination-bullet-inactive-color: #999;
      /* bolitas inactivas */
      --swiper-pagination-bullet-inactive-opacity: 0.5;
    }

    .heroSwiper .swiper-button-next,
    .heroSwiper .swiper-button-prev {
      background: none;
    }

    /* Flecha blanca con contorno negro */
    .heroSwiper .swiper-button-next::after,
    .heroSwiper .swiper-button-prev::after {
      color: #ffffff;
      /* relleno blanco */
      -webkit-text-stroke: 2px #000000ff;
      /* contorno negro (Chrome, Edge, etc.) */
      text-stroke: 3px #000;
      /* estándar futuro */
    }

    .heroSwiper .swiper-wrapper,
    .heroSwiper .swiper-slide {
      height: auto !important;
    }

    .hero-visuals {
      max-width: 600px;
      width: 100%;
    }


    .hero-slide {
      position: relative;
      width: 100%;
      padding-top: 65%;
      /* relación 16:10 aprox */
      border-radius: 20px;
      overflow: hidden;
      background-size: cover;
      background-position: center;
      box-shadow: 0 15px 40px rgba(15, 23, 42, 0.15);
    }

    .hero-slide-overlay {
      position: absolute;
      inset: 0;
      background: transparent;
      /*linear-gradient(135deg, rgba(15, 23, 42, 0.6), rgba(102, 126, 234, 0.5));*/
    }

    .hero-slide-content {
      position: absolute;
      inset: 0;
      padding: 20px;
      display: flex;
      flex-direction: column;
      justify-content: flex-end;
      color: #fff;
    }

    .hero-slide-content h3 {
      margin-bottom: 5px;
      font-size: 1.2rem;
      font-weight: 600;
    }

    .hero-slide-content p {
      margin-bottom: 10px;
      font-size: 0.9rem;
    }

    .hero-slide--fallback {
      background: linear-gradient(135deg, #507cffff, #764ba2);
      color: #fff;
      padding: 20px;
    }




    .category-pill:hover,
    .category-pill.active {
      background: var(--accent-color) !important;
      color: var(--contrast-color) !important;
      border-color: var(--accent-color) !important;
      transform: translateY(-2px);
      box-shadow: 0 5px 15px rgba(0, 0, 0, 0.15);
    }

    /* Fix para las imágenes de productos */
    .product-card .product-image {
      position: relative;
      width: 100%;
      padding-top: 100%;
      /* Ratio 1:1 */
      overflow: hidden;
      border-radius: 15px 15px 0 0;
      background: var(--surface-color);
    }

    .product-card .product-image>div {
      position: absolute;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      display: flex;
      align-items: center;
      justify-content: center;
      background: linear-gradient(135deg, #667eea22 0%, #764ba222 100%);
    }

    /* Animación suave para filtros */
    .product-card {
      transition: all 0.3s ease;
    }

    .product-card.hidden {
      display: none;
    }

    .fade-in {
      animation: fadeIn 0.3s ease;
    }

    @keyframes fadeIn {
      from {
        opacity: 0;
        transform: translateY(10px);
      }

      to {
        opacity: 1;
        transform: translateY(0);
      }
    }
  </style>
</head>

<body class="index-page">

  <?php include 'includes/headeri.php'; ?>

  <main class="main">

    <!-- Hero Section -->
    <section id="hero" class="hero section">
      <div class="container">

        <?php if (esAdmin()): ?>
          <!-- Banner especial para Admin -->
          <div class="alert alert-info alert-dismissible fade show d-flex align-items-center mb-4" role="alert"
            data-aos="fade-down">
            <i class="bi bi-shield-check" style="font-size: 2rem; margin-right: 15px;"></i>
            <div class="flex-grow-1">
              <strong>¡Hola Administrador!</strong> Estás viendo la tienda como cliente.
            </div>
            <a href="admin.php" class="btn btn-primary me-2">
              <i class="bi bi-gear-fill"></i> Panel Admin
            </a>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
          </div>
        <?php endif; ?>

        <div class="hero-container">
          <div class="hero-content" data-aos="fade-up" data-aos-delay="100">
            <div class="content-wrapper">
              <h1 class="hero-title">Desechables Punto Fijo</h1>
              <p class="hero-description">
                Tu tienda de confianza en Barahoja, Aguachica.
                Encuentra todo lo que necesitas en productos desechables de calidad.
              </p>
              <a href="productos.php" class="btn-primary">
                <i class="bi bi-grid-fill"></i> Ver Catálogo
              </a>
            </div>
          </div>

          <!-- Carrusel a la derecha -->
          <div class="hero-visuals" data-aos="fade-left" data-aos-delay="200">
            <div class="swiper heroSwiper">
              <div class="swiper-wrapper">
                <?php foreach ($slides as $slide): ?>
                  <div class="swiper-slide">
                    <div class="hero-slide"
                      style="background-image: url('<?php echo htmlspecialchars($slide['imagen']); ?>');">
                      <div class="hero-slide-overlay"></div>
                      <div class="hero-slide-content">
                        <h3><?php echo htmlspecialchars($slide['titulo']); ?></h3>
                        <?php if (!empty($slide['descripcion'])): ?>
                          <p><?php echo htmlspecialchars($slide['descripcion']); ?></p>
                        <?php endif; ?>

                        <?php if (!empty($slide['boton_url'])): ?>
                          <a href="<?php echo htmlspecialchars($slide['boton_url']); ?>" class="btn btn-light btn-sm">
                            <?php echo htmlspecialchars($slide['boton_texto'] ?: 'Ver más'); ?>
                          </a>
                        <?php endif; ?>
                      </div>
                    </div>
                  </div>
                <?php endforeach; ?>

                <?php if (empty($slides)): ?>
                  <!-- Fallback si no hay slides configurados -->
                  <div class="swiper-slide">
                    <div class="hero-slide hero-slide--fallback">
                      <h3>Bienvenido a Desechables Punto Fijo</h3>
                      <p>Configura tus banners desde el panel de administración.</p>
                    </div>
                  </div>
                <?php endif; ?>
              </div>

              <div class="swiper-pagination"></div>
              <div class="swiper-button-next"></div>
              <div class="swiper-button-prev"></div>
            </div>
          </div>
        </div>




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
  <script src="assets/vendor/swiper/swiper-bundle.min.js"></script>

  <!-- Main JS File -->
  <script src="assets/js/main.js"></script>

  <script>
    document.addEventListener('DOMContentLoaded', function () {
      // Solo si existe el carrusel
      if (document.querySelector('.heroSwiper')) {
        new Swiper('.heroSwiper', {
          loop: true,
          autoplay: {
            delay: 5000,
          },
          pagination: {
            el: '.heroSwiper .swiper-pagination',
            clickable: true,
          },
          navigation: {
            nextEl: '.heroSwiper .swiper-button-next',
            prevEl: '.heroSwiper .swiper-button-prev',
          },
        });
      }
    });
  </script>

</body>

</html>