<?php
// Determinar la página actual para resaltar el elemento de menú correspondiente
$current_page = isset($_GET['page']) ? $_GET['page'] : 'dashboard';

// Función auxiliar para comprobar si un elemento del menú está activo
function isActive($page) {
    global $current_page;
    return ($current_page == $page) ? 'active' : '';
}

// Función para comprobar si un submenú debe estar abierto
function isSubmenuOpen($pages) {
    global $current_page;
    return in_array($current_page, $pages) ? 'show' : '';
}
?>

<aside class="sidenav bg-white navbar navbar-vertical navbar-expand-xs border-0 border-radius-xl my-3 fixed-start ms-4" id="sidenav-main">
    <div class="sidenav-header">
    <i class="fas fa-times p-3 cursor-pointer text-secondary opacity-5 position-absolute end-0 top-0 d-none d-xl-none" aria-hidden="true" id="iconSidenav"></i>
    <a class="navbar-brand m-0" href="main.php?page=dashboard">
        <img src="img/logo.png" width="26px" height="26px" class="navbar-brand-img h-100" alt="main_logo">
        <span class="ms-1 font-weight-bold">SGPOE</span>
    </a>
    </div>
    <hr class="horizontal dark mt-0">
    <div class="collapse navbar-collapse w-auto" id="sidenav-collapse-main">
      <ul class="navbar-nav">
        <!-- Elemento Proyectos con submenú -->
        <li class="nav-item">
          <a class="nav-link <?php echo isActive('info_general'); ?>" href="main.php?page=info_general">
            <div class="icon icon-shape icon-sm border-radius-md text-center me-2 d-flex align-items-center justify-content-center">
              <i class="ni ni-email-83 text-dark text-sm opacity-10"></i>
            </div>
            <span class="nav-link-text ms-1">Información General</span>
          </a>
        </li>
        <li class="nav-item">
          <!--<a class="nav-link <?php echo isActive('dashboard'); ?>" href="main.php?page=dashboard" onclick="toggleSubmenu(this, event)">-->
          <a class="nav-link <?php echo isActive('dashboard'); ?>" href="main.php?page=dashboard">
            <div class="icon icon-shape icon-sm border-radius-md text-center me-2 d-flex align-items-center justify-content-center">
              <i class="ni ni-tv-2 text-dark text-sm opacity-10"></i>
            </div>
            <span class="nav-link-text ms-1">DashBoard</span>
          </a>
          <!--
          <ul class="nav nav-sm flex-column submenu <?php echo isSubmenuOpen(['ano_suscripcion', 'tipo_proyecto', 'modalidad', 'ente_contratante', 'unidad_ejecutora', 'situacion_admin', 'financiacion', 'coordinador', 'asesores', 'ficha_tecnica']); ?>">
            <li class="nav-item">
              <a class="nav-link ps-4 <?php echo isActive('ano_suscripcion'); ?>" href="main.php?page=ano_suscripcion">Año Suscripción</a>
            </li>
            <li class="nav-item">
              <a class="nav-link ps-4 <?php echo isActive('tipo_proyecto'); ?>" href="main.php?page=tipo_proyecto">Tipo de Proyecto</a>
            </li>
            <li class="nav-item">
              <a class="nav-link ps-4 <?php echo isActive('modalidad'); ?>" href="main.php?page=modalidad">Modalidad</a>
            </li>
            <li class="nav-item">
              <a class="nav-link ps-4 <?php echo isActive('ente_contratante'); ?>" href="main.php?page=ente_contratante">Ente Contratante</a>
            </li>
            <li class="nav-item">
              <a class="nav-link ps-4 <?php echo isActive('unidad_ejecutora'); ?>" href="main.php?page=unidad_ejecutora">Unidad Ejecutora</a>
            </li>
            <li class="nav-item">
              <a class="nav-link ps-4 <?php echo isActive('situacion_admin'); ?>" href="main.php?page=situacion_admin">Situación Admin.</a>
            </li>
            <li class="nav-item">
              <a class="nav-link ps-4 <?php echo isActive('financiacion'); ?>" href="main.php?page=financiacion">Financiación</a>
            </li>
            <li class="nav-item">
              <a class="nav-link ps-4 <?php echo isActive('coordinador'); ?>" href="main.php?page=coordinador">Coordinador</a>
            </li>
            <li class="nav-item">
              <a class="nav-link ps-4 <?php echo isActive('asesores'); ?>" href="main.php?page=asesores">Asesores</a>
            </li>
            <li class="nav-item">
              <a class="nav-link ps-4 <?php echo isActive('ficha_tecnica'); ?>" href="main.php?page=ficha_tecnica">Ficha Técnica</a>
            </li>
          </ul>
        </li>
-->
        <!-- Otros elementos del menú -->
        <li class="nav-item">
          <a class="nav-link <?php echo isActive('proyecto'); ?>" href="main.php?page=proyecto">
            <div class="icon icon-shape icon-sm border-radius-md text-center me-2 d-flex align-items-center justify-content-center">
              <i class="ni ni-chart-bar-32 text-dark text-sm opacity-10"></i>
            </div>
            <span class="nav-link-text ms-1">Proyectos</span>
          </a>
        </li>
        <li class="nav-item">
          <a class="nav-link <?php echo isActive('consultas_especializadas'); ?>" href="main.php?page=consultas_especializadas">
            <div class="icon icon-shape icon-sm border-radius-md text-center me-2 d-flex align-items-center justify-content-center">
              <i class="ni ni-chart-bar-32 text-dark text-sm opacity-10"></i>
            </div>
            <span class="nav-link-text ms-1">Consultas Especializadas</span>
          </a>
        </li>
        <li class="nav-item">
          <a class="nav-link <?php echo isActive('consulta_natural'); ?>" href="main.php?page=consulta_natural">
            <div class="icon icon-shape icon-sm border-radius-md text-center me-2 d-flex align-items-center justify-content-center">
              <i class="ni ni-chat-round text-dark text-sm opacity-10"></i>
            </div>
            <span class="nav-link-text ms-1">Consulta Natural</span>
          </a>
        </li>
        <li class="nav-item">
          <a class="nav-link <?php echo isActive('entidades'); ?>" href="main.php?page=entidad">
            <div class="icon icon-shape icon-sm border-radius-md text-center me-2 d-flex align-items-center justify-content-center">
              <i class="ni ni-building text-dark text-sm opacity-10"></i>
            </div>
            <span class="nav-link-text ms-1">Entidades</span>
          </a>
        </li>
        
        <li class="nav-item">
          <a class="nav-link <?php echo isActive('comite_central'); ?>" href="main.php?page=comite_central">
            <div class="icon icon-shape icon-sm border-radius-md text-center me-2 d-flex align-items-center justify-content-center">
              <i class="ni ni-single-02 text-dark text-sm opacity-10"></i>
            </div>
            <span class="nav-link-text ms-1">Comité Central</span>
          </a>
        </li>
        <li class="nav-item">
          <a class="nav-link <?php echo isActive('contratos'); ?>" href="main.php?page=contratos">
            <div class="icon icon-shape icon-sm border-radius-md text-center me-2 d-flex align-items-center justify-content-center">
              <i class="ni ni-single-copy-04 text-dark text-sm opacity-10"></i>
            </div>
            <span class="nav-link-text ms-1">Contratos</span>
          </a>
        </li>
        <li class="nav-item">
          <a class="nav-link <?php echo isActive('convocatorias'); ?>" href="main.php?page=convocatorias">
            <div class="icon icon-shape icon-sm border-radius-md text-center me-2 d-flex align-items-center justify-content-center">
              <i class="ni ni-notification-70 text-dark text-sm opacity-10"></i>
            </div>
            <span class="nav-link-text ms-1">Convocatorias</span>
          </a>
        </li>
        <li class="nav-item">
          <a class="nav-link <?php echo isActive('resoluciones'); ?>" href="main.php?page=resoluciones">
            <div class="icon icon-shape icon-sm border-radius-md text-center me-2 d-flex align-items-center justify-content-center">
              <i class="ni ni-paper-diploma text-dark text-sm opacity-10"></i>
            </div>
            <span class="nav-link-text ms-1">Resoluciones</span>
          </a>
        </li>
        <li class="nav-item">
          <a class="nav-link <?php echo isActive('usuario'); ?>" href="main.php?page=usuario">
            <div class="icon icon-shape icon-sm border-radius-md text-center me-2 d-flex align-items-center justify-content-center">
              <i class="ni ni-circle-08 text-dark text-sm opacity-10"></i>
            </div>
            <span class="nav-link-text ms-1">Usuario</span>
          </a>
        </li>
        <li class="nav-item">
          <a class="nav-link" href="logout.php">
            <div class="icon icon-shape icon-sm border-radius-md text-center me-2 d-flex align-items-center justify-content-center">
              <i class="ni ni-button-power text-dark text-sm opacity-10"></i>
            </div>
            <span class="nav-link-text ms-1">Cerrar Sesión</span>
          </a>
        </li>
      </ul>
    </div>
  </aside>