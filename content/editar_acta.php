<?php
/**
 * Página para editar un acta existente de un proyecto
 */

// Verificar que se hayan proporcionado los parámetros necesarios
if (!isset($_GET['proyecto_id']) || !is_numeric($_GET['proyecto_id']) || !isset($_GET['acta_id']) || !is_numeric($_GET['acta_id'])) {
    // Redirigir a la lista de proyectos si no se proporcionan IDs válidos
    header('Location: main.php?page=proyecto');
    exit;
}

$proyecto_id = intval($_GET['proyecto_id']);
$acta_id = intval($_GET['acta_id']);

// Incluir los modelos necesarios
if (file_exists('models/proyecto_model.php')) {
    include_once 'models/proyecto_model.php';
} else {
    echo '<div class="alert alert-danger">Error: No se puede encontrar el modelo de proyectos.</div>';
    exit;
}

// Verificar que las funciones necesarias existen
if (!function_exists('obtenerProyectoDetalle') || !function_exists('obtenerDetalleActa') || !function_exists('actualizarActaProyecto')) {
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

// Obtener el detalle del acta a editar
$acta = obtenerDetalleActa($proyecto['anio_pro'], $proyecto_id, $acta_id);

// Si no se encuentra el acta, mostrar mensaje de error
if (!$acta) {
    echo '<div class="alert alert-danger" role="alert">El acta solicitada no existe o no se puede acceder a ella en este momento.</div>';
    exit;
}

// Obtener tipos de actas disponibles
$tipos_acta = obtenerTiposActa();

// Inicializar variables para mensajes
$mensaje = '';
$error = '';

// Procesar el formulario si se envió
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['editar_acta'])) {
    // Recoger datos del formulario
    $tipo_acta = isset($_POST['tipo_acta']) ? trim($_POST['tipo_acta']) : '';
    $fecha_acta = isset($_POST['fecha_acta']) ? trim($_POST['fecha_acta']) : '';
    $observaciones = isset($_POST['observaciones']) ? trim($_POST['observaciones']) : '';
    
    // Validar datos obligatorios
    if (empty($tipo_acta)) {
        $error = 'El tipo de acta es obligatorio';
    } else if (empty($fecha_acta)) {
        $error = 'La fecha del acta es obligatoria';
    } else {
        // Preparar datos del acta
        $datos_acta = [
            'anio_pro' => $proyecto['anio_pro'],
            'numero_pro' => $proyecto['numero_pro'],
            'numero_acta' => $acta_id,
            'tipo_acta' => $tipo_acta,
            'fecha_acta' => $fecha_acta,
            'observa' => $observaciones,
            'usuario' => $_SESSION['username'] ?? 'ADMIN' // Usuario actual o predeterminado
        ];
        
        // Verificar si se subió un nuevo archivo
        $archivo_tmp = null;
        if (isset($_FILES['archivo']) && $_FILES['archivo']['error'] == 0) {
            // Validar tipo de archivo
            $extension = strtolower(pathinfo($_FILES['archivo']['name'], PATHINFO_EXTENSION));
            
            // Solo aceptar archivos PDF
            if ($extension != 'pdf') {
                $error = 'Solo se permiten archivos PDF';
            } else {
                $archivo_tmp = $_FILES['archivo']['tmp_name'];
            }
        }
        
        if (empty($error)) {
            // Intentar actualizar el acta
            $resultado = actualizarActaProyecto($datos_acta, $archivo_tmp);
            
            if (is_array($resultado) && isset($resultado['error'])) {
                $error = $resultado['error'];
            } else {
                $mensaje = 'Acta actualizada correctamente';
                
                // Recargar los datos del acta para mostrar la información actualizada
                $acta = obtenerDetalleActa($proyecto['anio_pro'], $proyecto_id, $acta_id);
                
                // Redirigir a la página del proyecto después de un breve retraso
                echo '<meta http-equiv="refresh" content="2;url=main.php?page=proyecto_individual&id=' . $proyecto_id . '#actas">';
            }
        }
    }
}

// Función para formatear fechas para inputs
function formatearFechaInput($fecha) {
    if (!$fecha) return "";
    
    try {
        $date = new DateTime($fecha);
        return $date->format('Y-m-d');
    } catch (Exception $e) {
        return "";
    }
}
?>

<div class="container-fluid py-4">
    <!-- Encabezado de la página -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <h4 class="mb-0">Editar Acta</h4>
                <a href="main.php?page=proyecto_individual&id=<?php echo $proyecto_id; ?>#actas" class="btn btn-sm btn-outline-secondary">
                    <i class="fas fa-arrow-left me-1"></i> Volver al Proyecto
                </a>
            </div>
            <p class="text-sm text-muted">
                Editar acta para el proyecto: <strong><?php echo htmlspecialchars($proyecto['nombre']); ?></strong>
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
    
    <!-- Información resumida del proyecto -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <h6 class="text-uppercase text-sm font-weight-bolder">Proyecto</h6>
                            <p class="text-sm mb-0"><?php echo htmlspecialchars($proyecto['nombre']); ?></p>
                        </div>
                        <div class="col-md-3">
                            <h6 class="text-uppercase text-sm font-weight-bolder">Número</h6>
                            <p class="text-sm mb-0"><?php echo htmlspecialchars($proyecto['numero_pro_entidad']); ?></p>
                        </div>
                        <div class="col-md-3">
                            <h6 class="text-uppercase text-sm font-weight-bolder">Entidad</h6>
                            <p class="text-sm mb-0"><?php echo htmlspecialchars($proyecto['entidad']); ?></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Formulario para editar acta -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header pb-0">
                    <h5 class="mb-0">Información del Acta</h5>
                </div>
                <div class="card-body">
                    <form method="POST" action="" enctype="multipart/form-data">
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label for="tipo_acta" class="form-label">Tipo de Acta *</label>
                                <select class="form-select" id="tipo_acta" name="tipo_acta" required>
                                    <option value="">Seleccione un tipo...</option>
                                    <?php foreach ($tipos_acta as $tipo): ?>
                                    <option value="<?php echo $tipo['codigo']; ?>" <?php echo $acta['tipo_acta'] == $tipo['codigo'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($tipo['descripcion']); ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                                <small class="text-muted">Seleccione el tipo de acta</small>
                            </div>
                            
                            <div class="col-md-4 mb-3">
                                <label for="fecha_acta" class="form-label">Fecha del Acta *</label>
                                <input type="date" class="form-control" id="fecha_acta" name="fecha_acta" 
                                       value="<?php echo formatearFechaInput($acta['fecha_acta']); ?>" required>
                                <small class="text-muted">Fecha en que se realizó el acta</small>
                            </div>
                            
                            <div class="col-md-4 mb-3">
                                <label for="archivo" class="form-label">Documento del Acta (PDF)</label>
                                <input type="file" class="form-control" id="archivo" name="archivo" accept=".pdf">
                                <small class="text-muted">Sólo se permiten archivos PDF. Deje vacío para mantener el documento actual.</small>
                                <?php if (!empty($acta['archivo'])): ?>
                                <div class="mt-2">
                                    <strong>Documento actual:</strong> 
                                    <a href="http://200.69.103.17/idexud/siexud/actasproy/<?php echo htmlspecialchars($acta['archivo']); ?>" target="_blank" class="text-primary">
                                        <i class="fas fa-file-pdf me-1"></i><?php echo htmlspecialchars($acta['archivo']); ?>
                                    </a>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-12 mb-3">
                                <label for="observaciones" class="form-label">Observaciones</label>
                                <textarea class="form-control" id="observaciones" name="observaciones" rows="4"><?php echo htmlspecialchars($acta['observa'] ?: ''); ?></textarea>
                                <small class="text-muted">Información adicional sobre el acta (opcional)</small>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-12 text-end">
                                <a href="main.php?page=proyecto_individual&id=<?php echo $proyecto_id; ?>#actas" class="btn btn-secondary me-2">
                                    <i class="fas fa-times me-1"></i> Cancelar
                                </a>
                                <button type="submit" name="editar_acta" class="btn btn-primary">
                                    <i class="fas fa-save me-1"></i> Actualizar Acta
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Validación del formulario
    const form = document.querySelector('form');
    const tipoActaSelect = document.getElementById('tipo_acta');
    const fechaActaInput = document.getElementById('fecha_acta');
    const archivoInput = document.getElementById('archivo');
    
    form.addEventListener('submit', function(event) {
        let hasError = false;
        
        // Validar tipo de acta
        if (!tipoActaSelect.value) {
            tipoActaSelect.classList.add('is-invalid');
            hasError = true;
        } else {
            tipoActaSelect.classList.remove('is-invalid');
            tipoActaSelect.classList.add('is-valid');
        }
        
        // Validar fecha
        if (!fechaActaInput.value) {
            fechaActaInput.classList.add('is-invalid');
            hasError = true;
        } else {
            fechaActaInput.classList.remove('is-invalid');
            fechaActaInput.classList.add('is-valid');
        }
        
        // Validar archivo solo si se ha seleccionado uno
        if (archivoInput.files && archivoInput.files.length > 0) {
            const fileName = archivoInput.files[0].name;
            const fileExt = fileName.split('.').pop().toLowerCase();
            
            if (fileExt !== 'pdf') {
                archivoInput.classList.add('is-invalid');
                alert('Solo se permiten archivos PDF.');
                hasError = true;
            } else {
                archivoInput.classList.remove('is-invalid');
                archivoInput.classList.add('is-valid');
            }
        }
        
        if (hasError) {
            event.preventDefault();
            alert('Por favor, complete todos los campos obligatorios correctamente.');
        }
    });
    
    // Limpiar validación al cambiar los campos
    tipoActaSelect.addEventListener('change', function() {
        this.classList.remove('is-invalid');
        if (this.value) {
            this.classList.add('is-valid');
        } else {
            this.classList.remove('is-valid');
        }
    });
    
    fechaActaInput.addEventListener('change', function() {
        this.classList.remove('is-invalid');
        if (this.value) {
            this.classList.add('is-valid');
        } else {
            this.classList.remove('is-valid');
        }
    });
    
    archivoInput.addEventListener('change', function() {
        this.classList.remove('is-invalid');
        if (this.files && this.files.length > 0) {
            // Validar extensión
            const fileName = this.files[0].name;
            const fileExt = fileName.split('.').pop().toLowerCase();
            
            if (fileExt !== 'pdf') {
                this.classList.add('is-invalid');
                alert('Solo se permiten archivos PDF.');
            } else {
                this.classList.add('is-valid');
            }
        } else {
            this.classList.remove('is-valid');
        }
    });
});
</script>