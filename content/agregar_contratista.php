<?php
/**
 * Página para agregar un contratista a un proyecto
 * 
 * Este archivo maneja la interfaz y la lógica para agregar un contratista (OPS) a un proyecto existente
 */

 

// Verificar que se haya proporcionado un ID de proyecto
if (!isset($_GET['proyecto_id']) || !is_numeric($_GET['proyecto_id'])) {
    // Redirigir a la lista de proyectos si no se proporciona un ID válido
    header('Location: main.php?page=proyecto');
    exit;
}

$proyecto_id = intval($_GET['proyecto_id']);

// Incluir los modelos necesarios
if (file_exists('models/proyecto_model.php')) {
    include_once 'models/proyecto_model.php';
} else {
    echo '<div class="alert alert-danger">Error: No se puede encontrar el modelo de proyectos.</div>';
    exit;
}

if (file_exists('models/contratista_model.php')) {
    include_once 'models/contratista_model.php';
} else {
    echo '<div class="alert alert-danger">Error: No se puede encontrar el modelo de contratistas.</div>';
    exit;
}

// Obtener información del proyecto
$proyecto = obtenerProyectoDetalle($proyecto_id);

// Si no se encuentra el proyecto, mostrar mensaje de error
if (!$proyecto) {
    echo '<div class="alert alert-danger" role="alert">El proyecto solicitado no existe o no se puede acceder a él en este momento.</div>';
    exit;
}

// Cargar listas para selectores
$tiposPersona = obtenerTiposPersona();
$tiposNacionalidad = obtenerTiposNacionalidad();
$tiposContrato = obtenerTiposContrato();
$tiposVinculacion = obtenerTiposVinculacion();
$facultades = obtenerFacultades();
$tiposConfiguracion = obtenerTiposConfiguracion();
$tiposRegistro = obtenerTiposRegistro();
$tiposModificacion = obtenerTiposModificacion();
$tipologias = obtenerTipologias();
$tiposProceso = obtenerTiposProceso();
$tiposMoneda = obtenerTiposMoneda();
$tiposTasa = obtenerTiposTasa();
$unidades = obtenerUnidades(); // Esta función debe existir en proyecto_model.php

// Inicializar variables para mensajes
$errores = [];
$mensaje_exito = '';

// Variable para controlar si se muestra el formulario de contratista o el de contrato
$mostrarFormContrato = false;
$contratista = null;
$identificacion = '';

// Obtener el siguiente número de contrato disponible para este proyecto
function obtenerSiguienteNumeroContrato($anio_pro, $numero_pro) {
    try {
        // Obtener conexión
        $conn = conectarOracle();
        
        // Consulta SQL para obtener el máximo número de contrato
        $sql = "SELECT NVL(MAX(NUMERO_CONTRATO), 0) + 1 AS siguiente_numero 
                FROM PROYECTO_OPS 
                WHERE ANIO_PRO = :anio_pro AND NUMERO_PRO = :numero_pro";
        
        // Preparar consulta
        $stid = oci_parse($conn, $sql);
        
        // Vincular parámetros
        oci_bind_by_name($stid, ':anio_pro', $anio_pro);
        oci_bind_by_name($stid, ':numero_pro', $numero_pro);
        
        // Ejecutar consulta
        oci_execute($stid);
        
        // Obtener resultado
        $row = oci_fetch_assoc($stid);
        $siguiente_numero = $row['SIGUIENTE_NUMERO'];
        
        // Liberar recursos
        oci_free_statement($stid);
        oci_close($conn);
        
        return $siguiente_numero;
        
    } catch (Exception $e) {
        error_log("Error en obtenerSiguienteNumeroContrato: " . $e->getMessage());
        return 1; // Por defecto, empezar desde 1
    }
}

// Obtener el siguiente número de contrato para mostrar en el formulario
$siguiente_numero_contrato = obtenerSiguienteNumeroContrato($proyecto['anio_pro'], $proyecto_id);

// Procesar formulario de búsqueda de contratista
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['buscar_contratista'])) {
    $identificacion = trim($_POST['identificacion']);
    
    if (empty($identificacion)) {
        $errores[] = 'Debe ingresar una identificación para buscar';
    } else {
        $contratista = obtenerContratistaPorId($identificacion);
        if ($contratista) {
            $mostrarFormContrato = true;
        } else {
            // Contratista no encontrado, se debe crear uno nuevo
            $mostrarFormContrato = false;
        }
    }
}

// Procesar formulario de creación de contratista
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['crear_contratista'])) {
    // Recoger datos del formulario
    $identificacion = trim($_POST['identificacion']);
    $nombre1 = trim($_POST['nombre1']);
    $nombre2 = trim($_POST['nombre2'] ?? '');
    $apellido1 = trim($_POST['apellido1'] ?? '');
    $apellido2 = trim($_POST['apellido2'] ?? '');
    $tipo_persona = intval($_POST['tipo_persona']);
    $tipo_nacionalidad = intval($_POST['tipo_nacionalidad']);
    $fecha_nacimiento = trim($_POST['fecha_nacimiento'] ?? '');
    $direccion = trim($_POST['direccion'] ?? '');
    $tel_fijo = trim($_POST['tel_fijo'] ?? '');
    $tel_celular = trim($_POST['tel_celular'] ?? '');
    $correo = trim($_POST['correo'] ?? '');
    $genero = trim($_POST['genero'] ?? 'M');
    
    // Validar datos
    if (empty($identificacion)) {
        $errores[] = 'La identificación es obligatoria';
    }
    if (empty($nombre1)) {
        $errores[] = 'El primer nombre es obligatorio';
    }
    if ($tipo_persona == 1 && empty($apellido1)) {
        $errores[] = 'El primer apellido es obligatorio para personas naturales';
    }
    
    // Si no hay errores, crear el contratista
    if (empty($errores)) {
        $datos_contratista = [
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
        
        $resultado = crearContratista($datos_contratista);
        
        if ($resultado === true) {
            $mensaje_exito = 'Contratista creado correctamente';
            $contratista = obtenerContratistaPorId($identificacion);
            $mostrarFormContrato = true;
        } else {
            $errores[] = is_string($resultado) ? $resultado : 'Error al crear el contratista';
        }
    }
}

// Procesar formulario de creación de contrato
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['crear_contrato'])) {
    // Recoger datos del formulario
    $identificacion = trim($_POST['identificacion']);
    $numero_contrato = intval($_POST['numero_contrato']);
    $codigo_entidad = isset($_POST['codigo_entidad']) ? intval($_POST['codigo_entidad']) : 108; // Valor por defecto
    $vigencia_fiscal = intval($_POST['vigencia_fiscal']);
    $tipo_registro = intval($_POST['tipo_registro']);
    $tipo_modificacion = intval($_POST['tipo_modificacion']);
    $tipo_configuracion = intval($_POST['tipo_configuracion']);
    $tipologia = intval($_POST['tipologia']);
    $tipo_proceso = intval($_POST['tipo_proceso']);
    $numero_publicacion = intval($_POST['numero_publicacion'] ?? 0);
    $fecha_publicacion = trim($_POST['fecha_publicacion'] ?? '');
    $tipo_contrato = intval($_POST['tipo_contrato']);
    $tipo_vinculacion = intval($_POST['tipo_vinculacion']);
    $facultad = intval($_POST['facultad'] ?? 0);
    $proyecto_curricular = intval($_POST['proyecto_curricular'] ?? 0);
    $objeto = trim($_POST['objeto']);
    $fecha_suscripcion = trim($_POST['fecha_suscripcion']);
    $fecha_inicio = trim($_POST['fecha_inicio']);
    $fecha_terminacion = trim($_POST['fecha_terminacion']);
    $valor = str_replace([',', '.'], '', $_POST['valor']);
    $tipo_moneda = intval($_POST['tipo_moneda']);
    $tipo_tasa = intval($_POST['tipo_tasa']);
    $situacion_contrato = intval($_POST['situacion_contrato'] ?? 8); // Por defecto: en trámite
    $observaciones = trim($_POST['observaciones'] ?? '');
    $unidad = intval($_POST['unidad']);
    $cdp = trim($_POST['cdp'] ?? '');
    $fecha_cdp = trim($_POST['fecha_cdp'] ?? '');
    $rp = trim($_POST['rp'] ?? '');
    $fecha_rp = trim($_POST['fecha_rp'] ?? '');
    $secop = trim($_POST['secop'] ?? '');
    
    // Calcular duración automáticamente
    $duracion = calcularDuracionContrato($fecha_inicio, $fecha_terminacion);
    $anio = $duracion['anio'];
    $mes = $duracion['mes'];
    $dia = $duracion['dia'];
    $hora = $duracion['hora'];
    
    // Validar datos
    if (empty($identificacion)) {
        $errores[] = 'La identificación es obligatoria';
    }
    if ($numero_contrato <= 0) {
        $errores[] = 'El número de contrato debe ser mayor que cero';
    }
    if (empty($objeto)) {
        $errores[] = 'El objeto del contrato es obligatorio';
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
    if (empty($valor) || intval($valor) <= 0) {
        $errores[] = 'El valor del contrato debe ser mayor que cero';
    }
    
    // Si no hay errores, crear el contrato
    if (empty($errores)) {
        $datos_contrato = [
            'anio_pro' => $proyecto['anio_pro'],
            'numero_pro' => $proyecto_id,
            'identificacion' => $identificacion,
            'numero_contrato' => $numero_contrato,
            'codigo_entidad' => $codigo_entidad,
            'vigencia_fiscal' => $vigencia_fiscal,
            'tipo_registro' => $tipo_registro,
            'tipo_modificacion' => $tipo_modificacion,
            'tipo_configuracion' => $tipo_configuracion,
            'tipologia' => $tipologia,
            'tipo_proceso' => $tipo_proceso,
            'numero_publicacion' => $numero_publicacion,
            'fecha_publicacion' => $fecha_publicacion,
            'tipo_contrato' => $tipo_contrato,
            'tipo_vinculacion' => $tipo_vinculacion,
            'facultad' => $facultad,
            'proyecto_curricular' => $proyecto_curricular,
            'objeto' => $objeto,
            'fecha_suscripcion' => $fecha_suscripcion,
            'fecha_inicio' => $fecha_inicio,
            'fecha_terminacion' => $fecha_terminacion,
            'valor' => $valor,
            'tipo_moneda' => $tipo_moneda,
            'tipo_tasa' => $tipo_tasa,
            'anio' => $anio,
            'mes' => $mes,
            'dia' => $dia,
            'hora' => $hora,
            'situacion_contrato' => $situacion_contrato,
            'observaciones' => $observaciones,
            'unidad' => $unidad,
            'cdp' => $cdp,
            'fecha_cdp' => $fecha_cdp,
            'rp' => $rp,
            'fecha_rp' => $fecha_rp,
            'secop' => $secop
        ];
        
        $resultado = crearContratoProyecto($datos_contrato);
        
        if ($resultado === true) {
            $mensaje_exito = 'Contrato creado correctamente';
            // Redireccionar a la página del proyecto después de 2 segundos
            echo '<meta http-equiv="refresh" content="2;url=main.php?page=proyecto_individual&id=' . $proyecto_id . '#contratistas">';
        } else {
            $errores[] = is_string($resultado) ? $resultado : 'Error al crear el contrato';
        }
    }
}
?>
<!-- Bootstrap JS Bundle with Popper -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-ka7Sk0Gln4gmtz2MlQnikT1wXgYsOg+OMhuP+IlRH9sENBO0LRn5q+8nbTov4+1p" crossorigin="anonymous"></script>
<div class="container-fluid py-4">
    <!-- Encabezado de la página -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <h4 class="mb-0">Agregar Contratista/OPS a Proyecto</h4>
                <a href="main.php?page=proyecto_individual&id=<?php echo $proyecto_id; ?>#contratistas" class="btn btn-sm btn-outline-secondary">
                    <i class="fas fa-arrow-left me-1"></i> Volver al Proyecto
                </a>
            </div>
            <p class="text-sm text-muted">Complete el formulario para agregar un contratista al proyecto: <strong><?php echo htmlspecialchars($proyecto['nombre']); ?></strong></p>
        </div>
    </div>
    
    <!-- Mensajes de error o éxito -->
    <?php if (!empty($errores)): ?>
    <div class="alert alert-danger" role="alert">
        <ul class="mb-0">
            <?php foreach ($errores as $error): ?>
            <li><?php echo $error; ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
    <?php endif; ?>
    
    <?php if (!empty($mensaje_exito)): ?>
    <div class="alert alert-success" role="alert">
        <?php echo $mensaje_exito; ?>
    </div>
    <?php endif; ?>
    
    <!-- Información resumida del proyecto -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4">
                            <h6 class="text-uppercase text-sm font-weight-bolder">Proyecto</h6>
                            <p class="text-sm mb-0"><?php echo htmlspecialchars($proyecto['nombre']); ?></p>
                        </div>
                        <div class="col-md-2">
                            <h6 class="text-uppercase text-sm font-weight-bolder">Código</h6>
                            <p class="text-sm mb-0"><?php echo $proyecto['numero_pro']; ?></p>
                        </div>
                        <div class="col-md-2">
                            <h6 class="text-uppercase text-sm font-weight-bolder">Año</h6>
                            <p class="text-sm mb-0"><?php echo $proyecto['anio_pro']; ?></p>
                        </div>
                        <div class="col-md-4">
                            <h6 class="text-uppercase text-sm font-weight-bolder">Entidad</h6>
                            <p class="text-sm mb-0"><?php echo htmlspecialchars($proyecto['entidad']); ?></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Formulario de búsqueda de contratista -->
    <?php if (!$mostrarFormContrato): ?>
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header pb-0">
                    <h5 class="mb-0">Buscar Contratista Existente</h5>
                </div>
                <div class="card-body">
                    <form method="POST" action="" id="formBusqueda">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="identificacion" class="form-label">Número de Identificación *</label>
                                    <input type="text" class="form-control" id="identificacion" name="identificacion" required value="<?php echo htmlspecialchars($identificacion); ?>">
                                    <div class="form-text">Ingrese el número de identificación sin puntos ni comas</div>
                                </div>
                            </div>
                            <div class="col-md-6 d-flex align-items-end">
                                <button type="submit" name="buscar_contratista" class="btn btn-primary">
                                    <i class="fas fa-search me-1"></i> Buscar Contratista
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Formulario de creación de contratista (si no se encontró en la búsqueda) -->
    <?php if (!empty($identificacion) && !$contratista): ?>
    <div class="row mt-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header pb-0">
                    <h5 class="mb-0">Crear Nuevo Contratista</h5>
                    <p class="text-sm text-muted">El contratista con identificación <strong><?php echo htmlspecialchars($identificacion); ?></strong> no existe. Complete los siguientes datos para crearlo.</p>
                </div>
                <div class="card-body">
                    <form method="POST" action="" id="formCrearContratista">
                        <input type="hidden" name="identificacion" value="<?php echo htmlspecialchars($identificacion); ?>">
                        
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label for="tipo_persona" class="form-label">Tipo de Persona *</label>
                                <select class="form-select" id="tipo_persona" name="tipo_persona" required>
                                    <option value="">Seleccione...</option>
                                    <?php foreach ($tiposPersona as $tipo): ?>
                                    <option value="<?php echo $tipo['codigo']; ?>"><?php echo htmlspecialchars($tipo['descripcion']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="col-md-4 mb-3">
                                <label for="tipo_nacionalidad" class="form-label">Nacionalidad *</label>
                                <select class="form-select" id="tipo_nacionalidad" name="tipo_nacionalidad" required>
                                    <option value="">Seleccione...</option>
                                    <?php foreach ($tiposNacionalidad as $tipo): ?>
                                    <option value="<?php echo $tipo['codigo']; ?>"><?php echo htmlspecialchars($tipo['descripcion']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="col-md-4 mb-3">
                                <label for="genero" class="form-label">Género</label>
                                <select class="form-select" id="genero" name="genero">
                                    <option value="M">Masculino</option>
                                    <option value="F">Femenino</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="row persona-natural">
                            <div class="col-md-3 mb-3">
                                <label for="nombre1" class="form-label">Primer Nombre *</label>
                                <input type="text" class="form-control" id="nombre1" name="nombre1" required>
                            </div>
                            
                            <div class="col-md-3 mb-3">
                                <label for="nombre2" class="form-label">Segundo Nombre</label>
                                <input type="text" class="form-control" id="nombre2" name="nombre2">
                            </div>
                            
                            <div class="col-md-3 mb-3">
                                <label for="apellido1" class="form-label">Primer Apellido *</label>
                                <input type="text" class="form-control" id="apellido1" name="apellido1">
                            </div>
                            
                            <div class="col-md-3 mb-3">
                                <label for="apellido2" class="form-label">Segundo Apellido</label>
                                <input type="text" class="form-control" id="apellido2" name="apellido2">
                            </div>
                        </div>
                        
                        <div class="row persona-juridica" style="display: none;">
                            <div class="col-md-12 mb-3">
                                <label for="nombre1" class="form-label">Razón Social *</label>
                                <input type="text" class="form-control" id="razon_social" name="nombre1">
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label for="fecha_nacimiento" class="form-label">Fecha de Nacimiento</label>
                                <input type="date" class="form-control" id="fecha_nacimiento" name="fecha_nacimiento">
                            </div>
                            
                            <div class="col-md-8 mb-3">
                                <label for="direccion" class="form-label">Dirección</label>
                                <input type="text" class="form-control" id="direccion" name="direccion">
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label for="tel_fijo" class="form-label">Teléfono Fijo</label>
                                <input type="text" class="form-control" id="tel_fijo" name="tel_fijo">
                            </div>
                            
                            <div class="col-md-4 mb-3">
                                <label for="tel_celular" class="form-label">Teléfono Celular</label>
                                <input type="text" class="form-control" id="tel_celular" name="tel_celular">
                            </div>
                            
                            <div class="col-md-4 mb-3">
                                <label for="correo" class="form-label">Correo Electrónico</label>
                                <input type="email" class="form-control" id="correo" name="correo">
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-12 text-end">
                                <button type="submit" name="crear_contratista" class="btn btn-primary">
                                    <i class="fas fa-user-plus me-1"></i> Crear Contratista
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>
    
    <?php else: ?>
    <!-- Formulario de creación de contrato (si se encontró o creó un contratista) -->
    <div class="row mt-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header pb-0">
                    <h5 class="mb-0">Información del Contratista</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-3">
                            <p class="text-sm mb-1 text-uppercase font-weight-bold">Identificación</p>
                            <p class="mb-3"><?php echo htmlspecialchars($contratista['identificacion']); ?></p>
                        </div>
                        <div class="col-md-5">
                            <p class="text-sm mb-1 text-uppercase font-weight-bold">Nombre</p>
                            <p class="mb-3">
                                <?php 
                                if ($contratista['tipo_persona'] == 1) {
                                    echo htmlspecialchars(trim($contratista['nombre1'] . ' ' . $contratista['nombre2'] . ' ' . $contratista['apellido1'] . ' ' . $contratista['apellido2']));
                                } else {
                                    echo htmlspecialchars($contratista['nombre1']); // Razón social
                                }
                                ?>
                            </p>
                        </div>
                        <div class="col-md-4">
                            <p class="text-sm mb-1 text-uppercase font-weight-bold">Contacto</p>
                            <p class="mb-3">
                                <?php if (!empty($contratista['tel_celular'])): ?>
                                <i class="fas fa-phone me-1"></i> <?php echo htmlspecialchars($contratista['tel_celular']); ?><br>
                                <?php endif; ?>
                                <?php if (!empty($contratista['correo'])): ?>
                                <i class="fas fa-envelope me-1"></i> <?php echo htmlspecialchars($contratista['correo']); ?>
                                <?php endif; ?>
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="row mt-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header pb-0">
                    <h5 class="mb-0">Crear Contrato</h5>
                </div>
                <div class="card-body">
                    <form method="POST" action="" id="formCrearContrato">
                        <input type="hidden" name="identificacion" value="<?php echo htmlspecialchars($contratista['identificacion']); ?>">
                        
                        <ul class="nav nav-tabs mb-4" id="contratoTab" role="tablist">
                            <li class="nav-item" role="presentation">
                                <button class="nav-link active" id="general-tab" data-bs-toggle="tab" data-bs-target="#general" type="button" role="tab" aria-controls="general" aria-selected="true">Información General</button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="fechas-tab" data-bs-toggle="tab" data-bs-target="#fechas" type="button" role="tab" aria-controls="fechas" aria-selected="false">Fechas y Plazos</button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="financiero-tab" data-bs-toggle="tab" data-bs-target="#financiero" type="button" role="tab" aria-controls="financiero" aria-selected="false">Información Financiera</button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="adicional-tab" data-bs-toggle="tab" data-bs-target="#adicional" type="button" role="tab" aria-controls="adicional" aria-selected="false">Información Adicional</button>
                            </li>
                        </ul>
                        
                        <div class="tab-content" id="contratoTabContent">
                            <!-- Pestaña de Información General -->
                            <div class="tab-pane fade show active" id="general" role="tabpanel" aria-labelledby="general-tab">
                                <div class="row">
                                    <div class="col-md-4 mb-3">
                                        <label for="numero_contrato" class="form-label">Número de Contrato *</label>
                                        <input type="number" class="form-control" id="numero_contrato" name="numero_contrato" required value="<?php echo $siguiente_numero_contrato; ?>">
                                    </div>
                                    
                                    <div class="col-md-4 mb-3">
                                        <label for="vigencia_fiscal" class="form-label">Vigencia Fiscal *</label>
                                        <input type="number" class="form-control" id="vigencia_fiscal" name="vigencia_fiscal" required value="<?php echo date('Y'); ?>">
                                    </div>
                                    
                                    <div class="col-md-4 mb-3">
                                        <label for="unidad" class="form-label">Unidad Ejecutora *</label>
                                        <select class="form-select" id="unidad" name="unidad" required>
                                            <option value="">Seleccione...</option>
                                            <?php foreach ($unidades as $uni): ?>
                                            <option value="<?php echo $uni['codigo']; ?>" <?php echo ($proyecto['unidad'] == $uni['codigo']) ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($uni['descripcion']); ?>
                                            </option>
                                            <?php endforeach; ?>
                                        </select>
                                        </div>
                                
                                <div class="row">
                                    <div class="col-md-4 mb-3">
                                        <label for="tipo_registro" class="form-label">Tipo de Registro *</label>
                                        <select class="form-select" id="tipo_registro" name="tipo_registro" required>
                                            <option value="">Seleccione...</option>
                                            <?php foreach ($tiposRegistro as $tipo): ?>
                                            <option value="<?php echo $tipo['codigo']; ?>"><?php echo htmlspecialchars($tipo['descripcion']); ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    
                                    <div class="col-md-4 mb-3">
                                        <label for="tipo_modificacion" class="form-label">Tipo de Modificación *</label>
                                        <select class="form-select" id="tipo_modificacion" name="tipo_modificacion" required>
                                            <option value="">Seleccione...</option>
                                            <?php foreach ($tiposModificacion as $tipo): ?>
                                            <option value="<?php echo $tipo['codigo']; ?>"><?php echo htmlspecialchars($tipo['descripcion']); ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    
                                    <div class="col-md-4 mb-3">
                                        <label for="tipo_configuracion" class="form-label">Configuración *</label>
                                        <select class="form-select" id="tipo_configuracion" name="tipo_configuracion" required>
                                            <option value="">Seleccione...</option>
                                            <?php foreach ($tiposConfiguracion as $tipo): ?>
                                            <option value="<?php echo $tipo['codigo']; ?>"><?php echo htmlspecialchars($tipo['descripcion']); ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-4 mb-3">
                                        <label for="tipologia" class="form-label">Tipología *</label>
                                        <select class="form-select" id="tipologia" name="tipologia" required>
                                            <option value="">Seleccione...</option>
                                            <?php foreach ($tipologias as $tipo): ?>
                                            <option value="<?php echo $tipo['codigo']; ?>"><?php echo htmlspecialchars($tipo['descripcion']); ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    
                                    <div class="col-md-4 mb-3">
                                        <label for="tipo_proceso" class="form-label">Tipo de Proceso *</label>
                                        <select class="form-select" id="tipo_proceso" name="tipo_proceso" required>
                                            <option value="">Seleccione...</option>
                                            <?php foreach ($tiposProceso as $tipo): ?>
                                            <option value="<?php echo $tipo['codigo']; ?>"><?php echo htmlspecialchars($tipo['descripcion']); ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    
                                    <div class="col-md-4 mb-3">
                                        <label for="numero_publicacion" class="form-label">Número de Publicación</label>
                                        <input type="text" class="form-control" id="numero_publicacion" name="numero_publicacion">
                                    </div>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-4 mb-3">
                                        <label for="fecha_publicacion" class="form-label">Fecha de Publicación</label>
                                        <input type="date" class="form-control" id="fecha_publicacion" name="fecha_publicacion">
                                    </div>
                                    
                                    <div class="col-md-4 mb-3">
                                        <label for="tipo_contrato" class="form-label">Tipo de Contrato *</label>
                                        <select class="form-select" id="tipo_contrato" name="tipo_contrato" required>
                                            <option value="">Seleccione...</option>
                                            <?php foreach ($tiposContrato as $tipo): ?>
                                            <option value="<?php echo $tipo['codigo']; ?>"><?php echo htmlspecialchars($tipo['descripcion']); ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    
                                    <div class="col-md-4 mb-3">
                                        <label for="tipo_vinculacion" class="form-label">Tipo de Vinculación *</label>
                                        <select class="form-select" id="tipo_vinculacion" name="tipo_vinculacion" required>
                                            <option value="">Seleccione...</option>
                                            <?php foreach ($tiposVinculacion as $tipo): ?>
                                            <option value="<?php echo $tipo['codigo']; ?>"><?php echo htmlspecialchars($tipo['descripcion']); ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="facultad" class="form-label">Facultad</label>
                                        <select class="form-select" id="facultad" name="facultad">
                                            <option value="">Seleccione...</option>
                                            <?php foreach ($facultades as $fac): ?>
                                            <option value="<?php echo $fac['codigo']; ?>"><?php echo htmlspecialchars($fac['descripcion']); ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    
                                    <div class="col-md-6 mb-3">
                                        <label for="proyecto_curricular" class="form-label">Proyecto Curricular</label>
                                        <input type="text" class="form-control" id="proyecto_curricular" name="proyecto_curricular">
                                    </div>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-12 mb-3">
                                        <label for="objeto" class="form-label">Objeto del Contrato *</label>
                                        <textarea class="form-control" id="objeto" name="objeto" rows="3" required></textarea>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Pestaña de Fechas y Plazos -->
                            <div class="tab-pane fade" id="fechas" role="tabpanel" aria-labelledby="fechas-tab">
                                <div class="row">
                                    <div class="col-md-4 mb-3">
                                        <label for="fecha_suscripcion" class="form-label">Fecha de Suscripción *</label>
                                        <input type="date" class="form-control" id="fecha_suscripcion" name="fecha_suscripcion" required value="<?php echo date('Y-m-d'); ?>">
                                    </div>
                                    
                                    <div class="col-md-4 mb-3">
                                        <label for="fecha_inicio" class="form-label">Fecha de Inicio *</label>
                                        <input type="date" class="form-control" id="fecha_inicio" name="fecha_inicio" required value="<?php echo date('Y-m-d'); ?>">
                                    </div>
                                    
                                    <div class="col-md-4 mb-3">
                                        <label for="fecha_terminacion" class="form-label">Fecha de Terminación *</label>
                                        <input type="date" class="form-control" id="fecha_terminacion" name="fecha_terminacion" required value="<?php echo date('Y-m-d', strtotime('+1 month')); ?>">
                                    </div>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-3 mb-3">
                                        <label class="form-label">Duración (Años)</label>
                                        <input type="text" class="form-control" id="duracion_anio" readonly>
                                    </div>
                                    
                                    <div class="col-md-3 mb-3">
                                        <label class="form-label">Duración (Meses)</label>
                                        <input type="text" class="form-control" id="duracion_mes" readonly>
                                    </div>
                                    
                                    <div class="col-md-3 mb-3">
                                        <label class="form-label">Duración (Días)</label>
                                        <input type="text" class="form-control" id="duracion_dia" readonly>
                                    </div>
                                    
                                    <div class="col-md-3 mb-3">
                                        <label class="form-label">Duración Total (Días)</label>
                                        <input type="text" class="form-control" id="duracion_total_dias" readonly>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Pestaña de Información Financiera -->
                            <div class="tab-pane fade" id="financiero" role="tabpanel" aria-labelledby="financiero-tab">
                                <div class="row">
                                    <div class="col-md-4 mb-3">
                                        <label for="valor" class="form-label">Valor del Contrato *</label>
                                        <div class="input-group">
                                            <span class="input-group-text">$</span>
                                            <input type="text" class="form-control money-format" id="valor" name="valor" required>
                                        </div>
                                    </div>
                                    
                                    <div class="col-md-4 mb-3">
                                        <label for="tipo_moneda" class="form-label">Tipo de Moneda *</label>
                                        <select class="form-select" id="tipo_moneda" name="tipo_moneda" required>
                                            <option value="">Seleccione...</option>
                                            <?php foreach ($tiposMoneda as $tipo): ?>
                                            <option value="<?php echo $tipo['codigo']; ?>" <?php echo ($tipo['codigo'] == 1) ? 'selected' : ''; ?>><?php echo htmlspecialchars($tipo['descripcion']); ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    
                                    <div class="col-md-4 mb-3">
                                        <label for="tipo_tasa" class="form-label">Tipo de Tasa *</label>
                                        <select class="form-select" id="tipo_tasa" name="tipo_tasa" required>
                                            <option value="">Seleccione...</option>
                                            <?php foreach ($tiposTasa as $tipo): ?>
                                            <option value="<?php echo $tipo['codigo']; ?>" <?php echo ($tipo['codigo'] == 1) ? 'selected' : ''; ?>><?php echo htmlspecialchars($tipo['descripcion']); ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-4 mb-3">
                                        <label for="cdp" class="form-label">Número CDP</label>
                                        <input type="text" class="form-control" id="cdp" name="cdp">
                                    </div>
                                    
                                    <div class="col-md-4 mb-3">
                                        <label for="fecha_cdp" class="form-label">Fecha CDP</label>
                                        <input type="date" class="form-control" id="fecha_cdp" name="fecha_cdp">
                                    </div>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-4 mb-3">
                                        <label for="rp" class="form-label">Número RP</label>
                                        <input type="text" class="form-control" id="rp" name="rp">
                                    </div>
                                    
                                    <div class="col-md-4 mb-3">
                                        <label for="fecha_rp" class="form-label">Fecha RP</label>
                                        <input type="date" class="form-control" id="fecha_rp" name="fecha_rp">
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Pestaña de Información Adicional -->
                            <div class="tab-pane fade" id="adicional" role="tabpanel" aria-labelledby="adicional-tab">
                                <div class="row">
                                    <div class="col-md-12 mb-3">
                                        <label for="secop" class="form-label">Enlace SECOP</label>
                                        <input type="url" class="form-control" id="secop" name="secop" placeholder="https://www.secop.gov.co/...">
                                    </div>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-12 mb-3">
                                        <label for="observaciones" class="form-label">Observaciones</label>
                                        <textarea class="form-control" id="observaciones" name="observaciones" rows="3"></textarea>
                                    </div>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-4 mb-3">
                                        <label for="situacion_contrato" class="form-label">Situación del Contrato</label>
                                        <select class="form-select" id="situacion_contrato" name="situacion_contrato">
                                            <option value="8" selected>En Trámite</option>
                                            <option value="14">En Ejecución</option>
                                            <option value="19">Finalizado</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row mt-4">
                            <div class="col-12 text-end">
                                <a href="main.php?page=proyecto_individual&id=<?php echo $proyecto_id; ?>#contratistas" class="btn btn-secondary me-2">
                                    <i class="fas fa-times me-1"></i> Cancelar
                                </a>
                                <button type="submit" name="crear_contrato" class="btn btn-primary">
                                    <i class="fas fa-save me-1"></i> Crear Contrato
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Toggle entre persona natural y jurídica
    const tipoPersonaSelect = document.getElementById('tipo_persona');
    const personaNaturalDiv = document.querySelector('.persona-natural');
    const personaJuridicaDiv = document.querySelector('.persona-juridica');
    
    if (tipoPersonaSelect) {
        tipoPersonaSelect.addEventListener('change', function() {
            const tipoPersona = parseInt(this.value);
            
            if (tipoPersona === 1) { // Persona Natural
                personaNaturalDiv.style.display = 'flex';
                personaJuridicaDiv.style.display = 'none';
                
                // Hacer apellido1 requerido
                document.getElementById('apellido1').setAttribute('required', 'required');
                
                // Cambiar etiqueta
                document.getElementById('nombre1').placeholder = 'Primer Nombre';
            } else { // Persona Jurídica u otro
                personaNaturalDiv.style.display = 'none';
                personaJuridicaDiv.style.display = 'flex';
                
                // Quitar requerido de apellido1
                document.getElementById('apellido1').removeAttribute('required');
                
                // Cambiar etiqueta
                document.getElementById('razon_social').placeholder = 'Razón Social';
            }
        });
    }
    
    // Inicializar las pestañas de Bootstrap manualmente
    const triggerTabList = document.querySelectorAll('#contratoTab button');
    triggerTabList.forEach(function(triggerEl) {
        const tabTrigger = new bootstrap.Tab(triggerEl);
        
        triggerEl.addEventListener('click', function(event) {
            event.preventDefault();
            tabTrigger.show();
        });
    });
    
    // Cálculo de duración automática
    const fechaInicioInput = document.getElementById('fecha_inicio');
    const fechaTerminacionInput = document.getElementById('fecha_terminacion');
    const duracionAnioInput = document.getElementById('duracion_anio');
    const duracionMesInput = document.getElementById('duracion_mes');
    const duracionDiaInput = document.getElementById('duracion_dia');
    const duracionTotalDiasInput = document.getElementById('duracion_total_dias');
    
    function calcularDuracion() {
        if (fechaInicioInput && fechaTerminacionInput && 
            fechaInicioInput.value && fechaTerminacionInput.value) {
            
            const fechaInicio = new Date(fechaInicioInput.value);
            const fechaFin = new Date(fechaTerminacionInput.value);
            
            if (fechaFin >= fechaInicio) {
                // Calcular diferencia en días
                const diffTime = Math.abs(fechaFin - fechaInicio);
                const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
                
                // Calcular años, meses y días
                const años = Math.floor(diffDays / 365);
                const meses = Math.floor((diffDays % 365) / 30);
                const dias = diffDays - (años * 365) - (meses * 30);
                
                // Mostrar en los campos
                if (duracionAnioInput) duracionAnioInput.value = años;
                if (duracionMesInput) duracionMesInput.value = meses;
                if (duracionDiaInput) duracionDiaInput.value = dias;
                if (duracionTotalDiasInput) duracionTotalDiasInput.value = diffDays;
            }
        }
    }
    
    if (fechaInicioInput && fechaTerminacionInput) {
        fechaInicioInput.addEventListener('change', calcularDuracion);
        fechaTerminacionInput.addEventListener('change', calcularDuracion);
        
        // Calcular duración inicial si ambas fechas tienen valores
        calcularDuracion();
    }
    
    // Formateo de campos de moneda
    document.querySelectorAll('.money-format').forEach(input => {
        input.addEventListener('input', function(e) {
            // Obtener valor sin formato
            let value = this.value.replace(/\D/g, '');
            
            // Formatear con separadores de miles
            if (value !== '') {
                value = parseInt(value, 10).toLocaleString('es-CO');
            }
            
            this.value = value;
        });
    });
});
</script>

<style>
    /* Estilos para las pestañas */
    .nav-tabs .nav-link {
        color: #6c757d;
        font-weight: 500;
    }
    
    .nav-tabs .nav-link.active {
        color: #5e72e4;
        font-weight: 600;
    }
    
    /* Estilos para los campos requeridos */
    .form-label::after {
        content: " *";
        color: #f5365c;
    }
    
    .form-label:not([for="identificacion"]):not([for="nombre1"]):not([for="apellido1"]):not([for="numero_contrato"]):not([for="vigencia_fiscal"]):not([for="unidad"]):not([for="tipo_registro"]):not([for="tipo_modificacion"]):not([for="tipo_configuracion"]):not([for="tipologia"]):not([for="tipo_contrato"]):not([for="tipo_vinculacion"]):not([for="tipo_proceso"]):not([for="objeto"]):not([for="fecha_suscripcion"]):not([for="fecha_inicio"]):not([for="fecha_terminacion"]):not([for="valor"]):not([for="tipo_moneda"]):not([for="tipo_tasa"]):not([for="tipo_persona"]):not([for="tipo_nacionalidad"])::after {
        content: "";
    }
</style>