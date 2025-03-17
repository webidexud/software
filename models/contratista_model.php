<?php
/**
 * Funciones del modelo contratista para interactuar con la base de datos
 * Este archivo contiene las funciones necesarias para gestionar contratistas
 */

// Incluir el archivo de conexión
require_once 'config/database.php';

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
 * Obtiene los contratos asociados a un contratista en un proyecto específico
 * @param string $contratistaId ID del contratista
 * @param int $proyectoId ID del proyecto
 * @return array Lista de contratos del contratista
 */
function obtenerContratosContratista($contratistaId, $proyectoId) {
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
        
        // Consulta SQL para obtener contratos del contratista en el proyecto
        // Corregimos la consulta para evitar el error ORA-00904
        $sql = "SELECT 
                    po.ANIO_PRO,
                    po.NUMERO_PRO,
                    po.IDENTIFICACION,
                    po.NUMERO_CONTRATO,
                    po.TIPO_CONTRATO,
                    tc.DESCRIPCION as TIPO_CONTRATO_DESC,
                    po.FECHA_SUSCRIPCION,
                    po.FECHA_INICIO,
                    po.FECHA_TERMINACION,
                    po.VALOR,
                    po.OBJETO,
                    po.SITUACION_CONTRATO,
                    sc.DESCRIPCION as SITUACION_DESC
                FROM 
                    PROYECTO_OPS po
                LEFT JOIN
                    TIPO_CONTRATO tc ON po.TIPO_CONTRATO = tc.CODIGO
                LEFT JOIN
                    SITUACION sc ON po.SITUACION_CONTRATO = sc.CODIGO
                WHERE 
                    po.IDENTIFICACION = :contratista_id
                    AND po.ANIO_PRO = :anio_pro
                    AND po.NUMERO_PRO = :numero_pro
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
        oci_bind_by_name($stid, ':contratista_id', $contratistaId);
        oci_bind_by_name($stid, ':anio_pro', $anio_pro);
        oci_bind_by_name($stid, ':numero_pro', $proyectoId);
        
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
            
            // Buscar si hay documentos asociados al contrato
            $contrato['documento_url'] = obtenerDocumentoContrato($contrato['numero_contrato'], $contratistaId);
            
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
 * Obtiene la URL del documento asociado a un contrato
 * @param int $numeroContrato Número del contrato
 * @param string $contratistaId ID del contratista
 * @return string|null URL del documento o null si no existe
 */
function obtenerDocumentoContrato($numeroContrato, $contratistaId) {
    try {
        // Obtener conexión
        $conn = conectarOracle();
        
        // Consulta SQL para obtener el documento del contrato
        $sql = "SELECT 
                    dc.ARCHIVO
                FROM 
                    DOCUMENTO_CONTRATO dc
                WHERE 
                    dc.NUMERO_CONTRATO = :numero_contrato
                    AND dc.CONTRATISTA = :contratista_id
                    AND dc.ESTADO = 'A'
                    AND ROWNUM = 1";
        
        // Preparar consulta
        $stid = oci_parse($conn, $sql);
        if (!$stid) {
            $e = oci_error($conn);
            error_log("Error al preparar consulta de documento contrato: " . $e['message']);
            return null;
        }
        
        // Vincular parámetros
        oci_bind_by_name($stid, ':numero_contrato', $numeroContrato);
        oci_bind_by_name($stid, ':contratista_id', $contratistaId);
        
        // Ejecutar consulta
        $r = oci_execute($stid);
        if (!$r) {
            $e = oci_error($stid);
            error_log("Error al ejecutar consulta de documento contrato: " . $e['message']);
            return null;
        }
        
        // Obtener resultado
        $row = oci_fetch_assoc($stid);
        
        // Liberar recursos
        oci_free_statement($stid);
        oci_close($conn);
        
        // Devolver URL si hay un archivo
        if ($row && !empty($row['ARCHIVO'])) {
            return "http://siexud.udistrital.edu.co/idexud/siexud/contratos/upload/" . $row['ARCHIVO'];
        }
        
        return null;
        
    } catch (Exception $e) {
        error_log("Error en obtenerDocumentoContrato: " . $e->getMessage());
        return null;
    }
}

/**
 * Actualiza la información de un contratista existente
 * @param array $datos Datos del contratista a actualizar
 * @return bool True si se actualizó correctamente, False en caso de error
 */
function actualizarContratista($datos) {
    try {
        // Obtener conexión
        $conn = conectarOracle();
        
        // Iniciar transacción
        oci_set_action($conn, 'actualizarContratista');
        
        // Preparar la consulta SQL para actualización
        $sql = "UPDATE CONTRATOS_OPS SET 
                    NOMBRE1 = :nombre1,
                    NOMBRE2 = :nombre2,
                    APELLIDO1 = :apellido1,
                    APELLIDO2 = :apellido2,
                    DIRECCION = :direccion,
                    TEL_FIJO = :tel_fijo,
                    TEL_CELULAR = :tel_celular,
                    CORREO = :correo
                WHERE IDENTIFICACION = :identificacion";
        
        // Preparar statement
        $stmt = oci_parse($conn, $sql);
        if (!$stmt) {
            $e = oci_error($conn);
            error_log("Error al preparar consulta de actualización de contratista: " . $e['message']);
            return false;
        }
        
        // Vincular parámetros
        oci_bind_by_name($stmt, ':nombre1', $datos['nombre1']);
        oci_bind_by_name($stmt, ':nombre2', $datos['nombre2']);
        oci_bind_by_name($stmt, ':apellido1', $datos['apellido1']);
        oci_bind_by_name($stmt, ':apellido2', $datos['apellido2']);
        oci_bind_by_name($stmt, ':direccion', $datos['direccion']);
        oci_bind_by_name($stmt, ':tel_fijo', $datos['tel_fijo']);
        oci_bind_by_name($stmt, ':tel_celular', $datos['tel_celular']);
        oci_bind_by_name($stmt, ':correo', $datos['correo']);
        oci_bind_by_name($stmt, ':identificacion', $datos['identificacion']);
        
        // Ejecutar consulta
        $r = oci_execute($stmt, OCI_NO_AUTO_COMMIT);
        if (!$r) {
            $e = oci_error($stmt);
            error_log("Error al ejecutar consulta de actualización de contratista: " . $e['message']);
            oci_rollback($conn);
            return false;
        }
        
        // Confirmar transacción
        oci_commit($conn);
        
        // Liberar recursos
        oci_free_statement($stmt);
        oci_close($conn);
        
        return true;
        
    } catch (Exception $e) {
        error_log("Error en actualizarContratista: " . $e->getMessage());
        return false;
    }
}

/**
 * Crea un nuevo contrato para un contratista en un proyecto
 * @param array $datos Datos del contrato a crear
 * @return bool|int Número del contrato creado o False en caso de error
 */
function crearContratoContratista($datos) {
    try {
        // Obtener conexión
        $conn = conectarOracle();
        
        // Iniciar transacción
        oci_set_action($conn, 'crearContratoContratista');
        
        // Obtener siguiente número de contrato
        $sqlNumero = "SELECT NVL(MAX(NUMERO_CONTRATO), 0) + 1 AS siguiente FROM PROYECTO_OPS 
                      WHERE ANIO_PRO = :anio_pro AND NUMERO_PRO = :numero_pro";
        $stmtNumero = oci_parse($conn, $sqlNumero);
        oci_bind_by_name($stmtNumero, ':anio_pro', $datos['anio_pro']);
        oci_bind_by_name($stmtNumero, ':numero_pro', $datos['numero_pro']);
        oci_execute($stmtNumero);
        $rowNumero = oci_fetch_assoc($stmtNumero);
        $numeroContrato = $rowNumero['SIGUIENTE'];
        
        // Preparar la consulta SQL para inserción
        $sql = "INSERT INTO PROYECTO_OPS (
                    ANIO_PRO, NUMERO_PRO, IDENTIFICACION, NUMERO_CONTRATO,
                    TIPO_CONTRATO, OBJETO, FECHA_SUSCRIPCION, FECHA_INICIO,
                    FECHA_TERMINACION, VALOR, UNIDAD, ESTADO
                ) VALUES (
                    :anio_pro, :numero_pro, :identificacion, :numero_contrato,
                    :tipo_contrato, :objeto, TO_DATE(:fecha_suscripcion, 'YYYY-MM-DD'),
                    TO_DATE(:fecha_inicio, 'YYYY-MM-DD'), TO_DATE(:fecha_terminacion, 'YYYY-MM-DD'),
                    :valor, :unidad, 'A'
                )";
        
        // Preparar statement
        $stmt = oci_parse($conn, $sql);
        if (!$stmt) {
            $e = oci_error($conn);
            error_log("Error al preparar consulta de inserción de contrato: " . $e['message']);
            return false;
        }
        
        // Vincular parámetros
        oci_bind_by_name($stmt, ':anio_pro', $datos['anio_pro']);
        oci_bind_by_name($stmt, ':numero_pro', $datos['numero_pro']);
        oci_bind_by_name($stmt, ':identificacion', $datos['identificacion']);
        oci_bind_by_name($stmt, ':numero_contrato', $numeroContrato);
        oci_bind_by_name($stmt, ':tipo_contrato', $datos['tipo_contrato']);
        oci_bind_by_name($stmt, ':objeto', $datos['objeto']);
        oci_bind_by_name($stmt, ':fecha_suscripcion', $datos['fecha_suscripcion']);
        oci_bind_by_name($stmt, ':fecha_inicio', $datos['fecha_inicio']);
        oci_bind_by_name($stmt, ':fecha_terminacion', $datos['fecha_terminacion']);
        oci_bind_by_name($stmt, ':valor', $datos['valor']);
        oci_bind_by_name($stmt, ':unidad', $datos['unidad']);
        
        // Ejecutar consulta
        $r = oci_execute($stmt, OCI_NO_AUTO_COMMIT);
        if (!$r) {
            $e = oci_error($stmt);
            error_log("Error al ejecutar consulta de inserción de contrato: " . $e['message']);
            oci_rollback($conn);
            return false;
        }
        
        // Si hay un archivo de documento, procesarlo
        if (!empty($datos['archivo_tmp']) && file_exists($datos['archivo_tmp'])) {
            $extension = strtolower(pathinfo($datos['archivo_nombre'], PATHINFO_EXTENSION));
            $nombreArchivo = $datos['anio_pro'] . '_' . $datos['numero_pro'] . '_' . $numeroContrato . '_' . 
                              $datos['identificacion'] . '.' . $extension;
            
            // Directorio local para guardar documentos
            $dir_local = 'uploads/contratos/';
            if (!is_dir($dir_local)) {
                mkdir($dir_local, 0755, true);
            }
            
            $rutaLocal = $dir_local . $nombreArchivo;
            
            // Mover archivo a directorio local
            if (move_uploaded_file($datos['archivo_tmp'], $rutaLocal)) {
                // Insertar registro en DOCUMENTO_CONTRATO
                $sqlDoc = "INSERT INTO DOCUMENTO_CONTRATO (
                              ANIO_PRO, NUMERO_PRO, NUMERO_CONTRATO, ANIO_CONTRATO,
                              NUMERO_DOC, TIPO_DOC, FECHA_DOC, ARCHIVO, ESTADO, 
                              USUARIO, CONTRATISTA
                          ) VALUES (
                              :anio_pro, :numero_pro, :numero_contrato, :anio_contrato,
                              1, 1, TO_DATE(:fecha_doc, 'YYYY-MM-DD'), :archivo, 'A',
                              :usuario, :contratista
                          )";
                
                $stmtDoc = oci_parse($conn, $sqlDoc);
                oci_bind_by_name($stmtDoc, ':anio_pro', $datos['anio_pro']);
                oci_bind_by_name($stmtDoc, ':numero_pro', $datos['numero_pro']);
                oci_bind_by_name($stmtDoc, ':numero_contrato', $numeroContrato);
                oci_bind_by_name($stmtDoc, ':anio_contrato', $datos['anio_pro']);
                oci_bind_by_name($stmtDoc, ':fecha_doc', $datos['fecha_suscripcion']);
                oci_bind_by_name($stmtDoc, ':archivo', $nombreArchivo);
                oci_bind_by_name($stmtDoc, ':usuario', $datos['usuario']);
                oci_bind_by_name($stmtDoc, ':contratista', $datos['identificacion']);
                
                $rDoc = oci_execute($stmtDoc, OCI_NO_AUTO_COMMIT);
                if (!$rDoc) {
                    $e = oci_error($stmtDoc);
                    error_log("Error al insertar documento: " . $e['message']);
                    // No rollback, continuamos con el contrato
                }
                
                oci_free_statement($stmtDoc);
                
                // Intentar subir al servidor remoto (función a implementar según necesidades)
                // subirArchivoContratosFTP($rutaLocal, $nombreArchivo);
            }
        }
        
        // Confirmar transacción
        oci_commit($conn);
        
        // Liberar recursos
        oci_free_statement($stmt);
        if (isset($stmtNumero)) oci_free_statement($stmtNumero);
        oci_close($conn);
        
        return $numeroContrato;
        
    } catch (Exception $e) {
        error_log("Error en crearContratoContratista: " . $e->getMessage());
        return false;
    }
}

/**
 * Obtiene el detalle de un contrato específico
 * @param int $numeroContrato Número del contrato
 * @param string $contratistaId ID del contratista
 * @param int $proyectoId ID del proyecto
 * @return array|null Datos del contrato o null si no existe
 */
function obtenerDetalleContrato($numeroContrato, $contratistaId, $proyectoId) {
    try {
        // Obtener conexión
        $conn = conectarOracle();
        
        // Obtener ANIO_PRO del proyecto
        $sqlProy = "SELECT ANIO_PRO FROM PROYECTO WHERE NUMERO_PRO = :id";
        $stidProy = oci_parse($conn, $sqlProy);
        oci_bind_by_name($stidProy, ':id', $proyectoId);
        oci_execute($stidProy);
        $rowProy = oci_fetch_assoc($stidProy);
        
        if (!$rowProy) {
            return null;
        }
        
        $anio_pro = $rowProy['ANIO_PRO'];
        
        // Consulta SQL para obtener detalle del contrato
        $sql = "SELECT 
                    po.ANIO_PRO,
                    po.NUMERO_PRO,
                    po.IDENTIFICACION,
                    po.NUMERO_CONTRATO,
                    po.TIPO_CONTRATO,
                    tc.DESCRIPCION as TIPO_CONTRATO_DESC,
                    po.OBJETO,
                    po.FECHA_SUSCRIPCION,
                    po.FECHA_INICIO,
                    po.FECHA_TERMINACION,
                    po.VALOR,
                    po.UNIDAD,
                    u.DESCRIPCION as UNIDAD_DESC,
                    po.SITUACION_CONTRATO,
                    sc.DESCRIPCION as SITUACION_DESC,
                    po.ESTADO,
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
                    UNIDAD u ON po.UNIDAD = u.CODIGO
                LEFT JOIN
                    SITUACION sc ON po.SITUACION_CONTRATO = sc.CODIGO
                WHERE 
                    po.NUMERO_CONTRATO = :numero_contrato
                    AND po.IDENTIFICACION = :contratista_id
                    AND po.ANIO_PRO = :anio_pro
                    AND po.NUMERO_PRO = :numero_pro";
        
        // Preparar consulta
        $stid = oci_parse($conn, $sql);
        if (!$stid) {
            $e = oci_error($conn);
            error_log("Error al preparar consulta de detalle contrato: " . $e['message']);
            return null;
        }
        
        // Vincular parámetros
        oci_bind_by_name($stid, ':numero_contrato', $numeroContrato);
        oci_bind_by_name($stid, ':contratista_id', $contratistaId);
        oci_bind_by_name($stid, ':anio_pro', $anio_pro);
        oci_bind_by_name($stid, ':numero_pro', $proyectoId);
        
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
            
            // Buscar documento asociado
            $contrato['documento_url'] = obtenerDocumentoContrato($numeroContrato, $contratistaId);
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
 * Lista de tipos de contrato disponibles
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
        error_log("Error en obtenerTiposContrato: " . $e['message']);
        
        // Datos de ejemplo en caso de error
        return [
            ['codigo' => 1, 'descripcion' => 'Prestación de Servicios'],
            ['codigo' => 2, 'descripcion' => 'Consultoría'],
            ['codigo' => 3, 'descripcion' => 'Obra'],
            ['codigo' => 4, 'descripcion' => 'Suministro']
        ];
    }
}

/**
 * Actualiza la información de un contrato existente
 * @param array $datos Datos del contrato a actualizar
 * @return bool True si se actualizó correctamente, False en caso de error
 */
function actualizarContrato($datos) {
    try {
        // Obtener conexión
        $conn = conectarOracle();
        
        // Iniciar transacción
        oci_set_action($conn, 'actualizarContrato');
        
        // Preparar la consulta SQL para actualización
        $sql = "UPDATE PROYECTO_OPS SET 
                    TIPO_CONTRATO = :tipo_contrato,
                    OBJETO = :objeto,
                    FECHA_SUSCRIPCION = TO_DATE(:fecha_suscripcion, 'YYYY-MM-DD'),
                    FECHA_INICIO = TO_DATE(:fecha_inicio, 'YYYY-MM-DD'),
                    FECHA_TERMINACION = TO_DATE(:fecha_terminacion, 'YYYY-MM-DD'),
                    VALOR = :valor,
                    UNIDAD = :unidad,
                    SITUACION_CONTRATO = :situacion_contrato,
                    CDP = :cdp,
                    FECHA_CDP = TO_DATE(:fecha_cdp, 'YYYY-MM-DD'),
                    RP = :rp,
                    FECHA_RP = TO_DATE(:fecha_rp, 'YYYY-MM-DD'),
                    SECOP = :secop
                WHERE 
                    NUMERO_CONTRATO = :numero_contrato
                    AND IDENTIFICACION = :identificacion
                    AND ANIO_PRO = :anio_pro
                    AND NUMERO_PRO = :numero_pro";
        
        // Preparar statement
        $stmt = oci_parse($conn, $sql);
        if (!$stmt) {
            $e = oci_error($conn);
            error_log("Error al preparar consulta de actualización de contrato: " . $e['message']);
            return false;
        }
        
        // Vincular parámetros
        oci_bind_by_name($stmt, ':tipo_contrato', $datos['tipo_contrato']);
        oci_bind_by_name($stmt, ':objeto', $datos['objeto']);
        oci_bind_by_name($stmt, ':fecha_suscripcion', $datos['fecha_suscripcion']);
        oci_bind_by_name($stmt, ':fecha_inicio', $datos['fecha_inicio']);
        oci_bind_by_name($stmt, ':fecha_terminacion', $datos['fecha_terminacion']);
        oci_bind_by_name($stmt, ':valor', $datos['valor']);
        oci_bind_by_name($stmt, ':unidad', $datos['unidad']);
        oci_bind_by_name($stmt, ':situacion_contrato', $datos['situacion_contrato']);
        oci_bind_by_name($stmt, ':cdp', $datos['cdp']);
        oci_bind_by_name($stmt, ':fecha_cdp', $datos['fecha_cdp']);
        oci_bind_by_name($stmt, ':rp', $datos['rp']);
        oci_bind_by_name($stmt, ':fecha_rp', $datos['fecha_rp']);
        oci_bind_by_name($stmt, ':secop', $datos['secop']);
        oci_bind_by_name($stmt, ':numero_contrato', $datos['numero_contrato']);
        oci_bind_by_name($stmt, ':identificacion', $datos['identificacion']);
        oci_bind_by_name($stmt, ':anio_pro', $datos['anio_pro']);
        oci_bind_by_name($stmt, ':numero_pro', $datos['numero_pro']);
        
        // Ejecutar consulta
        $r = oci_execute($stmt, OCI_NO_AUTO_COMMIT);
        if (!$r) {
            $e = oci_error($stmt);
            error_log("Error al ejecutar consulta de actualización de contrato: " . $e['message']);
            oci_rollback($conn);
            return false;
        }
        
        // Procesar documento si se proporciona
        if (!empty($datos['archivo_tmp']) && file_exists($datos['archivo_tmp'])) {
            $extension = strtolower(pathinfo($datos['archivo_nombre'], PATHINFO_EXTENSION));
            $nombreArchivo = $datos['anio_pro'] . '_' . $datos['numero_pro'] . '_' . $datos['numero_contrato'] . '_' . 
                            $datos['identificacion'] . '.' . $extension;
            
            // Directorio local para guardar documentos
            $dir_local = 'uploads/contratos/';
            if (!is_dir($dir_local)) {
                mkdir($dir_local, 0755, true);
            }
            
            $rutaLocal = $dir_local . $nombreArchivo;
            
            // Mover archivo a directorio local
            if (move_uploaded_file($datos['archivo_tmp'], $rutaLocal)) {
                // Verificar si ya existe un documento
                $sqlCheckDoc = "SELECT COUNT(*) as existe FROM DOCUMENTO_CONTRATO 
                               WHERE NUMERO_CONTRATO = :numero_contrato 
                               AND CONTRATISTA = :contratista_id
                               AND ANIO_PRO = :anio_pro
                               AND NUMERO_PRO = :numero_pro";
                
                $stmtCheckDoc = oci_parse($conn, $sqlCheckDoc);
                oci_bind_by_name($stmtCheckDoc, ':numero_contrato', $datos['numero_contrato']);
                oci_bind_by_name($stmtCheckDoc, ':contratista_id', $datos['identificacion']);
                oci_bind_by_name($stmtCheckDoc, ':anio_pro', $datos['anio_pro']);
                oci_bind_by_name($stmtCheckDoc, ':numero_pro', $datos['numero_pro']);
                oci_execute($stmtCheckDoc);
                
                $rowCheck = oci_fetch_assoc($stmtCheckDoc);
                $existeDoc = $rowCheck['EXISTE'] > 0;
                
                if ($existeDoc) {
                    // Actualizar documento existente
                    $sqlDoc = "UPDATE DOCUMENTO_CONTRATO SET 
                              ARCHIVO = :archivo,
                              FECHA_DOC = TO_DATE(:fecha_doc, 'YYYY-MM-DD'),
                              USUARIO = :usuario
                              WHERE NUMERO_CONTRATO = :numero_contrato 
                              AND CONTRATISTA = :contratista_id
                              AND ANIO_PRO = :anio_pro
                              AND NUMERO_PRO = :numero_pro";
                } else {
                    // Insertar nuevo documento
                    $sqlDoc = "INSERT INTO DOCUMENTO_CONTRATO (
                                ANIO_PRO, NUMERO_PRO, NUMERO_CONTRATO, ANIO_CONTRATO,
                                NUMERO_DOC, TIPO_DOC, FECHA_DOC, ARCHIVO, ESTADO, 
                                USUARIO, CONTRATISTA
                              ) VALUES (
                                :anio_pro, :numero_pro, :numero_contrato, :anio_pro,
                                1, 1, TO_DATE(:fecha_doc, 'YYYY-MM-DD'), :archivo, 'A',
                                :usuario, :contratista_id
                              )";
                }
                
                $stmtDoc = oci_parse($conn, $sqlDoc);
                
                if ($existeDoc) {
                    oci_bind_by_name($stmtDoc, ':archivo', $nombreArchivo);
                    oci_bind_by_name($stmtDoc, ':fecha_doc', $datos['fecha_suscripcion']);
                    oci_bind_by_name($stmtDoc, ':usuario', $datos['usuario']);
                    oci_bind_by_name($stmtDoc, ':numero_contrato', $datos['numero_contrato']);
                    oci_bind_by_name($stmtDoc, ':contratista_id', $datos['identificacion']);
                    oci_bind_by_name($stmtDoc, ':anio_pro', $datos['anio_pro']);
                    oci_bind_by_name($stmtDoc, ':numero_pro', $datos['numero_pro']);
                } else {
                    oci_bind_by_name($stmtDoc, ':anio_pro', $datos['anio_pro']);
                    oci_bind_by_name($stmtDoc, ':numero_pro', $datos['numero_pro']);
                    oci_bind_by_name($stmtDoc, ':numero_contrato', $datos['numero_contrato']);
                    oci_bind_by_name($stmtDoc, ':fecha_doc', $datos['fecha_suscripcion']);
                    oci_bind_by_name($stmtDoc, ':archivo', $nombreArchivo);
                    oci_bind_by_name($stmtDoc, ':usuario', $datos['usuario']);
                    oci_bind_by_name($stmtDoc, ':contratista_id', $datos['identificacion']);
                }
                
                $rDoc = oci_execute($stmtDoc, OCI_NO_AUTO_COMMIT);
                if (!$rDoc) {
                    $e = oci_error($stmtDoc);
                    error_log("Error al actualizar documento: " . $e['message']);
                    // No hacemos rollback, continuamos con la actualización del contrato
                }
                
                oci_free_statement($stmtDoc);
                
                // Intentar subir al servidor remoto (función a implementar según necesidades)
                // subirArchivoContratosFTP($rutaLocal, $nombreArchivo);
            }
        }
        
        // Confirmar transacción
        oci_commit($conn);
        
        // Liberar recursos
        oci_free_statement($stmt);
        oci_close($conn);
        
        return true;
        
    } catch (Exception $e) {
        error_log("Error en actualizarContrato: " . $e->getMessage());
        return false;
    }
}

/**
 * Obtiene la lista de situaciones de contrato disponibles
 * @return array Lista de situaciones
 */
function obtenerSituacionesContrato() {
    try {
        // Obtener conexión
        $conn = conectarOracle();
        
        // Consulta SQL
        $sql = "SELECT CODIGO as codigo, DESCRIPCION as descripcion
                FROM SITUACION
                WHERE CODIGO IN (1, 2, 3, 4, 5, 6)  -- Situaciones típicas para contratos
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
        $situaciones = [];
        
        while ($row = oci_fetch_assoc($stid)) {
            $situaciones[] = [
                'codigo' => $row['CODIGO'],
                'descripcion' => $row['DESCRIPCION']
            ];
        }
        
        // Liberar recursos
        oci_free_statement($stid);
        oci_close($conn);
        
        return $situaciones;
        
    } catch (Exception $e) {
        error_log("Error en obtenerSituacionesContrato: " . $e['message']);
        
        // Datos de ejemplo en caso de error
        return [
            ['codigo' => 1, 'descripcion' => 'Activo'],
            ['codigo' => 2, 'descripcion' => 'Finalizado'],
            ['codigo' => 3, 'descripcion' => 'Suspendido'],
            ['codigo' => 4, 'descripcion' => 'Terminado Anticipadamente'],
            ['codigo' => 5, 'descripcion' => 'Liquidado'],
            ['codigo' => 6, 'descripcion' => 'Anulado']
        ];
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