<?php
/**
 * Página para visualizar los detalles de un contratista y sus actas asociadas a un proyecto
 */

// Verificar que se hayan proporcionado los IDs necesarios
if (!isset($_GET['id']) || !is_numeric($_GET['id']) || !isset($_GET['proyecto_id']) || !is_numeric($_GET['proyecto_id'])) {
    // Redirigir a la lista de proyectos si no se proporcionan IDs válidos
    header('Location: main.php?page=proyecto');
    exit;
}

$contratista_id = $_GET['id'];
$proyecto_id = intval($_GET['proyecto_id']);
$numero_contrato = isset($_GET['contrato']) ? intval($_GET['contrato']) : null;

// Incluir el modelo de contratista
if (file_exists('models/contratista_model.php')) {
    include_once 'models/contratista_model.php';
} else {
    echo '<div class="alert alert-danger">Error: No se puede encontrar el modelo de contratistas.</div>';
    exit;
}

// Obtener datos del contratista
$contratista = obtenerContratistaPorId($contratista_id);

// Si no se encuentra el contratista, mostrar mensaje de error
if (!$contratista) {
    echo '<div class="alert alert-danger" role="alert">El contratista solicitado no existe o no se puede acceder a él en este momento.</div>';
    exit;
}

// Obtener datos del proyecto
$proyecto = obtenerProyectoResumido($proyecto_id);

// Si no se encuentra el proyecto, mostrar mensaje de error
if (!$proyecto) {
    echo '<div class="alert alert-danger" role="alert">El proyecto solicitado no existe o no se puede acceder a él en este momento.</div>';
    exit;
}

// Obtener contratos del contratista en este proyecto
$contratos = obtenerContratosContratista($contratista_id, $proyecto_id);

// Si hay un contrato específico y está proporcionado en la URL, filtrar
$contrato_actual = null;
if ($numero_contrato !== null) {
    foreach ($contratos as $contrato) {
        if ($contrato['numero_contrato'] == $numero_contrato) {
            $contrato_actual = $contrato;
            break;
        }
    }
}

// Si no se especificó un contrato o no se encontró, usar el primero
if ($contrato_actual === null && !empty($contratos)) {
    $contrato_actual = $contratos[0];
    $numero_contrato = $contrato_actual['numero_contrato'];
}

// Obtener las actas del contratista en este proyecto y contrato
$actas = obtenerActasContratista($contratista_id, $proyecto_id, $numero_contrato);

// Obtener los documentos del contratista en este proyecto y contrato
$documentos = obtenerDocumentosContratista($contratista_id, $proyecto_id, $numero_contrato);

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
    if (!$valor) return "$0";
    
    return '$' . number_format($valor, 0, '.', ',');
}
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

.contratista-header {
    background: linear-gradient(to right, #667eea, #764ba2);
    border-radius: var(--radius-lg);
    color: white;
    padding: 1.5rem;
    margin-bottom: 1.5rem;
    position: relative;
    overflow: hidden;
    box-shadow: var(--shadow-md);
}

.contratista-header::before {
    content: '';
    position: absolute;
    top: 0;
    right: 0;
    width: 300px;
    height: 300px;
    background: radial-gradient(circle at center, rgba(255, 255, 255, 0.1) 0%, rgba(255, 255, 255, 0) 70%);
    border-radius: 50%;
    transform: translate(100px, -100px);
}

.contratista-id {
    display: inline-flex;
    align-items: center;
    padding: 0.35rem 0.75rem;
    background-color: rgba(255, 255, 255, 0.2);
    border-radius: var(--radius);
    font-weight: 600;
    font-size: 0.875rem;
    margin-bottom: 0.5rem;
}

.contratista-name {
    font-size: 1.5rem;
    font-weight: 700;
    margin-bottom: 0.5rem;
}

.contratista-type {
    opacity: 0.9;
    font-size: 0.9rem;
    margin-bottom: 1rem;
}

.project-reference {
    background-color: rgba(255, 255, 255, 0.15);
    border-radius: var(--radius);
    padding: 0.75rem 1rem;
    font-size: 0.875rem;
    max-width: fit-content;
}

.project-reference a {
    color: white;
    text-decoration: none;
    display: flex;
    align-items: center;
}

.project-reference a:hover {
    text-decoration: underline;
}

.project-reference i {
    margin-right: 0.5rem;
}

.nav-tabs {
    border-bottom: 2px solid var(--neutral-200);
    gap: 0.5rem;
    margin-bottom: 1.5rem;
}

.nav-tabs .nav-link {
    color: var(--neutral-600);
    border: none;
    border-bottom: 2px solid transparent;
    margin-bottom: -2px;
    border-radius: 0;
    padding: 0.75rem 1rem;
    font-weight: 600;
    transition: all 0.2s ease;
}

.nav-tabs .nav-link:hover {
    color: var(--primary);
    border-color: transparent;
    background-color: rgba(99, 102, 241, 0.05);
}

.nav-tabs .nav-link.active {
    color: var(--primary);
    border-color: var(--primary);
    background-color: transparent;
}

.nav-tabs .nav-link i {
    margin-right: 0.5rem;
}

.card {
    background-color: var(--card-bg);
    border-radius: var(--radius-lg);
    border: none;
    box-shadow: var(--shadow);
    overflow: hidden;
    margin-bottom: 1.5rem;
}

.card-header {
    background-color: var(--card-bg);
    border-bottom: 1px solid var(--neutral-200);
    padding: 1rem 1.25rem;
}

.card-header h5 {
    margin-bottom: 0;
    font-weight: 600;
    font-size: 1rem;
    color: var(--neutral-800);
}

.card-body {
    padding: 1.25rem;
}

.info-group {
    margin-bottom: 1.5rem;
}

.info-group:last-child {
    margin-bottom: 0;
}

.info-group h6 {
    font-weight: 600;
    font-size: 0.875rem;
    color: var(--neutral-600);
    margin-bottom: 0.75rem;
    padding-bottom: 0.5rem;
    border-bottom: 1px solid var(--neutral-200);
}

.info-row {
    display: flex;
    flex-wrap: wrap;
    margin-bottom: 0.5rem;
}

.info-label {
    width: 120px;
    font-size: 0.8rem;
    color: var(--neutral-500);
    padding-right: 1rem;
}

.info-value {
    flex: 1;
    min-width: 60%;
    font-size: 0.875rem;
    color: var(--neutral-800);
    font-weight: 500;
}

.contract-card {
    border: 1px solid var(--neutral-200);
    border-radius: var(--radius);
    padding: 1rem;
    margin-bottom: 1rem;
    background-color: var(--neutral-50);
    transition: all 0.2s ease;
}

.contract-card:hover {
    box-shadow: var(--shadow-sm);
    border-color: var(--primary-light);
}

.contract-card.active {
    border-color: var(--primary);
    background-color: rgba(99, 102, 241, 0.05);
}

.contract-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 0.75rem;
}

.contract-number {
    font-weight: 600;
    color: var(--primary);
    font-size: 0.9rem;
}

.contract-dates {
    font-size: 0.75rem;
    color: var(--neutral-500);
}

.contract-title {
    font-weight: 500;
    font-size: 0.875rem;
    margin-bottom: 0.5rem;
    color: var(--neutral-700);
}

.contract-value {
    font-weight: 600;
    font-size: 0.875rem;
    color: var(--neutral-800);
}

.contract-type {
    display: inline-block;
    font-size: 0.7rem;
    padding: 0.2rem 0.5rem;
    border-radius: var(--radius-sm);
    background-color: var(--neutral-200);
    color: var(--neutral-700);
    font-weight: 500;
}

.tabla-actas {
    width: 100%;
    border-collapse: separate;
    border-spacing: 0;
}

.tabla-actas th {
    background-color: var(--neutral-100);
    color: var(--neutral-600);
    font-size: 0.75rem;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    font-weight: 600;
    padding: 0.75rem 1rem;
    text-align: left;
    border-bottom: 1px solid var(--neutral-200);
}

.tabla-actas th:first-child {
    border-top-left-radius: var(--radius);
}

.tabla-actas th:last-child {
    border-top-right-radius: var(--radius);
}

.tabla-actas td {
    padding: 0.75rem 1rem;
    vertical-align: middle;
    border-bottom: 1px solid var(--neutral-200);
    font-size: 0.875rem;
    color: var(--neutral-700);
}

.tabla-actas tbody tr:hover {
    background-color: var(--neutral-50);
}

.tabla-actas tr:last-child td {
    border-bottom: none;
}

.tabla-actas tr:last-child td:first-child {
    border-bottom-left-radius: var(--radius);
}

.tabla-actas tr:last-child td:last-child {
    border-bottom-right-radius: var(--radius);
}

.badge {
    display: inline-block;
    padding: 0.35em 0.65em;
    font-size: 0.75em;
    font-weight: 600;
    line-height: 1;
    text-align: center;
    white-space: nowrap;
    vertical-align: baseline;
    border-radius: var(--radius-sm);
}

.badge-primary {
    background-color: var(--primary-light);
    color: var(--primary-dark);
}

.badge-success {
    background-color: var(--success-light);
    color: var(--success);
}

.badge-info {
    background-color: var(--info-light);
    color: var(--info);
}

.badge-warning {
    background-color: var(--warning-light);
    color: var(--warning);
}

.empty-state {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 3rem;
    text-align: center;
    background-color: var(--neutral-50);
    border-radius: var(--radius);
    border: 1px dashed var(--neutral-300);
}

.empty-state i {
    font-size: 2.5rem;
    color: var(--neutral-400);
    margin-bottom: 1rem;
}

.empty-state h6 {
    font-size: 1rem;
    font-weight: 600;
    color: var(--neutral-700);
    margin-bottom: 0.5rem;
}

.empty-state p {
    font-size: 0.875rem;
    color: var(--neutral-500);
    max-width: 300px;
    margin: 0 auto;
}

.btn-link {
    color: var(--primary);
    text-decoration: none;
    font-weight: 500;
    transition: all 0.2s ease;
}

.btn-link:hover {
    color: var(--primary-dark);
    text-decoration: underline;
}

.btn-link i {
    margin-right: 0.25rem;
}

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

.btn-view {
    background-color: rgba(6, 182, 212, 0.1);
    color: var(--info);
    border: 1px solid rgba(6, 182, 212, 0.2);
}

.btn-view:hover {
    background-color: rgba(6, 182, 212, 0.2);
    color: var(--info);
    transform: translateY(-1px);
}

.btn-download {
    background-color: rgba(16, 185, 129, 0.1);
    color: var(--success);
    border: 1px solid rgba(16, 185, 129, 0.2);
}

.btn-download:hover {
    background-color: rgba(16, 185, 129, 0.2);
    color: var(--success);
    transform: translateY(-1px);
}

@media (max-width: 768px) {
    .info-label {
        width: 100%;
        margin-bottom: 0.25rem;
    }
    
    .info-value {
        width: 100%;
    }
    
    .info-row {
        margin-bottom: 1rem;
    }
}
</style>

<div class="container-fluid py-4">
    <!-- Botón de regreso -->
    <a href="main.php?page=proyecto_individual&id=<?php echo $proyecto_id; ?>" class="btn btn-secondary mb-3">
        <i class="fas fa-arrow-left"></i> Volver al proyecto
    </a>
    
    <!-- Encabezado del Contratista -->
    <div class="contratista-header">
        <div class="row">
            <div class="col-md-8">
                <div class="contratista-id">
                    <i class="fas fa-id-card mr-2"></i> ID: <?php echo $contratista['identificacion']; ?>
                </div>
                <h4 class="contratista-name"><?php echo htmlspecialchars($contratista['nombre_completo']); ?></h4>
                <div class="contratista-type">
                    <i class="fas fa-user-tag"></i> 
                    <?php echo $contratista['tipo_persona_desc'] ?: 'Tipo: ' . $contratista['tipo_persona']; ?>
                    <?php if (!empty($contratista['tipo_nacionalidad_desc'])): ?>
                    | <i class="fas fa-globe-americas"></i> <?php echo $contratista['tipo_nacionalidad_desc']; ?>
                    <?php endif; ?>
                </div>
                
                <div class="project-reference mt-3">
                    <a href="main.php?page=proyecto_individual&id=<?php echo $proyecto_id; ?>">
                        <i class="fas fa-project-diagram"></i>
                        Proyecto: <?php echo htmlspecialchars($proyecto['numero_pro_entidad'] ?: $proyecto['numero_pro']); ?>
                    </a>
                </div>
            </div>
            <div class="col-md-4 mt-3 mt-md-0 d-flex align-items-center justify-content-md-end">
                <?php if (!empty($contratista['correo']) || !empty($contratista['tel_celular'])): ?>
                <div class="d-flex flex-column">
                    <?php if (!empty($contratista['correo'])): ?>
                    <a href="mailto:<?php echo htmlspecialchars($contratista['correo']); ?>" class="btn btn-sm btn-light mb-2">
                        <i class="fas fa-envelope"></i> <?php echo htmlspecialchars($contratista['correo']); ?>
                    </a>
                    <?php endif; ?>
                    <?php if (!empty($contratista['tel_celular'])): ?>
                    <a href="tel:<?php echo htmlspecialchars($contratista['tel_celular']); ?>" class="btn btn-sm btn-light">
                        <i class="fas fa-phone"></i> <?php echo htmlspecialchars($contratista['tel_celular']); ?>
                    </a>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Navegación por pestañas -->
    <ul class="nav nav-tabs" id="contratistaTabs" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active" id="info-tab" data-bs-toggle="tab" data-bs-target="#info" type="button" role="tab" aria-controls="info" aria-selected="true">
                <i class="fas fa-info-circle"></i> Información General
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="contratos-tab" data-bs-toggle="tab" data-bs-target="#contratos" type="button" role="tab" aria-controls="contratos" aria-selected="false">
                <i class="fas fa-file-contract"></i> Contratos
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="actas-tab" data-bs-toggle="tab" data-bs-target="#actas" type="button" role="tab" aria-controls="actas" aria-selected="false">
                <i class="fas fa-file-signature"></i> Actas
                <?php if (!empty($actas)): ?>
                <span class="badge badge-primary"><?php echo count($actas); ?></span>
                <?php endif; ?>
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="documentos-tab" data-bs-toggle="tab" data-bs-target="#documentos" type="button" role="tab" aria-controls="documentos" aria-selected="false">
                <i class="fas fa-file-alt"></i> Documentos
                <?php if (!empty($documentos)): ?>
                <span class="badge badge-primary"><?php echo count($documentos); ?></span>
                <?php endif; ?>
            </button>
        </li>
    </ul>
    
    <!-- Contenido de las pestañas -->
    <div class="tab-content" id="contratistaTabContent">
        <!-- Pestaña de Información General -->
        <div class="tab-pane fade show active" id="info" role="tabpanel" aria-labelledby="info-tab">
            <div class="row">
                <div class="col-lg-8">
                    <div class="card">
                        <div class="card-header">
                            <h5><i class="fas fa-user mr-2 text-primary"></i> Información Personal</h5>
                        </div>
                        <div class="card-body">
                            <div class="info-group">
                                <h6>Datos de Identificación</h6>
                                <div class="info-row">
                                    <div class="info-label">Identificación</div>
                                    <div class="info-value"><?php echo htmlspecialchars($contratista['identificacion']); ?></div>
                                </div>
                                <div class="info-row">
                                    <div class="info-label">Nombres</div>
                                    <div class="info-value">
                                        <?php if ($contratista['tipo_persona'] == 1): // Persona Natural ?>
                                            <?php echo htmlspecialchars(trim($contratista['nombre1'] . ' ' . ($contratista['nombre2'] ?? ''))); ?>
                                        <?php else: ?>
                                            <?php echo htmlspecialchars($contratista['nombre1']); ?>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <?php if ($contratista['tipo_persona'] == 1): // Solo para personas naturales ?>
                                <div class="info-row">
                                    <div class="info-label">Apellidos</div>
                                    <div class="info-value">
                                        <?php echo htmlspecialchars(trim(($contratista['apellido1'] ?? '') . ' ' . ($contratista['apellido2'] ?? ''))); ?>
                                    </div>
                                </div>
                                <?php endif; ?>
                                <div class="info-row">
                                    <div class="info-label">Tipo de Persona</div>
                                    <div class="info-value"><?php echo htmlspecialchars($contratista['tipo_persona_desc'] ?: 'Tipo ' . $contratista['tipo_persona']); ?></div>
                                </div>
                                <div class="info-row">
                                    <div class="info-label">Nacionalidad</div>
                                    <div class="info-value"><?php echo htmlspecialchars($contratista['tipo_nacionalidad_desc'] ?: ($contratista['tipo_nacionalidad'] ?: 'No especificada')); ?></div>
                                </div>
                                <?php if (!empty($contratista['fecha_nacimiento'])): ?>
                                <div class="info-row">
                                    <div class="info-label">Fecha Nacimiento</div>
                                    <div class="info-value"><?php echo formatearFecha($contratista['fecha_nacimiento']); ?></div>
                                </div>
                                <?php endif; ?>
                                <?php if (!empty($contratista['genero'])): ?>
                                <div class="info-row">
                                    <div class="info-label">Género</div>
                                    <div class="info-value">
                                        <?php 
                                        $genero = '';
                                        switch ($contratista['genero']) {
                                            case 'M': $genero = 'Masculino'; break;
                                            case 'F': $genero = 'Femenino'; break;
                                            default: $genero = $contratista['genero'];
                                        }
                                        echo htmlspecialchars($genero); 
                                        ?>
                                    </div>
                                </div>
                                <?php endif; ?>
                            </div>
                            
                            <div class="info-group">
                                <h6>Datos de Contacto</h6>
                                <div class="info-row">
                                    <div class="info-label">Dirección</div>
                                    <div class="info-value"><?php echo htmlspecialchars($contratista['direccion'] ?: 'No especificada'); ?></div>
                                </div>
                                <div class="info-row">
                                    <div class="info-label">Teléfono Fijo</div>
                                    <div class="info-value"><?php echo htmlspecialchars($contratista['tel_fijo'] ?: 'No especificado'); ?></div>
                                </div>
                                <div class="info-row">
                                    <div class="info-label">Teléfono Celular</div>
                                    <div class="info-value">
                                        <?php if (!empty($contratista['tel_celular'])): ?>
                                            <a href="tel:<?php echo htmlspecialchars($contratista['tel_celular']); ?>" class="btn-link">
                                                <i class="fas fa-phone-alt"></i> <?php echo htmlspecialchars($contratista['tel_celular']); ?>
                                            </a>
                                        <?php else: ?>
                                            No especificado
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div class="info-row">
                                    <div class="info-label">Correo Electrónico</div>
                                    <div class="info-value">
                                        <?php if (!empty($contratista['correo'])): ?>
                                            <a href="mailto:<?php echo htmlspecialchars($contratista['correo']); ?>" class="btn-link">
                                                <i class="fas fa-envelope"></i> <?php echo htmlspecialchars($contratista['correo']); ?>
                                            </a>
                                        <?php else: ?>
                                            No especificado
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-lg-4">
                    <div class="card">
                        <div class="card-header">
                            <h5><i class="fas fa-project-diagram mr-2 text-primary"></i> Proyecto Asociado</h5>
                        </div>
                        <div class="card-body">
                            <div class="info-row">
                                <div class="info-label">Código</div>
                                <div class="info-value"><?php echo htmlspecialchars($proyecto['numero_pro']); ?></div>
                            </div>
                            <div class="info-row">
                                <div class="info-label">Referencia</div>
                                <div class="info-value"><?php echo htmlspecialchars($proyecto['numero_pro_entidad'] ?: '-'); ?></div>
                            </div>
                            <div class="info-row">
                                <div class="info-label">Nombre</div>
                                <div class="info-value"><?php echo htmlspecialchars($proyecto['nombre']); ?></div>
                            </div>
                            <div class="info-row">
                                <div class="info-label">Entidad</div>
                                <div class="info-value"><?php echo htmlspecialchars($proyecto['entidad'] ?: '-'); ?></div>
                            </div>
                            <div class="info-row">
                                <div class="info-label">Valor</div>
                                <div class="info-value"><?php echo formatearMoneda($proyecto['valor']); ?></div>
                            </div>
                            <div class="mt-3">
                                <a href="main.php?page=proyecto_individual&id=<?php echo $proyecto_id; ?>" class="btn btn-sm btn-primary">
                                    <i class="fas fa-eye"></i> Ver Proyecto Completo
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Pestaña de Contratos -->
        <div class="tab-pane fade" id="contratos" role="tabpanel" aria-labelledby="contratos-tab">
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h5><i class="fas fa-file-contract mr-2 text-primary"></i> Contratos con este Proyecto</h5>
                        </div>
                        <div class="card-body">
                            <?php if (empty($contratos)): ?>
                                <div class="empty-state">
                                    <i class="fas fa-file-contract"></i>
                                    <h6>No hay contratos registrados</h6>
                                    <p>No se encontraron contratos relacionados con este contratista en este proyecto.</p>
                                </div>
                            <?php else: ?>
                                <div class="row">
                                    <?php foreach ($contratos as $contrato): ?>
                                        <div class="col-md-6 mb-3">
                                            <div class="contract-card <?php echo ($contrato['numero_contrato'] == $numero_contrato) ? 'active' : ''; ?>">
                                                <div class="contract-header">
                                                    <div class="contract-number">
                                                        Contrato N° <?php echo $contrato['numero_contrato']; ?>/<?php echo $contrato['anio_contrato'] ?: $contrato['anio']; ?>
                                                    </div>
                                                    <div class="contract-type">
                                                        <?php echo htmlspecialchars($contrato['tipo_contrato_desc'] ?: 'Tipo ' . $contrato['tipo_contrato']); ?>
                                                    </div>
                                                </div>
                                                <div class="contract-title">
                                                    <?php echo htmlspecialchars(substr($contrato['objeto'], 0, 100) . (strlen($contrato['objeto']) > 100 ? '...' : '')); ?>
                                                </div>
                                                <div class="contract-dates mb-2">
                                                    <i class="far fa-calendar-alt"></i> 
                                                    <?php echo formatearFecha($contrato['fecha_inicio']); ?> - 
                                                    <?php echo formatearFecha($contrato['fecha_fin']); ?>
                                                </div>
                                                <div class="d-flex justify-content-between align-items-center">
                                                    <div class="contract-value">
                                                        <?php echo formatearMoneda($contrato['valor']); ?>
                                                    </div>
                                                    <a href="main.php?page=ver_contratista&id=<?php echo $contratista_id; ?>&proyecto_id=<?php echo $proyecto_id; ?>&contrato=<?php echo $contrato['numero_contrato']; ?>" class="btn-link">
                                                        <i class="fas fa-file-signature"></i> Ver Actas
                                                    </a>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Pestaña de Actas -->
        <div class="tab-pane fade" id="actas" role="tabpanel" aria-labelledby="actas-tab">
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5>
                                <i class="fas fa-file-signature mr-2 text-primary"></i> 
                                Actas del Contrato
                                <?php if ($contrato_actual): ?>
                                    N° <?php echo $contrato_actual['numero_contrato']; ?>/<?php echo $contrato_actual['anio_contrato'] ?: $contrato_actual['anio']; ?>
                                <?php endif; ?>
                            </h5>
                            <?php if (count($contratos) > 1): ?>
                                <div class="dropdown">
                                    <button class="btn btn-sm btn-outline-primary dropdown-toggle" type="button" id="dropdownMenuButton" data-bs-toggle="dropdown" aria-expanded="false">
                                        Cambiar Contrato
                                    </button>
                                    <ul class="dropdown-menu" aria-labelledby="dropdownMenuButton">
                                        <?php foreach ($contratos as $contrato): ?>
                                            <li>
                                                <a class="dropdown-item <?php echo ($contrato['numero_contrato'] == $numero_contrato) ? 'active' : ''; ?>" 
                                                   href="main.php?page=ver_contratista&id=<?php echo $contratista_id; ?>&proyecto_id=<?php echo $proyecto_id; ?>&contrato=<?php echo $contrato['numero_contrato']; ?>#actas">
                                                    Contrato N° <?php echo $contrato['numero_contrato']; ?>/<?php echo $contrato['anio_contrato'] ?: $contrato['anio']; ?>
                                                </a>
                                            </li>
                                        <?php endforeach; ?>
                                    </ul>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="card-body">
                            <?php if (empty($actas)): ?>
                                <div class="empty-state">
                                    <i class="fas fa-file-signature"></i>
                                    <h6>No hay actas registradas</h6>
                                    <p>No se encontraron actas asociadas a este contratista para el contrato seleccionado.</p>
                                </div>
                            <?php else: ?>
                                <div class="table-responsive">
                                    <table class="tabla-actas">
                                        <thead>
                                            <tr>
                                                <th width="60">N°</th>
                                                <th>Tipo</th>
                                                <th width="120">Fecha</th>
                                                <th>Observaciones</th>
                                                <?php if (array_filter(array_column($actas, 'valor_op'))): ?>
                                                    <th width="120">Valor OP</th>
                                                <?php endif; ?>
                                                <th width="120" class="text-center">Acciones</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach($actas as $acta): ?>
                                                <tr>
                                                    <td class="text-center"><?php echo $acta['numero_acta']; ?></td>
                                                    <td>
                                                        <?php echo htmlspecialchars($acta['tipo_acta_desc'] ?: 'Tipo ' . $acta['tipo_acta']); ?>
                                                    </td>
                                                    <td><?php echo formatearFecha($acta['fecha_acta']); ?></td>
                                                    <td>
                                                        <?php 
                                                        $observa = trim($acta['observa'] ?? '');
                                                        if (!empty($observa)) {
                                                            echo htmlspecialchars(substr($observa, 0, 80) . (strlen($observa) > 80 ? '...' : ''));
                                                        } else {
                                                            echo '<span class="text-muted">Sin observaciones</span>';
                                                        }
                                                        ?>
                                                    </td>
                                                    <?php if (array_filter(array_column($actas, 'valor_op'))): ?>
                                                        <td><?php echo !empty($acta['valor_op']) ? formatearMoneda($acta['valor_op']) : '-'; ?></td>
                                                    <?php endif; ?>
                                                    <td class="text-center">
                                                        <div class="d-flex justify-content-center gap-2">
                                                            <!-- Aquí podrían ir botones para ver o descargar el acta si estuvieran implementados -->
                                                            <span class="action-button btn-view">
                                                            <a href="http://siexud.udistrital.edu.co/idexud/siexud/actascont/upload/<?php echo $documento['archivo']; ?>" target="_blank" class="action-button btn-view">
                                                                <i class="fas fa-eye"></i> Ver
                                                            </a>
                                                            </span>
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
        
        <!-- Pestaña de Documentos -->
        <div class="tab-pane fade" id="documentos" role="tabpanel" aria-labelledby="documentos-tab">
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5>
                                <i class="fas fa-file-alt mr-2 text-primary"></i> 
                                Documentos del Contrato
                                <?php if ($contrato_actual): ?>
                                    N° <?php echo $contrato_actual['numero_contrato']; ?>/<?php echo $contrato_actual['anio_contrato'] ?: $contrato_actual['anio']; ?>
                                <?php endif; ?>
                            </h5>
                            <?php if (count($contratos) > 1): ?>
                                <div class="dropdown">
                                    <button class="btn btn-sm btn-outline-primary dropdown-toggle" type="button" id="dropdownMenuButton2" data-bs-toggle="dropdown" aria-expanded="false">
                                        Cambiar Contrato
                                    </button>
                                    <ul class="dropdown-menu" aria-labelledby="dropdownMenuButton2">
                                        <?php foreach ($contratos as $contrato): ?>
                                            <li>
                                                <a class="dropdown-item <?php echo ($contrato['numero_contrato'] == $numero_contrato) ? 'active' : ''; ?>" 
                                                   href="main.php?page=ver_contratista&id=<?php echo $contratista_id; ?>&proyecto_id=<?php echo $proyecto_id; ?>&contrato=<?php echo $contrato['numero_contrato']; ?>#documentos">
                                                    Contrato N° <?php echo $contrato['numero_contrato']; ?>/<?php echo $contrato['anio_contrato'] ?: $contrato['anio']; ?>
                                                </a>
                                            </li>
                                        <?php endforeach; ?>
                                    </ul>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="card-body">
                            <?php if (empty($documentos)): ?>
                                <div class="empty-state">
                                    <i class="fas fa-file-alt"></i>
                                    <h6>No hay documentos registrados</h6>
                                    <p>No se encontraron documentos asociados a este contratista para el contrato seleccionado.</p>
                                </div>
                            <?php else: ?>
                                <div class="table-responsive">
                                    <table class="tabla-actas">
                                        <thead>
                                            <tr>
                                                <th width="60">N°</th>
                                                <th>Tipo</th>
                                                <th width="120">Fecha</th>
                                                <th>Archivo</th>
                                                <th width="120" class="text-center">Acciones</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach($documentos as $documento): ?>
                                                <tr>
                                                    <td class="text-center"><?php echo $documento['numero_doc']; ?></td>
                                                    <td>
                                                        <?php echo htmlspecialchars($documento['tipo_doc_desc'] ?: 'Tipo ' . $documento['tipo_doc']); ?>
                                                    </td>
                                                    <td><?php echo formatearFecha($documento['fecha_doc']); ?></td>
                                                    <td>
                                                        <?php echo htmlspecialchars($documento['archivo'] ?: 'Sin archivo'); ?>
                                                    </td>
                                                    <td class="text-center">
                                                        <?php if (!empty($documento['archivo'])): ?>
                                                        <div class="d-flex justify-content-center gap-2">
                                                            <a href="http://siexud.udistrital.edu.co/idexud/siexud/actascont/upload/<?php echo $documento['archivo']; ?>" target="_blank" class="action-button btn-view">
                                                                <i class="fas fa-eye"></i> Ver
                                                            </a>
                                                            <a href="http://siexud.udistrital.edu.co/idexud/siexud/actascont/upload/<?php echo $documento['archivo']; ?>" class="action-button btn-download" download>
                                                                <i class="fas fa-download"></i>
                                                            </a>
                                                        </div>
                                                        <?php else: ?>
                                                        <span class="text-muted">No disponible</span>
                                                        <?php endif; ?>
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
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Activar la pestaña guardada en el hash de la URL, si existe
    const hash = window.location.hash;
    if (hash) {
        const tabId = hash.substring(1);
        const tab = document.querySelector(`#${tabId}-tab`);
        if (tab) {
            const bsTab = new bootstrap.Tab(tab);
            bsTab.show();
        }
    }
    
    // Guardar la pestaña activa en el hash de la URL cuando cambia
    const tabLinks = document.querySelectorAll('button[data-bs-toggle="tab"]');
    tabLinks.forEach(tabLink => {
        tabLink.addEventListener('shown.bs.tab', event => {
            const target = event.target.getAttribute('data-bs-target').substring(1);
            window.location.hash = target;
        });
    });
});
</script>