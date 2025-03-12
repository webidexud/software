<?php
/**
 * Página para visualizar los detalles de un contratista
 * Esta página muestra información completa de un contratista y sus contratos asociados
 */

// Verificar que se haya proporcionado un ID
if (!isset($_GET['id']) || empty($_GET['id'])) {
    // Redirigir a la lista de proyectos si no se proporciona un ID válido
    header('Location: main.php?page=proyecto');
    exit;
}

$contratista_id = $_GET['id'];
$proyecto_id = isset($_GET['proyecto_id']) ? intval($_GET['proyecto_id']) : null;

// Incluir el modelo de proyecto si aún no está incluido
if (!function_exists('obtenerDetalleContratista')) {
    if (file_exists('models/contratista_model.php')) {
        include_once 'models/contratista_model.php';
    } else if (file_exists('models/proyecto_model.php')) {
        include_once 'models/proyecto_model.php';
    } else {
        echo '<div class="alert alert-danger">Error: No se pueden encontrar los modelos necesarios.</div>';
        exit;
    }
}

// Verificar si la función existe o crear una versión temporal
if (!function_exists('obtenerDetalleContratista')) {
    function obtenerDetalleContratista($contratista_id) {
        try {
            $conn = conectarOracle();
            $sql = "SELECT 
                        c.IDENTIFICACION,
                        c.NOMBRE1,
                        c.NOMBRE2,
                        c.APELLIDO1,
                        c.APELLIDO2,
                        c.TIPO_PERSONA,
                        c.TIPO_NACIONALIDAD,
                        c.FECHA_NACIMIENTO,
                        c.DIRECCION,
                        c.TEL_FIJO,
                        c.TEL_CELULAR,
                        c.CORREO,
                        c.GENERO,
                        c.ESTADO,
                        tp.DESCRIPCION as TIPO_PERSONA_DESC,
                        n.DESCRIPCION as NACIONALIDAD_DESC
                    FROM 
                        CONTRATOS_OPS c
                    LEFT JOIN
                        SIV_TIPO_PERSONA tp ON c.TIPO_PERSONA = tp.CODIGO
                    LEFT JOIN
                        SIV_NACIONALIDAD n ON c.TIPO_NACIONALIDAD = n.CODIGO
                    WHERE 
                        c.IDENTIFICACION = :id";
            
            $stid = oci_parse($conn, $sql);
            if (!$stid) {
                $e = oci_error($conn);
                error_log("Error al preparar consulta: " . $e['message']);
                return getContratistaPorDefecto($contratista_id);
            }
            
            oci_bind_by_name($stid, ':id', $contratista_id);
            $r = oci_execute($stid);
            if (!$r) {
                $e = oci_error($stid);
                error_log("Error al ejecutar consulta: " . $e['message']);
                oci_free_statement($stid);
                return getContratistaPorDefecto($contratista_id);
            }
            
            $row = oci_fetch_assoc($stid);
            oci_free_statement($stid);
            
            if (!$row) {
                return getContratistaPorDefecto($contratista_id);
            }
            
            $contratista = array();
            foreach ($row as $key => $value) {
                $contratista[strtolower($key)] = $value;
            }
            
            $nombres = trim($contratista['nombre1'] . ' ' . ($contratista['nombre2'] ?? ''));
            $apellidos = trim(($contratista['apellido1'] ?? '') . ' ' . ($contratista['apellido2'] ?? ''));
            $contratista['nombre_completo'] = trim($nombres . ' ' . $apellidos);
            $contratista['tipo_id_desc'] = 'Cédula de Ciudadanía';
            $contratista['tipo_nacionalidad_desc'] = $contratista['nacionalidad_desc'] ?? 'Colombiana';
            
            oci_close($conn);
            return $contratista;
            
        } catch (Exception $e) {
            error_log("Error en obtenerDetalleContratista: " . $e->getMessage());
            return getContratistaPorDefecto($contratista_id);
        }
    }
    
    function getContratistaPorDefecto($contratista_id) {
        return [
            'identificacion' => $contratista_id,
            'nombre1' => 'Contratista',
            'nombre2' => '',
            'apellido1' => $contratista_id,
            'apellido2' => '',
            'nombre_completo' => "Contratista " . $contratista_id,
            'tipo_persona' => 1,
            'tipo_persona_desc' => 'Persona Natural',
            'tipo_id_desc' => 'Cédula de Ciudadanía',
            'tipo_nacionalidad_desc' => 'Colombiana',
            'fecha_nacimiento' => null,
            'direccion' => '',
            'tel_fijo' => '',
            'tel_celular' => '',
            'correo' => '',
            'genero' => '',
            'estado' => 'A'
        ];
    }
}

if (!function_exists('obtenerActasContratista')) {
    function obtenerActasContratista($contratista_id, $proyecto_id = null) {
        try {
            $conn = conectarOracle();
            if ($proyecto_id === null) {
                $sql = "SELECT 
                            a.ANIO_PRO,
                            a.NUMERO_PRO,
                            a.NUMERO_ACTA,
                            a.TIPO_ACTA,
                            t.DESCRIPCION as TIPO_DESCRIPCION,
                            a.FECHA_ACTA,
                            a.OBSERVA,
                            a.ESTADO,
                            p.NOMBRE as NOMBRE_PROYECTO
                        FROM 
                            ACTA_PROYECTO a
                        JOIN
                            PROYECTO p ON a.NUMERO_PRO = p.NUMERO_PRO AND a.ANIO_PRO = p.ANIO_PRO
                        LEFT JOIN 
                            TIPO_ACTA t ON a.TIPO_ACTA = t.CODIGO
                        WHERE 
                            EXISTS (
                                SELECT 1 FROM PROYECTO_OPS po 
                                WHERE po.NUMERO_PRO = a.NUMERO_PRO 
                                AND po.IDENTIFICACION = :id
                            )
                        AND a.ESTADO = 'A'
                        ORDER BY 
                            a.FECHA_ACTA DESC";
                
                $stid = oci_parse($conn, $sql);
                oci_bind_by_name($stid, ':id', $contratista_id);
            } else {
                $sql = "SELECT 
                            a.ANIO_PRO,
                            a.NUMERO_PRO,
                            a.NUMERO_ACTA,
                            a.TIPO_ACTA,
                            t.DESCRIPCION as TIPO_DESCRIPCION,
                            a.FECHA_ACTA,
                            a.OBSERVA,
                            a.ESTADO,
                            p.NOMBRE as NOMBRE_PROYECTO
                        FROM 
                            ACTA_PROYECTO a
                        JOIN
                            PROYECTO p ON a.NUMERO_PRO = p.NUMERO_PRO AND a.ANIO_PRO = p.ANIO_PRO
                        LEFT JOIN 
                            TIPO_ACTA t ON a.TIPO_ACTA = t.CODIGO
                        WHERE 
                            a.NUMERO_PRO = :proyecto_id
                            AND EXISTS (
                                SELECT 1 FROM PROYECTO_OPS po 
                                WHERE po.NUMERO_PRO = a.NUMERO_PRO 
                                AND po.IDENTIFICACION = :id
                            )
                        AND a.ESTADO = 'A'
                        ORDER BY 
                            a.FECHA_ACTA DESC";
                
                $stid = oci_parse($conn, $sql);
                oci_bind_by_name($stid, ':id', $contratista_id);
                oci_bind_by_name($stid, ':proyecto_id', $proyecto_id);
            }
            
            if (!$stid) {
                $e = oci_error($conn);
                error_log("Error al preparar consulta de actas de contratista: " . $e['message']);
                return [];
            }
            
            $r = oci_execute($stid);
            if (!$r) {
                $e = oci_error($stid);
                error_log("Error al ejecutar consulta de actas de contratista: " . $e['message']);
                oci_free_statement($stid);
                oci_close($conn);
                return [];
            }
            
            $actas = [];
            while ($row = oci_fetch_assoc($stid)) {
                $acta = array();
                foreach ($row as $key => $value) {
                    $acta[strtolower($key)] = $value;
                }
                $actas[] = $acta;
            }
            
            oci_free_statement($stid);
            oci_close($conn);
            
            return $actas;
            
        } catch (Exception $e) {
            error_log("Error en obtenerActasContratista: " . $e->getMessage());
            return [];
        }
    }
}

function obtenerContratosContratista($contratista_id) {
    try {
        $conn = conectarOracle();
        $sql = "SELECT 
                    po.ANIO_PRO,
                    po.NUMERO_PRO,
                    po.IDENTIFICACION,
                    po.FECHA_INICIO,
                    po.FECHA_TERMINACION as FECHA_FIN,
                    po.VALOR,
                    po.OBJETO as ROL_CONTRATISTA,
                    po.ESTADO,
                    p.NOMBRE as NOMBRE_PROYECTO,
                    tc.DESCRIPCION as TIPO_CONTRATO_DESC,
                    e.DESCRIPCION as ENTIDAD_DESC
                FROM 
                    PROYECTO_OPS po
                JOIN
                    PROYECTO p ON po.NUMERO_PRO = p.NUMERO_PRO
                LEFT JOIN
                    TIPO_CONTRATO tc ON po.TIPO_CONTRATO = tc.CODIGO
                LEFT JOIN
                    (SELECT es.NUMERO_PRO, e.DESCRIPCION 
                     FROM ENTIDAD e
                     JOIN ENTE_SUSCRIPTOR es ON e.CODIGO = es.ENTIDAD
                     WHERE ROWNUM = 1) e ON e.NUMERO_PRO = po.NUMERO_PRO
                WHERE 
                    po.IDENTIFICACION = :id
                ORDER BY 
                    po.FECHA_INICIO DESC";
        
        $stid = oci_parse($conn, $sql);
        if (!$stid) {
            $e = oci_error($conn);
            error_log("Error al preparar consulta de contratos: " . $e['message']);
            return [];
        }
        
        oci_bind_by_name($stid, ':id', $contratista_id);
        
        $r = oci_execute($stid);
        if (!$r) {
            $e = oci_error($stid);
            error_log("Error al ejecutar consulta de contratos: " . $e['message']);
            oci_free_statement($stid);
            oci_close($conn);
            return [];
        }
        
        $contratos = [];
        while ($row = oci_fetch_assoc($stid)) {
            $contrato = array();
            foreach ($row as $key => $value) {
                $contrato[strtolower($key)] = $value;
            }
            $contrato['entidad'] = $contrato['entidad_desc'] ?? 'Entidad Contratante';
            $contratos[] = $contrato;
        }
        
        oci_free_statement($stid);
        oci_close($conn);
        
        return $contratos;
        
    } catch (Exception $e) {
        error_log("Error en obtenerContratosContratista: " . $e->getMessage());
        return [];
    }
}

// Obtener datos del contratista
$contratista = obtenerDetalleContratista($contratista_id);

// Si no se encuentra el contratista, mostrar mensaje de error
if (!$contratista) {
    echo '<div class="alert alert-danger" role="alert">El contratista solicitado no existe o no se puede acceder a él en este momento.</div>';
    exit;
}

// Obtener contratos del contratista
$contratos = obtenerContratosContratista($contratista_id);

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
?>

<!-- Estilos específicos para esta página -->
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

.card-title {
    font-size: 1.1rem;
    font-weight: 600;
    color: var(--dark);
    margin-bottom: 0;
}

.profile-header {
    background: linear-gradient(135deg, var(--primary), var(--primary-dark));
    color: white;
    padding: 25px;
    border-radius: 12px;
    margin-bottom: 25px;
    box-shadow: var(--shadow-md);
    position: relative;
    overflow: hidden;
}

.profile-header::before {
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

.header-content {
    position: relative;
    z-index: 1;
    display: flex;
    align-items: center;
    gap: 20px;
}

.profile-image {
    width: 80px;
    height: 80px;
    border-radius: 50%;
    background-color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    color: var(--primary);
    font-size: 2rem;
    border: 4px solid rgba(255, 255, 255, 0.2);
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
}

.profile-details {
    flex-grow: 1;
}

.profile-details h4 {
    font-size: 1.6rem;
    font-weight: 700;
    margin-bottom: 6px;
}

.profile-details p {
    opacity: 0.9;
    font-weight: 400;
    font-size: 0.95rem;
    margin-bottom: 0;
}

.profile-badges {
    display: flex;
    gap: 10px;
    margin-top: 10px;
}

.profile-badge {
    padding: 4px 10px;
    background-color: rgba(255, 255, 255, 0.2);
    border-radius: 20px;
    font-size: 0.75rem;
    display: inline-flex;
    align-items: center;
    gap: 5px;
}

.header-actions {
    display: flex;
    gap: 10px;
}

.info-section {
    margin-bottom: 24px;
}

.info-section-title {
    font-size: 1rem;
    font-weight: 600;
    color: var(--dark);
    margin-bottom: 1rem;
    padding-bottom: 0.5rem;
    border-bottom: 1px solid var(--light);
}

.info-row {
    display: flex;
    margin-bottom: 0.75rem;
}

.info-label {
    width: 40%;
    font-size: 0.875rem;
    color: var(--secondary);
    padding-right: 1rem;
}

.info-value {
    width: 60%;
    font-size: 0.875rem;
    color: var(--dark);
    font-weight: 500;
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

.tab-content {
    padding: 24px 20px;
}

.custom-table {
    width: 100%;
    border-collapse: separate;
    border-spacing: 0;
    border-radius: 8px;
    overflow: hidden;
    box-shadow: var(--shadow-sm);
}

.custom-table th {
    background-color: var(--neutral);
    color: var(--secondary);
    font-size: 0.75rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    padding: 12px 16px;
    border-bottom: 1px solid var(--light);
}

.custom-table td {
    padding: 12px 16px;
    vertical-align: middle;
    border-bottom: 1px solid var(--light);
    font-size: 0.875rem;
    color: var(--dark);
}

.custom-table tr:last-child td {
    border-bottom: none;
}

.custom-table tr:hover {
    background-color: rgba(94, 114, 228, 0.03);
}

.btn-action {
    padding: 6px 12px;
    font-size: 0.75rem;
    border-radius: 6px;
    font-weight: 600;
    transition: var(--transition);
    display: inline-flex;
    align-items: center;
    gap: 5px;
}

.btn-action:hover {
    transform: translateY(-2px);
    box-shadow: var(--shadow-sm);
}

.btn-primary {
    background-color: var(--primary);
    border-color: var(--primary);
    color: white;
}

.btn-primary:hover {
    background-color: var(--primary-dark);
    border-color: var(--primary-dark);
}

.btn-info {
    background-color: var(--info);
    border-color: var(--info);
    color: white;
}

.btn-info:hover {
    background-color: #0fb8de;
    border-color: #0fb8de;
}

.btn-secondary {
    background-color: var(--white);
    border-color: var(--light);
    color: var(--dark);
}

.btn-secondary:hover {
    background-color: var(--light);
    border-color: var(--secondary);
}

.status-badge {
    display: inline-flex;
    align-items: center;
    padding: 4px 10px;
    border-radius: 20px;
    font-size: 0.7rem;
    font-weight: 600;
    text-transform: uppercase;
}

.status-A {
    background-color: rgba(45, 206, 137, 0.1);
    color: var(--success);
}

.status-I {
    background-color: rgba(245, 54, 92, 0.1);
    color: var(--danger);
}

.status-F {
    background-color: rgba(136, 152, 170, 0.1);
    color: var(--secondary);
}

@keyframes fadeIn {
    from { opacity: 0; transform: translateY(10px); }
    to { opacity: 1; transform: translateY(0); }
}

.animate-fade-in {
    animation: fadeIn 0.3s ease forwards;
}

.back-button {
    display: inline-flex;
    align-items: center;
    padding: 0.5rem 1rem;
    background-color: var(--neutral);
    color: var(--dark);
    border-radius: var(--button-border-radius);
    font-weight: 500;
    font-size: 0.875rem;
    transition: all 0.2s ease;
    margin-bottom: 1rem;
    text-decoration: none;
}

.back-button:hover {
    background-color: var(--light);
    transform: translateX(-2px);
}

.back-button i {
    margin-right: 0.5rem;
}

@media (max-width: 768px) {
    .profile-header {
        padding: 20px;
    }
    
    .profile-image {
        width: 60px;
        height: 60px;
        font-size: 1.5rem;
    }
    
    .profile-details h4 {
        font-size: 1.3rem;
    }
    
    .info-row {
        flex-direction: column;
    }
    
    .info-label, .info-value {
        width: 100%;
    }
    
    .info-label {
        margin-bottom: 4px;
    }
    
    .nav-tabs .nav-link {
        padding: 10px 12px;
        font-size: 0.85rem;
    }
    
    .nav-tabs .nav-link i {
        margin-right: 5px;
    }
}
</style>

<div class="container-fluid py-4">
    <?php if ($proyecto_id): ?>
    <a href="main.php?page=proyecto_individual&id=<?php echo $proyecto_id; ?>" class="back-button">
        <i class="fas fa-arrow-left"></i> Volver al Proyecto
    </a>
    <?php else: ?>
    <a href="main.php?page=proyecto" class="back-button">
        <i class="fas fa-arrow-left"></i> Volver a Proyectos
    </a>
    <?php endif; ?>
    
    <div class="profile-header animate-fade-in">
        <div class="header-content">
            <div class="profile-image">
                <?php if ($contratista['tipo_persona'] == 1): ?>
                <i class="fas fa-user"></i>
                <?php else: ?>
                <i class="fas fa-building"></i>
                <?php endif; ?>
            </div>
            <div class="profile-details">
                <h4><?php echo htmlspecialchars($contratista['nombre_completo']); ?></h4>
                <p>
                    <i class="fas fa-id-card me-2"></i>
                    <?php echo htmlspecialchars($contratista['tipo_id_desc'] ?? 'ID'); ?>: 
                    <?php echo htmlspecialchars($contratista['identificacion']); ?>
                </p>
                
                <div class="profile-badges">
                    <div class="profile-badge">
                        <i class="fas <?php echo $contratista['tipo_persona'] == 1 ? 'fa-user' : 'fa-building'; ?>"></i>
                        <?php echo htmlspecialchars($contratista['tipo_persona_desc'] ?? ($contratista['tipo_persona'] == 1 ? 'Persona Natural' : 'Persona Jurídica')); ?>
                    </div>
                    <div class="profile-badge">
                        <i class="fas fa-globe"></i>
                        <?php echo htmlspecialchars($contratista['tipo_nacionalidad_desc'] ?? 'Nacionalidad no especificada'); ?>
                    </div>
                    <div class="profile-badge status-<?php echo $contratista['estado']; ?>">
                        <i class="fas <?php echo $contratista['estado'] == 'A' ? 'fa-check-circle' : 'fa-times-circle'; ?>"></i>
                        <?php echo $contratista['estado'] == 'A' ? 'Activo' : 'Inactivo'; ?>
                    </div>
                </div>
            </div>
            <div class="header-actions">
                <?php if ($proyecto_id): ?>
                <a href="main.php?page=editar_contratista&id=<?php echo $contratista_id; ?>&proyecto_id=<?php echo $proyecto_id; ?>" class="btn btn-primary">
                    <i class="fas fa-edit"></i> Editar
                </a>
                <a href="main.php?page=agregar_acta&proyecto_id=<?php echo $proyecto_id; ?>&contratista_id=<?php echo $contratista_id; ?>" class="btn btn-info ml-2">
                    <i class="fas fa-file-signature"></i> Agregar Acta
                </a>
                <?php else: ?>
                <a href="main.php?page=editar_contratista&id=<?php echo $contratista_id; ?>" class="btn btn-primary">
                    <i class="fas fa-edit"></i> Editar
                </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <div class="row">
        <div class="col-12">
            <div class="card animate-fade-in" style="animation-delay: 0.1s;">
                <div class="card-body p-0">
                    <ul class="nav nav-tabs" id="contratistaTabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" id="info-tab" data-bs-toggle="tab" data-bs-target="#info" type="button" role="tab" aria-controls="info" aria-selected="true">
                                <i class="fas fa-user-circle"></i> Información Personal
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="contacto-tab" data-bs-toggle="tab" data-bs-target="#contacto" type="button" role="tab" aria-controls="contacto" aria-selected="false">
                                <i class="fas fa-address-book"></i> Contacto
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="contratos-tab" data-bs-toggle="tab" data-bs-target="#contratos" type="button" role="tab" aria-controls="contratos" aria-selected="false">
                                <i class="fas fa-file-contract"></i> Contratos
                                <span class="badge bg-primary rounded-pill"><?php echo count($contratos); ?></span>
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="actas-tab" data-bs-toggle="tab" data-bs-target="#actas" type="button" role="tab" aria-controls="actas" aria-selected="false">
                                <i class="fas fa-file-signature"></i> Actas
                                <?php if(!empty($actas_contratista)): ?>
                                <span class="badge bg-primary rounded-pill"><?php echo count($actas_contratista); ?></span>
                                <?php endif; ?>
                            </button>
                        </li>
                    </ul>
                    
                    <div class="tab-content" id="contratoTabContent">
                        <div class="tab-pane fade show active" id="info" role="tabpanel" aria-labelledby="info-tab">
                            <div class="row">
                                <div class="col-lg-6">
                                    <div class="info-section">
                                        <h5 class="info-section-title">Datos Básicos</h5>
                                        <?php if ($contratista['tipo_persona'] == 1): ?>
                                        <div class="info-row">
                                            <div class="info-label">Primer Nombre</div>
                                            <div class="info-value"><?php echo htmlspecialchars($contratista['nombre1'] ?: '-'); ?></div>
                                        </div>
                                        <div class="info-row">
                                            <div class="info-label">Segundo Nombre</div>
                                            <div class="info-value"><?php echo htmlspecialchars($contratista['nombre2'] ?: '-'); ?></div>
                                        </div>
                                        <div class="info-row">
                                            <div class="info-label">Primer Apellido</div>
                                            <div class="info-value"><?php echo htmlspecialchars($contratista['apellido1'] ?: '-'); ?></div>
                                        </div>
                                        <div class="info-row">
                                            <div class="info-label">Segundo Apellido</div>
                                            <div class="info-value"><?php echo htmlspecialchars($contratista['apellido2'] ?: '-'); ?></div>
                                        </div>
                                        <?php else: ?>
                                        <div class="info-row">
                                            <div class="info-label">Razón Social</div>
                                            <div class="info-value"><?php echo htmlspecialchars($contratista['razon_social'] ?: $contratista['nombre1'] ?: '-'); ?></div>
                                        </div>
                                        <?php endif; ?>
                                        <div class="info-row">
                                            <div class="info-label">Tipo de Identificación</div>
                                            <div class="info-value"><?php echo isset($contratista['tipo_id_desc']) ? htmlspecialchars($contratista['tipo_id_desc']) : 'Cédula de Ciudadanía'; ?></div>
                                        </div>
                                        <div class="info-row">
                                            <div class="info-label">Número de Identificación</div>
                                            <div class="info-value"><?php echo htmlspecialchars($contratista['identificacion']); ?></div>
                                        </div>
                                        <div class="info-row">
                                            <div class="info-label">Tipo de Persona</div>
                                            <div class="info-value"><?php echo isset($contratista['tipo_persona_desc']) ? htmlspecialchars($contratista['tipo_persona_desc']) : 'Persona Natural'; ?></div>
                                        </div>
                                        <div class="info-row">
                                            <div class="info-label">Nacionalidad</div>
                                            <div class="info-value"><?php echo isset($contratista['tipo_nacionalidad_desc']) ? htmlspecialchars($contratista['tipo_nacionalidad_desc']) : 'Colombiana'; ?></div>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="col-lg-6">
                                    <div class="info-section">
                                        <h5 class="info-section-title">Información Adicional</h5>
                                        <?php if ($contratista['tipo_persona'] == 1): ?>
                                        <div class="info-row">
                                            <div class="info-label">Fecha de Nacimiento</div>
                                            <div class="info-value"><?php echo isset($contratista['fecha_nacimiento']) ? formatearFecha($contratista['fecha_nacimiento']) : '-'; ?></div>
                                        </div>
                                        <div class="info-row">
                                            <div class="info-label">Lugar de Nacimiento</div>
                                            <div class="info-value"><?php echo isset($contratista['lugar_nacimiento']) ? htmlspecialchars($contratista['lugar_nacimiento']) : '-'; ?></div>
                                        </div>
                                        <div class="info-row">
                                            <div class="info-label">Género</div>
                                            <div class="info-value">
                                                <?php 
                                                if (isset($contratista['genero'])) {
                                                    if ($contratista['genero'] == 'M') {
                                                        echo 'Masculino';
                                                    } elseif ($contratista['genero'] == 'F') {
                                                        echo 'Femenino';
                                                    } elseif ($contratista['genero'] == 'O') {
                                                        echo 'Otro';
                                                    } else {
                                                        echo htmlspecialchars($contratista['genero']);
                                                    }
                                                } else {
                                                    echo '-';
                                                }
                                                ?>
                                            </div>
                                        </div>
                                        <?php endif; ?>
                                        
                                        <div class="info-row">
                                            <div class="info-label">Estado</div>
                                            <div class="info-value">
                                                <span class="status-badge status-<?php echo $contratista['estado']; ?>">
                                                    <?php echo $contratista['estado'] == 'A' ? 'Activo' : 'Inactivo'; ?>
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="tab-pane fade" id="contacto" role="tabpanel" aria-labelledby="contacto-tab">
                            <div class="row">
                                <div class="col-lg-6">
                                    <div class="info-section">
                                        <h5 class="info-section-title">Datos de Contacto</h5>
                                        <div class="info-row">
                                            <div class="info-label">Dirección</div>
                                            <div class="info-value"><?php echo htmlspecialchars($contratista['direccion'] ?: '-'); ?></div>
                                        </div>
                                        <div class="info-row">
                                            <div class="info-label">Teléfono Fijo</div>
                                            <div class="info-value">
                                                <?php if (!empty($contratista['tel_fijo'])): ?>
                                                <a href="tel:<?php echo htmlspecialchars($contratista['tel_fijo']); ?>" class="text-primary">
                                                    <i class="fas fa-phone-alt me-1"></i> <?php echo htmlspecialchars($contratista['tel_fijo']); ?>
                                                </a>
                                                <?php else: ?>
                                                -
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                        <div class="info-row">
                                            <div class="info-label">Teléfono Celular</div>
                                            <div class="info-value">
                                                <?php if (!empty($contratista['tel_celular'])): ?>
                                                <a href="tel:<?php echo htmlspecialchars($contratista['tel_celular']); ?>" class="text-primary">
                                                    <i class="fas fa-mobile-alt me-1"></i> <?php echo htmlspecialchars($contratista['tel_celular']); ?>
                                                </a>
                                                <?php else: ?>
                                                -
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                        <div class="info-row">
                                            <div class="info-label">Correo Electrónico</div>
                                            <div class="info-value">
                                                <?php if (!empty($contratista['correo'])): ?>
                                                <a href="mailto:<?php echo htmlspecialchars($contratista['correo']); ?>" class="text-primary">
                                                    <i class="fas fa-envelope me-1"></i> <?php echo htmlspecialchars($contratista['correo']); ?>
                                                </a>
                                                <?php else: ?>
                                                -
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="col-lg-6">
                                    <div class="info-section">
                                        <h5 class="info-section-title">Acciones Rápidas</h5>
                                        <div class="d-flex flex-column gap-2">
                                            <?php if (!empty($contratista['tel_celular'])): ?>
                                            <a href="tel:<?php echo htmlspecialchars($contratista['tel_celular']); ?>" class="btn btn-info btn-action">
                                                <i class="fas fa-phone-alt"></i> Llamar al Celular
                                            </a>
                                            <?php endif; ?>
                                            
                                            <?php if (!empty($contratista['correo'])): ?>
                                            <a href="mailto:<?php echo htmlspecialchars($contratista['correo']); ?>" class="btn btn-primary btn-action">
                                                <i class="fas fa-envelope"></i> Enviar Correo
                                            </a>
                                            <?php endif; ?>
                                            
                                            <a href="main.php?page=editar_contratista&id=<?php echo $contratista_id; ?><?php echo $proyecto_id ? '&proyecto_id='.$proyecto_id : ''; ?>" class="btn btn-secondary btn-action">
                                                <i class="fas fa-edit"></i> Editar Información
                                            </a>
                                            
                                            <?php if ($proyecto_id): ?>
                                            <a href="main.php?page=agregar_acta&proyecto_id=<?php echo $proyecto_id; ?>&contratista_id=<?php echo $contratista_id; ?>" class="btn btn-primary btn-action">
                                                <i class="fas fa-file-signature"></i> Agregar Acta
                                            </a>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="tab-pane fade" id="contratos" role="tabpanel" aria-labelledby="contratos-tab">
                            <?php if (empty($contratos)): ?>
                            <div class="text-center py-5">
                                <i class="fas fa-file-contract text-secondary mb-3" style="font-size: 3rem;"></i>
                                <h5 class="text-secondary">No se encontraron contratos</h5>
                                <p class="text-muted">Este contratista no tiene contratos registrados en el sistema.</p>
                                <a href="main.php?page=agregar_contrato&contratista_id=<?php echo $contratista_id; ?><?php echo $proyecto_id ? '&proyecto_id='.$proyecto_id : ''; ?>" class="btn btn-primary mt-2">
                                    <i class="fas fa-plus"></i> Agregar Contrato
                                </a>
                            </div>
                            <?php else: ?>
                            <div class="d-flex justify-content-between align-items-center mb-4">
                                <h5 class="mb-0">Listado de Contratos</h5>
                                <a href="main.php?page=agregar_contrato&contratista_id=<?php echo $contratista_id; ?><?php echo $proyecto_id ? '&proyecto_id='.$proyecto_id : ''; ?>" class="btn btn-primary btn-sm">
                                    <i class="fas fa-plus"></i> Agregar Contrato
                                </a>
                            </div>
                            <div class="table-responsive">
                                <table class="custom-table">
                                    <thead>
                                        <tr>
                                            <th>Proyecto</th>
                                            <th>Tipo</th>
                                            <th>Fecha Inicio</th>
                                            <th>Fecha Fin</th>
                                            <th>Valor</th>
                                            <th>Estado</th>
                                            <th>Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($contratos as $contrato): ?>
                                        <tr>
                                            <td>
                                                <div style="max-width: 250px;">
                                                    <div class="fw-bold"><?php echo htmlspecialchars($contrato['nombre_proyecto']); ?></div>
                                                    <div class="text-muted small"><?php echo htmlspecialchars($contrato['entidad'] ?: ''); ?></div>
                                                </div>
                                            </td>
                                            <td><?php echo htmlspecialchars($contrato['tipo_contrato_desc'] ?: '-'); ?></td>
                                            <td><?php echo formatearFecha($contrato['fecha_inicio']); ?></td>
                                            <td><?php echo formatearFecha($contrato['fecha_fin']); ?></td>
                                            <td><?php echo formatearMoneda($contrato['valor']); ?></td>
                                            <td>
                                                <span class="status-badge status-<?php echo $contrato['estado']; ?>">
                                                    <?php 
                                                    if ($contrato['estado'] == 'A') {
                                                        echo 'Activo';
                                                    } elseif ($contrato['estado'] == 'F') {
                                                        echo 'Finalizado';
                                                    } elseif ($contrato['estado'] == 'I') {
                                                        echo 'Inactivo';
                                                    } else {
                                                        echo $contrato['estado'];
                                                    }
                                                    ?>
                                                </span>
                                            </td>
                                            <td>
                                                <div class="d-flex gap-2">
                                                    <a href="main.php?page=ver_contrato&id=<?php echo $contrato['numero_pro']; ?>&contratista_id=<?php echo $contratista_id; ?>" class="btn btn-info btn-sm">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                    <a href="main.php?page=editar_contrato&id=<?php echo $contrato['numero_pro']; ?>&contratista_id=<?php echo $contratista_id; ?>" class="btn btn-primary btn-sm">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    <a href="main.php?page=proyecto_individual&id=<?php echo $contrato['numero_pro']; ?>" class="btn btn-secondary btn-sm">
                                                        <i class="fas fa-project-diagram"></i>
                                                    </a>
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
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    var triggerTabList = [].slice.call(document.querySelectorAll('#contratistaTabs button'));
    triggerTabList.forEach(function(triggerEl) {
        var tabTrigger = new bootstrap.Tab(triggerEl);
        
        triggerEl.addEventListener('click', function(event) {
            event.preventDefault();
            tabTrigger.show();
            
            const tabId = this.getAttribute('data-bs-target').substring(1);
            localStorage.setItem('activeContratistaTab', tabId);
        });
    });
    
    const activeTab = localStorage.getItem('activeContratistaTab');
    if (activeTab) {
        const tab = document.querySelector(`#contratistaTabs button[data-bs-target="#${activeTab}"]`);
        if (tab) {
            const tabInstance = new bootstrap.Tab(tab);
            tabInstance.show();
        }
    }
});
</script>