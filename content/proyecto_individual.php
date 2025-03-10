<?php
/**
 * Página para visualizar el detalle completo de un proyecto
 */

// Verificar que se haya proporcionado un ID
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    // Redirigir a la lista de proyectos si no se proporciona un ID válido
    header('Location: main.php?page=proyecto');
    exit;
}

$proyecto_id = intval($_GET['id']);

// Verificar si el archivo modelo existe
if (file_exists('models/proyecto_model.php')) {
    // Incluir el modelo de proyecto
    include_once 'models/proyecto_model.php';
    
    // Obtener el detalle del proyecto
    $proyecto = function_exists('obtenerProyectoDetalle') ? obtenerProyectoDetalle($proyecto_id) : null;
} else {
    // Si no encuentra el modelo, mostrar mensaje de error
    $proyecto = null;
}

// Si no se encuentra el proyecto, mostrar mensaje de error
if (!$proyecto) {
    echo '<div class="alert alert-danger" role="alert">El proyecto solicitado no existe o no se puede acceder a él en este momento.</div>';
    exit;
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

// Obtener información adicional del tipo de proyecto
$tipo_proyecto_descripcion = "";
if (!empty($proyecto['tipo_proyecto'])) {
    try {
        $conn = conectarOracle();
        $sql = "SELECT DESCRIPCION FROM TIPO_PROYECTO WHERE CODIGO = :codigo";
        $stmt = oci_parse($conn, $sql);
        oci_bind_by_name($stmt, ':codigo', $proyecto['tipo_proyecto']);
        oci_execute($stmt);
        
        $row = oci_fetch_assoc($stmt);
        if ($row) {
            $tipo_proyecto_descripcion = $row['DESCRIPCION'];
        }
        
        oci_free_statement($stmt);
        oci_close($conn);
    } catch (Exception $e) {
        error_log("Error al obtener descripción de tipo de proyecto: " . $e->getMessage());
    }
}

// Obtener información del banco
$banco_nombre = "";
if (!empty($proyecto['codigo_bancario'])) {
    try {
        $conn = conectarOracle();
        
        // Asegurarse de que el código sea del tipo correcto
        // Intenta convertir a entero si es un campo numérico
        $codigo_bancario = $proyecto['codigo_bancario'];
        
        // Verifica si el código bancario es numérico
        if (is_numeric($codigo_bancario)) {
            $sql = "SELECT DESCRIPCION FROM BANCO WHERE CODIGO = :codigo";
            $stmt = oci_parse($conn, $sql);
            
            // Asegúrate de que se trata como un número si ese es el tipo esperado
            if (!$stmt) {
                $e = oci_error($conn);
                error_log("Error al preparar consulta de banco: " . $e['message']);
            } else {
                // Convierte explícitamente a entero para asegurar compatibilidad
                $codigo_bancario_int = intval($codigo_bancario);
                oci_bind_by_name($stmt, ':codigo', $codigo_bancario_int);
                
                // Ejecutar la consulta con manejo de errores
                $rBanco = @oci_execute($stmt);
                
                if ($rBanco) {
                    $rowBanco = oci_fetch_assoc($stmt);
                    if ($rowBanco) {
                        $banco_nombre = $rowBanco['DESCRIPCION'];
                    }
                } else {
                    $e = oci_error($stmt);
                    error_log("Error al consultar banco (código numérico): " . $e['message']);
                    
                    // Intentar como string si falla el enfoque numérico
                    $sql = "SELECT DESCRIPCION FROM BANCO WHERE CODIGO = :codigo";
                    $stmt = oci_parse($conn, $sql);
                    oci_bind_by_name($stmt, ':codigo', $codigo_bancario);
                    $rBanco = @oci_execute($stmt);
                    
                    if ($rBanco) {
                        $rowBanco = oci_fetch_assoc($stmt);
                        if ($rowBanco) {
                            $banco_nombre = $rowBanco['DESCRIPCION'];
                        }
                    } else {
                        $e = oci_error($stmt);
                        error_log("Error al consultar banco (código string): " . $e['message']);
                    }
                }
                
                // Liberar el recurso
                if ($stmt) {
                    oci_free_statement($stmt);
                }
            }
        } else {
            // Si no es numérico, tratar como string
            $sql = "SELECT DESCRIPCION FROM BANCO WHERE CODIGO = :codigo";
            $stmt = oci_parse($conn, $sql);
            
            if (!$stmt) {
                $e = oci_error($conn);
                error_log("Error al preparar consulta de banco (string): " . $e['message']);
            } else {
                oci_bind_by_name($stmt, ':codigo', $codigo_bancario);
                $rBanco = @oci_execute($stmt);
                
                if ($rBanco) {
                    $rowBanco = oci_fetch_assoc($stmt);
                    if ($rowBanco) {
                        $banco_nombre = $rowBanco['DESCRIPCION'];
                    }
                } else {
                    $e = oci_error($stmt);
                    error_log("Error al consultar banco: " . $e['message']);
                }
                
                // Liberar el recurso
                oci_free_statement($stmt);
            }
        }
        
        // Cerrar la conexión
        oci_close($conn);
        
    } catch (Exception $e) {
        error_log("Error al obtener nombre del banco: " . $e->getMessage());
    }
}
?>

<!-- Estilos específicos para esta página -->
<style>
.project-header {
    background-color: #fff;
    border-radius: 10px;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
    padding: 20px;
    margin-bottom: 24px;
    position: relative;
    overflow: hidden;
}
.project-number {
    background-color: rgba(94, 114, 228, 0.1);
    color: #5e72e4;
    font-weight: 600;
    border-radius: 6px;
    padding: 6px 12px;
    font-size: 0.875rem;
    display: inline-block;
}
.project-header::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    width: 8px;
    height: 100%;
    background-color: #5e72e4;
}
.project-title {
    font-weight: 700;
    color: #344767;
    margin-bottom: 5px;
}
.project-subtitle {
    font-size: 1rem;
    color: #67748e;
    font-weight: 500;
}
.project-code {
    background-color: rgba(94, 114, 228, 0.1);
    color: #5e72e4;
    font-weight: 600;
    border-radius: 6px;
    padding: 6px 12px;
    font-size: 0.875rem;
    display: inline-block;
}
.detail-card {
    border-radius: 10px;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
    margin-bottom: 24px;
    border: none;
    overflow: hidden;
}
.detail-card .card-header {
    background-color: #f8f9fa;
    border-bottom: 1px solid rgba(0, 0, 0, 0.05);
    padding: 15px 20px;
}
.detail-card .card-header h6 {
    margin-bottom: 0;
    font-weight: 600;
    color: #344767;
}
.detail-card .card-body {
    padding: 20px;
}
.info-group {
    margin-bottom: 20px;
}
.info-group:last-child {
    margin-bottom: 0;
}
.info-label {
    font-size: 0.75rem;
    font-weight: 600;
    color: #8392AB;
    margin-bottom: 4px;
    display: block;
}
.info-value {
    font-size: 0.875rem;
    color: #344767;
    font-weight: 500;
}
.btn-back {
    transition: all 0.3s;
    font-weight: 600;
}
.btn-back:hover {
    transform: translateX(-5px);
}
.status-badge {
    font-size: 0.75rem;
    padding: 0.3rem 0.75rem;
    border-radius: 6px;
    font-weight: 600;
}
.status-8 {
    background-color: rgba(243, 156, 18, 0.15);
    color: #f39c12;
}
.status-14 {
    background-color: rgba(66, 135, 245, 0.15);
    color: #4287f5;
}
.project-dates {
    display: flex;
    align-items: center;
    margin-top: 15px;
}
.date-divider {
    margin: 0 15px;
    color: #8392AB;
}
.value-card {
    background-color: #fff;
    border-radius: 10px;
    padding: 15px;
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.05);
    height: 100%;
    border-left: 3px solid #5e72e4;
}
.value-title {
    font-size: 0.75rem;
    color: #8392AB;
    margin-bottom: 5px;
}
.value-amount {
    font-size: 1.25rem;
    font-weight: 700;
    color: #344767;
}
.detail-section {
    margin-bottom: 10px;
    padding-bottom: 10px;
    border-bottom: 1px dashed #e9ecef;
}
.detail-section:last-child {
    border-bottom: none;
    margin-bottom: 0;
    padding-bottom: 0;
}
.object-text {
    background-color: #f8f9fa;
    border-radius: 8px;
    padding: 15px;
    border-left: 3px solid #5e72e4;
    font-size: 0.875rem;
    color: #344767;
    line-height: 1.6;
}
.tab-content {
    background-color: #fff;
    border-radius: 0 0 10px 10px;
    padding: 20px;
}
.nav-tabs .nav-link {
    color: #67748e;
    font-weight: 600;
    padding: 10px 16px;
    border: none;
    border-radius: 0;
}
.nav-tabs .nav-link.active {
    color: #5e72e4;
    border-bottom: 2px solid #5e72e4;
    background-color: transparent;
}
.nav-tabs {
    border-bottom: 1px solid #e9ecef;
}
.tab-pane {
    padding: 15px 0;
}
.text-primary-light {
    color: #5e72e4;
}
.link-icon {
    font-size: 0.875rem;
    margin-right: 5px;
    color: #5e72e4;
}
.print-section {
    display: none;
}
@media print {
    .no-print {
        display: none !important;
    }
    .print-section {
        display: block;
    }
    .tab-content {
        display: block !important; 
        opacity: 1 !important;
        visibility: visible !important;
    }
    .tab-pane {
        display: block !important;
        opacity: 1 !important;
        visibility: visible !important;
    }
    .project-header {
        box-shadow: none;
        break-inside: avoid;
    }
    .detail-card {
        box-shadow: none;
        break-inside: avoid;
        page-break-inside: avoid;
        margin-bottom: 20px;
    }
    .value-card {
        box-shadow: none;
    }
    body {
        font-size: 12pt;
        color: #000;
    }
    h4, h5, h6 {
        page-break-after: avoid;
    }
    .page-break {
        page-break-before: always;
    }
    a {
        text-decoration: none !important;
        color: #000 !important;
    }
    .print-header {
        text-align: center;
        margin-bottom: 20px;
    }
    .print-header h2 {
        margin-bottom: 5px;
    }
    .print-header p {
        color: #666;
    }
}
.action-button {
    transition: all 0.2s;
    margin: 0 2px;
    padding: 0.4rem 0.65rem;
    font-size: 0.75rem;
}
.action-button:hover {
    transform: translateY(-2px);
    box-shadow: 0 2px 5px rgba(0, 0, 0, 0.15);
}
</style>

<!-- Sección visible solo en impresión -->
<div class="print-section">
    <div class="print-header">
        <h2>DETALLE DE PROYECTO</h2>
        <p>Sistema de Gestión de Proyectos - SGPOE</p>
    </div>
</div>

<!-- Cabecera del Proyecto -->
<div class="project-header">
    <div class="row">
        <div class="col-md-8">
            <div class="d-flex align-items-center mb-2">
                <a href="main.php?page=proyecto" class="btn btn-sm btn-outline-secondary btn-back me-3 no-print">
                    <i class="fas fa-arrow-left me-1"></i> Volver
                </a>
                <span class="project-code me-2"><?php echo $proyecto['anio_pro']; ?></span>
                <span class="project-code me-3"><?php echo $proyecto['numero_pro_entidad']; ?></span>
                <span class="status-badge status-<?php echo $proyecto['situacion']; ?>">
                    <?php echo isset($proyecto['situacion_descripcion']) ? $proyecto['situacion_descripcion'] : ($proyecto['situacion'] == 8 ? 'Suscrito' : ($proyecto['situacion'] == 14 ? 'En Ejecución' : 'Desconocido')); ?>
                </span>
            </div>
            <h4 class="project-title"><?php echo htmlspecialchars($proyecto['nombre']); ?></h4>
            <p class="project-subtitle mb-2"><?php echo htmlspecialchars($proyecto['entidad']); ?></p>
            <div class="project-dates">
                <span class="text-sm">
                    <i class="far fa-calendar-alt me-1"></i> Inicio: <?php echo formatearFecha($proyecto['fecha_inicio']); ?>
                </span>
                <span class="date-divider">|</span>
                <span class="text-sm">
                    <i class="far fa-calendar-check me-1"></i> Fin: <?php echo formatearFecha($proyecto['fecha_termina']); ?>
                </span>
                <span class="date-divider">|</span>
                <span class="text-sm">
                    <i class="fas fa-signature me-1"></i> Suscripción: <?php echo formatearFecha($proyecto['fecha_suscripcion']); ?>
                </span>
            </div>
        </div>
        <div class="col-md-4">
            <div class="row">
                <div class="col-md-6 mb-3">
                    <div class="value-card">
                        <p class="value-title">Valor Total</p>
                        <p class="value-amount"><?php echo formatearMoneda($proyecto['valor']); ?></p>
                    </div>
                </div>
                <div class="col-md-6 mb-3">
                    <div class="value-card">
                        <p class="value-title">Aporte UD</p>
                        <p class="value-amount"><?php echo formatearMoneda($proyecto['aporte_ud']); ?></p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Información Principal del Proyecto -->
<div class="card detail-card">
    <div class="card-header no-print">
        <ul class="nav nav-tabs" id="projectTab" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="info-tab" data-bs-toggle="tab" data-bs-target="#info" type="button" role="tab" aria-controls="info" aria-selected="true">Información General</button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="financial-tab" data-bs-toggle="tab" data-bs-target="#financial" type="button" role="tab" aria-controls="financial" aria-selected="false">Información Financiera</button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="contacts-tab" data-bs-toggle="tab" data-bs-target="#contacts" type="button" role="tab" aria-controls="contacts" aria-selected="false">Contactos</button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="details-tab" data-bs-toggle="tab" data-bs-target="#details" type="button" role="tab" aria-controls="details" aria-selected="false">Detalles Adicionales</button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="actas-tab" data-bs-toggle="tab" data-bs-target="#actas" type="button" role="tab" aria-controls="actas" aria-selected="false">Actas del Proyecto</button>
            </li>
        </ul>
    </div>
    <div class="tab-content" id="projectTabContent">
        <!-- Pestaña de Información General -->
        <div class="tab-pane fade show active" id="info" role="tabpanel" aria-labelledby="info-tab">
            <div class="row">
                <div class="col-12 mb-4">
                    <div class="info-group">
                        <label class="info-label">Objeto del Proyecto</label>
                        <div class="object-text">
                            <?php echo nl2br(htmlspecialchars($proyecto['objeto'])); ?>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-4">
                    <div class="detail-section">
                        <label class="info-label">Código del Proyecto</label>
                        <p class="info-value"><?php echo $proyecto['numero_pro']; ?></p>
                    </div>
                    
                    <div class="detail-section">
                        <label class="info-label">Entidad Contratante</label>
                        <p class="info-value"><?php echo htmlspecialchars($proyecto['entidad']); ?></p>
                    </div>
                    
                    <div class="detail-section">
                        <label class="info-label">Tipo de Proyecto</label>
                        <p class="info-value">
                            <?php 
                            if (!empty($tipo_proyecto_descripcion)) {
                                echo htmlspecialchars($tipo_proyecto_descripcion);
                            } else if (isset($proyecto['tipo_proyecto_descripcion'])) {
                                echo htmlspecialchars($proyecto['tipo_proyecto_descripcion']);
                            } else {
                                echo $proyecto['tipo_proyecto'] . ' - Por definir';
                            }
                            ?>
                        </p>
                    </div>
                    
                    <div class="detail-section">
                        <label class="info-label">Modalidad</label>
                        <p class="info-value">
                            <?php 
                            if (isset($proyecto['modalidad_descripcion'])) {
                                echo htmlspecialchars($proyecto['modalidad_descripcion']);
                            } else {
                                echo $proyecto['modalidad'] . ' - Por definir';
                            }
                            ?>
                        </p>
                    </div>
                </div>
                
                <div class="col-md-4">
                    <div class="detail-section">
                        <label class="info-label">Número de Referencia</label>
                        <p class="info-value"><?php echo htmlspecialchars($proyecto['numero_pro_entidad']); ?></p>
                    </div>
                    
                    <div class="detail-section">
                        <label class="info-label">Año</label>
                        <p class="info-value"><?php echo $proyecto['anio_pro']; ?></p>
                    </div>
                    
                    <div class="detail-section">
                        <label class="info-label">Situación</label>
                        <p class="info-value">
                            <?php 
                            if (isset($proyecto['situacion_descripcion'])) {
                                echo htmlspecialchars($proyecto['situacion_descripcion']);
                            } else {
                                echo $proyecto['situacion'] . ' - Por definir';
                            }
                            ?>
                        </p>
                    </div>
                    
                    <div class="detail-section">
                        <label class="info-label">Estado</label>
                        <p class="info-value"><?php echo $proyecto['estado'] == 'A' ? 'Activo' : 'Inactivo'; ?></p>
                    </div>
                </div>
                
                <div class="col-md-4">
                    <div class="detail-section">
                        <label class="info-label">Fecha de Inicio</label>
                        <p class="info-value"><?php echo formatearFecha($proyecto['fecha_inicio']); ?></p>
                    </div>
                    
                    <div class="detail-section">
                        <label class="info-label">Fecha de Terminación</label>
                        <p class="info-value"><?php echo formatearFecha($proyecto['fecha_termina']); ?></p>
                    </div>
                    
                    <div class="detail-section">
                        <label class="info-label">Fecha de Suscripción</label>
                        <p class="info-value"><?php echo formatearFecha($proyecto['fecha_suscripcion']); ?></p>
                    </div>
                    
                    <div class="detail-section">
                        <label class="info-label">Unidad</label>
                        <p class="info-value">
                            <?php 
                            if (isset($proyecto['unidad_descripcion'])) {
                                echo htmlspecialchars($proyecto['unidad_descripcion']);
                            } else {
                                echo $proyecto['unidad'] ? $proyecto['unidad'] . ' - Por definir' : '-';
                            }
                            ?>
                        </p>
                    </div>
                </div>
                
                <!-- Observaciones (si existen) -->
                <?php if(!empty($proyecto['observaciones'])): ?>
                <div class="col-12 mt-4">
                    <div class="info-group">
                        <label class="info-label">Observaciones</label>
                        <div class="object-text">
                            <?php echo nl2br(htmlspecialchars($proyecto['observaciones'])); ?>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
                
                <!-- Beneficiarios (si existen) -->
                <?php if(!empty($proyecto['beneficiarios'])): ?>
                <div class="col-12 mt-4">
                    <div class="info-group">
                        <label class="info-label">Beneficiarios</label>
                        <p class="info-value"><?php echo number_format($proyecto['beneficiarios']); ?> personas</p>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Pestaña de Información Financiera -->
        <div class="tab-pane fade" id="financial" role="tabpanel" aria-labelledby="financial-tab">
            <div class="row">
                <div class="col-md-6">
                    <div class="detail-section">
                        <label class="info-label">Valor Total del Proyecto</label>
                        <p class="info-value"><?php echo formatearMoneda($proyecto['valor']); ?></p>
                    </div>
                    
                    <div class="detail-section">
                        <label class="info-label">Aporte Universidad Distrital</label>
                        <p class="info-value"><?php echo formatearMoneda($proyecto['aporte_ud']); ?></p>
                    </div>
                    
                    <div class="detail-section">
                        <label class="info-label">Tipo de Financiación</label>
                        <p class="info-value">
                            <?php 
                            if (isset($proyecto['financiacion_descripcion'])) {
                                echo htmlspecialchars($proyecto['financiacion_descripcion']);
                            } else {
                                echo $proyecto['financiacion'] . ' - Por definir';
                            }
                            ?>
                        </p>
                    </div>
                </div>
                
                <div class="col-md-6">
                    <div class="detail-section">
                        <label class="info-label">Código Tesoral</label>
                        <p class="info-value"><?php echo htmlspecialchars($proyecto['codigo_tesoral'] ?: '-'); ?></p>
                    </div>
                    
                    <div class="detail-section">
                        <label class="info-label">Banco</label>
                        <p class="info-value">
                            <?php
                            if (!empty($banco_nombre)) {
                                echo htmlspecialchars($banco_nombre);
                            } else {
                                echo htmlspecialchars($proyecto['codigo_bancario'] ?: '-');
                            }
                            ?>
                        </p>
                    </div>
                    
                    <div class="detail-section">
                        <label class="info-label">Tipo de Cuenta</label>
                        <p class="info-value"><?php echo htmlspecialchars($proyecto['tipo_cuenta'] ?: '-'); ?></p>
                    </div>
                    
                    <div class="detail-section">
                        <label class="info-label">Número de Cuenta</label>
                        <p class="info-value"><?php echo htmlspecialchars($proyecto['numero_cuenta'] ?: '-'); ?></p>
                    </div>
                    
                    <div class="detail-section">
                        <label class="info-label">Referencia UD</label>
                        <p class="info-value"><?php echo htmlspecialchars($proyecto['referencia_ud'] ?: '-'); ?></p>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Pestaña de Contactos -->
        <div class="tab-pane fade" id="contacts" role="tabpanel" aria-labelledby="contacts-tab">
            <div class="row">
                <div class="col-md-6">
                    <div class="detail-section">
                        <label class="info-label">Supervisor</label>
                        <p class="info-value"><?php echo htmlspecialchars($proyecto['supervisor'] ?: '-'); ?></p>
                    </div>
                </div>
                
                <div class="col-md-6">
                    <div class="detail-section">
                        <label class="info-label">Correo Principal</label>
                        <?php if(!empty($proyecto['email_main'])): ?>
                        <p class="info-value">
                            <a href="mailto:<?php echo htmlspecialchars($proyecto['email_main']); ?>" class="text-primary-light">
                                <i class="fas fa-envelope link-icon"></i><?php echo htmlspecialchars($proyecto['email_main']); ?>
                            </a>
                        </p>
                        <?php else: ?>
                        <p class="info-value">-</p>
                        <?php endif; ?>
                    </div>
                    
                    <div class="detail-section">
                        <label class="info-label">Correos Secundarios</label>
                        <?php if(!empty($proyecto['email_secundarios'])): ?>
                        <div class="info-value">
                            <?php 
                            $emails = preg_split('/[,;\s]+/', $proyecto['email_secundarios']);
                            foreach ($emails as $email) {
                                $email = trim($email);
                                if (!empty($email)) {
                                    echo '<a href="mailto:' . htmlspecialchars($email) . '" class="text-primary-light d-block mb-1">';
                                    echo '<i class="fas fa-envelope link-icon"></i>' . htmlspecialchars($email) . '</a>';
                                }
                            }
                            ?>
                        </div>
                        <?php else: ?>
                        <p class="info-value">-</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Pestaña de Detalles Adicionales -->
        <div class="tab-pane fade" id="details" role="tabpanel" aria-labelledby="details-tab">
            <div class="row">
                <div class="col-md-6">
                    <div class="detail-section">
                        <label class="info-label">Acto Administrativo</label>
                        <p class="info-value"><?php echo htmlspecialchars($proyecto['acto_administrativo'] ?: '-'); ?></p>
                    </div>
                    
                    <div class="detail-section">
                        <label class="info-label">Enlace SECOP</label>
                        <?php if(!empty($proyecto['enlace_secop'])): ?>
                        <p class="info-value">
                            <a href="<?php echo htmlspecialchars($proyecto['enlace_secop']); ?>" target="_blank" class="text-primary-light">
                                <i class="fas fa-external-link-alt link-icon"></i><?php echo htmlspecialchars($proyecto['enlace_secop']); ?>
                            </a>
                        </p>
                        <?php else: ?>
                        <p class="info-value">-</p>
                        <?php endif; ?>
                    </div>
                    
                    <div class="detail-section">
                        <label class="info-label">Datos Adicionales</label>
                        <?php if(!empty($proyecto['datos_adicionales'])): ?>
                        <p class="info-value"><?php echo nl2br(htmlspecialchars($proyecto['datos_adicionales'])); ?></p>
                        <?php else: ?>
                        <p class="info-value">-</p>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="col-md-6">
                    <div class="detail-section">
                        <label class="info-label">Usuario Creador</label>
                        <p class="info-value"><?php echo htmlspecialchars($proyecto['usuario'] ?: '-'); ?></p>
                    </div>
                    
                    <div class="detail-section">
                        <label class="info-label">Fecha de Registro</label>
                        <?php 
                        $fechaRegistro = '';
                        if (!empty($proyecto['anio']) && !empty($proyecto['mes']) && !empty($proyecto['dia'])) {
                            $fechaRegistro = sprintf('%04d-%02d-%02d', $proyecto['anio'], $proyecto['mes'], $proyecto['dia']);
                            if (!empty($proyecto['hora'])) {
                                $fechaRegistro .= sprintf(' %02d:00:00', $proyecto['hora']);
                            }
                        }
                        ?>
                        <p class="info-value"><?php echo $fechaRegistro ? formatearFecha($fechaRegistro) : '-'; ?></p>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Pestaña de Actas -->
        <div class="tab-pane fade" id="actas" role="tabpanel" aria-labelledby="actas-tab">
            <?php
            // Obtener las actas del proyecto
            $actas = obtenerActasProyecto($proyecto_id);
            
            // Ordenar las actas por número (de menor a mayor)
            usort($actas, function($a, $b) {
                return $a['numero_acta'] - $b['numero_acta'];
            });
            
            // URL base para los documentos
            $baseUrlDocumentos = "http://siexud.udistrital.edu.co/idexud/siexud/actasproy/upload/";
            ?>
            
            <?php if(empty($actas)): ?>
            <div class="text-center py-5">
                <i class="fas fa-file-alt fa-3x text-muted mb-3"></i>
                <p class="text-muted">No se encontraron actas registradas para este proyecto.</p>
                <a href="main.php?page=agregar_acta&proyecto_id=<?php echo $proyecto_id; ?>" class="btn btn-sm btn-primary mt-2 no-print">
                    <i class="fas fa-plus me-1"></i> Agregar Acta
                </a>
            </div>
            <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover table-striped">
                    <thead>
                        <tr>
                            <th>N°</th>
                            <th>Tipo</th>
                            <th>Fecha</th>
                            <th>Observaciones</th>
                            <th class="text-center no-print">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($actas as $acta): 
                            // Obtener el documento asociado al acta
                            $documentoActa = obtenerDocumentoActa($proyecto_id, $acta['numero_acta'], $acta['tipo_acta']);
                            $tieneDocumento = !empty($documentoActa) && !empty($documentoActa['archivo']);
                            $urlDocumento = $tieneDocumento ? $baseUrlDocumentos . $documentoActa['archivo'] : '';
                        ?>
                        <tr>
                            <td><?php echo $acta['numero_acta']; ?></td>
                            <td><?php echo htmlspecialchars($acta['tipo_descripcion'] ?: 'Tipo '.$acta['tipo_acta']); ?></td>
                            <td><?php echo formatearFecha($acta['fecha_acta']); ?></td>
                            <td><?php echo htmlspecialchars(substr($acta['observa'], 0, 80) . (strlen($acta['observa']) > 80 ? '...' : '')); ?></td>
                            <td class="text-center no-print">
                                <?php if($tieneDocumento): ?>
                                <button type="button" class="btn btn-sm btn-info action-button" 
                                    data-bs-toggle="modal" data-bs-target="#documentoModal" 
                                    data-doc-url="<?php echo htmlspecialchars($urlDocumento); ?>"
                                    data-doc-title="Acta #<?php echo $acta['numero_acta']; ?> - <?php echo htmlspecialchars($acta['tipo_descripcion'] ?: 'Tipo '.$acta['tipo_acta']); ?>"
                                    title="Ver Acta">
                                    <i class="fas fa-eye me-1"></i> Ver
                                </button>
                                <a href="<?php echo htmlspecialchars($urlDocumento); ?>" class="btn btn-sm btn-primary action-button" title="Descargar Acta" target="_blank">
                                    <i class="fas fa-download me-1"></i> Descargar
                                </a>
                                <?php else: ?>
                                <button disabled class="btn btn-sm btn-secondary action-button" title="Documento no disponible">
                                    <i class="fas fa-file-alt me-1"></i> No disponible
                                </button>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <div class="p-3 text-end no-print">
                <a href="main.php?page=agregar_acta&proyecto_id=<?php echo $proyecto_id; ?>" class="btn btn-sm btn-primary">
                    <i class="fas fa-plus me-1"></i> Agregar Acta
                </a>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Modal para visualizar documentos -->
<div class="modal fade" id="documentoModal" tabindex="-1" aria-labelledby="documentoModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="documentoModalLabel">Visualización de Documento</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-0">
                <div class="ratio ratio-16x9">
                    <iframe id="documentoIframe" src="" allowfullscreen></iframe>
                </div>
            </div>
            <div class="modal-footer">
                <a id="descargarDocumento" href="#" class="btn btn-primary" target="_blank">
                    <i class="fas fa-download me-1"></i> Descargar
                </a>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>

<!-- Botones de Acción -->
<div class="row mb-4 no-print">
    <div class="col-12 text-end">
        <a href="main.php?page=proyecto" class="btn btn-secondary me-2">
            <i class="fas fa-arrow-left me-1"></i> Volver a Proyectos
        </a>
        <button id="btnImprimir" class="btn btn-success me-2">
            <i class="fas fa-print me-1"></i> Imprimir
        </button>
        <a href="main.php?page=editar_proyecto&id=<?php echo $proyecto_id; ?>" class="btn btn-primary">
            <i class="fas fa-edit me-1"></i> Editar Proyecto
        </a>
    </div>
</div>

<!-- Script para activar las pestañas de Bootstrap y la funcionalidad de impresión -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Activar las pestañas de Bootstrap
    var triggerTabList = [].slice.call(document.querySelectorAll('#projectTab button'));
    triggerTabList.forEach(function(triggerEl) {
        var tabTrigger = new bootstrap.Tab(triggerEl);
        
        triggerEl.addEventListener('click', function(event) {
            event.preventDefault();
            tabTrigger.show();
        });
    });
    
    // Funcionalidad de impresión
    document.getElementById('btnImprimir').addEventListener('click', function() {
        // Preparar para imprimir
        window.print();
    });
    
    // Manejo del modal de documentos
    const documentoModal = document.getElementById('documentoModal');
    if (documentoModal) {
        documentoModal.addEventListener('show.bs.modal', function (event) {
            // Botón que activó el modal
            const button = event.relatedTarget;
            
            // Extraer información
            const docUrl = button.getAttribute('data-doc-url');
            const docTitle = button.getAttribute('data-doc-title');
            
            // Actualizar el modal
            const modalTitle = documentoModal.querySelector('.modal-title');
            const iframe = documentoModal.querySelector('#documentoIframe');
            const downloadLink = documentoModal.querySelector('#descargarDocumento');
            
            modalTitle.textContent = docTitle;
            
            // Determinar el tipo de documento y configurar el iframe en consecuencia
            const fileExtension = docUrl.split('.').pop().toLowerCase();
            
            if (['pdf', 'jpg', 'jpeg', 'png', 'gif'].includes(fileExtension)) {
                // Para PDF e imágenes, se pueden mostrar directamente
                iframe.src = docUrl;
            } else {
                // Para otros tipos de documentos, usar Google Docs Viewer
                iframe.src = 'https://docs.google.com/viewer?url=' + encodeURIComponent(docUrl) + '&embedded=true';
            }
            
            // Actualizar el enlace de descarga
            downloadLink.href = docUrl;
        });
        
        // Limpiar el iframe cuando se cierra el modal
        documentoModal.addEventListener('hidden.bs.modal', function () {
            const iframe = documentoModal.querySelector('#documentoIframe');
            iframe.src = '';
        });
    }

    // Si hay un hash en la URL (por ejemplo #actas), activar esa pestaña
    if (window.location.hash) {
        const tabName = window.location.hash.substring(1);
        const tab = document.getElementById(tabName + '-tab');
        if (tab) {
            const bsTab = new bootstrap.Tab(tab);
            bsTab.show();
        }
    }
});
</script>