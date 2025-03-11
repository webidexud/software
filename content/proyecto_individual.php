<?php
/**
 * Página para visualizar el detalle completo de un proyecto
 * Versión mejorada con interfaz amigable y gama de colores suave
 */

// Verificar que se haya proporcionado un ID
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    // Redirigir a la lista de proyectos si no se proporciona un ID válido
    header('Location: main.php?page=proyecto');
    exit;
}

$proyecto_id = intval($_GET['id']);

// Verificar si el archivo modelo existe
if (file_exists('models/proyecto_model.php')) {
    // Incluir el modelo de proyecto
    include_once 'models/proyecto_model.php';
    
    // Obtener el detalle del proyecto
    $proyecto = function_exists('obtenerProyectoDetalle') ? obtenerProyectoDetalle($proyecto_id) : null;
} else {
    // Si no encuentra el modelo, mostrar mensaje de error
    $proyecto = null;
}

// Si no se encuentra el proyecto, mostrar mensaje de error
if (!$proyecto) {
    echo '<div class="alert alert-danger" role="alert">El proyecto solicitado no existe o no se puede acceder a él en este momento.</div>';
    exit;
}

// Función para formatear fechas
function formatearFecha($fecha) {
    if (!$fecha) return "-";
    
    try {
        $date = new DateTime($fecha);
        return $date->format('d/m/Y');
    } catch (Exception $e) {
        return "-";
    }
}

// Función para formatear valores monetarios
function formatearMoneda($valor) {
    if (!$valor) return "$0.00";
    
    return '$' . number_format($valor, 2, '.', ',');
}

// Obtener los datos de las entidades asociadas
$entidadesProyecto = obtenerEntidadesProyecto($proyecto_id);

// Obtener los contratistas asociados al proyecto
$contratistas = obtenerContratistasProyecto($proyecto_id);

// Obtener las actas del proyecto
$actas = obtenerActasProyecto($proyecto_id);

// Calcular la duración del proyecto en días
$duracion = null;
if (function_exists('calcularDuracionProyecto') && !empty($proyecto['fecha_inicio']) && !empty($proyecto['fecha_termina'])) {
    $duracion = calcularDuracionProyecto($proyecto['fecha_inicio'], $proyecto['fecha_termina']);
}

// Calcular porcentaje de avance basado en el tiempo transcurrido
$porcentajeAvance = 0;
$diasRestantes = 0;
$estadoTiempo = 'normal';

if (!empty($proyecto['fecha_inicio']) && !empty($proyecto['fecha_termina'])) {
    $fechaInicio = new DateTime($proyecto['fecha_inicio']);
    $fechaFin = new DateTime($proyecto['fecha_termina']);
    $hoy = new DateTime();
    
    $duracionTotal = $fechaInicio->diff($fechaFin)->days;
    
    if ($duracionTotal > 0) {
        if ($hoy < $fechaInicio) {
            // El proyecto aún no ha comenzado
            $porcentajeAvance = 0;
            $diasRestantes = $duracionTotal;
            $estadoTiempo = 'pendiente';
        } elseif ($hoy > $fechaFin) {
            // El proyecto ya terminó
            $porcentajeAvance = 100;
            $diasRestantes = 0;
            $estadoTiempo = 'completado';
        } else {
            // El proyecto está en curso
            $transcurrido = $fechaInicio->diff($hoy)->days;
            $porcentajeAvance = min(100, round(($transcurrido / $duracionTotal) * 100));
            $diasRestantes = $fechaFin->diff($hoy)->days;
            
            // Determinar si está atrasado
            $porcentajeIdeal = ($transcurrido / $duracionTotal) * 100;
            if ($porcentajeAvance < $porcentajeIdeal) {
                $estadoTiempo = 'atrasado';
            } elseif ($porcentajeAvance > $porcentajeIdeal + 10) {
                $estadoTiempo = 'adelantado';
            }
        }
    }
}

// Obtener información de las últimas actividades (simulado)
$ultimasActividades = [
    [
        'fecha' => date('Y-m-d H:i:s', strtotime('-3 days')), 
        'tipo' => 'Actualización', 
        'descripcion' => 'Se actualizó la información del proyecto',
        'usuario' => 'admin'
    ],
    [
        'fecha' => date('Y-m-d H:i:s', strtotime('-7 days')), 
        'tipo' => 'Documento', 
        'descripcion' => 'Se agregó un acta de seguimiento',
        'usuario' => 'coordinador'
    ],
    [
        'fecha' => date('Y-m-d H:i:s', strtotime('-14 days')), 
        'tipo' => 'Cambio', 
        'descripcion' => 'Se actualizó el presupuesto del proyecto',
        'usuario' => 'admin'
    ]
];
?>

<!-- Estilos específicos para esta página -->
<style>
:root {
    --primary: #6366F1;
    --primary-light: #A5B4FC;
    --primary-dark: #4F46E5;
    --secondary: #64748B;
    --success: #10B981;
    --success-light: #D1FAE5;
    --info: #06B6D4;
    --info-light: #CFFAFE;
    --warning: #F59E0B;
    --warning-light: #FEF3C7;
    --danger: #EF4444;
    --danger-light: #FEE2E2;
    --neutral: #F8FAFC;
    --neutral-50: #F8FAFC;
    --neutral-100: #F1F5F9;
    --neutral-200: #E2E8F0;
    --neutral-300: #CBD5E1;
    --neutral-400: #94A3B8;
    --neutral-500: #64748B;
    --neutral-600: #475569;
    --neutral-700: #334155;
    --neutral-800: #1E293B;
    --neutral-900: #0F172A;
    
    --body-bg: #F8FAFC;
    --card-bg: #FFFFFF;
    --shadow-sm: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
    --shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1), 0 1px 2px 0 rgba(0, 0, 0, 0.06);
    --shadow-md: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
    --shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
    
    --radius-sm: 0.25rem;
    --radius: 0.375rem;
    --radius-md: 0.5rem;
    --radius-lg: 0.75rem;
    --radius-xl: 1rem;
    
    --font-sans: ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
}

/* Estilos generales */
body {
    font-family: var(--font-sans);
    background-color: var(--body-bg);
    color: var(--neutral-700);
}

.custom-card {
    background-color: var(--card-bg);
    border-radius: var(--radius-lg);
    box-shadow: var(--shadow);
    border: none;
    transition: all 0.2s ease;
    overflow: hidden;
}

.custom-card:hover {
    box-shadow: var(--shadow-md);
}

.custom-card-header {
    background-color: var(--card-bg);
    border-bottom: 1px solid var(--neutral-200);
    padding: 1.25rem 1.5rem;
}

.custom-card-title {
    font-weight: 600;
    color: var(--neutral-800);
    font-size: 1.1rem;
    margin-bottom: 0;
}

.custom-card-body {
    padding: 1.5rem;
}

/* Estilos para el encabezado del proyecto */
.project-header {
    background-color: #fff;
    border-radius: var(--radius-lg);
    box-shadow: var(--shadow-md);
    padding: 1.5rem;
    position: relative;
    overflow: hidden;
    margin-bottom: 1.5rem;
    transition: transform 0.3s ease;
    border-left: 5px solid var(--primary);
}

.project-header::before {
    content: '';
    position: absolute;
    top: 0;
    right: 0;
    width: 200px;
    height: 200px;
    background: radial-gradient(circle at center, rgba(99, 102, 241, 0.07) 0%, rgba(99, 102, 241, 0) 70%);
    border-radius: 50%;
    transform: translate(30%, -30%);
    z-index: 0;
}

.project-title {
    color: var(--neutral-900);
    font-weight: 700;
    margin-bottom: 0.5rem;
    position: relative;
    z-index: 1;
}

.project-subtitle {
    color: var(--neutral-600);
    font-size: 1rem;
    font-weight: 500;
    margin-bottom: 0.5rem;
    position: relative;
    z-index: 1;
}

.project-code {
    display: inline-flex;
    align-items: center;
    padding: 0.35rem 0.75rem;
    background-color: var(--primary-light);
    color: var(--primary-dark);
    border-radius: var(--radius);
    font-weight: 600;
    font-size: 0.875rem;
    margin-bottom: 0.75rem;
}

.project-code i {
    margin-right: 0.5rem;
}

.status-badge {
    display: inline-flex;
    align-items: center;
    padding: 0.35rem 0.75rem;
    border-radius: var(--radius);
    font-weight: 600;
    font-size: 0.75rem;
    text-transform: uppercase;
    letter-spacing: 0.025em;
}

.status-8 {
    background-color: var(--warning-light);
    color: var(--warning);
}

.status-14 {
    background-color: var(--info-light);
    color: var(--info);
}

.status-19 {
    background-color: var(--success-light);
    color: var(--success);
}

.project-dates {
    display: flex;
    flex-wrap: wrap;
    gap: 1rem;
    margin-top: 1rem;
}

.date-item {
    display: flex;
    align-items: center;
    color: var(--neutral-600);
    font-size: 0.875rem;
}

.date-item i {
    margin-right: 0.5rem;
    color: var(--primary);
}

/* Estilos para tabs personalizados */
.custom-tabs {
    display: flex;
    border-bottom: 1px solid var(--neutral-200);
    margin-bottom: 1.5rem;
    overflow-x: auto;
    scrollbar-width: thin;
}

.custom-tabs::-webkit-scrollbar {
    height: 4px;
}

.custom-tabs::-webkit-scrollbar-track {
    background: var(--neutral-100);
}

.custom-tabs::-webkit-scrollbar-thumb {
    background-color: var(--neutral-300);
    border-radius: 20px;
}

.custom-tab {
    padding: 0.75rem 1.25rem;
    font-weight: 500;
    color: var(--neutral-500);
    position: relative;
    cursor: pointer;
    transition: all 0.2s ease;
    white-space: nowrap;
}

.custom-tab:hover {
    color: var(--primary);
}

.custom-tab.active {
    color: var(--primary);
}

.custom-tab.active::after {
    content: '';
    position: absolute;
    bottom: -1px;
    left: 0;
    width: 100%;
    height: 2px;
    background-color: var(--primary);
    border-radius: 2px 2px 0 0;
}

.custom-tab i {
    margin-right: 0.5rem;
}

.tab-content {
    display: none;
}

.tab-content.active {
    display: block;
}

/* Estilos para detalles de proyecto */
.info-section {
    margin-bottom: 2rem;
}

.info-section-title {
    font-size: 1rem;
    font-weight: 600;
    color: var(--neutral-700);
    margin-bottom: 1rem;
    padding-bottom: 0.5rem;
    border-bottom: 1px solid var(--neutral-200);
}

.info-row {
    display: flex;
    margin-bottom: 0.5rem;
}

.info-label {
    width: 40%;
    font-size: 0.875rem;
    color: var(--neutral-500);
    padding-right: 1rem;
}

.info-value {
    width: 60%;
    font-size: 0.875rem;
    color: var(--neutral-800);
    font-weight: 500;
}

.object-box {
    background-color: var(--neutral-50);
    border-radius: var(--radius);
    padding: 1rem;
    border: 1px solid var(--neutral-200);
    font-size: 0.875rem;
    color: var(--neutral-700);
    line-height: 1.6;
    margin-bottom: 1.5rem;
}

/* Estilos para tarjetas de valores */
.value-cards {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
    gap: 1rem;
    margin-bottom: 1.5rem;
}

.value-card {
    background-color: var(--card-bg);
    border-radius: var(--radius);
    padding: 1rem;
    box-shadow: var(--shadow-sm);
    transition: all 0.2s ease;
    border: 1px solid var(--neutral-200);
}

.value-card:hover {
    transform: translateY(-2px);
    box-shadow: var(--shadow);
}

.value-label {
    font-size: 0.75rem;
    color: var(--neutral-500);
    margin-bottom: 0.5rem;
}

.value-amount {
    font-size: 1.25rem;
    font-weight: 700;
    color: var(--neutral-800);
}

.progress-container {
    margin: 1.5rem 0;
}

.progress-label {
    display: flex;
    justify-content: space-between;
    margin-bottom: 0.5rem;
}

.progress-title {
    font-size: 0.875rem;
    font-weight: 500;
    color: var(--neutral-700);
}

.progress-percentage {
    font-size: 0.875rem;
    font-weight: 600;
    color: var(--neutral-800);
}

.custom-progress {
    height: 0.5rem;
    background-color: var(--neutral-200);
    border-radius: var(--radius);
    overflow: hidden;
}

.custom-progress-bar {
    height: 100%;
    border-radius: var(--radius);
}

.progress-normal {
    background-color: var(--primary);
}

.progress-atrasado {
    background-color: var(--warning);
}

.progress-adelantado {
    background-color: var(--success);
}

.progress-completado {
    background-color: var(--info);
}

.progress-pendiente {
    background-color: var(--neutral-400);
}

/* Estilos para tabla de actas */
.custom-table {
    width: 100%;
    border-collapse: separate;
    border-spacing: 0;
    margin-bottom: 1.5rem;
}

.custom-table th {
    background-color: var(--neutral-100);
    color: var(--neutral-600);
    font-size: 0.75rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    padding: 0.75rem 1rem;
    border-bottom: 1px solid var(--neutral-200);
}

.custom-table th:first-child {
    border-top-left-radius: var(--radius);
}

.custom-table th:last-child {
    border-top-right-radius: var(--radius);
}

.custom-table td {
    padding: 0.75rem 1rem;
    vertical-align: middle;
    border-bottom: 1px solid var(--neutral-200);
    font-size: 0.875rem;
    color: var(--neutral-700);
}

.custom-table tbody tr:hover {
    background-color: var(--neutral-50);
}

.custom-table tbody tr:last-child td {
    border-bottom: none;
}

.custom-table tbody tr:last-child td:first-child {
    border-bottom-left-radius: var(--radius);
}

.custom-table tbody tr:last-child td:last-child {
    border-bottom-right-radius: var(--radius);
}

/* Estilos para botones y acciones */
.action-button {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 0.75rem;
    font-weight: 500;
    padding: 0.35rem 0.75rem;
    border-radius: var(--radius);
    transition: all 0.2s ease;
    cursor: pointer;
    text-decoration: none;
}

.action-button i {
    margin-right: 0.4rem;
}

.btn-primary {
    background-color: var(--primary);
    color: white;
    border: none;
}

.btn-primary:hover {
    background-color: var(--primary-dark);
    transform: translateY(-1px);
}

.btn-secondary {
    background-color: var(--neutral-100);
    color: var(--neutral-700);
    border: 1px solid var(--neutral-200);
}

.btn-secondary:hover {
    background-color: var(--neutral-200);
    transform: translateY(-1px);
}

.btn-info {
    background-color: var(--info-light);
    color: var(--info);
    border: none;
}

.btn-info:hover {
    background-color: rgba(6, 182, 212, 0.2);
    transform: translateY(-1px);
}

.btn-success {
    background-color: var(--success-light);
    color: var(--success);
    border: none;
}

.btn-success:hover {
    background-color: rgba(16, 185, 129, 0.2);
    transform: translateY(-1px);
}

.btn-warning {
    background-color: var(--warning-light);
    color: var(--warning);
    border: none;
}

.btn-warning:hover {
    background-color: rgba(245, 158, 11, 0.2);
    transform: translateY(-1px);
}

.btn-danger {
    background-color: var(--danger-light);
    color: var(--danger);
    border: none;
}

.btn-danger:hover {
    background-color: rgba(239, 68, 68, 0.2);
    transform: translateY(-1px);
}

.btn-group {
    display: flex;
    flex-wrap: wrap;
    gap: 0.5rem;
}

/* Activity Timeline */
.timeline {
    position: relative;
    margin: 0 0 1.5rem 0.5rem;
}

.timeline::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    height: 100%;
    width: 2px;
    background-color: var(--neutral-200);
}

.timeline-item {
    position: relative;
    margin-bottom: 1.5rem;
    padding-left: 1.5rem;
}

.timeline-item:last-child {
    margin-bottom: 0;
}

.timeline-dot {
    position: absolute;
    top: 0.25rem;
    left: -0.3125rem;
    width: 0.625rem;
    height: 0.625rem;
    border-radius: 50%;
    background-color: var(--primary);
}

.timeline-content {
    margin-bottom: 0.25rem;
}

.timeline-date {
    font-size: 0.75rem;
    color: var(--neutral-500);
}

.timeline-title {
    font-size: 0.875rem;
    font-weight: 600;
    color: var(--neutral-800);
    margin-bottom: 0.25rem;
}

.timeline-desc {
    font-size: 0.8125rem;
    color: var(--neutral-600);
}

.timeline-usuario {
    font-size: 0.75rem;
    color: var(--neutral-500);
    font-style: italic;
}

/* Notificaciones y tags */
.notification-badge {
    display: inline-flex;
    align-items: center;
    padding: 0.2rem 0.5rem;
    background-color: var(--primary-light);
    color: var(--primary-dark);
    border-radius: var(--radius);
    font-weight: 600;
    font-size: 0.75rem;
    margin-left: 0.5rem;
}

.tag {
    display: inline-flex;
    align-items: center;
    padding: 0.2rem 0.5rem;
    border-radius: var(--radius-sm);
    font-weight: 500;
    font-size: 0.75rem;
    margin-right: 0.5rem;
    margin-bottom: 0.5rem;
}

.tag-primary {
    background-color: var(--primary-light);
    color: var(--primary-dark);
}

.tag-info {
    background-color: var(--info-light);
    color: var(--info);
}

.tag-success {
    background-color: var(--success-light);
    color: var(--success);
}

.empty-state {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 2rem;
    text-align: center;
}

.empty-state-icon {
    font-size: 2.5rem;
    color: var(--neutral-300);
    margin-bottom: 1rem;
}

.empty-state-title {
    font-size: 1rem;
    font-weight: 600;
    color: var(--neutral-600);
    margin-bottom: 0.5rem;
}

.empty-state-desc {
    font-size: 0.875rem;
    color: var(--neutral-500);
    max-width: 300px;
    margin: 0 auto;
}

.back-button {
    display: inline-flex;
    align-items: center;
    padding: 0.5rem 1rem;
    background-color: var(--neutral-100);
    color: var(--neutral-700);
    border-radius: var(--radius);
    font-weight: 500;
    font-size: 0.875rem;
    transition: all 0.2s ease;
    margin-bottom: 1rem;
    text-decoration: none;
}

.back-button:hover {
    background-color: var(--neutral-200);
    transform: translateX(-2px);
}

.back-button i {
    margin-right: 0.5rem;
}

/* Responsive */
@media (max-width: 768px) {
    .value-cards {
        grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
    }
    
    .info-row {
        flex-direction: column;
        margin-bottom: 1rem;
    }
    
    .info-label, .info-value {
        width: 100%;
    }
    
    .info-label {
        margin-bottom: 0.25rem;
    }
    
    .project-header {
        padding: 1.25rem;
    }
    
    .custom-tab {
        padding: 0.75rem 1rem;
    }
}

@media (max-width: 576px) {
    .project-dates {
        flex-direction: column;
        gap: 0.5rem;
    }
    
    .value-cards {
        grid-template-columns: 1fr 1fr;
    }
    
    .action-button {
        padding: 0.25rem 0.5rem;
    }
    
    .btn-group {
        flex-direction: column;
        width: 100%;
    }
    
    .btn-group .action-button {
        width: 100%;
        justify-content: center;
    }
}
</style>

<div class="container-fluid py-4">
    <!-- Botón de regreso -->
    <a href="main.php?page=proyecto" class="back-button">
        <i class="fas fa-arrow-left"></i> Volver a Proyectos
    </a>
    
    <!-- Encabezado del Proyecto -->
    <div class="project-header">
        <div class="row">
            <div class="col-md-8">
                <div class="project-code">
                    <i class="fas fa-file-contract"></i> <?php echo htmlspecialchars($proyecto['numero_pro_entidad']); ?>
                </div>
                <h4 class="project-title"><?php echo htmlspecialchars($proyecto['nombre']); ?></h4>
                <p class="project-subtitle"><?php echo htmlspecialchars($proyecto['entidad']); ?></p>
                
                <?php 
                $situacionClass = 'status-8';
                if ($proyecto['situacion'] == 14) {
                    $situacionClass = 'status-14';
                } elseif ($proyecto['situacion'] == 19) {
                    $situacionClass = 'status-19';
                }
                ?>
                
                <span class="status-badge <?php echo $situacionClass; ?>">
                    <?php echo isset($proyecto['situacion_descripcion']) ? $proyecto['situacion_descripcion'] : 'Estado ' . $proyecto['situacion']; ?>
                </span>
                
                <div class="project-dates">
                    <div class="date-item">
                        <i class="far fa-calendar-alt"></i> Inicio: <?php echo formatearFecha($proyecto['fecha_inicio']); ?>
                    </div>
                    <div class="date-item">
                        <i class="far fa-calendar-check"></i> Fin: <?php echo formatearFecha($proyecto['fecha_termina']); ?>
                    </div>
                    <div class="date-item">
                        <i class="fas fa-signature"></i> Suscripción: <?php echo formatearFecha($proyecto['fecha_suscripcion']); ?>
                    </div>
                </div>
            </div>
            <div class="col-md-4 mt-3 mt-md-0">
                <div class="value-cards">
                    <div class="value-card">
                        <div class="value-label">Valor Total</div>
                        <div class="value-amount"><?php echo formatearMoneda($proyecto['valor']); ?></div>
                    </div>
                    <div class="value-card">
                        <div class="value-label">Aporte UD</div>
                        <div class="value-amount"><?php echo formatearMoneda($proyecto['aporte_ud']); ?></div>
                    </div>
                </div>
                
                <!-- Barra de progreso del proyecto basada en el tiempo transcurrido -->
                <?php if ($porcentajeAvance > 0 || $estadoTiempo == 'pendiente' || $estadoTiempo == 'completado'): ?>
                <div class="progress-container">
                    <div class="progress-label">
                        <div class="progress-title">
                            <?php 
                            switch ($estadoTiempo) {
                                case 'pendiente':
                                    echo 'Pendiente de inicio';
                                    break;
                                case 'atrasado':
                                    echo 'En progreso (atrasado)';
                                    break;
                                case 'adelantado':
                                    echo 'En progreso (adelantado)';
                                    break;
                                case 'completado':
                                    echo 'Completado';
                                    break;
                                default:
                                    echo 'En progreso';
                            }
                            ?>
                        </div>
                        <div class="progress-percentage"><?php echo $porcentajeAvance; ?>%</div>
                    </div>
                    <div class="custom-progress">
                        <div class="custom-progress-bar progress-<?php echo $estadoTiempo; ?>" style="width: <?php echo $porcentajeAvance; ?>%"></div>
                    </div>
                    <div class="date-item" style="margin-top: 0.5rem; font-size: 0.75rem;">
                        <?php if ($estadoTiempo == 'pendiente'): ?>
                            <i class="far fa-clock"></i> El proyecto iniciará en los próximos días
                        <?php elseif ($estadoTiempo == 'completado'): ?>
                            <i class="fas fa-check-circle"></i> Proyecto finalizado
                        <?php else: ?>
                            <i class="far fa-hourglass"></i> <?php echo $diasRestantes; ?> días restantes
                        <?php endif; ?>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Navegación por pestañas personalizada -->
    <div class="custom-tabs" id="projectTabs">
        <div class="custom-tab active" data-tab="info">
            <i class="fas fa-info-circle"></i> Información General
        </div>
        <div class="custom-tab" data-tab="financiera">
            <i class="fas fa-money-bill-wave"></i> Información Financiera
        </div>
        <div class="custom-tab" data-tab="contactos">
            <i class="fas fa-address-book"></i> Contactos
        </div>
        <div class="custom-tab" data-tab="actas">
            <i class="fas fa-file-signature"></i> Actas del Proyecto
            <?php if(!empty($actas)): ?>
            <span class="notification-badge"><?php echo count($actas); ?></span>
            <?php endif; ?>
        </div>
        <div class="custom-tab" data-tab="contratistas">
            <i class="fas fa-users"></i> Contratistas
            <?php if(!empty($contratistas)): ?>
            <span class="notification-badge"><?php echo count($contratistas); ?></span>
            <?php endif; ?>
        </div>
        <div class="custom-tab" data-tab="actividad">
            <i class="fas fa-history"></i> Actividad Reciente
        </div>
    </div>
    
    <!-- Contenido de las pestañas -->
    <!-- Pestaña de Información General -->
    <div class="tab-content active" id="tab-info">
        <div class="row">
            <div class="col-lg-8">
                <!-- Objeto del proyecto -->
                <div class="info-section">
                    <h5 class="info-section-title">Objeto del Proyecto</h5>
                    <div class="object-box">
                        <?php echo nl2br(htmlspecialchars($proyecto['objeto'])); ?>
                    </div>
                </div>
                
                <!-- Información básica -->
                <div class="custom-card mb-4">
                    <div class="custom-card-header">
                        <h6 class="custom-card-title">Información Básica</h6>
                    </div>
                    <div class="custom-card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="info-row">
                                    <div class="info-label">Código del Proyecto</div>
                                    <div class="info-value"><?php echo $proyecto['numero_pro']; ?></div>
                                </div>
                                <div class="info-row">
                                    <div class="info-label">Entidad Contratante</div>
                                    <div class="info-value"><?php echo htmlspecialchars($proyecto['entidad']); ?></div>
                                </div>
                                <div class="info-row">
                                    <div class="info-label">Tipo de Proyecto</div>
                                    <div class="info-value">
                                        <?php 
                                        if (!empty($proyecto['tipo_proyecto_descripcion'])) {
                                            echo htmlspecialchars($proyecto['tipo_proyecto_descripcion']);
                                        } else {
                                            echo $proyecto['tipo_proyecto'] . ' - Por definir';
                                        }
                                        ?>
                                    </div>
                                </div>
                                <div class="info-row">
                                    <div class="info-label">Modalidad</div>
                                    <div class="info-value">
                                        <?php 
                                        if (isset($proyecto['modalidad_descripcion'])) {
                                            echo htmlspecialchars($proyecto['modalidad_descripcion']);
                                        } else {
                                            echo $proyecto['modalidad'] . ' - Por definir';
                                        }
                                        ?>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="info-row">
                                    <div class="info-label">Número de Referencia</div>
                                    <div class="info-value"><?php echo htmlspecialchars($proyecto['numero_pro_entidad']); ?></div>
                                </div>
                                <div class="info-row">
                                    <div class="info-label">Año</div>
                                    <div class="info-value"><?php echo $proyecto['anio_pro']; ?></div>
                                </div>
                                <div class="info-row">
                                    <div class="info-label">Situación</div>
                                    <div class="info-value">
                                        <?php 
                                        if (isset($proyecto['situacion_descripcion'])) {
                                            echo htmlspecialchars($proyecto['situacion_descripcion']);
                                        } else {
                                            echo $proyecto['situacion'] . ' - Por definir';
                                        }
                                        ?>
                                    </div>
                                </div>
                                <div class="info-row">
                                    <div class="info-label">Estado</div>
                                    <div class="info-value"><?php echo $proyecto['estado'] == 'A' ? 'Activo' : 'Inactivo'; ?></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Fechas y plazos -->
                <div class="custom-card mb-4">
                    <div class="custom-card-header">
                        <h6 class="custom-card-title">Fechas y Plazos</h6>
                    </div>
                    <div class="custom-card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="info-row">
                                    <div class="info-label">Fecha de Inicio</div>
                                    <div class="info-value"><?php echo formatearFecha($proyecto['fecha_inicio']); ?></div>
                                </div>
                                <div class="info-row">
                                    <div class="info-label">Fecha de Terminación</div>
                                    <div class="info-value"><?php echo formatearFecha($proyecto['fecha_termina']); ?></div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="info-row">
                                    <div class="info-label">Fecha de Suscripción</div>
                                    <div class="info-value"><?php echo formatearFecha($proyecto['fecha_suscripcion']); ?></div>
                                </div>
                                <div class="info-row">
                                    <div class="info-label">Duración</div>
                                    <div class="info-value">
                                        <?php 
                                        if ($duracion) {
                                            $partes = [];
                                            if ($duracion['anios'] > 0) {
                                                $partes[] = $duracion['anios'] . ' ' . ($duracion['anios'] == 1 ? 'año' : 'años');
                                            }
                                            if ($duracion['meses'] > 0) {
                                                $partes[] = $duracion['meses'] . ' ' . ($duracion['meses'] == 1 ? 'mes' : 'meses');
                                            }
                                            if ($duracion['dias'] > 0) {
                                                $partes[] = $duracion['dias'] . ' ' . ($duracion['dias'] == 1 ? 'día' : 'días');
                                            }
                                            echo !empty($partes) ? implode(', ', $partes) : '-';
                                        } else {
                                            echo '-';
                                        }
                                        ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Observaciones -->
                <?php if(!empty($proyecto['observaciones'])): ?>
                <div class="custom-card mb-4">
                    <div class="custom-card-header">
                        <h6 class="custom-card-title">Observaciones</h6>
                    </div>
                    <div class="custom-card-body">
                        <div class="object-box">
                            <?php echo nl2br(htmlspecialchars($proyecto['observaciones'])); ?>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            </div>
            
            <div class="col-lg-4">
                <!-- Unidad ejecutora -->
                <div class="custom-card mb-4">
                    <div class="custom-card-header">
                        <h6 class="custom-card-title">Unidad Ejecutora</h6>
                    </div>
                    <div class="custom-card-body">
                        <div class="info-row">
                            <div class="info-label">Unidad</div>
                            <div class="info-value">
                                <?php 
                                if (isset($proyecto['unidad_descripcion'])) {
                                    echo htmlspecialchars($proyecto['unidad_descripcion']);
                                } else {
                                    echo $proyecto['unidad'] ? $proyecto['unidad'] . ' - Por definir' : 'No asignada';
                                }
                                ?>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Beneficiarios -->
                <?php if(!empty($proyecto['beneficiarios'])): ?>
                <div class="custom-card mb-4">
                    <div class="custom-card-header">
                        <h6 class="custom-card-title">Beneficiarios</h6>
                    </div>
                    <div class="custom-card-body">
                        <div class="info-row">
                            <div class="info-label">Número de Beneficiarios</div>
                            <div class="info-value"><?php echo number_format($proyecto['beneficiarios']); ?> personas</div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
                
                <!-- Acciones rápidas -->
                <div class="custom-card mb-4">
                    <div class="custom-card-header">
                        <h6 class="custom-card-title">Acciones Rápidas</h6>
                    </div>
                    <div class="custom-card-body">
                        <div class="btn-group">
                            <a href="main.php?page=editar_proyecto&id=<?php echo $proyecto_id; ?>" class="action-button btn-primary">
                                <i class="fas fa-edit"></i> Editar Proyecto
                            </a>
                            <a href="main.php?page=agregar_acta&proyecto_id=<?php echo $proyecto_id; ?>" class="action-button btn-success">
                                <i class="fas fa-file-signature"></i> Agregar Acta
                            </a>
                            <button type="button" class="action-button btn-info" onclick="imprimirProyecto()">
                                <i class="fas fa-print"></i> Imprimir
                            </button>
                            <button type="button" class="action-button btn-secondary" onclick="exportarProyecto(<?php echo $proyecto_id; ?>)">
                                <i class="fas fa-file-export"></i> Exportar
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Pestaña de Información Financiera -->
    <div class="tab-content" id="tab-financiera">
        <div class="row">
            <div class="col-lg-8">
                <!-- Resumen financiero -->
                <div class="custom-card mb-4">
                    <div class="custom-card-header">
                        <h6 class="custom-card-title">Resumen Financiero</h6>
                    </div>
                    <div class="custom-card-body">
                        <div class="value-cards">
                            <div class="value-card">
                                <div class="value-label">Valor Total</div>
                                <div class="value-amount"><?php echo formatearMoneda($proyecto['valor']); ?></div>
                            </div>
                            <div class="value-card">
                                <div class="value-label">Aporte UD</div>
                                <div class="value-amount"><?php echo formatearMoneda($proyecto['aporte_ud']); ?></div>
                            </div>
                            <div class="value-card">
                                <div class="value-label">Aporte Entidad</div>
                                <div class="value-amount">
                                    <?php echo formatearMoneda($proyecto['valor'] - $proyecto['aporte_ud']); ?>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Tipo de financiación -->
                        <div class="info-row mt-4">
                            <div class="info-label">Tipo de Financiación</div>
                            <div class="info-value">
                                <?php 
                                if (isset($proyecto['financiacion_descripcion'])) {
                                    echo htmlspecialchars($proyecto['financiacion_descripcion']);
                                } else {
                                    echo $proyecto['financiacion'] . ' - Por definir';
                                }
                                ?>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Información bancaria -->
                <div class="custom-card mb-4">
                    <div class="custom-card-header">
                        <h6 class="custom-card-title">Información Bancaria</h6>
                    </div>
                    <div class="custom-card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="info-row">
                                    <div class="info-label">Código Tesoral</div>
                                    <div class="info-value"><?php echo htmlspecialchars($proyecto['codigo_tesoral'] ?: '-'); ?></div>
                                </div>
                                <div class="info-row">
                                    <div class="info-label">Banco</div>
                                    <div class="info-value">
                                        <?php
                                        if (!empty($proyecto['codigo_bancario_descripcion'])) {
                                            echo htmlspecialchars($proyecto['codigo_bancario_descripcion']);
                                        } else {
                                            echo htmlspecialchars($proyecto['codigo_bancario'] ?: '-');
                                        }
                                        ?>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="info-row">
                                    <div class="info-label">Tipo de Cuenta</div>
                                    <div class="info-value"><?php echo htmlspecialchars($proyecto['tipo_cuenta'] ?: '-'); ?></div>
                                </div>
                                <div class="info-row">
                                    <div class="info-label">Número de Cuenta</div>
                                    <div class="info-value"><?php echo htmlspecialchars($proyecto['numero_cuenta'] ?: '-'); ?></div>
                                </div>
                            </div>
                        </div>
                        <div class="info-row">
                            <div class="info-label">Referencia UD</div>
                            <div class="info-value"><?php echo htmlspecialchars($proyecto['referencia_ud'] ?: '-'); ?></div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-4">
                <!-- Gráfico de distribución financiera -->
                <div class="custom-card mb-4">
                    <div class="custom-card-header">
                        <h6 class="custom-card-title">Distribución Financiera</h6>
                    </div>
                    <div class="custom-card-body">
                        <div id="chart-financiero" style="height: 250px; display: flex; align-items: center; justify-content: center;">
                            <canvas id="financeChart" width="250" height="250"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Pestaña de Contactos -->
    <div class="tab-content" id="tab-contactos">
        <div class="row">
            <div class="col-lg-6">
                <!-- Contactos del proyecto -->
                <div class="custom-card mb-4">
                    <div class="custom-card-header">
                        <h6 class="custom-card-title">Información de Contacto</h6>
                    </div>
                    <div class="custom-card-body">
                        <div class="info-row">
                            <div class="info-label">Supervisor</div>
                            <div class="info-value"><?php echo htmlspecialchars($proyecto['supervisor'] ?: '-'); ?></div>
                        </div>
                        <div class="info-row">
                            <div class="info-label">Correo Principal</div>
                            <div class="info-value">
                                <?php if(!empty($proyecto['email_main'])): ?>
                                <a href="mailto:<?php echo htmlspecialchars($proyecto['email_main']); ?>" class="text-primary">
                                    <i class="fas fa-envelope"></i> <?php echo htmlspecialchars($proyecto['email_main']); ?>
                                </a>
                                <?php else: ?>
                                -
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="info-row">
                            <div class="info-label">Correos Secundarios</div>
                            <div class="info-value">
                                <?php if(!empty($proyecto['email_secundarios'])): ?>
                                <?php 
                                $emails = preg_split('/[,;\s]+/', $proyecto['email_secundarios']);
                                foreach ($emails as $email) {
                                    $email = trim($email);
                                    if (!empty($email)) {
                                        echo '<a href="mailto:' . htmlspecialchars($email) . '" class="text-primary d-block mb-1">';
                                        echo '<i class="fas fa-envelope"></i> ' . htmlspecialchars($email) . '</a>';
                                    }
                                }
                                ?>
                                <?php else: ?>
                                -
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-6">
                <!-- Entidades relacionadas -->
                <div class="custom-card mb-4">
                    <div class="custom-card-header">
                        <h6 class="custom-card-title">Entidades Relacionadas</h6>
                    </div>
                    <div class="custom-card-body">
                        <?php if(!empty($entidadesProyecto)): ?>
                        <?php foreach($entidadesProyecto as $entidad): ?>
                        <div class="info-row">
                            <div class="info-label">Entidad Principal</div>
                            <div class="info-value">
                                <?php echo htmlspecialchars($entidad['entidad_descripcion'] ?? $entidad['entidad']); ?>
                                <?php if(!empty($entidad['numero_pro_entidad'])): ?>
                                <div style="margin-top: 3px; font-size: 0.8rem; color: var(--neutral-500);">
                                    Referencia: <?php echo htmlspecialchars($entidad['numero_pro_entidad']); ?>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                        <?php else: ?>
                        <div class="empty-state">
                            <div class="empty-state-icon">
                                <i class="fas fa-building"></i>
                            </div>
                            <div class="empty-state-title">Sin entidades asociadas</div>
                            <div class="empty-state-desc">No hay entidades asociadas a este proyecto.</div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Pestaña de Actas -->
    <div class="tab-content" id="tab-actas">
        <div class="row">
            <div class="col-12">
                <!-- Tabla de actas -->
                <div class="custom-card mb-4">
                    <div class="custom-card-header d-flex justify-content-between align-items-center">
                        <h6 class="custom-card-title">Actas del Proyecto</h6>
                        <a href="main.php?page=agregar_acta&proyecto_id=<?php echo $proyecto_id; ?>" class="action-button btn-success">
                            <i class="fas fa-plus"></i> Nueva Acta
                        </a>
                    </div>
                    <div class="custom-card-body">
                        <?php if(empty($actas)): ?>
                        <div class="empty-state">
                            <div class="empty-state-icon">
                                <i class="fas fa-file-alt"></i>
                            </div>
                            <div class="empty-state-title">Sin actas registradas</div>
                            <div class="empty-state-desc">No hay actas registradas para este proyecto. Puede agregar un acta con el botón superior.</div>
                        </div>
                        <?php else: ?>
                        <div class="table-responsive">
                            <table class="custom-table">
                                <thead>
                                    <tr>
                                        <th width="60">N°</th>
                                        <th>Tipo</th>
                                        <th width="120">Fecha</th>
                                        <th>Observaciones</th>
                                        <th width="180" class="text-center">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach($actas as $acta): 
                                        // Obtener el documento asociado al acta
                                        $documentoActa = obtenerDocumentoActa($proyecto_id, $acta['numero_acta'], $acta['tipo_acta']);
                                        $tieneDocumento = !empty($documentoActa) && !empty($documentoActa['archivo']);
                                        $urlDocumento = $tieneDocumento ? "http://siexud.udistrital.edu.co/idexud/siexud/actasproy/upload/" . $documentoActa['archivo'] : '';
                                    ?>
                                    <tr>
                                        <td class="text-center"><?php echo $acta['numero_acta']; ?></td>
                                        <td><?php echo htmlspecialchars($acta['tipo_descripcion'] ?: 'Tipo '.$acta['tipo_acta']); ?></td>
                                        <td><?php echo formatearFecha($acta['fecha_acta']); ?></td>
                                        <td>
                                            <?php 
                                            $observa = trim($acta['observa'] ?? '');
                                            if (!empty($observa)) {
                                                echo htmlspecialchars(substr($observa, 0, 80) . (strlen($observa) > 80 ? '...' : ''));
                                            } else {
                                                echo '<span style="color: var(--neutral-400);">Sin observaciones</span>';
                                            }
                                            ?>
                                        </td>
                                        <td class="text-center">
                                            <div class="btn-group">
                                                <?php if($tieneDocumento): ?>
                                                <a href="<?php echo htmlspecialchars($urlDocumento); ?>" class="action-button btn-info" target="_blank" title="Ver Acta">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                <a href="<?php echo htmlspecialchars($urlDocumento); ?>" class="action-button btn-primary" download title="Descargar Acta">
                                                    <i class="fas fa-download"></i>
                                                </a>
                                                <?php else: ?>
                                                <button disabled class="action-button btn-secondary" title="Documento no disponible">
                                                    <i class="fas fa-file-alt"></i>
                                                </button>
                                                <?php endif; ?>
                                                <a href="main.php?page=editar_acta&proyecto_id=<?php echo $proyecto_id; ?>&numero_acta=<?php echo $acta['numero_acta']; ?>&tipo_acta=<?php echo $acta['tipo_acta']; ?>" class="action-button btn-warning" title="Editar Acta">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    



<!-- Pestaña de Contratistas -->
<div class="tab-content" id="tab-contratistas">
    <div class="row">
        <div class="col-12">
            <!-- Tabla de contratistas -->
            <div class="custom-card mb-4">
                <div class="custom-card-header d-flex justify-content-between align-items-center">
                    <h6 class="custom-card-title">Contratistas Asociados al Proyecto</h6>
                    <a href="main.php?page=agregar_contratista&proyecto_id=<?php echo $proyecto_id; ?>" class="action-button btn-success">
                        <i class="fas fa-plus"></i> Nuevo Contratista
                    </a>
                </div>
                <div class="custom-card-body">
                    <?php if(empty($contratistas)): ?>
                    <div class="empty-state">
                        <div class="empty-state-icon">
                            <i class="fas fa-users"></i>
                        </div>
                        <div class="empty-state-title">Sin contratistas asociados</div>
                        <div class="empty-state-desc">No hay contratistas asociados a este proyecto. Puede agregar un contratista con el botón superior.</div>
                    </div>
                    <?php else: ?>
                    <div class="table-responsive">
                        <table class="custom-table">
                            <thead>
                                <tr>
                                    <th>Identificación</th>
                                    <th>Nombre</th>
                                    <th>Tipo</th>
                                    <th>Tipo Contrato</th>
                                    <th>Valor</th>
                                    <th>Fechas</th>
                                    <th width="130" class="text-center">Contacto</th>
                                    <th width="130" class="text-center">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($contratistas as $contratista): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($contratista['identificacion']); ?></td>
                                    <td>
                                        <div style="font-weight: 500;">
                                            <?php echo htmlspecialchars($contratista['nombre_completo']); ?>
                                        </div>
                                        <?php if(!empty($contratista['rol_contratista'])): ?>
                                        <div style="font-size: 0.8rem; color: var(--neutral-600); margin-top: 3px;">
                                            <?php echo htmlspecialchars(substr($contratista['rol_contratista'], 0, 50) . (strlen($contratista['rol_contratista']) > 50 ? '...' : '')); ?>
                                        </div>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php 
                                        echo htmlspecialchars($contratista['tipo_persona_desc'] ?: 
                                            ($contratista['tipo_persona'] == 1 ? 'Natural' : 
                                             ($contratista['tipo_persona'] == 2 ? 'Jurídica' : 'Otro'))); 
                                        ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($contratista['tipo_contrato_desc'] ?: '-'); ?></td>
                                    <td>
                                        <?php if(!empty($contratista['valor'])): ?>
                                            <?php echo formatearMoneda($contratista['valor']); ?>
                                        <?php else: ?>
                                            -
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div style="font-size: 0.8rem;">
                                            <div><strong>Inicio:</strong> <?php echo formatearFecha($contratista['fecha_inicio']); ?></div>
                                            <div><strong>Fin:</strong> <?php echo formatearFecha($contratista['fecha_fin']); ?></div>
                                        </div>
                                    </td>
                                    <td class="text-center">
                                        <div class="btn-group">
                                            <?php if(!empty($contratista['correo'])): ?>
                                            <a href="mailto:<?php echo htmlspecialchars($contratista['correo']); ?>" class="action-button btn-info" title="Enviar correo">
                                                <i class="fas fa-envelope"></i>
                                            </a>
                                            <?php endif; ?>
                                            <?php if(!empty($contratista['tel_celular'])): ?>
                                            <a href="tel:<?php echo htmlspecialchars($contratista['tel_celular']); ?>" class="action-button btn-secondary" title="Llamar">
                                                <i class="fas fa-phone"></i>
                                            </a>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td class="text-center">
                                        <div class="btn-group">
                                            <a href="main.php?page=ver_contratista&id=<?php echo $contratista['identificacion']; ?>" class="action-button btn-info" title="Ver contratista">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <a href="main.php?page=editar_contratista&id=<?php echo $contratista['identificacion']; ?>&proyecto_id=<?php echo $proyecto_id; ?>" class="action-button btn-warning" title="Editar contratista">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>






    <!-- Pestaña de Actividad Reciente -->
    <div class="tab-content" id="tab-actividad">
        <div class="row">
            <div class="col-lg-8">
                <!-- Actividad reciente -->
                <div class="custom-card mb-4">
                    <div class="custom-card-header">
                        <h6 class="custom-card-title">Registro de Actividad</h6>
                    </div>
                    <div class="custom-card-body">
                        <div class="timeline">
                            <?php foreach($ultimasActividades as $actividad): ?>
                            <div class="timeline-item">
                                <div class="timeline-dot"></div>
                                <div class="timeline-content">
                                    <div class="timeline-date">
                                        <?php echo date('d/m/Y H:i', strtotime($actividad['fecha'])); ?>
                                    </div>
                                    <div class="timeline-title">
                                        <?php echo htmlspecialchars($actividad['tipo']); ?>
                                    </div>
                                    <div class="timeline-desc">
                                        <?php echo htmlspecialchars($actividad['descripcion']); ?>
                                    </div>
                                    <div class="timeline-usuario">
                                        Por: <?php echo htmlspecialchars($actividad['usuario']); ?>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-4">
                <!-- Información adicional -->
                <div class="custom-card mb-4">
                    <div class="custom-card-header">
                        <h6 class="custom-card-title">Información Adicional</h6>
                    </div>
                    <div class="custom-card-body">
                        <div class="info-row">
                            <div class="info-label">Acto Administrativo</div>
                            <div class="info-value"><?php echo htmlspecialchars($proyecto['acto_administrativo'] ?: '-'); ?></div>
                        </div>
                        <div class="info-row">
                            <div class="info-label">Enlace SECOP</div>
                            <div class="info-value">
                                <?php if(!empty($proyecto['enlace_secop'])): ?>
                                <a href="<?php echo htmlspecialchars($proyecto['enlace_secop']); ?>" target="_blank" class="text-primary">
                                    <i class="fas fa-external-link-alt"></i> Ver en SECOP
                                </a>
                                <?php else: ?>
                                -
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="info-row">
                            <div class="info-label">Usuario Creador</div>
                            <div class="info-value"><?php echo htmlspecialchars($proyecto['usuario'] ?: '-'); ?></div>
                        </div>
                        <div class="info-row">
                            <div class="info-label">Fecha de Registro</div>
                            <div class="info-value">
                                <?php 
                                if (!empty($proyecto['fecha_registro'])) {
                                    echo formatearFecha($proyecto['fecha_registro']);
                                } else {
                                    echo '-';
                                }
                                ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Script para manejar las pestañas y otras funcionalidades -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Manejo de pestañas
    const tabs = document.querySelectorAll('.custom-tab');
    const tabContents = document.querySelectorAll('.tab-content');
    
    tabs.forEach(tab => {
        tab.addEventListener('click', function() {
            const tabId = this.getAttribute('data-tab');
            
            // Quitar active de todas las pestañas
            tabs.forEach(t => t.classList.remove('active'));
            
            // Agregar active a la pestaña seleccionada
            this.classList.add('active');
            
            // Ocultar todos los contenidos de pestañas
            tabContents.forEach(content => {
                content.classList.remove('active');
            });
            
            // Mostrar el contenido de la pestaña seleccionada
            document.getElementById('tab-' + tabId).classList.add('active');
            
            // Actualizar URL con hash para mantener la pestaña al recargar
            window.location.hash = tabId;
        });
    });
    
    // Comprobar si hay un hash en la URL para activar la pestaña correspondiente
    if (window.location.hash) {
        const tabId = window.location.hash.substring(1);
        const tab = document.querySelector(`.custom-tab[data-tab="${tabId}"]`);
        if (tab) {
            tab.click();
        }
    }
    
    // Crear gráfico de distribución financiera
    const valorTotal = <?php echo $proyecto['valor'] ?: 0; ?>;
    const aporteUD = <?php echo $proyecto['aporte_ud'] ?: 0; ?>;
    const aporteEntidad = valorTotal - aporteUD;
    
    const ctx = document.getElementById('financeChart');
    if (ctx) {
        new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: ['Aporte UD', 'Aporte Entidad'],
                datasets: [{
                    data: [aporteUD, aporteEntidad],
                    backgroundColor: ['rgba(99, 102, 241, 0.8)', 'rgba(16, 185, 129, 0.8)'],
                    borderColor: ['#6366F1', '#10B981'],
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '65%',
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            font: {
                                size: 12
                            },
                            padding: 15
                        }
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                const value = context.raw;
                                const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                const percentage = Math.round((value / total) * 100);
                                return `${context.label}: ${value.toLocaleString()} (${percentage}%)`;
                            }
                        }
                    }
                }
            }
        });
    }
});

// Función para imprimir el proyecto
function imprimirProyecto() {
    // Preparar contenido para imprimir
    window.print();
}

// Función para exportar el proyecto
function exportarProyecto(id) {
    alert('La funcionalidad de exportación se implementaría aquí.');
    // Aquí iría la lógica para exportar el proyecto a PDF, Excel, etc.
}
</script>

<style media="print">
body {
    font-family: Arial, sans-serif;
    color: black;
    background-color: white;
}

.container-fluid {
    width: 100%;
    padding: 0;
}

.back-button,
.custom-tabs,
.action-button,
.btn-group {
    display: none !important;
}

.project-header {
    border: none;
    box-shadow: none;
    page-break-inside: avoid;
    border-bottom: 1px solid #ccc;
    padding-bottom: 1.5rem;
}

.custom-card {
    border: none;
    box-shadow: none;
    margin-bottom: 1.5rem;
    page-break-inside: avoid;
}

.custom-card-header {
    background-color: white;
    border-bottom: 1px solid #ccc;
}

.tab-content {
    display: block !important;
    margin-bottom: 2rem;
    page-break-before: always;
}

.tab-content:first-of-type {
    page-break-before: auto;
}

.tab-content h4 {
    font-size: 16pt;
    margin-top: 2rem;
    margin-bottom: 1rem;
    border-bottom: 1px solid #ccc;
    padding-bottom: 0.5rem;
}

.tab-content h5 {
    font-size: 14pt;
}

.info-section-title:before {
    content: "► ";
}

.object-box {
    border: 1px solid #ccc;
    padding: 0.5rem;
    margin-bottom: 1rem;
}

.value-cards {
    display: flex;
    flex-wrap: wrap;
    gap: 1rem;
}

.value-card {
    border: 1px solid #ccc;
    flex: 1;
    min-width: 150px;
}

table.custom-table {
    border-collapse: collapse;
    width: 100%;
    margin-bottom: 1rem;
}

table.custom-table th,
table.custom-table td {
    border: 1px solid #ccc;
    padding: 0.5rem;
    text-align: left;
}

table.custom-table th {
    background-color: #f5f5f5;
}

.timeline::before {
    display: none;
}

.timeline-item {
    border-left: 1px solid #ccc;
    margin-left: 0;
    padding-left: 1rem;
}

.timeline-dot {
    display: none;
}

@page {
    size: A4;
    margin: 2cm;
}
</style>