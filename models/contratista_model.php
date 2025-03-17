<?php
// Incluir el archivo de conexión si no está incluido
if (!function_exists('conectarOracle')) {
    require_once 'config/database.php';
}

// Incluir el modelo de proyecto si es necesario para obtener información básica
if (!function_exists('obtenerProyectoDetalle')) {
    require_once 'models/proyecto_model.php';
}

/**
 * Obtiene información detallada de un contratista por su ID
 * @param string $id Identificación del contratista
 * @return array|null Datos del contratista o null si no existe
 */
function obtenerContratistaPorId($id) {
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
                    c.IDENTIFICACION = :id
                    AND c.ESTADO = 'A'";
        
        // Preparar consulta
        $stid = oci_parse($conn, $sql);
        if (!$stid) {
            $e = oci_error($conn);
            error_log("Error al preparar consulta: " . $e['message']);
            return null;
        }
        
        // Vincular parámetros
        oci_bind_by_name($stid, ':id', $id);
        
        // Ejecutar consulta
        $r = oci_execute($stid);
        if (!$r) {
            $e = oci_error($stid);
            error_log("Error al ejecutar consulta: " . $e['message']);
            return null;
        }
        
        // Obtener resultado
        $row = oci_fetch_assoc($stid);
        
        // Si no hay resultados, retornar null
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
        
        // Agregar el nombre completo formateado
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
 * Obtiene información resumida de un proyecto por su ID
 * @param int $proyecto_id ID del proyecto
 * @return array|null Datos resumidos del proyecto o null si no existe
 */
function obtenerProyectoResumido($proyecto_id) {
    try {
        // Si existe la función obtenerProyectoDetalle, usarla
        if (function_exists('obtenerProyectoDetalle')) {
            $proyecto = obtenerProyectoDetalle($proyecto_id);
            
            // Si obtuvimos el proyecto completo, devolver solo los campos necesarios
            if ($proyecto) {
                return [
                    'numero_pro' => $proyecto['numero_pro'],
                    'anio_pro' => $proyecto['anio_pro'],
                    'numero_pro_entidad' => $proyecto['numero_pro_entidad'],
                    'nombre' => $proyecto['nombre'],
                    'entidad' => $proyecto['entidad'],
                    'valor' => $proyecto['valor']
                ];
            }
            
            return null;
        }
        
        // Si no existe la función, hacer una consulta directa
        $conn = conectarOracle();
        
        $sql = "SELECT 
                    p.NUMERO_PRO,
                    p.ANIO_PRO,
                    p.NOMBRE,
                    p.VALOR,
                    en.NUMERO_PRO_ENTIDAD,
                    e.DESCRIPCION as ENTIDAD
                FROM 
                    PROYECTO p
                LEFT JOIN 
                    ENTE_SUSCRIPTOR en ON p.NUMERO_PRO = en.NUMERO_PRO AND p.ANIO_PRO = en.ANIO_PRO
                LEFT JOIN 
                    ENTIDAD e ON en.ENTIDAD = e.CODIGO
                WHERE 
                    p.NUMERO_PRO = :id
                    AND ROWNUM = 1";
        
        $stid = oci_parse($conn, $sql);
        oci_bind_by_name($stid, ':id', $proyecto_id);
        $r = oci_execute($stid);
        
        if (!$r) {
            $e = oci_error($stid);
            error_log("Error al ejecutar consulta: " . $e['message']);
            return null;
        }
        
        $row = oci_fetch_assoc($stid);
        
        // Convertir claves a minúsculas
        $proyecto = array();
        if ($row) {
            foreach ($row as $key => $value) {
                $proyecto[strtolower($key)] = $value;
            }
        }
        
        oci_free_statement($stid);
        oci_close($conn);
        
        return !empty($proyecto) ? $proyecto : null;
        
    } catch (Exception $e) {
        error_log("Error en obtenerProyectoResumido: " . $e->getMessage());
        return null;
    }
}

/**
 * Obtiene los contratos de un contratista en un proyecto específico
 * @param string $contratista_id ID del contratista
 * @param int $proyecto_id ID del proyecto
 * @return array Lista de contratos del contratista en el proyecto
 */
function obtenerContratosContratista($contratista_id, $proyecto_id) {
    try {
        // Obtener conexión
        $conn = conectarOracle();
        
        // Obtener ANIO_PRO del proyecto
        $sqlAnio = "SELECT ANIO_PRO FROM PROYECTO WHERE NUMERO_PRO = :id";
        $stmtAnio = oci_parse($conn, $sqlAnio);
        oci_bind_by_name($stmtAnio, ':id', $proyecto_id);
        oci_execute($stmtAnio);
        $rowAnio = oci_fetch_assoc($stmtAnio);
        
        if (!$rowAnio) {
            return [];
        }
        
        $anio_pro = $rowAnio['ANIO_PRO'];
        
        // Consulta SQL para obtener los contratos del contratista en el proyecto
        $sql = "SELECT 
                    po.ANIO_PRO,
                    po.NUMERO_PRO,
                    po.IDENTIFICACION,
                    po.NUMERO_CONTRATO,
                    po.ANIO_CONTRATO,
                    po.VIGENCIA_FISCAL,
                    po.TIPO_CONTRATO,
                    tc.DESCRIPCION as TIPO_CONTRATO_DESC,
                    po.OBJETO,
                    po.FECHA_INICIO,
                    po.FECHA_TERMINACION as FECHA_FIN,
                    po.VALOR,
                    po.SITUACION_CONTRATO,
                    sc.DESCRIPCION as SITUACION_DESC
                FROM 
                    PROYECTO_OPS po
                LEFT JOIN
                    TIPO_CONTRATO tc ON po.TIPO_CONTRATO = tc.CODIGO
                LEFT JOIN
                    SITUACION sc ON po.SITUACION_CONTRATO = sc.CODIGO
                WHERE 
                    po.ANIO_PRO = :anio_pro
                    AND po.NUMERO_PRO = :numero_pro
                    AND po.IDENTIFICACION = :identificacion
                    AND po.ESTADO = 'A'
                ORDER BY 
                    po.NUMERO_CONTRATO";
        
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
        oci_bind_by_name($stid, ':identificacion', $contratista_id);
        
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
 * Obtiene las actas asociadas a un contratista en un proyecto y contrato específicos
 * @param string $contratista_id ID del contratista
 * @param int $proyecto_id ID del proyecto
 * @param int $numero_contrato Número del contrato (opcional)
 * @return array Lista de actas
 */
function obtenerActasContratista($contratista_id, $proyecto_id, $numero_contrato = null) {
    try {
        // Obtener conexión
        $conn = conectarOracle();
        
        // Obtener ANIO_PRO del proyecto
        $sqlAnio = "SELECT ANIO_PRO FROM PROYECTO WHERE NUMERO_PRO = :id";
        $stmtAnio = oci_parse($conn, $sqlAnio);
        oci_bind_by_name($stmtAnio, ':id', $proyecto_id);
        oci_execute($stmtAnio);
        $rowAnio = oci_fetch_assoc($stmtAnio);
        
        if (!$rowAnio) {
            return [];
        }
        
        $anio_pro = $rowAnio['ANIO_PRO'];
        
        // Consulta base para obtener actas
        $sql = "SELECT 
                    a.ANIO_PRO,
                    a.NUMERO_PRO,
                    a.NUMERO_COM,
                    a.RUBRO,
                    a.ITEM,
                    a.ANIO_CONTRATO,
                    a.NUMERO_CONTRATO,
                    a.NUMERO_ACTA,
                    a.TIPO_ACTA,
                    ta.DESCRIPCION as TIPO_ACTA_DESC,
                    a.FECHA_ACTA,
                    a.OBSERVA,
                    a.ESTADO,
                    a.CONTRATISTA,
                    a.NUMERO_OP,
                    a.VALOR_OP
                FROM 
                    ACTA_CONTRATO a
                LEFT JOIN
                    TIPO_ACTA ta ON a.TIPO_ACTA = ta.CODIGO
                WHERE 
                    a.ANIO_PRO = :anio_pro
                    AND a.NUMERO_PRO = :numero_pro
                    AND a.CONTRATISTA = :contratista";
        
        // Agregar filtro por número de contrato si se proporciona
        if ($numero_contrato !== null) {
            $sql .= " AND a.NUMERO_CONTRATO = :numero_contrato";
        }
        
        $sql .= " AND a.ESTADO = 'A'
                ORDER BY a.FECHA_ACTA DESC, a.NUMERO_ACTA DESC";
        
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
        oci_bind_by_name($stid, ':contratista', $contratista_id);
        
        if ($numero_contrato !== null) {
            oci_bind_by_name($stid, ':numero_contrato', $numero_contrato);
        }
        
        // Ejecutar consulta
        $r = oci_execute($stid);
        if (!$r) {
            $e = oci_error($stid);
            error_log("Error al ejecutar consulta: " . $e['message']);
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
 * Obtiene los documentos asociados a un contratista en un proyecto y contrato específicos
 * @param string $contratista_id ID del contratista
 * @param int $proyecto_id ID del proyecto
 * @param int $numero_contrato Número del contrato (opcional)
 * @return array Lista de documentos
 */
function obtenerDocumentosContratista($contratista_id, $proyecto_id, $numero_contrato = null) {
    try {
        // Obtener conexión
        $conn = conectarOracle();
        
        // Obtener ANIO_PRO del proyecto
        $sqlAnio = "SELECT ANIO_PRO FROM PROYECTO WHERE NUMERO_PRO = :id";
        $stmtAnio = oci_parse($conn, $sqlAnio);
        oci_bind_by_name($stmtAnio, ':id', $proyecto_id);
        oci_execute($stmtAnio);
        $rowAnio = oci_fetch_assoc($stmtAnio);
        
        if (!$rowAnio) {
            return [];
        }
        
        $anio_pro = $rowAnio['ANIO_PRO'];
        
        // Consulta base para obtener documentos
        $sql = "SELECT 
                    d.ANIO_PRO,
                    d.NUMERO_PRO,
                    d.NUMERO_CONTRATO,
                    d.ANIO_CONTRATO,
                    d.NUMERO_DOC,
                    d.TIPO_DOC,
                    td.DESCRIPCION as TIPO_DOC_DESC,
                    d.FECHA_DOC,
                    d.ARCHIVO,
                    d.ESTADO,
                    d.CONTRATISTA
                FROM 
                    DOCUMENTO_CONTRATO d
                LEFT JOIN
                    TIPO_DOCUMENTO td ON d.TIPO_DOC = td.CODIGO
                WHERE 
                    d.ANIO_PRO = :anio_pro
                    AND d.NUMERO_PRO = :numero_pro
                    AND d.CONTRATISTA = :contratista";
        
        // Agregar filtro por número de contrato si se proporciona
        if ($numero_contrato !== null) {
            $sql .= " AND d.NUMERO_CONTRATO = :numero_contrato";
        }
        
        $sql .= " AND d.ESTADO = 'A'
                ORDER BY d.FECHA_DOC DESC, d.NUMERO_DOC DESC";
        
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
        oci_bind_by_name($stid, ':contratista', $contratista_id);
        
        if ($numero_contrato !== null) {
            oci_bind_by_name($stid, ':numero_contrato', $numero_contrato);
        }
        
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
        error_log("Error en obtenerDocumentosContratista: " . $e->getMessage());
        return [];
    }
}
?>