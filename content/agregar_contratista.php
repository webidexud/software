<?php
/**
 * Página para agregar un nuevo contratista a un proyecto
 * 
 * Este archivo maneja la interfaz y la lógica para asociar un contratista 
 * existente o crear uno nuevo y vincularlo a un proyecto específico
 */

// Verificar que se haya proporcionado un ID de proyecto
if (!isset($_GET['proyecto_id']) || !is_numeric($_GET['proyecto_id'])) {
    // Redirigir a la lista de proyectos si no se proporciona un ID válido
    header('Location: main.php?page=proyecto');
    exit;
}

$proyecto_id = intval($_GET['proyecto_id']);

// Incluir los modelos necesarios
if (!function_exists('obtenerProyectoDetalle')) {
    if (file_exists('models/proyecto_model.php')) {
        include_once 'models/proyecto_model.php';
    } else {
        echo '<div class="alert alert-danger">Error: No se puede encontrar el modelo de proyectos.</div>';
        exit;
    }
}

// Incluir modelo específico para contratistas
if (!file_exists('models/contratista_model.php')) {
    // Crear el modelo si no existe
    include_once 'models/contratista_model.php';
} else {
    include_once 'models/contratista_model.php';
}

// Obtener información del proyecto
$proyecto = obtenerProyectoDetalle($proyecto_id);

// Si no se encuentra el proyecto, mostrar mensaje de error
if (!$proyecto) {
    echo '<div class="alert alert-danger" role="alert">El proyecto solicitado no existe o no se puede acceder a él en este momento.</div>';
    exit;
}

// Obtener lista de tipos de persona
$tiposPersona = obtenerTiposPersona();

// Obtener lista de tipos de nacionalidad 
$tiposNacionalidad = obtenerTiposNacionalidad();

// Obtener lista de tipos de contrato
$tiposContrato = obtenerTiposContrato();

// Obtener lista de tipos de vinculación
$tiposVinculacion = obtenerTiposVinculacion();

// Obtener lista de tipologías
$tipologias = obtenerTipologias();

// Obtener lista de facultades
$facultades = obtenerFacultades();

// Obtener lista de proyectos curriculares
$proyectosCurriculares = obtenerProyectosCurriculares();

// Inicializar variables para mensajes
$errores = [];
$mensaje_exito = '';

// Determinar el modo de operación: buscar contratista existente o crear nuevo
$modo = isset($_GET['modo']) ? $_GET['modo'] : 'buscar';

// Variables para datos del contratista
$contratista = null;
$contratistaID = null;

// Procesar búsqueda de contratista
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['buscar_contratista'])) {
    $identificacion = isset($_POST['identificacion']) ? trim($_POST['identificacion']) : '';
    
    if (empty($identificacion)) {
        $errores[] = 'Debe ingresar un número de identificación para buscar';
    } else {
        // Buscar contratista existente
        $contratista = obtenerContratistaExistente($identificacion);
        $contratistaID = $identificacion;
        
        if (!$contratista) {
            // No se encontró el contratista, cambiar a modo crear
            $modo = 'crear';
        }
    }
}

// Procesar formulario de creación de contratista
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['crear_contratista'])) {
    // Recoger datos del formulario
    $identificacion = isset($_POST['identificacion']) ? trim($_POST['identificacion']) : '';
    $nombre1 = isset($_POST['nombre1']) ? trim($_POST['nombre1']) : '';
    $nombre2 = isset($_POST['nombre2']) ? trim($_POST['nombre2']) : '';
    $apellido1 = isset($_POST['apellido1']) ? trim($_POST['apellido1']) : '';
    $apellido2 = isset($_POST['apellido2']) ? trim($_POST['apellido2']) : '';
    $tipo_persona = isset($_POST['tipo_persona']) ? intval($_POST['tipo_persona']) : 1;
    $tipo_nacionalidad = isset($_POST['tipo_nacionalidad']) ? intval($_POST['tipo_nacionalidad']) : 1;
    $fecha_nacimiento = isset($_POST['fecha_nacimiento']) ? $_POST['fecha_nacimiento'] : null;
    $direccion = isset($_POST['direccion']) ? trim($_POST['direccion']) : '';
    $tel_fijo = isset($_POST['tel_fijo']) ? trim($_POST['tel_fijo']) : '';
    $tel_celular = isset($_POST['tel_celular']) ? trim($_POST['tel_celular']) : '';
    $correo = isset($_POST['correo']) ? trim($_POST['correo']) : '';
    $genero = isset($_POST['genero']) ? trim($_POST['genero']) : 'M';
    
    // Validaciones básicas
    if (empty($identificacion)) {
        $errores[] = 'La identificación es obligatoria';
    }
    
    if ($tipo_persona == 1) { // Persona Natural
        if (empty($nombre1)) {
            $errores[] = 'El primer nombre es obligatorio para personas naturales';
        }
        if (empty($apellido1)) {
            $errores[] = 'El primer apellido es obligatorio para personas naturales';
        }
    } else { // Persona Jurídica
        if (empty($nombre1)) {
            $errores[] = 'La razón social es obligatoria para personas jurídicas';
        }
    }
    
    if (empty($correo)) {
        $errores[] = 'El correo electrónico es obligatorio';
    } elseif (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
        $errores[] = 'El formato del correo electrónico no es válido';
    }
    
    // Si no hay errores, crear el contratista
    if (empty($errores)) {
        $datosContratista = [
            'identificacion' => $identificacion,
            'nombre1' => $nombre1,
            'nombre2' => $nombre2,
            'apellido1' => $apellido1,
            'apellido2' => $apellido2,
            'tipo_persona' => $tipo_persona,
            'tipo_nacionalidad' => $tipo_nacionalidad,
            'fecha_nacimiento' => $fecha_nacimiento,
            'direccion' => $direccion,
            'tel_fijo' => $tel_fijo,
            'tel_celular' => $tel_celular,
            'correo' => $correo,
            'genero' => $genero
        ];
        
        // Verificar si ya existe
        $contratista_existente = obtenerContratistaExistente($identificacion);
        
        if ($contratista_existente) {
            // Actualizar contratista existente
            $resultado = actualizarContratista($datosContratista);
            if ($resultado) {
                $contratista = obtenerContratistaExistente($identificacion);
                $contratistaID = $identificacion;
                $mensaje_exito = 'Contratista actualizado correctamente.';
            } else {
                $errores[] = 'Error al actualizar el contratista en la base de datos';
            }
        } else {
            // Crear nuevo contratista
            $resultado = crearContratista($datosContratista);
            if ($resultado) {
                $contratista = obtenerContratistaExistente($identificacion);
                $contratistaID = $identificacion;
                $mensaje_exito = 'Contratista creado correctamente.';
            } else {
                $errores[] = 'Error al crear el contratista en la base de datos';
            }
        }
    }
}

// Procesar asociación de contratista a proyecto
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['asociar_contratista'])) {
    // Recoger datos del formulario
    $identificacion = isset($_POST['identificacion']) ? trim($_POST['identificacion']) : '';
    $numero_contrato = isset($_POST['numero_contrato']) ? intval($_POST['numero_contrato']) : 0;
    $tipo_contrato = isset($_POST['tipo_contrato']) ? intval($_POST['tipo_contrato']) : 0;
    $tipo_vinculacion = isset($_POST['tipo_vinculacion']) ? intval($_POST['tipo_vinculacion']) : 0;
    $tipo_proceso = isset($_POST['tipo_proceso']) ? intval($_POST['tipo_proceso']) : 0;
    $tipologia = isset($_POST['tipologia']) ? intval($_POST['tipologia']) : 0;
    $facultad = isset($_POST['facultad']) ? intval($_POST['facultad']) : 0;
    $proyecto_curricular = isset($_POST['proyecto_curricular']) ? intval($_POST['proyecto_curricular']) : 0;
    $fecha_inicio = isset($_POST['fecha_inicio']) ? $_POST['fecha_inicio'] : '';
    $fecha_terminacion = isset($_POST['fecha_terminacion']) ? $_POST['fecha_terminacion'] : '';
    $fecha_suscripcion = isset($_POST['fecha_suscripcion']) ? $_POST['fecha_suscripcion'] : date('Y-m-d');
    $valor = isset($_POST['valor']) ? str_replace(',', '', $_POST['valor']) : 0;
    $objeto = isset($_POST['objeto']) ? trim($_POST['objeto']) : '';
    
    // Validaciones básicas
    if (empty($identificacion)) {
        $errores[] = 'La identificación del contratista es obligatoria';
    }
    
    if (empty($tipo_contrato)) {
        $errores[] = 'El tipo de contrato es obligatorio';
    }
    
    if (empty($fecha_inicio)) {
        $errores[] = 'La fecha de inicio es obligatoria';
    }
    
    if (empty($fecha_terminacion)) {
        $errores[] = 'La fecha de terminación es obligatoria';
    }
    
    if (!empty($fecha_inicio) && !empty($fecha_terminacion) && $fecha_inicio > $fecha_terminacion) {
        $errores[] = 'La fecha de inicio no puede ser posterior a la fecha de terminación';
    }
    
    if (empty($objeto)) {
        $errores[] = 'El objeto/rol del contratista es obligatorio';
    }
    
    // Si no hay errores, asociar el contratista al proyecto
    if (empty($errores)) {
        // Preparar datos para la asociación
        $datosAsociacion = [
            'anio_pro' => $proyecto['anio_pro'],
            'numero_pro' => $proyecto_id,
            'identificacion' => $identificacion,
            'numero_contrato' => $numero_contrato,
            'tipo_contrato' => $tipo_contrato,
            'tipo_vinculacion' => $tipo_vinculacion,
            'tipo_proceso' => $tipo_proceso,
            'tipologia' => $tipologia,
            'facultad' => $facultad,
            'proyecto_curricular' => $proyecto_curricular,
            'fecha_inicio' => $fecha_inicio,
            'fecha_terminacion' => $fecha_terminacion,
            'fecha_suscripcion' => $fecha_suscripcion,
            'valor' => $valor,
            'objeto' => $objeto,
            'unidad' => $proyecto['unidad'] // Usar la unidad del proyecto
        ];
        
        // Verificar si ya existe una asociación para este contratista y proyecto
        $asociacion_existente = verificarAsociacionExistente($identificacion, $proyecto_id, $proyecto['anio_pro']);
        
        if ($asociacion_existente) {
            // Actualizar la asociación existente
            $resultado = actualizarAsociacionContratista($datosAsociacion);
            if ($resultado) {
                $mensaje_exito = 'Asociación actualizada correctamente.';
                // Redirigir a la página del proyecto después de un breve retraso
                echo '<meta http-equiv="refresh" content="2;url=main.php?page=proyecto_individual&id=' . $proyecto_id . '#contratistas">';
            } else {
                $errores[] = 'Error al actualizar la asociación en la base de datos';
            }
        } else {
            // Crear nueva asociación
            $resultado = asociarContratistaProyecto($datosAsociacion);
            if ($resultado) {
                $mensaje_exito = 'Contratista asociado correctamente al proyecto.';
                // Redirigir a la página del proyecto después de un breve retraso
                echo '<meta http-equiv="refresh" content="2;url=main.php?page=proyecto_individual&id=' . $proyecto_id . '#contratistas">';
            } else {
                $errores[] = 'Error al asociar el contratista al proyecto';
            }
        }
    }
}

// Función para formatear fechas
function formatearFecha($fecha) {
    if (!$fecha) return "";
    
    try {
        $date = new DateTime($fecha);
        return $date->format('Y-m-d');
    } catch (Exception $e) {
        return "";
    }
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
}

.custom-card-body {
    padding: 1.5rem;
}

.form-label {
    font-size: 0.875rem;
    font-weight: 600;
    color: var(--neutral-700);
    margin-bottom: 0.5rem;
}

.form-control, .form-select {
    border-radius: var(--radius);
    padding: 0.625rem 0.75rem;
    border: 1px solid var(--neutral-300);
    font-size: 0.875rem;
    background-color: var(--card-bg);
    transition: all 0.15s ease;
}

.form-control:focus, .form-select:focus {
    border-color: var(--primary-light);
    box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
}

.input-group-text {
    background-color: var(--neutral-100);
    border-color: var(--neutral-300);
    color: var(--neutral-500);
}

.btn {
    font-weight: 500;
    padding: 0.625rem 1.25rem;
    border-radius: var(--radius);
    transition: all 0.15s ease;
}

.btn-primary {
    background-color: var(--primary);
    border-color: var(--primary);
}

.btn-primary:hover {
    background-color: var(--primary-dark);
    border-color: var(--primary-dark);
    transform: translateY(-1px);
    box-shadow: var(--shadow-sm);
}

.btn-secondary {
    background-color: var(--neutral-100);
    border-color: var(--neutral-300);
    color: var(--neutral-700);
}

.btn-secondary:hover {
    background-color: var(--neutral-200);
    border-color: var(--neutral-400);
    color: var(--neutral-800);
    transform: translateY(-1px);
}

.btn-success {
    background-color: var(--success);
    border-color: var(--success);
    color: white;
}

.btn-success:hover {
    background-color: #0da271;
    border-color: #0da271;
    transform: translateY(-1px);
    box-shadow: var(--shadow-sm);
}

.btn i {
    margin-right: 0.5rem;
}

.alert {
    border-radius: var(--radius);
    border: none;
    padding: 1rem 1.25rem;
    margin-bottom: 1.5rem;
}

.alert-danger {
    background-color: var(--danger-light);
    color: var(--danger);
}

.alert-success {
    background-color: var(--success-light);
    color: var(--success);
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

.progress {
    height: 0.5rem;
    background-color: var(--neutral-200);
    border-radius: 1rem;
    overflow: hidden;
}

.progress-bar {
    background-color: var(--primary);
    border-radius: 1rem;
}

.nav-tabs {
    border-bottom: 1px solid var(--neutral-200);
    margin-bottom: 1.5rem;
}

.nav-tabs .nav-link {
    color: var(--neutral-600);
    font-weight: 500;
    padding: 0.75rem 1.25rem;
    border: none;
    border-bottom: 2px solid transparent;
    margin-bottom: -1px;
    background: transparent;
}

.nav-tabs .nav-link:hover {
    color: var(--primary);
    border-bottom-color: var(--neutral-300);
}

.nav-tabs .nav-link.active {
    color: var(--primary);
    border-bottom-color: var(--primary);
}

.form-text {
    color: var(--neutral-500);
    font-size: 0.75rem;
    margin-top: 0.25rem;
}

.text-muted {
    color: var(--neutral-500) !important;
}

.card-subtitle {
    color: white;
    font-size: 0.875rem;
    margin-top: 0.25rem;
}

.form-required:after {
    content: " *";
    color: var(--danger);
}

.hidden {
    display: none;
}

.page-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1.5rem;
    padding-bottom: 1rem;
    border-bottom: 1px solid var(--neutral-200);
}

.page-header h4 {
    font-weight: 600;
    color: white;
    margin-bottom: 0;
}

.steps-indicator {
    display: flex;
    margin-bottom: 1.5rem;
}

.step {
    flex: 1;
    text-align: center;
    padding: 1rem 0.5rem;
    position: relative;
}

.step:not(:last-child):after {
    content: '';
    position: absolute;
    top: 2rem;
    right: -1.5rem;
    width: 3rem;
    height: 1px;
    background-color: var(--neutral-300);
}

.step-number {
    display: inline-flex;
    justify-content: center;
    align-items: center;
    width: 2rem;
    height: 2rem;
    border-radius: 50%;
    background-color: var(--neutral-200);
    color: var(--neutral-600);
    font-weight: 600;
    margin-bottom: 0.5rem;
}

.step.active .step-number {
    background-color: var(--primary);
    color: white;
}

.step-title {
    font-size: 0.875rem;
    font-weight: 500;
    color: var(--neutral-600);
}

.step.active .step-title {
    color: var(--primary);
    font-weight: 600;
}

.step.completed .step-number {
    background-color: var(--success);
    color: white;
}

.step.completed .step-title {
    color: var(--success);
}

.tab-content > .tab-pane {
    display: none;
}

.tab-content > .active {
    display: block;
}

@media (max-width: 768px) {
    .steps-indicator {
        flex-direction: column;
    }
    
    .step:not(:last-child):after {
        top: auto;
        right: auto;
        bottom: 0;
        left: 50%;
        transform: translateX(-50%);
        width: 1px;
        height: 1rem;
    }
}
</style>

<div class="container-fluid py-4">
    <!-- Botón de regreso -->
    <a href="main.php?page=proyecto_individual&id=<?php echo $proyecto_id; ?>#contratistas" class="back-button">
        <i class="fas fa-arrow-left"></i> Volver al Proyecto
    </a>
    
    <!-- Encabezado de la página -->
    <div class="page-header">
        <div>
            <h4>Agregar Contratista al Proyecto</h4>
            <p class="card-subtitle">
                Proyecto: <strong><?php echo htmlspecialchars($proyecto['nombre']); ?></strong> (<?php echo htmlspecialchars($proyecto['numero_pro_entidad']); ?>)
            </p>
        </div>
    </div>
    
    <!-- Mensajes de error o éxito -->
    <?php if (!empty($errores)): ?>
    <div class="alert alert-danger" role="alert">
        <i class="fas fa-exclamation-triangle me-2"></i>
        <ul class="mb-0">
            <?php foreach ($errores as $error): ?>
            <li><?php echo $error; ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
    <?php endif; ?>
    
    <?php if (!empty($mensaje_exito)): ?>
    <div class="alert alert-success" role="alert">
        <i class="fas fa-check-circle me-2"></i> <?php echo $mensaje_exito; ?>
    </div>
    <?php endif; ?>
    
    <!-- Indicador de pasos -->
    <div class="steps-indicator">
        <div class="step <?php echo ($modo == 'buscar' && !$contratista) ? 'active' : ($contratista ? 'completed' : ''); ?>">
            <div class="step-number">1</div>
            <div class="step-title">Identificar/Crear Contratista</div>
        </div>
        <div class="step <?php echo ($contratista) ? 'active' : ''; ?>">
            <div class="step-number">2</div>
            <div class="step-title">Asociar al Proyecto</div>
        </div>
    </div>
    
    <?php if ($modo == 'buscar' && !$contratista): ?>
    <!-- Paso 1: Buscar o Crear Contratista -->
    <div class="custom-card">
        <div class="custom-card-header">
            <h6 class="custom-card-title">Buscar Contratista Existente</h6>
        </div>
        <div class="custom-card-body">
            <form method="POST" action="">
                <div class="row">
                    <div class="col-md-8">
                        <div class="mb-3">
                            <label for="identificacion" class="form-label form-required">Número de Identificación</label>
                            <input type="text" class="form-control" id="identificacion" name="identificacion" required placeholder="Ingrese la identificación del contratista">
                            <div class="form-text">Ingrese el número de identificación sin puntos ni guiones</div>
                        </div>
                    </div>
                    <div class="col-md-4 d-flex align-items-end">
                        <button type="submit" name="buscar_contratista" class="btn btn-primary w-100">
                            <i class="fas fa-search"></i> Buscar Contratista
                        </button>
                    </div>
                </div>
            </form>
            
            <div class="mt-4 text-center">
                <p class="text-muted">¿El contratista no existe?</p>
                <a href="main.php?page=agregar_contratista&proyecto_id=<?php echo $proyecto_id; ?>&modo=crear" class="btn btn-secondary">
                    <i class="fas fa-user-plus"></i> Crear Nuevo Contratista
                </a>
            </div>
        </div>
    </div>
    <?php endif; ?>
    
    <?php if ($modo == 'crear' && !$contratista): ?>
    <!-- Formulario para crear nuevo contratista -->
    <div class="custom-card">
        <div class="custom-card-header">
            <h6 class="custom-card-title">Crear Nuevo Contratista</h6>
        </div>
        <div class="custom-card-body">
            <form method="POST" action="">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="identificacion" class="form-label form-required">Número de Identificación</label>
                        <input type="text" class="form-control" id="identificacion" name="identificacion" required 
                               value="<?php echo isset($_POST['identificacion']) ? htmlspecialchars($_POST['identificacion']) : ''; ?>">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="tipo_persona" class="form-label form-required">Tipo de Persona</label>
                        <select class="form-select" id="tipo_persona" name="tipo_persona" required>
                            <?php foreach ($tiposPersona as $tipo): ?>
                            <option value="<?php echo $tipo['codigo']; ?>" <?php echo (isset($_POST['tipo_persona']) && $_POST['tipo_persona'] == $tipo['codigo']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($tipo['descripcion']); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                
                <div id="persona-natural" class="row">
                    <div class="col-md-3 mb-3">
                        <label for="nombre1" class="form-label form-required">Primer Nombre</label>
                        <input type="text" class="form-control" id="nombre1" name="nombre1" 
                               value="<?php echo isset($_POST['nombre1']) ? htmlspecialchars($_POST['nombre1']) : ''; ?>">
                    </div>
                    <div class="col-md-3 mb-3">
                        <label for="nombre2" class="form-label">Segundo Nombre</label>
                        <input type="text" class="form-control" id="nombre2" name="nombre2" 
                               value="<?php echo isset($_POST['nombre2']) ? htmlspecialchars($_POST['nombre2']) : ''; ?>">
                    </div>
                    <div class="col-md-3 mb-3">
                        <label for="apellido1" class="form-label form-required">Primer Apellido</label>
                        <input type="text" class="form-control" id="apellido1" name="apellido1" 
                               value="<?php echo isset($_POST['apellido1']) ? htmlspecialchars($_POST['apellido1']) : ''; ?>">
                    </div>
                    <div class="col-md-3 mb-3">
                        <label for="apellido2" class="form-label">Segundo Apellido</label>
                        <input type="text" class="form-control" id="apellido2" name="apellido2" 
                               value="<?php echo isset($_POST['apellido2']) ? htmlspecialchars($_POST['apellido2']) : ''; ?>">
                    </div>
                </div>
                
                <div id="persona-juridica" class="row" style="display: none;">
                    <div class="col-md-12 mb-3">
                        <label for="razon_social" class="form-label form-required">Razón Social</label>
                        <input type="text" class="form-control" id="razon_social" name="nombre1" 
                               value="<?php echo isset($_POST['nombre1']) ? htmlspecialchars($_POST['nombre1']) : ''; ?>">
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label for="tipo_nacionalidad" class="form-label">Tipo de Nacionalidad</label>
                        <select class="form-select" id="tipo_nacionalidad" name="tipo_nacionalidad">
                            <?php foreach ($tiposNacionalidad as $tipo): ?>
                            <option value="<?php echo $tipo['codigo']; ?>" <?php echo (isset($_POST['tipo_nacionalidad']) && $_POST['tipo_nacionalidad'] == $tipo['codigo']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($tipo['descripcion']); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label for="fecha_nacimiento" class="form-label">Fecha de Nacimiento</label>
                        <input type="date" class="form-control" id="fecha_nacimiento" name="fecha_nacimiento" 
                               value="<?php echo isset($_POST['fecha_nacimiento']) ? htmlspecialchars($_POST['fecha_nacimiento']) : ''; ?>">
                    </div>
                    <div class="col-md-4 mb-3">
                        <label for="genero" class="form-label">Género</label>
                        <select class="form-select" id="genero" name="genero">
                            <option value="M" <?php echo (isset($_POST['genero']) && $_POST['genero'] == 'M') ? 'selected' : ''; ?>>Masculino</option>
                            <option value="F" <?php echo (isset($_POST['genero']) && $_POST['genero'] == 'F') ? 'selected' : ''; ?>>Femenino</option>
                            <option value="O" <?php echo (isset($_POST['genero']) && $_POST['genero'] == 'O') ? 'selected' : ''; ?>>Otro</option>
                        </select>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="direccion" class="form-label">Dirección</label>
                        <input type="text" class="form-control" id="direccion" name="direccion" 
                               value="<?php echo isset($_POST['direccion']) ? htmlspecialchars($_POST['direccion']) : ''; ?>">
                    </div>
                    <div class="col-md-3 mb-3">
                        <label for="tel_fijo" class="form-label">Teléfono Fijo</label>
                        <input type="text" class="form-control" id="tel_fijo" name="tel_fijo" 
                               value="<?php echo isset($_POST['tel_fijo']) ? htmlspecialchars($_POST['tel_fijo']) : ''; ?>">
                    </div>
                    <div class="col-md-3 mb-3">
                        <label for="tel_celular" class="form-label">Teléfono Celular</label>
                        <input type="text" class="form-control" id="tel_celular" name="tel_celular" 
                               value="<?php echo isset($_POST['tel_celular']) ? htmlspecialchars($_POST['tel_celular']) : ''; ?>">
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="correo" class="form-label form-required">Correo Electrónico</label>
                        <input type="email" class="form-control" id="correo" name="correo" required 
                               value="<?php echo isset($_POST['correo']) ? htmlspecialchars($_POST['correo']) : ''; ?>">
                    </div>
                </div>
                
                <div class="text-end">
                    <a href="main.php?page=agregar_contratista&proyecto_id=<?php echo $proyecto_id; ?>" class="btn btn-secondary me-2">
                        <i class="fas fa-arrow-left"></i> Volver a Búsqueda
                    </a>
                    <button type="submit" name="crear_contratista" class="btn btn-success">
                        <i class="fas fa-save"></i> Guardar Contratista
                    </button>
                </div>
            </form>
        </div>
    </div>
    <?php endif; ?>
    
    <?php if ($contratista): ?>
    <!-- Información del contratista encontrado o creado -->
    <div class="custom-card">
        <div class="custom-card-header">
            <h6 class="custom-card-title">Información del Contratista</h6>
        </div>
        <div class="custom-card-body">
            <div class="row">
                <div class="col-md-4 mb-3">
                    <p class="form-label">Identificación</p>
                    <p class="fw-bold"><?php echo htmlspecialchars($contratista['identificacion']); ?></p>
                </div>
                <div class="col-md-4 mb-3">
                    <p class="form-label">Tipo de Persona</p>
                    <p class="fw-bold">
                        <?php 
                        $tipo_persona_desc = '';
                        foreach ($tiposPersona as $tipo) {
                            if ($tipo['codigo'] == $contratista['tipo_persona']) {
                                $tipo_persona_desc = $tipo['descripcion'];
                                break;
                            }
                        }
                        echo htmlspecialchars($tipo_persona_desc);
                        ?>
                    </p>
                </div>
                <div class="col-md-4 mb-3">
                    <p class="form-label">Nombre / Razón Social</p>
                    <p class="fw-bold">
                        <?php 
                        if ($contratista['tipo_persona'] == 1) { // Persona Natural
                            echo htmlspecialchars(trim($contratista['nombre1'] . ' ' . $contratista['nombre2'] . ' ' . 
                                                       $contratista['apellido1'] . ' ' . $contratista['apellido2']));
                        } else { // Persona Jurídica
                            echo htmlspecialchars($contratista['nombre1']);
                        }
                        ?>
                    </p>
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-4 mb-3">
                    <p class="form-label">Correo Electrónico</p>
                    <p class="fw-bold">
                        <a href="mailto:<?php echo htmlspecialchars($contratista['correo']); ?>">
                            <?php echo htmlspecialchars($contratista['correo']); ?>
                        </a>
                    </p>
                </div>
                <div class="col-md-4 mb-3">
                    <p class="form-label">Teléfono</p>
                    <p class="fw-bold">
                        <?php 
                        $tel = !empty($contratista['tel_celular']) ? $contratista['tel_celular'] : $contratista['tel_fijo'];
                        echo !empty($tel) ? htmlspecialchars($tel) : 'No registrado';
                        ?>
                    </p>
                </div>
                <div class="col-md-4 mb-3">
                    <p class="form-label">Dirección</p>
                    <p class="fw-bold">
                        <?php echo !empty($contratista['direccion']) ? htmlspecialchars($contratista['direccion']) : 'No registrada'; ?>
                    </p>
                </div>
            </div>
            
            <hr>
            
            <!-- Formulario para asociar contratista al proyecto -->
            <h5 class="mt-4 mb-3">Asociar al Proyecto <?php echo htmlspecialchars($proyecto['numero_pro_entidad']); ?></h5>
            
            <form method="POST" action="">
                <input type="hidden" name="identificacion" value="<?php echo htmlspecialchars($contratistaID); ?>">
                
                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label for="numero_contrato" class="form-label form-required">Número de Contrato</label>
                        <input type="number" class="form-control" id="numero_contrato" name="numero_contrato" required min="1" 
                               value="<?php echo isset($_POST['numero_contrato']) ? htmlspecialchars($_POST['numero_contrato']) : '1'; ?>">
                    </div>
                    <div class="col-md-4 mb-3">
                        <label for="tipo_contrato" class="form-label form-required">Tipo de Contrato</label>
                        <select class="form-select" id="tipo_contrato" name="tipo_contrato" required>
                            <option value="">Seleccione un tipo...</option>
                            <?php foreach ($tiposContrato as $tipo): ?>
                            <option value="<?php echo $tipo['codigo']; ?>" <?php echo (isset($_POST['tipo_contrato']) && $_POST['tipo_contrato'] == $tipo['codigo']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($tipo['descripcion']); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label for="tipo_vinculacion" class="form-label">Tipo de Vinculación</label>
                        <select class="form-select" id="tipo_vinculacion" name="tipo_vinculacion">
                            <option value="">Seleccione un tipo...</option>
                            <?php foreach ($tiposVinculacion as $tipo): ?>
                            <option value="<?php echo $tipo['codigo']; ?>" <?php echo (isset($_POST['tipo_vinculacion']) && $_POST['tipo_vinculacion'] == $tipo['codigo']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($tipo['descripcion']); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label for="tipologia" class="form-label">Tipología</label>
                        <select class="form-select" id="tipologia" name="tipologia">
                            <option value="">Seleccione una tipología...</option>
                            <?php foreach ($tipologias as $tipo): ?>
                            <option value="<?php echo $tipo['codigo']; ?>" <?php echo (isset($_POST['tipologia']) && $_POST['tipologia'] == $tipo['codigo']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($tipo['descripcion']); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label for="facultad" class="form-label">Facultad</label>
                        <select class="form-select" id="facultad" name="facultad">
                            <option value="">Seleccione una facultad...</option>
                            <?php foreach ($facultades as $facultad): ?>
                            <option value="<?php echo $facultad['codigo']; ?>" <?php echo (isset($_POST['facultad']) && $_POST['facultad'] == $facultad['codigo']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($facultad['descripcion']); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label for="proyecto_curricular" class="form-label">Proyecto Curricular</label>
                        <select class="form-select" id="proyecto_curricular" name="proyecto_curricular">
                            <option value="">Seleccione un proyecto...</option>
                            <?php foreach ($proyectosCurriculares as $curricular): ?>
                            <option value="<?php echo $curricular['codigo']; ?>" <?php echo (isset($_POST['proyecto_curricular']) && $_POST['proyecto_curricular'] == $curricular['codigo']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($curricular['descripcion']); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label for="fecha_suscripcion" class="form-label">Fecha de Suscripción</label>
                        <input type="date" class="form-control" id="fecha_suscripcion" name="fecha_suscripcion" 
                               value="<?php echo isset($_POST['fecha_suscripcion']) ? htmlspecialchars($_POST['fecha_suscripcion']) : date('Y-m-d'); ?>">
                    </div>
                    <div class="col-md-4 mb-3">
                        <label for="fecha_inicio" class="form-label form-required">Fecha de Inicio</label>
                        <input type="date" class="form-control" id="fecha_inicio" name="fecha_inicio" required 
                               value="<?php echo isset($_POST['fecha_inicio']) ? htmlspecialchars($_POST['fecha_inicio']) : formatearFecha($proyecto['fecha_inicio']); ?>">
                    </div>
                    <div class="col-md-4 mb-3">
                        <label for="fecha_terminacion" class="form-label form-required">Fecha de Terminación</label>
                        <input type="date" class="form-control" id="fecha_terminacion" name="fecha_terminacion" required 
                               value="<?php echo isset($_POST['fecha_terminacion']) ? htmlspecialchars($_POST['fecha_terminacion']) : formatearFecha($proyecto['fecha_termina']); ?>">
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label for="valor" class="form-label">Valor del Contrato</label>
                        <div class="input-group">
                            <span class="input-group-text">$</span>
                            <input type="text" class="form-control" id="valor" name="valor" 
                                   value="<?php echo isset($_POST['valor']) ? htmlspecialchars($_POST['valor']) : '0'; ?>">
                        </div>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label for="tipo_proceso" class="form-label">Tipo de Proceso</label>
                        <select class="form-select" id="tipo_proceso" name="tipo_proceso">
                            <option value="">Seleccione un tipo...</option>
                            <!-- Aquí se pueden agregar opciones de tipos de proceso -->
                        </select>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-12 mb-3">
                        <label for="objeto" class="form-label form-required">Objeto / Rol del Contratista</label>
                        <textarea class="form-control" id="objeto" name="objeto" rows="4" required><?php echo isset($_POST['objeto']) ? htmlspecialchars($_POST['objeto']) : ''; ?></textarea>
                        <div class="form-text">Describa el objeto del contrato o el rol que desempeñará el contratista en el proyecto</div>
                    </div>
                </div>
                
                <div class="text-end">
                    <a href="main.php?page=proyecto_individual&id=<?php echo $proyecto_id; ?>#contratistas" class="btn btn-secondary me-2">
                        <i class="fas fa-times"></i> Cancelar
                    </a>
                    <button type="submit" name="asociar_contratista" class="btn btn-success">
                        <i class="fas fa-link"></i> Asociar al Proyecto
                    </button>
                </div>
            </form>
        </div>
    </div>
    <?php endif; ?>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Función para manejar el tipo de persona
    const tipoPersonaSelect = document.getElementById('tipo_persona');
    const personaNaturalDiv = document.getElementById('persona-natural');
    const personaJuridicaDiv = document.getElementById('persona-juridica');
    
    if (tipoPersonaSelect && personaNaturalDiv && personaJuridicaDiv) {
        // Función para mostrar/ocultar campos según el tipo de persona
        function togglePersonaFields() {
            const tipoPersona = parseInt(tipoPersonaSelect.value);
            
            if (tipoPersona === 1) { // Persona Natural
                personaNaturalDiv.style.display = 'flex';
                personaJuridicaDiv.style.display = 'none';
                
                // Hacer requeridos los campos de persona natural
                document.getElementById('nombre1').required = true;
                document.getElementById('apellido1').required = true;
                document.getElementById('razon_social').required = false;
            } else { // Persona Jurídica u otro tipo
                personaNaturalDiv.style.display = 'none';
                personaJuridicaDiv.style.display = 'flex';
                
                // Hacer requeridos los campos de persona jurídica
                document.getElementById('nombre1').required = false;
                document.getElementById('apellido1').required = false;
                document.getElementById('razon_social').required = true;
            }
        }
        
        // Aplicar al cargar la página
        togglePersonaFields();
        
        // Aplicar cuando cambie el tipo de persona
        tipoPersonaSelect.addEventListener('change', togglePersonaFields);
    }
    
    // Formatear campo de valor con separadores de miles
    const valorInput = document.getElementById('valor');
    if (valorInput) {
        // Formatear al cargar
        formatNumber(valorInput);
        
        // Formatear cuando cambie
        valorInput.addEventListener('input', function() {
            formatNumber(this);
        });
        
        // Formatear cuando pierda el foco
        valorInput.addEventListener('blur', function() {
            formatNumber(this);
        });
    }
    
    // Función para formatear números con separadores de miles
    function formatNumber(input) {
        // Obtener solo dígitos y punto decimal
        let value = input.value.replace(/[^\d.]/g, '');
        
        // Verificar si hay un valor para formatear
        if (value !== '') {
            // Separar parte entera y decimal
            let parts = value.split('.');
            let integerPart = parts[0];
            
            // Formatear parte entera con separadores de miles
            integerPart = integerPart.replace(/\B(?=(\d{3})+(?!\d))/g, ',');
            
            // Reconstruir el valor
            value = integerPart + (parts.length > 1 ? '.' + parts[1] : '');
            
            // Actualizar el campo
            input.value = value;
        }
    }
    
    // Eliminar formato de número antes de enviar el formulario
    const forms = document.querySelectorAll('form');
    forms.forEach(form => {
        form.addEventListener('submit', function() {
            if (valorInput) {
                valorInput.value = valorInput.value.replace(/,/g, '');
            }
        });
    });
});
</script>