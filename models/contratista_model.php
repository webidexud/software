<?php
/**
 * Modelo para gestión de contratistas y sus actas
 */

// Incluir el archivo de conexión si aún no está incluido
if (!function_exists('conectarOracle')) {
    require_once 'config/database.php';
}

/**
 * Obtiene los datos completos de un contratista por su identificación
 * @param string $identificacion Número de identificación del contratista
 * @return array|null Datos del contratista o null si no existe
 */
function obtenerContratistaPorId($identificacion) {
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
                    c.IDENTIFICACION = :identificacion
                    AND c.ESTADO = 'A'";
        
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
        error_log("Error en obtenerContratistaPorId: " . $e->getMessage());
        return null;
    }
}

/**
 * Obtiene los contratos de un contratista en un proyecto específico
 * @param string $identificacion Número de identificación del contratista
 * @param int $numeroProyecto Número del proyecto
 * @return array Lista de contratos
 */
function obtenerContratosContratista($identificacion, $numeroProyecto) {
    try {
        // Obtener conexión
        $conn = conectarOracle();
        
        // Consulta SQL para obtener contratos del contratista en el proyecto
        $sql = "SELECT 
                    po.ANIO_PRO,
                    po.NUMERO_PRO,
                    po.IDENTIFICACION,
                    po.NUMERO_CONTRATO,
                    po.ANIO as ANIO_CONTRATO,
                    po.OBJETO,
                    po.FECHA_SUSCRIPCION,
                    po.FECHA_INICIO,
                    po.FECHA_TERMINACION as FECHA_FIN,
                    po.VALOR,
                    po.TIPO_CONTRATO,
                    tc.DESCRIPCION as TIPO_CONTRATO_DESC,
                    po.SITUACION_CONTRATO,
                    s.DESCRIPCION as SITUACION_DESC,
                    po.CDP,
                    po.FECHA_CDP,
                    po.RP,
                    po.FECHA_RP,
                    po.SECOP
                FROM 
                    PROYECTO_OPS po
                LEFT JOIN
                    TIPO_CONTRATO tc ON po.TIPO_CONTRATO = tc.CODIGO
                LEFT JOIN
                    SITUACION s ON po.SITUACION_CONTRATO = s.CODIGO
                WHERE 
                    po.NUMERO_PRO = :numero_pro
                    AND po.IDENTIFICACION = :identificacion
                    AND po.ESTADO = 'A'
                ORDER BY 
                    po.FECHA_INICIO DESC";
        
        // Preparar consulta
        $stid = oci_parse($conn, $sql);
        if (!$stid) {
            $e = oci_error($conn);
            error_log("Error al preparar consulta de contratos: " . $e['message']);
            return [];
        }
        
        // Vincular parámetros
        oci_bind_by_name($stid, ':numero_pro', $numeroProyecto);
        oci_bind_by_name($stid, ':identificacion', $identificacion);
        
        // Ejecutar consulta
        $r = oci_execute($stid);
        if (!$r) {
            $e = oci_error($stid);
            error_log("Error al ejecutar consulta de contratos: " . $e['message']);
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
 * Obtiene las actas asociadas a un contratista en un proyecto específico
 * @param string $identificacion Número de identificación del contratista
 * @param int $numeroProyecto Número del proyecto
 * @param int $numeroContrato Número del contrato (opcional)
 * @return array Lista de actas
 */
function obtenerActasContratista($identificacion, $numeroProyecto, $numeroContrato = null) {
    try {
        // Obtener conexión
        $conn = conectarOracle();
        
        // Consulta SQL para obtener actas del contratista en el proyecto
        $sql = "SELECT 
                    ac.ANIO_PRO,
                    ac.NUMERO_PRO,
                    ac.ANIO_CONTRATO,
                    ac.NUMERO_CONTRATO,
                    ac.NUMERO_ACTA,
                    ac.TIPO_ACTA,
                    ta.DESCRIPCION as TIPO_ACTA_DESC,
                    ac.FECHA_ACTA,
                    ac.OBSERVA,
                    ac.ESTADO,
                    ac.NUMERO_OP,
                    ac.VALOR_OP
                FROM 
                    ACTA_CONTRATO ac
                LEFT JOIN
                    TIPO_ACTA ta ON ac.TIPO_ACTA = ta.CODIGO
                WHERE 
                    ac.NUMERO_PRO = :numero_pro
                    AND ac.CONTRATISTA = :identificacion
                    AND ac.ESTADO = 'A'";
        
        // Añadir filtro por número de contrato si se proporciona
        if ($numeroContrato !== null) {
            $sql .= " AND ac.NUMERO_CONTRATO = :numero_contrato";
        }
        
        $sql .= " ORDER BY ac.FECHA_ACTA DESC, ac.NUMERO_ACTA DESC";
        
        // Preparar consulta
        $stid = oci_parse($conn, $sql);
        if (!$stid) {
            $e = oci_error($conn);
            error_log("Error al preparar consulta de actas: " . $e['message']);
            return [];
        }
        
        // Vincular parámetros
        oci_bind_by_name($stid, ':numero_pro', $numeroProyecto);
        oci_bind_by_name($stid, ':identificacion', $identificacion);
        
        if ($numeroContrato !== null) {
            oci_bind_by_name($stid, ':numero_contrato', $numeroContrato);
        }
        
        // Ejecutar consulta
        $r = oci_execute($stid);
        if (!$r) {
            $e = oci_error($stid);
            error_log("Error al ejecutar consulta de actas: " . $e['message']);
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
        oci_close($conn);
        
        return $actas;
        
    } catch (Exception $e) {
        error_log("Error en obtenerActasContratista: " . $e->getMessage());
        return [];
    }
}

/**
 * Obtiene los documentos asociados a un contratista en un proyecto específico
 * @param string $identificacion Número de identificación del contratista
 * @param int $numeroProyecto Número del proyecto
 * @param int $numeroContrato Número del contrato (opcional)
 * @return array Lista de documentos
 */
function obtenerDocumentosContratista($identificacion, $numeroProyecto, $numeroContrato = null) {
    try {
        // Obtener conexión
        $conn = conectarOracle();
        
        // Consulta SQL para obtener documentos del contratista en el proyecto
        $sql = "SELECT 
                    dc.ANIO_PRO,
                    dc.NUMERO_PRO,
                    dc.ANIO_CONTRATO,
                    dc.NUMERO_CONTRATO,
                    dc.NUMERO_DOC,
                    dc.TIPO_DOC,
                    td.DESCRIPCION as TIPO_DOC_DESC,
                    dc.FECHA_DOC,
                    dc.ARCHIVO,
                    dc.ESTADO
                FROM 
                    DOCUMENTO_CONTRATO dc
                LEFT JOIN
                    TIPO_DOCUMENTO td ON dc.TIPO_DOC = td.CODIGO
                WHERE 
                    dc.NUMERO_PRO = :numero_pro
                    AND dc.CONTRATISTA = :identificacion
                    AND dc.ESTADO = 'A'";
        
        // Añadir filtro por número de contrato si se proporciona
        if ($numeroContrato !== null) {
            $sql .= " AND dc.NUMERO_CONTRATO = :numero_contrato";
        }
        
        $sql .= " ORDER BY dc.FECHA_DOC DESC, dc.NUMERO_DOC DESC";
        
        // Preparar consulta
        $stid = oci_parse($conn, $sql);
        if (!$stid) {
            $e = oci_error($conn);
            error_log("Error al preparar consulta de documentos: " . $e['message']);
            return [];
        }
        
        // Vincular parámetros
        oci_bind_by_name($stid, ':numero_pro', $numeroProyecto);
        oci_bind_by_name($stid, ':identificacion', $identificacion);
        
        if ($numeroContrato !== null) {
            oci_bind_by_name($stid, ':numero_contrato', $numeroContrato);
        }
        
        // Ejecutar consulta
        $r = oci_execute($stid);
        if (!$r) {
            $e = oci_error($stid);
            error_log("Error al ejecutar consulta de documentos: " . $e['message']);
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
        error_log("Error en obtenerDocumentosContratista: " . $e->getMessage());
        return [];
    }
}

/**
 * Obtiene el detalle de un proyecto
 * @param int $numeroProyecto Número del proyecto
 * @return array|null Datos del proyecto o null si no existe
 */
function obtenerProyectoResumido($numeroProyecto) {
    try {
        // Obtener conexión
        $conn = conectarOracle();
        
        // Consulta SQL para obtener datos básicos del proyecto
        $sql = "SELECT 
                    p.ANIO_PRO,
                    p.NUMERO_PRO,
                    p.NOMBRE,
                    p.VALOR,
                    (SELECT es.NUMERO_PRO_ENTIDAD FROM ENTE_SUSCRIPTOR es WHERE es.NUMERO_PRO = p.NUMERO_PRO AND ROWNUM = 1) as NUMERO_PRO_ENTIDAD,
                    (SELECT e.DESCRIPCION FROM ENTIDAD e JOIN ENTE_SUSCRIPTOR es ON e.CODIGO = es.ENTIDAD WHERE es.NUMERO_PRO = p.NUMERO_PRO AND ROWNUM = 1) as ENTIDAD
                FROM 
                    PROYECTO p
                WHERE 
                    p.NUMERO_PRO = :numero_pro
                    AND p.ESTADO = 'A'";
        
        // Preparar consulta
        $stid = oci_parse($conn, $sql);
        if (!$stid) {
            $e = oci_error($conn);
            error_log("Error al preparar consulta de proyecto: " . $e['message']);
            return null;
        }
        
        // Vincular parámetros
        oci_bind_by_name($stid, ':numero_pro', $numeroProyecto);
        
        // Ejecutar consulta
        $r = oci_execute($stid);
        if (!$r) {
            $e = oci_error($stid);
            error_log("Error al ejecutar consulta de proyecto: " . $e['message']);
            return null;
        }
        
        // Obtener resultado
        $row = oci_fetch_assoc($stid);
        
        // Convertir claves a minúsculas
        if ($row) {
            $proyecto = array();
            foreach ($row as $key => $value) {
                $proyecto[strtolower($key)] = $value;
            }
        } else {
            $proyecto = null;
        }
        
        // Liberar recursos
        oci_free_statement($stid);
        oci_close($conn);
        
        return $proyecto;
        
    } catch (Exception $e) {
        error_log("Error en obtenerProyectoResumido: " . $e->getMessage());
        return null;
    }
}


?>