<?php
// Antes de incluir el archivo de contenido

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
// Determinar qué página de contenido se debe cargar
$page = isset($_GET['page']) ? $_GET['page'] : 'dashboard';

// Lista de páginas permitidas para evitar inclusión de archivos maliciosos
$allowed_pages = [
    'dashboard', 'proyecto', 'proyecto_individual', 'entidad', 'editar_proyecto', 'crear_proyecto', 'consulta_natural', 'agregar_acta',
     'info_general', 'consultas_especializadas'
];

// Verificar que la página solicitada está en la lista de permitidas
if (!in_array($page, $allowed_pages)) {
    $page = 'dashboard'; // Página por defecto si la solicitada no es válida
}

// Ruta al archivo de contenido
$content_file = "content/{$page}.php";

// Si el archivo no existe, usar una página de error
if (!file_exists($content_file)) {
    $content_file = "content/404.php";
}
?>

<!DOCTYPE html>
<html lang="es">

<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
  <link rel="apple-touch-icon" sizes="76x76" href="assets/img/apple-icon.png">
  <link rel="icon" type="image/png" href="assets/img/favicon.png">
  <title>SGPOE - Sistema de Gestión de Proyectos</title>
  
  <!-- Fonts and icons -->
  <link href="https://fonts.googleapis.com/css?family=Open+Sans:300,400,600,700" rel="stylesheet" />
  
  <!-- Nucleo Icons -->

  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-EVSTQN3/azprG1Anm3QDgpJLIm9Nao0Yz1ztcQTwFspd3yD65VohhpuuCOmLASjC" crossorigin="anonymous">

  <!-- Font Awesome Icons -->
  <script src="https://kit.fontawesome.com/42d5adcbca.js" crossorigin="anonymous"></script>
  
  <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.9.1/chart.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

  <!-- CSS Files -->
  <link id="pagestyle" href="assets/css/argon-dashboard.css?v=2.1.0" rel="stylesheet" />
  
  <!-- Estilos para el submenú -->
  <style>
    .submenu {
      max-height: 0;
      overflow: hidden;
      transition: max-height 0.3s ease;
    }

    .submenu.show {
      max-height: 500px;
    }

    .nav-link .fa-caret-down {
      transition: transform 0.3s;
    }

    .nav-link.active .fa-caret-down {
      transform: rotate(180deg);
    }
  </style>
</head>

<body class="g-sidenav-show bg-gray-100">
  <!-- Fondo oscuro para la barra superior -->
  <div class="min-height-300 bg-dark position-absolute w-100"></div>
  
  <!-- Incluir el sidebar -->
  <?php include 'sidebar.php'; ?>
  
  <!-- Contenido principal -->
  <main class="main-content position-relative border-radius-lg">
    <!-- Barra de navegación superior -->
    <nav class="navbar navbar-main navbar-expand-lg px-0 mx-4 shadow-none border-radius-xl" id="navbarBlur" data-scroll="false">
      <div class="container-fluid py-1 px-3">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb bg-transparent mb-0 pb-0 pt-1 px-0 me-sm-6 me-5">
                <li class="breadcrumb-item text-sm"><a class="opacity-5 text-white" href="main.php?page=dashboard">Inicio</a></li>
                <li class="breadcrumb-item text-sm text-white active" aria-current="page"><?php echo ucfirst($page); ?></li>
            </ol>
            <h6 class="font-weight-bolder text-white mb-0"><?php echo ucfirst($page); ?></h6>
        </nav>
        <div class="collapse navbar-collapse mt-sm-0 mt-2 me-md-0 me-sm-4" id="navbar">
          <div class="ms-md-auto pe-md-3 d-flex align-items-center">
            <div class="input-group">
              <span class="input-group-text text-body"><i class="fas fa-search" aria-hidden="true"></i></span>
              <input type="text" class="form-control" placeholder="Buscar...">
            </div>
          </div>
          <ul class="navbar-nav justify-content-end">
            <li class="nav-item d-flex align-items-center">
              <a href="perfil.php" class="nav-link text-white font-weight-bold px-0">
                <i class="fa fa-user me-sm-1"></i>
                <span class="d-sm-inline d-none">Mi Perfil</span>
              </a>
            </li>
            <li class="nav-item d-xl-none ps-3 d-flex align-items-center">
              <a href="javascript:;" class="nav-link text-white p-0" id="iconNavbarSidenav">
                <div class="sidenav-toggler-inner">
                  <i class="sidenav-toggler-line bg-white"></i>
                  <i class="sidenav-toggler-line bg-white"></i>
                  <i class="sidenav-toggler-line bg-white"></i>
                </div>
              </a>
            </li>
            <li class="nav-item px-3 d-flex align-items-center">
              <a href="javascript:;" class="nav-link text-white p-0">
                <i class="fa fa-cog fixed-plugin-button-nav cursor-pointer"></i>
              </a>
            </li>
            <li class="nav-item dropdown pe-2 d-flex align-items-center">
              <a href="javascript:;" class="nav-link text-white p-0" id="dropdownMenuButton" data-bs-toggle="dropdown" aria-expanded="false">
                <i class="fa fa-bell cursor-pointer"></i>
              </a>
              <ul class="dropdown-menu dropdown-menu-end px-2 py-3 me-sm-n4" aria-labelledby="dropdownMenuButton">
                <!-- Aquí irían las notificaciones -->
                <li class="mb-2">
                  <a class="dropdown-item border-radius-md" href="javascript:;">
                    <div class="d-flex py-1">
                      <div class="d-flex flex-column justify-content-center">
                        <h6 class="text-sm font-weight-normal mb-1">
                          <span class="font-weight-bold">Nuevo proyecto</span> creado
                        </h6>
                        <p class="text-xs text-secondary mb-0">
                          <i class="fa fa-clock me-1"></i>
                          Hace 13 minutos
                        </p>
                      </div>
                    </div>
                  </a>
                </li>
              </ul>
            </li>
          </ul>
        </div>
      </div>
    </nav>
    
    <!-- Contenedor principal del contenido -->
    <div class="container-fluid py-4">
        <?php 
        // Preservar todos los parámetros GET para la página incluida
        $_GET_backup = $_GET;
        include $content_file; 
        $_GET = $_GET_backup; // Restaurar por si acaso
        ?>

      
      <!-- Pie de página -->
      <footer class="footer pt-3">
        <div class="container-fluid">
          <div class="row align-items-center justify-content-lg-between">
            <div class="col-lg-6 mb-lg-0 mb-4">
              <div class="copyright text-center text-sm text-muted text-lg-start">
                © <?php echo date('Y'); ?> SGPOE - Sistema de Gestión de Proyectos
              </div>
            </div>
            <div class="col-lg-6">
              <ul class="nav nav-footer justify-content-center justify-content-lg-end">
                <li class="nav-item">
                  <a href="soporte.php" class="nav-link text-muted">Soporte</a>
                </li>
                <li class="nav-item">
                  <a href="acerca.php" class="nav-link text-muted">Acerca de</a>
                </li>
              </ul>
            </div>
          </div>
        </div>
      </footer>
    </div>
  </main>

  <!-- Scripts -->
  <script src="assets/js/core/popper.min.js"></script>
  <script src="assets/js/core/bootstrap.min.js"></script>
  <script src="assets/js/plugins/perfect-scrollbar.min.js"></script>
  <script src="assets/js/plugins/smooth-scrollbar.min.js"></script>
  
  <!-- Script para el submenú -->
  <script>
    function toggleSubmenu(element, event) {
      event.preventDefault();
      const navItem = element.parentElement;
      const submenu = navItem.querySelector('.submenu');
      submenu.classList.toggle('show');
      element.classList.toggle('active');
    }

    // Auto-expandir el submenú si la página actual es parte del submenú
    document.addEventListener('DOMContentLoaded', function() {
      // Implementación básica para abrir el submenú cuando se está en una página del submenú
      const currentPath = window.location.pathname;
      const submenuLinks = document.querySelectorAll('.submenu .nav-link');
      
      submenuLinks.forEach(link => {
        if (currentPath.includes(link.getAttribute('href'))) {
          const submenu = link.closest('.submenu');
          submenu.classList.add('show');
          const parentLink = submenu.previousElementSibling;
          parentLink.classList.add('active');
        }
      });
    });
  </script>
  
  <!-- Control de scrollbar -->
  <script>
    var win = navigator.platform.indexOf('Win') > -1;
    if (win && document.querySelector('#sidenav-scrollbar')) {
      var options = {
        damping: '0.5'
      }
      Scrollbar.init(document.querySelector('#sidenav-scrollbar'), options);
    }
  </script>
  
  <!-- Dashboard JS -->
  <script src="assets/js/argon-dashboard.min.js?v=2.1.0"></script>
</body>

</html>