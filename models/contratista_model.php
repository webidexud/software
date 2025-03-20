<?php
// Incluir el archivo de conexión
require_once 'config/database.php';

/**
 * Obtiene todos los contratistas registrados en el sistema
 * @return array Lista de contratistas
 */
function obtenerTodosContratistas() {
    try {
        // Obtener conexión
        $conn = conectarOracle();
        
        // Consulta SQL
        $sql = "SELECT 
                    c.IDENTIFICACION as identificacion,
                    c.NOMBRE1 as nombre1, 
                    c.NOMBRE2 as nombre2,
                    c.APELLIDO1 as apellido1,
                    c.APELLIDO2 as apellido2,
                    c.TIPO_PERSONA as tipo_persona,
                    tp.DESCRIPCION as tipo_persona_desc,
                    c.TIPO_NACIONALIDAD as tipo_nacionalidad,
                    tn.DESCRIPCION as nacionalidad_desc,
                    c.FECHA_NACIMIENTO as fecha_nacimiento,
                    c.DIRECCION as direccion,
                    c.TEL_FIJO as tel_fijo,
                    c.TEL_CELULAR as tel_celular,
                    c.CORREO as correo,
                    c.GENERO as genero,
                    c.ESTADO as estado
                FROM 
                    CONTRATOS_OPS c
                LEFT JOIN 
                    SIV_TIPO_PERSONA tp ON c.TIPO_PERSONA = tp.CODIGO
                LEFT JOIN 
                    SIV_NACIONALIDAD tn ON c.TIPO_NACIONALIDAD = tn.CODIGO
                WHERE 
                    c.ESTADO = 'A'
                ORDER BY 
                    c.APELLIDO1, c.APELLIDO2, c.NOMBRE1, c.NOMBRE2";
        
        // Preparar consulta
        $stid = oci_parse($conn, $sql);
        if (!$stid) {
            $e = oci_error($conn);
            error_log("Error al preparar consulta: " . $e['message']);
            return [];
        }
        
        // Ejecutar consulta
        $r = oci_execute($stid);
        if (!$r) {
            $e = oci_error($stid);
            error_log("Error al ejecutar consulta: " . $e['message']);
            return [];
        }
        
        // Procesar resultados
        $contratistas = [];
        
        while ($row = oci_fetch_assoc($stid)) {
            // Convertir claves a minúsculas
            $contratista = array();
            foreach ($row as $key => $value) {
                $contratista[strtolower($key)] = $value;
            }
            
            // Formatear nombre completo
            if ($contratista['tipo_persona'] == 1) { // Persona Natural
                $nombres = trim($contratista['nombre1'] . ' ' . ($contratista['nombre2'] ?? ''));
                $apellidos = trim(($contratista['apellido1'] ?? '') . ' ' . ($contratista['apellido2'] ?? ''));
                $contratista['nombre_completo'] = trim($nombres . ' ' . $apellidos);
            } else { // Persona Jurídica u otro tipo
                $contratista['nombre_completo'] = $contratista['nombre1']; // Asumiendo que es la razón social
            }
            
            $contratistas[] = $contratista;
        }
        
        // Liberar recursos
        oci_free_statement($stid);
        oci_close($conn);
        
        return $contratistas;
        
    } catch (Exception $e) {
        error_log("Error en obtenerTodosContratistas: " . $e->getMessage());
        return [];
    }
}

/**
 * Obtiene un contratista por su identificación
 * @param int $identificacion Número de identificación del contratista
 * @return array|null Datos del contratista o null si no existe
 */
function obtenerContratistaPorId($identificacion) {
    try {
        // Obtener conexión
        $conn = conectarOracle();
        
        // Consulta SQL
        $sql = "SELECT 
                    c.IDENTIFICACION as identificacion,
                    c.NOMBRE1 as nombre1, 
                    c.NOMBRE2 as nombre2,
                    c.APELLIDO1 as apellido1,
                    c.APELLIDO2 as apellido2,
                    c.TIPO_PERSONA as tipo_persona,
                    c.TIPO_NACIONALIDAD as tipo_nacionalidad,
                    c.FECHA_NACIMIENTO as fecha_nacimiento,
                    c.DIRECCION as direccion,
                    c.TEL_FIJO as tel_fijo,
                    c.TEL_CELULAR as tel_celular,
                    c.CORREO as correo,
                    c.GENERO as genero,
                    c.ESTADO as estado
                FROM 
                    CONTRATOS_OPS c
                WHERE 
                    c.IDENTIFICACION = :identificacion";
        
        // Preparar consulta
        $stid = oci_parse($conn, $sql);
        if (!$stid) {
            $e = oci_error($conn);
            error_log("Error al preparar consulta: " . $e['message']);
            return null;
        }
        
        // Vincular parámetros
        oci_bind_by_name($stid, ':identificacion', $identificacion);
        
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
            $contratista = array();
            foreach ($row as $key => $value) {
                $contratista[strtolower($key)] = $value;
            }
        } else {
            $contratista = null;
        }
        
        // Liberar recursos
        oci_free_statement($stid);
        oci_close($conn);
        
        return $contratista;
        
    } catch (Exception $e) {
        error_log("Error en obtenerContratistaPorId: " . $e->getMessage());
        return null;
    }
}

/**
 * Obtiene los contratos asociados a un contratista
 * @param int $identificacion Número de identificación del contratista
 * @return array Lista de contratos del contratista
 */
function obtenerContratosContratista($identificacion) {
    try {
        // Obtener conexión
        $conn = conectarOracle();
        
        // Consulta SQL
        $sql = "SELECT 
                    po.ANIO_PRO as anio_pro,
                    po.NUMERO_PRO as numero_pro,
                    po.IDENTIFICACION as identificacion,
                    po.NUMERO_CONTRATO as numero_contrato,
                    po.VIGENCIA_FISCAL as vigencia_fiscal,
                    po.TIPO_REGISTRO as tipo_registro,
                    po.TIPO_MODIFICACION as tipo_modificacion,
                    po.TIPO_CONFIGURACION as tipo_configuracion,
                    po.TIPOLOGIA as tipologia,
                    po.TIPO_PROCESO as tipo_proceso,
                    po.FECHA_PUBLICACION as fecha_publicacion,
                    po.TIPO_CONTRATO as tipo_contrato,
                    po.TIPO_VINCULACION as tipo_vinculacion,
                    po.FACULTAD as facultad,
                    po.PROYECTO_CURRICULAR as proyecto_curricular,
                    po.OBJETO as objeto,
                    po.FECHA_SUSCRIPCION as fecha_suscripcion,
                    po.FECHA_INICIO as fecha_inicio,
                    po.FECHA_TERMINACION as fecha_terminacion,
                    po.VALOR as valor,
                    po.TIPO_MONEDA as tipo_moneda,
                    po.TIPO_TASA as tipo_tasa,
                    po.ANIO as anio,
                    po.MES as mes,
                    po.DIA as dia,
                    po.HORA as hora,
                    po.SITUACION_CONTRATO as situacion_contrato,
                    po.OBSERVACIONES as observaciones,
                    po.UNIDAD as unidad,
                    p.NOMBRE as nombre_proyecto
                FROM 
                    PROYECTO_OPS po
                JOIN
                    PROYECTO p ON po.ANIO_PRO = p.ANIO_PRO AND po.NUMERO_PRO = p.NUMERO_PRO
                WHERE 
                    po.IDENTIFICACION = :identificacion
                ORDER BY 
                    po.FECHA_INICIO DESC";
        
        // Preparar consulta
        $stid = oci_parse($conn, $sql);
        if (!$stid) {
            $e = oci_error($conn);
            error_log("Error al preparar consulta: " . $e['message']);
            return [];
        }
        
        // Vincular parámetros
        oci_bind_by_name($stid, ':identificacion', $identificacion);
        
        // Ejecutar consulta
        $r = oci_execute($stid);
        if (!$r) {
            $e = oci_error($stid);
            error_log("Error al ejecutar consulta: " . $e['message']);
            return [];
        }
        
        // Procesar resultados
        $contratos = [];
        
        while ($row = oci_fetch_assoc($stid)) {
            // Convertir claves a minúsculas
            $contrato = array();
            foreach ($row as $key => $value) {
                $contrato[strtolower($key)] = $value;
            }
            
            $contratos[] = $contrato;
        }
        
        // Liberar recursos
        oci_free_statement($stid);
        oci_close($conn);
        
        return $contratos;
        
    } catch (Exception $e) {
        error_log("Error en obtenerContratosContratista: " . $e->getMessage());
        return [];
    }
}

/**
 * Crea un nuevo contratista en el sistema
 * @param array $datos Datos del contratista
 * @return bool|string True si se creó correctamente, mensaje de error en caso contrario
 */
function crearContratista($datos) {
    try {
        // Obtener conexión
        $conn = conectarOracle();
        
        // Verificar si el contratista ya existe
        $sql_check = "SELECT COUNT(*) as existe FROM CONTRATOS_OPS WHERE IDENTIFICACION = :identificacion";
        $stmt_check = oci_parse($conn, $sql_check);
        oci_bind_by_name($stmt_check, ':identificacion', $datos['identificacion']);
        oci_execute($stmt_check);
        $row = oci_fetch_assoc($stmt_check);
        
        if ($row['EXISTE'] > 0) {
            return "El contratista con identificación " . $datos['identificacion'] . " ya existe en el sistema.";
        }
        
        // Consulta SQL para insertar
        $sql = "INSERT INTO CONTRATOS_OPS (
                    IDENTIFICACION, NOMBRE1, NOMBRE2, APELLIDO1, APELLIDO2, 
                    TIPO_PERSONA, TIPO_NACIONALIDAD, FECHA_NACIMIENTO, 
                    DIRECCION, TEL_FIJO, TEL_CELULAR, CORREO, GENERO, ESTADO
                ) VALUES (
                    :identificacion, :nombre1, :nombre2, :apellido1, :apellido2, 
                    :tipo_persona, :tipo_nacionalidad, TO_DATE(:fecha_nacimiento, 'YYYY-MM-DD'), 
                    :direccion, :tel_fijo, :tel_celular, :correo, :genero, 'A'
                )";
        
        // Preparar consulta
        $stmt = oci_parse($conn, $sql);
        if (!$stmt) {
            $e = oci_error($conn);
            error_log("Error al preparar consulta: " . $e['message']);
            return "Error al crear contratista: " . $e['message'];
        }
        
        // Vincular parámetros
        oci_bind_by_name($stmt, ':identificacion', $datos['identificacion']);
        oci_bind_by_name($stmt, ':nombre1', $datos['nombre1']);
        oci_bind_by_name($stmt, ':nombre2', $datos['nombre2']);
        oci_bind_by_name($stmt, ':apellido1', $datos['apellido1']);
        oci_bind_by_name($stmt, ':apellido2', $datos['apellido2']);
        oci_bind_by_name($stmt, ':tipo_persona', $datos['tipo_persona']);
        oci_bind_by_name($stmt, ':tipo_nacionalidad', $datos['tipo_nacionalidad']);
        oci_bind_by_name($stmt, ':fecha_nacimiento', $datos['fecha_nacimiento']);
        oci_bind_by_name($stmt, ':direccion', $datos['direccion']);
        oci_bind_by_name($stmt, ':tel_fijo', $datos['tel_fijo']);
        oci_bind_by_name($stmt, ':tel_celular', $datos['tel_celular']);
        oci_bind_by_name($stmt, ':correo', $datos['correo']);
        oci_bind_by_name($stmt, ':genero', $datos['genero']);
        
        // Ejecutar consulta
        $r = oci_execute($stmt);
        if (!$r) {
            $e = oci_error($stmt);
            error_log("Error al ejecutar consulta: " . $e['message']);
            return "Error al crear contratista: " . $e['message'];
        }
        
        // Liberar recursos
        oci_free_statement($stmt);
        oci_close($conn);
        
        return true;
        
    } catch (Exception $e) {
        error_log("Error en crearContratista: " . $e->getMessage());
        return "Error general al crear contratista: " . $e->getMessage();
    }
}

/**
 * Actualiza los datos de un contratista existente
 * @param array $datos Datos del contratista
 * @return bool|string True si se actualizó correctamente, mensaje de error en caso contrario
 */
function actualizarContratista($datos) {
    try {
        // Obtener conexión
        $conn = conectarOracle();
        
        // Verificar si el contratista existe
        $sql_check = "SELECT COUNT(*) as existe FROM CONTRATOS_OPS WHERE IDENTIFICACION = :identificacion";
        $stmt_check = oci_parse($conn, $sql_check);
        oci_bind_by_name($stmt_check, ':identificacion', $datos['identificacion']);
        oci_execute($stmt_check);
        $row = oci_fetch_assoc($stmt_check);
        
        if ($row['EXISTE'] == 0) {
            return "El contratista con identificación " . $datos['identificacion'] . " no existe en el sistema.";
        }
        
        // Consulta SQL para actualizar
        $sql = "UPDATE CONTRATOS_OPS SET 
                    NOMBRE1 = :nombre1, 
                    NOMBRE2 = :nombre2, 
                    APELLIDO1 = :apellido1, 
                    APELLIDO2 = :apellido2, 
                    TIPO_PERSONA = :tipo_persona, 
                    TIPO_NACIONALIDAD = :tipo_nacionalidad, 
                    FECHA_NACIMIENTO = TO_DATE(:fecha_nacimiento, 'YYYY-MM-DD'), 
                    DIRECCION = :direccion, 
                    TEL_FIJO = :tel_fijo, 
                    TEL_CELULAR = :tel_celular, 
                    CORREO = :correo, 
                    GENERO = :genero
                WHERE IDENTIFICACION = :identificacion";
        
        // Preparar consulta
        $stmt = oci_parse($conn, $sql);
        if (!$stmt) {
            $e = oci_error($conn);
            error_log("Error al preparar consulta: " . $e['message']);
            return "Error al actualizar contratista: " . $e['message'];
        }
        
        // Vincular parámetros
        oci_bind_by_name($stmt, ':nombre1', $datos['nombre1']);
        oci_bind_by_name($stmt, ':nombre2', $datos['nombre2']);
        oci_bind_by_name($stmt, ':apellido1', $datos['apellido1']);
        oci_bind_by_name($stmt, ':apellido2', $datos['apellido2']);
        oci_bind_by_name($stmt, ':tipo_persona', $datos['tipo_persona']);
        oci_bind_by_name($stmt, ':tipo_nacionalidad', $datos['tipo_nacionalidad']);
        oci_bind_by_name($stmt, ':fecha_nacimiento', $datos['fecha_nacimiento']);
        oci_bind_by_name($stmt, ':direccion', $datos['direccion']);
        oci_bind_by_name($stmt, ':tel_fijo', $datos['tel_fijo']);
        oci_bind_by_name($stmt, ':tel_celular', $datos['tel_celular']);
        oci_bind_by_name($stmt, ':correo', $datos['correo']);
        oci_bind_by_name($stmt, ':genero', $datos['genero']);
        oci_bind_by_name($stmt, ':identificacion', $datos['identificacion']);
        
        // Ejecutar consulta
        $r = oci_execute($stmt);
        if (!$r) {
            $e = oci_error($stmt);
            error_log("Error al ejecutar consulta: " . $e['message']);
            return "Error al actualizar contratista: " . $e['message'];
        }
        
        // Liberar recursos
        oci_free_statement($stmt);
        oci_close($conn);
        
        return true;
        
    } catch (Exception $e) {
        error_log("Error en actualizarContratista: " . $e->getMessage());
        return "Error general al actualizar contratista: " . $e->getMessage();
    }
}

/**
 * Asocia un contrato a un contratista y proyecto
 * @param array $datos Datos del contrato
 * @return bool|string True si se creó correctamente, mensaje de error en caso contrario
 */
function crearContratoProyecto($datos) {
    try {
        // Obtener conexión
        $conn = conectarOracle();
        
        // Verificar si el contratista existe
        $sql_check_contratista = "SELECT COUNT(*) as existe FROM CONTRATOS_OPS WHERE IDENTIFICACION = :identificacion";
        $stmt_check_contratista = oci_parse($conn, $sql_check_contratista);
        oci_bind_by_name($stmt_check_contratista, ':identificacion', $datos['identificacion']);
        oci_execute($stmt_check_contratista);
        $row_contratista = oci_fetch_assoc($stmt_check_contratista);
        
        if ($row_contratista['EXISTE'] == 0) {
            return "El contratista con identificación " . $datos['identificacion'] . " no existe en el sistema.";
        }
        
        // Verificar si el proyecto existe
        $sql_check_proyecto = "SELECT COUNT(*) as existe FROM PROYECTO WHERE ANIO_PRO = :anio_pro AND NUMERO_PRO = :numero_pro";
        $stmt_check_proyecto = oci_parse($conn, $sql_check_proyecto);
        oci_bind_by_name($stmt_check_proyecto, ':anio_pro', $datos['anio_pro']);
        oci_bind_by_name($stmt_check_proyecto, ':numero_pro', $datos['numero_pro']);
        oci_execute($stmt_check_proyecto);
        $row_proyecto = oci_fetch_assoc($stmt_check_proyecto);
        
        if ($row_proyecto['EXISTE'] == 0) {
            return "El proyecto especificado no existe en el sistema.";
        }
        
        // Verificar si ya existe un contrato con el mismo número para este contratista
        $sql_check_contrato = "SELECT COUNT(*) as existe FROM PROYECTO_OPS 
                              WHERE IDENTIFICACION = :identificacion 
                              AND NUMERO_CONTRATO = :numero_contrato 
                              AND ANIO_PRO = :anio_pro 
                              AND NUMERO_PRO = :numero_pro";
        $stmt_check_contrato = oci_parse($conn, $sql_check_contrato);
        oci_bind_by_name($stmt_check_contrato, ':identificacion', $datos['identificacion']);
        oci_bind_by_name($stmt_check_contrato, ':numero_contrato', $datos['numero_contrato']);
        oci_bind_by_name($stmt_check_contrato, ':anio_pro', $datos['anio_pro']);
        oci_bind_by_name($stmt_check_contrato, ':numero_pro', $datos['numero_pro']);
        oci_execute($stmt_check_contrato);
        $row_contrato = oci_fetch_assoc($stmt_check_contrato);
        
        if ($row_contrato['EXISTE'] > 0) {
            return "Ya existe un contrato con el mismo número para este contratista y proyecto.";
        }
        
        // Iniciar transacción
        oci_set_action($conn, 'crearContratoProyecto');
        
        // Consulta SQL para insertar en PROYECTO_OPS
        $sql = "INSERT INTO PROYECTO_OPS (
                    ANIO_PRO, NUMERO_PRO, IDENTIFICACION, NUMERO_CONTRATO,
                    CODIGO_ENTIDAD, VIGENCIA_FISCAL, TIPO_REGISTRO, TIPO_MODIFICACION,
                    TIPO_CONFIGURACION, TIPOLOGIA, TIPO_PROCESO, NUMERO_PUBLICACION,
                    FECHA_PUBLICACION, TIPO_CONTRATO, TIPO_VINCULACION, FACULTAD,
                    PROYECTO_CURRICULAR, OBJETO, FECHA_SUSCRIPCION, FECHA_INICIO,
                    FECHA_TERMINACION, VALOR, TIPO_MONEDA, TIPO_TASA,
                    ANIO, MES, DIA, HORA, SITUACION_CONTRATO,
                    OBSERVACIONES, UNIDAD, ESTADO, CDP, FECHA_CDP, RP, FECHA_RP, SECOP
                ) VALUES (
                    :anio_pro, :numero_pro, :identificacion, :numero_contrato,
                    :codigo_entidad, :vigencia_fiscal, :tipo_registro, :tipo_modificacion,
                    :tipo_configuracion, :tipologia, :tipo_proceso, :numero_publicacion,
                    TO_DATE(:fecha_publicacion, 'YYYY-MM-DD'), :tipo_contrato, :tipo_vinculacion, :facultad,
                    :proyecto_curricular, :objeto, TO_DATE(:fecha_suscripcion, 'YYYY-MM-DD'), TO_DATE(:fecha_inicio, 'YYYY-MM-DD'),
                    TO_DATE(:fecha_terminacion, 'YYYY-MM-DD'), :valor, :tipo_moneda, :tipo_tasa,
                    :anio, :mes, :dia, :hora, :situacion_contrato,
                    :observaciones, :unidad, 'A', :cdp, TO_DATE(:fecha_cdp, 'YYYY-MM-DD'), :rp, TO_DATE(:fecha_rp, 'YYYY-MM-DD'), :secop
                )";
        
        // Preparar consulta
        $stmt = oci_parse($conn, $sql);
        if (!$stmt) {
            $e = oci_error($conn);
            error_log("Error al preparar consulta: " . $e['message']);
            return "Error al crear contrato: " . $e['message'];
        }
        
        // Vincular parámetros
        oci_bind_by_name($stmt, ':anio_pro', $datos['anio_pro']);
        oci_bind_by_name($stmt, ':numero_pro', $datos['numero_pro']);
        oci_bind_by_name($stmt, ':identificacion', $datos['identificacion']);
        oci_bind_by_name($stmt, ':numero_contrato', $datos['numero_contrato']);
        oci_bind_by_name($stmt, ':codigo_entidad', $datos['codigo_entidad']);
        oci_bind_by_name($stmt, ':vigencia_fiscal', $datos['vigencia_fiscal']);
        oci_bind_by_name($stmt, ':tipo_registro', $datos['tipo_registro']);
        oci_bind_by_name($stmt, ':tipo_modificacion', $datos['tipo_modificacion']);
        oci_bind_by_name($stmt, ':tipo_configuracion', $datos['tipo_configuracion']);
        oci_bind_by_name($stmt, ':tipologia', $datos['tipologia']);
        oci_bind_by_name($stmt, ':tipo_proceso', $datos['tipo_proceso']);
        oci_bind_by_name($stmt, ':numero_publicacion', $datos['numero_publicacion']);
        oci_bind_by_name($stmt, ':fecha_publicacion', $datos['fecha_publicacion']);
        oci_bind_by_name($stmt, ':tipo_contrato', $datos['tipo_contrato']);
        oci_bind_by_name($stmt, ':tipo_vinculacion', $datos['tipo_vinculacion']);
        oci_bind_by_name($stmt, ':facultad', $datos['facultad']);
        oci_bind_by_name($stmt, ':proyecto_curricular', $datos['proyecto_curricular']);
        oci_bind_by_name($stmt, ':objeto', $datos['objeto']);
        oci_bind_by_name($stmt, ':fecha_suscripcion', $datos['fecha_suscripcion']);
        oci_bind_by_name($stmt, ':fecha_inicio', $datos['fecha_inicio']);
        oci_bind_by_name($stmt, ':fecha_terminacion', $datos['fecha_terminacion']);
        oci_bind_by_name($stmt, ':valor', $datos['valor']);
        oci_bind_by_name($stmt, ':tipo_moneda', $datos['tipo_moneda']);
        oci_bind_by_name($stmt, ':tipo_tasa', $datos['tipo_tasa']);
        oci_bind_by_name($stmt, ':anio', $datos['anio']);
        oci_bind_by_name($stmt, ':mes', $datos['mes']);
        oci_bind_by_name($stmt, ':dia', $datos['dia']);
        oci_bind_by_name($stmt, ':hora', $datos['hora']);
        oci_bind_by_name($stmt, ':situacion_contrato', $datos['situacion_contrato']);
        oci_bind_by_name($stmt, ':observaciones', $datos['observaciones']);
        oci_bind_by_name($stmt, ':unidad', $datos['unidad']);
        oci_bind_by_name($stmt, ':cdp', $datos['cdp']);
        oci_bind_by_name($stmt, ':fecha_cdp', $datos['fecha_cdp']);
        oci_bind_by_name($stmt, ':rp', $datos['rp']);
        oci_bind_by_name($stmt, ':fecha_rp', $datos['fecha_rp']);
        oci_bind_by_name($stmt, ':secop', $datos['secop']);
        
        // Ejecutar consulta
        $r = oci_execute($stmt, OCI_NO_AUTO_COMMIT);
        if (!$r) {
            $e = oci_error($stmt);
            error_log("Error al ejecutar consulta: " . $e['message']);
            oci_rollback($conn);
            return "Error al crear contrato: " . $e['message'];
        }
        
        // Confirmar transacción
        oci_commit($conn);
        
        // Liberar recursos
        oci_free_statement($stmt);
        oci_close($conn);
        
        return true;
        
    } catch (Exception $e) {
        error_log("Error en crearContratoProyecto: " . $e->getMessage());
        return "Error general al crear contrato: " . $e->getMessage();
    }
}

/**
 * Calcula la duración en años, meses, días y horas entre dos fechas
 * @param string $fecha_inicio Fecha de inicio (YYYY-MM-DD)
 * @param string $fecha_fin Fecha de fin (YYYY-MM-DD)
 * @return array Duración calculada [anio, mes, dia, hora]
 */
function calcularDuracionContrato($fecha_inicio, $fecha_fin) {
    try {
        // Convertir fechas a objetos DateTime
        $inicio = new DateTime($fecha_inicio);
        $fin = new DateTime($fecha_fin);
        
        // Calcular la diferencia entre fechas
        $diff = $inicio->diff($fin);
        
        return [
            'anio' => $diff->y,
            'mes' => $diff->m,
            'dia' => $diff->d,
            'hora' => 0 // Por defecto en 0, ajustable si se requiere
        ];
    } catch (Exception $e) {
        error_log("Error al calcular duración del contrato: " . $e->getMessage());
        return [
            'anio' => 0,
            'mes' => 0,
            'dia' => 0,
            'hora' => 0
        ];
    }
}

/**
 * Obtiene la lista de tipos de persona
 * @return array Lista de tipos de persona
 */
function obtenerTiposPersona() {
    try {
        // Obtener conexión
        $conn = conectarOracle();
        
        // Consulta SQL
        $sql = "SELECT CODIGO as codigo, DESCRIPCION as descripcion
                FROM SIV_TIPO_PERSONA
                ORDER BY CODIGO";
        
        // Preparar consulta
        $stid = oci_parse($conn, $sql);
        if (!$stid) {
            $e = oci_error($conn);
            error_log("Error al preparar consulta: " . $e['message']);
            return [];
        }
        
        // Ejecutar consulta
        $r = oci_execute($stid);
        if (!$r) {
            $e = oci_error($stid);
            error_log("Error al ejecutar consulta: " . $e['message']);
            return [];
        }
        
        // Procesar resultados
        $tipos = [];
        
        while ($row = oci_fetch_assoc($stid)) {
            $tipos[] = [
                'codigo' => $row['CODIGO'],
                'descripcion' => $row['DESCRIPCION']
            ];
        }
        
        // Liberar recursos
        oci_free_statement($stid);
        oci_close($conn);
        
        return $tipos;
        
    } catch (Exception $e) {
        error_log("Error en obtenerTiposPersona: " . $e->getMessage());
        return [
            ['codigo' => 1, 'descripcion' => 'Persona Natural'],
            ['codigo' => 2, 'descripcion' => 'Persona Jurídica']
        ];
    }
}

/**
 * Obtiene la lista de tipos de nacionalidad
 * @return array Lista de tipos de nacionalidad
 */
function obtenerTiposNacionalidad() {
    try {
        // Obtener conexión
        $conn = conectarOracle();
        
        // Consulta SQL
        $sql = "SELECT CODIGO as codigo, DESCRIPCION as descripcion
                FROM SIV_NACIONALIDAD
                ORDER BY CODIGO";
        
        // Preparar consulta
        $stid = oci_parse($conn, $sql);
        if (!$stid) {
            $e = oci_error($conn);
            error_log("Error al preparar consulta: " . $e['message']);
            return [];
        }
        
        // Ejecutar consulta
        $r = oci_execute($stid);
        if (!$r) {
            $e = oci_error($stid);
            error_log("Error al ejecutar consulta: " . $e['message']);
            return [];
        }
        
        // Procesar resultados
        $tipos = [];
        
        while ($row = oci_fetch_assoc($stid)) {
            $tipos[] = [
                'codigo' => $row['CODIGO'],
                'descripcion' => $row['DESCRIPCION']
            ];
        }
        
        // Liberar recursos
        oci_free_statement($stid);
        oci_close($conn);
        
        return $tipos;
        
    } catch (Exception $e) {
        error_log("Error en obtenerTiposNacionalidad: " . $e->getMessage());
        return [
            ['codigo' => 1, 'descripcion' => 'Colombiana'],
            ['codigo' => 2, 'descripcion' => 'Extranjera']
        ];
    }
}

/**
 * Obtiene la lista de tipos de contrato
 * @return array Lista de tipos de contrato
 */
function obtenerTiposContrato() {
    try {
        // Obtener conexión
        $conn = conectarOracle();
        
        // Consulta SQL
        $sql = "SELECT CODIGO as codigo, DESCRIPCION as descripcion
                FROM TIPO_CONTRATO
                ORDER BY CODIGO";
        
        // Preparar consulta
        $stid = oci_parse($conn, $sql);
        if (!$stid) {
            $e = oci_error($conn);
            error_log("Error al preparar consulta: " . $e['message']);
            return [];
        }
        
        // Ejecutar consulta
        $r = oci_execute($stid);
        if (!$r) {
            $e = oci_error($stid);
            error_log("Error al ejecutar consulta: " . $e['message']);
            return [];
        }
        
        // Procesar resultados
        $tipos = [];
        
        while ($row = oci_fetch_assoc($stid)) {
            $tipos[] = [
                'codigo' => $row['CODIGO'],
                'descripcion' => $row['DESCRIPCION']
            ];
        }
        
        // Liberar recursos
        oci_free_statement($stid);
        oci_close($conn);
        
        return $tipos;
        
    } catch (Exception $e) {
        error_log("Error en obtenerTiposContrato: " . $e->getMessage());
        return [
            ['codigo' => 1, 'descripcion' => 'Prestación de Servicios'],
            ['codigo' => 2, 'descripcion' => 'Obra'],
            ['codigo' => 3, 'descripcion' => 'Suministro']
        ];
    }
}

/**
 * Obtiene la lista de tipos de vinculación
 * @return array Lista de tipos de vinculación
 */
function obtenerTiposVinculacion() {
    try {
        // Obtener conexión
        $conn = conectarOracle();
        
        // Consulta SQL
        $sql = "SELECT CODIGO as codigo, DESCRIPCION as descripcion
                FROM VINCULACION
                ORDER BY CODIGO";
        
        // Preparar consulta
        $stid = oci_parse($conn, $sql);
        if (!$stid) {
            $e = oci_error($conn);
            error_log("Error al preparar consulta: " . $e['message']);
            return [];
        }
        
        // Ejecutar consulta
        $r = oci_execute($stid);
        if (!$r) {
            $e = oci_error($stid);
            error_log("Error al ejecutar consulta: " . $e['message']);
            return [];
        }
        
        // Procesar resultados
        $tipos = [];
        
        while ($row = oci_fetch_assoc($stid)) {
            $tipos[] = [
                'codigo' => $row['CODIGO'],
                'descripcion' => $row['DESCRIPCION']
            ];
        }
        
        // Liberar recursos
        oci_free_statement($stid);
        oci_close($conn);
        
        return $tipos;
        
    } catch (Exception $e) {
        error_log("Error en obtenerTiposVinculacion: " . $e->getMessage());
        return [
            ['codigo' => 1, 'descripcion' => 'Planta'],
            ['codigo' => 2, 'descripcion' => 'Contratista']
        ];
    }
}

/**
 * Obtiene la lista de facultades
 * @return array Lista de facultades
 */
function obtenerFacultades() {
    try {
        // Obtener conexión
        $conn = conectarOracle();
        
        // Consulta SQL
        $sql = "SELECT CODIGO as codigo, DESCRIPCION as descripcion
                FROM FACULTAD
                ORDER BY DESCRIPCION";
        
        // Preparar consulta
        $stid = oci_parse($conn, $sql);
        if (!$stid) {
            $e = oci_error($conn);
            error_log("Error al preparar consulta: " . $e['message']);
            return [];
        }
        
        // Ejecutar consulta
        $r = oci_execute($stid);
        if (!$r) {
            $e = oci_error($stid);
            error_log("Error al ejecutar consulta: " . $e['message']);
            return [];
        }
        
        // Procesar resultados
        $facultades = [];
        
        while ($row = oci_fetch_assoc($stid)) {
            $facultades[] = [
                'codigo' => $row['CODIGO'],
                'descripcion' => $row['DESCRIPCION']
            ];
        }
        
        // Liberar recursos
        oci_free_statement($stid);
        oci_close($conn);
        
        return $facultades;
        
    } catch (Exception $e) {
        error_log("Error en obtenerFacultades: " . $e->getMessage());
        return [
            ['codigo' => 1, 'descripcion' => 'Facultad de Ingeniería'],
            ['codigo' => 2, 'descripcion' => 'Facultad de Ciencias y Educación'],
            ['codigo' => 3, 'descripcion' => 'Facultad de Medio Ambiente y Recursos Naturales']
        ];
    }
}

/**
 * Obtiene la lista de tipos de configuración
 * @return array Lista de tipos de configuración
 */
function obtenerTiposConfiguracion() {
    try {
        // Obtener conexión
        $conn = conectarOracle();
        
        // Consulta SQL
        $sql = "SELECT CODIGO as codigo, DESCRIPCION as descripcion
                FROM SIV_CONFIGURACION
                ORDER BY CODIGO";
        
        // Preparar consulta
        $stid = oci_parse($conn, $sql);
        if (!$stid) {
            $e = oci_error($conn);
            error_log("Error al preparar consulta: " . $e['message']);
            return [];
        }
        
        // Ejecutar consulta
        $r = oci_execute($stid);
        if (!$r) {
            $e = oci_error($stid);
            error_log("Error al ejecutar consulta: " . $e['message']);
            return [];
        }
        
        // Procesar resultados
        $tipos = [];
        
        while ($row = oci_fetch_assoc($stid)) {
            $tipos[] = [
                'codigo' => $row['CODIGO'],
                'descripcion' => $row['DESCRIPCION']
            ];
        }
        
        // Liberar recursos
        oci_free_statement($stid);
        oci_close($conn);
        
        return $tipos;
        
    } catch (Exception $e) {
        error_log("Error en obtenerTiposConfiguracion: " . $e->getMessage());
        return [
            ['codigo' => 1, 'descripcion' => 'Administración Pública'],
            ['codigo' => 2, 'descripcion' => 'Contratación Directa']
        ];
    }
}

/**
 * Obtiene la lista de tipos de registro
 * @return array Lista de tipos de registro
 */
function obtenerTiposRegistro() {
    try {
        // Obtener conexión
        $conn = conectarOracle();
        
        // Consulta SQL
        $sql = "SELECT CODIGO as codigo, DESCRIPCION as descripcion
                FROM SIV_TIPO_REGISTRO
                ORDER BY CODIGO";
        
        // Preparar consulta
        $stid = oci_parse($conn, $sql);
        if (!$stid) {
            $e = oci_error($conn);
            error_log("Error al preparar consulta: " . $e['message']);
            return [];
        }
        
        // Ejecutar consulta
        $r = oci_execute($stid);
        if (!$r) {
            $e = oci_error($stid);
            error_log("Error al ejecutar consulta: " . $e['message']);
            return [];
        }
        
        // Procesar resultados
        $tipos = [];
        
        while ($row = oci_fetch_assoc($stid)) {
            $tipos[] = [
                'codigo' => $row['CODIGO'],
                'descripcion' => $row['DESCRIPCION']
            ];
        }
        
        // Liberar recursos
        oci_free_statement($stid);
        oci_close($conn);
        
        return $tipos;
        
    } catch (Exception $e) {
        error_log("Error en obtenerTiposRegistro: " . $e->getMessage());
        return [
            ['codigo' => 1, 'descripcion' => 'Normal'],
            ['codigo' => 2, 'descripcion' => 'Especial']
        ];
    }
}

/**
 * Obtiene la lista de tipos de modificación
 * @return array Lista de tipos de modificación
 */
function obtenerTiposModificacion() {
    try {
        // Obtener conexión
        $conn = conectarOracle();
        
        // Consulta SQL
        $sql = "SELECT CODIGO as codigo, DESCRIPCION as descripcion
                FROM SIV_MODIFICACION
                ORDER BY CODIGO";
        
        // Preparar consulta
        $stid = oci_parse($conn, $sql);
        if (!$stid) {
            $e = oci_error($conn);
            error_log("Error al preparar consulta: " . $e['message']);
            return [];
        }
        
        // Ejecutar consulta
        $r = oci_execute($stid);
        if (!$r) {
            $e = oci_error($stid);
            error_log("Error al ejecutar consulta: " . $e['message']);
            return [];
        }
        
        // Procesar resultados
        $tipos = [];
        
        while ($row = oci_fetch_assoc($stid)) {
            $tipos[] = [
                'codigo' => $row['CODIGO'],
                'descripcion' => $row['DESCRIPCION']
            ];
        }
        
        // Liberar recursos
        oci_free_statement($stid);
        oci_close($conn);
        
        return $tipos;
        
    } catch (Exception $e) {
        error_log("Error en obtenerTiposModificacion: " . $e->getMessage());
        return [
            ['codigo' => 1, 'descripcion' => 'Sin Modificación'],
            ['codigo' => 2, 'descripcion' => 'Adición'],
            ['codigo' => 3, 'descripcion' => 'Prórroga']
        ];
    }
}

/**
 * Obtiene la lista de tipologías
 * @return array Lista de tipologías
 */
function obtenerTipologias() {
    try {
        // Obtener conexión
        $conn = conectarOracle();
        
        // Consulta SQL
        $sql = "SELECT CODIGO as codigo, DESCRIPCION as descripcion
                FROM SIV_TIPOLOGIA
                ORDER BY CODIGO";
        
        // Preparar consulta
        $stid = oci_parse($conn, $sql);
        if (!$stid) {
            $e = oci_error($conn);
            error_log("Error al preparar consulta: " . $e['message']);
            return [];
        }
        
        // Ejecutar consulta
        $r = oci_execute($stid);
        if (!$r) {
            $e = oci_error($stid);
            error_log("Error al ejecutar consulta: " . $e['message']);
            return [];
        }
        
        // Procesar resultados
        $tipologias = [];
        
        while ($row = oci_fetch_assoc($stid)) {
            $tipologias[] = [
                'codigo' => $row['CODIGO'],
                'descripcion' => $row['DESCRIPCION']
            ];
        }
        
        // Liberar recursos
        oci_free_statement($stid);
        oci_close($conn);
        
        return $tipologias;
        
    } catch (Exception $e) {
        error_log("Error en obtenerTipologias: " . $e->getMessage());
        return [
            ['codigo' => 1, 'descripcion' => 'Servicios Profesionales'],
            ['codigo' => 2, 'descripcion' => 'Servicios Técnicos'],
            ['codigo' => 3, 'descripcion' => 'Servicios Asistenciales']
        ];
    }
}

/**
 * Obtiene la lista de tipos de proceso
 * @return array Lista de tipos de proceso
 */
function obtenerTiposProceso() {
    try {
        // Obtener conexión
        $conn = conectarOracle();
        
        // Consulta SQL
        $sql = "SELECT CODIGO as codigo, DESCRIPCION as descripcion
                FROM SIV_PROCESO
                ORDER BY CODIGO";
        
        // Preparar consulta
        $stid = oci_parse($conn, $sql);
        if (!$stid) {
            $e = oci_error($conn);
            error_log("Error al preparar consulta: " . $e['message']);
            return [];
        }
        
        // Ejecutar consulta
        $r = oci_execute($stid);
        if (!$r) {
            $e = oci_error($stid);
            error_log("Error al ejecutar consulta: " . $e['message']);
            return [];
        }
        
        // Procesar resultados
        $tipos = [];
        
        while ($row = oci_fetch_assoc($stid)) {
            $tipos[] = [
                'codigo' => $row['CODIGO'],
                'descripcion' => $row['DESCRIPCION']
            ];
        }
        
        // Liberar recursos
        oci_free_statement($stid);
        oci_close($conn);
        
        return $tipos;
        
    } catch (Exception $e) {
        error_log("Error en obtenerTiposProceso: " . $e->getMessage());
        return [
            ['codigo' => 1, 'descripcion' => 'Licitación Pública'],
            ['codigo' => 2, 'descripcion' => 'Selección Abreviada'],
            ['codigo' => 3, 'descripcion' => 'Contratación Directa']
        ];
    }
}

/**
 * Obtiene la lista de tipos de moneda
 * @return array Lista de tipos de moneda
 */
function obtenerTiposMoneda() {
    try {
        // Obtener conexión
        $conn = conectarOracle();
        
        // Consulta SQL
        $sql = "SELECT CODIGO as codigo, DESCRIPCION as descripcion
                FROM SIV_TIPO_MONEDA
                ORDER BY CODIGO";
        
        // Preparar consulta
        $stid = oci_parse($conn, $sql);
        if (!$stid) {
            $e = oci_error($conn);
            error_log("Error al preparar consulta: " . $e['message']);
            return [];
        }
        
        // Ejecutar consulta
        $r = oci_execute($stid);
        if (!$r) {
            $e = oci_error($stid);
            error_log("Error al ejecutar consulta: " . $e['message']);
            return [];
        }
        
        // Procesar resultados
        $tipos = [];
        
        while ($row = oci_fetch_assoc($stid)) {
            $tipos[] = [
                'codigo' => $row['CODIGO'],
                'descripcion' => $row['DESCRIPCION']
            ];
        }
        
        // Liberar recursos
        oci_free_statement($stid);
        oci_close($conn);
        
        return $tipos;
        
    } catch (Exception $e) {
        error_log("Error en obtenerTiposMoneda: " . $e->getMessage());
        return [
            ['codigo' => 1, 'descripcion' => 'Peso Colombiano'],
            ['codigo' => 2, 'descripcion' => 'Dólar Estadounidense'],
            ['codigo' => 3, 'descripcion' => 'Euro']
        ];
    }
    
}

/**
 * Obtiene la lista de tipos de tasa
 * @return array Lista de tipos de tasa
 */
function obtenerTiposTasa() {
    try {
        // Obtener conexión
        $conn = conectarOracle();
        
        // Consulta SQL
        $sql = "SELECT CODIGO as codigo, DESCRIPCION as descripcion
                FROM SIV_TIPO_TASA
                ORDER BY CODIGO";
        
        // Preparar consulta
        $stid = oci_parse($conn, $sql);
        if (!$stid) {
            $e = oci_error($conn);
            error_log("Error al preparar consulta: " . $e['message']);
            return [];
        }
        
        // Ejecutar consulta
        $r = oci_execute($stid);
        if (!$r) {
            $e = oci_error($stid);
            error_log("Error al ejecutar consulta: " . $e['message']);
            return [];
        }
        
        // Procesar resultados
        $tipos = [];
        
        while ($row = oci_fetch_assoc($stid)) {
            $tipos[] = [
                'codigo' => $row['CODIGO'],
                'descripcion' => $row['DESCRIPCION']
            ];
        }
        
        // Liberar recursos
        oci_free_statement($stid);
        oci_close($conn);
        
        return $tipos;
        
    } catch (Exception $e) {
        error_log("Error en obtenerTiposTasa: " . $e['message']);
        return [
            ['codigo' => 1, 'descripcion' => 'Fija'],
            ['codigo' => 2, 'descripcion' => 'Variable']
        ];
    }
}
















































/**
 * Obtiene los detalles de un contratista específico
 * @param string $identificacion Número de identificación del contratista
 * @return array|null Datos del contratista o null si no existe
 */
function obtenerDetalleContratista($identificacion) {
    try {
        // Obtener conexión
        $conn = conectarOracle();
        
        // Consulta SQL para obtener datos del contratista
        $sql = "SELECT 
                    c.IDENTIFICACION,
                    c.NOMBRE1,
                    c.NOMBRE2,
                    c.APELLIDO1,
                    c.APELLIDO2,
                    c.TIPO_PERSONA,
                    tp.DESCRIPCION as TIPO_PERSONA_DESC,
                    c.FECHA_NACIMIENTO,
                    c.DIRECCION,
                    c.TEL_FIJO,
                    c.TEL_CELULAR,
                    c.CORREO,
                    c.GENERO,
                    c.ESTADO
                FROM 
                    CONTRATOS_OPS c
                LEFT JOIN
                    SIV_TIPO_PERSONA tp ON c.TIPO_PERSONA = tp.CODIGO
                WHERE 
                    c.IDENTIFICACION = :identificacion";
        
        // Preparar consulta
        $stid = oci_parse($conn, $sql);
        if (!$stid) {
            $e = oci_error($conn);
            error_log("Error al preparar consulta de contratista: " . $e['message']);
            return null;
        }
        
        // Vincular parámetros
        oci_bind_by_name($stid, ':identificacion', $identificacion);
        
        // Ejecutar consulta
        $r = oci_execute($stid);
        if (!$r) {
            $e = oci_error($stid);
            error_log("Error al ejecutar consulta de contratista: " . $e['message']);
            return null;
        }
        
        // Obtener resultado
        $row = oci_fetch_assoc($stid);
        
        // Convertir claves a minúsculas
        if ($row) {
            $contratista = array();
            foreach ($row as $key => $value) {
                $contratista[strtolower($key)] = $value;
            }
            
            // Formatear nombre completo
            if ($contratista['tipo_persona'] == 1) { // Persona Natural
                $nombres = trim($contratista['nombre1'] . ' ' . ($contratista['nombre2'] ?? ''));
                $apellidos = trim(($contratista['apellido1'] ?? '') . ' ' . ($contratista['apellido2'] ?? ''));
                $contratista['nombre_completo'] = trim($nombres . ' ' . $apellidos);
            } else { // Persona Jurídica u otro tipo
                $contratista['nombre_completo'] = $contratista['nombre1']; // Asumiendo que es la razón social
            }
        } else {
            $contratista = null;
        }
        
        // Liberar recursos
        oci_free_statement($stid);
        oci_close($conn);
        
        return $contratista;
        
    } catch (Exception $e) {
        error_log("Error en obtenerDetalleContratista: " . $e->getMessage());
        return null;
    }
}
/**
 * Obtiene las actas relacionadas con un contratista en un proyecto específico
 * @param string $contratistaId ID del contratista
 * @param int $proyectoId ID del proyecto
 * @return array Lista de actas relacionadas con el contratista
 */
function obtenerActasContratistaProyecto($contratistaId, $proyectoId) {
    try {
        // Obtener conexión
        $conn = conectarOracle();
        
        // Extraer ANIO_PRO del proyecto
        $sqlProy = "SELECT ANIO_PRO FROM PROYECTO WHERE NUMERO_PRO = :id";
        $stidProy = oci_parse($conn, $sqlProy);
        oci_bind_by_name($stidProy, ':id', $proyectoId);
        oci_execute($stidProy);
        $rowProy = oci_fetch_assoc($stidProy);
        
        if (!$rowProy) {
            return [];
        }
        
        $anio_pro = $rowProy['ANIO_PRO'];
        
        // Obtener nombre del contratista para buscar menciones en las actas
        $sqlContratista = "SELECT 
                              NOMBRE1, NOMBRE2, APELLIDO1, APELLIDO2, TIPO_PERSONA 
                           FROM 
                              CONTRATOS_OPS 
                           WHERE 
                              IDENTIFICACION = :contratista_id";
        $stidContratista = oci_parse($conn, $sqlContratista);
        oci_bind_by_name($stidContratista, ':contratista_id', $contratistaId);
        oci_execute($stidContratista);
        $rowContratista = oci_fetch_assoc($stidContratista);
        
        if (!$rowContratista) {
            return [];
        }
        
        // Formar nombre del contratista para búsqueda
        $nombreBusqueda = '';
        if ($rowContratista['TIPO_PERSONA'] == 1) { // Persona natural
            $nombreBusqueda = trim($rowContratista['NOMBRE1'] . ' ' . $rowContratista['APELLIDO1']);
        } else {
            $nombreBusqueda = trim($rowContratista['NOMBRE1']); // Razón social
        }
        
        // Consulta SQL para obtener actas relacionadas con el contratista en el proyecto
        // Esta consulta busca actas que coinciden con alguno de estos criterios:
        // 1. Actas donde se menciona el ID del contratista en las observaciones
        // 2. Actas donde se menciona el nombre del contratista en las observaciones
        // 3. Actas generadas cerca de las fechas de inicio o terminación de los contratos del contratista
        $sql = "SELECT DISTINCT
                    a.ANIO_PRO,
                    a.NUMERO_PRO,
                    a.NUMERO_ACTA,
                    a.TIPO_ACTA,
                    t.DESCRIPCION as TIPO_DESCRIPCION,
                    a.FECHA_ACTA,
                    a.OBSERVA,
                    a.ESTADO,
                    d.ARCHIVO
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
                    AND a.ESTADO = 'A'
                    AND (
                        -- Buscar menciones del contratista en observaciones (ID)
                        UPPER(a.OBSERVA) LIKE UPPER('%' || :contratista_id || '%')
                        OR
                        -- Buscar menciones del nombre del contratista
                        UPPER(a.OBSERVA) LIKE UPPER('%' || :nombre_busqueda || '%')
                        OR
                        -- Actas temporalmente relacionadas con contratos del contratista
                        EXISTS (
                            SELECT 1 
                            FROM PROYECTO_OPS po
                            WHERE po.IDENTIFICACION = :contratista_id
                            AND po.ANIO_PRO = a.ANIO_PRO
                            AND po.NUMERO_PRO = a.NUMERO_PRO
                            AND (
                                (a.FECHA_ACTA BETWEEN po.FECHA_INICIO - 30 AND po.FECHA_INICIO + 30)
                                OR 
                                (a.FECHA_ACTA BETWEEN po.FECHA_TERMINACION - 30 AND po.FECHA_TERMINACION + 30)
                            )
                        )
                    )
                ORDER BY 
                    a.FECHA_ACTA DESC, a.NUMERO_ACTA DESC";
        
        // Preparar consulta
        $stid = oci_parse($conn, $sql);
        if (!$stid) {
            $e = oci_error($conn);
            error_log("Error al preparar consulta de actas relacionadas con contratista: " . $e['message']);
            return [];
        }
        
        // Vincular parámetros
        oci_bind_by_name($stid, ':anio_pro', $anio_pro);
        oci_bind_by_name($stid, ':numero_pro', $proyectoId);
        oci_bind_by_name($stid, ':contratista_id', $contratistaId);
        oci_bind_by_name($stid, ':nombre_busqueda', $nombreBusqueda);
        
        // Ejecutar consulta
        $r = oci_execute($stid);
        if (!$r) {
            $e = oci_error($stid);
            error_log("Error al ejecutar consulta de actas relacionadas con contratista: " . $e['message']);
            return [];
        }
        
        // Procesar resultados
        $actas = [];
        
        while ($row = oci_fetch_assoc($stid)) {
            // Convertir claves a minúsculas
            $acta = array();
            foreach ($row as $key => $value) {
                $acta[strtolower($key)] = $value;
            }
            $actas[] = $acta;
        }
        
        // Liberar recursos
        oci_free_statement($stid);
        if (isset($stidContratista)) oci_free_statement($stidContratista);
        if (isset($stidProy)) oci_free_statement($stidProy);
        oci_close($conn);
        
        return $actas;
        
    } catch (Exception $e) {
        error_log("Error en obtenerActasContratistaProyecto: " . $e->getMessage());
        return [];
    }
}