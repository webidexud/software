<?php
/**
 * Página para agregar una nueva acta a un proyecto
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

// Verificar si existe el archivo actas_proyecto_manager.php
if (file_exists('models/actas_proyecto_manager.php')) {
    include_once 'models/actas_proyecto_manager.php';
} else {
    // Definir funciones mínimas solo si no existen ya
    if (!function_exists('obtenerTiposActa')) {
        function obtenerTiposActa() {
            return [
                ['codigo' => 1, 'descripcion' => 'Acta de Inicio'],
                ['codigo' => 2, 'descripcion' => 'Acta de Seguimiento'],
                ['codigo' => 3, 'descripcion' => 'Acta de Cierre'],
                ['codigo' => 4, 'descripcion' => 'Acta de Suspensión'],
                ['codigo' => 5, 'descripcion' => 'Acta de Reinicio']
            ];
        }
    }
    
    if (!function_exists('crearActaProyecto')) {
        function crearActaProyecto($datos, $archivo_tmp) {
            // Simulación para desarrollo, ahora con la nueva ruta del servidor remoto
            if (!empty($archivo_tmp)) {
                // Definir la ruta remota para guardar el archivo
                $dir_destino = '/var/www/html/idexud/siexud/actasproy/';
                
                // Nombre único para el archivo
                $nombre_archivo = "acta_" . time() . "_" . basename($_FILES['archivo']['name']);
                
                // Ruta local temporal para el archivo
                $ruta_local_tmp = 'uploads/temp/' . $nombre_archivo;
                
                // Crear directorio local temporal si no existe
                if (!is_dir('uploads/temp/')) {
                    mkdir('uploads/temp/', 0755, true);
                }
                
                // Primero movemos el archivo a la ruta temporal local
                if (move_uploaded_file($archivo_tmp, $ruta_local_tmp)) {
                    // Configuración de la conexión SFTP
                    $sftp_host = '200.69.103.17';
                    $sftp_user = 'root';
                    $sftp_pass = 'QDameco2016';
                    $sftp_port = 22; // Puerto estándar SSH
                    
                    // Intentar subir el archivo al servidor remoto
                    $connection = ssh2_connect($sftp_host, $sftp_port);
                    
                    if ($connection) {
                        if (ssh2_auth_password($connection, $sftp_user, $sftp_pass)) {
                            $sftp = ssh2_sftp($connection);
                            
                            // Ruta completa de destino en el servidor remoto
                            $remoteFile = ssh2_sftp_realpath($sftp, $dir_destino) . '/' . $nombre_archivo;
                            
                            // Subir el archivo
                            $stream = fopen("ssh2.sftp://{$sftp}{$remoteFile}", 'w');
                            
                            if ($stream) {
                                $data_to_send = file_get_contents($ruta_local_tmp);
                                fwrite($stream, $data_to_send);
                                fclose($stream);
                                
                                // Eliminar archivo temporal local
                                unlink($ruta_local_tmp);
                                
                                // Registrar éxito en el log para depuración
                                error_log("Archivo subido correctamente al servidor remoto: {$remoteFile}");
                                
                                return 1; // Simular éxito
                            } else {
                                error_log("Error al abrir stream SFTP para escribir");
                                return ['error' => 'Error al abrir el archivo en el servidor remoto'];
                            }
                        } else {
                            error_log("Error de autenticación SSH");
                            return ['error' => 'Error de autenticación en el servidor remoto'];
                        }
                    } else {
                        error_log("Error de conexión SSH");
                        return ['error' => 'Error de conexión al servidor remoto'];
                    }
                } else {
                    return ['error' => 'Error al mover el archivo subido a la ubicación temporal'];
                }
            }
            return ['error' => 'Error al procesar el archivo'];
        }
    }
}

// Obtener información del proyecto
$proyecto = obtenerProyectoDetalle($proyecto_id);

// Si no se encuentra el proyecto, mostrar mensaje de error
if (!$proyecto) {
    echo '<div class="alert alert-danger" role="alert">El proyecto solicitado no existe o no se puede acceder a él en este momento.</div>';
    exit;
}

// Obtener tipos de actas disponibles
$tipos_acta = obtenerTiposActa();

// Inicializar variables para mensajes
$mensaje = '';
$error = '';

// Procesar el formulario si se envió
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['agregar_acta'])) {
    // Recoger datos del formulario
    $tipo_acta = isset($_POST['tipo_acta']) ? trim($_POST['tipo_acta']) : '';
    $fecha_acta = isset($_POST['fecha_acta']) ? trim($_POST['fecha_acta']) : '';
    $observaciones = isset($_POST['observaciones']) ? trim($_POST['observaciones']) : '';
    
    // Validar datos obligatorios
    if (empty($tipo_acta)) {
        $error = 'El tipo de acta es obligatorio';
    } else if (empty($fecha_acta)) {
        $error = 'La fecha del acta es obligatoria';
    } else if (!isset($_FILES['archivo']) || $_FILES['archivo']['error'] != 0) {
        $error = 'Debe seleccionar un archivo para el acta';
    } else {
        // Validar tipo de archivo
        $archivo_tmp = $_FILES['archivo']['tmp_name'];
        $extension = strtolower(pathinfo($_FILES['archivo']['name'], PATHINFO_EXTENSION));
        $extensiones_permitidas = ['pdf', 'doc', 'docx', 'jpg', 'jpeg', 'png', 'xls', 'xlsx'];
        
        if (!in_array($extension, $extensiones_permitidas)) {
            $error = 'El tipo de archivo no es permitido. Se aceptan: ' . implode(', ', $extensiones_permitidas);
        } else {
            // Preparar datos del acta
            $datos_acta = [
                'anio_pro' => $proyecto['anio_pro'],
                'numero_pro' => $proyecto['numero_pro'],
                'tipo_acta' => $tipo_acta,
                'fecha_acta' => $fecha_acta,
                'observa' => $observaciones,
                'usuario' => $_SESSION['username'] ?? 'ADMIN' // Usuario actual o predeterminado
            ];
            
            // Intentar crear el acta
            $resultado = crearActaProyecto($datos_acta, $archivo_tmp);
            
            if (is_array($resultado) && isset($resultado['error'])) {
                $error = $resultado['error'];
            } else {
                $mensaje = 'Acta agregada correctamente';
                
                // Redirigir a la página del proyecto después de un breve retraso
                echo '<meta http-equiv="refresh" content="2;url=main.php?page=proyecto_individual&id=' . $proyecto_id . '#actas">';
            }
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
?>

<div class="container-fluid py-4">
    <!-- Encabezado de la página -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <h4 class="mb-0">Agregar Acta</h4>
                <a href="main.php?page=proyecto_individual&id=<?php echo $proyecto_id; ?>#actas" class="btn btn-sm btn-outline-secondary">
                    <i class="fas fa-arrow-left me-1"></i> Volver al Proyecto
                </a>
            </div>
            <p class="text-sm text-muted">
                Agregar una nueva acta al proyecto: <strong><?php echo htmlspecialchars($proyecto['nombre']); ?></strong>
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
    
    <!-- Formulario para agregar acta -->
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
                                    <option value="<?php echo $tipo['codigo']; ?>" <?php echo isset($_POST['tipo_acta']) && $_POST['tipo_acta'] == $tipo['codigo'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($tipo['descripcion']); ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                                <small class="text-muted">Seleccione el tipo de acta a registrar</small>
                            </div>
                            
                            <div class="col-md-4 mb-3">
                                <label for="fecha_acta" class="form-label">Fecha del Acta *</label>
                                <input type="date" class="form-control" id="fecha_acta" name="fecha_acta" 
                                       value="<?php echo isset($_POST['fecha_acta']) ? $_POST['fecha_acta'] : date('Y-m-d'); ?>" required>
                                <small class="text-muted">Fecha en que se realizó el acta</small>
                            </div>
                            
                            <div class="col-md-4 mb-3">
                                <label for="archivo" class="form-label">Documento del Acta *</label>
                                <input type="file" class="form-control" id="archivo" name="archivo" required>
                                <small class="text-muted">Formatos permitidos: PDF, DOC, DOCX, JPG, JPEG, PNG, XLS, XLSX</small>
                                <small class="text-info">El archivo se guardará en el servidor remoto (200.69.103.17)</small>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-12 mb-3">
                                <label for="observaciones" class="form-label">Observaciones</label>
                                <textarea class="form-control" id="observaciones" name="observaciones" rows="4"><?php echo isset($_POST['observaciones']) ? htmlspecialchars($_POST['observaciones']) : ''; ?></textarea>
                                <small class="text-muted">Información adicional sobre el acta (opcional)</small>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-12 text-end">
                                <a href="main.php?page=proyecto_individual&id=<?php echo $proyecto_id; ?>#actas" class="btn btn-secondary me-2">
                                    <i class="fas fa-times me-1"></i> Cancelar
                                </a>
                                <button type="submit" name="agregar_acta" class="btn btn-primary">
                                    <i class="fas fa-save me-1"></i> Guardar Acta
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Información sobre tipos de actas -->
    <div class="row mt-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header pb-0">
                    <h5 class="mb-0">Información sobre Tipos de Actas</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Tipo de Acta</th>
                                    <th>Descripción</th>
                                    <th>Uso Recomendado</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>Acta de Inicio</td>
                                    <td>Documento que formaliza el inicio del proyecto</td>
                                    <td>Al comenzar el proyecto, después de la firma del contrato</td>
                                </tr>
                                <tr>
                                    <td>Acta de Seguimiento</td>
                                    <td>Documento que registra el avance y estado del proyecto</td>
                                    <td>Durante la ejecución del proyecto, según los hitos establecidos</td>
                                </tr>
                                <tr>
                                    <td>Acta de Suspensión</td>
                                    <td>Documento que formaliza la suspensión temporal del proyecto</td>
                                    <td>Cuando sea necesario detener temporalmente el proyecto</td>
                                </tr>
                                <tr>
                                    <td>Acta de Prórroga</td>
                                    <td>Documento que formaliza la extensión del plazo del proyecto</td>
                                    <td>Cuando se requiera ampliar el tiempo de ejecución</td>
                                </tr>
                                <tr>
                                    <td>Acta de Cierre</td>
                                    <td>Documento que formaliza la finalización del proyecto</td>
                                    <td>Al completar todas las actividades y entregables del proyecto</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
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
        
        // Validar archivo
        if (!archivoInput.files || archivoInput.files.length === 0) {
            archivoInput.classList.add('is-invalid');
            hasError = true;
        } else {
            // Validar extensión
            const fileName = archivoInput.files[0].name;
            const fileExt = fileName.split('.').pop().toLowerCase();
            const allowedExts = ['pdf', 'doc', 'docx', 'jpg', 'jpeg', 'png', 'xls', 'xlsx'];
            
            if (!allowedExts.includes(fileExt)) {
                archivoInput.classList.add('is-invalid');
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
            const allowedExts = ['pdf', 'doc', 'docx', 'jpg', 'jpeg', 'png', 'xls', 'xlsx'];
            
            if (!allowedExts.includes(fileExt)) {
                this.classList.add('is-invalid');
                alert('Tipo de archivo no permitido. Se aceptan: ' + allowedExts.join(', '));
            } else {
                this.classList.add('is-valid');
            }
        } else {
            this.classList.remove('is-valid');
        }
    });
});
</script>