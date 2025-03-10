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

<div class="container-fluid py-4">
    <!-- Encabezado de la página -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <h4 class="mb-0">Editar Proyecto</h4>
                <a href="main.php?page=proyecto_individual&id=<?php echo $proyecto_id; ?>" class="btn btn-sm btn-outline-secondary">
                    <i class="fas fa-arrow-left me-1"></i> Volver a Detalles
                </a>
            </div>
            <p class="text-sm text-muted">Actualice la información completa del proyecto <?php echo htmlspecialchars($proyecto['numero_pro_entidad']); ?></p>
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
    
    <!-- Formulario de edición -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <form method="POST" action="">
                        <ul class="nav nav-tabs mb-4" id="myTab" role="tablist">
                            <li class="nav-item" role="presentation">
                                <button class="nav-link active" id="general-tab" data-bs-toggle="tab" data-bs-target="#general" type="button" role="tab" aria-controls="general" aria-selected="true">Información General</button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="financiera-tab" data-bs-toggle="tab" data-bs-target="#financiera" type="button" role="tab" aria-controls="financiera" aria-selected="false">Información Financiera</button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="contacto-tab" data-bs-toggle="tab" data-bs-target="#contacto" type="button" role="tab" aria-controls="contacto" aria-selected="false">Contactos</button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="adicional-tab" data-bs-toggle="tab" data-bs-target="#adicional" type="button" role="tab" aria-controls="adicional" aria-selected="false">Información Adicional</button>
                            </li>
                        </ul>
                        <div class="tab-content" id="myTabContent">
                            <!-- Pestaña de Información General -->
                            <div class="tab-pane fade show active" id="general" role="tabpanel" aria-labelledby="general-tab">
                                <div class="row">
                                    <div class="col-md-4 mb-3">
                                        <label for="numero_pro" class="form-label">Código del Proyecto</label>
                                        <input type="text" class="form-control" id="numero_pro" value="<?php echo $proyecto['numero_pro']; ?>" disabled>
                                        <small class="text-muted">Este campo no se puede modificar</small>
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label for="numero_pro_entidad" class="form-label">Número de Referencia</label>
                                        <input type="text" class="form-control" id="numero_pro_entidad" value="<?php echo htmlspecialchars($proyecto['numero_pro_entidad']); ?>" disabled>
                                        <small class="text-muted">Este campo no se puede modificar</small>
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label for="anio_pro" class="form-label">Año</label>
                                        <input type="text" class="form-control" id="anio_pro" value="<?php echo $proyecto['anio_pro']; ?>" disabled>
                                        <small class="text-muted">Este campo no se puede modificar</small>
                                    </div>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-12 mb-3">
                                        <label for="nombre" class="form-label">Nombre del Proyecto *</label>
                                        <input type="text" class="form-control" id="nombre" name="nombre" required value="<?php echo htmlspecialchars($proyecto['nombre']); ?>">
                                    </div>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-12 mb-3">
                                        <label for="objeto" class="form-label">Objeto del Proyecto *</label>
                                        <textarea class="form-control" id="objeto" name="objeto" rows="5" required><?php echo htmlspecialchars($proyecto['objeto']); ?></textarea>
                                    </div>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-4 mb-3">
                                        <label for="fecha_inicio" class="form-label">Fecha de Inicio *</label>
                                        <input type="date" class="form-control" id="fecha_inicio" name="fecha_inicio" required value="<?php echo formatearFechaInput($proyecto['fecha_inicio']); ?>">
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label for="fecha_termina" class="form-label">Fecha de Terminación *</label>
                                        <input type="date" class="form-control" id="fecha_termina" name="fecha_termina" required value="<?php echo formatearFechaInput($proyecto['fecha_termina']); ?>">
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label for="fecha_suscripcion" class="form-label">Fecha de Suscripción</label>
                                        <input type="date" class="form-control" id="fecha_suscripcion" name="fecha_suscripcion" value="<?php echo formatearFechaInput($proyecto['fecha_suscripcion']); ?>">
                                    </div>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-4 mb-3">
                                        <label for="tipo_proyecto" class="form-label">Tipo de Proyecto</label>
                                        <select class="form-select" id="tipo_proyecto" name="tipo_proyecto">
                                            <option value="">Seleccione...</option>
                                            <?php foreach ($tiposProyecto as $tipo): ?>
                                            <option value="<?php echo $tipo['codigo']; ?>" <?php echo $proyecto['tipo_proyecto'] == $tipo['codigo'] ? 'selected' : ''; ?>>
                                                <?php echo $tipo['codigo'] . ' - ' . htmlspecialchars($tipo['descripcion']); ?>
                                            </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label for="modalidad" class="form-label">Modalidad</label>
                                        <select class="form-select" id="modalidad" name="modalidad">
                                            <option value="">Seleccione...</option>
                                            <?php foreach ($modalidades as $mod): ?>
                                            <option value="<?php echo $mod['codigo']; ?>" <?php echo $proyecto['modalidad'] == $mod['codigo'] ? 'selected' : ''; ?>>
                                                <?php echo $mod['codigo'] . ' - ' . htmlspecialchars($mod['descripcion']); ?>
                                            </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label for="situacion" class="form-label">Situación</label>
                                        <select class="form-select" id="situacion" name="situacion">
                                            <option value="">Seleccione...</option>
                                            <?php foreach ($situaciones as $sit): ?>
                                            <option value="<?php echo $sit['codigo']; ?>" <?php echo $proyecto['situacion'] == $sit['codigo'] ? 'selected' : ''; ?>>
                                                <?php echo $sit['codigo'] . ' - ' . htmlspecialchars($sit['descripcion']); ?>
                                            </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="unidad" class="form-label">Unidad Ejecutora</label>
                                        <select class="form-select" id="unidad" name="unidad">
                                            <option value="">Seleccione...</option>
                                            <?php foreach ($unidades as $uni): ?>
                                            <option value="<?php echo $uni['codigo']; ?>" <?php echo $proyecto['unidad'] == $uni['codigo'] ? 'selected' : ''; ?>>
                                                <?php echo $uni['codigo'] . ' - ' . htmlspecialchars($uni['descripcion']); ?>
                                            </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="entidad_principal" class="form-label">Entidad Contratante Principal *</label>
                                        <select class="form-select" id="entidad_principal" name="entidad_principal" required>
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
                                    </div>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-12 mb-3">
                                        <label for="observaciones" class="form-label">Observaciones</label>
                                        <textarea class="form-control" id="observaciones" name="observaciones" rows="3"><?php echo htmlspecialchars($proyecto['observaciones'] ?: ''); ?></textarea>
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
                                            <input type="text" class="form-control" id="valor" name="valor" required value="<?php echo formatearMoneda($proyecto['valor']); ?>">
                                        </div>
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label for="aporte_ud" class="form-label">Aporte UD</label>
                                        <div class="input-group">
                                            <span class="input-group-text">$</span>
                                            <input type="text" class="form-control" id="aporte_ud" name="aporte_ud" value="<?php echo formatearMoneda($proyecto['aporte_ud']); ?>">
                                        </div>
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label for="financiacion" class="form-label">Tipo de Financiación</label>
                                        <select class="form-select" id="financiacion" name="financiacion">
                                            <option value="">Seleccione...</option>
                                            <?php foreach ($financiaciones as $fin): ?>
                                            <option value="<?php echo $fin['codigo']; ?>" <?php echo $proyecto['financiacion'] == $fin['codigo'] ? 'selected' : ''; ?>>
                                                <?php echo $fin['codigo'] . ' - ' . htmlspecialchars($fin['descripcion']); ?>
                                            </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="codigo_tesoral" class="form-label">Código Tesoral</label>
                                        <input type="text" class="form-control" id="codigo_tesoral" name="codigo_tesoral" value="<?php echo htmlspecialchars($proyecto['codigo_tesoral'] ?: ''); ?>">
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="codigo_bancario" class="form-label">Banco</label>
                                        <select class="form-select" id="codigo_bancario" name="codigo_bancario">
                                            <option value="">Seleccione...</option>
                                            <?php foreach ($bancos as $banco): ?>
                                            <option value="<?php echo $banco['codigo']; ?>" <?php echo $proyecto['codigo_bancario'] == $banco['codigo'] ? 'selected' : ''; ?>>
                                                <?php echo $banco['codigo'] . ' - ' . htmlspecialchars($banco['descripcion']); ?>
                                            </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="tipo_cuenta" class="form-label">Tipo de Cuenta</label>
                                        <select class="form-select" id="tipo_cuenta" name="tipo_cuenta">
                                            <option value="">Seleccione...</option>
                                            <option value="AHORROS" <?php echo $proyecto['tipo_cuenta'] == 'AHORROS' ? 'selected' : ''; ?>>AHORROS</option>
                                            <option value="CORRIENTE" <?php echo $proyecto['tipo_cuenta'] == 'CORRIENTE' ? 'selected' : ''; ?>>CORRIENTE</option>
                                        </select>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="numero_cuenta" class="form-label">Número de Cuenta</label>
                                        <input type="text" class="form-control" id="numero_cuenta" name="numero_cuenta" value="<?php echo htmlspecialchars($proyecto['numero_cuenta'] ?: ''); ?>">
                                    </div>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-12 mb-3">
                                        <label for="referencia_ud" class="form-label">Referencia UD</label>
                                        <input type="text" class="form-control" id="referencia_ud" name="referencia_ud" value="<?php echo htmlspecialchars($proyecto['referencia_ud'] ?: ''); ?>">
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Pestaña de Contactos -->
                            <div class="tab-pane fade" id="contacto" role="tabpanel" aria-labelledby="contacto-tab">
                                <div class="row">
                                    <div class="col-md-12 mb-3">
                                        <label for="supervisor" class="form-label">Supervisor</label>
                                        <input type="text" class="form-control" id="supervisor" name="supervisor" value="<?php echo htmlspecialchars($proyecto['supervisor'] ?: ''); ?>">
                                    </div>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="email_main" class="form-label">Correo Principal</label>
                                        <input type="email" class="form-control" id="email_main" name="email_main" value="<?php echo htmlspecialchars($proyecto['email_main'] ?: ''); ?>">
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="email_secundarios" class="form-label">Correos Secundarios</label>
                                        <input type="text" class="form-control" id="email_secundarios" name="email_secundarios" 
                                               value="<?php echo htmlspecialchars($proyecto['email_secundarios'] ?: ''); ?>"
                                               placeholder="Separados por coma o punto y coma">
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Pestaña de Información Adicional -->
                            <div class="tab-pane fade" id="adicional" role="tabpanel" aria-labelledby="adicional-tab">
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="enlace_secop" class="form-label">Enlace SECOP</label>
                                        <input type="url" class="form-control" id="enlace_secop" name="enlace_secop" value="<?php echo htmlspecialchars($proyecto['enlace_secop'] ?: ''); ?>">
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="acto_administrativo" class="form-label">Acto Administrativo</label>
                                        <input type="text" class="form-control" id="acto_administrativo" name="acto_administrativo" value="<?php echo htmlspecialchars($proyecto['acto_administrativo'] ?: ''); ?>">
                                    </div>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="beneficiarios" class="form-label">Beneficiarios</label>
                                        <input type="text" class="form-control" id="beneficiarios" name="beneficiarios" value="<?php echo formatearMoneda($proyecto['beneficiarios'] ?: 0); ?>">
                                        <small class="text-muted">Número de personas beneficiadas por el proyecto</small>
                                    </div>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-12 mb-3">
                                        <label for="datos_adicionales" class="form-label">Datos Adicionales</label>
                                        <textarea class="form-control" id="datos_adicionales" name="datos_adicionales" rows="3"><?php echo htmlspecialchars($proyecto['datos_adicionales'] ?: ''); ?></textarea>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row mt-4">
                            <div class="col-12">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save me-1"></i> Guardar Cambios
                                </button>
                                <a href="main.php?page=proyecto_individual&id=<?php echo $proyecto_id; ?>" class="btn btn-secondary">
                                    <i class="fas fa-times me-1"></i> Cancelar
                                </a>
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
});
</script>