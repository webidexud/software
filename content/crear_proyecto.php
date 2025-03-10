<?php
/**
 * Página para crear un nuevo proyecto
 * 
 * Este archivo maneja la interfaz y la lógica para crear un nuevo proyecto en el sistema
 * Incluye validaciones y la integración con las tablas de la base de datos
 */

// Incluir los modelos necesarios
if (!function_exists('obtenerTiposProyecto')) {
    if (file_exists('models/proyecto_model.php')) {
        include_once 'models/proyecto_model.php';
    } else {
        echo '<div class="alert alert-danger">Error: No se puede encontrar el modelo de proyectos.</div>';
        exit;
    }
}

// Incluir el modelo para IA
if (file_exists('models/ai_model.php')) {
    include_once 'models/ai_model.php';
}

// Incluir el modelo para calcular la duración del proyecto
if (!function_exists('calcularDuracionProyecto')) {
    if (file_exists('models/proyecto_individual_model.php')) {
        include_once 'models/proyecto_individual_model.php';
    }
}

// Inicializar variables para mensajes
$errores = [];
$mensaje_exito = '';

// Cargar datos maestros para los selectores
$tiposProyecto = obtenerTiposProyecto();
$modalidades = obtenerModalidades();
$financiaciones = obtenerFinanciaciones();
$situaciones = obtenerSituaciones();
$unidades = obtenerUnidades();
$entidades = obtenerEntidades();
$bancos = function_exists('obtenerBancos') ? obtenerBancos() : [];

// Inicializar array de datos extraídos
$datos_extraidos = array(
    'nombre' => '',
    'objeto' => '',
    'entidad_principal' => 0,
    'valor' => 0,
    'fecha_inicio' => '',
    'fecha_termina' => '',
    'fecha_suscripcion' => '',
    'supervisor' => '',
    'nit' => '',
    'codigo_tesoral' => '',
    'email_main' => '',
    'email_secundarios' => '',
    'acto_administrativo' => '',
    'beneficiarios' => 0
);

// Procesar documento con IA si se ha enviado
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['procesar_documento']) && isset($_FILES['documento_minuta']) && $_FILES['documento_minuta']['error'] == 0) {
    // Verificar que existe la función de procesamiento
    if (function_exists('procesarDocumentoConIA')) {
        // Mover el archivo subido a una ubicación temporal
        $archivo_temporal = $_FILES['documento_minuta']['tmp_name'];
        $nombre_archivo = $_FILES['documento_minuta']['name'];
        $extension = pathinfo($nombre_archivo, PATHINFO_EXTENSION);
        
        // Directorio para almacenar archivos temporales
        $dir_temp = 'uploads/temp/';
        if (!is_dir($dir_temp)) {
            mkdir($dir_temp, 0755, true);
        }
        
        // Generar nombre único para evitar colisiones
        $archivo_destino = $dir_temp . uniqid() . '.' . $extension;
        
        // Mover el archivo
        if (move_uploaded_file($archivo_temporal, $archivo_destino)) {
            // Procesar el documento con IA
            $datos_extraidos = procesarDocumentoConIA($archivo_destino);
            
            // Mostrar mensaje de éxito
            if (!isset($datos_extraidos['error'])) {
                $mensaje_exito = 'Documento procesado correctamente. Se han autocompletado algunos campos del formulario.';
            } else {
                $errores[] = 'Error al procesar el documento: ' . $datos_extraidos['error'];
            }
            
            // Eliminar el archivo temporal después de procesarlo
            if (file_exists($archivo_destino)) {
                unlink($archivo_destino);
            }
        } else {
            $errores[] = 'Error al subir el archivo. Inténtelo de nuevo.';
        }
    } else {
        $errores[] = 'La funcionalidad de procesamiento de documentos no está disponible.';
    }
}

// Función para generar número de proyecto automáticamente (año actual + número secuencial)
function generarNumeroProyecto() {
    // Obtener año actual
    $anio_actual = date('Y');
    
    try {
        // Obtener conexión a la base de datos
        $conn = conectarOracle();
        
        // Obtener el último número de proyecto para el año actual
        $sql = "SELECT MAX(TO_NUMBER(REGEXP_SUBSTR(NUMERO_PRO_ENTIDAD, '[0-9]+$'))) as ultimo_numero 
                FROM ENTE_SUSCRIPTOR 
                WHERE ANIO_PRO = :anio_pro";
        
        $stmt = oci_parse($conn, $sql);
        oci_bind_by_name($stmt, ':anio_pro', $anio_actual);
        oci_execute($stmt);
        
        $row = oci_fetch_assoc($stmt);
        $ultimo_numero = $row['ULTIMO_NUMERO'];
        
        // Si no hay proyectos para este año, empezar desde 1
        if (!$ultimo_numero) {
            $ultimo_numero = 0;
        }
        
        // Generar nuevo número (incrementar en 1)
        $nuevo_numero = $ultimo_numero + 1;
        
        // Formatear como PRY-AAAA-XXX (donde XXX es el número rellenado con ceros)
        $numero_proyecto = 'PRY-' . $anio_actual . '-' . str_pad($nuevo_numero, 3, '0', STR_PAD_LEFT);
        
        oci_free_statement($stmt);
        oci_close($conn);
        
        return $numero_proyecto;
    } catch (Exception $e) {
        error_log("Error al generar número de proyecto: " . $e->getMessage());
        return 'PRY-' . $anio_actual . '-XXX';
    }
}

/**
 * Inserta un registro en la tabla EJECUTOR
 * @param array $datos Datos del ejecutor
 * @return bool True si se insertó correctamente, False en caso contrario
 */
function insertarEjecutorProyecto($datos) {
    try {
        // Obtener conexión
        $conn = conectarOracle();
        
        // Preparar la consulta SQL
        $sql = "INSERT INTO EJECUTOR (
                    ANIO_PRO, NUMERO_PRO, UNIDAD, APORTE_EJECUTOR, 
                    SUPERVISOR, FECHA_INICIAL, FECHA_FINAL, ESTADO
                ) VALUES (
                    :anio_pro, :numero_pro, :unidad, :aporte_ejecutor,
                    :supervisor, TO_DATE(:fecha_inicial, 'YYYY-MM-DD'),
                    TO_DATE(:fecha_final, 'YYYY-MM-DD'), 'A'
                )";
        
        // Preparar statement
        $stmt = oci_parse($conn, $sql);
        if (!$stmt) {
            $e = oci_error($conn);
            error_log("Error al preparar inserción en EJECUTOR: " . $e['message']);
            return false;
        }
        
        // Vincular parámetros
        oci_bind_by_name($stmt, ':anio_pro', $datos['anio_pro']);
        oci_bind_by_name($stmt, ':numero_pro', $datos['numero_pro']);
        oci_bind_by_name($stmt, ':unidad', $datos['unidad']);
        oci_bind_by_name($stmt, ':aporte_ejecutor', $datos['aporte_ejecutor']);
        oci_bind_by_name($stmt, ':supervisor', $datos['supervisor']);
        oci_bind_by_name($stmt, ':fecha_inicial', $datos['fecha_inicial']);
        oci_bind_by_name($stmt, ':fecha_final', $datos['fecha_final']);
        
        // Ejecutar la consulta
        $resultado = oci_execute($stmt);
        
        // Liberar recursos
        oci_free_statement($stmt);
        oci_close($conn);
        
        return $resultado;
        
    } catch (Exception $e) {
        error_log("Error en insertarEjecutorProyecto: " . $e->getMessage());
        return false;
    }
}

// Función para crear un nuevo proyecto en la base de datos
function crearNuevoProyecto($datos) {
    try {
        // Obtener conexión
        $conn = conectarOracle();
        
        // Iniciar transacción
        oci_set_action($conn, 'crearNuevoProyecto');
        
        // 1. Obtener el siguiente valor para NUMERO_PRO
        $stidSeq = oci_parse($conn, "SELECT MAX(NUMERO_PRO) + 1 AS nuevo_numero FROM PROYECTO");
        oci_execute($stidSeq);
        $rowSeq = oci_fetch_assoc($stidSeq);
        $numero_pro = $rowSeq['NUEVO_NUMERO'];
        
        // Si es el primer proyecto, establecer como 1
        if (!$numero_pro) {
            $numero_pro = 1;
        }
        
        // Obtener el año actual para ANIO_PRO (ahora usamos el valor del campo)
        $anio_actual = $datos['anio_pro']; // Usar el año ingresado en el formulario
        $hora_actual = date('H');
        
        // Calcular la duración del proyecto (años, meses, días)
        $duracion = null;
        if (function_exists('calcularDuracionProyecto')) {
            $duracion = calcularDuracionProyecto($datos['fecha_inicio'], $datos['fecha_termina']);
        } else {
            // Si la función no está disponible, usar valores predeterminados
            $duracion = array(
                'anio' => 0,  // Para coincidir con la columna ANIO
                'mes' => 0,   // Para coincidir con la columna MES
                'dia' => 0    // Para coincidir con la columna DIA
            );
        }
        
        // 2. Insertar en la tabla PROYECTO
        $sql = "INSERT INTO PROYECTO (
                    NUMERO_PRO, ANIO_PRO, NOMBRE, OBJETO, 
                    TIPO_PROYECTO, MODALIDAD, FINANCIACION, 
                    VALOR, APORTE_UD, FECHA_INICIO, FECHA_TERMINA, 
                    FECHA_SUSCRIPCION, SITUACION, BENEFICIARIOS, 
                    OBSERVACIONES, CODIGO_TESORAL, CODIGO_BANCARIO, 
                    USUARIO, UNIDAD, TIPO_CUENTA, NUMERO_CUENTA, 
                    REFERENCIA_UD, COORDINADOR, EMAIL_MAIN, 
                    EMAIL_SECUNDARIOS, SUPERVISOR, ENLACE_SECOP, 
                    ACTO_ADMINISTRATIVO, DATOS_ADICIONALES, ESTADO,
                    ANIO, MES, DIA, HORA, FECHA_REGISTRO
                ) VALUES (
                    :numero_pro, :anio_pro, :nombre, :objeto, 
                    :tipo_proyecto, :modalidad, :financiacion, 
                    :valor, :aporte_ud, TO_DATE(:fecha_inicio, 'YYYY-MM-DD'), 
                    TO_DATE(:fecha_termina, 'YYYY-MM-DD'), TO_DATE(:fecha_suscripcion, 'YYYY-MM-DD'), 
                    :situacion, :beneficiarios, :observaciones, :codigo_tesoral, 
                    :codigo_bancario, :usuario, :unidad, :tipo_cuenta, 
                    :numero_cuenta, :referencia_ud, :coordinador, :email_main, 
                    :email_secundarios, :supervisor, :enlace_secop, 
                    :acto_administrativo, :datos_adicionales, 'A',
                    :anio, :mes, :dia, :hora, SYSDATE
                )";
        
        // Preparar y ejecutar la consulta
        $stid = oci_parse($conn, $sql);
        
        // Vincular parámetros
        oci_bind_by_name($stid, ':numero_pro', $numero_pro);
        oci_bind_by_name($stid, ':anio_pro', $anio_actual);
        oci_bind_by_name($stid, ':nombre', $datos['nombre']);
        oci_bind_by_name($stid, ':objeto', $datos['objeto']);
        oci_bind_by_name($stid, ':tipo_proyecto', $datos['tipo_proyecto']);
        oci_bind_by_name($stid, ':modalidad', $datos['modalidad']);
        oci_bind_by_name($stid, ':financiacion', $datos['financiacion']);
        oci_bind_by_name($stid, ':valor', $datos['valor']);
        oci_bind_by_name($stid, ':aporte_ud', $datos['aporte_ud']);
        oci_bind_by_name($stid, ':fecha_inicio', $datos['fecha_inicio']);
        oci_bind_by_name($stid, ':fecha_termina', $datos['fecha_termina']);
        oci_bind_by_name($stid, ':fecha_suscripcion', $datos['fecha_suscripcion']);
        oci_bind_by_name($stid, ':situacion', $datos['situacion']);
        oci_bind_by_name($stid, ':beneficiarios', $datos['beneficiarios']);
        oci_bind_by_name($stid, ':observaciones', $datos['observaciones']);
        oci_bind_by_name($stid, ':codigo_tesoral', $datos['codigo_tesoral']);
        oci_bind_by_name($stid, ':codigo_bancario', $datos['codigo_bancario']);
        oci_bind_by_name($stid, ':usuario', $datos['usuario']);
        oci_bind_by_name($stid, ':unidad', $datos['unidad']);
        oci_bind_by_name($stid, ':tipo_cuenta', $datos['tipo_cuenta']);
        oci_bind_by_name($stid, ':numero_cuenta', $datos['numero_cuenta']);
        oci_bind_by_name($stid, ':referencia_ud', $datos['referencia_ud']);
        oci_bind_by_name($stid, ':coordinador', $datos['coordinador']);
        oci_bind_by_name($stid, ':email_main', $datos['email_main']);
        oci_bind_by_name($stid, ':email_secundarios', $datos['email_secundarios']);
        oci_bind_by_name($stid, ':supervisor', $datos['supervisor']);
        oci_bind_by_name($stid, ':enlace_secop', $datos['enlace_secop']);
        oci_bind_by_name($stid, ':acto_administrativo', $datos['acto_administrativo']);
        oci_bind_by_name($stid, ':datos_adicionales', $datos['datos_adicionales']);
        oci_bind_by_name($stid, ':anio', $duracion['anio']);
        oci_bind_by_name($stid, ':mes', $duracion['mes']);
        oci_bind_by_name($stid, ':dia', $duracion['dia']);
        oci_bind_by_name($stid, ':hora', $hora_actual);
        
        // Ejecutar la consulta
        $r = oci_execute($stid, OCI_NO_AUTO_COMMIT);
        if (!$r) {
            $e = oci_error($stid);
            error_log("Error al insertar en PROYECTO: " . $e['message']);
            oci_rollback($conn);
            return false;
        }
        
        // 3. Insertar en la tabla ENTE_SUSCRIPTOR
        $sqlEnte = "INSERT INTO ENTE_SUSCRIPTOR (
                        ANIO_PRO, NUMERO_PRO, ENTIDAD, NUMERO_PRO_ENTIDAD
                    ) VALUES (
                        :anio_pro, :numero_pro, :entidad, :numero_pro_entidad
                    )";
        
        $stidEnte = oci_parse($conn, $sqlEnte);
        
        // Vincular parámetros
        oci_bind_by_name($stidEnte, ':anio_pro', $anio_actual);
        oci_bind_by_name($stidEnte, ':numero_pro', $numero_pro);
        oci_bind_by_name($stidEnte, ':entidad', $datos['entidad_principal']);
        oci_bind_by_name($stidEnte, ':numero_pro_entidad', $datos['numero_pro_entidad']);
        
        // Ejecutar la consulta
        $rEnte = oci_execute($stidEnte, OCI_NO_AUTO_COMMIT);
        if (!$rEnte) {
            $e = oci_error($stidEnte);
            error_log("Error al insertar en ENTE_SUSCRIPTOR: " . $e['message']);
            oci_rollback($conn);
            return false;
        }
        
        // Confirmar la transacción
        $commit = oci_commit($conn);
        
        // 4. Intentar insertar en la tabla EJECUTOR si hay unidad ejecutora
        if ($commit && !empty($datos['unidad']) && function_exists('insertarEjecutorProyecto')) {
            $datosEjecutor = [
                'anio_pro' => $anio_actual,
                'numero_pro' => $numero_pro,
                'unidad' => $datos['unidad'],
                'aporte_ejecutor' => $datos['aporte_entidad'],
                'supervisor' => null, // Podría ser un campo a añadir en el futuro
                'fecha_inicial' => $datos['fecha_inicio'],
                'fecha_final' => $datos['fecha_termina']
            ];
            
            // Insertar en EJECUTOR (si falla, no afecta a la transacción principal)
            $resultadoEjecutor = insertarEjecutorProyecto($datosEjecutor);
            if (!$resultadoEjecutor) {
                error_log("Advertencia: No se pudo insertar en la tabla EJECUTOR para el proyecto $numero_pro");
            }
        }
        
        // Liberar recursos
        oci_free_statement($stidSeq);
        oci_free_statement($stid);
        oci_free_statement($stidEnte);
        oci_close($conn);
        
        return $commit ? $numero_pro : false;
        
    } catch (Exception $e) {
        error_log("Error en crearNuevoProyecto: " . $e->getMessage());
        return false;
    }
}

// Procesar el formulario si se envió
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_POST['procesar_documento'])) {
    // Recoger los datos del formulario
    $anio_pro = isset($_POST['anio_pro']) ? intval($_POST['anio_pro']) : date('Y');
    $nombre = isset($_POST['nombre']) ? trim($_POST['nombre']) : '';
    $objeto = isset($_POST['objeto']) ? trim($_POST['objeto']) : '';
    $fecha_inicio = isset($_POST['fecha_inicio']) ? $_POST['fecha_inicio'] : '';
    $fecha_termina = isset($_POST['fecha_termina']) ? $_POST['fecha_termina'] : '';
    $fecha_suscripcion = isset($_POST['fecha_suscripcion']) ? $_POST['fecha_suscripcion'] : '';
    $tipo_proyecto = isset($_POST['tipo_proyecto']) ? intval($_POST['tipo_proyecto']) : null;
    $modalidad = isset($_POST['modalidad']) ? intval($_POST['modalidad']) : null;
    $financiacion = isset($_POST['financiacion']) ? intval($_POST['financiacion']) : null;
    $situacion = isset($_POST['situacion']) ? intval($_POST['situacion']) : 8; // Por defecto: Suscrito
    $unidad = isset($_POST['unidad']) ? intval($_POST['unidad']) : null;
    $valor = isset($_POST['valor']) ? floatval($_POST['valor']) : 0;
    $aporte_ud = isset($_POST['aporte_ud']) ? floatval($_POST['aporte_ud']) : 0;
    $aporte_entidad = isset($_POST['aporte_entidad']) ? floatval($_POST['aporte_entidad']) : 0;
    $observaciones = isset($_POST['observaciones']) ? trim($_POST['observaciones']) : '';
    // Eliminado el campo coordinador
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
    $beneficiarios = isset($_POST['beneficiarios']) ? floatval($_POST['beneficiarios']) : 0;
    $datos_adicionales = isset($_POST['datos_adicionales']) ? trim($_POST['datos_adicionales']) : '';
    $numero_pro_entidad = isset($_POST['numero_pro_entidad']) ? trim($_POST['numero_pro_entidad']) : generarNumeroProyecto();
    
    // Asegurarse de que los campos numéricos tienen valores válidos
    // Nota: Ya no utilizamos valores NULL para estos campos, ahora son obligatorios
    $tipo_proyecto = intval($tipo_proyecto);
    $modalidad = intval($modalidad);
    $financiacion = intval($financiacion);
    $situacion = !empty($situacion) ? $situacion : 8; // Por defecto: Suscrito
    $unidad = !empty($unidad) ? $unidad : null;
    $beneficiarios = !empty($beneficiarios) ? $beneficiarios : 0;

    // Asegurarse de que las fechas tienen formato válido
    $fecha_inicio = !empty($fecha_inicio) ? $fecha_inicio : date('Y-m-d');
    $fecha_termina = !empty($fecha_termina) ? $fecha_termina : date('Y-m-d', strtotime('+1 year'));
    $fecha_suscripcion = !empty($fecha_suscripcion) ? $fecha_suscripcion : date('Y-m-d');

    // Asegurarse de que los campos de texto tienen longitudes válidas
    $nombre = substr(trim($nombre), 0, 800);
    $objeto = substr(trim($objeto), 0, 1800);
    $observaciones = substr(trim($observaciones), 0, 4000);
    $email_main = substr(trim($email_main), 0, 80);
    $email_secundarios = substr(trim($email_secundarios), 0, 2000);
    $codigo_tesoral = substr(trim($codigo_tesoral), 0, 20);
    $codigo_bancario = substr(trim($codigo_bancario), 0, 20);
    $tipo_cuenta = substr(trim($tipo_cuenta), 0, 20);
    $numero_cuenta = substr(trim($numero_cuenta), 0, 20);
    $referencia_ud = substr(trim($referencia_ud), 0, 20);
    $supervisor = substr(trim($supervisor), 0, 120);
    $enlace_secop = substr(trim($enlace_secop), 0, 1000);
    $acto_administrativo = substr(trim($acto_administrativo), 0, 50);
    $datos_adicionales = substr(trim($datos_adicionales), 0, 1500);

    // Validar que el valor no sea negativo
    $valor = max(0, $valor);
    $aporte_ud = max(0, $aporte_ud);
    $aporte_entidad = max(0, $aporte_entidad);
    
    // Validar datos obligatorios
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
    if (empty($tipo_proyecto)) {
        $errores[] = 'Debe seleccionar un tipo de proyecto';
    }
    if (empty($modalidad)) {
        $errores[] = 'Debe seleccionar una modalidad';
    }
    if (empty($financiacion)) {
        $errores[] = 'Debe seleccionar un tipo de financiación';
    }
    
    // Si no hay errores, crear el proyecto
    if (empty($errores)) {
        // Usuario actual (en un sistema real esto vendría de la sesión)
        $usuario_actual = $_SESSION['username'] ?? 'ADMIN';
        
        // Preparar los datos para la creación
        $datosProyecto = [
            'anio_pro' => $anio_pro,
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
            'aporte_entidad' => $aporte_entidad,
            'observaciones' => $observaciones,
            'coordinador' => null, // Coordinador eliminado según solicitud
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
            'datos_adicionales' => $datos_adicionales,
            'usuario' => $usuario_actual,
            'numero_pro_entidad' => $numero_pro_entidad
        ];
        
        // Crear el proyecto
        $resultado = crearNuevoProyecto($datosProyecto);
        
        if ($resultado) {
            $mensaje_exito = 'El proyecto se ha creado correctamente.';
            
            // Redireccionar a la página del proyecto recién creado después de 2 segundos
            echo '<meta http-equiv="refresh" content="2;url=main.php?page=proyecto_individual&id=' . $resultado . '">';
        } else {
            $errores[] = 'Error al crear el proyecto en la base de datos';
        }
    }
}
?>

<div class="container-fluid py-4">
    <!-- Encabezado de la página -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <h4 class="mb-0">Crear Nuevo Proyecto</h4>
                <a href="main.php?page=proyecto" class="btn btn-sm btn-outline-secondary">
                    <i class="fas fa-arrow-left me-1"></i> Volver a Proyectos
                </a>
            </div>
            <p class="text-sm text-muted">Complete el formulario para crear un nuevo proyecto en el sistema</p>
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
    
    <!-- Formulario de creación -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">


                <div class="row mb-4">
    <div class="col-12">
        <div class="card">
            <div class="card-header p-3">
                <h6 class="mb-0">Cargar Documento</h6>
            </div>
            <div class="card-body p-3">
                <form method="POST" action="" enctype="multipart/form-data" id="formProcesarDocumento">
                    <div class="row">
                        <div class="col-md-8">
                            <div class="mb-3">
                                <label for="documento_minuta" class="form-label">Cargar Minuta o Contrato</label>
                                <input class="form-control" type="file" id="documento_minuta" name="documento_minuta" accept=".pdf,.txt">
                                <small class="form-text text-muted">Cargue el documento para autocompletar campos del formulario.</small>
                            </div>
                        </div>
                        <div class="col-md-4 d-flex align-items-end">
                            <button type="submit" name="procesar_documento" class="btn btn-info w-100">
                                <i class="fas fa-robot me-1"></i> Procesar con IA
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>



                    <form method="POST" action="" enctype="multipart/form-data">
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
                                        <label for="anio_pro" class="form-label">Año del Proyecto</label>
                                        <input type="number" class="form-control" id="anio_pro" name="anio_pro" min="2000" max="2100" value="<?php echo date('Y'); ?>">
                                        <small class="text-muted">Año del proyecto (por defecto: año actual)</small>
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label for="numero_pro_entidad" class="form-label">Número del Proyecto</label>
                                        <input type="text" class="form-control" id="numero_pro_entidad" name="numero_pro_entidad" value="<?php echo isset($datos_extraidos['numero_pro_entidad']) ? htmlspecialchars($datos_extraidos['numero_pro_entidad']) : ''; ?>">
                                        <small class="text-muted">Número o referencia del proyecto</small>
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label for="entidad_principal" class="form-label">Entidad Contratante Principal *</label>
                                        <select class="form-select <?php echo (isset($datos_extraidos['entidad_principal']) && $datos_extraidos['entidad_principal'] > 0) ? 'is-valid' : ''; ?>" id="entidad_principal" name="entidad_principal" required>
                                            <option value="">Seleccione...</option>
                                            <?php foreach ($entidades as $ent): ?>
                                            <option value="<?php echo $ent['codigo']; ?>" <?php echo (isset($datos_extraidos['entidad_principal']) && $datos_extraidos['entidad_principal'] == $ent['codigo']) ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($ent['descripcion']); ?>
                                            </option>
                                            <?php endforeach; ?>
                                        </select>
                                        <?php if (isset($datos_extraidos['entidad_nombre']) && !empty($datos_extraidos['entidad_nombre']) && empty($datos_extraidos['entidad_principal'])): ?>
                                        <small class="text-warning">Se detectó posible entidad: "<?php echo htmlspecialchars($datos_extraidos['entidad_nombre']); ?>" pero no pudo asociarse automáticamente. Por favor seleccione manualmente.</small>
                                        <?php else: ?>
                                        <small class="text-muted">Entidad que contrata el proyecto</small>
                                        <?php endif; ?>
                                    </div>

                                </div>
                                
                                <div class="row">
                                    <div class="col-md-12 mb-3">
                                        <label for="nombre" class="form-label">Nombre del Proyecto *</label>
                                        <input type="text" class="form-control" id="nombre" name="nombre" required maxlength="800" value="<?php echo isset($datos_extraidos['nombre']) ? htmlspecialchars($datos_extraidos['nombre']) : ''; ?>">
                                        <small class="text-muted">Nombre completo y descriptivo del proyecto (máximo 800 caracteres)</small>
                                    </div>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-12 mb-3">
                                        <label for="objeto" class="form-label">Objeto del Proyecto *</label>
                                        <textarea class="form-control" id="objeto" name="objeto" rows="5" required maxlength="1800"><?php echo isset($datos_extraidos['objeto']) ? htmlspecialchars($datos_extraidos['objeto']) : ''; ?></textarea>
                                        <small class="text-muted">Descripción detallada del objetivo del proyecto (máximo 1800 caracteres)</small>
                                    </div>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-4 mb-3">
                                        <label for="fecha_inicio" class="form-label">Fecha de Inicio *</label>
                                        <input type="date" class="form-control" id="fecha_inicio" name="fecha_inicio" required value="<?php echo isset($datos_extraidos['fecha_inicio']) ? $datos_extraidos['fecha_inicio'] : ''; ?>">
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label for="fecha_termina" class="form-label">Fecha de Terminación *</label>
                                        <input type="date" class="form-control" id="fecha_termina" name="fecha_termina" required value="<?php echo isset($datos_extraidos['fecha_termina']) ? $datos_extraidos['fecha_termina'] : ''; ?>">
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label for="fecha_suscripcion" class="form-label">Fecha de Suscripción</label>
                                        <input type="date" class="form-control" id="fecha_suscripcion" name="fecha_suscripcion" value="<?php echo isset($datos_extraidos['fecha_suscripcion']) ? $datos_extraidos['fecha_suscripcion'] : date('Y-m-d'); ?>">
                                    </div>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-4 mb-3">
                                        <label for="tipo_proyecto" class="form-label">Tipo de Proyecto *</label>
                                        <select class="form-select" id="tipo_proyecto" name="tipo_proyecto" required>
                                            <option value="">Seleccione...</option>
                                            <?php foreach ($tiposProyecto as $tipo): ?>
                                            <option value="<?php echo $tipo['codigo']; ?>" <?php echo (isset($datos_extraidos['tipo_proyecto']) && $datos_extraidos['tipo_proyecto'] == $tipo['codigo']) ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($tipo['descripcion']); ?>
                                            </option>
                                            <?php endforeach; ?>
                                        </select>
                                        <small class="text-muted">Este campo es obligatorio</small>
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label for="modalidad" class="form-label">Modalidad *</label>
                                        <select class="form-select" id="modalidad" name="modalidad" required>
                                            <option value="">Seleccione...</option>
                                            <?php foreach ($modalidades as $mod): ?>
                                            <option value="<?php echo $mod['codigo']; ?>" <?php echo (isset($datos_extraidos['modalidad']) && $datos_extraidos['modalidad'] == $mod['codigo']) ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($mod['descripcion']); ?>
                                            </option>
                                            <?php endforeach; ?>
                                        </select>
                                        <small class="text-muted">Este campo es obligatorio</small>
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label for="situacion" class="form-label">Situación</label>
                                        <select class="form-select" id="situacion" name="situacion">
                                            <option value="">Seleccione...</option>
                                            <?php foreach ($situaciones as $sit): ?>
                                            <option value="<?php echo $sit['codigo']; ?>" <?php echo $sit['codigo'] == 8 ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($sit['descripcion']); ?>
                                            </option>
                                            <?php endforeach; ?>
                                        </select>
                                        <small class="text-muted">Por defecto: Suscrito</small>
                                    </div>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-12 mb-3">
                                        <label for="unidad" class="form-label">Unidad Ejecutora</label>
                                        <select class="form-select" id="unidad" name="unidad">
                                            <option value="">Seleccione...</option>
                                            <?php foreach ($unidades as $uni): ?>
                                            <option value="<?php echo $uni['codigo']; ?>" <?php echo (isset($datos_extraidos['unidad']) && $datos_extraidos['unidad'] == $uni['codigo']) ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($uni['descripcion']); ?>
                                            </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-12 mb-3">
                                        <label for="observaciones" class="form-label">Observaciones</label>
                                        <textarea class="form-control" id="observaciones" name="observaciones" rows="3" maxlength="4000"><?php echo isset($datos_extraidos['observaciones']) ? htmlspecialchars($datos_extraidos['observaciones']) : ''; ?></textarea>
                                        <small class="text-muted">Observaciones generales del proyecto (máximo 4000 caracteres)</small>
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
                                            <input type="text" class="form-control" id="valor" name="valor" required value="<?php echo isset($datos_extraidos['valor']) ? number_format($datos_extraidos['valor'], 0, '.', ',') : ''; ?>">
                                        </div>
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label for="aporte_ud" class="form-label">Aporte UD</label>
                                        <div class="input-group">
                                            <span class="input-group-text">$</span>
                                            <input type="text" class="form-control" id="aporte_ud" name="aporte_ud" value="<?php echo isset($datos_extraidos['aporte_ud']) ? number_format($datos_extraidos['aporte_ud'], 0, '.', ',') : '0'; ?>">
                                        </div>
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label for="aporte_entidad" class="form-label">Aporte Entidad</label>
                                        <div class="input-group">
                                            <span class="input-group-text">$</span>
                                            <input type="text" class="form-control" id="aporte_entidad" name="aporte_entidad" value="<?php echo isset($datos_extraidos['aporte_entidad']) ? number_format($datos_extraidos['aporte_entidad'], 0, '.', ',') : '0'; ?>">
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-4 mb-3">
                                        <label for="financiacion" class="form-label">Tipo de Financiación *</label>
                                        <select class="form-select" id="financiacion" name="financiacion" required>
                                            <option value="">Seleccione...</option>
                                            <?php foreach ($financiaciones as $fin): ?>
                                            <option value="<?php echo $fin['codigo']; ?>" <?php echo (isset($datos_extraidos['financiacion']) && $datos_extraidos['financiacion'] == $fin['codigo']) ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($fin['descripcion']); ?>
                                            </option>
                                            <?php endforeach; ?>
                                        </select>
                                        <small class="text-muted">Este campo es obligatorio</small>
                                    </div>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="codigo_tesoral" class="form-label">Código Tesoral</label>
                                        <input type="text" class="form-control" id="codigo_tesoral" name="codigo_tesoral" maxlength="20" value="<?php echo isset($datos_extraidos['codigo_tesoral']) ? htmlspecialchars($datos_extraidos['codigo_tesoral']) : ''; ?>">
                                        <small class="text-muted">Máximo 20 caracteres</small>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="codigo_bancario" class="form-label">Banco</label>
                                        <select class="form-select" id="codigo_bancario" name="codigo_bancario">
                                            <option value="">Seleccione...</option>
                                            <?php foreach ($bancos as $banco): ?>
                                            <option value="<?php echo $banco['codigo']; ?>" <?php echo (isset($datos_extraidos['codigo_bancario']) && $datos_extraidos['codigo_bancario'] == $banco['codigo']) ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($banco['descripcion']); ?>
                                            </option>
                                            <?php endforeach; ?>
                                        </select>
                                        <small class="text-muted">Banco donde se manejarán los recursos</small>
                                    </div>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="tipo_cuenta" class="form-label">Tipo de Cuenta</label>
                                        <select class="form-select" id="tipo_cuenta" name="tipo_cuenta">
                                            <option value="">Seleccione...</option>
                                            <option value="AHORROS" <?php echo (isset($datos_extraidos['tipo_cuenta']) && $datos_extraidos['tipo_cuenta'] == 'AHORROS') ? 'selected' : ''; ?>>Ahorros</option>
                                            <option value="CORRIENTE" <?php echo (isset($datos_extraidos['tipo_cuenta']) && $datos_extraidos['tipo_cuenta'] == 'CORRIENTE') ? 'selected' : ''; ?>>Corriente</option>
                                        </select>
                                        <small class="text-muted">Tipo de cuenta bancaria</small>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="numero_cuenta" class="form-label">Número de Cuenta</label>
                                        <input type="text" class="form-control" id="numero_cuenta" name="numero_cuenta" maxlength="20" value="<?php echo isset($datos_extraidos['numero_cuenta']) ? htmlspecialchars($datos_extraidos['numero_cuenta']) : ''; ?>">
                                        <small class="text-muted">Máximo 20 caracteres</small>
                                    </div>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-12 mb-3">
                                        <label for="referencia_ud" class="form-label">Referencia UD</label>
                                        <input type="text" class="form-control" id="referencia_ud" name="referencia_ud" maxlength="20" value="<?php echo isset($datos_extraidos['referencia_ud']) ? htmlspecialchars($datos_extraidos['referencia_ud']) : ''; ?>">
                                        <small class="text-muted">Máximo 20 caracteres</small>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Pestaña de Contactos -->
                            <div class="tab-pane fade" id="contacto" role="tabpanel" aria-labelledby="contacto-tab">
                                <div class="row">
                                    <div class="col-md-12 mb-3">
                                        <label for="supervisor" class="form-label">Supervisor</label>
                                        <input type="text" class="form-control" id="supervisor" name="supervisor" maxlength="120" value="<?php echo isset($datos_extraidos['supervisor']) ? htmlspecialchars($datos_extraidos['supervisor']) : ''; ?>">
                                        <small class="text-muted">Nombre del supervisor del proyecto (máximo 120 caracteres)</small>
                                    </div>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="email_main" class="form-label">Correo Principal</label>
                                        <input type="email" class="form-control" id="email_main" name="email_main" maxlength="80" value="<?php echo isset($datos_extraidos['email_main']) ? htmlspecialchars($datos_extraidos['email_main']) : ''; ?>">
                                        <small class="text-muted">Correo electrónico principal (máximo 80 caracteres)</small>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="email_secundarios" class="form-label">Correos Secundarios</label>
                                        <input type="text" class="form-control" id="email_secundarios" name="email_secundarios" 
                                               placeholder="Separados por coma o punto y coma" maxlength="2000" 
                                               value="<?php echo isset($datos_extraidos['email_secundarios']) ? htmlspecialchars($datos_extraidos['email_secundarios']) : ''; ?>">
                                        <small class="text-muted">Correos adicionales de contacto (máximo 2000 caracteres)</small>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Pestaña de Información Adicional -->
                            <div class="tab-pane fade" id="adicional" role="tabpanel" aria-labelledby="adicional-tab">
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="enlace_secop" class="form-label">Enlace SECOP</label>
                                        <input type="url" class="form-control" id="enlace_secop" name="enlace_secop" maxlength="1000" value="<?php echo isset($datos_extraidos['enlace_secop']) ? htmlspecialchars($datos_extraidos['enlace_secop']) : ''; ?>">
                                        <small class="text-muted">Enlace a la información del SECOP (máximo 1000 caracteres)</small>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="acto_administrativo" class="form-label">Acto Administrativo</label>
                                        <input type="text" class="form-control" id="acto_administrativo" name="acto_administrativo" maxlength="50" value="<?php echo isset($datos_extraidos['acto_administrativo']) ? htmlspecialchars($datos_extraidos['acto_administrativo']) : ''; ?>">
                                        <small class="text-muted">Número o referencia del acto administrativo (máximo 50 caracteres)</small>
                                    </div>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="beneficiarios" class="form-label">Beneficiarios</label>
                                        <input type="text" class="form-control" id="beneficiarios" name="beneficiarios" value="<?php echo isset($datos_extraidos['beneficiarios']) ? number_format($datos_extraidos['beneficiarios'], 0, '.', ',') : '0'; ?>">
                                        <small class="text-muted">Número de personas beneficiadas por el proyecto</small>
                                    </div>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-12 mb-3">
                                        <label for="datos_adicionales" class="form-label">Datos Adicionales</label>
                                        <textarea class="form-control" id="datos_adicionales" name="datos_adicionales" rows="3" maxlength="1500"><?php echo isset($datos_extraidos['datos_adicionales']) ? htmlspecialchars($datos_extraidos['datos_adicionales']) : ''; ?></textarea>
                                        <small class="text-muted">Información adicional relevante para el proyecto (máximo 1500 caracteres)</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row mt-4">
                            <div class="col-12">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save me-1"></i> Crear Proyecto
                                </button>
                                <a href="main.php?page=proyecto" class="btn btn-secondary">
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

<!-- Script para activar las pestañas -->
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
    
    // Si hay datos extraídos por IA, mostrar un indicador visual
    <?php if (!empty($mensaje_exito) && strpos($mensaje_exito, 'Documento procesado') !== false): ?>
    // Resaltar campos autocompletados
    const camposExtraidos = document.querySelectorAll('input[value]:not([value=""]), textarea:not(:empty), select option[selected]:not([value=""])');
    camposExtraidos.forEach(function(campo) {
        if (campo.tagName === 'OPTION') {
            campo.parentElement.classList.add('border-success');
            setTimeout(function() {
                campo.parentElement.classList.remove('border-success');
            }, 5000);
        } else {
            campo.classList.add('border-success');
            setTimeout(function() {
                campo.classList.remove('border-success');
            }, 5000);
        }
    });
    <?php endif; ?>
});
</script>

<!-- Script para formatear campos numéricos -->
<script>
// Función para formatear números con comas
function formatNumber(input) {
    // Eliminar comas existentes
    let value = input.value.replace(/,/g, '');
    
    // Verificar si es un número válido
    if (value !== '' && !isNaN(value)) {
        // Formatear con comas
        input.value = parseFloat(value).toLocaleString('en-US', {
            maximumFractionDigits: 2,
            minimumFractionDigits: 0
        });
    }
}

// Función para obtener el valor sin formato para enviar al servidor
function getNumericValue(input) {
    return input.value.replace(/,/g, '');
}

// Aplicar el formato a los campos numéricos cuando se carga la página
document.addEventListener('DOMContentLoaded', function() {
    // Agregar listeners a los campos de valor
    const valorInput = document.getElementById('valor');
    const aporteUdInput = document.getElementById('aporte_ud');
    const aporteEntidadInput = document.getElementById('aporte_entidad');
    const beneficiariosInput = document.getElementById('beneficiarios');
    
    // Aplicar formateo a los campos de valor al perder el foco
    if (valorInput) {
        valorInput.addEventListener('blur', function() {
            formatNumber(this);
        });
    }
    
    if (aporteUdInput) {
        aporteUdInput.addEventListener('blur', function() {
            formatNumber(this);
        });
    }
    
    if (aporteEntidadInput) {
        aporteEntidadInput.addEventListener('blur', function() {
            formatNumber(this);
        });
    }
    
    if (beneficiariosInput) {
        beneficiariosInput.addEventListener('blur', function() {
            formatNumber(this);
        });
    }
    
    // Formatear los campos numéricos al cargar la página
    [valorInput, aporteUdInput, aporteEntidadInput, beneficiariosInput].forEach(function(input) {
        if (input && input.value) {
            formatNumber(input);
        }
    });
    
    // Modificar el formulario para enviar valores sin formato
    const form = document.querySelector('form');
    if (form) {
        form.addEventListener('submit', function(e) {
            // Verificar si ya estamos procesando un documento (evitar quitar el formateo en ese caso)
            if (e.submitter && e.submitter.getAttribute('name') === 'procesar_documento') {
                return true;
            }
            
            // Evitar envío del formulario
            e.preventDefault();
            
            // Obtener valores sin formato
            if (valorInput) {
                valorInput.value = getNumericValue(valorInput);
            }
            
            if (aporteUdInput) {
                aporteUdInput.value = getNumericValue(aporteUdInput);
            }
            
            if (aporteEntidadInput) {
                aporteEntidadInput.value = getNumericValue(aporteEntidadInput);
            }
            
            if (beneficiariosInput) {
                beneficiariosInput.value = getNumericValue(beneficiariosInput);
            }
            
            // Enviar el formulario
            this.submit();
        });
    }
});
</script>
