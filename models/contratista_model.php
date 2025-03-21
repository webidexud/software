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
 * Modelo para la gestión de contratistas
 * 
 * Este archivo contiene funciones para la gestión de contratistas
 * y sus contratos asociados a proyectos
 */

// Incluir el archivo de conexión
require_once 'config/database.php';

/**
 * Obtiene todos los tipos de persona
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
            error_log("Error al preparar consulta de tipos de persona: " . $e['message']);
            return [];
        }
        
        // Ejecutar consulta
        $r = oci_execute($stid);
        if (!$r) {
            $e = oci_error($stid);
            error_log("Error al ejecutar consulta de tipos de persona: " . $e['message']);
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
        
        // Datos de ejemplo en caso de error
        return [
            ['codigo' => 1, 'descripcion' => 'Natural'],
            ['codigo' => 2, 'descripcion' => 'Jurídica']
        ];
    }
}

/**
 * Obtiene todos los tipos de nacionalidad
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
            error_log("Error al preparar consulta de nacionalidades: " . $e['message']);
            return [];
        }
        
        // Ejecutar consulta
        $r = oci_execute($stid);
        if (!$r) {
            $e = oci_error($stid);
            error_log("Error al ejecutar consulta de nacionalidades: " . $e['message']);
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
        
        // Datos de ejemplo en caso de error
        return [
            ['codigo' => 1, 'descripcion' => 'Colombiana'],
            ['codigo' => 2, 'descripcion' => 'Extranjera']
        ];
    }
}

/**
 * Obtiene todos los tipos de contrato
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
            error_log("Error al preparar consulta de tipos de contrato: " . $e['message']);
            return [];
        }
        
        // Ejecutar consulta
        $r = oci_execute($stid);
        if (!$r) {
            $e = oci_error($stid);
            error_log("Error al ejecutar consulta de tipos de contrato: " . $e['message']);
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
        
        // Datos de ejemplo en caso de error
        return [
            ['codigo' => 1, 'descripcion' => 'Prestación de Servicios'],
            ['codigo' => 2, 'descripcion' => 'Contrato Laboral'],
            ['codigo' => 3, 'descripcion' => 'Obra o Labor']
        ];
    }
}

/**
 * Obtiene todos los tipos de vinculación
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
            error_log("Error al preparar consulta de tipos de vinculación: " . $e['message']);
            return [];
        }
        
        // Ejecutar consulta
        $r = oci_execute($stid);
        if (!$r) {
            $e = oci_error($stid);
            error_log("Error al ejecutar consulta de tipos de vinculación: " . $e['message']);
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
        
        // Datos de ejemplo en caso de error
        return [
            ['codigo' => 1, 'descripcion' => 'Directa'],
            ['codigo' => 2, 'descripcion' => 'Indirecta']
        ];
    }
}

/**
 * Obtiene todas las facultades
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
            error_log("Error al preparar consulta de facultades: " . $e['message']);
            return [];
        }
        
        // Ejecutar consulta
        $r = oci_execute($stid);
        if (!$r) {
            $e = oci_error($stid);
            error_log("Error al ejecutar consulta de facultades: " . $e['message']);
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
        
        // Datos de ejemplo en caso de error
        return [
            ['codigo' => 1, 'descripcion' => 'Facultad de Ingeniería'],
            ['codigo' => 2, 'descripcion' => 'Facultad de Ciencias y Educación'],
            ['codigo' => 3, 'descripcion' => 'Facultad de Artes']
        ];
    }
}

/**
 * Obtiene todos los proyectos curriculares
 * @return array Lista de proyectos curriculares
 */
function obtenerProyectosCurriculares() {
    try {
        // Obtener conexión
        $conn = conectarOracle();
        
        // Consulta SQL
        $sql = "SELECT CODIGO as codigo, DESCRIPCION as descripcion
                FROM PROYECTO_CURRICULAR
                ORDER BY DESCRIPCION";
        
        // Verificar si la tabla existe, si no existe, consultar otra tabla similar
        $stid_check = oci_parse($conn, "SELECT COUNT(*) as existe FROM ALL_TABLES WHERE TABLE_NAME = 'PROYECTO_CURRICULAR'");
        oci_execute($stid_check);
        $row_check = oci_fetch_assoc($stid_check);
        
        if ($row_check['EXISTE'] == 0) {
            // Si la tabla no existe, usar una consulta alternativa o retornar datos de ejemplo
            oci_free_statement($stid_check);
            oci_close($conn);
            
            return [
                ['codigo' => 1, 'descripcion' => 'Ingeniería de Sistemas'],
                ['codigo' => 2, 'descripcion' => 'Ingeniería Industrial'],
                ['codigo' => 3, 'descripcion' => 'Licenciatura en Educación']
            ];
        }
        
        // Preparar consulta
        $stid = oci_parse($conn, $sql);
        if (!$stid) {
            $e = oci_error($conn);
            error_log("Error al preparar consulta de proyectos curriculares: " . $e['message']);
            return [];
        }
        
        // Ejecutar consulta
        $r = oci_execute($stid);
        if (!$r) {
            $e = oci_error($stid);
            error_log("Error al ejecutar consulta de proyectos curriculares: " . $e['message']);
            return [];
        }
        
        // Procesar resultados
        $proyectos = [];
        
        while ($row = oci_fetch_assoc($stid)) {
            $proyectos[] = [
                'codigo' => $row['CODIGO'],
                'descripcion' => $row['DESCRIPCION']
            ];
        }
        
        // Liberar recursos
        oci_free_statement($stid);
        oci_close($conn);
        
        return $proyectos;
        
    } catch (Exception $e) {
        error_log("Error en obtenerProyectosCurriculares: " . $e->getMessage());
        
        // Datos de ejemplo en caso de error
        return [
            ['codigo' => 1, 'descripcion' => 'Ingeniería de Sistemas'],
            ['codigo' => 2, 'descripcion' => 'Ingeniería Industrial'],
            ['codigo' => 3, 'descripcion' => 'Licenciatura en Educación']
        ];
    }
}



/**
 * Obtiene detalles de un contratista por su identificación
 * @param string $identificacion Número de identificación del contratista
 * @return array|null Datos del contratista o null si no existe
 */
function obtenerContratista($identificacion) {
    try {
        // Obtener conexión
        $conn = conectarOracle();
        
        // Consulta SQL
        $sql = "SELECT 
                    c.IDENTIFICACION, c.NOMBRE1, c.NOMBRE2, c.APELLIDO1, c.APELLIDO2,
                    c.TIPO_PERSONA, c.TIPO_NACIONALIDAD, c.FECHA_NACIMIENTO,
                    c.DIRECCION, c.TEL_FIJO, c.TEL_CELULAR, c.CORREO, c.GENERO, c.ESTADO,
                    tp.DESCRIPCION as TIPO_PERSONA_DESC,
                    tn.DESCRIPCION as TIPO_NACIONALIDAD_DESC
                FROM 
                    CONTRATOS_OPS c
                    LEFT JOIN SIV_TIPO_PERSONA tp ON c.TIPO_PERSONA = tp.CODIGO
                    LEFT JOIN SIV_NACIONALIDAD tn ON c.TIPO_NACIONALIDAD = tn.CODIGO
                WHERE 
                    c.IDENTIFICACION = :id
                    AND c.ESTADO = 'A'";
        
        // Preparar consulta
        $stid = oci_parse($conn, $sql);
        if (!$stid) {
            $e = oci_error($conn);
            error_log("Error al preparar consulta para obtener contratista: " . $e['message']);
            return null;
        }
        
        // Vincular parámetros
        oci_bind_by_name($stid, ':id', $identificacion);
        
        // Ejecutar consulta
        $r = oci_execute($stid);
        if (!$r) {
            $e = oci_error($stid);
            error_log("Error al ejecutar consulta para obtener contratista: " . $e['message']);
            return null;
        }
        
        // Obtener resultado
        $row = oci_fetch_assoc($stid);
        
        // Convertir claves a minúsculas (para mantener consistencia)
        if ($row) {
            $contratista = array();
            foreach ($row as $key => $value) {
                $contratista[strtolower($key)] = $value;
            }
            
            // Formato de nombre completo
            if ($contratista['tipo_persona'] == 1) {
                // Persona natural: nombre completo
                $nombres = trim($contratista['nombre1'] . ' ' . ($contratista['nombre2'] ?? ''));
                $apellidos = trim(($contratista['apellido1'] ?? '') . ' ' . ($contratista['apellido2'] ?? ''));
                $contratista['nombre_completo'] = trim($nombres . ' ' . $apellidos);
            } else {
                // Persona jurídica: razón social
                $contratista['nombre_completo'] = $contratista['nombre1'];
            }
        } else {
            $contratista = null;
        }
        
        // Liberar recursos
        oci_free_statement($stid);
        oci_close($conn);
        
        return $contratista;
        
    } catch (Exception $e) {
        error_log("Error en obtenerContratista: " . $e->getMessage());
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




/**
 * Verifica si un contratista existe por su identificación
 * @param string $identificacion Número de identificación del contratista
 * @return bool True si existe, False si no existe
 */
function verificarContratista($identificacion) {
    try {
        // Obtener conexión
        $conn = conectarOracle();
        
        // Consulta SQL para verificar si existe
        $sql = "SELECT COUNT(*) as total FROM CONTRATOS_OPS WHERE IDENTIFICACION = :id";
        
        // Preparar consulta
        $stid = oci_parse($conn, $sql);
        if (!$stid) {
            $e = oci_error($conn);
            error_log("Error al preparar consulta para verificar contratista: " . $e['message']);
            return false;
        }
        
        // Vincular parámetros
        oci_bind_by_name($stid, ':id', $identificacion);
        
        // Ejecutar consulta
        $r = oci_execute($stid);
        if (!$r) {
            $e = oci_error($stid);
            error_log("Error al ejecutar consulta para verificar contratista: " . $e['message']);
            return false;
        }
        
        // Obtener resultado
        $row = oci_fetch_assoc($stid);
        $existe = ($row['TOTAL'] > 0);
        
        // Liberar recursos
        oci_free_statement($stid);
        oci_close($conn);
        
        return $existe;
        
    } catch (Exception $e) {
        error_log("Error en verificarContratista: " . $e->getMessage());
        return false;
    }
}

/**
 * Busca un contratista por su identificación
 * @param string $identificacion ID del contratista a buscar
 * @return array|null Datos del contratista si existe, null si no existe
 */
function obtenerContratistaPorId($identificacion) {
    try {
        // Obtener conexión
        $conn = conectarOracle();
        
        // Consulta SQL para buscar el contratista
        $sql = "SELECT 
                    c.IDENTIFICACION,
                    c.NOMBRE1,
                    c.NOMBRE2,
                    c.APELLIDO1,
                    c.APELLIDO2,
                    c.TIPO_PERSONA,
                    tp.DESCRIPCION as TIPO_PERSONA_DESC,
                    c.TIPO_NACIONALIDAD,
                    tn.DESCRIPCION as TIPO_NACIONALIDAD_DESC,
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
                LEFT JOIN 
                    SIV_NACIONALIDAD tn ON c.TIPO_NACIONALIDAD = tn.CODIGO
                WHERE 
                    c.IDENTIFICACION = :id
                    AND c.ESTADO = 'A'";
        
        // Preparar consulta
        $stid = oci_parse($conn, $sql);
        if (!$stid) {
            $e = oci_error($conn);
            error_log("Error al preparar consulta para buscar contratista: " . $e['message']);
            return null;
        }
        
        // Vincular parámetros
        oci_bind_by_name($stid, ':id', $identificacion);
        
        // Ejecutar consulta
        $r = oci_execute($stid);
        if (!$r) {
            $e = oci_error($stid);
            error_log("Error al ejecutar consulta para buscar contratista: " . $e['message']);
            return null;
        }
        
        // Obtener resultado
        $row = oci_fetch_assoc($stid);
        
        // Si no se encontró, retornar null
        if (!$row) {
            oci_free_statement($stid);
            oci_close($conn);
            return null;
        }
        
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
 * Crea un nuevo contratista
 * @param array $datos Datos del contratista
 * @return bool|string True si se creó correctamente, mensaje de error en caso contrario
 */
function crearContratista($datos) {
    try {
        // Verificar si ya existe un contratista con esa identificación
        if (verificarContratista($datos['identificacion'])) {
            return "Ya existe un contratista con la identificación " . $datos['identificacion'];
        }
        
        // Obtener conexión
        $conn = conectarOracle();
        
        // Iniciar transacción
        oci_set_action($conn, 'crearContratista');
        
        // Consulta SQL para insertar en CONTRATOS_OPS
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
        $stid = oci_parse($conn, $sql);
        if (!$stid) {
            $e = oci_error($conn);
            error_log("Error al preparar consulta para crear contratista: " . $e['message']);
            return "Error al preparar consulta: " . $e['message'];
        }
        
        // Vincular parámetros con valores por defecto para nulos
        $nombre1 = $datos['nombre1'] ?? '';
        $nombre2 = $datos['nombre2'] ?? '';
        $apellido1 = $datos['apellido1'] ?? '';
        $apellido2 = $datos['apellido2'] ?? '';
        $tipo_persona = $datos['tipo_persona'] ?? 1;
        $tipo_nacionalidad = $datos['tipo_nacionalidad'] ?? 1;
        $fecha_nacimiento = !empty($datos['fecha_nacimiento']) ? $datos['fecha_nacimiento'] : null;
        $direccion = $datos['direccion'] ?? '';
        $tel_fijo = $datos['tel_fijo'] ?? '';
        $tel_celular = $datos['tel_celular'] ?? '';
        $correo = $datos['correo'] ?? '';
        $genero = $datos['genero'] ?? 'M';
        
        oci_bind_by_name($stid, ':identificacion', $datos['identificacion']);
        oci_bind_by_name($stid, ':nombre1', $nombre1);
        oci_bind_by_name($stid, ':nombre2', $nombre2);
        oci_bind_by_name($stid, ':apellido1', $apellido1);
        oci_bind_by_name($stid, ':apellido2', $apellido2);
        oci_bind_by_name($stid, ':tipo_persona', $tipo_persona);
        oci_bind_by_name($stid, ':tipo_nacionalidad', $tipo_nacionalidad);
        oci_bind_by_name($stid, ':fecha_nacimiento', $fecha_nacimiento);
        oci_bind_by_name($stid, ':direccion', $direccion);
        oci_bind_by_name($stid, ':tel_fijo', $tel_fijo);
        oci_bind_by_name($stid, ':tel_celular', $tel_celular);
        oci_bind_by_name($stid, ':correo', $correo);
        oci_bind_by_name($stid, ':genero', $genero);
        
        // Ejecutar consulta
        $r = oci_execute($stid, OCI_NO_AUTO_COMMIT);
        if (!$r) {
            $e = oci_error($stid);
            error_log("Error al ejecutar consulta para crear contratista: " . $e['message']);
            oci_rollback($conn);
            return "Error al ejecutar la consulta: " . $e['message'];
        }
        
        // Confirmar transacción
        $commit = oci_commit($conn);
        
        // Liberar recursos
        oci_free_statement($stid);
        oci_close($conn);
        
        return $commit ? true : "Error al confirmar la transacción";
        
    } catch (Exception $e) {
        error_log("Error en crearContratista: " . $e->getMessage());
        return "Error general: " . $e->getMessage();
    }
}

/**
 * Actualiza los datos de un contratista existente
 * @param array $datos Datos del contratista
 * @return bool|string True si se actualizó correctamente, mensaje de error en caso contrario
 */
function actualizarContratista($datos) {
    try {
        // Verificar si el contratista existe
        if (!verificarContratista($datos['identificacion'])) {
            return "No existe un contratista con la identificación " . $datos['identificacion'];
        }
        
        // Obtener conexión
        $conn = conectarOracle();
        
        // Iniciar transacción
        oci_set_action($conn, 'actualizarContratista');
        
        // Consulta SQL para actualizar en CONTRATOS_OPS
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
                WHERE 
                    IDENTIFICACION = :identificacion";
        
        // Preparar consulta
        $stid = oci_parse($conn, $sql);
        if (!$stid) {
            $e = oci_error($conn);
            error_log("Error al preparar consulta para actualizar contratista: " . $e['message']);
            return "Error al preparar consulta: " . $e['message'];
        }
        
        // Vincular parámetros con valores por defecto para nulos
        $nombre1 = $datos['nombre1'] ?? '';
        $nombre2 = $datos['nombre2'] ?? '';
        $apellido1 = $datos['apellido1'] ?? '';
        $apellido2 = $datos['apellido2'] ?? '';
        $tipo_persona = $datos['tipo_persona'] ?? 1;
        $tipo_nacionalidad = $datos['tipo_nacionalidad'] ?? 1;
        $fecha_nacimiento = !empty($datos['fecha_nacimiento']) ? $datos['fecha_nacimiento'] : null;
        $direccion = $datos['direccion'] ?? '';
        $tel_fijo = $datos['tel_fijo'] ?? '';
        $tel_celular = $datos['tel_celular'] ?? '';
        $correo = $datos['correo'] ?? '';
        $genero = $datos['genero'] ?? 'M';
        
        oci_bind_by_name($stid, ':nombre1', $nombre1);
        oci_bind_by_name($stid, ':nombre2', $nombre2);
        oci_bind_by_name($stid, ':apellido1', $apellido1);
        oci_bind_by_name($stid, ':apellido2', $apellido2);
        oci_bind_by_name($stid, ':tipo_persona', $tipo_persona);
        oci_bind_by_name($stid, ':tipo_nacionalidad', $tipo_nacionalidad);
        oci_bind_by_name($stid, ':fecha_nacimiento', $fecha_nacimiento);
        oci_bind_by_name($stid, ':direccion', $direccion);
        oci_bind_by_name($stid, ':tel_fijo', $tel_fijo);
        oci_bind_by_name($stid, ':tel_celular', $tel_celular);
        oci_bind_by_name($stid, ':correo', $correo);
        oci_bind_by_name($stid, ':genero', $genero);
        oci_bind_by_name($stid, ':identificacion', $datos['identificacion']);
        
        // Ejecutar consulta
        $r = oci_execute($stid, OCI_NO_AUTO_COMMIT);
        if (!$r) {
            $e = oci_error($stid);
            error_log("Error al ejecutar consulta para actualizar contratista: " . $e['message']);
            oci_rollback($conn);
            return "Error al ejecutar la consulta: " . $e['message'];
        }
        
        // Confirmar transacción
        $commit = oci_commit($conn);
        
        // Liberar recursos
        oci_free_statement($stid);
        oci_close($conn);
        
        return $commit ? true : "Error al confirmar la transacción";
        
    } catch (Exception $e) {
        error_log("Error en actualizarContratista: " . $e->getMessage());
        return "Error general: " . $e->getMessage();
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
 * Asocia un contratista a un proyecto mediante un contrato
 * @param array $datos Datos del contrato de proyecto
 * @return bool|string True si se asoció correctamente, mensaje de error en caso contrario
 */
function asociarContratistaProyecto($datos) {
    try {
        // Verificar si el contratista existe
        if (!verificarContratista($datos['identificacion'])) {
            return "No existe un contratista con la identificación " . $datos['identificacion'];
        }
        
        // Obtener conexión
        $conn = conectarOracle();
        
        // Iniciar transacción
        oci_set_action($conn, 'asociarContratistaProyecto');
        
        // Verificar si ya existe un contrato con el mismo número para este contratista y proyecto
        $sql_check = "SELECT COUNT(*) as total FROM PROYECTO_OPS 
                      WHERE IDENTIFICACION = :identificacion 
                      AND NUMERO_CONTRATO = :numero_contrato 
                      AND ANIO_PRO = :anio_pro 
                      AND NUMERO_PRO = :numero_pro";
        
        $stid_check = oci_parse($conn, $sql_check);
        oci_bind_by_name($stid_check, ':identificacion', $datos['identificacion']);
        oci_bind_by_name($stid_check, ':numero_contrato', $datos['numero_contrato']);
        oci_bind_by_name($stid_check, ':anio_pro', $datos['anio_pro']);
        oci_bind_by_name($stid_check, ':numero_pro', $datos['numero_pro']);
        oci_execute($stid_check);
        
        $row = oci_fetch_assoc($stid_check);
        if ($row['TOTAL'] > 0) {
            oci_free_statement($stid_check);
            oci_close($conn);
            return "Ya existe un contrato con el número " . $datos['numero_contrato'] . " para este contratista en este proyecto";
        }
        
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
        $stid = oci_parse($conn, $sql);
        if (!$stid) {
            $e = oci_error($conn);
            error_log("Error al preparar consulta para asociar contratista: " . $e['message']);
            return "Error al preparar consulta: " . $e['message'];
        }
        
        // Valores por defecto para campos opcionales
        $codigo_entidad = $datos['codigo_entidad'] ?? 108;
        $vigencia_fiscal = $datos['vigencia_fiscal'] ?? date('Y');
        $tipo_registro = $datos['tipo_registro'] ?? 1;
        $tipo_modificacion = $datos['tipo_modificacion'] ?? 1;
        $tipo_configuracion = $datos['tipo_configuracion'] ?? 1;
        $tipologia = $datos['tipologia'] ?? 1;
        $tipo_proceso = $datos['tipo_proceso'] ?? 3; // Contratación directa por defecto
        $numero_publicacion = $datos['numero_publicacion'] ?? 0;
        $fecha_publicacion = $datos['fecha_publicacion'] ?? null;
        $tipo_vinculacion = $datos['tipo_vinculacion'] ?? 1;
        $facultad = $datos['facultad'] ?? null;
        $proyecto_curricular = $datos['proyecto_curricular'] ?? null;
        $fecha_suscripcion = $datos['fecha_suscripcion'] ?? $datos['fecha_inicio'];
        $situacion_contrato = $datos['situacion_contrato'] ?? 8; // En trámite por defecto
        $observaciones = $datos['observaciones'] ?? '';
        $cdp = $datos['cdp'] ?? '';
        $fecha_cdp = !empty($datos['fecha_cdp']) ? $datos['fecha_cdp'] : null;
        $rp = $datos['rp'] ?? '';
        $fecha_rp = !empty($datos['fecha_rp']) ? $datos['fecha_rp'] : null;
        $secop = $datos['secop'] ?? '';
        
        // Vincular parámetros
        oci_bind_by_name($stid, ':anio_pro', $datos['anio_pro']);
        oci_bind_by_name($stid, ':numero_pro', $datos['numero_pro']);
        oci_bind_by_name($stid, ':identificacion', $datos['identificacion']);
        oci_bind_by_name($stid, ':numero_contrato', $datos['numero_contrato']);
        oci_bind_by_name($stid, ':codigo_entidad', $codigo_entidad);
        oci_bind_by_name($stid, ':vigencia_fiscal', $vigencia_fiscal);
        oci_bind_by_name($stid, ':tipo_registro', $tipo_registro);
        oci_bind_by_name($stid, ':tipo_modificacion', $tipo_modificacion);
        oci_bind_by_name($stid, ':tipo_configuracion', $tipo_configuracion);
        oci_bind_by_name($stid, ':tipologia', $tipologia);
        oci_bind_by_name($stid, ':tipo_proceso', $tipo_proceso);
        oci_bind_by_name($stid, ':numero_publicacion', $numero_publicacion);
        oci_bind_by_name($stid, ':fecha_publicacion', $fecha_publicacion);
        oci_bind_by_name($stid, ':tipo_contrato', $datos['tipo_contrato']);
        oci_bind_by_name($stid, ':tipo_vinculacion', $tipo_vinculacion);
        oci_bind_by_name($stid, ':facultad', $facultad);
        oci_bind_by_name($stid, ':proyecto_curricular', $proyecto_curricular);
        oci_bind_by_name($stid, ':objeto', $datos['objeto']);
        oci_bind_by_name($stid, ':fecha_suscripcion', $fecha_suscripcion);
        oci_bind_by_name($stid, ':fecha_inicio', $datos['fecha_inicio']);
        oci_bind_by_name($stid, ':fecha_terminacion', $datos['fecha_terminacion']);
        oci_bind_by_name($stid, ':valor', $datos['valor']);
        oci_bind_by_name($stid, ':tipo_moneda', $datos['tipo_moneda']);
        oci_bind_by_name($stid, ':tipo_tasa', $datos['tipo_tasa']);
        oci_bind_by_name($stid, ':anio', $datos['anio']);
        oci_bind_by_name($stid, ':mes', $datos['mes']);
        oci_bind_by_name($stid, ':dia', $datos['dia']);
        oci_bind_by_name($stid, ':hora', $datos['hora']);
        oci_bind_by_name($stid, ':situacion_contrato', $situacion_contrato);
        oci_bind_by_name($stid, ':observaciones', $observaciones);
        oci_bind_by_name($stid, ':unidad', $datos['unidad']);
        oci_bind_by_name($stid, ':cdp', $cdp);
        oci_bind_by_name($stid, ':fecha_cdp', $fecha_cdp);
        oci_bind_by_name($stid, ':rp', $rp);
        oci_bind_by_name($stid, ':fecha_rp', $fecha_rp);
        oci_bind_by_name($stid, ':secop', $secop);
        
        // Ejecutar consulta
        $r = oci_execute($stid, OCI_NO_AUTO_COMMIT);
        if (!$r) {
            $e = oci_error($stid);
            error_log("Error al ejecutar consulta para asociar contratista: " . $e['message']);
            oci_rollback($conn);
            return "Error al ejecutar la consulta: " . $e['message'];
        }
        
        // Confirmar transacción
        $commit = oci_commit($conn);
        
        // Liberar recursos
        oci_free_statement($stid);
        oci_close($conn);
        
        return $commit ? true : "Error al confirmar la transacción";
        
    } catch (Exception $e) {
        error_log("Error en asociarContratistaProyecto: " . $e->getMessage());
        return "Error general: " . $e->getMessage();
    }
}

/**
 * Obtiene información detallada de un contrato específico para un contratista
 * @param int $identificacion ID del contratista
 * @param int $numero_contrato Número del contrato
 * @param int $proyecto_id ID del proyecto
 * @return array|null Datos del contrato o null si no existe
 */
function obtenerDetalleContrato($identificacion, $numero_contrato, $proyecto_id) {
    try {
        // Obtener conexión
        $conn = conectarOracle();
        
        // Extraer ANIO_PRO del proyecto
        $sqlProy = "SELECT ANIO_PRO FROM PROYECTO WHERE NUMERO_PRO = :id";
        $stidProy = oci_parse($conn, $sqlProy);
        oci_bind_by_name($stidProy, ':id', $proyecto_id);
        oci_execute($stidProy);
        $rowProy = oci_fetch_assoc($stidProy);
        
        if (!$rowProy) {
            return null;
        }
        
        $anio_pro = $rowProy['ANIO_PRO'];
        
        // Consulta SQL para obtener detalles del contrato
        $sql = "SELECT 
                    po.*,
                    tc.DESCRIPCION as TIPO_CONTRATO_DESC,
                    tp.DESCRIPCION as TIPO_PROCESO_DESC,
                    tv.DESCRIPCION as TIPO_VINCULACION_DESC,
                    s.DESCRIPCION as SITUACION_DESC,
                    f.DESCRIPCION as FACULTAD_DESC,
                    pc.DESCRIPCION as PROYECTO_CURRICULAR_DESC,
                    u.DESCRIPCION as UNIDAD_DESC
                FROM 
                    PROYECTO_OPS po
                LEFT JOIN 
                    TIPO_CONTRATO tc ON po.TIPO_CONTRATO = tc.CODIGO
                LEFT JOIN 
                    SIV_PROCESO tp ON po.TIPO_PROCESO = tp.CODIGO
                LEFT JOIN 
                    VINCULACION tv ON po.TIPO_VINCULACION = tv.CODIGO
                LEFT JOIN 
                    SITUACION s ON po.SITUACION_CONTRATO = s.CODIGO
                LEFT JOIN 
                    FACULTAD f ON po.FACULTAD = f.CODIGO
                LEFT JOIN 
                    PROYECTO_CURRICULAR pc ON po.PROYECTO_CURRICULAR = pc.CODIGO
                LEFT JOIN 
                    UNIDAD u ON po.UNIDAD = u.CODIGO
                WHERE 
                    po.IDENTIFICACION = :identificacion
                    AND po.NUMERO_CONTRATO = :numero_contrato
                    AND po.ANIO_PRO = :anio_pro
                    AND po.NUMERO_PRO = :numero_pro
                    AND po.ESTADO = 'A'";
        
        // Preparar consulta
        $stid = oci_parse($conn, $sql);
        if (!$stid) {
            $e = oci_error($conn);
            error_log("Error al preparar consulta de detalle contrato: " . $e['message']);
            return null;
        }
        
        // Vincular parámetros
        oci_bind_by_name($stid, ':identificacion', $identificacion);
        oci_bind_by_name($stid, ':numero_contrato', $numero_contrato);
        oci_bind_by_name($stid, ':anio_pro', $anio_pro);
        oci_bind_by_name($stid, ':numero_pro', $proyecto_id);
        
        // Ejecutar consulta
        $r = oci_execute($stid);
        if (!$r) {
            $e = oci_error($stid);
            error_log("Error al ejecutar consulta de detalle contrato: " . $e['message']);
            return null;
        }
        
        // Obtener resultado
        $row = oci_fetch_assoc($stid);
        
        // Convertir claves a minúsculas
        if ($row) {
            $contrato = array();
            foreach ($row as $key => $value) {
                $contrato[strtolower($key)] = $value;
            }
        } else {
            $contrato = null;
        }
        
        // Liberar recursos
        oci_free_statement($stid);
        oci_close($conn);
        
        return $contrato;
        
    } catch (Exception $e) {
        error_log("Error en obtenerDetalleContrato: " . $e->getMessage());
        return null;
    }
}

/**
 * Actualiza un contrato existente para un contratista
 * @param array $datos Datos del contrato a actualizar
 * @return bool|string True si se actualizó correctamente, mensaje de error en caso contrario
 */
function actualizarContratoProyecto($datos) {
    try {
        // Verificar que existan los datos mínimos requeridos
        if (empty($datos['identificacion']) || empty($datos['numero_contrato']) || 
            empty($datos['anio_pro']) || empty($datos['numero_pro'])) {
            return "Faltan datos obligatorios para actualizar el contrato";
        }
        
        // Obtener conexión
        $conn = conectarOracle();
        
        // Verificar si existe el contrato
        $sql_check = "SELECT COUNT(*) as existe FROM PROYECTO_OPS 
                      WHERE IDENTIFICACION = :identificacion 
                      AND NUMERO_CONTRATO = :numero_contrato 
                      AND ANIO_PRO = :anio_pro 
                      AND NUMERO_PRO = :numero_pro";
        
        $stid_check = oci_parse($conn, $sql_check);
        oci_bind_by_name($stid_check, ':identificacion', $datos['identificacion']);
        oci_bind_by_name($stid_check, ':numero_contrato', $datos['numero_contrato']);
        oci_bind_by_name($stid_check, ':anio_pro', $datos['anio_pro']);
        oci_bind_by_name($stid_check, ':numero_pro', $datos['numero_pro']);
        oci_execute($stid_check);
        
        $row = oci_fetch_assoc($stid_check);
        if ($row['EXISTE'] == 0) {
            oci_free_statement($stid_check);
            oci_close($conn);
            return "No existe el contrato especificado para este contratista en este proyecto";
        }
        
        // Iniciar transacción
        oci_set_action($conn, 'actualizarContratoProyecto');
        
        // Consulta SQL para actualizar en PROYECTO_OPS
        $sql = "UPDATE PROYECTO_OPS SET
                    CODIGO_ENTIDAD = :codigo_entidad,
                    VIGENCIA_FISCAL = :vigencia_fiscal,
                    TIPO_REGISTRO = :tipo_registro,
                    TIPO_MODIFICACION = :tipo_modificacion,
                    TIPO_CONFIGURACION = :tipo_configuracion,
                    TIPOLOGIA = :tipologia,
                    TIPO_PROCESO = :tipo_proceso,
                    NUMERO_PUBLICACION = :numero_publicacion,
                    FECHA_PUBLICACION = TO_DATE(:fecha_publicacion, 'YYYY-MM-DD'),
                    TIPO_CONTRATO = :tipo_contrato,
                    TIPO_VINCULACION = :tipo_vinculacion,
                    FACULTAD = :facultad,
                    PROYECTO_CURRICULAR = :proyecto_curricular,
                    OBJETO = :objeto,
                    FECHA_SUSCRIPCION = TO_DATE(:fecha_suscripcion, 'YYYY-MM-DD'),
                    FECHA_INICIO = TO_DATE(:fecha_inicio, 'YYYY-MM-DD'),
                    FECHA_TERMINACION = TO_DATE(:fecha_terminacion, 'YYYY-MM-DD'),
                    VALOR = :valor,
                    TIPO_MONEDA = :tipo_moneda,
                    TIPO_TASA = :tipo_tasa,
                    ANIO = :anio,
                    MES = :mes,
                    DIA = :dia,
                    HORA = :hora,
                    SITUACION_CONTRATO = :situacion_contrato,
                    OBSERVACIONES = :observaciones,
                    UNIDAD = :unidad,
                    CDP = :cdp,
                    FECHA_CDP = TO_DATE(:fecha_cdp, 'YYYY-MM-DD'),
                    RP = :rp,
                    FECHA_RP = TO_DATE(:fecha_rp, 'YYYY-MM-DD'),
                    SECOP = :secop
                WHERE 
                    IDENTIFICACION = :identificacion
                    AND NUMERO_CONTRATO = :numero_contrato
                    AND ANIO_PRO = :anio_pro
                    AND NUMERO_PRO = :numero_pro";
        
        // Preparar consulta
        $stid = oci_parse($conn, $sql);
        if (!$stid) {
            $e = oci_error($conn);
            error_log("Error al preparar consulta para actualizar contrato: " . $e['message']);
            return "Error al preparar consulta: " . $e['message'];
        }
        
        // Valores por defecto para campos opcionales
        $codigo_entidad = $datos['codigo_entidad'] ?? 108;
        $vigencia_fiscal = $datos['vigencia_fiscal'] ?? date('Y');
        $tipo_registro = $datos['tipo_registro'] ?? 1;
        $tipo_modificacion = $datos['tipo_modificacion'] ?? 1;
        $tipo_configuracion = $datos['tipo_configuracion'] ?? 1;
        $tipologia = $datos['tipologia'] ?? 1;
        $tipo_proceso = $datos['tipo_proceso'] ?? 3;
        $numero_publicacion = $datos['numero_publicacion'] ?? 0;
        $fecha_publicacion = $datos['fecha_publicacion'] ?? null;
        $tipo_vinculacion = $datos['tipo_vinculacion'] ?? 1;
        $facultad = $datos['facultad'] ?? null;
        $proyecto_curricular = $datos['proyecto_curricular'] ?? null;
        $fecha_suscripcion = $datos['fecha_suscripcion'] ?? $datos['fecha_inicio'];
        $situacion_contrato = $datos['situacion_contrato'] ?? 8;
        $observaciones = $datos['observaciones'] ?? '';
        $cdp = $datos['cdp'] ?? '';
        $fecha_cdp = !empty($datos['fecha_cdp']) ? $datos['fecha_cdp'] : null;
        $rp = $datos['rp'] ?? '';
        $fecha_rp = !empty($datos['fecha_rp']) ? $datos['fecha_rp'] : null;
        $secop = $datos['secop'] ?? '';
        
        // Vincular parámetros
        oci_bind_by_name($stid, ':codigo_entidad', $codigo_entidad);
        oci_bind_by_name($stid, ':vigencia_fiscal', $vigencia_fiscal);
        oci_bind_by_name($stid, ':tipo_registro', $tipo_registro);
        oci_bind_by_name($stid, ':tipo_modificacion', $tipo_modificacion);
        oci_bind_by_name($stid, ':tipo_configuracion', $tipo_configuracion);
        oci_bind_by_name($stid, ':tipologia', $tipologia);
        oci_bind_by_name($stid, ':tipo_proceso', $tipo_proceso);
        oci_bind_by_name($stid, ':numero_publicacion', $numero_publicacion);
        oci_bind_by_name($stid, ':fecha_publicacion', $fecha_publicacion);
        oci_bind_by_name($stid, ':tipo_contrato', $datos['tipo_contrato']);
        oci_bind_by_name($stid, ':tipo_vinculacion', $tipo_vinculacion);
        oci_bind_by_name($stid, ':facultad', $facultad);
        oci_bind_by_name($stid, ':proyecto_curricular', $proyecto_curricular);
        oci_bind_by_name($stid, ':objeto', $datos['objeto']);
        oci_bind_by_name($stid, ':fecha_suscripcion', $fecha_suscripcion);
        oci_bind_by_name($stid, ':fecha_inicio', $datos['fecha_inicio']);
        oci_bind_by_name($stid, ':fecha_terminacion', $datos['fecha_terminacion']);
        oci_bind_by_name($stid, ':valor', $datos['valor']);
        oci_bind_by_name($stid, ':tipo_moneda', $datos['tipo_moneda']);
        oci_bind_by_name($stid, ':tipo_tasa', $datos['tipo_tasa']);
        oci_bind_by_name($stid, ':anio', $datos['anio']);
        oci_bind_by_name($stid, ':mes', $datos['mes']);
        oci_bind_by_name($stid, ':dia', $datos['dia']);
        oci_bind_by_name($stid, ':hora', $datos['hora']);
        oci_bind_by_name($stid, ':situacion_contrato', $situacion_contrato);
        oci_bind_by_name($stid, ':observaciones', $observaciones);
        oci_bind_by_name($stid, ':unidad', $datos['unidad']);
        oci_bind_by_name($stid, ':cdp', $cdp);
        oci_bind_by_name($stid, ':fecha_cdp', $fecha_cdp);
        oci_bind_by_name($stid, ':rp', $rp);
        oci_bind_by_name($stid, ':fecha_rp', $fecha_rp);
        oci_bind_by_name($stid, ':secop', $secop);
        oci_bind_by_name($stid, ':identificacion', $datos['identificacion']);
        oci_bind_by_name($stid, ':numero_contrato', $datos['numero_contrato']);
        oci_bind_by_name($stid, ':anio_pro', $datos['anio_pro']);
        oci_bind_by_name($stid, ':numero_pro', $datos['numero_pro']);
        
        // Ejecutar consulta
        $r = oci_execute($stid, OCI_NO_AUTO_COMMIT);
        if (!$r) {
            $e = oci_error($stid);
            error_log("Error al ejecutar consulta para actualizar contrato: " . $e['message']);
            oci_rollback($conn);
            return "Error al ejecutar la consulta: " . $e['message'];
        }
        
        // Confirmar transacción
        $commit = oci_commit($conn);
        
        // Liberar recursos
        oci_free_statement($stid);
        oci_close($conn);
        
        return $commit ? true : "Error al confirmar la transacción";
        
    } catch (Exception $e) {
        error_log("Error en actualizarContratoProyecto: " . $e->getMessage());
        return "Error general: " . $e->getMessage();
    }
}

/**
 * Elimina un contrato de un contratista
 * @param string $identificacion ID del contratista
 * @param int $numero_contrato Número del contrato
 * @param int $proyecto_id ID del proyecto
 * @return bool|string True si se eliminó correctamente, mensaje de error en caso contrario
 */
function eliminarContratoProyecto($identificacion, $numero_contrato, $proyecto_id) {
    try {
        // Obtener conexión
        $conn = conectarOracle();
        
        // Extraer ANIO_PRO del proyecto
        $sqlProy = "SELECT ANIO_PRO FROM PROYECTO WHERE NUMERO_PRO = :id";
        $stidProy = oci_parse($conn, $sqlProy);
        oci_bind_by_name($stidProy, ':id', $proyecto_id);
        oci_execute($stidProy);
        $rowProy = oci_fetch_assoc($stidProy);
        
        if (!$rowProy) {
            return "No se encontró el proyecto especificado";
        }
        
        $anio_pro = $rowProy['ANIO_PRO'];
        
        // Iniciar transacción
        oci_set_action($conn, 'eliminarContratoProyecto');
        
        // Verificar si existen documentos asociados al contrato
        $sql_docs = "SELECT COUNT(*) as total FROM DOCUMENTO_CONTRATO 
                    WHERE ANIO_PRO = :anio_pro 
                    AND NUMERO_PRO = :numero_pro 
                    AND ANIO_CONTRATO = :anio_pro 
                    AND NUMERO_CONTRATO = :numero_contrato
                    AND CONTRATISTA = :identificacion";
        
        $stid_docs = oci_parse($conn, $sql_docs);
        oci_bind_by_name($stid_docs, ':anio_pro', $anio_pro);
        oci_bind_by_name($stid_docs, ':numero_pro', $proyecto_id);
        oci_bind_by_name($stid_docs, ':numero_contrato', $numero_contrato);
        oci_bind_by_name($stid_docs, ':identificacion', $identificacion);
        oci_execute($stid_docs);
        
        $row_docs = oci_fetch_assoc($stid_docs);
        if ($row_docs['TOTAL'] > 0) {
            // Eliminar documentos asociados
            $sql_del_docs = "DELETE FROM DOCUMENTO_CONTRATO 
                            WHERE ANIO_PRO = :anio_pro 
                            AND NUMERO_PRO = :numero_pro 
                            AND ANIO_CONTRATO = :anio_pro 
                            AND NUMERO_CONTRATO = :numero_contrato
                            AND CONTRATISTA = :identificacion";
            
            $stid_del_docs = oci_parse($conn, $sql_del_docs);
            oci_bind_by_name($stid_del_docs, ':anio_pro', $anio_pro);
            oci_bind_by_name($stid_del_docs, ':numero_pro', $proyecto_id);
            oci_bind_by_name($stid_del_docs, ':numero_contrato', $numero_contrato);
            oci_bind_by_name($stid_del_docs, ':identificacion', $identificacion);
            
            $r_del_docs = oci_execute($stid_del_docs, OCI_NO_AUTO_COMMIT);
            if (!$r_del_docs) {
                $e = oci_error($stid_del_docs);
                error_log("Error al eliminar documentos del contrato: " . $e['message']);
                oci_rollback($conn);
                return "Error al eliminar documentos asociados al contrato: " . $e['message'];
            }
        }
        
        // Verificar si existen actas asociadas al contrato
        $sql_actas = "SELECT COUNT(*) as total FROM ACTA_CONTRATO 
                      WHERE ANIO_PRO = :anio_pro 
                      AND NUMERO_PRO = :numero_pro 
                      AND ANIO_CONTRATO = :anio_pro 
                      AND NUMERO_CONTRATO = :numero_contrato
                      AND CONTRATISTA = :identificacion";
        
        $stid_actas = oci_parse($conn, $sql_actas);
        oci_bind_by_name($stid_actas, ':anio_pro', $anio_pro);
        oci_bind_by_name($stid_actas, ':numero_pro', $proyecto_id);
        oci_bind_by_name($stid_actas, ':numero_contrato', $numero_contrato);
        oci_bind_by_name($stid_actas, ':identificacion', $identificacion);
        oci_execute($stid_actas);
        
        $row_actas = oci_fetch_assoc($stid_actas);
        if ($row_actas['TOTAL'] > 0) {
            // Eliminar actas asociadas
            $sql_del_actas = "DELETE FROM ACTA_CONTRATO 
                             WHERE ANIO_PRO = :anio_pro 
                             AND NUMERO_PRO = :numero_pro 
                             AND ANIO_CONTRATO = :anio_pro 
                             AND NUMERO_CONTRATO = :numero_contrato
                             AND CONTRATISTA = :identificacion";
            
            $stid_del_actas = oci_parse($conn, $sql_del_actas);
            oci_bind_by_name($stid_del_actas, ':anio_pro', $anio_pro);
            oci_bind_by_name($stid_del_actas, ':numero_pro', $proyecto_id);
            oci_bind_by_name($stid_del_actas, ':numero_contrato', $numero_contrato);
            oci_bind_by_name($stid_del_actas, ':identificacion', $identificacion);
            
            $r_del_actas = oci_execute($stid_del_actas, OCI_NO_AUTO_COMMIT);
            if (!$r_del_actas) {
                $e = oci_error($stid_del_actas);
                error_log("Error al eliminar actas del contrato: " . $e['message']);
                oci_rollback($conn);
                return "Error al eliminar actas asociadas al contrato: " . $e['message'];
            }
        }
        
        // Eliminar el contrato
        $sql = "DELETE FROM PROYECTO_OPS 
                WHERE IDENTIFICACION = :identificacion 
                AND NUMERO_CONTRATO = :numero_contrato 
                AND ANIO_PRO = :anio_pro 
                AND NUMERO_PRO = :numero_pro";
        
        $stid = oci_parse($conn, $sql);
        if (!$stid) {
            $e = oci_error($conn);
            error_log("Error al preparar consulta para eliminar contrato: " . $e['message']);
            oci_rollback($conn);
            return "Error al preparar consulta: " . $e['message'];
        }
        
        oci_bind_by_name($stid, ':identificacion', $identificacion);
        oci_bind_by_name($stid, ':numero_contrato', $numero_contrato);
        oci_bind_by_name($stid, ':anio_pro', $anio_pro);
        oci_bind_by_name($stid, ':numero_pro', $proyecto_id);
        
        $r = oci_execute($stid, OCI_NO_AUTO_COMMIT);
        if (!$r) {
            $e = oci_error($stid);
            error_log("Error al ejecutar consulta para eliminar contrato: " . $e['message']);
            oci_rollback($conn);
            return "Error al ejecutar la consulta: " . $e['message'];
        }
        
        // Confirmar transacción
        $commit = oci_commit($conn);
        
        // Liberar recursos
        oci_free_statement($stid);
        oci_close($conn);
        
        return $commit ? true : "Error al confirmar la transacción";
        
    } catch (Exception $e) {
        error_log("Error en eliminarContratoProyecto: " . $e->getMessage());
        return "Error general: " . $e->getMessage();
    }
}

/**
 * Busca contratistas por varios criterios
 * @param array $criterios Criterios de búsqueda (identificacion, nombre, apellido, etc.)
 * @return array Lista de contratistas que coinciden con los criterios
 */
function buscarContratistas($criterios = []) {
    try {
        // Obtener conexión
        $conn = conectarOracle();
        
        // Base de la consulta SQL
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
                    c.CORREO as correo,
                    c.TEL_CELULAR as tel_celular,
                    c.ESTADO as estado
                FROM 
                    CONTRATOS_OPS c
                LEFT JOIN 
                    SIV_TIPO_PERSONA tp ON c.TIPO_PERSONA = tp.CODIGO
                LEFT JOIN 
                    SIV_NACIONALIDAD tn ON c.TIPO_NACIONALIDAD = tn.CODIGO
                WHERE 
                    c.ESTADO = 'A'";
        
        $condiciones = [];
        $parametros = [];
        
        // Agregar condiciones según los criterios proporcionados
        if (!empty($criterios['identificacion'])) {
            $condiciones[] = "c.IDENTIFICACION = :identificacion";
            $parametros[':identificacion'] = $criterios['identificacion'];
        }
        
        if (!empty($criterios['nombre'])) {
            $condiciones[] = "(UPPER(c.NOMBRE1) LIKE UPPER('%' || :nombre || '%') OR UPPER(c.NOMBRE2) LIKE UPPER('%' || :nombre || '%'))";
            $parametros[':nombre'] = $criterios['nombre'];
        }
        
        if (!empty($criterios['apellido'])) {
            $condiciones[] = "(UPPER(c.APELLIDO1) LIKE UPPER('%' || :apellido || '%') OR UPPER(c.APELLIDO2) LIKE UPPER('%' || :apellido || '%'))";
            $parametros[':apellido'] = $criterios['apellido'];
        }
        
        if (!empty($criterios['tipo_persona'])) {
            $condiciones[] = "c.TIPO_PERSONA = :tipo_persona";
            $parametros[':tipo_persona'] = $criterios['tipo_persona'];
        }
        
        if (!empty($criterios['correo'])) {
            $condiciones[] = "UPPER(c.CORREO) LIKE UPPER('%' || :correo || '%')";
            $parametros[':correo'] = $criterios['correo'];
        }
        
        // Si hay condiciones, añadirlas a la consulta
        if (!empty($condiciones)) {
            $sql .= " AND " . implode(" AND ", $condiciones);
        }
        
        // Ordenar los resultados
        $sql .= " ORDER BY c.APELLIDO1, c.APELLIDO2, c.NOMBRE1, c.NOMBRE2";
        
        // Preparar consulta
        $stid = oci_parse($conn, $sql);
        if (!$stid) {
            $e = oci_error($conn);
            error_log("Error al preparar consulta: " . $e['message']);
            return [];
        }
        
        // Vincular parámetros
        foreach ($parametros as $nombre => $valor) {
            oci_bind_by_name($stid, $nombre, $parametros[$nombre]);
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
        error_log("Error en buscarContratistas: " . $e->getMessage());
        return [];
    }
}

/**
 * Registra documento asociado a un contrato
 * @param array $datos Datos del documento
 * @param string $archivo_tmp Ruta temporal del archivo
 * @return bool|string True si se registró correctamente, mensaje de error en caso contrario
 */
function registrarDocumentoContrato($datos, $archivo_tmp) {
    try {
        // Verificar datos requeridos
        if (empty($datos['anio_pro']) || empty($datos['numero_pro']) || 
            empty($datos['numero_contrato']) || empty($datos['tipo_doc']) || 
            empty($datos['fecha_doc']) || empty($datos['contratista'])) {
            return "Faltan datos obligatorios para registrar el documento";
        }
        
        // Obtener conexión
        $conn = conectarOracle();
        
        // Obtener próximo número de documento
        $sql_num = "SELECT NVL(MAX(NUMERO_DOC), 0) + 1 as proximo_numero 
                    FROM DOCUMENTO_CONTRATO 
                    WHERE ANIO_PRO = :anio_pro 
                    AND NUMERO_PRO = :numero_pro 
                    AND ANIO_CONTRATO = :anio_contrato 
                    AND NUMERO_CONTRATO = :numero_contrato";
        
        $stid_num = oci_parse($conn, $sql_num);
        oci_bind_by_name($stid_num, ':anio_pro', $datos['anio_pro']);
        oci_bind_by_name($stid_num, ':numero_pro', $datos['numero_pro']);
        oci_bind_by_name($stid_num, ':anio_contrato', $datos['anio_pro']); // Asumimos mismo año
        oci_bind_by_name($stid_num, ':numero_contrato', $datos['numero_contrato']);
       oci_execute($stid_num);
       $row_num = oci_fetch_assoc($stid_num);
       $numero_doc = $row_num['PROXIMO_NUMERO'];
       
       // Iniciar transacción
       oci_set_action($conn, 'registrarDocumentoContrato');
       
       // Si hay archivo, procesarlo
       $nombre_archivo = '';
       if (!empty($archivo_tmp) && file_exists($archivo_tmp)) {
           // Generar nombre único para el archivo
           $extension = strtolower(pathinfo($_FILES['archivo']['name'], PATHINFO_EXTENSION));
           $nombre_archivo = $datos['anio_pro'] . '_' . $datos['numero_pro'] . '_' . 
                            $datos['numero_contrato'] . '_' . $datos['contratista'] . '_' . 
                            $numero_doc . '.' . $extension;
           
           // Directorio local temporal
           $dir_temp_local = 'uploads/temp/';
           
           // Crear directorio si no existe
           if (!is_dir($dir_temp_local)) {
               mkdir($dir_temp_local, 0755, true);
           }
           
           // Ruta temporal local
           $ruta_local_tmp = $dir_temp_local . $nombre_archivo;
           
           // Mover el archivo a la ubicación temporal
           if (move_uploaded_file($archivo_tmp, $ruta_local_tmp)) {
               // Subir archivo al servidor remoto o guardar localmente
               $resultado_subida = subirArchivoSFTP($ruta_local_tmp, $nombre_archivo);
               
               if ($resultado_subida !== true) {
                   // Plan B: Guardar localmente
                   $dir_local_backup = 'uploads/contratos/';
                   if (!is_dir($dir_local_backup)) {
                       mkdir($dir_local_backup, 0755, true);
                   }
                   
                   $ruta_local_final = $dir_local_backup . $nombre_archivo;
                   if (!copy($ruta_local_tmp, $ruta_local_final)) {
                       oci_rollback($conn);
                       return "Error al guardar el archivo";
                   }
               }
               
               // Eliminar archivo temporal
               if (file_exists($ruta_local_tmp)) {
                   @unlink($ruta_local_tmp);
               }
           } else {
               oci_rollback($conn);
               return "Error al mover el archivo subido";
           }
       }
       
       // Insertar en DOCUMENTO_CONTRATO
       $sql = "INSERT INTO DOCUMENTO_CONTRATO (
                   ANIO_PRO, NUMERO_PRO, NUMERO_CONTRATO, ANIO_CONTRATO,
                   NUMERO_DOC, TIPO_DOC, FECHA_DOC, ARCHIVO,
                   ESTADO, USUARIO, CONTRATISTA
               ) VALUES (
                   :anio_pro, :numero_pro, :numero_contrato, :anio_contrato,
                   :numero_doc, :tipo_doc, TO_DATE(:fecha_doc, 'YYYY-MM-DD'),
                   :archivo, 'A', :usuario, :contratista
               )";
       
       // Preparar consulta
       $stid = oci_parse($conn, $sql);
       if (!$stid) {
           $e = oci_error($conn);
           error_log("Error al preparar consulta: " . $e['message']);
           oci_rollback($conn);
           return "Error al preparar consulta: " . $e['message'];
       }
       
       // Valores para vincular
       $anio_contrato = $datos['anio_pro']; // Asumimos mismo año
       $usuario = $datos['usuario'] ?? 'ADMIN';
       
       // Vincular parámetros
       oci_bind_by_name($stid, ':anio_pro', $datos['anio_pro']);
       oci_bind_by_name($stid, ':numero_pro', $datos['numero_pro']);
       oci_bind_by_name($stid, ':numero_contrato', $datos['numero_contrato']);
       oci_bind_by_name($stid, ':anio_contrato', $anio_contrato);
       oci_bind_by_name($stid, ':numero_doc', $numero_doc);
       oci_bind_by_name($stid, ':tipo_doc', $datos['tipo_doc']);
       oci_bind_by_name($stid, ':fecha_doc', $datos['fecha_doc']);
       oci_bind_by_name($stid, ':archivo', $nombre_archivo);
       oci_bind_by_name($stid, ':usuario', $usuario);
       oci_bind_by_name($stid, ':contratista', $datos['contratista']);
       
       // Ejecutar consulta
       $r = oci_execute($stid, OCI_NO_AUTO_COMMIT);
       if (!$r) {
           $e = oci_error($stid);
           error_log("Error al ejecutar consulta: " . $e['message']);
           oci_rollback($conn);
           return "Error al ejecutar la consulta: " . $e['message'];
       }
       
       // Confirmar transacción
       $commit = oci_commit($conn);
       
       // Liberar recursos
       oci_free_statement($stid);
       oci_close($conn);
       
       return $commit ? true : "Error al confirmar la transacción";
       
   } catch (Exception $e) {
       error_log("Error en registrarDocumentoContrato: " . $e->getMessage());
       return "Error general: " . $e->getMessage();
   }
}

/**
* Obtiene los documentos asociados a un contrato
* @param string $identificacion ID del contratista
* @param int $numero_contrato Número del contrato
* @param int $proyecto_id ID del proyecto
* @return array Lista de documentos del contrato
*/
function obtenerDocumentosContrato($identificacion, $numero_contrato, $proyecto_id) {
   try {
       // Obtener conexión
       $conn = conectarOracle();
       
       // Extraer ANIO_PRO del proyecto
       $sqlProy = "SELECT ANIO_PRO FROM PROYECTO WHERE NUMERO_PRO = :id";
       $stidProy = oci_parse($conn, $sqlProy);
       oci_bind_by_name($stidProy, ':id', $proyecto_id);
       oci_execute($stidProy);
       $rowProy = oci_fetch_assoc($stidProy);
       
       if (!$rowProy) {
           return [];
       }
       
       $anio_pro = $rowProy['ANIO_PRO'];
       
       // Consulta SQL para obtener documentos del contrato
       $sql = "SELECT 
                   dc.ANIO_PRO,
                   dc.NUMERO_PRO,
                   dc.NUMERO_CONTRATO,
                   dc.ANIO_CONTRATO,
                   dc.NUMERO_DOC,
                   dc.TIPO_DOC,
                   td.DESCRIPCION as TIPO_DESCRIPCION,
                   dc.FECHA_DOC,
                   dc.ARCHIVO,
                   dc.ESTADO,
                   dc.CONTRATISTA
               FROM 
                   DOCUMENTO_CONTRATO dc
               LEFT JOIN 
                   TIPO_DOCUMENTO td ON dc.TIPO_DOC = td.CODIGO
               WHERE 
                   dc.ANIO_PRO = :anio_pro
                   AND dc.NUMERO_PRO = :numero_pro
                   AND dc.NUMERO_CONTRATO = :numero_contrato
                   AND dc.ANIO_CONTRATO = :anio_contrato
                   AND dc.CONTRATISTA = :contratista
                   AND dc.ESTADO = 'A'
               ORDER BY 
                   dc.FECHA_DOC DESC, dc.NUMERO_DOC DESC";
       
       // Preparar consulta
       $stid = oci_parse($conn, $sql);
       if (!$stid) {
           $e = oci_error($conn);
           error_log("Error al preparar consulta: " . $e['message']);
           return [];
       }
       
       // Vincular parámetros
       oci_bind_by_name($stid, ':anio_pro', $anio_pro);
       oci_bind_by_name($stid, ':numero_pro', $proyecto_id);
       oci_bind_by_name($stid, ':numero_contrato', $numero_contrato);
       oci_bind_by_name($stid, ':anio_contrato', $anio_pro); // Mismo año
       oci_bind_by_name($stid, ':contratista', $identificacion);
       
       // Ejecutar consulta
       $r = oci_execute($stid);
       if (!$r) {
           $e = oci_error($stid);
           error_log("Error al ejecutar consulta: " . $e['message']);
           return [];
       }
       
       // Procesar resultados
       $documentos = [];
       
       while ($row = oci_fetch_assoc($stid)) {
           // Convertir claves a minúsculas
           $documento = array();
           foreach ($row as $key => $value) {
               $documento[strtolower($key)] = $value;
           }
           $documentos[] = $documento;
       }
       
       // Liberar recursos
       oci_free_statement($stid);
       oci_close($conn);
       
       return $documentos;
       
   } catch (Exception $e) {
       error_log("Error en obtenerDocumentosContrato: " . $e->getMessage());
       return [];
   }
}

/**
* Obtiene los tipos de documentos para contratos
* @return array Lista de tipos de documentos
*/
function obtenerTiposDocumentoContrato() {
   try {
       // Obtener conexión
       $conn = conectarOracle();
       
       // Consulta SQL
       $sql = "SELECT CODIGO as codigo, DESCRIPCION as descripcion
               FROM TIPO_DOCUMENTO
               WHERE ACTIVO = 'S'
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
       error_log("Error en obtenerTiposDocumentoContrato: " . $e->getMessage());
       
       // Datos de ejemplo en caso de error
       return [
           ['codigo' => 1, 'descripcion' => 'Contrato'],
           ['codigo' => 2, 'descripcion' => 'Otro Si'],
           ['codigo' => 3, 'descripcion' => 'Adición'],
           ['codigo' => 4, 'descripcion' => 'Prórroga'],
           ['codigo' => 5, 'descripcion' => 'Acta de Inicio']
       ];
   }
}

/**
* Función auxiliar para subir archivos vía SFTP
* @param string $archivo_local Ruta del archivo local
* @param string $nombre_remoto Nombre del archivo en el servidor remoto
* @return bool|string True si la subida fue exitosa, mensaje de error en caso contrario
*/
function subirArchivoSFTP($archivo_local, $nombre_remoto) {
   // Configuración del servidor SFTP
   $sftp_host = '200.69.103.17';
   $sftp_user = 'oracle';
   $sftp_pass = 'QDameco2016';
   $sftp_port = 22;
   $dir_destino = '/var/www/html/idexud/siexud/contratos/';
   
   try {
       // Verificar que el archivo local existe
       if (!file_exists($archivo_local)) {
           return "El archivo local no existe: $archivo_local";
       }
       
       // Verificar que podemos leer el archivo
       if (!is_readable($archivo_local)) {
           return "No se puede leer el archivo local: $archivo_local";
       }
       
       // Comprobar que existe la extensión SSH2
       if (!function_exists('ssh2_connect')) {
           return "La extensión SSH2 no está disponible. El archivo se guardará localmente.";
       }
       
       // Establecer conexión SSH
       $connection = @ssh2_connect($sftp_host, $sftp_port);
       if (!$connection) {
           return "No se pudo conectar al servidor SSH: $sftp_host:$sftp_port";
       }
       
       // Autenticación
       if (!@ssh2_auth_password($connection, $sftp_user, $sftp_pass)) {
           return "Error de autenticación con el usuario: $sftp_user";
       }
       
       // Inicializar SFTP
       $sftp = @ssh2_sftp($connection);
       if (!$sftp) {
           return "No se pudo inicializar el subsistema SFTP";
       }
       
       // Verificar si el directorio remoto existe y es accesible
       $remoteDir = "ssh2.sftp://{$sftp}{$dir_destino}";
       if (!@file_exists($remoteDir)) {
           return "El directorio remoto no existe o no es accesible: $dir_destino";
       }
       
       // Ruta completa del archivo remoto
       $remote_file = $dir_destino . $nombre_remoto;
       $remote_stream = @fopen("ssh2.sftp://{$sftp}{$remote_file}", 'w');
       
       if (!$remote_stream) {
           return "No se pudo abrir el stream para escritura en el servidor remoto";
       }
       
       // Leer el contenido del archivo local
       $local_content = @file_get_contents($archivo_local);
       if ($local_content === false) {
           return "No se pudo leer el contenido del archivo local";
       }
       
       // Escribir en el servidor remoto
       $bytes_written = @fwrite($remote_stream, $local_content);
       @fclose($remote_stream);
       
       if ($bytes_written === false || $bytes_written === 0) {
           return "Error al escribir datos en el archivo remoto";
       }
       
       // Verificar que el archivo existe en el servidor remoto
       $check_file = @file_exists("ssh2.sftp://{$sftp}{$remote_file}");
       if (!$check_file) {
           return "No se pudo verificar la existencia del archivo en el servidor remoto";
       }
       
       return true;
   } catch (Exception $e) {
       error_log("Error en subirArchivoSFTP: " . $e->getMessage());
       return $e->getMessage();
   }
}

/**
* Obtiene estadísticas de contratistas para dashboard
* @param int $año_filtro Año para filtrar (opcional)
* @return array Estadísticas de contratistas
*/
function obtenerEstadisticasContratistas($año_filtro = null) {
   try {
       // Obtener conexión
       $conn = conectarOracle();
       
       // Condición de año si se proporciona
       $condicion_año = "";
       if (!empty($año_filtro)) {
           $condicion_año = " AND EXTRACT(YEAR FROM po.FECHA_SUSCRIPCION) = :anio";
       }
       
       // Consulta SQL para contratistas activos
       $sql_activos = "SELECT COUNT(DISTINCT c.IDENTIFICACION) as total
                       FROM CONTRATOS_OPS c
                       JOIN PROYECTO_OPS po ON c.IDENTIFICACION = po.IDENTIFICACION
                       WHERE c.ESTADO = 'A' 
                       AND po.ESTADO = 'A'
                       AND po.SITUACION_CONTRATO = 14" . $condicion_año; // 14 = En ejecución
       
       // Preparar consulta
       $stid_activos = oci_parse($conn, $sql_activos);
       if (!$stid_activos) {
           $e = oci_error($conn);
           error_log("Error al preparar consulta de estadísticas: " . $e['message']);
           return [];
       }
       
       // Vincular parámetros si hay filtro de año
       if (!empty($año_filtro)) {
           oci_bind_by_name($stid_activos, ':anio', $año_filtro);
       }
       
       // Ejecutar consulta
       $r_activos = oci_execute($stid_activos);
       if (!$r_activos) {
           $e = oci_error($stid_activos);
           error_log("Error al ejecutar consulta de estadísticas: " . $e['message']);
           return [];
       }
       
       // Obtener resultado
       $row_activos = oci_fetch_assoc($stid_activos);
       $total_activos = $row_activos['TOTAL'];
       
       // Consulta SQL para total de contratistas
       $sql_total = "SELECT COUNT(DISTINCT c.IDENTIFICACION) as total
                   FROM CONTRATOS_OPS c
                   JOIN PROYECTO_OPS po ON c.IDENTIFICACION = po.IDENTIFICACION
                   WHERE c.ESTADO = 'A'" . $condicion_año;
       
       // Preparar consulta
       $stid_total = oci_parse($conn, $sql_total);
       if (!$stid_total) {
           $e = oci_error($conn);
           error_log("Error al preparar consulta de estadísticas: " . $e['message']);
           return [];
       }
       
       // Vincular parámetros si hay filtro de año
       if (!empty($año_filtro)) {
           oci_bind_by_name($stid_total, ':anio', $año_filtro);
       }
       
       // Ejecutar consulta
       $r_total = oci_execute($stid_total);
       if (!$r_total) {
           $e = oci_error($stid_total);
           error_log("Error al ejecutar consulta de estadísticas: " . $e['message']);
           return [];
       }
       
       // Obtener resultado
       $row_total = oci_fetch_assoc($stid_total);
       $total = $row_total['TOTAL'];
       
       // Consulta SQL para valor total de contratos
       $sql_valor = "SELECT SUM(po.VALOR) as total_valor
                   FROM PROYECTO_OPS po
                   WHERE po.ESTADO = 'A'" . $condicion_año;
       
       // Preparar consulta
       $stid_valor = oci_parse($conn, $sql_valor);
       if (!$stid_valor) {
           $e = oci_error($conn);
           error_log("Error al preparar consulta de estadísticas: " . $e['message']);
           return [];
       }
       
       // Vincular parámetros si hay filtro de año
       if (!empty($año_filtro)) {
           oci_bind_by_name($stid_valor, ':anio', $año_filtro);
       }
       
       // Ejecutar consulta
       $r_valor = oci_execute($stid_valor);
       if (!$r_valor) {
           $e = oci_error($stid_valor);
           error_log("Error al ejecutar consulta de estadísticas: " . $e['message']);
           return [];
       }
       
       // Obtener resultado
       $row_valor = oci_fetch_assoc($stid_valor);
       $total_valor = $row_valor['TOTAL_VALOR'];
       
       // Liberar recursos
       oci_free_statement($stid_activos);
       oci_free_statement($stid_total);
       oci_free_statement($stid_valor);
       oci_close($conn);
       
       return [
           'contratistas_activos' => $total_activos,
           'total_contratistas' => $total,
           'valor_total_contratos' => $total_valor
       ];
       
   } catch (Exception $e) {
       error_log("Error en obtenerEstadisticasContratistas: " . $e->getMessage());
       return [
           'contratistas_activos' => 0,
           'total_contratistas' => 0,
           'valor_total_contratos' => 0
       ];
   }
}

