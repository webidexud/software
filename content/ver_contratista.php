<?php
/**
 * Página para visualizar detalles de un contratista específico
 * Muestra información personal, contratos y actas relacionadas
 */

// Verificar que se han proporcionado los parámetros necesarios
if (!isset($_GET['id']) || !is_numeric($_GET['id']) || !isset($_GET['proyecto_id']) || !is_numeric($_GET['proyecto_id'])) {
    // Redirigir a la lista de proyectos si no se proporcionan IDs válidos
    header('Location: main.php?page=proyecto');
    exit;
}

$contratista_id = $_GET['id'];
$proyecto_id = $_GET['proyecto_id'];
$contrato_id = isset($_GET['contrato_id']) ? $_GET['contrato_id'] : null;

// Incluir el modelo de contratista si existe
if (file_exists('models/contratista_model.php')) {
    include_once 'models/contratista_model.php';
} else {
    echo '<div class="alert alert-danger">Error: No se puede encontrar el modelo de contratistas.</div>';
    exit;
}

// Incluir el modelo de proyecto para usar funciones compartidas
if (file_exists('models/proyecto_model.php')) {
    include_once 'models/proyecto_model.php';
} else {
    echo '<div class="alert alert-danger">Error: No se puede encontrar el modelo de proyectos.</div>';
    exit;
}

// Obtener datos del contratista
$contratista = obtenerDetalleContratista($contratista_id);

// Si no se encuentra el contratista, mostrar mensaje de error
if (!$contratista) {
    echo '<div class="alert alert-danger" role="alert">El contratista solicitado no existe o no se puede acceder a él en este momento.</div>';
    exit;
}

// Obtener contratos del contratista en el proyecto
$contratos = obtenerContratosContratista($contratista_id, $proyecto_id);

// Filtrar para mostrar solo el contrato específico si se proporcionó
if (!empty($contrato_id) && !empty($contratos)) {
    $contrato_seleccionado = null;
    foreach ($contratos as $contrato) {
        if ($contrato['numero_contrato'] == $contrato_id) {
            $contrato_seleccionado = $contrato;
            break;
        }
    }
    
    // Si se encontró el contrato específico, actualizar la variable para mostrar solo ese
    if ($contrato_seleccionado) {
        $contratos = [$contrato_seleccionado];
    }
}

// Obtener actas relacionadas con el contratista en el proyecto y contrato específico
$actas = obtenerActasContratistaProyecto($contratista_id, $proyecto_id, $contrato_id);

// Si no hay actas relacionadas directamente, intentar obtener todas las actas del proyecto
if (empty($actas) && function_exists('obtenerActasProyecto') && empty($contrato_id)) {
    $actas = obtenerActasProyecto($proyecto_id);
    
    // Si hay muchas actas, limitamos a las más recientes para no saturar la vista
    if (count($actas) > 5) {
        $actas = array_slice($actas, 0, 5);
    }
}

// Si aún no hay actas disponibles y el modelo de actas no está cargado, intentar cargarlo
if (empty($actas) && file_exists('models/actas_proyecto_model.php') && empty($contrato_id)) {
    include_once 'models/actas_proyecto_model.php';
    
    // Verificar si ahora está disponible la función para obtener actas
    if (function_exists('obtenerActasProyecto')) {
        $actas = obtenerActasProyecto($proyecto_id);
        
        // Limitar a las 5 más recientes
        if (count($actas) > 5) {
            $actas = array_slice($actas, 0, 5);
        }
    }
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

// Obtener los datos del proyecto
$proyecto = obtenerProyectoDetalle($proyecto_id);
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
    margin-bottom: 1.5rem;
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
    display: flex;
    align-items: center;
}

.custom-card-title i {
    margin-right: 0.75rem;
    color: var(--primary);
}

.custom-card-body {
    padding: 1.5rem;
}

/* Estilos para el encabezado del contratista */
.contratista-header {
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

.contratista-header::before {
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

.contratista-avatar {
    width: 80px;
    height: 80px;
    background-color: var(--primary-light);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: var(--primary-dark);
    font-size: 2rem;
    font-weight: 600;
    margin-bottom: 1rem;
}

.contratista-name {
    color: var(--neutral-900);
    font-weight: 700;
    margin-bottom: 0.5rem;
    position: relative;
    z-index: 1;
}

.contratista-info {
    display: flex;
    flex-wrap: wrap;
    gap: 1rem;
    margin-top: 1rem;
}

.info-item {
    display: flex;
    align-items: center;
    color: var(--neutral-600);
    font-size: 0.875rem;
}

.info-item i {
    margin-right: 0.5rem;
    color: var(--primary);
}

.tipo-badge {
    display: inline-flex;
    align-items: center;
    padding: 0.35rem 0.75rem;
    border-radius: var(--radius);
    font-weight: 600;
    font-size: 0.75rem;
    text-transform: uppercase;
    letter-spacing: 0.025em;
    background-color: var(--primary-light);
    color: var(--primary-dark);
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

/* Estilos para detalles del contratista */
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

.proyecto-box {
    background-color: var(--neutral-50);
    border-radius: var(--radius);
    padding: 1rem;
    border: 1px solid var(--neutral-200);
    font-size: 0.875rem;
    color: var(--neutral-700);
    line-height: 1.6;
    margin-bottom: 1.5rem;
}

/* Estilos para tabla de contratos */
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

.action-button.btn-view {
    background-color: var(--info-light);
    color: var(--info);
}

.action-button.btn-view:hover {
    background-color: var(--info);
    color: white;
}

.action-button.btn-download {
    background-color: var(--success-light);
    color: var(--success);
}

.action-button.btn-download:hover {
    background-color: var(--success);
    color: white;
}

.btn-group {
    display: flex;
    flex-wrap: wrap;
    gap: 0.5rem;
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

/* Estado del contrato */
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

.status-activo {
    background-color: var(--success-light);
    color: var(--success);
}

.status-finalizado {
    background-color: var(--neutral-200);
    color: var(--neutral-600);
}

.status-suspendido {
    background-color: var(--warning-light);
    color: var(--warning);
}

/* Responsive */
@media (max-width: 768px) {
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
    
    .contratista-header {
        padding: 1.25rem;
    }
    
    .custom-tab {
        padding: 0.75rem 1rem;
    }
}

@media (max-width: 576px) {
    .contratista-info {
        flex-direction: column;
        gap: 0.5rem;
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
    <a href="main.php?page=proyecto_individual&id=<?php echo $proyecto_id; ?>" class="back-button">
        <i class="fas fa-arrow-left"></i> Volver al Proyecto
    </a>
    
    <!-- Encabezado del Contratista -->
<div class="contratista-header">
    <div class="row">
        <div class="col-md-8">
            <div class="d-flex align-items-center mb-3">
                <div class="contratista-avatar">
                    <i class="fas fa-user"></i>
                </div>
                <div class="ms-3">
                    <h4 class="contratista-name"><?php echo htmlspecialchars($contratista['nombre_completo']); ?></h4>
                    <div class="d-flex align-items-center">
                        <?php 
                        $tipo_contratista = '';
                        if (!empty($contratista['tipo_persona_desc'])) {
                            $tipo_contratista = $contratista['tipo_persona_desc'];
                        } else if ($contratista['tipo_persona'] == 1) {
                            $tipo_contratista = 'Persona Natural';
                        } else if ($contratista['tipo_persona'] == 2) {
                            $tipo_contratista = 'Persona Jurídica';
                        } else {
                            $tipo_contratista = 'Otro';
                        }
                        ?>
                        <span class="tipo-badge"><?php echo htmlspecialchars($tipo_contratista); ?></span>
                        <span class="ms-2 text-muted"><?php echo htmlspecialchars($contratista['identificacion']); ?></span>
                        
                        <?php if (!empty($contrato_id)): ?>
                        <span class="ms-3 badge bg-primary">Contrato N° <?php echo htmlspecialchars($contrato_id); ?></span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <div class="contratista-info">
                <?php if(!empty($contratista['correo'])): ?>
                <div class="info-item">
                    <i class="fas fa-envelope"></i> <?php echo htmlspecialchars($contratista['correo']); ?>
                </div>
                <?php endif; ?>
                
                <?php if(!empty($contratista['tel_celular'])): ?>
                <div class="info-item">
                    <i class="fas fa-mobile-alt"></i> <?php echo htmlspecialchars($contratista['tel_celular']); ?>
                </div>
                <?php endif; ?>
                
                <?php if(!empty($contratista['tel_fijo'])): ?>
                <div class="info-item">
                    <i class="fas fa-phone"></i> <?php echo htmlspecialchars($contratista['tel_fijo']); ?>
                </div>
                <?php endif; ?>
                
                <?php if(!empty($contratista['direccion'])): ?>
                <div class="info-item">
                    <i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($contratista['direccion']); ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
        <div class="col-md-4 mt-3 mt-md-0">
            <div class="proyecto-box">
                <strong>Proyecto:</strong> <?php echo htmlspecialchars($proyecto['nombre']); ?>
                <div class="mt-2">
                    <small class="text-muted">Código: <?php echo htmlspecialchars($proyecto['numero_pro_entidad']); ?></small>
                </div>
                
                <?php if (!empty($contrato_id) && !empty($contratos) && count($contratos) === 1): ?>
                <hr>
                <div>
                    <strong>Valor del Contrato:</strong> <?php echo formatearMoneda($contratos[0]['valor']); ?>
                </div>
                <div>
                    <strong>Tipo:</strong> <?php echo htmlspecialchars($contratos[0]['tipo_contrato_desc'] ?? 'No especificado'); ?>
                </div>
                <div class="mt-2">
                    <span class="badge bg-info">
                        <?php echo formatearFecha($contratos[0]['fecha_inicio']); ?> a <?php echo formatearFecha($contratos[0]['fecha_terminacion']); ?>
                    </span>
                </div>
                <?php endif; ?>
            </div>
            
            <div class="d-flex justify-content-end mt-2">
                <a href="main.php?page=editar_contratista&id=<?php echo $contratista_id; ?>&proyecto_id=<?php echo $proyecto_id; ?><?php echo !empty($contrato_id) ? '&contrato_id='.$contrato_id : ''; ?>" class="action-button btn-primary">
                    <i class="fas fa-edit"></i> Editar Contratista
                </a>
            </div>
        </div>
    </div>
</div>
    
    <!-- Navegación por pestañas personalizada -->
    <div class="custom-tabs" id="contratistaTabs">
        <div class="custom-tab active" data-tab="info">
            <i class="fas fa-info-circle"></i> Información General
        </div>
        <div class="custom-tab" data-tab="contratos">
            <i class="fas fa-file-contract"></i> Contratos
            <?php if(!empty($contratos)): ?>
            <span class="badge bg-primary rounded-pill ms-1"><?php echo count($contratos); ?></span>
            <?php endif; ?>
        </div>
        <div class="custom-tab" data-tab="actas">
            <i class="fas fa-file-signature"></i> Actas Relacionadas
            <?php if(!empty($actas)): ?>
            <span class="badge bg-primary rounded-pill ms-1"><?php echo count($actas); ?></span>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Contenido de las pestañas -->
    <!-- Pestaña de Información General -->
    <div class="tab-content active" id="tab-info">
        <div class="row">
            <div class="col-lg-6">
                <!-- Información personal -->
                <div class="custom-card">
                    <div class="custom-card-header">
                        <h6 class="custom-card-title">
                            <i class="fas fa-user-circle"></i> Información Personal
                        </h6>
                    </div>
                    <div class="custom-card-body">
                        <div class="info-row">
                            <div class="info-label">Identificación</div>
                            <div class="info-value"><?php echo htmlspecialchars($contratista['identificacion']); ?></div>
                        </div>
                        
                        <?php if($contratista['tipo_persona'] == 1): // Persona Natural ?>
                        <div class="info-row">
                            <div class="info-label">Primer Nombre</div>
                            <div class="info-value"><?php echo htmlspecialchars($contratista['nombre1']); ?></div>
                        </div>
                        
                        <?php if(!empty($contratista['nombre2'])): ?>
                        <div class="info-row">
                            <div class="info-label">Segundo Nombre</div>
                            <div class="info-value"><?php echo htmlspecialchars($contratista['nombre2']); ?></div>
                        </div>
                        <?php endif; ?>
                        
                        <div class="info-row">
                            <div class="info-label">Primer Apellido</div>
                            <div class="info-value"><?php echo htmlspecialchars($contratista['apellido1']); ?></div>
                        </div>
                        
                        <?php if(!empty($contratista['apellido2'])): ?>
                        <div class="info-row">
                            <div class="info-label">Segundo Apellido</div>
                            <div class="info-value"><?php echo htmlspecialchars($contratista['apellido2']); ?></div>
                        </div>
                        <?php endif; ?>
                        
                        <?php else: // Persona Jurídica ?>
                        <div class="info-row">
                            <div class="info-label">Razón Social</div>
                            <div class="info-value"><?php echo htmlspecialchars($contratista['nombre1']); ?></div>
                        </div>
                        <?php endif; ?>
                        
                        <div class="info-row">
                            <div class="info-label">Tipo de Persona</div>
                            <div class="info-value"><?php echo htmlspecialchars($tipo_contratista); ?></div>
                        </div>
                        
                        <?php if(!empty($contratista['fecha_nacimiento'])): ?>
                        <div class="info-row">
                            <div class="info-label">Fecha de Nacimiento</div>
                            <div class="info-value"><?php echo formatearFecha($contratista['fecha_nacimiento']); ?></div>
                        </div>
                        <?php endif; ?>
                        
                        <?php if(!empty($contratista['genero'])): ?>
                        <div class="info-row">
                            <div class="info-label">Género</div>
                            <div class="info-value">
                                <?php echo $contratista['genero'] == 'M' ? 'Masculino' : ($contratista['genero'] == 'F' ? 'Femenino' : 'Otro'); ?>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-6">
                <!-- Información de contacto -->
                <div class="custom-card">
                    <div class="custom-card-header">
                        <h6 class="custom-card-title">
                            <i class="fas fa-address-book"></i> Información de Contacto
                        </h6>
                    </div>
                    <div class="custom-card-body">
                        <div class="info-row">
                            <div class="info-label">Correo Electrónico</div>
                            <div class="info-value">
                                <?php if(!empty($contratista['correo'])): ?>
                                <a href="mailto:<?php echo htmlspecialchars($contratista['correo']); ?>">
                                    <?php echo htmlspecialchars($contratista['correo']); ?>
                                </a>
                                                                <?php else: ?>
                                <span class="text-muted">No especificado</span>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <div class="info-row">
                            <div class="info-label">Teléfono Fijo</div>
                            <div class="info-value">
                                <?php if(!empty($contratista['tel_fijo'])): ?>
                                <a href="tel:<?php echo htmlspecialchars($contratista['tel_fijo']); ?>">
                                    <?php echo htmlspecialchars($contratista['tel_fijo']); ?>
                                </a>
                                <?php else: ?>
                                <span class="text-muted">No especificado</span>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <div class="info-row">
                            <div class="info-label">Dirección</div>
                            <div class="info-value">
                                <?php if(!empty($contratista['direccion'])): ?>
                                <?php echo htmlspecialchars($contratista['direccion']); ?>
                                <?php else: ?>
                                <span class="text-muted">No especificada</span>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <div class="info-row">
                            <div class="info-label">Estado</div>
                            <div class="info-value">
                                <?php if($contratista['estado'] == 'A'): ?>
                                <span class="status-badge status-activo">Activo</span>
                                <?php else: ?>
                                <span class="status-badge status-finalizado">Inactivo</span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Acciones rápidas -->
                <div class="custom-card">
                    <div class="custom-card-header">
                        <h6 class="custom-card-title">
                            <i class="fas fa-bolt"></i> Acciones Rápidas
                        </h6>
                    </div>
                    <div class="custom-card-body">
                        <div class="btn-group">
                            <a href="mailto:<?php echo htmlspecialchars($contratista['correo'] ?? ''); ?>" class="action-button btn-primary" <?php echo empty($contratista['correo']) ? 'disabled' : ''; ?>>
                                <i class="fas fa-envelope"></i> Enviar Correo
                            </a>
                            <a href="main.php?page=agregar_contrato&contratista_id=<?php echo $contratista_id; ?>&proyecto_id=<?php echo $proyecto_id; ?>" class="action-button btn-success">
                                <i class="fas fa-plus"></i> Nuevo Contrato
                            </a>
                            <button type="button" class="action-button btn-info" onclick="imprimirContratista()">
                                <i class="fas fa-print"></i> Imprimir
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Pestaña de Contratos -->
<div class="tab-content" id="tab-contratos">
    <div class="row">
        <div class="col-12">
            <!-- Tabla de contratos -->
            <div class="custom-card">
                <div class="custom-card-header d-flex justify-content-between align-items-center">
                    <h6 class="custom-card-title">
                        <i class="fas fa-file-contract"></i> Contratos Asociados
                    </h6>
                    
                    <!-- Agregar un debug counter visible para desarrollo -->
                    <?php if(!empty($contratos)): ?>
                    <span class="badge bg-secondary">Encontrados: <?php echo count($contratos); ?></span>
                    <?php endif; ?>
                    
                    <a href="main.php?page=agregar_contrato&contratista_id=<?php echo $contratista_id; ?>&proyecto_id=<?php echo $proyecto_id; ?>" class="action-button btn-success">
                        <i class="fas fa-plus"></i> Nuevo Contrato
                    </a>
                </div>
                <div class="custom-card-body">
                    <?php if(empty($contratos)): ?>
                    <div class="empty-state">
                        <div class="empty-state-icon">
                            <i class="fas fa-file-contract"></i>
                        </div>
                        <div class="empty-state-title">Sin contratos registrados</div>
                        <div class="empty-state-desc">
                            <p>No hay contratos asociados a este contratista en el proyecto actual.</p>
                            <div class="mt-3">
                                <a href="main.php?page=agregar_contrato&contratista_id=<?php echo $contratista_id; ?>&proyecto_id=<?php echo $proyecto_id; ?>" class="btn btn-sm btn-primary">
                                    <i class="fas fa-plus me-1"></i> Crear nuevo contrato
                                </a>
                            </div>
                        </div>
                    </div>
                    <?php else: ?>
                    <div class="table-responsive">
                        <table class="custom-table">
                            <thead>
                                <tr>
                                    <th width="80">Número</th>
                                    <th>Tipo Contrato</th>
                                    <th>Objeto</th>
                                    <th width="120">Valor</th>
                                    <th width="140">Fechas</th>
                                    <th width="100">Estado</th>
                                    <th width="150" class="text-center">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($contratos as $contrato): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($contrato['numero_contrato']); ?></td>
                                    <td>
                                        <?php 
                                        echo htmlspecialchars($contrato['tipo_contrato_desc'] ?? 
                                            ('Tipo ' . $contrato['tipo_contrato'])); 
                                        ?>
                                    </td>
                                    <td>
                                        <?php 
                                        if (!empty($contrato['objeto'])) {
                                            // Si es muy largo, mostrar solo un fragmento con tooltip
                                            if (strlen($contrato['objeto']) > 80) {
                                                $objetoCorto = substr($contrato['objeto'], 0, 77) . '...';
                                                echo '<div title="' . htmlspecialchars($contrato['objeto']) . '">' . 
                                                     htmlspecialchars($objetoCorto) . '</div>';
                                            } else {
                                                echo htmlspecialchars($contrato['objeto']);
                                            }
                                        } else {
                                            echo '<span class="text-muted">Sin objeto definido</span>';
                                        }
                                        ?>
                                    </td>
                                    <td><?php echo formatearMoneda($contrato['valor']); ?></td>
                                    <td>
                                        <div style="font-size: 0.8rem;">
                                            <div><strong>Inicio:</strong> <?php echo formatearFecha($contrato['fecha_inicio']); ?></div>
                                            <div><strong>Fin:</strong> <?php echo formatearFecha($contrato['fecha_terminacion']); ?></div>
                                            
                                            <?php
                                            // Calcular duración en días
                                            if (!empty($contrato['fecha_inicio']) && !empty($contrato['fecha_terminacion'])) {
                                                $inicio = new DateTime($contrato['fecha_inicio']);
                                                $fin = new DateTime($contrato['fecha_terminacion']);
                                                $duracion = $inicio->diff($fin)->days;
                                                
                                                echo '<div class="mt-1"><span class="badge bg-light text-dark">' . 
                                                     $duracion . ' días</span></div>';
                                            }
                                            ?>
                                        </div>
                                    </td>
                                    <td>
                                        <?php 
                                        $situacion_contrato = $contrato['situacion_contrato'] ?? null;
                                        if ($situacion_contrato) {
                                            // Verificar si existe una descripción para la situación
                                            if (!empty($contrato['situacion_desc'])) {
                                                $situacion_text = $contrato['situacion_desc'];
                                            } else {
                                                // Asignar un texto según el código
                                                switch ($situacion_contrato) {
                                                    case 1:
                                                        $situacion_text = 'Activo';
                                                        $status_class = 'status-activo';
                                                        break;
                                                    case 2:
                                                        $situacion_text = 'Finalizado';
                                                        $status_class = 'status-finalizado';
                                                        break;
                                                    case 3:
                                                        $situacion_text = 'Suspendido';
                                                        $status_class = 'status-suspendido';
                                                        break;
                                                    default:
                                                        $situacion_text = 'Situación ' . $situacion_contrato;
                                                        $status_class = '';
                                                }
                                            }
                                        } else {
                                            // Si no hay situación, determinar por fechas
                                            $hoy = new DateTime();
                                            $fecha_fin = !empty($contrato['fecha_terminacion']) ? 
                                                new DateTime($contrato['fecha_terminacion']) : null;
                                                
                                            if ($fecha_fin && $hoy > $fecha_fin) {
                                                $situacion_text = 'Finalizado';
                                                $status_class = 'status-finalizado';
                                            } else {
                                                $situacion_text = 'Activo';
                                                $status_class = 'status-activo';
                                            }
                                        }
                                        ?>
                                        <span class="status-badge <?php echo $status_class; ?>"><?php echo $situacion_text; ?></span>
                                    </td>
                                    <td class="text-center">
                                        <div class="btn-group">
                                            <a href="main.php?page=ver_contrato&id=<?php echo $contrato['numero_contrato']; ?>&contratista_id=<?php echo $contratista_id; ?>&proyecto_id=<?php echo $proyecto_id; ?>" class="action-button btn-view" title="Ver contrato">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <a href="main.php?page=editar_contrato&id=<?php echo $contrato['numero_contrato']; ?>&contratista_id=<?php echo $contratista_id; ?>&proyecto_id=<?php echo $proyecto_id; ?>" class="action-button btn-info" title="Editar contrato">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <?php if(!empty($contrato['documento_url'])): ?>
                                            <a href="<?php echo htmlspecialchars($contrato['documento_url']); ?>" class="action-button btn-download" target="_blank" title="Descargar contrato">
                                                <i class="fas fa-download"></i>
                                            </a>
                                            <?php endif; ?>
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
    
   <!-- Pestaña de Actas Relacionadas -->
<div class="tab-content" id="tab-actas">
    <div class="row">
        <div class="col-12">
            <!-- Tabla de actas -->
            <div class="custom-card">
                <div class="custom-card-header d-flex justify-content-between align-items-center">
                    <h6 class="custom-card-title">
                        <i class="fas fa-file-signature"></i> 
                        <?php if (!empty($contrato_id)): ?>
                            Actas y Documentos del Contrato N° <?php echo htmlspecialchars($contrato_id); ?>
                        <?php else: ?>
                            Actas y Documentos del Contratista
                        <?php endif; ?>
                    </h6>
                    
                    <!-- Agregar un contador de actas encontradas -->
                    <?php if(!empty($actas)): ?>
                    <span class="badge bg-secondary">Encontradas: <?php echo count($actas); ?></span>
                    <?php endif; ?>
                    
                    <a href="main.php?page=agregar_acta_contratista&proyecto_id=<?php echo $proyecto_id; ?>&id=<?php echo $contratista_id; ?><?php echo !empty($contrato_id) ? '&contrato_id='.$contrato_id : ''; ?>" class="action-button btn-success">
                        <i class="fas fa-plus"></i> Nueva Acta
                    </a>
                </div>
                <div class="custom-card-body">
                    <?php if(empty($actas)): ?>
                    <div class="empty-state">
                        <div class="empty-state-icon">
                            <i class="fas fa-file-alt"></i>
                        </div>
                        <div class="empty-state-title">Sin actas relacionadas</div>
                        <div class="empty-state-desc">
                            <?php if (!empty($contrato_id)): ?>
                                No se encontraron actas relacionadas con este contrato.
                            <?php else: ?>
                                No se encontraron actas relacionadas con este contratista.
                            <?php endif; ?>
                            
                            <div class="mt-3">
                                <a href="main.php?page=agregar_acta_contratista&proyecto_id=<?php echo $proyecto_id; ?>&id=<?php echo $contratista_id; ?><?php echo !empty($contrato_id) ? '&contrato_id='.$contrato_id : ''; ?>" class="btn btn-sm btn-primary">
                                    <i class="fas fa-plus me-1"></i> Agregar acta<?php echo !empty($contrato_id) ? ' para este contrato' : ''; ?>
                                </a>
                            </div>
                        </div>
                    </div>
                    <?php else: ?>
                    <div class="table-responsive">
                        <table class="custom-table">
                            <thead>
                                <tr>
                                    <th width="60">N°</th>
                                    <th>Tipo</th>
                                    <th>Contrato</th>
                                    <th width="120">Fecha</th>
                                    <th>Observaciones</th>
                                    <th width="150" class="text-center">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($actas as $acta): ?>
                                <tr>
                                    <td class="text-center"><?php echo $acta['numero_acta']; ?></td>
                                    <td>
                                        <?php 
                                        // Asegurar que tenemos una descripción del tipo de acta
                                        $tipoActaDesc = !empty($acta['tipo_descripcion']) ? 
                                            $acta['tipo_descripcion'] : 'Tipo ' . $acta['tipo_acta'];
                                        echo htmlspecialchars($tipoActaDesc); 
                                        ?>
                                    </td>
                                    <td>
                                        <span class="badge bg-primary"><?php echo $acta['numero_contrato']; ?></span>
                                    </td>
                                    <td><?php echo formatearFecha($acta['fecha_acta']); ?></td>
                                    <td>
                                        <?php 
                                        // Formatear las observaciones
                                        $observa = trim($acta['observa'] ?? '');
                                        if (!empty($observa)) {
                                            // Si es muy larga, mostrar solo un fragmento
                                            if (strlen($observa) > 80) {
                                                $observaCorta = substr($observa, 0, 80) . '...';
                                                echo '<div title="' . htmlspecialchars($observa) . '">' . 
                                                     htmlspecialchars($observaCorta) . '</div>';
                                            } else {
                                                echo htmlspecialchars($observa);
                                            }
                                        } else {
                                            echo '<span class="text-muted">Sin observaciones</span>';
                                        }
                                        ?>
                                    </td>
                                    <td class="text-center">
                                        <div class="btn-group">
                                            <?php if(!empty($acta['archivo'])): ?>
                                            <?php 
                                            $urlActa = !empty($acta['archivo_url']) ? 
                                                $acta['archivo_url'] : 
                                                "http://siexud.udistrital.edu.co/idexud/siexud/actascont/upload/" . $acta['archivo'];
                                            ?>
                                            <a href="<?php echo htmlspecialchars($urlActa); ?>" class="action-button btn-view" target="_blank" title="Ver Acta">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <a href="<?php echo htmlspecialchars($urlActa); ?>" class="action-button btn-download" download title="Descargar Acta">
                                                <i class="fas fa-download"></i>
                                            </a>
                                            <?php else: ?>
                                            <a href="main.php?page=editar_acta_contratista&proyecto_id=<?php echo $proyecto_id; ?>&contratista_id=<?php echo $contratista_id; ?>&numero_contrato=<?php echo $acta['numero_contrato']; ?>&numero_acta=<?php echo $acta['numero_acta']; ?>&tipo_acta=<?php echo $acta['tipo_acta']; ?>" class="action-button btn-warning" title="Editar Acta">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <button disabled class="action-button btn-secondary" title="Documento no disponible">
                                                <i class="fas fa-file-alt"></i>
                                            </button>
                                            <?php endif; ?>
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
});

// Función para imprimir los detalles del contratista
function imprimirContratista() {
    window.print();
}
</script>
