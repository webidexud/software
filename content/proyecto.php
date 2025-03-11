<?php
/**
 * Página de gestión de proyectos
 * 
 * Esta página muestra la lista de proyectos activos con filtros y opciones de gestión
 */

// Verificar si el archivo modelo existe
if (file_exists('models/proyecto_model.php')) {
    // Incluir el modelo de proyecto
    include_once 'models/proyecto_model.php';
    
    // Obtener todos los proyectos
    $proyectos = function_exists('obtenerTodosProyectos') ? obtenerTodosProyectos() : [];
    
    // Obtener listas para filtros
    $anios = function_exists('obtenerListaAnios') ? obtenerListaAnios() : [];
    $entidades = function_exists('obtenerListaEntidades') ? obtenerListaEntidades() : [];
    $situaciones = function_exists('obtenerListaSituaciones') ? obtenerListaSituaciones() : [];
} else {
    // Si no encuentra el modelo, usar datos de ejemplo
    $proyectos = [
        [
            'anio' => 2024,
            'numero' => 'PRY-2024-001',
            'nombre' => 'Desarrollo Sistema de Gestión Documental',
            'entidad' => 'Ministerio de Educación Nacional',
            'situacion' => 'En ejecución',
            'id' => 1
        ],
        [
            'anio' => 2023,
            'numero' => 'PRY-2023-052',
            'nombre' => 'Implementación Plataforma de Capacitación Virtual',
            'entidad' => 'Secretaría de Educación Distrital',
            'situacion' => 'Finalizado',
            'id' => 2
        ],
        [
            'anio' => 2023,
            'numero' => 'PRY-2023-018',
            'nombre' => 'Estudio de Factibilidad Infraestructura Vial',
            'entidad' => 'Instituto de Desarrollo Urbano',
            'situacion' => 'Suscrito',
            'id' => 3
        ]
    ];
    
    // Crear listas de ejemplo para filtros
    $anios = [2022, 2023, 2024, 2025];
    $entidades = ['Ministerio de Educación Nacional', 'Secretaría de Educación Distrital', 'Instituto de Desarrollo Urbano', 'Secretaría Distrital de Ambiente', 'Alcaldía Mayor de Bogotá'];
    $situaciones = ['En ejecución', 'Finalizado', 'Suscrito'];
}

// Contar proyectos por año (para mostrar en las etiquetas de filtro)
$proyectosPorAnio = [];
foreach ($proyectos as $proyecto) {
    if (!isset($proyectosPorAnio[$proyecto['anio']])) {
        $proyectosPorAnio[$proyecto['anio']] = 0;
    }
    $proyectosPorAnio[$proyecto['anio']]++;
}

// Contar proyectos por entidad
$proyectosPorEntidad = [];
foreach ($proyectos as $proyecto) {
    if (!isset($proyectosPorEntidad[$proyecto['entidad']])) {
        $proyectosPorEntidad[$proyecto['entidad']] = 0;
    }
    $proyectosPorEntidad[$proyecto['entidad']]++;
}

// Contar proyectos por situación
$proyectosPorSituacion = [];
foreach ($proyectos as $proyecto) {
    if (!isset($proyectosPorSituacion[$proyecto['situacion']])) {
        $proyectosPorSituacion[$proyecto['situacion']] = 0;
    }
    $proyectosPorSituacion[$proyecto['situacion']]++;
}

// Preparar datos para las búsquedas dinámicas
$entidadesPorAnio = [];
foreach ($proyectos as $proyecto) {
    if (!isset($entidadesPorAnio[$proyecto['anio']])) {
        $entidadesPorAnio[$proyecto['anio']] = [];
    }
    if (!in_array($proyecto['entidad'], $entidadesPorAnio[$proyecto['anio']])) {
        $entidadesPorAnio[$proyecto['anio']][] = $proyecto['entidad'];
    }
}

$situacionesPorAnio = [];
foreach ($proyectos as $proyecto) {
    if (!isset($situacionesPorAnio[$proyecto['anio']])) {
        $situacionesPorAnio[$proyecto['anio']] = [];
    }
    if (!in_array($proyecto['situacion'], $situacionesPorAnio[$proyecto['anio']])) {
        $situacionesPorAnio[$proyecto['anio']][] = $proyecto['situacion'];
    }
}

// Codificar datos para usar en JavaScript
$entidadesPorAnioJSON = json_encode($entidadesPorAnio);
$situacionesPorAnioJSON = json_encode($situacionesPorAnio);
?>

<!-- Estilos específicos para esta página -->
<style>
:root {
    /* Nueva paleta de colores tecnológica verde-azul */
    --primary: #0ea5e9;    /* Azul brillante */
    --primary-dark: #0284c7;
    --primary-light: #38bdf8;
    --secondary: #10b981;  /* Verde esmeralda */
    --secondary-dark: #059669;
    --secondary-light: #34d399;
    --accent: #06b6d4;     /* Cian */
    --success: #22c55e;    /* Verde */
    --info: #3b82f6;      /* Azul */
    --warning: #f59e0b;   /* Ámbar */
    --danger: #ef4444;    /* Rojo */
    --light: #f1f5f9;     /* Slate 100 */
    --dark: #0f172a;      /* Slate 900 */
    --neutral: #f8fafc;   /* Slate 50 */
    --white: #ffffff;
    --gray: #94a3b8;      /* Slate 400 */
    --grad-blue: linear-gradient(135deg, #0ea5e9, #06b6d4);
    --grad-green: linear-gradient(135deg, #10b981, #059669);
    
    --shadow-sm: 0 1px 3px rgba(0, 0, 0, 0.08), 0 1px 2px rgba(0, 0, 0, 0.05);
    --shadow-md: 0 4px 6px rgba(0, 0, 0, 0.07), 0 2px 4px rgba(0, 0, 0, 0.05);
    --shadow-lg: 0 10px 15px rgba(0, 0, 0, 0.05), 0 4px 6px rgba(0, 0, 0, 0.03);
    --shadow-xl: 0 20px 25px rgba(0, 0, 0, 0.04), 0 8px 10px rgba(0, 0, 0, 0.02);
    
    --transition: all 0.3s ease;
    
    --card-border-radius: 12px;
    --button-border-radius: 8px;
    --item-border-radius: 6px;
    
    --font-main: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
    --font-heading: var(--font-main);
    
    --z-index-dropdown: 1000;
    --z-index-modal: 1050;
    --z-index-toast: 1060;
    --z-index-loading: 1070;
}

/* Estilos generales */
body {
    font-family: var(--font-main);
}

.card {
    box-shadow: var(--shadow-sm);
    border-radius: var(--card-border-radius);
    border: none;
    transition: var(--transition);
    overflow: hidden;
}

.card:hover {
    box-shadow: var(--shadow-md);
}

.card-header {
    background-color: var(--white);
    border-bottom: 1px solid var(--light);
    padding: 18px 20px;
}

.card-title {
    font-size: 1.1rem;
    font-weight: 600;
    color: var(--dark);
    margin-bottom: 0;
}

.page-header {
    background: var(--grad-blue);
    padding: 30px;
    border-radius: 16px;
    box-shadow: var(--shadow-md);
    margin-bottom: 30px;
    color: white;
    position: relative;
    overflow: hidden;
    backdrop-filter: blur(10px);
    border: 1px solid rgba(255, 255, 255, 0.1);
}

.page-header::before {
    content: "";
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: 
        radial-gradient(circle at 20% 150%, rgba(6, 182, 212, 0.8) 0%, rgba(14, 165, 233, 0) 30%),
        radial-gradient(circle at 80% -20%, rgba(16, 185, 129, 0.8) 0%, rgba(14, 165, 233, 0) 30%);
    opacity: 0.8;
    z-index: -1;
}

.page-header::after {
    content: "";
    position: absolute;
    top: 0;
    right: 0;
    width: 300px;
    height: 100%;
    background: url('data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 200 200"><path fill="rgba(255, 255, 255, 0.07)" d="M39.3,-63.8C50.9,-55.1,59.9,-42.8,67.4,-29.1C74.9,-15.5,80.9,-0.5,80,14.1C79.1,28.7,71.4,42.8,60.2,52.4C49.1,62,34.4,67.2,20.3,69.9C6.2,72.6,-7.3,72.9,-19.6,68.9C-31.9,64.9,-42.9,56.7,-51.5,46.2C-60.2,35.8,-66.4,23.2,-71.2,8.6C-75.9,-6,-79.3,-22.5,-73.2,-34.7C-67.1,-46.9,-51.5,-54.8,-37.1,-62.4C-22.6,-70,-11.3,-77.3,1.5,-79.6C14.3,-81.9,28.6,-79.1,39.3,-70.3Z" transform="translate(100 100)" /></svg>');
    background-size: contain;
    background-repeat: no-repeat;
    background-position: center right;
    opacity: 0.2;
}

.header-content {
    display: flex;
    align-items: flex-start;
    gap: 20px;
}

.header-icon {
    background: rgba(255, 255, 255, 0.15);
    width: 56px;
    height: 56px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
    border: 1px solid rgba(255, 255, 255, 0.2);
    margin-top: 5px;
}

.page-title {
    font-size: 1.75rem;
    font-weight: 700;
    margin-bottom: 6px;
}

.page-subtitle {
    opacity: 0.9;
    font-weight: 400;
    font-size: 1rem;
    margin-bottom: 15px;
}

.header-stats {
    display: flex;
    gap: 20px;
    margin-top: 10px;
}

.header-stat {
    display: flex;
    flex-direction: column;
    background: rgba(255, 255, 255, 0.1);
    padding: 8px 15px;
    border-radius: 8px;
    min-width: 80px;
    backdrop-filter: blur(5px);
    border: 1px solid rgba(255, 255, 255, 0.2);
}

.stat-value {
    font-size: 1.2rem;
    font-weight: 700;
}

.stat-label {
    font-size: 0.7rem;
    opacity: 0.8;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.header-actions {
    display: flex;
    gap: 10px;
    margin-top: 15px;
    justify-content: flex-end;
}

.btn-header-action {
    width: 38px;
    height: 38px;
    border-radius: 50%;
    background: rgba(255, 255, 255, 0.15);
    border: 1px solid rgba(255, 255, 255, 0.3);
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: all 0.2s;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
}

.btn-header-action:hover {
    background: rgba(255, 255, 255, 0.25);
    transform: translateY(-2px);
}

/* Estilos para los filtros */
.filter-card {
    background-color: var(--white);
    border-radius: 12px;
}

.filter-header {
    padding: 18px 20px;
    border-bottom: 1px solid var(--light);
    display: flex;
    justify-content: space-between;
    align-items: center;
    cursor: pointer;
}

.filter-header h6 {
    margin: 0;
    font-weight: 600;
    color: var(--dark);
    display: flex;
    align-items: center;
}

.filter-header h6 i {
    transition: var(--transition);
    margin-right: 10px;
    color: var(--primary);
}

.filter-header .toggle-icon {
    transition: var(--transition);
}

.filter-body {
    padding: 0;
    max-height: 0;
    overflow: hidden;
    transition: max-height 0.3s ease, padding 0.3s ease;
}

.filter-body.show {
    padding: 20px;
    max-height: 800px;
}

.filter-header.collapsed .toggle-icon {
    transform: rotate(-90deg);
}

.filter-tags {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
    margin-top: 15px;
}

.filter-tag {
    background-color: var(--light);
    color: var(--dark);
    padding: 6px 12px;
    border-radius: 30px;
    font-size: 0.75rem;
    font-weight: 600;
    transition: var(--transition);
    cursor: pointer;
    display: flex;
    align-items: center;
}

.filter-tag:hover {
    background-color: var(--primary);
    color: white;
}

.filter-tag.active {
    background-color: var(--primary);
    color: white;
}

.filter-tag .badge {
    margin-left: 5px;
    background-color: rgba(255, 255, 255, 0.2);
    color: inherit;
    font-size: 0.7rem;
    padding: 3px 6px;
    border-radius: 10px;
}

.form-label {
    font-size: 0.75rem;
    font-weight: 600;
    color: var(--dark);
    margin-bottom: 6px;
    opacity: 0.8;
}

.form-control, .form-select {
    border-radius: 8px;
    font-size: 0.85rem;
    border-color: var(--light);
    padding: 8px 12px;
    box-shadow: none !important;
    transition: var(--transition);
}

.form-control:focus, .form-select:focus {
    border-color: var(--primary);
}

.form-control::placeholder {
    color: var(--gray);
    opacity: 0.6;
}

.btn-filter {
    font-weight: 600;
    padding: 7px 20px;
    border-radius: 8px;
    transition: var(--transition);
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 7px;
}

.btn-filter:hover {
    transform: translateY(-2px);
    box-shadow: var(--shadow-sm);
}

.btn-primary {
    background: var(--grad-blue);
    border: none;
}

.btn-primary:hover {
    background: linear-gradient(135deg, #0284c7, #0891b2);
    box-shadow: 0 4px 12px rgba(14, 165, 233, 0.2);
}

.btn-outline-secondary {
    border-color: var(--light);
    color: var(--dark);
}

.btn-outline-secondary:hover {
    background-color: var(--light);
    color: var(--dark);
    border-color: var(--light);
}

/* Estilos para la tabla */
.table-container {
    background-color: var(--white);
    border-radius: 12px;
    overflow: hidden;
    box-shadow: var(--shadow-sm);
}

.table {
    margin-bottom: 0;
}

.table th {
    font-size: 0.7rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    font-weight: 600;
    color: var(--gray);
    background-color: var(--neutral);
    border-top: none;
    padding: 15px 20px;
    white-space: nowrap;
}

.table td {
    padding: 13px 20px;
    vertical-align: middle;
    color: var(--dark);
    border-color: var(--light);
    font-size: 0.85rem;
}

.table tr {
    transition: var(--transition);
}

.table tr:hover {
    background-color: rgba(14, 165, 233, 0.03);
}

.table-striped tbody tr:nth-of-type(odd) {
    background-color: rgba(248, 249, 250, 0.5);
}

.table-striped tbody tr:hover {
    background-color: rgba(14, 165, 233, 0.03);
}

/* Estilos para las etiquetas de situación */
.status-badge {
    padding: 6px 10px;
    border-radius: 6px;
    font-size: 0.7rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.3px;
    display: inline-block;
    min-width: 100px;
    text-align: center;
}

.status-Suscrito {
    background-color: rgba(245, 158, 11, 0.1);
    color: var(--warning);
}

.status-Finalizado, .status-finalizado {
    background-color: rgba(34, 197, 94, 0.1);
    color: var(--success);
}

.status-ejecución, .status-ejecucion, .status-ejecución, .status-En.ejecución {
    background-color: rgba(59, 130, 246, 0.1);
    color: var(--info);
}

/* Estilos para los botones de acción */
.action-buttons {
    display: flex;
    gap: 8px;
    justify-content: center;
}

.action-button {
    padding: 6px 12px;
    font-size: 0.75rem;
    border-radius: 6px;
    transition: var(--transition);
    font-weight: 600;
    display: inline-flex;
    align-items: center;
    gap: 5px;
}

.action-button:hover {
    transform: translateY(-2px);
    box-shadow: var(--shadow-sm);
}

.btn-view {
    background-color: rgba(14, 165, 233, 0.1);
    color: var(--primary);
    border: 1px solid rgba(14, 165, 233, 0.2);
}

.btn-view:hover {
    background-color: var(--primary);
    color: white;
    border-color: var(--primary);
}

.btn-edit {
    background-color: rgba(16, 185, 129, 0.1);
    color: var(--secondary);
    border: 1px solid rgba(16, 185, 129, 0.2);
}

.btn-edit:hover {
    background-color: var(--secondary);
    color: white;
    border-color: var(--secondary);
}

/* Estilos para la sección de paginación y conteo */
.pagination-info {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 15px 20px;
    border-top: 1px solid var(--light);
    background-color: var(--white);
    border-radius: 0 0 var(--card-border-radius) var(--card-border-radius);
}

.count-display {
    font-size: 0.8rem;
    color: var(--gray);
}

.pagination {
    margin: 0;
}

.page-link {
    color: var(--primary);
    border-color: var(--light);
    padding: 6px 12px;
    font-size: 0.8rem;
    transition: var(--transition);
}

.page-link:hover {
    background-color: var(--primary);
    color: var(--white);
    border-color: var(--primary);
}

.page-item.active .page-link {
    background-color: var(--primary);
    border-color: var(--primary);
}

/* Animaciones */
@keyframes fadeIn {
    from { opacity: 0; transform: translateY(10px); }
    to { opacity: 1; transform: translateY(0); }
}

.animate-fade-in {
    animation: fadeIn 0.3s ease forwards;
}

.btn-add-project {
    background: var(--grad-green);
    border: none;
    box-shadow: var(--shadow-sm);
    transition: var(--transition);
    font-weight: 600;
    letter-spacing: 0.5px;
    padding: 10px 24px;
    display: flex;
    align-items: center;
    gap: 8px;
}

.btn-add-project:hover {
    transform: translateY(-3px);
    box-shadow: var(--shadow-md);
    background: linear-gradient(45deg, #059669, #10b981);
}

/* Indicador de actividad de filtro */
.filter-active-indicator {
    width: 8px;
    height: 8px;
    border-radius: 50%;
    background-color: var(--success);
    display: none;
    margin-left: 8px;
}

.filter-active-indicator.show {
    display: inline-block;
}

/* Estilos responsivos */
@media (max-width: 768px) {
    .page-header {
        padding: 20px;
    }
    
    .pagination-info {
        flex-direction: column;
        gap: 15px;
    }
    
    .filter-tag {
        font-size: 0.7rem;
        padding: 5px 10px;
    }
    
    .card-header {
        padding: 15px;
    }
    
    .table th, .table td {
        padding: 10px 15px;
    }
}

/* Mejoras para dispositivos pequeños */
@media (max-width: 576px) {
    .action-button {
        padding: 5px 8px;
        font-size: 0.7rem;
    }
    
    .table td, .table th {
        padding: 8px 10px;
        font-size: 0.75rem;
    }
    
    .status-badge {
        min-width: auto;
        font-size: 0.65rem;
        padding: 4px 8px;
    }
}

/* Estilo para notificación de carga */
.loading-overlay {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(255, 255, 255, 0.8);
    backdrop-filter: blur(3px);
    display: flex;
    flex-direction: column;
    justify-content: center;
    align-items: center;
    z-index: var(--z-index-loading);
    opacity: 0;
    visibility: hidden;
    transition: all 0.3s ease;
}

.loading-overlay.show {
    opacity: 1;
    visibility: visible;
}

.loading-spinner {
    width: 50px;
    height: 50px;
    position: relative;
    margin-bottom: 15px;
}

.loading-spinner:before, .loading-spinner:after {
    content: "";
    position: absolute;
    border-radius: 50%;
    animation-duration: 1.8s;
    animation-iteration-count: infinite;
    animation-timing-function: ease-in-out;
    filter: drop-shadow(0 0 2px rgba(0, 0, 0, 0.1));
}

.loading-spinner:before {
    width: 100%;
    height: 100%;
    background-color: rgba(14, 165, 233, 0.15);
    animation-name: pulse;
}

.loading-spinner:after {
    width: 80%;
    height: 80%;
    background-color: white;
    top: 10%;
    left: 10%;
    animation-name: pulse-inner;
}

.loading-text {
    color: var(--primary);
    font-size: 1rem;
    font-weight: 600;
    text-shadow: 0 1px 0 rgba(255, 255, 255, 0.7);
}

@keyframes pulse {
    0% { transform: scale(0.95); opacity: 0.7; }
    50% { transform: scale(1); opacity: 1; }
    100% { transform: scale(0.95); opacity: 0.7; }
}

@keyframes pulse-inner {
    0% { transform: scale(0.9); opacity: 0.5; }
    50% { transform: scale(1); opacity: 1; }
    100% { transform: scale(0.9); opacity: 0.5; }
}

/* Botón flotante para móvil */
.mobile-add-btn {
    position: fixed;
    bottom: 30px;
    right: 30px;
    width: 60px;
    height: 60px;
    border-radius: 50%;
    background: var(--grad-green);
    color: white;
    display: none;
    justify-content: center;
    align-items: center;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.2);
    z-index: var(--z-index-dropdown);
    transition: var(--transition);
}

.mobile-add-btn:hover {
    transform: scale(1.05);
}

.mobile-add-btn i {
    font-size: 24px;
}

@media (max-width: 768px) {
    .mobile-add-btn {
        display: flex;
    }
    
    .desktop-add-btn {
        display: none;
    }
}

/* Tabla moderna con hover */
.table {
    border-collapse: separate;
    border-spacing: 0;
}

.table tr {
    box-shadow: 0 1px 0 rgba(0, 0, 0, 0.02);
}

.table tbody tr {
    transition: all 0.2s;
    border-radius: 8px;
}

.table tbody tr:hover {
    background-color: rgba(14, 165, 233, 0.05);
    transform: translateY(-1px);
    box-shadow: 0 3px 10px rgba(0, 0, 0, 0.04);
}

/* Estilos decorativos */
.decorative-dots {
    position: absolute;
    width: 200px;
    height: 100px;
    background-image: radial-gradient(circle, rgba(255, 255, 255, 0.15) 2px, transparent 2px);
    background-size: 15px 15px;
    background-position: 0 0;
    opacity: 0.5;
    z-index: 0;
}

.decorative-dots-1 {
    top: 20px;
    right: 40px;
    transform: rotate(15deg);
}

.decorative-dots-2 {
    bottom: 20px;
    left: 40px;
    transform: rotate(-15deg);
}
</style>

<!-- Sección de Encabezado y Botón de Agregar -->
<div class="page-header animate-fade-in">
    <div class="decorative-dots decorative-dots-1"></div>
    <div class="decorative-dots decorative-dots-2"></div>
    <div class="row align-items-center">
        <div class="col-lg-8">
            <div class="header-content">
                <div class="header-icon">
                    <i class="fas fa-project-diagram"></i>
                </div>
                <div>
                    <h4 class="page-title">Gestión de Proyectos</h4>
                    <p class="page-subtitle">
                        Visualice, filtre y administre todos los proyectos en el sistema
                    </p>
                    <div class="header-stats">
                        <div class="header-stat">
                            <span class="stat-value"><?php echo count($proyectos); ?></span>
                            <span class="stat-label">Proyectos</span>
                        </div>
                        <div class="header-stat">
                            <span class="stat-value"><?php echo count($proyectosPorAnio); ?></span>
                            <span class="stat-label">Años</span>
                        </div>
                        <div class="header-stat">
                            <span class="stat-value"><?php echo count($proyectosPorEntidad); ?></span>
                            <span class="stat-label">Entidades</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-4 text-lg-end mt-3 mt-lg-0">
            <a href="main.php?page=crear_proyecto" class="btn btn-add-project desktop-add-btn">
                <i class="fas fa-plus"></i> Nuevo Proyecto
            </a>
            <div class="header-actions">
                <button class="btn-header-action" id="btnToggleFullscreen" title="Pantalla completa">
                    <i class="fas fa-expand-alt"></i>
                </button>
                <button class="btn-header-action" id="btnRefreshData" title="Actualizar datos">
                    <i class="fas fa-sync-alt"></i>
                </button>
                <button class="btn-header-action" id="btnShowHelp" title="Ayuda">
                    <i class="fas fa-question-circle"></i>
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Botón flotante para móvil -->
<a href="main.php?page=crear_proyecto" class="mobile-add-btn">
    <i class="fas fa-plus"></i>
</a>

<!-- Sección de Filtros -->
<div class="row mb-4">
    <div class="col-12">
        <div class="card filter-card animate-fade-in" style="animation-delay: 0.1s;">
            <div class="filter-header" id="filterHeader">
                <h6>
                    <i class="fas fa-filter"></i> Filtros de Búsqueda
                    <span class="filter-active-indicator" id="filterActiveIndicator"></span>
                </h6>
                <i class="fas fa-chevron-down toggle-icon"></i>
            </div>
            <div class="filter-body" id="filterBody">
                <form id="filtroProyectos">
                    <div class="row g-3">
                        <!-- Filtro por Año -->
                        <div class="col-md-3 col-sm-6">
                            <label for="filtroAnio" class="form-label">Año del Proyecto</label>
                            <select class="form-select" id="filtroAnio">
                                <option value="">Todos los años</option>
                                <?php foreach($anios as $anio): ?>
                                <option value="<?php echo $anio; ?>"><?php echo $anio; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <!-- Filtro por Número -->
                        <div class="col-md-3 col-sm-6">
                            <label for="filtroNumero" class="form-label">Número de Referencia</label>
                            <input type="text" class="form-control" id="filtroNumero" placeholder="Ej: PRY-2024-001">
                        </div>
                        
                        <!-- Filtro por Nombre -->
                        <div class="col-md-6 col-sm-12">
                            <label for="filtroNombre" class="form-label">Nombre o Descripción</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-search"></i></span>
                                <input type="text" class="form-control" id="filtroNombre" placeholder="Buscar por nombre del proyecto...">
                            </div>
                        </div>
                        
                        <!-- Filtro por Entidad -->
                        <div class="col-md-6">
                            <label for="filtroEntidad" class="form-label">Entidad Contratante</label>
                            <select class="form-select" id="filtroEntidad">
                                <option value="">Todas las entidades</option>
                                <?php foreach($entidades as $entidad): ?>
                                <option value="<?php echo $entidad; ?>"><?php echo $entidad; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <!-- Filtro por Situación -->
                        <div class="col-md-6">
                            <label for="filtroSituacion" class="form-label">Situación del Proyecto</label>
                            <select class="form-select" id="filtroSituacion">
                                <option value="">Todas las situaciones</option>
                                <?php foreach($situaciones as $situacion): ?>
                                <option value="<?php echo $situacion; ?>"><?php echo $situacion; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    
                    <!-- Filtros Rápidos - Etiquetas -->
                    <div class="mt-4">
                        <h6 class="form-label">Filtros Rápidos por Año</h6>
                        <div class="filter-tags">
                            <div class="filter-tag" data-filter="anio" data-value="">
                                Todos <span class="badge"><?php echo count($proyectos); ?></span>
                            </div>
                            <?php foreach($proyectosPorAnio as $anio => $cantidad): ?>
                            <div class="filter-tag" data-filter="anio" data-value="<?php echo $anio; ?>">
                                <?php echo $anio; ?> <span class="badge"><?php echo $cantidad; ?></span>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <div class="mt-3">
                        <h6 class="form-label">Filtros por Situación</h6>
                        <div class="filter-tags">
                            <?php foreach($proyectosPorSituacion as $situacion => $cantidad): ?>
                            <div class="filter-tag" data-filter="situacion" data-value="<?php echo $situacion; ?>">
                                <?php echo $situacion; ?> <span class="badge"><?php echo $cantidad; ?></span>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    
                    <div class="row mt-4">
                        <div class="col-12 text-end">
                            <button type="button" id="btnLimpiarFiltros" class="btn btn-outline-secondary btn-filter me-2">
                                <i class="fas fa-eraser"></i> Limpiar Filtros
                            </button>
                            <button type="button" id="btnFiltrar" class="btn btn-primary btn-filter">
                                <i class="fas fa-search"></i> Aplicar Filtros
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Sección de Tabla de Proyectos -->
<div class="row">
    <div class="col-12">
        <div class="card animate-fade-in" style="animation-delay: 0.2s;">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h6 class="card-title mb-0">
                    <i class="fas fa-list-ul me-2 text-primary"></i>
                    Listado de Proyectos
                </h6>
                <div class="d-flex align-items-center">
                    <div class="dropdown me-2">
                        <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" id="exportDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="fas fa-download me-1"></i> Exportar
                        </button>
                        <ul class="dropdown-menu" aria-labelledby="exportDropdown">
                            <li><a class="dropdown-item" href="#" id="exportExcel"><i class="fas fa-file-excel me-2 text-success"></i> Excel</a></li>
                            <li><a class="dropdown-item" href="#" id="exportPDF"><i class="fas fa-file-pdf me-2 text-danger"></i> PDF</a></li>
                            <li><a class="dropdown-item" href="#" id="exportCSV"><i class="fas fa-file-csv me-2 text-primary"></i> CSV</a></li>
                        </ul>
                    </div>
                    <div class="input-group input-group-sm" style="width: 200px;">
                        <input type="text" class="form-control" id="tableSearch" placeholder="Buscar...">
                        <button class="btn btn-outline-secondary" type="button">
                            <i class="fas fa-search"></i>
                        </button>
                    </div>
                </div>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle" id="tablaProyectos">
                    <thead>
                        <tr>
                            <th class="text-center" style="width: 80px;">Año</th>
                            <th style="width: 140px;">Número</th>
                            <th>Nombre del Proyecto</th>
                            <th>Entidad Contratante</th>
                            <th class="text-center" style="width: 120px;">Situación</th>
                            <th class="text-center" style="width: 160px;">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(empty($proyectos)): ?>
                        <tr>
                            <td colspan="6" class="text-center py-4">
                                <div class="py-5">
                                    <i class="fas fa-folder-open text-muted mb-3" style="font-size: 3rem;"></i>
                                    <p class="mb-1 fw-bold">No se encontraron proyectos</p>
                                    <p class="text-muted small">Intenta cambiar los filtros de búsqueda o crea un nuevo proyecto</p>
                                </div>
                            </td>
                        </tr>
                        <?php else: ?>
                        <?php foreach($proyectos as $proyecto): ?>
                        <tr class="proyecto-row">
                            <td class="text-center proyecto-anio">
                                <span class="fw-semibold"><?php echo $proyecto['anio']; ?></span>
                            </td>
                            <td class="proyecto-numero">
                                <div class="d-flex align-items-center">
                                    <i class="fas fa-file-contract text-primary me-2"></i>
                                    <span class="fw-semibold"><?php echo $proyecto['numero']; ?></span>
                                </div>
                            </td>
                            <td>
                                <div class="proyecto-nombre text-wrap" style="max-width: 300px;">
                                    <?php echo htmlspecialchars($proyecto['nombre']); ?>
                                </div>
                            </td>
                            <td>
                                <div class="proyecto-entidad text-wrap" style="max-width: 200px;">
                                    <i class="fas fa-building text-secondary me-1"></i>
                                    <?php echo htmlspecialchars($proyecto['entidad']); ?>
                                </div>
                            </td>
                            <td class="text-center">
                                <?php 
                                    $situacionLower = strtolower($proyecto['situacion']);
                                    $statusClass = 'status-Suscrito';
                                    
                                    if (strpos($situacionLower, 'ejecuci') !== false || strpos($situacionLower, 'ejecución') !== false) {
                                        $statusClass = 'status-ejecucion';
                                    } elseif (strpos($situacionLower, 'finalizado') !== false) {
                                        $statusClass = 'status-Finalizado';
                                    }
                                ?>
                                <span class="status-badge <?php echo $statusClass; ?> proyecto-situacion">
                                    <?php echo $proyecto['situacion']; ?>
                                </span>
                            </td>
                            <td class="text-center">
                                <div class="action-buttons">
                                    <a href="main.php?page=proyecto_individual&id=<?php echo $proyecto['id']; ?>" class="btn action-button btn-view" title="Ver detalles">
                                        <i class="fas fa-eye"></i> Ver
                                    </a>
                                    <a href="main.php?page=editar_proyecto&id=<?php echo $proyecto['id']; ?>" class="btn action-button btn-edit" title="Editar proyecto">
                                        <i class="fas fa-edit"></i> Editar
                                    </a>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            <div class="pagination-info">
                <div class="count-display">
                    Mostrando <span id="contador-registros">0</span> de <?php echo count($proyectos); ?> proyectos
                </div>
                <nav aria-label="Page navigation">
                    <ul class="pagination pagination-sm" id="paginacion">
                        <!-- La paginación se generará con JavaScript -->
                    </ul>
                </nav>
            </div>
        </div>
    </div>
</div>

<!-- Overlay de carga -->
<div class="loading-overlay" id="loadingOverlay">
    <div class="loading-spinner"></div>
    <div class="loading-text">Procesando...</div>
</div>

<!-- Script para el filtrado y otras funcionalidades -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Variables principales
    const tablaProyectos = document.getElementById('tablaProyectos');
    const filasProyectos = document.querySelectorAll('.proyecto-row');
    const contadorRegistros = document.getElementById('contador-registros');
    const btnFiltrar = document.getElementById('btnFiltrar');
    const btnLimpiarFiltros = document.getElementById('btnLimpiarFiltros');
    const filtroForm = document.getElementById('filtroProyectos');
    const filterHeader = document.getElementById('filterHeader');
    const filterBody = document.getElementById('filterBody');
    const filterActiveIndicator = document.getElementById('filterActiveIndicator');
    const loadingOverlay = document.getElementById('loadingOverlay');
    const tableSearch = document.getElementById('tableSearch');
    
    // Variables para filtros dinámicos
    const filtroAnio = document.getElementById('filtroAnio');
    const filtroEntidad = document.getElementById('filtroEntidad');
    const filtroSituacion = document.getElementById('filtroSituacion');
    
    // Datos para filtros dependientes
    const entidadesPorAnio = <?php echo $entidadesPorAnioJSON; ?>;
    const situacionesPorAnio = <?php echo $situacionesPorAnioJSON; ?>;
    
    // Configuración de paginación
    const itemsPorPagina = 10;
    let paginaActual = 1;
    let filasFiltradas = [];
    
    // Inicialización
    inicializar();
    
    // Función de inicialización
    function inicializar() {
        // Contar y mostrar filas visibles inicialmente
        actualizarContadorRegistros();
        
        // Configurar toggle para el panel de filtros
        configurarToggleFiltros();
        
        // Configurar eventos de filtros
        configurarEventosFiltros();
        
        // Configurar filtros rápidos (tags)
        configurarFiltrosRapidos();
        
        // Configurar paginación inicial
        actualizarPaginacion();
        
        // Inicializar tooltips
        inicializarTooltips();
        
        // Configurar búsqueda en tabla
        configurarBusquedaTabla();
        
        // Configurar exportación
        configurarExportacion();
        
        // Establecer filas iniciales
        filasFiltradas = Array.from(filasProyectos);
        mostrarPagina(1);
    }
    
    // Función para configurar el toggle de filtros
    function configurarToggleFiltros() {
        filterHeader.addEventListener('click', function() {
            filterBody.classList.toggle('show');
            this.classList.toggle('collapsed');
        });
        
        // Mostrar filtros por defecto
        filterBody.classList.add('show');
    }
    
    // Función para configurar eventos de filtros
    function configurarEventosFiltros() {
        // Evento para el botón de filtrar
        btnFiltrar.addEventListener('click', function() {
            mostrarCargando();
            setTimeout(function() {
                aplicarFiltros();
                ocultarCargando();
            }, 300);
        });
        
        // Evento para limpiar filtros
        btnLimpiarFiltros.addEventListener('click', function() {
            limpiarFiltros();
        });
        
        // Filtrado dinámico: cuando cambia el año, actualizar opciones de entidad y situación
        filtroAnio.addEventListener('change', function() {
            actualizarFiltrosDependientes();
        });
    }
    
    // Función para configurar filtros rápidos (tags)
    function configurarFiltrosRapidos() {
        document.querySelectorAll('.filter-tag').forEach(tag => {
            tag.addEventListener('click', function() {
                const filtroTipo = this.getAttribute('data-filter');
                const filtroValor = this.getAttribute('data-value');
                
                // Quitar la clase active de todos los tags del mismo tipo
                document.querySelectorAll(`.filter-tag[data-filter="${filtroTipo}"]`).forEach(t => {
                    t.classList.remove('active');
                });
                
                // Agregar clase active al tag seleccionado
                this.classList.add('active');
                
                // Establecer el valor en el filtro correspondiente del formulario
                if (filtroTipo === 'anio') {
                    filtroAnio.value = filtroValor;
                    // Actualizar filtros dependientes
                    actualizarFiltrosDependientes();
                } else if (filtroTipo === 'situacion') {
                    filtroSituacion.value = filtroValor;
                }
                
                // Aplicar filtros
                mostrarCargando();
                setTimeout(function() {
                    aplicarFiltros();
                    ocultarCargando();
                }, 200);
            });
        });
    }
    
    // Función para actualizar filtros dependientes (entidades y situaciones según año)
    function actualizarFiltrosDependientes() {
        const anioSeleccionado = filtroAnio.value;
        
        // Guardar selecciones actuales
        const entidadSeleccionada = filtroEntidad.value;
        const situacionSeleccionada = filtroSituacion.value;
        
        // Actualizar opciones de entidades según el año
        if (anioSeleccionado && entidadesPorAnio[anioSeleccionado]) {
            // Vaciar las opciones actuales excepto la primera (Todas las entidades)
            while (filtroEntidad.options.length > 1) {
                filtroEntidad.remove(1);
            }
            
            // Agregar las entidades del año seleccionado
            entidadesPorAnio[anioSeleccionado].forEach(entidad => {
                const option = document.createElement('option');
                option.value = entidad;
                option.textContent = entidad;
                filtroEntidad.appendChild(option);
            });
            
            // Restaurar la selección si existe en las nuevas opciones
            if (entidadSeleccionada) {
                const existe = Array.from(filtroEntidad.options).some(opt => opt.value === entidadSeleccionada);
                filtroEntidad.value = existe ? entidadSeleccionada : '';
            }
        } else {
            // Si no hay año seleccionado o no hay datos, restaurar todas las entidades
            llenarOpcionesOriginales(filtroEntidad, <?php echo json_encode($entidades); ?>);
        }
        
        // Actualizar opciones de situaciones según el año (similar a entidades)
        if (anioSeleccionado && situacionesPorAnio[anioSeleccionado]) {
            // Vaciar las opciones actuales excepto la primera
            while (filtroSituacion.options.length > 1) {
                filtroSituacion.remove(1);
            }
            
            // Agregar las situaciones del año seleccionado
            situacionesPorAnio[anioSeleccionado].forEach(situacion => {
                const option = document.createElement('option');
                option.value = situacion;
                option.textContent = situacion;
                filtroSituacion.appendChild(option);
            });
            
            // Restaurar la selección si existe en las nuevas opciones
            if (situacionSeleccionada) {
                const existe = Array.from(filtroSituacion.options).some(opt => opt.value === situacionSeleccionada);
                filtroSituacion.value = existe ? situacionSeleccionada : '';
            }
        } else {
            // Si no hay año seleccionado o no hay datos, restaurar todas las situaciones
            llenarOpcionesOriginales(filtroSituacion, <?php echo json_encode($situaciones); ?>);
        }
    }
    
    // Función para llenar un select con sus opciones originales
    function llenarOpcionesOriginales(selectElement, opciones) {
        // Guardar la selección actual
        const valorActual = selectElement.value;
        
        // Vaciar las opciones actuales excepto la primera
        while (selectElement.options.length > 1) {
            selectElement.remove(1);
        }
        
        // Agregar todas las opciones originales
        opciones.forEach(opcion => {
            const option = document.createElement('option');
            option.value = opcion;
            option.textContent = opcion;
            selectElement.appendChild(option);
        });
        
        // Restaurar la selección si existe
        if (valorActual) {
            const existe = Array.from(selectElement.options).some(opt => opt.value === valorActual);
            selectElement.value = existe ? valorActual : '';
        }
    }
    
    // Función para aplicar filtros
    function aplicarFiltros() {
        const filtroAnioValor = filtroAnio.value.toLowerCase();
        const filtroNumeroValor = document.getElementById('filtroNumero').value.toLowerCase();
        const filtroNombreValor = document.getElementById('filtroNombre').value.toLowerCase();
        const filtroEntidadValor = filtroEntidad.value.toLowerCase();
        const filtroSituacionValor = filtroSituacion.value.toLowerCase();
        
        let contadorVisible = 0;
        filasFiltradas = [];
        
        // Verificar si hay algún filtro activo
        const hayFiltrosActivos = filtroAnioValor || filtroNumeroValor || filtroNombreValor || 
                                 filtroEntidadValor || filtroSituacionValor;
        
        // Mostrar indicador de filtro activo
        filterActiveIndicator.classList.toggle('show', hayFiltrosActivos);
        
        filasProyectos.forEach(fila => {
            const anio = fila.querySelector('.proyecto-anio').textContent.toLowerCase();
            const numero = fila.querySelector('.proyecto-numero').textContent.toLowerCase();
            const nombre = fila.querySelector('.proyecto-nombre').textContent.toLowerCase();
            const entidad = fila.querySelector('.proyecto-entidad').textContent.toLowerCase();
            const situacion = fila.querySelector('.proyecto-situacion').textContent.toLowerCase();
            
            // Verificar si la fila cumple con todos los filtros
            const coincideAnio = filtroAnioValor === '' || anio.includes(filtroAnioValor);
            const coincideNumero = filtroNumeroValor === '' || numero.includes(filtroNumeroValor);
            const coincideNombre = filtroNombreValor === '' || nombre.includes(filtroNombreValor);
            const coincideEntidad = filtroEntidadValor === '' || entidad.includes(filtroEntidadValor);
            const coincideSituacion = filtroSituacionValor === '' || situacion.includes(filtroSituacionValor);
            
            // Si cumple con todos los filtros, mostrar la fila
            if (coincideAnio && coincideNumero && coincideNombre && coincideEntidad && coincideSituacion) {
                filasFiltradas.push(fila);
                contadorVisible++;
            }
        });
        
        // Actualizar contador y paginación
        contadorRegistros.textContent = contadorVisible;
        actualizarPaginacion();
        mostrarPagina(1); // Volver a la primera página
        
        // Mostrar mensaje cuando no hay resultados
        const tablaBody = tablaProyectos.querySelector('tbody');
        if (contadorVisible === 0 && filasProyectos.length > 0) {
            // Si hay proyectos pero ninguno coincide con los filtros
            tablaBody.innerHTML = `
                <tr>
                    <td colspan="6" class="text-center py-4">
                        <div class="py-5">
                            <i class="fas fa-filter text-muted mb-3" style="font-size: 3rem;"></i>
                            <p class="mb-1 fw-bold">No se encontraron proyectos con los filtros aplicados</p>
                            <p class="text-muted small">Intenta cambiar los filtros de búsqueda</p>
                        </div>
                    </td>
                </tr>
            `;
        }
    }
    
    // Función para limpiar todos los filtros
    function limpiarFiltros() {
        filtroForm.reset();
        
        // Quitar clase active de todos los filtros rápidos
        document.querySelectorAll('.filter-tag').forEach(tag => {
            tag.classList.remove('active');
        });
        
        // Activar el tag "Todos" en el filtro de año
        document.querySelector('.filter-tag[data-filter="anio"][data-value=""]').classList.add('active');
        
        // Restaurar todas las opciones en los filtros dependientes
        llenarOpcionesOriginales(filtroEntidad, <?php echo json_encode($entidades); ?>);
        llenarOpcionesOriginales(filtroSituacion, <?php echo json_encode($situaciones); ?>);
        
        // Ocultar indicador de filtro activo
        filterActiveIndicator.classList.remove('show');
        
        // Aplicar filtros (mostrar todos)
        mostrarCargando();
        setTimeout(function() {
            aplicarFiltros();
            ocultarCargando();
        }, 300);
    }
    
    // Función para actualizar el contador de registros visibles
    function actualizarContadorRegistros() {
        let visibles = 0;
        filasProyectos.forEach(fila => {
            if (fila.style.display !== 'none') {
                visibles++;
            }
        });
        contadorRegistros.textContent = visibles;
    }
    
    // Función para actualizar la paginación
    function actualizarPaginacion() {
        const totalFilas = filasFiltradas.length;
        const totalPaginas = Math.ceil(totalFilas / itemsPorPagina);
        
        const paginacionElement = document.getElementById('paginacion');
        paginacionElement.innerHTML = '';
        
        // Si hay menos de 2 páginas, no mostramos la paginación
        if (totalPaginas <= 1) {
            return;
        }
        
        // Botón anterior
        const prevBtn = document.createElement('li');
        prevBtn.className = 'page-item ' + (paginaActual === 1 ? 'disabled' : '');
        prevBtn.innerHTML = `<a class="page-link" href="#" aria-label="Previous">
                               <span aria-hidden="true">&laquo;</span>
                             </a>`;
        prevBtn.addEventListener('click', function(e) {
            e.preventDefault();
            if (paginaActual > 1) {
                mostrarPagina(paginaActual - 1);
            }
        });
        paginacionElement.appendChild(prevBtn);
        
        // Páginas
        const maxPaginas = 5; // Máximo de páginas a mostrar
        let startPage = Math.max(1, paginaActual - Math.floor(maxPaginas / 2));
        let endPage = Math.min(totalPaginas, startPage + maxPaginas - 1);
        
        // Ajustar startPage si endPage está en el límite
        if (endPage === totalPaginas) {
            startPage = Math.max(1, endPage - maxPaginas + 1);
        }
        
        // Agregar primera página y ellipsis si es necesario
        if (startPage > 1) {
            const primeraPagina = document.createElement('li');
            primeraPagina.className = 'page-item';
            primeraPagina.innerHTML = `<a class="page-link" href="#">1</a>`;
            primeraPagina.addEventListener('click', function(e) {
                e.preventDefault();
                mostrarPagina(1);
            });
            paginacionElement.appendChild(primeraPagina);
            
            if (startPage > 2) {
                const ellipsis = document.createElement('li');
                ellipsis.className = 'page-item disabled';
                ellipsis.innerHTML = `<a class="page-link" href="#">...</a>`;
                paginacionElement.appendChild(ellipsis);
            }
        }
        
        // Agregar páginas numeradas
        for (let i = startPage; i <= endPage; i++) {
            const pagina = document.createElement('li');
            pagina.className = 'page-item ' + (i === paginaActual ? 'active' : '');
            pagina.innerHTML = `<a class="page-link" href="#">${i}</a>`;
            pagina.addEventListener('click', function(e) {
                e.preventDefault();
                mostrarPagina(i);
            });
            paginacionElement.appendChild(pagina);
        }
        
        // Agregar última página y ellipsis si es necesario
        if (endPage < totalPaginas) {
            if (endPage < totalPaginas - 1) {
                const ellipsis = document.createElement('li');
                ellipsis.className = 'page-item disabled';
                ellipsis.innerHTML = `<a class="page-link" href="#">...</a>`;
                paginacionElement.appendChild(ellipsis);
            }
            
            const ultimaPagina = document.createElement('li');
            ultimaPagina.className = 'page-item';
            ultimaPagina.innerHTML = `<a class="page-link" href="#">${totalPaginas}</a>`;
            ultimaPagina.addEventListener('click', function(e) {
                e.preventDefault();
                mostrarPagina(totalPaginas);
            });
            paginacionElement.appendChild(ultimaPagina);
        }
        
        // Botón siguiente
        const nextBtn = document.createElement('li');
        nextBtn.className = 'page-item ' + (paginaActual === totalPaginas ? 'disabled' : '');
        nextBtn.innerHTML = `<a class="page-link" href="#" aria-label="Next">
                               <span aria-hidden="true">&raquo;</span>
                             </a>`;
        nextBtn.addEventListener('click', function(e) {
            e.preventDefault();
            if (paginaActual < totalPaginas) {
                mostrarPagina(paginaActual + 1);
            }
        });
        paginacionElement.appendChild(nextBtn);
    }
    
    // Función para mostrar una página específica
    function mostrarPagina(pagina) {
        paginaActual = pagina;
        const inicio = (pagina - 1) * itemsPorPagina;
        const fin = inicio + itemsPorPagina;
        
        // Ocultar todas las filas
        filasProyectos.forEach(fila => {
            fila.style.display = 'none';
        });
        
        // Mostrar solo las filas de la página actual
        filasFiltradas.slice(inicio, fin).forEach(fila => {
            fila.style.display = '';
        });
        
        // Actualizar estado de la paginación
        actualizarPaginacion();
        
        // Scroll hasta arriba de la tabla
        tablaProyectos.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }
    
    // Función para inicializar tooltips de Bootstrap
    function inicializarTooltips() {
        const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl, {
                placement: 'top',
                delay: { show: 300, hide: 100 }
            });
        });
    }
    
    // Función para configurar búsqueda rápida en la tabla
    function configurarBusquedaTabla() {
        tableSearch.addEventListener('input', function() {
            const searchTerm = this.value.toLowerCase().trim();
            
            if (searchTerm === '') {
                // Si no hay término de búsqueda, mostrar todos según los filtros actuales
                aplicarFiltros();
                return;
            }
            
            // Filtrar las filas
            let contadorVisible = 0;
            filasFiltradas = [];
            
            filasProyectos.forEach(fila => {
                const textoFila = fila.textContent.toLowerCase();
                
                if (textoFila.includes(searchTerm)) {
                    filasFiltradas.push(fila);
                    contadorVisible++;
                }
            });
            
            // Actualizar contador y mostrar primera página
            contadorRegistros.textContent = contadorVisible;
            actualizarPaginacion();
            mostrarPagina(1);
            
            // Mostrar mensaje si no hay resultados
            const tablaBody = tablaProyectos.querySelector('tbody');
            if (contadorVisible === 0) {
                tablaBody.innerHTML = `
                    <tr>
                        <td colspan="6" class="text-center py-4">
                            <div class="py-5">
                                <i class="fas fa-search text-muted mb-3" style="font-size: 3rem;"></i>
                                <p class="mb-1 fw-bold">No se encontraron coincidencias</p>
                                <p class="text-muted small">Intenta con otros términos de búsqueda</p>
                            </div>
                        </td>
                    </tr>
                `;
            }
        });
    }
    
    // Función para configurar opciones de exportación
    function configurarExportacion() {
        document.getElementById('exportExcel').addEventListener('click', function(e) {
            e.preventDefault();
            exportarTabla('excel');
        });
        
        document.getElementById('exportPDF').addEventListener('click', function(e) {
            e.preventDefault();
            exportarTabla('pdf');
        });
        
        document.getElementById('exportCSV').addEventListener('click', function(e) {
            e.preventDefault();
            exportarTabla('csv');
        });
    }
    
    // Función para exportar tabla a diferentes formatos
    function exportarTabla(formato) {
        alert('Esta funcionalidad de exportación a ' + formato.toUpperCase() + ' se implementaría con una biblioteca adicional.');
        // Aquí implementarías la exportación real (ej. con SheetJS para Excel, jsPDF para PDF, etc.)
    }
    
    // Funciones para mostrar/ocultar el overlay de carga
    function mostrarCargando() {
        loadingOverlay.classList.add('show');
    }
    
    function ocultarCargando() {
        loadingOverlay.classList.remove('show');
    }
    
    // Configurar botones de acciones en el encabezado
    configurarBotonesEncabezado();
        
    // Inicializar atajos de teclado
    inicializarAtajosTeclado();
    
    // Aplicar filtros y mostrar la primera página al cargar
    aplicarFiltros();
    mostrarPagina(1);
});

// Función para configurar los botones de acciones en el encabezado
function configurarBotonesEncabezado() {
    // Botón de pantalla completa
    const btnToggleFullscreen = document.getElementById('btnToggleFullscreen');
    if (btnToggleFullscreen) {
        btnToggleFullscreen.addEventListener('click', function() {
            toggleFullScreen();
        });
    }

    // Botón de actualizar datos
    const btnRefreshData = document.getElementById('btnRefreshData');
    if (btnRefreshData) {
        btnRefreshData.addEventListener('click', function() {
            this.querySelector('i').classList.add('fa-spin');
            
            mostrarCargando();
            
            // Simular actualización de datos
            setTimeout(() => {
                this.querySelector('i').classList.remove('fa-spin');
                ocultarCargando();
                mostrarNotificacion('Datos actualizados correctamente', 'success');
                
                // Aquí iría la lógica real para recargar los datos
                aplicarFiltros();
            }, 1000);
        });
    }
    
    // Botón de ayuda
    const btnShowHelp = document.getElementById('btnShowHelp');
    if (btnShowHelp) {
        btnShowHelp.addEventListener('click', function() {
            mostrarAyuda();
        });
    }
}

// Función para alternar pantalla completa
function toggleFullScreen() {
    if (!document.fullscreenElement) {
        // Entrar en pantalla completa
        if (document.documentElement.requestFullscreen) {
            document.documentElement.requestFullscreen();
        } else if (document.documentElement.mozRequestFullScreen) {
            document.documentElement.mozRequestFullScreen();
        } else if (document.documentElement.webkitRequestFullscreen) {
            document.documentElement.webkitRequestFullscreen();
        } else if (document.documentElement.msRequestFullscreen) {
            document.documentElement.msRequestFullscreen();
        }
        
        // Cambiar icono a compress
        document.getElementById('btnToggleFullscreen').innerHTML = '<i class="fas fa-compress-alt"></i>';
        
    } else {
        // Salir de pantalla completa
        if (document.exitFullscreen) {
            document.exitFullscreen();
        } else if (document.mozCancelFullScreen) {
            document.mozCancelFullScreen();
        } else if (document.webkitExitFullscreen) {
            document.webkitExitFullscreen();
        } else if (document.msExitFullscreen) {
            document.msExitFullscreen();
        }
        
        // Cambiar icono a expand
        document.getElementById('btnToggleFullscreen').innerHTML = '<i class="fas fa-expand-alt"></i>';
    }
}

// Función para mostrar la ventana de ayuda
function mostrarAyuda() {
    // Crear el modal de ayuda
    const helpModal = document.createElement('div');
    helpModal.className = 'modal fade';
    helpModal.id = 'helpModal';
    helpModal.setAttribute('tabindex', '-1');
    helpModal.setAttribute('aria-labelledby', 'helpModalLabel');
    helpModal.setAttribute('aria-hidden', 'true');
    
    helpModal.innerHTML = `
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="helpModalLabel">
                        <i class="fas fa-question-circle me-2"></i>
                        Ayuda - Gestión de Proyectos
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="help-section mb-4">
                        <h5 class="text-primary mb-3">
                            <i class="fas fa-filter me-2"></i>
                            Filtrado de Proyectos
                        </h5>
                        <p>Para filtrar los proyectos puede utilizar cualquiera de estas opciones:</p>
                        <ul class="help-list">
                            <li>Use los <strong>filtros rápidos</strong> para filtrar por año o situación con un solo clic</li>
                            <li>Complete los campos del formulario de filtros para búsquedas más específicas</li>
                            <li>Use el campo de búsqueda en la tabla para buscar en todas las columnas</li>
                        </ul>
                    </div>
                    <div class="help-section mb-4">
                        <h5 class="text-primary mb-3">
                            <i class="fas fa-cog me-2"></i>
                            Acciones Disponibles
                        </h5>
                        <p>Para cada proyecto puede realizar las siguientes acciones:</p>
                        <ul class="help-list">
                            <li><i class="fas fa-eye text-primary me-2"></i> <strong>Ver detalles</strong>: Muestra toda la información del proyecto</li>
                            <li><i class="fas fa-edit text-success me-2"></i> <strong>Editar proyecto</strong>: Permite modificar los datos del proyecto</li>
                        </ul>
                    </div>
                    <div class="help-section">
                        <h5 class="text-primary mb-3">
                            <i class="fas fa-keyboard me-2"></i>
                            Atajos de Teclado
                        </h5>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="shortcut-item">
                                    <span class="shortcut-key">F</span> Enfoque en filtros
                                </div>
                                <div class="shortcut-item">
                                    <span class="shortcut-key">L</span> Limpiar filtros
                                </div>
                                <div class="shortcut-item">
                                    <span class="shortcut-key">R</span> Refrescar datos
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="shortcut-item">
                                    <span class="shortcut-key">N</span> Nuevo proyecto
                                </div>
                                <div class="shortcut-item">
                                    <span class="shortcut-key">S</span> Buscar en tabla
                                </div>
                                <div class="shortcut-item">
                                    <span class="shortcut-key">F11</span> Pantalla completa
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-primary" data-bs-dismiss="modal">Entendido</button>
                </div>
            </div>
        </div>
    `;
    
    // Agregar al DOM
    document.body.appendChild(helpModal);
    
    // Inicializar y mostrar el modal
    const modal = new bootstrap.Modal(helpModal);
    modal.show();
    
    // Configurar para eliminar del DOM al cerrarse
    helpModal.addEventListener('hidden.bs.modal', function () {
        helpModal.remove();
    });
}

// Función para mostrar notificaciones
function mostrarNotificacion(mensaje, tipo) {
    // Crear elemento de notificación
    const notificacion = document.createElement('div');
    notificacion.className = `toast-notification toast-${tipo}`;
    
    // Definir contenido según el tipo
    let iconClass = 'fas fa-info-circle';
    if (tipo === 'success') iconClass = 'fas fa-check-circle';
    if (tipo === 'warning') iconClass = 'fas fa-exclamation-triangle';
    if (tipo === 'error') iconClass = 'fas fa-exclamation-circle';
    
    // Estructura de la notificación
    notificacion.innerHTML = `
        <div class="toast-icon">
            <i class="${iconClass}"></i>
        </div>
        <div class="toast-content">
            <p>${mensaje}</p>
        </div>
        <button class="toast-close" onclick="this.parentElement.remove()">
            <i class="fas fa-times"></i>
        </button>
    `;
    
    // Agregar al DOM
    document.body.appendChild(notificacion);
    
    // Mostrar con animación
    setTimeout(() => {
        notificacion.classList.add('show');
    }, 10);
    
    // Quitar después de un tiempo
    setTimeout(() => {
        notificacion.classList.remove('show');
        setTimeout(() => {
            notificacion.remove();
        }, 300);
    }, 5000);
}

// Función para inicializar atajos de teclado
function inicializarAtajosTeclado() {
    document.addEventListener('keydown', function(e) {
        // Solo responder a atajos cuando no se está escribiendo en campos de texto/formularios
        if (document.activeElement.tagName === 'INPUT' || 
            document.activeElement.tagName === 'TEXTAREA' || 
            document.activeElement.tagName === 'SELECT') {
            return;
        }
        
        // Definir atajos
        switch (e.key.toLowerCase()) {
            case 'f':
                // Enfocar en filtros
                document.getElementById('filtroNombre').focus();
                e.preventDefault();
                break;
            case 'l':
                // Limpiar filtros
                document.getElementById('btnLimpiarFiltros').click();
                e.preventDefault();
                break;
            case 'r':
                // Refrescar datos
                document.getElementById('btnRefreshData').click();
                e.preventDefault();
                break;
            case 'n':
                // Nuevo proyecto
                window.location.href = 'main.php?page=crear_proyecto';
                e.preventDefault();
                break;
            case 's':
                // Buscar en tabla
                document.getElementById('tableSearch').focus();
                e.preventDefault();
                break;
        }
    });
}
</script>