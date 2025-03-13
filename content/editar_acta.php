<?php
/**
 * Página para editar un acta existente de un proyecto
 */

// Verificar que se hayan proporcionado los parámetros necesarios
if (!isset($_GET['proyecto_id']) || !is_numeric($_GET['proyecto_id']) || 
    !isset($_GET['numero_acta']) || !is_numeric($_GET['numero_acta']) || 
    !isset($_GET['tipo_acta']) || !is_numeric($_GET['tipo_acta'])) {
    // Redirigir a la lista de proyectos si no se proporcionan IDs válidos
    header('Location: main.php?page=proyecto');
    exit;
}

$proyecto_id = intval($_GET['proyecto_id']);
$numero_acta = intval($_GET['numero_acta']);
$tipo_acta = intval($_GET['tipo_acta']);

// Incluir los modelos necesarios
if (file_exists('models/proyecto_model.php')) {
    include_once 'models/proyecto_model.php';
} else {
    echo '<div class="alert alert-danger">Error: No se puede encontrar el modelo de proyectos.</div>';
    exit;
}

// Incluir explícitamente el modelo de actas
if (file_exists('models/actas_proyecto_model.php')) {
    include_once 'models/actas_proyecto_model.php';
} else {
    echo '<div class="alert alert-danger">Error: No se puede encontrar el modelo de actas.</div>';
    exit;
}

// Obtener información del proyecto
$proyecto = obtenerProyectoDetalle($proyecto_id);

// Si no se encuentra el proyecto, mostrar mensaje de error
if (!$proyecto) {
    echo '<div class="alert alert-danger" role="alert">El proyecto solicitado no existe o no se puede acceder a él en este momento.</div>';
    exit;
}

// Verificar si la función ya existe
if (!function_exists('obtenerDetalleActa')) {
    /**
     * Función local para obtener el detalle de un acta específica
     * @param int $anio_pro Año del proyecto
     * @param int $numero_pro Número del proyecto
     * @param int $numero_acta Número del acta
     * @return array|null Detalles del acta o null si no existe
     */
    function obtenerDetalleActa($anio_pro, $numero_pro, $numero_acta) {
        try {
            // Obtener conexión
            $conn = conectarOracle();
            
            // Consulta SQL
            $sql = "SELECT 
                        a.ANIO_PRO, a.NUMERO_PRO, a.NUMERO_ACTA, a.TIPO_ACTA,
                        t.DESCRIPCION as TIPO_DESCRIPCION, a.FECHA_ACTA, a.OBSERVA,
                        a.ESTADO, a.USUARIO, d.ARCHIVO
                    FROM 
                        ACTA_PROYECTO a
                    LEFT JOIN 
                        TIPO_ACTA t ON a.TIPO_ACTA = t.CODIGO
                    LEFT JOIN 
                        DOCUMENTO_PROYECTO d ON a.ANIO_PRO = d.ANIO_PRO 
                                             AND a.NUMERO_PRO = d.NUMERO_PRO 
                                             AND a.NUMERO_ACTA = d.NUMERO_DOC 
                                             AND a.TIPO_ACTA = d.TIPO_DOC
                    WHERE 
                        a.ANIO_PRO = :anio_pro
                        AND a.NUMERO_PRO = :numero_pro
                        AND a.NUMERO_ACTA = :numero_acta";
            
            // Preparar consulta
            $stid = oci_parse($conn, $sql);
            
            // Vincular parámetros
            oci_bind_by_name($stid, ':anio_pro', $anio_pro);
            oci_bind_by_name($stid, ':numero_pro', $numero_pro);
            oci_bind_by_name($stid, ':numero_acta', $numero_acta);
            
            // Ejecutar consulta
            $r = oci_execute($stid);
            if (!$r) {
                $e = oci_error($stid);
                error_log("Error al ejecutar consulta: " . $e['message']);
                return null;
            }
            
            // Obtener resultado
            $row = oci_fetch_assoc($stid);
            
            // Convertir claves a minúsculas
            if ($row) {
                $acta = array();
                foreach ($row as $key => $value) {
                    $acta[strtolower($key)] = $value;
                }
                $result = $acta;
            } else {
                $result = null;
            }
            
            // Liberar recursos
            oci_free_statement($stid);
            oci_close($conn);
            
            return $result;
            
        } catch (Exception $e) {
            error_log("Error en obtenerDetalleActa: " . $e->getMessage());
            return null;
        }
    }
}

// Obtener el detalle del acta
$acta = obtenerDetalleActa($proyecto['anio_pro'], $proyecto_id, $numero_acta);

// Si no se encuentra el acta, mostrar mensaje de error
if (!$acta) {
    echo '<div class="alert alert-danger" role="alert">El acta solicitada no existe o no se puede acceder a ella en este momento.</div>';
    exit;
}

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
    $tipo_acta_nuevo = isset($_POST['tipo_acta']) ? trim($_POST['tipo_acta']) : '';
    $fecha_acta = isset($_POST['fecha_acta']) ? trim($_POST['fecha_acta']) : '';
    $observaciones = isset($_POST['observaciones']) ? trim($_POST['observaciones']) : '';
    
    // Validar datos obligatorios
    if (empty($tipo_acta_nuevo)) {
        $error = 'El tipo de acta es obligatorio';
    } else if (empty($fecha_acta)) {
        $error = 'La fecha del acta es obligatoria';
    } else {
        // Preparar datos del acta para actualización
        $datos_acta = [
            'anio_pro' => $proyecto['anio_pro'],
            'numero_pro' => $proyecto['numero_pro'],
            'numero_acta' => $numero_acta,
            'tipo_acta' => $tipo_acta_nuevo,
            'fecha_acta' => $fecha_acta,
            'observa' => $observaciones,
            'usuario' => $_SESSION['username'] ?? 'ADMIN' // Usuario actual o predeterminado
        ];
        
        // Si se subió un nuevo archivo, procesarlo
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
        
        // Si no hay errores, actualizar el acta
        if (empty($error)) {
            // Verificar si la función existe
            if (!function_exists('actualizarActaProyecto')) {
                /**
                 * Función local para actualizar un acta existente y su documento asociado
                 * @param array $datos Datos del acta a actualizar
                 * @param string $archivo_tmp Ruta temporal del archivo subido (opcional)
                 * @return bool|array True si fue exitoso, o array con error si falló
                 */
                function actualizarActaProyecto($datos, $archivo_tmp = null) {
                    try {
                        // Validar datos obligatorios
                        if (empty($datos['anio_pro']) || empty($datos['numero_pro']) || 
                            empty($datos['numero_acta']) || empty($datos['tipo_acta']) || 
                            empty($datos['fecha_acta'])) {
                            return ['error' => 'Faltan datos obligatorios para actualizar el acta'];
                        }
                        
                        // Obtener conexión
                        $conn = conectarOracle();
                        
                        // Iniciar transacción
                        oci_set_action($conn, 'actualizarActaProyecto');
                        
                        // 1. Actualizar en la tabla ACTA_PROYECTO
                        $sql = "UPDATE ACTA_PROYECTO SET 
                                    TIPO_ACTA = :tipo_acta, 
                                    FECHA_ACTA = TO_DATE(:fecha_acta, 'YYYY-MM-DD'), 
                                    OBSERVA = :observa, 
                                    USUARIO = :usuario
                                WHERE 
                                    ANIO_PRO = :anio_pro 
                                    AND NUMERO_PRO = :numero_pro 
                                    AND NUMERO_ACTA = :numero_acta";
                        
                        // Preparar consulta
                        $stid = oci_parse($conn, $sql);
                        if (!$stid) {
                            $e = oci_error($conn);
                            error_log("Error al preparar consulta de actualización: " . $e['message']);
                            return ['error' => $e['message']];
                        }
                        
                        // Vincular parámetros
                        oci_bind_by_name($stid, ':tipo_acta', $datos['tipo_acta']);
                        oci_bind_by_name($stid, ':fecha_acta', $datos['fecha_acta']);
                        oci_bind_by_name($stid, ':observa', $datos['observa']);
                        oci_bind_by_name($stid, ':usuario', $datos['usuario']);
                        oci_bind_by_name($stid, ':anio_pro', $datos['anio_pro']);
                        oci_bind_by_name($stid, ':numero_pro', $datos['numero_pro']);
                        oci_bind_by_name($stid, ':numero_acta', $datos['numero_acta']);
                        
                        // Ejecutar consulta
                        $r = oci_execute($stid, OCI_NO_AUTO_COMMIT);
                        if (!$r) {
                            $e = oci_error($stid);
                            error_log("Error al actualizar en ACTA_PROYECTO: " . $e['message']);
                            oci_rollback($conn);
                            return ['error' => $e['message']];
                        }
                        
                        // Si se proporcionó un archivo, actualizar o crear documento
                        if ($archivo_tmp && file_exists($archivo_tmp)) {
                            // Primero buscar si ya existe un documento asociado a esta acta
                            $sql_check_doc = "SELECT ARCHIVO FROM DOCUMENTO_PROYECTO 
                                        WHERE ANIO_PRO = :anio_pro 
                                        AND NUMERO_PRO = :numero_pro 
                                        AND NUMERO_DOC = :numero_acta
                                        AND TIPO_DOC = :tipo_acta";
                            
                            $stid_check_doc = oci_parse($conn, $sql_check_doc);
                            oci_bind_by_name($stid_check_doc, ':anio_pro', $datos['anio_pro']);
                            oci_bind_by_name($stid_check_doc, ':numero_pro', $datos['numero_pro']);
                            oci_bind_by_name($stid_check_doc, ':numero_acta', $datos['numero_acta']);
                            oci_bind_by_name($stid_check_doc, ':tipo_acta', $datos['tipo_acta']);
                            oci_execute($stid_check_doc);
                            
                            $row_doc = oci_fetch_assoc($stid_check_doc);
                            $archivo_anterior = $row_doc ? $row_doc['ARCHIVO'] : null;
                            
                            oci_free_statement($stid_check_doc);
                            
                            // Obtener tipo de acta
                            $tipo_acta_info = obtenerTipoActaPorCodigo($datos['tipo_acta']);
                            $tipo_acta_desc = $tipo_acta_info ? strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $tipo_acta_info['descripcion'])) : 'acta';
                            
                            // Generar nombre único para el archivo
                            $extension = strtolower(pathinfo($_FILES['archivo']['name'], PATHINFO_EXTENSION));
                            $nombre_archivo = $datos['anio_pro'] . '_' . $datos['numero_pro'] . '_' . $tipo_acta_desc . '_' . $datos['numero_acta'] . '.' . $extension;
                            
                            // Directorio local
                            $dir_local = 'uploads/actas/';
                            
                            // Crear directorio local si no existe
                            if (!is_dir($dir_local)) {
                                mkdir($dir_local, 0755, true);
                            }
                            
                            // Ruta local
                            $ruta_local = $dir_local . $nombre_archivo;
                            
                            // Intentar mover o subir el archivo
                            $archivo_actualizado = false;
                            
                            // Primero intentamos con SFTP si está disponible
                            if (function_exists('ssh2_connect')) {
                                $sftp_host = '200.69.103.17';
                                $sftp_user = 'oracle';
                                $sftp_pass = 'QDameco2016';
                                $sftp_port = 22;
                                $dir_destino_remoto = '/var/www/html/idexud/siexud/actasproy/upload/';
                                
                                try {
                                    $connection = ssh2_connect($sftp_host, $sftp_port);
                                    if ($connection && ssh2_auth_password($connection, $sftp_user, $sftp_pass)) {
                                        $sftp = ssh2_sftp($connection);
                                        
                                        $remote_file = $dir_destino_remoto . $nombre_archivo;
                                        $stream = fopen("ssh2.sftp://{$sftp}{$remote_file}", 'w');
                                        
                                        if ($stream) {
                                            $data = file_get_contents($archivo_tmp);
                                            fwrite($stream, $data);
                                            fclose($stream);
                                            $archivo_actualizado = true;
                                        }
                                    }
                                } catch (Exception $e) {
                                    error_log("Error SFTP: " . $e->getMessage());
                                }
                            }
                            
                            // Si falló el SFTP, intentamos mover localmente
                            if (!$archivo_actualizado && move_uploaded_file($archivo_tmp, $ruta_local)) {
                                $archivo_actualizado = true;
                            }
                            
                            // Si el archivo se actualizó correctamente
                            if ($archivo_actualizado) {
                                // Actualizar o insertar en la base de datos según si existe ya un documento
                                if ($archivo_anterior) {
                                    $sql_doc = "UPDATE DOCUMENTO_PROYECTO SET 
                                                FECHA_DOC = TO_DATE(:fecha_doc, 'YYYY-MM-DD'), 
                                                ARCHIVO = :archivo, 
                                                USUARIO = :usuario
                                            WHERE 
                                                ANIO_PRO = :anio_pro 
                                                AND NUMERO_PRO = :numero_pro 
                                                AND NUMERO_DOC = :numero_doc
                                                AND TIPO_DOC = :tipo_doc";
                                } else {
                                    $sql_doc = "INSERT INTO DOCUMENTO_PROYECTO (
                                                ANIO_PRO, NUMERO_PRO, NUMERO_DOC, TIPO_DOC, 
                                                FECHA_DOC, ARCHIVO, ESTADO, USUARIO
                                            ) VALUES (
                                                :anio_pro, :numero_pro, :numero_doc, :tipo_doc, 
                                                TO_DATE(:fecha_doc, 'YYYY-MM-DD'), 
                                                :archivo, 'A', :usuario
                                            )";
                                }
                                
                                $stid_doc = oci_parse($conn, $sql_doc);
                                oci_bind_by_name($stid_doc, ':anio_pro', $datos['anio_pro']);
                                oci_bind_by_name($stid_doc, ':numero_pro', $datos['numero_pro']);
                                oci_bind_by_name($stid_doc, ':numero_doc', $datos['numero_acta']);
                                oci_bind_by_name($stid_doc, ':tipo_doc', $datos['tipo_acta']);
                                oci_bind_by_name($stid_doc, ':fecha_doc', $datos['fecha_acta']);
                                oci_bind_by_name($stid_doc, ':archivo', $nombre_archivo);
                                oci_bind_by_name($stid_doc, ':usuario', $datos['usuario']);
                                
                                $r_doc = oci_execute($stid_doc, OCI_NO_AUTO_COMMIT);
                                if (!$r_doc) {
                                    $e = oci_error($stid_doc);
                                    error_log("Error al actualizar/insertar en DOCUMENTO_PROYECTO: " . $e['message']);
                                    oci_rollback($conn);
                                    return ['error' => $e['message']];
                                }
                                
                                oci_free_statement($stid_doc);
                            } else {
                                oci_rollback($conn);
                                return ['error' => 'No se pudo actualizar el archivo. Por favor, inténtelo de nuevo.'];
                            }
                        }
                        
                        // Confirmar transacción
                        oci_commit($conn);
                        
                        // Liberar recursos
                        oci_free_statement($stid);
                        oci_close($conn);
                        
                        return true;
                        
                    } catch (Exception $e) {
                        error_log("Error en actualizarActaProyecto: " . $e->getMessage());
                        return ['error' => "Error general: " . $e->getMessage()];
                    }
                }
                
                // Si necesitamos la función de obtenerTipoActaPorCodigo
                if (!function_exists('obtenerTipoActaPorCodigo')) {
                    function obtenerTipoActaPorCodigo($codigo) {
                        try {
                            // Obtener conexión
                            $conn = conectarOracle();
                            
                            // Consulta SQL para obtener el tipo de acta
                            $sql = "SELECT CODIGO, DESCRIPCION FROM TIPO_ACTA WHERE CODIGO = :codigo";
                            
                            // Preparar consulta
                            $stid = oci_parse($conn, $sql);
                            
                            // Vincular parámetros
                            oci_bind_by_name($stid, ':codigo', $codigo);
                            
                            // Ejecutar consulta
                            oci_execute($stid);
                            
                            // Obtener resultado
                            $row = oci_fetch_assoc($stid);
                            
                            // Liberar recursos
                            oci_free_statement($stid);
                            oci_close($conn);
                            
                            return $row ? [
                                'codigo' => $row['CODIGO'],
                                'descripcion' => $row['DESCRIPCION']
                            ] : null;
                            
                        } catch (Exception $e) {
                            error_log("Error en obtenerTipoActaPorCodigo: " . $e->getMessage());
                            return null;
                        }
                    }
                }
            }
            
            // Intentar actualizar el acta
            $resultado = actualizarActaProyecto($datos_acta, $archivo_tmp);
            
            if (is_array($resultado) && isset($resultado['error'])) {
                $error = $resultado['error'];
            } else {
                $mensaje = 'Acta actualizada correctamente';
                
                // Redirigir a la página del proyecto después de un breve retraso
                echo '<meta http-equiv="refresh" content="2;url=main.php?page=proyecto_individual&id=' . $proyecto_id . '#actas">';
            }
        }
    }
}

// Verificar si la función para obtener documentos existe
if (!function_exists('obtenerDocumentoActa')) {
    /**
     * Función local para obtener el documento asociado a un acta
     * @param int $proyectoId ID del proyecto
     * @param int $numeroActa Número del acta
     * @param int $tipoActa Tipo de acta
     * @return array|null Datos del documento o null si no existe
     */
    function obtenerDocumentoActa($proyectoId, $numeroActa, $tipoActa) {
        try {
            // Obtener conexión
            $conn = conectarOracle();
            
            // Obtener ANIO_PRO del proyecto
            $sqlAnio = "SELECT ANIO_PRO FROM PROYECTO WHERE NUMERO_PRO = :id";
            $stmtAnio = oci_parse($conn, $sqlAnio);
            oci_bind_by_name($stmtAnio, ':id', $proyectoId);
            oci_execute($stmtAnio);
            $rowAnio = oci_fetch_assoc($stmtAnio);
            
            if (!$rowAnio) {
                return null;
            }
            
            $anio_pro = $rowAnio['ANIO_PRO'];
            
            // Consulta SQL para obtener el documento asociado al acta
            $sql = "SELECT 
                        d.ANIO_PRO,
                        d.NUMERO_PRO,
                        d.NUMERO_DOC,
                        d.TIPO_DOC,
                        d.FECHA_DOC,
                        d.ARCHIVO,
                        d.ESTADO
                    FROM 
                        DOCUMENTO_PROYECTO d
                    WHERE 
                        d.ANIO_PRO = :anio_pro
                        AND d.NUMERO_PRO = :numero_pro
                        AND d.TIPO_DOC = :tipo_acta
                        AND d.NUMERO_DOC = :numero_acta
                        AND d.ESTADO = 'A'";
            
            // Preparar consulta
            $stid = oci_parse($conn, $sql);
            
            // Vincular parámetros
            oci_bind_by_name($stid, ':anio_pro', $anio_pro);
            oci_bind_by_name($stid, ':numero_pro', $proyectoId);
            oci_bind_by_name($stid, ':tipo_acta', $tipoActa);
            oci_bind_by_name($stid, ':numero_acta', $numeroActa);
            
            // Ejecutar consulta
            $r = oci_execute($stid);
            if (!$r) {
                $e = oci_error($stid);
                error_log("Error al ejecutar consulta de documento acta: " . $e['message']);
                return null;
            }
            
            // Obtener resultado
            $row = oci_fetch_assoc($stid);
            
            // Convertir claves a minúsculas (para mantener consistencia)
            if ($row) {
                $documento = array();
                foreach ($row as $key => $value) {
                    $documento[strtolower($key)] = $value;
                }
            } else {
                $documento = null;
            }
            
            // Liberar recursos
            oci_free_statement($stid);
            oci_close($conn);
            
            return $documento;
            
        } catch (Exception $e) {
            error_log("Error en obtenerDocumentoActa: " . $e->getMessage());
            return null;
        }
    }
}

// Obtener el documento asociado al acta
$documento = obtenerDocumentoActa($proyecto_id, $numero_acta, $acta['tipo_acta']);

// URL base para los documentos
$baseUrlDocumentos = "http://siexud.udistrital.edu.co/idexud/siexud/actasproy/upload/";

// Función para formatear fechas
function formatearFecha($fecha) {
    if (!$fecha) return '-';
    
    try {
        $date = new DateTime($fecha);
        return $date->format('d/m/Y');
    } catch (Exception $e) {
        return '-';
    }
}

function formatearFechaInput($fecha) {
    if (!$fecha) return '';
    
    try {
        $date = new DateTime($fecha);
        return $date->format('Y-m-d');
    } catch (Exception $e) {
        return '';
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
                Editar acta #<?php echo $numero_acta; ?> del proyecto: <strong><?php echo htmlspecialchars($proyecto['nombre']); ?></strong>
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
    
    <!-- Información actual del acta -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header pb-0">
                    <h5 class="mb-0">Información Actual del Acta</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4">
                            <h6 class="text-uppercase text-sm font-weight-bolder">Tipo de Acta</h6>
                            <p class="text-sm mb-0">
                                <?php 
                                $tipo_descripcion = '';
                                foreach ($tipos_acta as $tipo) {
                                    if ($tipo['codigo'] == $acta['tipo_acta']) {
                                        $tipo_descripcion = $tipo['descripcion'];
                                        break;
                                    }
                                }
                                echo htmlspecialchars($tipo_descripcion ?: 'Tipo ' . $acta['tipo_acta']); 
                                ?>
                            </p>
                        </div>
                        <div class="col-md-4">
                            <h6 class="text-uppercase text-sm font-weight-bolder">Fecha del Acta</h6>
                            <p class="text-sm mb-0"><?php echo formatearFecha($acta['fecha_acta']); ?></p>
                        </div>
                        <div class="col-md-4">
                            <h6 class="text-uppercase text-sm font-weight-bolder">Documento Actual</h6>
                            <?php if ($documento && !empty($documento['archivo'])): ?>
                            <p class="text-sm mb-0">
                                <a href="<?php echo $baseUrlDocumentos . htmlspecialchars($documento['archivo']); ?>" target="_blank" class="text-primary">
                                    <i class="fas fa-file-pdf me-1"></i> Ver documento
                                </a>
                            </p>
                            <?php else: ?>
                            <p class="text-sm mb-0 text-muted">No hay documento asociado</p>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <?php if (!empty($acta['observa'])): ?>
                    <div class="row mt-3">
                        <div class="col-12">
                            <h6 class="text-uppercase text-sm font-weight-bolder">Observaciones</h6>
                            <p class="text-sm mb-0"><?php echo nl2br(htmlspecialchars($acta['observa'])); ?></p>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Formulario para editar acta -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header pb-0">
                    <h5 class="mb-0">Editar Información del Acta</h5>
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
                                <label for="archivo" class="form-label">Reemplazar Documento (PDF)</label>
                                <input type="file" class="form-control" id="archivo" name="archivo" accept=".pdf">
                                <small class="text-muted">Deje en blanco para mantener el documento actual. Sólo se permiten archivos PDF.</small>
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
                                    <i class="fas fa-save me-1"></i> Guardar Cambios
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
        
        // Validar archivo si se proporciona uno nuevo
        if (archivoInput.files && archivoInput.files.length > 0) {
            // Validar extensión
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
        if (this.files && this.files.length > 0) {
            // Validar extensión
            const fileName = this.files[0].name;
            const fileExt = fileName.split('.').pop().toLowerCase();
            
            if (fileExt !== 'pdf') {
                this.classList.add('is-invalid');
                this.classList.remove('is-valid');
                alert('Solo se permiten archivos PDF.');
            } else {
                this.classList.remove('is-invalid');
                this.classList.add('is-valid');
            }
        } else {
            this.classList.remove('is-invalid');
            this.classList.remove('is-valid');
        }
    });
});
</script>