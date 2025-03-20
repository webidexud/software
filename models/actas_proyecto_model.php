<?php
/**
 * Funciones para gestión de actas de proyecto
 */

// Incluir el archivo de conexión si no está incluido
if (!function_exists('conectarOracle')) {
    require_once 'config/database.php';
}

// Incluir el modelo de proyecto si es necesario
if (!function_exists('obtenerSiguienteNumeroDocumento')) {
    require_once 'models/proyecto_model.php';
}

/**
 * Obtiene todos los tipos de acta disponibles
 * @return array Lista de tipos de acta
 */
if (!function_exists('obtenerTiposActa')) {
    function obtenerTiposActa() {
        try {
            // Obtener conexión
            $conn = conectarOracle();
            
            // Consulta SQL
            $sql = "SELECT CODIGO as codigo, DESCRIPCION as descripcion
                    FROM TIPO_ACTA
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
            error_log("Error en obtenerTiposActa: " . $e->getMessage());
            
            // Datos de ejemplo en caso de error
            return [
                ['codigo' => 1, 'descripcion' => 'Acta de Inicio'],
                ['codigo' => 2, 'descripcion' => 'Acta de Seguimiento'],
                ['codigo' => 3, 'descripcion' => 'Acta de Cierre'],
                ['codigo' => 4, 'descripcion' => 'Acta de Suspensión'],
                ['codigo' => 5, 'descripcion' => 'Acta de Reinicio']
            ];
        }
    }
}

/**
 * Crea una nueva acta de proyecto y sube el documento asociado al servidor remoto
 * @param array $datos Datos del acta
 * @param string $archivo_tmp Ruta temporal del archivo
 * @return int|array ID del acta creada o array con error
 */
if (!function_exists('crearActaProyecto')) {
    function crearActaProyecto($datos, $archivo_tmp) {
        try {
            // Validar datos obligatorios
            if (empty($datos['anio_pro']) || empty($datos['numero_pro']) || 
                empty($datos['tipo_acta']) || empty($datos['fecha_acta'])) {
                return ['error' => 'Faltan datos obligatorios para crear el acta'];
            }
            
            // Obtener conexión
            $conn = conectarOracle();
            
            // Obtener el siguiente número de acta para este proyecto
            $numero_acta = obtenerSiguienteNumeroActa($datos['anio_pro'], $datos['numero_pro']);
            
            // Iniciar transacción
            oci_set_action($conn, 'crearActaProyecto');
            
            // 1. Insertar en la tabla ACTA_PROYECTO
            $sql = "INSERT INTO ACTA_PROYECTO (
                        ANIO_PRO, NUMERO_PRO, NUMERO_ACTA, TIPO_ACTA,
                        FECHA_ACTA, OBSERVA, ESTADO, USUARIO
                    ) VALUES (
                        :anio_pro, :numero_pro, :numero_acta, :tipo_acta,
                        TO_DATE(:fecha_acta, 'YYYY-MM-DD'), :observa, 'A', :usuario
                    )";
            
            $stmt = oci_parse($conn, $sql);
            if (!$stmt) {
                $e = oci_error($conn);
                error_log("Error al preparar inserción en ACTA_PROYECTO: " . $e['message']);
                return ['error' => $e['message']];
            }
            
            // Vincular parámetros
            oci_bind_by_name($stmt, ':anio_pro', $datos['anio_pro']);
            oci_bind_by_name($stmt, ':numero_pro', $datos['numero_pro']);
            oci_bind_by_name($stmt, ':numero_acta', $numero_acta);
            oci_bind_by_name($stmt, ':tipo_acta', $datos['tipo_acta']);
            oci_bind_by_name($stmt, ':fecha_acta', $datos['fecha_acta']);
            oci_bind_by_name($stmt, ':observa', $datos['observa']);
            oci_bind_by_name($stmt, ':usuario', $datos['usuario']);
            
            // Ejecutar la inserción
            $r = oci_execute($stmt, OCI_NO_AUTO_COMMIT);
            if (!$r) {
                $e = oci_error($stmt);
                error_log("Error al insertar en ACTA_PROYECTO: " . $e['message']);
                oci_rollback($conn);
                return ['error' => $e['message']];
            }
            
            // 2. Gestionar el documento
            if (!empty($archivo_tmp) && file_exists($archivo_tmp)) {
                // Obtener tipo de acta
                $tipo_acta_info = obtenerTipoActaPorCodigo($datos['tipo_acta']);
                $tipo_acta_desc = $tipo_acta_info ? strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $tipo_acta_info['descripcion'])) : 'acta';
                
                // Generar nombre único para el archivo
                $extension = strtolower(pathinfo($_FILES['archivo']['name'], PATHINFO_EXTENSION));
                $nombre_archivo = $datos['anio_pro'] . '_' . $datos['numero_pro'] . '_' . $tipo_acta_desc . '_' . $numero_acta . '.' . $extension;
                
                // Directorio local temporal
                $dir_temp_local = 'uploads/temp/';
                
                // Crear directorio local temporal si no existe
                if (!is_dir($dir_temp_local)) {
                    mkdir($dir_temp_local, 0755, true);
                }
                
                // Ruta temporal local
                $ruta_local_tmp = $dir_temp_local . $nombre_archivo;
                
                // Mover el archivo a la ubicación temporal
                if (move_uploaded_file($archivo_tmp, $ruta_local_tmp)) {
                    // Subir archivo al servidor remoto
                    $resultado_subida = subirArchivoSFTP($ruta_local_tmp, $nombre_archivo);
                    
                    if ($resultado_subida === true) {
                        // La subida fue exitosa, ahora insertar en DOCUMENTO_PROYECTO
                        $sql_doc = "INSERT INTO DOCUMENTO_PROYECTO (
                                    ANIO_PRO, NUMERO_PRO, NUMERO_DOC, TIPO_DOC,
                                    FECHA_DOC, ARCHIVO, ESTADO, USUARIO
                                ) VALUES (
                                    :anio_pro, :numero_pro, :numero_doc, :tipo_doc,
                                    TO_DATE(:fecha_doc, 'YYYY-MM-DD'), :archivo, 'A', :usuario
                                )";
                        
                        $stmt_doc = oci_parse($conn, $sql_doc);
                        
                        // Usar el mismo número y tipo para documento y acta
                        oci_bind_by_name($stmt_doc, ':anio_pro', $datos['anio_pro']);
                        oci_bind_by_name($stmt_doc, ':numero_pro', $datos['numero_pro']);
                        oci_bind_by_name($stmt_doc, ':numero_doc', $numero_acta);
                        oci_bind_by_name($stmt_doc, ':tipo_doc', $datos['tipo_acta']);
                        oci_bind_by_name($stmt_doc, ':fecha_doc', $datos['fecha_acta']);
                        oci_bind_by_name($stmt_doc, ':archivo', $nombre_archivo);
                        oci_bind_by_name($stmt_doc, ':usuario', $datos['usuario']);
                        
                        $r_doc = oci_execute($stmt_doc, OCI_NO_AUTO_COMMIT);
                        if (!$r_doc) {
                            $e = oci_error($stmt_doc);
                            error_log("Error al insertar en DOCUMENTO_PROYECTO: " . $e['message']);
                            oci_rollback($conn);
                            return ['error' => "Error al insertar en DOCUMENTO_PROYECTO: " . $e['message']];
                        }
                        
                        oci_free_statement($stmt_doc);
                    } else {
                        // La subida falló pero continuamos para al menos registrar el acta
                        error_log("Error al subir archivo al servidor remoto: " . $resultado_subida);
                        
                        // Intentamos guardar localmente como respaldo
                        $dir_local_backup = 'uploads/actas/';
                        if (!is_dir($dir_local_backup)) {
                            mkdir($dir_local_backup, 0755, true);
                        }
                        
                        $ruta_local_final = $dir_local_backup . $nombre_archivo;
                        if (copy($ruta_local_tmp, $ruta_local_final)) {
                            error_log("Archivo guardado localmente como respaldo: " . $ruta_local_final);
                            
                            // Intentamos registrar el documento en la BD con el archivo local
                            $sql_doc = "INSERT INTO DOCUMENTO_PROYECTO (
                                        ANIO_PRO, NUMERO_PRO, NUMERO_DOC, TIPO_DOC,
                                        FECHA_DOC, ARCHIVO, ESTADO, USUARIO
                                    ) VALUES (
                                        :anio_pro, :numero_pro, :numero_doc, :tipo_doc,
                                        TO_DATE(:fecha_doc, 'YYYY-MM-DD'), :archivo, 'A', :usuario
                                    )";
                            
                            $stmt_doc = oci_parse($conn, $sql_doc);
                            
                            oci_bind_by_name($stmt_doc, ':anio_pro', $datos['anio_pro']);
                            oci_bind_by_name($stmt_doc, ':numero_pro', $datos['numero_pro']);
                            oci_bind_by_name($stmt_doc, ':numero_doc', $numero_acta);
                            oci_bind_by_name($stmt_doc, ':tipo_doc', $datos['tipo_acta']);
                            oci_bind_by_name($stmt_doc, ':fecha_doc', $datos['fecha_acta']);
                            oci_bind_by_name($stmt_doc, ':archivo', $nombre_archivo);
                            oci_bind_by_name($stmt_doc, ':usuario', $datos['usuario']);
                            
                            $r_doc = oci_execute($stmt_doc, OCI_NO_AUTO_COMMIT);
                            oci_free_statement($stmt_doc);
                        }
                    }
                    
                    // Eliminar archivo temporal local en cualquier caso
                    if (file_exists($ruta_local_tmp)) {
                        unlink($ruta_local_tmp);
                    }
                } else {
                    error_log("Error al mover el archivo subido a la ubicación temporal");
                    // No abortamos, para mantener el acta en la BD
                }
            }
            
            // Confirmar la transacción
            oci_commit($conn);
            
            // Liberar recursos
            oci_free_statement($stmt);
            oci_close($conn);
            
            return $numero_acta;
            
        } catch (Exception $e) {
            error_log("Error en crearActaProyecto: " . $e->getMessage());
            return ['error' => "Error general al crear acta: " . $e->getMessage()];
        }
    }
}

/**
 * Obtiene el detalle de un acta específica
 * @param int $anio_pro Año del proyecto
 * @param int $numero_pro Número del proyecto
 * @param int $numero_acta Número del acta
 * @return array|null Detalles del acta o null si no existe
 */
if (!function_exists('obtenerDetalleActa')) {
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

/**
 * Obtiene el documento asociado a un acta de proyecto
 * @param int $proyectoId ID del proyecto
 * @param int $numeroActa Número del acta
 * @param int $tipoActa Tipo de acta
 * @return array|null Datos del documento o null si no existe
 * 
 * Nota: La URL base para acceder a los documentos es:
 * http://200.69.103.17/idexud/siexud/actasproy/upload/
 */
if (!function_exists('obtenerDocumentoActa')) {
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
            if (!$stid) {
                $e = oci_error($conn);
                error_log("Error al preparar consulta de documento acta: " . $e['message']);
                return null;
            }
            
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
            error_log("Error en obtenerDocumentoActa: " . $e['message']);
            return null;
        }
    }
}

/**
 * Obtiene el siguiente número de acta disponible para un proyecto
 * @param int $anio_pro Año del proyecto
 * @param int $numero_pro Número del proyecto
 * @return int Siguiente número de acta
 */
if (!function_exists('obtenerSiguienteNumeroActa')) {
    function obtenerSiguienteNumeroActa($anio_pro, $numero_pro) {
        try {
            // Obtener conexión
            $conn = conectarOracle();
            
            // Consulta SQL para obtener el máximo número de acta
            $sql = "SELECT NVL(MAX(NUMERO_ACTA), 0) + 1 AS siguiente_numero 
                    FROM ACTA_PROYECTO 
                    WHERE ANIO_PRO = :anio_pro AND NUMERO_PRO = :numero_pro";
            
            // Preparar consulta
            $stid = oci_parse($conn, $sql);
            
            // Vincular parámetros
            oci_bind_by_name($stid, ':anio_pro', $anio_pro);
            oci_bind_by_name($stid, ':numero_pro', $numero_pro);
            
            // Ejecutar consulta
            oci_execute($stid);
            
            // Obtener resultado
            $row = oci_fetch_assoc($stid);
            $siguiente_numero = $row['SIGUIENTE_NUMERO'];
            
            // Liberar recursos
            oci_free_statement($stid);
            oci_close($conn);
            
            return $siguiente_numero;
            
        } catch (Exception $e) {
            error_log("Error en obtenerSiguienteNumeroActa: " . $e->getMessage());
            return 1; // Por defecto, empezar desde 1
        }
    }
}

/**
 * Actualiza un acta existente y su documento asociado si se proporciona uno nuevo
 * @param array $datos Datos del acta a actualizar
 * @param string $archivo_tmp Ruta temporal del archivo subido (opcional)
 * @return bool|array True si fue exitoso, o array con error si falló
 */
if (!function_exists('actualizarActaProyecto')) {
    function actualizarActaProyecto($datos, $archivo_tmp = null) {
        try {
            // Validar datos obligatorios
            if (empty($datos['anio_pro']) || empty($datos['numero_pro']) || 
                empty($datos['numero_acta']) || empty($datos['tipo_acta']) || 
                empty($datos['fecha_acta'])) {
                return ['error' => 'Faltan datos obligatorios para actualizar el acta'];
            }
            
            // Formatear fecha si es necesario
            if (!is_string($datos['fecha_acta'])) {
                $fecha_acta = date('d/m/Y', strtotime($datos['fecha_acta']));
            } else if (strpos($datos['fecha_acta'], '-') !== false) {
                // Si la fecha está en formato yyyy-mm-dd, convertirla a dd/mm/yyyy
                $fecha_acta = date('d/m/Y', strtotime($datos['fecha_acta']));
            } else {
                $fecha_acta = $datos['fecha_acta'];
            }
            
            // Obtener conexión
            $conn = conectarOracle();
            
            // Iniciar transacción
            oci_set_action($conn, 'actualizarActaProyecto');
            
            // 1. Verificar que el acta existe
            $sql_check = "SELECT COUNT(*) AS existe FROM ACTA_PROYECTO 
                        WHERE ANIO_PRO = :anio_pro 
                        AND NUMERO_PRO = :numero_pro 
                        AND NUMERO_ACTA = :numero_acta";
            
            $stmt_check = oci_parse($conn, $sql_check);
            if (!$stmt_check) {
                $e = oci_error($conn);
                error_log("Error al preparar consulta de verificación: " . $e['message']);
                return ['error' => $e['message']];
            }
            
            oci_bind_by_name($stmt_check, ':anio_pro', $datos['anio_pro']);
            oci_bind_by_name($stmt_check, ':numero_pro', $datos['numero_pro']);
            oci_bind_by_name($stmt_check, ':numero_acta', $datos['numero_acta']);
            
            $r_check = oci_execute($stmt_check);
            if (!$r_check) {
                $e = oci_error($stmt_check);
                error_log("Error al ejecutar consulta de verificación: " . $e['message']);
                return ['error' => $e['message']];
            }
            
            $row = oci_fetch_assoc($stmt_check);
            if (!$row || $row['EXISTE'] == 0) {
                oci_free_statement($stmt_check);
                return ['error' => 'El acta no existe'];
            }
            
            oci_free_statement($stmt_check);
            
            // 2. Actualizar en la tabla ACTA_PROYECTO
            $sql = "UPDATE ACTA_PROYECTO SET 
                        TIPO_ACTA = :tipo_acta, 
                        FECHA_ACTA = TO_DATE(:fecha_acta, 'DD/MM/YYYY'), 
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
            oci_bind_by_name($stid, ':fecha_acta', $fecha_acta);
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
                
                // Directorio local temporal
                $dir_temp_local = 'uploads/temp/';
                
                // Crear directorio local temporal si no existe
                if (!is_dir($dir_temp_local)) {
                    mkdir($dir_temp_local, 0755, true);
                }
                
                // Ruta temporal local
                $ruta_local_tmp = $dir_temp_local . $nombre_archivo;
                
                // Mover el archivo a la ubicación temporal
                if (move_uploaded_file($archivo_tmp, $ruta_local_tmp)) {
                    // Intentar subir el archivo
                    $resultado_subida = subirArchivoSFTP($ruta_local_tmp, $nombre_archivo);
                    
                    // Si la subida fue exitosa, o bien si guardamos localmente como respaldo
                    if ($resultado_subida === true || copy($ruta_local_tmp, 'uploads/actas/' . $nombre_archivo)) {
                        // Si existe un documento, actualizarlo
                        if ($archivo_anterior) {
                            $sql_doc = "UPDATE DOCUMENTO_PROYECTO SET 
                                        FECHA_DOC = TO_DATE(:fecha_doc, 'DD/MM/YYYY'), 
                                        ARCHIVO = :archivo, 
                                        USUARIO = :usuario
                                    WHERE 
                                        ANIO_PRO = :anio_pro 
                                        AND NUMERO_PRO = :numero_pro 
                                        AND NUMERO_DOC = :numero_doc
                                        AND TIPO_DOC = :tipo_doc";
                        } else {
                            // Si no existe, crear uno nuevo
                            $sql_doc = "INSERT INTO DOCUMENTO_PROYECTO (
                                        ANIO_PRO, NUMERO_PRO, NUMERO_DOC, TIPO_DOC, 
                                        FECHA_DOC, ARCHIVO, ESTADO, USUARIO
                                    ) VALUES (
                                        :anio_pro, :numero_pro, :numero_doc, :tipo_doc, 
                                        TO_DATE(:fecha_doc, 'DD/MM/YYYY'), 
                                        :archivo, 'A', :usuario
                                    )";
                        }
                        
                        // Preparar consulta
                        $stid_doc = oci_parse($conn, $sql_doc);
                        if (!$stid_doc) {
                            $e = oci_error($conn);
                            error_log("Error al preparar consulta de documento: " . $e['message']);
                            oci_rollback($conn);
                            return ['error' => $e['message']];
                        }
                        
                        // Vincular parámetros
                        oci_bind_by_name($stid_doc, ':anio_pro', $datos['anio_pro']);
                        oci_bind_by_name($stid_doc, ':numero_pro', $datos['numero_pro']);
                        oci_bind_by_name($stid_doc, ':numero_doc', $datos['numero_acta']);
                        oci_bind_by_name($stid_doc, ':tipo_doc', $datos['tipo_acta']);
                        oci_bind_by_name($stid_doc, ':fecha_doc', $fecha_acta);
                        oci_bind_by_name($stid_doc, ':archivo', $nombre_archivo);
                        oci_bind_by_name($stid_doc, ':usuario', $datos['usuario']);
                        
                        // Ejecutar consulta
                        $r_doc = oci_execute($stid_doc, OCI_NO_AUTO_COMMIT);
                        if (!$r_doc) {
                            $e = oci_error($stid_doc);
                            error_log("Error al actualizar/insertar en DOCUMENTO_PROYECTO: " . $e['message']);
                            oci_rollback($conn);
                            return ['error' => $e['message']];
                        }
                        
                        oci_free_statement($stid_doc);
                        
                        // Intentar eliminar el archivo anterior del servidor
                        if ($archivo_anterior) {
                            // Eliminar del servidor remoto
                            if ($resultado_subida === true) {
                                $sftp_host = '200.69.103.17';
                                $sftp_user = 'oracle';
                                $sftp_pass = 'QDameco2016';
                                $sftp_port = 22;
                                $dir_destino_remoto = '/var/www/html/idexud/siexud/actasproy/';
                                
                                try {
                                    $connection = ssh2_connect($sftp_host, $sftp_port);
                                    if ($connection && ssh2_auth_password($connection, $sftp_user, $sftp_pass)) {
                                        $sftp = ssh2_sftp($connection);
                                        
                                        // Intentar eliminar el archivo remoto
                                        $remote_file_path = $dir_destino_remoto . $archivo_anterior;
                                        @ssh2_sftp_unlink($sftp, $remote_file_path);
                                    }
                                } catch (Exception $ssh_error) {
                                    error_log("Error al eliminar archivo remoto: " . $ssh_error->getMessage());
                                    // No hacemos nada si falla, continuamos con el proceso
                                }
                            }
                            
                            // Eliminar archivo local
                            $ruta_local_anterior = 'uploads/actas/' . $archivo_anterior;
                            if (file_exists($ruta_local_anterior)) {
                                @unlink($ruta_local_anterior);
                            }
                        }
                    } else {
                        error_log("Error al subir o guardar el archivo: " . $resultado_subida);
                        oci_rollback($conn);
                        return ['error' => "Error al subir el archivo. Por favor, inténtelo de nuevo."];
                    }
                    
                    // Eliminar archivo temporal
                    if (file_exists($ruta_local_tmp)) {
                        @unlink($ruta_local_tmp);
                    }
                } else {
                    // Si falla al mover el archivo
                    oci_rollback($conn);
                    return ['error' => "Error al mover el archivo subido"];
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
}

/**
 * Elimina un acta y su documento asociado
 * @param int $anio_pro Año del proyecto
 * @param int $numero_pro Número del proyecto
 * @param int $numero_acta Número del acta
 * @return bool|array True si fue exitoso, o array con error si falló
 */
if (!function_exists('eliminarActaProyecto')) {
    function eliminarActaProyecto($anio_pro, $numero_pro, $numero_acta) {
        try {
            // Obtener conexión
            $conn = conectarOracle();
            
            // Iniciar transacción
            oci_set_action($conn, 'eliminarActaProyecto');
            
            // Primero verificar que el acta existe
            $sql_check = "SELECT COUNT(*) AS existe FROM ACTA_PROYECTO 
                        WHERE ANIO_PRO = :anio_pro 
                        AND NUMERO_PRO = :numero_pro 
                        AND NUMERO_ACTA = :numero_acta";
            
            $stmt_check = oci_parse($conn, $sql_check);
            if (!$stmt_check) {
                $e = oci_error($conn);
                error_log("Error al preparar consulta de verificación: " . $e['message']);
                return ['error' => $e['message']];
            }
            
            oci_bind_by_name($stmt_check, ':anio_pro', $anio_pro);
            oci_bind_by_name($stmt_check, ':numero_pro', $numero_pro);
            oci_bind_by_name($stmt_check, ':numero_acta', $numero_acta);
            
            $r_check = oci_execute($stmt_check);
            if (!$r_check) {
                $e = oci_error($stmt_check);
                error_log("Error al ejecutar consulta de verificación: " . $e['message']);
                return ['error' => $e['message']];
            }
            
            $row = oci_fetch_assoc($stmt_check);
            if (!$row || $row['EXISTE'] == 0) {
                oci_free_statement($stmt_check);
                return ['error' => 'El acta no existe'];
            }
            
            oci_free_statement($stmt_check);
            
            // Obtener información del acta y documentos asociados
            $sql_get_info = "SELECT a.TIPO_ACTA, d.ARCHIVO 
                             FROM ACTA_PROYECTO a
                             LEFT JOIN DOCUMENTO_PROYECTO d ON a.ANIO_PRO = d.ANIO_PRO 
                                                           AND a.NUMERO_PRO = d.NUMERO_PRO 
                                                           AND a.NUMERO_ACTA = d.NUMERO_DOC
                                                           AND a.TIPO_ACTA = d.TIPO_DOC
                             WHERE a.ANIO_PRO = :anio_pro 
                             AND a.NUMERO_PRO = :numero_pro 
                             AND a.NUMERO_ACTA = :numero_acta";
            
            $stid_get = oci_parse($conn, $sql_get_info);
            oci_bind_by_name($stid_get, ':anio_pro', $anio_pro);
            oci_bind_by_name($stid_get, ':numero_pro', $numero_pro);
            oci_bind_by_name($stid_get, ':numero_acta', $numero_acta);
            oci_execute($stid_get);
            
            $row = oci_fetch_assoc($stid_get);
            $tipo_acta = $row ? $row['TIPO_ACTA'] : null;
            $archivo = $row ? $row['ARCHIVO'] : null;
            
            oci_free_statement($stid_get);
            
            // Eliminar el documento de la base de datos si existe
            if ($tipo_acta) {
                $sql_del_doc = "DELETE FROM DOCUMENTO_PROYECTO 
                              WHERE ANIO_PRO = :anio_pro 
                              AND NUMERO_PRO = :numero_pro 
                              AND NUMERO_DOC = :numero_acta
                              AND TIPO_DOC = :tipo_acta";
                
                $stid_del_doc = oci_parse($conn, $sql_del_doc);
                if (!$stid_del_doc) {
                    $e = oci_error($conn);
                    error_log("Error al preparar consulta de eliminación en DOCUMENTO_PROYECTO: " . $e['message']);
                    return ['error' => $e['message']];
                }
                
                oci_bind_by_name($stid_del_doc, ':anio_pro', $anio_pro);
                oci_bind_by_name($stid_del_doc, ':numero_pro', $numero_pro);
                oci_bind_by_name($stid_del_doc, ':numero_acta', $numero_acta);
                oci_bind_by_name($stid_del_doc, ':tipo_acta', $tipo_acta);
                
                $r_del_doc = oci_execute($stid_del_doc, OCI_NO_AUTO_COMMIT);
                if (!$r_del_doc) {
                    $e = oci_error($stid_del_doc);
                    error_log("Error al ejecutar consulta de eliminación en DOCUMENTO_PROYECTO: " . $e['message']);
                    oci_rollback($conn);
                    return ['error' => $e['message']];
                }
                
                oci_free_statement($stid_del_doc);
            }
            
            // Eliminar el acta de la base de datos
            $sql = "DELETE FROM ACTA_PROYECTO 
                    WHERE ANIO_PRO = :anio_pro 
                    AND NUMERO_PRO = :numero_pro 
                    AND NUMERO_ACTA = :numero_acta";
            
            $stid = oci_parse($conn, $sql);
            if (!$stid) {
                $e = oci_error($conn);
                error_log("Error al preparar consulta de eliminación en ACTA_PROYECTO: " . $e['message']);
                oci_rollback($conn);
                return ['error' => $e['message']];
            }
            
            oci_bind_by_name($stid, ':anio_pro', $anio_pro);
            oci_bind_by_name($stid, ':numero_pro', $numero_pro);
            oci_bind_by_name($stid, ':numero_acta', $numero_acta);
            
            $r = oci_execute($stid, OCI_NO_AUTO_COMMIT);
            if (!$r) {
                $e = oci_error($stid);
                error_log("Error al ejecutar consulta de eliminación en ACTA_PROYECTO: " . $e['message']);
                oci_rollback($conn);
                return ['error' => $e['message']];
            }
            
            // Confirmar transacción
            oci_commit($conn);
            
            // Intentar eliminar el archivo físico
            if ($archivo) {
                // Eliminar del servidor remoto
                $sftp_host = '200.69.103.17';
                $sftp_user = 'oracle';
                $sftp_pass = 'QDameco2016';
                $sftp_port = 22;
                $dir_destino_remoto = '/var/www/html/idexud/siexud/actasproy/';
                
                try {
                    $connection = ssh2_connect($sftp_host, $sftp_port);
                    if ($connection && ssh2_auth_password($connection, $sftp_user, $sftp_pass)) {
                        $sftp = ssh2_sftp($connection);
                        
                        // Intentar eliminar el archivo remoto
                        $remote_file_path = $dir_destino_remoto . $archivo;
                        @ssh2_sftp_unlink($sftp, $remote_file_path);
                    }
                } catch (Exception $ssh_error) {
                    error_log("Error al eliminar archivo remoto: " . $ssh_error->getMessage());
                    // No hacemos nada si falla, el acta ya se eliminó de la BD
                }
                
                // Eliminar archivo local
                $ruta_local = 'uploads/actas/' . $archivo;
                if (file_exists($ruta_local)) {
                    @unlink($ruta_local);
                }
            }
            
            // Liberar recursos
            oci_free_statement($stid);
            oci_close($conn);
            
            return true;
            
        } catch (Exception $e) {
            error_log("Error en eliminarActaProyecto: " . $e->getMessage());
            return ['error' => "Error general: " . $e->getMessage()];
        }
    }
}

/**
 * Obtiene el tipo de acta por su código
 * @param int $codigo Código del tipo de acta
 * @return array|null Datos del tipo de acta o null si no existe
 */
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

/**
 * Sube un archivo al servidor remoto usando SFTP
 * @param string $archivo_local Ruta del archivo local
 * @param string $nombre_remoto Nombre del archivo en el servidor remoto
 * @return bool|string True si la subida fue exitosa, o mensaje de error
 */
if (!function_exists('subirArchivoSFTP')) {
    function subirArchivoSFTP($archivo_local, $nombre_remoto) {
        // Configuración de la conexión SFTP
        $sftp_host = '200.69.103.17';
        $sftp_user = 'oracle';
        $sftp_pass = 'QDameco2016';
        $sftp_port = 22;
        $dir_destino_remoto = '/var/www/html/idexud/siexud/actasproy/';
        
        // Verificar que el archivo local existe
        if (!file_exists($archivo_local)) {
            return "El archivo local no existe: $archivo_local";
        }
        
        // Verificar que podemos leer el archivo
        if (!is_readable($archivo_local)) {
            return "No se puede leer el archivo local: $archivo_local";
        }
        
        try {
            // Establecer conexión SSH
            $connection = @ssh2_connect($sftp_host, $sftp_port);
            if (!$connection) {
                throw new Exception("No se pudo conectar al servidor SSH: $sftp_host:$sftp_port");
            }
            
            // Autenticación
            if (!@ssh2_auth_password($connection, $sftp_user, $sftp_pass)) {
                throw new Exception("Error de autenticación con el usuario: $sftp_user");
            }
            
            // Inicializar SFTP
            $sftp = @ssh2_sftp($connection);
            if (!$sftp) {
                throw new Exception("No se pudo inicializar el subsistema SFTP");
            }
            
            // Verificar si el directorio remoto existe y es accesible
            $remoteDir = "ssh2.sftp://{$sftp}{$dir_destino_remoto}";
            if (!@file_exists($remoteDir)) {
                throw new Exception("El directorio remoto no existe o no es accesible: $dir_destino_remoto");
            }
            
            // Ruta completa del archivo remoto
            $remote_file = $dir_destino_remoto . $nombre_remoto;
            $remote_stream = @fopen("ssh2.sftp://{$sftp}{$remote_file}", 'w');
            
            if (!$remote_stream) {
                throw new Exception("No se pudo abrir el stream para escritura en el servidor remoto");
            }
            
            // Leer el contenido del archivo local
            $local_content = @file_get_contents($archivo_local);
            if ($local_content === false) {
                throw new Exception("No se pudo leer el contenido del archivo local");
            }
            
            // Escribir en el servidor remoto
            $bytes_written = @fwrite($remote_stream, $local_content);
            @fclose($remote_stream);
            
            if ($bytes_written === false || $bytes_written === 0) {
                throw new Exception("Error al escribir datos en el archivo remoto");
            }
            
            // Verificar que el archivo existe en el servidor remoto
            $check_file = @file_exists("ssh2.sftp://{$sftp}{$remote_file}");
            if (!$check_file) {
                throw new Exception("No se pudo verificar la existencia del archivo en el servidor remoto");
            }
            
            // Registrar éxito en el log
            error_log("Archivo subido correctamente al servidor remoto: $remote_file");
            
            return true;
        } catch (Exception $e) {
            error_log("Error en subirArchivoSFTP: " . $e->getMessage());
            return $e->getMessage();
        }
    }
}
?>