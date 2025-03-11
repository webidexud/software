<?php
/**
 * Página para editar los datos completos de un proyecto existente
 * Incluye relaciones con otras tablas
 */

// Verificar que se haya proporcionado un ID
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    // Redirigir a la lista de proyectos si no se proporciona un ID válido
    header('Location: main.php?page=proyecto');
    exit;
}

$proyecto_id = intval($_GET['id']);

// Incluir el modelo de proyecto si aún no está incluido
if (!function_exists('obtenerProyectoDetalle')) {
    if (file_exists('models/proyecto_model.php')) {
        include_once 'models/proyecto_model.php';
    } else {
        echo '<div class="alert alert-danger">Error: No se puede encontrar el modelo de proyectos.</div>';
        exit;
    }
}

// Obtener datos del proyecto
$proyecto = obtenerProyectoDetalle($proyecto_id);

// Si no se encuentra el proyecto, mostrar mensaje de error
if (!$proyecto) {
    echo '<div class="alert alert-danger" role="alert">El proyecto solicitado no existe o no se puede acceder a él en este momento.</div>';
    exit;
}

// Función para formatear fechas para inputs de tipo date
function formatearFechaInput($fecha) {
    if (!$fecha) return '';
    
    try {
        $date = new DateTime($fecha);
        return $date->format('Y-m-d');
    } catch (Exception $e) {
        return '';
    }
}

// Función para formatear valores monetarios con separadores de miles
function formatearMoneda($valor) {
    if (!$valor) return '';
    
    return number_format($valor, 0, '.', ',');
}

// Cargar datos maestros para los selectores
$tiposProyecto = obtenerTiposProyecto();
$modalidades = obtenerModalidades();
$financiaciones = obtenerFinanciaciones();
$situaciones = obtenerSituaciones();
$unidades = obtenerUnidades();
$entidades = obtenerEntidades();
$bancos = obtenerBancos();

// Obtener entidades asociadas al proyecto
$entidadesProyecto = obtenerEntidadesProyecto($proyecto_id);

// Inicializar variables para mensajes
$errores = [];
$mensaje_exito = '';

// Procesar el formulario si se envió
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Recoger los datos del formulario
    $nombre = isset($_POST['nombre']) ? trim($_POST['nombre']) : '';
    $objeto = isset($_POST['objeto']) ? trim($_POST['objeto']) : '';
    $fecha_inicio = isset($_POST['fecha_inicio']) ? $_POST['fecha_inicio'] : '';
    $fecha_termina = isset($_POST['fecha_termina']) ? $_POST['fecha_termina'] : '';
    $fecha_suscripcion = isset($_POST['fecha_suscripcion']) ? $_POST['fecha_suscripcion'] : '';
    $tipo_proyecto = isset($_POST['tipo_proyecto']) ? intval($_POST['tipo_proyecto']) : $proyecto['tipo_proyecto'];
    $modalidad = isset($_POST['modalidad']) ? intval($_POST['modalidad']) : $proyecto['modalidad'];
    $financiacion = isset($_POST['financiacion']) ? intval($_POST['financiacion']) : $proyecto['financiacion'];
    $situacion = isset($_POST['situacion']) ? intval($_POST['situacion']) : $proyecto['situacion'];
    $unidad = isset($_POST['unidad']) ? intval($_POST['unidad']) : $proyecto['unidad'];
    $valor = isset($_POST['valor']) ? str_replace(',', '', $_POST['valor']) : $proyecto['valor'];
    $aporte_ud = isset($_POST['aporte_ud']) ? str_replace(',', '', $_POST['aporte_ud']) : $proyecto['aporte_ud'];
    $observaciones = isset($_POST['observaciones']) ? trim($_POST['observaciones']) : '';
    $entidad_principal = isset($_POST['entidad_principal']) ? intval($_POST['entidad_principal']) : 0;
    $email_main = isset($_POST['email_main']) ? trim($_POST['email_main']) : '';
    $email_secundarios = isset($_POST['email_secundarios']) ? trim($_POST['email_secundarios']) : '';
    $codigo_tesoral = isset($_POST['codigo_tesoral']) ? trim($_POST['codigo_tesoral']) : '';
    $codigo_bancario = isset($_POST['codigo_bancario']) ? trim($_POST['codigo_bancario']) : '';
    $tipo_cuenta = isset($_POST['tipo_cuenta']) ? trim($_POST['tipo_cuenta']) : '';
    $numero_cuenta = isset($_POST['numero_cuenta']) ? trim($_POST['numero_cuenta']) : '';
    $referencia_ud = isset($_POST['referencia_ud']) ? trim($_POST['referencia_ud']) : '';
    $supervisor = isset($_POST['supervisor']) ? trim($_POST['supervisor']) : '';
    $enlace_secop = isset($_POST['enlace_secop']) ? trim($_POST['enlace_secop']) : '';
    $acto_administrativo = isset($_POST['acto_administrativo']) ? trim($_POST['acto_administrativo']) : '';
    $beneficiarios = isset($_POST['beneficiarios']) ? str_replace(',', '', $_POST['beneficiarios']) : 0;
    $datos_adicionales = isset($_POST['datos_adicionales']) ? trim($_POST['datos_adicionales']) : '';
    
    // Validar datos
    if (empty($nombre)) {
        $errores[] = 'El nombre del proyecto es obligatorio';
    }
    if (empty($objeto)) {
        $errores[] = 'El objeto del proyecto es obligatorio';
    }
    if (empty($fecha_inicio)) {
        $errores[] = 'La fecha de inicio es obligatoria';
    }
    if (empty($fecha_termina)) {
        $errores[] = 'La fecha de terminación es obligatoria';
    }
    if (!empty($fecha_inicio) && !empty($fecha_termina) && $fecha_inicio > $fecha_termina) {
        $errores[] = 'La fecha de inicio no puede ser posterior a la fecha de terminación';
    }
    if ($valor <= 0) {
        $errores[] = 'El valor del proyecto debe ser mayor que cero';
    }
    if ($entidad_principal <= 0) {
        $errores[] = 'Debe seleccionar una entidad contratante principal';
    }
    
    // Si no hay errores, actualizar el proyecto
    if (empty($errores)) {
        if (function_exists('actualizarProyectoCompleto')) {
            // Estructura con todos los datos del proyecto
            $datosProyecto = [
                'id' => $proyecto_id,
                'anio_pro' => $proyecto['anio_pro'],
                'nombre' => $nombre,
                'objeto' => $objeto,
                'tipo_proyecto' => $tipo_proyecto,
                'modalidad' => $modalidad,
                'financiacion' => $financiacion,
                'fecha_inicio' => $fecha_inicio,
                'fecha_termina' => $fecha_termina,
                'fecha_suscripcion' => $fecha_suscripcion,
                'situacion' => $situacion,
                'unidad' => $unidad,
                'valor' => $valor,
                'aporte_ud' => $aporte_ud,
                'observaciones' => $observaciones,
                'entidad_principal' => $entidad_principal,
                'email_main' => $email_main,
                'email_secundarios' => $email_secundarios,
                'codigo_tesoral' => $codigo_tesoral,
                'codigo_bancario' => $codigo_bancario,
                'tipo_cuenta' => $tipo_cuenta,
                'numero_cuenta' => $numero_cuenta,
                'referencia_ud' => $referencia_ud,
                'supervisor' => $supervisor,
                'enlace_secop' => $enlace_secop,
                'acto_administrativo' => $acto_administrativo,
                'beneficiarios' => $beneficiarios,
                'datos_adicionales' => $datos_adicionales
            ];
            
            $resultado = actualizarProyectoCompleto($datosProyecto);
            
            if ($resultado) {
                $mensaje_exito = 'El proyecto se ha actualizado correctamente.';
                // Recargar los datos del proyecto
                $proyecto = obtenerProyectoDetalle($proyecto_id);
                // Recargar entidades asociadas
                $entidadesProyecto = obtenerEntidadesProyecto($proyecto_id);

                // Redirigir a la página del proyecto después de un breve retraso
                header("refresh:2;url=main.php?page=proyecto_individual&id=" . $proyecto_id);
            } else {
                $errores[] = 'Error al actualizar el proyecto en la base de datos';
            }
        } else {
            $errores[] = 'La función para actualizar proyectos no está disponible';
        }
    }
}

// Obtener nombres descriptivos para los selectores
$tipoProyectoNombre = '';
foreach ($tiposProyecto as $tipo) {
    if ($tipo['codigo'] == $proyecto['tipo_proyecto']) {
        $tipoProyectoNombre = $tipo['codigo'] . ' - ' . $tipo['descripcion'];
        break;
    }
}

$modalidadNombre = '';
foreach ($modalidades as $mod) {
    if ($mod['codigo'] == $proyecto['modalidad']) {
        $modalidadNombre = $mod['codigo'] . ' - ' . $mod['descripcion'];
        break;
    }
}

$situacionNombre = '';
foreach ($situaciones as $sit) {
    if ($sit['codigo'] == $proyecto['situacion']) {
        $situacionNombre = $sit['codigo'] . ' - ' . $sit['descripcion'];
        break;
    }
}

$financiacionNombre = '';
foreach ($financiaciones as $fin) {
    if ($fin['codigo'] == $proyecto['financiacion']) {
        $financiacionNombre = $fin['codigo'] . ' - ' . $fin['descripcion'];
        break;
    }
}

$unidadNombre = '';
if (!empty($proyecto['unidad'])) {
    foreach ($unidades as $uni) {
        if ($uni['codigo'] == $proyecto['unidad']) {
            $unidadNombre = $uni['codigo'] . ' - ' . $uni['descripcion'];
            break;
        }
    }
}

$bancoNombre = '';
if (!empty($proyecto['codigo_bancario'])) {
    foreach ($bancos as $banco) {
        if ($banco['codigo'] == $proyecto['codigo_bancario']) {
            $bancoNombre = $banco['codigo'] . ' - ' . $banco['descripcion'];
            break;
        }
    }
}

$entidadNombre = '';
if (!empty($entidadesProyecto)) {
    foreach ($entidades as $ent) {
        if ($ent['codigo'] == $entidadesProyecto[0]['entidad']) {
            $entidadNombre = $ent['codigo'] . ' - ' . $ent['descripcion'];
            break;
        }
    }
}
?>

<!-- CSS personalizado para esta página -->
<style>
:root {
    --primary: #5e72e4;
    --primary-light: #8a98eb;
    --primary-dark: #324cdd;
    --secondary: #8392ab;
    --success: #2dce89;
    --info: #11cdef;
    --warning: #fb6340;
    --danger: #f5365c;
    --light: #e9ecef;
    --dark: #344767;
    --neutral: #f8f9fa;
    --white: #ffffff;
    
    --shadow-sm: 0 2px 5px rgba(0, 0, 0, 0.08);
    --shadow-md: 0 4px 12px rgba(0, 0, 0, 0.12);
    --shadow-lg: 0 8px 26px rgba(0, 0, 0, 0.15);
    
    --transition: all 0.3s ease;
    
    --card-border-radius: 12px;
    --button-border-radius: 8px;
    --input-border-radius: 8px;
}

.card {
    border-radius: var(--card-border-radius);
    border: none;
    box-shadow: var(--shadow-sm);
    transition: var(--transition);
    overflow: hidden;
    margin-bottom: 24px;
}

.card:hover {
    box-shadow: var(--shadow-md);
}

.card-header {
    background-color: var(--white);
    border-bottom: 1px solid var(--light);
    padding: 18px 20px;
}

.edit-project-header {
    background: linear-gradient(135deg, var(--primary), var(--primary-dark));
    color: white;
    padding: 20px 25px;
    border-radius: 12px;
    margin-bottom: 25px;
    box-shadow: var(--shadow-md);
    position: relative;
    overflow: hidden;
}

.edit-project-header::before {
    content: "";
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: 
        radial-gradient(circle at 20% 150%, rgba(67, 87, 201, 0.8) 0%, rgba(94, 114, 228, 0) 30%),
        radial-gradient(circle at 80% -20%, rgba(103, 119, 239, 0.8) 0%, rgba(94, 114, 228, 0) 30%);
    opacity: 0.8;
    z-index: 0;
}

.edit-project-header .header-content {
    position: relative;
    z-index: 1;
    display: flex;
    align-items: center;
    gap: 20px;
}

.header-icon {
    background: rgba(255, 255, 255, 0.15);
    width: 52px;
    height: 52px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.4rem;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
    border: 1px solid rgba(255, 255, 255, 0.2);
}

.header-title {
    flex-grow: 1;
}

.header-title h4 {
    font-size: 1.6rem;
    font-weight: 700;
    margin-bottom: 6px;
}

.header-title p {
    opacity: 0.9;
    font-weight: 400;
    font-size: 0.95rem;
    margin-bottom: 0;
}

.header-actions {
    display: flex;
    gap: 10px;
}

.nav-tabs {
    border-bottom: 1px solid var(--light);
    padding: 0 16px;
}

.nav-tabs .nav-link {
    color: var(--secondary);
    font-weight: 600;
    padding: 12px 16px;
    border: none;
    border-radius: 0;
    position: relative;
    transition: var(--transition);
    font-size: 0.95rem;
}

.nav-tabs .nav-link:hover {
    color: var(--primary);
    background-color: rgba(94, 114, 228, 0.05);
}

.nav-tabs .nav-link.active {
    color: var(--primary);
    border-bottom: 2px solid var(--primary);
    background-color: transparent;
}

.nav-tabs .nav-link i {
    margin-right: 7px;
    transition: var(--transition);
}

.nav-tabs .nav-link:hover i,
.nav-tabs .nav-link.active i {
    transform: translateY(-1px);
}

.tab-content {
    padding: 24px 20px;
}

.form-label {
    font-size: 0.8rem;
    font-weight: 600;
    color: var(--dark);
    margin-bottom: 6px;
    display: block;
}

.form-control, .form-select {
    border-radius: var(--input-border-radius);
    padding: 10px 12px;
    border-color: #e2e8f0;
    box-shadow: none !important;
    transition: var(--transition);
    font-size: 0.9rem;
}

.form-control:focus, .form-select:focus {
    border-color: var(--primary-light);
    box-shadow: 0 0 0 3px rgba(94, 114, 228, 0.1) !important;
}

.form-control::placeholder {
    color: #a0aec0;
    opacity: 0.6;
}

.form-text {
    font-size: 0.75rem;
    color: var(--secondary);
    margin-top: 4px;
}

.detail-section {
    margin-bottom: 24px;
}

.detail-section:last-child {
    margin-bottom: 0;
}

.btn-primary {
    background-color: var(--primary);
    border-color: var(--primary);
    border-radius: var(--button-border-radius);
    padding: 10px 20px;
    font-weight: 500;
    letter-spacing: 0.3px;
    transition: var(--transition);
    display: inline-flex;
    align-items: center;
    gap: 8px;
}

.btn-primary:hover, .btn-primary:focus {
    background-color: var(--primary-dark);
    border-color: var(--primary-dark);
    box-shadow: 0 4px 10px rgba(94, 114, 228, 0.3);
    transform: translateY(-1px);
}

.btn-secondary {
    background-color: var(--white);
    border-color: #e2e8f0;
    color: var(--dark);
    border-radius: var(--button-border-radius);
    padding: 10px 20px;
    font-weight: 500;
    transition: var(--transition);
    display: inline-flex;
    align-items: center;
    gap: 8px;
}

.btn-secondary:hover, .btn-secondary:focus {
    background-color: var(--light);
    border-color: #cbd5e1;
    color: var(--dark);
    box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
    transform: translateY(-1px);
}

.alert {
    border-radius: 8px;
    padding: 16px;
    margin-bottom: 24px;
    border: none;
    display: flex;
    align-items: flex-start;
}

.alert-icon {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 24px;
    height: 24px;
    border-radius: 50%;
    margin-right: 16px;
    flex-shrink: 0;
}

.alert-danger {
    background-color: rgba(245, 54, 92, 0.1);
    color: var(--danger);
}

.alert-danger .alert-icon {
    background-color: var(--danger);
    color: white;
}

.alert-success {
    background-color: rgba(45, 206, 137, 0.1);
    color: var(--success);
}

.alert-success .alert-icon {
    background-color: var(--success);
    color: white;
}

.alert-content {
    flex-grow: 1;
}

.alert ul {
    padding-left: 20px;
    margin-bottom: 0;
}

.alert ul li:not(:last-child) {
    margin-bottom: 8px;
}

.mb-3 {
    margin-bottom: 1.25rem !important;
}

.input-group-text {
    border-radius: var(--input-border-radius) 0 0 var(--input-border-radius);
    border-color: #e2e8f0;
    background-color: var(--neutral);
    color: var(--secondary);
}

.input-group>.form-control {
    border-radius: 0 var(--input-border-radius) var(--input-border-radius) 0;
}

textarea.form-control {
    min-height: 120px;
}

.object-textarea {
    min-height: 180px !important;
}

/* Estilo para los campos editables con fondo sutil */
.field-editable {
    background-color: rgba(94, 114, 228, 0.03);
    transition: background-color 0.3s ease;
}

.field-editable:focus {
    background-color: rgba(94, 114, 228, 0.07);
}

/* Animaciones */
@keyframes fadeIn {
    from { opacity: 0; transform: translateY(10px); }
    to { opacity: 1; transform: translateY(0); }
}

.animate-fade-in {
    animation: fadeIn 0.3s ease forwards;
}

/* Responsividad */
@media (max-width: 768px) {
    .edit-project-header {
        padding: 15px;
        margin-bottom: 15px;
    }
    
    .header-icon {
        width: 42px;
        height: 42px;
        font-size: 1.2rem;
    }
    
    .header-title h4 {
        font-size: 1.4rem;
    }
    
    .nav-tabs .nav-link {
        padding: 10px 8px;
        font-size: 0.85rem;
    }
    
    .nav-tabs .nav-link i {
        margin-right: 4px;
    }
    
    .tab-content {
        padding: 15px;
    }
}
</style>

<div class="container-fluid py-4">
    <!-- Encabezado de la página -->
    <div class="edit-project-header animate-fade-in">
        <div class="header-content">
            <div class="header-icon">
                <i class="fas fa-edit"></i>
            </div>
            <div class="header-title">
                <h4>Editar Proyecto</h4>
                <p>
                    Actualizando información de proyecto: <strong><?php echo htmlspecialchars($proyecto['numero_pro_entidad']); ?></strong>
                </p>
            </div>
            <div class="header-actions">
                <a href="main.php?page=proyecto_individual&id=<?php echo $proyecto_id; ?>" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Volver a Detalles
                </a>
            </div>
        </div>
    </div>
    
    <!-- Mensajes de error o éxito -->
    <?php if (!empty($errores)): ?>
    <div class="alert alert-danger animate-fade-in" role="alert">
        <span class="alert-icon">
            <i class="fas fa-exclamation-triangle"></i>
        </span>
        <div class="alert-content">
            <h6 class="fw-bold mb-1">Error al actualizar el proyecto</h6>
            <ul>
                <?php foreach ($errores as $error): ?>
                <li><?php echo $error; ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>
    <?php endif; ?>
    
    <?php if (!empty($mensaje_exito)): ?>
    <div class="alert alert-success animate-fade-in" role="alert">
        <span class="alert-icon">
            <i class="fas fa-check-circle"></i>
        </span>
        <div class="alert-content">
            <h6 class="fw-bold mb-1">Proyecto actualizado</h6>
            <p class="mb-0"><?php echo $mensaje_exito; ?></p>
            <p class="mb-0 mt-1"><small>Redireccionando a la vista de detalles...</small></p>
        </div>
    </div>
    <?php endif; ?>
    
    <!-- Formulario de edición -->
    <div class="row">
        <div class="col-12">
            <div class="card animate-fade-in" style="animation-delay: 0.1s;">
                <div class="card-body p-0">
                    <form method="POST" action="">
                        <ul class="nav nav-tabs" id="myTab" role="tablist">
                            <li class="nav-item" role="presentation">
                                <button class="nav-link active" id="general-tab" data-bs-toggle="tab" data-bs-target="#general" type="button" role="tab" aria-controls="general" aria-selected="true">
                                    <i class="fas fa-info-circle"></i> Información General
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="financiera-tab" data-bs-toggle="tab" data-bs-target="#financiera" type="button" role="tab" aria-controls="financiera" aria-selected="false">
                                    <i class="fas fa-money-bill-wave"></i> Información Financiera
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="contacto-tab" data-bs-toggle="tab" data-bs-target="#contacto" type="button" role="tab" aria-controls="contacto" aria-selected="false">
                                    <i class="fas fa-address-book"></i> Contactos
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="adicional-tab" data-bs-toggle="tab" data-bs-target="#adicional" type="button" role="tab" aria-controls="adicional" aria-selected="false">
                                    <i class="fas fa-clipboard-list"></i> Información Adicional
                                </button>
                            </li>
                        </ul>
                        <div class="tab-content" id="myTabContent">
                            <!-- Pestaña de Información General -->
                            <div class="tab-pane fade show active" id="general" role="tabpanel" aria-labelledby="general-tab">
                                <div class="row">
                                    <div class="col-md-4 mb-3">
                                        <label for="numero_pro" class="form-label">Código del Proyecto</label>
                                        <input type="text" class="form-control" id="numero_pro" value="<?php echo $proyecto['numero_pro']; ?>" disabled>
                                        <div class="form-text">Este campo no se puede modificar</div>
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label for="numero_pro_entidad" class="form-label">Número de Referencia</label>
                                        <input type="text" class="form-control" id="numero_pro_entidad" value="<?php echo htmlspecialchars($proyecto['numero_pro_entidad']); ?>" disabled>
                                        <div class="form-text">Este campo no se puede modificar</div>
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label for="anio_pro" class="form-label">Año</label>
                                        <input type="text" class="form-control" id="anio_pro" value="<?php echo $proyecto['anio_pro']; ?>" disabled>
                                        <div class="form-text">Este campo no se puede modificar</div>
                                    </div>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-12 mb-3">
                                        <label for="nombre" class="form-label">Nombre del Proyecto *</label>
                                        <input type="text" class="form-control field-editable" id="nombre" name="nombre" required maxlength="800" value="<?php echo htmlspecialchars($proyecto['nombre']); ?>">
                                        <div class="form-text">Nombre completo y descriptivo del proyecto (máximo 800 caracteres)</div>
                                    </div>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-12 mb-3">
                                        <label for="objeto" class="form-label">Objeto del Proyecto *</label>
                                        <textarea class="form-control field-editable object-textarea" id="objeto" name="objeto" rows="5" required maxlength="1800"><?php echo htmlspecialchars($proyecto['objeto']); ?></textarea>
                                        <div class="form-text">Descripción detallada del objetivo del proyecto (máximo 1800 caracteres)</div>
                                    </div>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-4 mb-3">
                                        <label for="fecha_inicio" class="form-label">Fecha de Inicio *</label>
                                        <input type="date" class="form-control field-editable" id="fecha_inicio" name="fecha_inicio" required value="<?php echo formatearFechaInput($proyecto['fecha_inicio']); ?>">
                                        <div class="form-text">Fecha en que inicia el proyecto</div>
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label for="fecha_termina" class="form-label">Fecha de Terminación *</label>
                                        <input type="date" class="form-control field-editable" id="fecha_termina" name="fecha_termina" required value="<?php echo formatearFechaInput($proyecto['fecha_termina']); ?>">
                                        <div class="form-text">Fecha en que termina el proyecto</div>
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label for="fecha_suscripcion" class="form-label">Fecha de Suscripción</label>
                                        <input type="date" class="form-control field-editable" id="fecha_suscripcion" name="fecha_suscripcion" value="<?php echo formatearFechaInput($proyecto['fecha_suscripcion']); ?>">
                                        <div class="form-text">Fecha en que se firmó el proyecto</div>
                                    </div>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-4 mb-3">
                                        <label for="tipo_proyecto" class="form-label">Tipo de Proyecto</label>
                                        <select class="form-select field-editable" id="tipo_proyecto" name="tipo_proyecto">
                                            <option value="">Seleccione...</option>
                                            <?php foreach ($tiposProyecto as $tipo): ?>
                                            <option value="<?php echo $tipo['codigo']; ?>" <?php echo $proyecto['tipo_proyecto'] == $tipo['codigo'] ? 'selected' : ''; ?>>
                                                <?php echo $tipo['codigo'] . ' - ' . htmlspecialchars($tipo['descripcion']); ?>
                                            </option>
                                            <?php endforeach; ?>
                                        </select>
                                        <div class="form-text">Categoría o tipo de proyecto</div>
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label for="modalidad" class="form-label">Modalidad</label>
                                        <select class="form-select field-editable" id="modalidad" name="modalidad">
                                            <option value="">Seleccione...</option>
                                            <?php foreach ($modalidades as $mod): ?>
                                            <option value="<?php echo $mod['codigo']; ?>" <?php echo $proyecto['modalidad'] == $mod['codigo'] ? 'selected' : ''; ?>>
                                                <?php echo $mod['codigo'] . ' - ' . htmlspecialchars($mod['descripcion']); ?>
                                            </option>
                                            <?php endforeach; ?>
                                        </select>
                                        <div class="form-text">Modalidad de contratación</div>
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label for="situacion" class="form-label">Situación</label>
                                        <select class="form-select field-editable" id="situacion" name="situacion">
                                            <option value="">Seleccione...</option>
                                            <?php foreach ($situaciones as $sit): ?>
                                            <option value="<?php echo $sit['codigo']; ?>" <?php echo $proyecto['situacion'] == $sit['codigo'] ? 'selected' : ''; ?>>
                                                <?php echo $sit['codigo'] . ' - ' . htmlspecialchars($sit['descripcion']); ?>
                                            </option>
                                            <?php endforeach; ?>
                                        </select>
                                        <div class="form-text">Estado actual del proyecto</div>
                                    </div>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="unidad" class="form-label">Unidad Ejecutora</label>
                                        <select class="form-select field-editable" id="unidad" name="unidad">
                                            <option value="">Seleccione...</option>
                                            <?php foreach ($unidades as $uni): ?>
                                            <option value="<?php echo $uni['codigo']; ?>" <?php echo $proyecto['unidad'] == $uni['codigo'] ? 'selected' : ''; ?>>
                                                <?php echo $uni['codigo'] . ' - ' . htmlspecialchars($uni['descripcion']); ?>
                                            </option>
                                            <?php endforeach; ?>
                                        </select>
                                        <div class="form-text">Unidad que ejecuta el proyecto</div>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="entidad_principal" class="form-label">Entidad Contratante Principal *</label>
                                        <select class="form-select field-editable" id="entidad_principal" name="entidad_principal" required>
                                            <option value="">Seleccione...</option>
                                            <?php foreach ($entidades as $ent): ?>
                                            <option value="<?php echo $ent['codigo']; ?>" 
                                                <?php 
                                                // Verificar si esta entidad está asociada como principal
                                                $selected = false;
                                                foreach ($entidadesProyecto as $entProy) {
                                                    if ($entProy['entidad'] == $ent['codigo']) {
                                                        $selected = true;
                                                        break;
                                                    }
                                                }
                                                echo $selected ? 'selected' : '';
                                                ?>
                                            >
                                                <?php echo $ent['codigo'] . ' - ' . htmlspecialchars($ent['descripcion']); ?>
                                            </option>
                                            <?php endforeach; ?>
                                        </select>
                                        <div class="form-text">Entidad que contrata el proyecto</div>
                                    </div>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-12 mb-3">
                                        <label for="observaciones" class="form-label">Observaciones</label>
                                        <textarea class="form-control field-editable" id="observaciones" name="observaciones" rows="3" maxlength="4000"><?php echo htmlspecialchars($proyecto['observaciones'] ?: ''); ?></textarea>
                                        <div class="form-text">Observaciones generales del proyecto (máximo 4000 caracteres)</div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Pestaña de Información Financiera -->
                            <div class="tab-pane fade" id="financiera" role="tabpanel" aria-labelledby="financiera-tab">
                                <div class="row">
                                    <div class="col-md-4 mb-3">
                                        <label for="valor" class="form-label">Valor Total *</label>
                                        <div class="input-group">
                                            <span class="input-group-text">$</span>
                                            <input type="text" class="form-control field-editable" id="valor" name="valor" required value="<?php echo formatearMoneda($proyecto['valor']); ?>">
                                        </div>
                                        <div class="form-text">Valor total del proyecto en pesos colombianos</div>
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label for="aporte_ud" class="form-label">Aporte UD</label>
                                        <div class="input-group">
                                            <span class="input-group-text">$</span>
                                            <input type="text" class="form-control field-editable" id="aporte_ud" name="aporte_ud" value="<?php echo formatearMoneda($proyecto['aporte_ud']); ?>">
                                        </div>
                                        <div class="form-text">Aporte de la Universidad Distrital</div>
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label for="financiacion" class="form-label">Tipo de Financiación</label>
                                        <select class="form-select field-editable" id="financiacion" name="financiacion">
                                            <option value="">Seleccione...</option>
                                            <?php foreach ($financiaciones as $fin): ?>
                                            <option value="<?php echo $fin['codigo']; ?>" <?php echo $proyecto['financiacion'] == $fin['codigo'] ? 'selected' : ''; ?>>
                                                <?php echo $fin['codigo'] . ' - ' . htmlspecialchars($fin['descripcion']); ?>
                                            </option>
                                            <?php endforeach; ?>
                                        </select>
                                        <div class="form-text">Tipo de financiación del proyecto</div>
                                    </div>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="codigo_tesoral" class="form-label">Código Tesoral</label>
                                        <input type="text" class="form-control field-editable" id="codigo_tesoral" name="codigo_tesoral" maxlength="20" value="<?php echo htmlspecialchars($proyecto['codigo_tesoral'] ?: ''); ?>">
                                        <div class="form-text">Código de identificación tesoral (máximo 20 caracteres)</div>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="codigo_bancario" class="form-label">Banco</label>
                                        <select class="form-select field-editable" id="codigo_bancario" name="codigo_bancario">
                                            <option value="">Seleccione...</option>
                                            <?php foreach ($bancos as $banco): ?>
                                            <option value="<?php echo $banco['codigo']; ?>" <?php echo $proyecto['codigo_bancario'] == $banco['codigo'] ? 'selected' : ''; ?>>
                                                <?php echo $banco['codigo'] . ' - ' . htmlspecialchars($banco['descripcion']); ?>
                                            </option>
                                            <?php endforeach; ?>
                                        </select>
                                        <div class="form-text">Entidad bancaria asociada al proyecto</div>
                                    </div>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="tipo_cuenta" class="form-label">Tipo de Cuenta</label>
                                        <select class="form-select field-editable" id="tipo_cuenta" name="tipo_cuenta">
                                            <option value="">Seleccione...</option>
                                            <option value="AHORROS" <?php echo $proyecto['tipo_cuenta'] == 'AHORROS' ? 'selected' : ''; ?>>AHORROS</option>
                                            <option value="CORRIENTE" <?php echo $proyecto['tipo_cuenta'] == 'CORRIENTE' ? 'selected' : ''; ?>>CORRIENTE</option>
                                        </select>
                                        <div class="form-text">Tipo de cuenta bancaria</div>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="numero_cuenta" class="form-label">Número de Cuenta</label>
                                        <input type="text" class="form-control field-editable" id="numero_cuenta" name="numero_cuenta" maxlength="20" value="<?php echo htmlspecialchars($proyecto['numero_cuenta'] ?: ''); ?>">
                                        <div class="form-text">Número de cuenta bancaria (máximo 20 caracteres)</div>
                                    </div>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-12 mb-3">
                                        <label for="referencia_ud" class="form-label">Referencia UD</label>
                                        <input type="text" class="form-control field-editable" id="referencia_ud" name="referencia_ud" maxlength="20" value="<?php echo htmlspecialchars($proyecto['referencia_ud'] ?: ''); ?>">
                                        <div class="form-text">Referencia interna de la Universidad Distrital (máximo 20 caracteres)</div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Pestaña de Contactos -->
                            <div class="tab-pane fade" id="contacto" role="tabpanel" aria-labelledby="contacto-tab">
                                <div class="row">
                                    <div class="col-md-12 mb-3">
                                        <label for="supervisor" class="form-label">Supervisor</label>
                                        <input type="text" class="form-control field-editable" id="supervisor" name="supervisor" maxlength="120" value="<?php echo htmlspecialchars($proyecto['supervisor'] ?: ''); ?>">
                                        <div class="form-text">Nombre del supervisor del proyecto (máximo 120 caracteres)</div>
                                    </div>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="email_main" class="form-label">Correo Principal</label>
                                        <div class="input-group">
                                            <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                                            <input type="email" class="form-control field-editable" id="email_main" name="email_main" maxlength="80" value="<?php echo htmlspecialchars($proyecto['email_main'] ?: ''); ?>">
                                        </div>
                                        <div class="form-text">Correo electrónico principal de contacto (máximo 80 caracteres)</div>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="email_secundarios" class="form-label">Correos Secundarios</label>
                                        <div class="input-group">
                                            <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                                            <input type="text" class="form-control field-editable" id="email_secundarios" name="email_secundarios" 
                                                placeholder="Separados por coma o punto y coma" maxlength="2000" 
                                                value="<?php echo htmlspecialchars($proyecto['email_secundarios'] ?: ''); ?>">
                                        </div>
                                        <div class="form-text">Correos electrónicos adicionales de contacto (máximo 2000 caracteres)</div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Pestaña de Información Adicional -->
                            <div class="tab-pane fade" id="adicional" role="tabpanel" aria-labelledby="adicional-tab">
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="enlace_secop" class="form-label">Enlace SECOP</label>
                                        <div class="input-group">
                                            <span class="input-group-text"><i class="fas fa-link"></i></span>
                                            <input type="url" class="form-control field-editable" id="enlace_secop" name="enlace_secop" maxlength="1000" value="<?php echo htmlspecialchars($proyecto['enlace_secop'] ?: ''); ?>">
                                        </div>
                                        <div class="form-text">Enlace a la información del SECOP (máximo 1000 caracteres)</div>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="acto_administrativo" class="form-label">Acto Administrativo</label>
                                        <input type="text" class="form-control field-editable" id="acto_administrativo" name="acto_administrativo" maxlength="50" value="<?php echo htmlspecialchars($proyecto['acto_administrativo'] ?: ''); ?>">
                                        <div class="form-text">Número o referencia del acto administrativo (máximo 50 caracteres)</div>
                                    </div>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="beneficiarios" class="form-label">Beneficiarios</label>
                                        <input type="text" class="form-control field-editable" id="beneficiarios" name="beneficiarios" value="<?php echo formatearMoneda($proyecto['beneficiarios'] ?: 0); ?>">
                                        <div class="form-text">Número de personas beneficiadas por el proyecto</div>
                                    </div>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-12 mb-3">
                                        <label for="datos_adicionales" class="form-label">Datos Adicionales</label>
                                        <textarea class="form-control field-editable" id="datos_adicionales" name="datos_adicionales" rows="3" maxlength="1500"><?php echo htmlspecialchars($proyecto['datos_adicionales'] ?: ''); ?></textarea>
                                        <div class="form-text">Información adicional relevante para el proyecto (máximo 1500 caracteres)</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row mt-4 px-3 pb-3">
                            <div class="col-12 d-flex justify-content-between">
                                <a href="main.php?page=proyecto_individual&id=<?php echo $proyecto_id; ?>" class="btn btn-secondary">
                                    <i class="fas fa-times"></i> Cancelar
                                </a>
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save"></i> Guardar Cambios
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Script para activar las pestañas y formatear valores numéricos -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Activar las pestañas de Bootstrap
    var triggerTabList = [].slice.call(document.querySelectorAll('#myTab button'));
    triggerTabList.forEach(function(triggerEl) {
        var tabTrigger = new bootstrap.Tab(triggerEl);
        
        triggerEl.addEventListener('click', function(event) {
            event.preventDefault();
            tabTrigger.show();
        });
    });
    
    // Formatear campos numéricos con separadores de miles
    function formatearNumero(input) {
        // Guardar la posición del cursor
        var start = input.selectionStart;
        var end = input.selectionEnd;
        var length = input.value.length;
        
        // Quitar cualquier caracter que no sea dígito o punto
        let valor = input.value.replace(/[^\d.]/g, '');
        
        // Formatear el número
        if (valor) {
            // Separar parte entera y decimal
            let partes = valor.split('.');
            let parteEntera = partes[0];
            
            // Formatear la parte entera con separadores de miles
            parteEntera = parteEntera.replace(/\B(?=(\d{3})+(?!\d))/g, ',');
            
            // Reconstruir el número con parte decimal si existe
            valor = parteEntera;
            if (partes.length > 1) {
                valor += '.' + partes[1];
            }
            
            input.value = valor;
            
            // Calcular la nueva posición del cursor
            var newLength = input.value.length;
            var offset = newLength - length;
            start += offset;
            end += offset;
            
            // Restaurar la posición del cursor
            input.setSelectionRange(start, end);
        }
    }
    
    // Aplicar formato a campos numéricos
    const camposNumericos = ['valor', 'aporte_ud', 'beneficiarios'];
    
    camposNumericos.forEach(function(id) {
        const campo = document.getElementById(id);
        if (campo) {
            // Formatear inicialmente
            formatearNumero(campo);
            
            // Actualizar en tiempo real al escribir
            campo.addEventListener('input', function() {
                formatearNumero(this);
            });
            
            // Asegurar formato al perder el foco
            campo.addEventListener('blur', function() {
                formatearNumero(this);
            });
        }
    });
    
    // Antes de enviar el formulario, eliminar los separadores de miles
    const formulario = document.querySelector('form');
    if (formulario) {
        formulario.addEventListener('submit', function(e) {
            camposNumericos.forEach(function(id) {
                const campo = document.getElementById(id);
                if (campo && campo.value) {
                    campo.value = campo.value.replace(/,/g, '');
                }
            });
        });
    }

    // Destacar campos al enfocarlos
    document.querySelectorAll('.field-editable').forEach(field => {
        field.addEventListener('focus', function() {
            this.classList.add('border-primary');
        });
        
        field.addEventListener('blur', function() {
            this.classList.remove('border-primary');
        });
    });

    // Restaurar la pestaña activa si se guardó en el almacenamiento local
    const lastActiveTab = localStorage.getItem('lastActiveEditProjectTab');
    if (lastActiveTab) {
        const tabElement = document.querySelector(`#myTab button[data-bs-target="${lastActiveTab}"]`);
        if (tabElement) {
            const tab = new bootstrap.Tab(tabElement);
            tab.show();
        }
    }

    // Guardar la pestaña activa cuando cambia
    document.querySelectorAll('#myTab button').forEach(button => {
        button.addEventListener('shown.bs.tab', function (event) {
            const targetTab = event.target.getAttribute('data-bs-target');
            localStorage.setItem('lastActiveEditProjectTab', targetTab);
        });
    });
});
</script>