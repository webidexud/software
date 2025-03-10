<?php
/**
 * Página para eliminar un acta existente de un proyecto
 */

// Verificar que se hayan proporcionado los parámetros necesarios
if (!isset($_GET['proyecto_id']) || !is_numeric($_GET['proyecto_id']) || !isset($_GET['acta_id']) || !is_numeric($_GET['acta_id'])) {
    // Redirigir a la lista de proyectos si no se proporcionan IDs válidos
    header('Location: main.php?page=proyecto');
    exit;
}

$proyecto_id = intval($_GET['proyecto_id']);
$acta_id = intval($_GET['acta_id']);
$confirmar = isset($_GET['confirmar']) && $_GET['confirmar'] == '1';

// Incluir los modelos necesarios
if (file_exists('models/proyecto_model.php')) {
    include_once 'models/proyecto_model.php';
} else {
    echo '<div class="alert alert-danger">Error: No se puede encontrar el modelo de proyectos.</div>';
    exit;
}

// Verificar que las funciones necesarias existen
if (!function_exists('obtenerProyectoDetalle') || !function_exists('obtenerDetalleActa') || !function_exists('eliminarActaProyecto')) {
    echo '<div class="alert alert-danger">Error: Algunas funciones necesarias no están disponibles.</div>';
    exit;
}

// Obtener información del proyecto
$proyecto = obtenerProyectoDetalle($proyecto_id);

// Si no se encuentra el proyecto, mostrar mensaje de error
if (!$proyecto) {
    echo '<div class="alert alert-danger" role="alert">El proyecto solicitado no existe o no se puede acceder a él en este momento.</div>';
    exit;
}

// Obtener el detalle del acta a eliminar
$acta = obtenerDetalleActa($proyecto['anio_pro'], $proyecto_id, $acta_id);

// Si no se encuentra el acta, mostrar mensaje de error
if (!$acta) {
    echo '<div class="alert alert-danger" role="alert">El acta solicitada no existe o no se puede acceder a ella en este momento.</div>';
    exit;
}

// Inicializar variables para mensajes
$mensaje = '';
$error = '';

// Procesar la confirmación de eliminación
if ($confirmar && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['eliminar_acta'])) {
    // Intentar eliminar el acta
    $resultado = eliminarActaProyecto($proyecto['anio_pro'], $proyecto_id, $acta_id);
    
    if (is_array($resultado) && isset($resultado['error'])) {
        $error = $resultado['error'];
    } else {
        $mensaje = 'Acta eliminada correctamente';
        
        // Redirigir a la página del proyecto después de un breve retraso
        echo '<meta http-equiv="refresh" content="2;url=main.php?page=proyecto_individual&id=' . $proyecto_id . '#actas">';
    }
}

// Obtener información del tipo de acta
$tipo_acta_descripcion = '';
if ($acta && isset($acta['tipo_acta'])) {
    foreach ($tipos_acta = obtenerTiposActa() as $tipo) {
        if ($tipo['codigo'] == $acta['tipo_acta']) {
            $tipo_acta_descripcion = $tipo['descripcion'];
            break;
        }
    }
}

// Función para formatear fechas
function formatearFecha($fecha) {
    if (!$fecha) return "No definida";
    
    try {
        $date = new DateTime($fecha);
        return $date->format('d/m/Y');
    } catch (Exception $e) {
        return "No definida";
    }
}
?>

<div class="container-fluid py-4">
    <!-- Encabezado de la página -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <h4 class="mb-0">Eliminar Acta</h4>
                <a href="main.php?page=proyecto_individual&id=<?php echo $proyecto_id; ?>#actas" class="btn btn-sm btn-outline-secondary">
                    <i class="fas fa-arrow-left me-1"></i> Volver al Proyecto
                </a>
            </div>
            <p class="text-sm text-muted">
                Eliminar acta del proyecto: <strong><?php echo htmlspecialchars($proyecto['nombre']); ?></strong>
            </p>
        </div>
    </div>
    
    <!-- Mensajes de error o éxito -->
    <?php if (!empty($error)): ?>
    <div class="alert alert-danger" role="alert">
        <i class="fas fa-exclamation-triangle me-2"></i> <?php echo $error; ?>
    </div>
    <?php endif; ?>
    
    <?php if (!empty($mensaje)): ?>
    <div class="alert alert-success" role="alert">
        <i class="fas fa-check-circle me-2"></i> <?php echo $mensaje; ?>
    </div>
    <?php endif; ?>
    
    <!-- Panel de confirmación -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header bg-danger text-white">
                    <h5 class="mb-0"><i class="fas fa-exclamation-triangle me-2"></i> Confirmar Eliminación</h5>
                </div>
                <div class="card-body">
                    <?php if (!$confirmar): ?>
                    <div class="alert alert-warning">
                        <h6 class="mb-3">¿Está seguro que desea eliminar esta acta?</h6>
                        <p>Esta acción eliminará permanentemente el acta y su documento asociado. No podrá recuperar esta información.</p>
                    </div>
                    
                    <div class="row mb-4">
                        <div class="col-md-12">
                            <h6 class="text-uppercase text-sm font-weight-bolder">Datos del Acta a Eliminar</h6>
                            <table class="table table-sm table-bordered">
                                <tr>
                                    <th style="width: 150px;">Número de Acta</th>
                                    <td><?php echo $acta['numero_acta']; ?></td>
                                </tr>
                                <tr>
                                    <th>Tipo</th>
                                    <td><?php echo htmlspecialchars($tipo_acta_descripcion); ?></td>
                                </tr>
                                <tr>
                                    <th>Fecha</th>
                                    <td><?php echo formatearFecha($acta['fecha_acta']); ?></td>
                                </tr>
                                <tr>
                                    <th>Observaciones</th>
                                    <td><?php echo nl2br(htmlspecialchars($acta['observa'] ?: 'No hay observaciones')); ?></td>
                                </tr>
                                <?php if (!empty($acta['archivo'])): ?>
                                <tr>
                                    <th>Documento</th>
                                    <td>
                                        <a href="http://200.69.103.17/idexud/siexud/actasproy/<?php echo htmlspecialchars($acta['archivo']); ?>" target="_blank">
                                            <i class="fas fa-file-pdf me-1"></i><?php echo htmlspecialchars($acta['archivo']); ?>
                                        </a>
                                    </td>
                                </tr>
                                <?php endif; ?>
                            </table>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-12 text-end">
                            <a href="main.php?page=proyecto_individual&id=<?php echo $proyecto_id; ?>#actas" class="btn btn-secondary me-2">
                                <i class="fas fa-times me-1"></i> Cancelar
                            </a>
                            <a href="main.php?page=eliminar_acta&proyecto_id=<?php echo $proyecto_id; ?>&acta_id=<?php echo $acta_id; ?>&confirmar=1" class="btn btn-danger">
                                <i class="fas fa-trash me-1"></i> Confirmar Eliminación
                            </a>
                        </div>
                    </div>
                    
                    <?php else: ?>
                    <!-- Formulario de eliminación final -->
                    <form method="POST" action="">
                        <div class="alert alert-danger">
                            <h6 class="mb-3">Confirmación Final</h6>
                            <p>¿Está absolutamente seguro que desea eliminar el acta número <?php echo $acta['numero_acta']; ?> de tipo "<?php echo htmlspecialchars($tipo_acta_descripcion); ?>"?</p>
                            <p><strong>Esta acción no se puede deshacer.</strong></p>
                        </div>
                        
                        <div class="row">
                            <div class="col-12 text-end">
                                <a href="main.php?page=proyecto_individual&id=<?php echo $proyecto_id; ?>#actas" class="btn btn-secondary me-2">
                                    <i class="fas fa-times me-1"></i> Cancelar
                                </a>
                                <button type="submit" name="eliminar_acta" class="btn btn-danger">
                                    <i class="fas fa-trash me-1"></i> Eliminar Definitivamente
                                </button>
                            </div>
                        </div>
                    </form>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>