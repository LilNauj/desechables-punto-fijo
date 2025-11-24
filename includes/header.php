<?php
// includes/header.php
?>
<header id="header" class="header sticky-top">
  <!-- Top Bar -->
  <div class="top-bar py-2">
    <div class="container-fluid container-xl">
      <div class="row align-items-center">
        <div class="col-lg-4 d-none d-lg-flex">
          <div class="top-bar-item">
            <i class="bi bi-telephone-fill me-2"></i>
            <span>¿Necesitas ayuda? Llámanos: </span>
            <a href="tel:+573177268740">317 726 8740</a>
          </div>
        </div>

        <div class="col-lg-4 col-md-12 text-center">
          <div class="announcement-slider">
            <div class="announcement-text">
              🎉 ¡Bienvenido a Desechables Punto Fijo!
            </div>
          </div>
        </div>

        <div class="col-lg-4 d-none d-lg-block">
          <div class="d-flex justify-content-end">
            <?php if (estaLogueado()): ?>
              <div class="top-bar-item me-3">
                <i class="bi bi-person-circle me-2"></i>
                <span>Hola, <?php echo htmlspecialchars($_SESSION['nombre']); ?></span>
              </div>
            <?php endif; ?>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Main Header -->
  <div class="main-header">
    <div class="container-fluid container-xl">
      <div class="d-flex py-3 align-items-center justify-content-between">

        <!-- Logo -->
        <a href="index.php" class="logo d-flex align-items-center">
          <h1 class="sitename">Desechables Punto Fijo</h1>
        </a>



        <!-- Actions -->
        <div class="header-actions d-flex align-items-center justify-content-end">

          <!-- Mobile Search Toggle -->
          <button class="header-action-btn mobile-search-toggle d-xl-none" type="button" data-bs-toggle="collapse" data-bs-target="#mobileSearch">
            <i class="bi bi-search"></i>
          </button>

          <!-- Account -->
          <div class="dropdown account-dropdown">
            <button class="header-action-btn" data-bs-toggle="dropdown">
              <i class="bi bi-person"></i>
            </button>
            <div class="dropdown-menu">
              <?php if (estaLogueado()): ?>
                <div class="dropdown-header">
                  <h6>Hola, <span class="text-primary"><?php echo htmlspecialchars($_SESSION['nombre']); ?></span></h6>
                  <p class="mb-0"><?php echo htmlspecialchars($_SESSION['email']); ?></p>
                </div>
                <div class="dropdown-body">
                  <?php if (esAdmin()): ?>
                    <a class="dropdown-item d-flex align-items-center" href="admin.php">
                      <i class="bi bi-speedometer2 me-2"></i>
                      <span>Panel Admin</span>
                    </a>
                  <?php endif; ?>
                  <a class="dropdown-item d-flex align-items-center" href="mis_pedidos.php">
                    <i class="bi bi-bag-check me-2"></i>
                    <span>Mis Pedidos</span>
                  </a>
                  <a class="dropdown-item d-flex align-items-center" href="perfil.php">
                    <i class="bi bi-person-circle me-2"></i>
                    <span>Mi Perfil</span>
                  </a>
                </div>
                <div class="dropdown-footer">
                  <a href="logout.php" class="btn btn-outline-danger w-100">
                    <i class="bi bi-box-arrow-right me-2"></i>Cerrar Sesión
                  </a>
                </div>
              <?php else: ?>
                <div class="dropdown-header">
                  <h6>¡Bienvenido!</h6>
                  <p class="mb-0">Accede a tu cuenta</p>
                </div>
                <div class="dropdown-footer">
                  <a href="login.php" class="btn btn-primary w-100 mb-2">Iniciar Sesión</a>
                  <a href="registro.php" class="btn btn-outline-primary w-100">Registrarse</a>
                </div>
              <?php endif; ?>
            </div>
          </div>

          <!-- Cart -->
          <a href="carrito.php" class="header-action-btn">
            <i class="bi bi-cart3"></i>
            <?php
            $cantidadCarrito = 0;
            if (isset($_SESSION['carrito'])) {
              foreach ($_SESSION['carrito'] as $item) {
                $cantidadCarrito += $item['cantidad'];
              }
            }
            ?>
            <?php if ($cantidadCarrito > 0): ?>
              <span class="badge"><?php echo $cantidadCarrito; ?></span>
            <?php endif; ?>
          </a>

          

        </div>
      </div>
    </div>
  </div>

  <!-- Mobile Search Form -->
  <div class="collapse" id="mobileSearch">
    <div class="container">
      <form class="search-form" action="buscar.php" method="GET">
        <div class="input-group">
          <input type="text" class="form-control" name="q" placeholder="Buscar productos...">
          <button class="btn" type="submit">
            <i class="bi bi-search"></i>
          </button>
        </div>
      </form>
    </div>
  </div>

</header>