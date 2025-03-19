<?php
/**
 * Funciones para gestión de actas asociadas a contratistas
 */

// Incluir el archivo de conexión si no está incluido
if (!function_exists('conectarOracle')) {
    require_once 'config/database.php';
}

/**
 * Obtiene los tipos de acta disponibles para contratistas
 * @return array Lista de tipos de acta para contratistas
 */
function obtenerTiposActaContratista() {
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
        error_log("Error en obtenerTiposActaContratista: " . $e->getMessage());
        
        // Datos de ejemplo en caso de error
        return [
            ['codigo' => 1, 'descripcion' => 'Acta de Inicio'],
            ['codigo' => 2, 'descripcion' => 'Informe de Actividades'],
            ['codigo' => 3, 'descripcion' => 'Acta de Suspensión'],
            ['codigo' => 4, 'descripcion' => 'Acta de Prórroga'],
            ['codigo' => 5, 'descripcion' => 'Acta de Liquidación']
        ];
    }
}

/**
 * Obtiene las actas asociadas a un contratista específico en un proyecto
 * @param string $contratista_id ID del contratista
 * @param int $proyecto_id ID del proyecto
 * @return array Lista de actas asociadas al contratista
 */
function obtenerActasContratista($contratista_id, $proyecto_id) {
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
        
        // Consulta SQL para obtener actas relacionadas con el contratista
        // Nota: Ajusta esta consulta según la estructura real de tu base de datos
        $sql = "SELECT 
                    a.ANIO_PRO,
                    a.NUMERO_PRO,
                    a.NUMERO_ACTA,
                    a.TIPO_ACTA,
                    t.DESCRIPCION as TIPO_DESCRIPCION,
                    a.FECHA_ACTA,
                    a.OBSERVA,
                    a.ESTADO,
                    d.ARCHIVO,
                    po.NUMERO_CONTRATO
                FROM 
                    ACTA_PROYECTO a
                LEFT JOIN 
                    TIPO_ACTA t ON a.TIPO_ACTA = t.CODIGO
                LEFT JOIN 
                    DOCUMENTO_PROYECTO d ON a.ANIO_PRO = d.ANIO_PRO 
                                        AND a.NUMERO_PRO = d.NUMERO_PRO 
                                        AND a.NUMERO_ACTA = d.NUMERO_DOC 
                                        AND a.TIPO_ACTA = d.TIPO_DOC
                LEFT JOIN 
                    PROYECTO_OPS po ON a.ANIO_PRO = po.ANIO_PRO 
                                    AND a.NUMERO_PRO = po.NUMERO_PRO
                WHERE 
                    a.ANIO_PRO = :anio_pro
                    AND a.NUMERO_PRO = :numero_pro
                    AND po.IDENTIFICACION = :contratista_id
                    AND a.ESTADO = 'A'
                ORDER BY 
                    a.FECHA_ACTA DESC";
        
        // Preparar consulta
        $stid = oci_parse($conn, $sql);
        if (!$stid) {
            $e = oci_error($conn);
            error_log("Error al preparar consulta de actas contratista: " . $e['message']);
            return [];
        }
        
        // Vincular parámetros
        oci_bind_by_name($stid, ':anio_pro', $anio_pro);
        oci_bind_by_name($stid, ':numero_pro', $proyecto_id);
        oci_bind_by_name($stid, ':contratista_id', $contratista_id);
        
        // Ejecutar consulta
        $r = oci_execute($stid);
        if (!$r) {
            $e = oci_error($stid);
            error_log("Error al ejecutar consulta de actas contratista: " . $e['message']);
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
 * Crea una nueva acta asociada a un contratista y sube el documento
 * @param array $datos Datos del acta
 * @param string $archivo_tmp Ruta temporal del archivo
 * @return int|array ID del acta creada o array con error
 */
function crearActaContratista($datos, $archivo_tmp) {
    try {
        // Validar datos obligatorios
        if (empty($datos['anio_pro']) || empty($datos['numero_pro']) || 
            empty($datos['tipo_acta']) || empty($datos['fecha_acta']) ||
            empty($datos['contratista_id'])) {
            return ['error' => 'Faltan datos obligatorios para crear el acta'];
        }
        
        // Obtener conexión
        $conn = conectarOracle();
        
        // Obtener el siguiente número de acta para este proyecto
        $numero_acta = obtenerSiguienteNumeroActa($datos['anio_pro'], $datos['numero_pro']);
        
        // Iniciar transacción
        oci_set_action($conn, 'crearActaContratista');
        
        // 1. Insertar en la tabla ACTA_PROYECTO
        $sql = "INSERT INTO ACTA_PROYECTO (
                    ANIO_PRO, NUMERO_PRO, NUMERO_ACTA, TIPO_ACTA,
                    FECHA_ACTA, OBSERVA, ESTADO, USUARIO, CONTRATISTA
                ) VALUES (
                    :anio_pro, :numero_pro, :numero_acta, :tipo_acta,
                    TO_DATE(:fecha_acta, 'YYYY-MM-DD'), :observa, 'A', :usuario, :contratista_id
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
        oci_bind_by_name($stmt, ':contratista_id', $datos['contratista_id']);
        
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
            $nombre_archivo = $datos['anio_pro'] . '_' . $datos['numero_pro'] . '_' . $datos['contratista_id'] . '_' . $tipo_acta_desc . '_' . $numero_acta . '.' . $extension;
            
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
                // Subir archivo al servidor remoto o guardar localmente
                $resultado_subida = subirArchivoSFTP($ruta_local_tmp, $nombre_archivo);
                
                if ($resultado_subida === true) {
                    // La subida fue exitosa, ahora insertar en DOCUMENTO_PROYECTO
                    $sql_doc = "INSERT INTO DOCUMENTO_PROYECTO (
                                ANIO_PRO, NUMERO_PRO, NUMERO_DOC, TIPO_DOC,
                                FECHA_DOC, ARCHIVO, ESTADO, USUARIO, CONTRATISTA
                            ) VALUES (
                                :anio_pro, :numero_pro, :numero_doc, :tipo_doc,
                                TO_DATE(:fecha_doc, 'YYYY-MM-DD'), :archivo, 'A', :usuario, :contratista_id
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
                    oci_bind_by_name($stmt_doc, ':contratista_id', $datos['contratista_id']);
                    
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
                    
                    // Plan B: Guardar localmente
                    $dir_local_backup = 'uploads/actas_contratistas/';
                    if (!is_dir($dir_local_backup)) {
                        mkdir($dir_local_backup, 0755, true);
                    }
                    
                    $ruta_local_final = $dir_local_backup . $nombre_archivo;
                    if (copy($ruta_local_tmp, $ruta_local_final)) {
                        error_log("Archivo guardado localmente como respaldo: " . $ruta_local_final);
                        
                        // Registrar el documento en la BD con el archivo local
                        $sql_doc = "INSERT INTO DOCUMENTO_PROYECTO (
                                    ANIO_PRO, NUMERO_PRO, NUMERO_DOC, TIPO_DOC,
                                    FECHA_DOC, ARCHIVO, ESTADO, USUARIO, CONTRATISTA
                                ) VALUES (
                                    :anio_pro, :numero_pro, :numero_doc, :tipo_doc,
                                    TO_DATE(:fecha_doc, 'YYYY-MM-DD'), :archivo, 'A', :usuario, :contratista_id
                                )";
                        
                        $stmt_doc = oci_parse($conn, $sql_doc);
                        
                        oci_bind_by_name($stmt_doc, ':anio_pro', $datos['anio_pro']);
                        oci_bind_by_name($stmt_doc, ':numero_pro', $datos['numero_pro']);
                        oci_bind_by_name($stmt_doc, ':numero_doc', $numero_acta);
                        oci_bind_by_name($stmt_doc, ':tipo_doc', $datos['tipo_acta']);
                        oci_bind_by_name($stmt_doc, ':fecha_doc', $datos['fecha_acta']);
                        oci_bind_by_name($stmt_doc, ':archivo', $nombre_archivo);
                        oci_bind_by_name($stmt_doc, ':usuario', $datos['usuario']);
                        oci_bind_by_name($stmt_doc, ':contratista_id', $datos['contratista_id']);
                        
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
        
        // 3. Actualizar información en PROYECTO_OPS si es necesario
        if (!empty($datos['numero_contrato'])) {
            $sql_update_ops = "UPDATE PROYECTO_OPS SET
                                NUMERO_CONTRATO = :numero_contrato
                              WHERE ANIO_PRO = :anio_pro
                                AND NUMERO_PRO = :numero_pro
                                AND IDENTIFICACION = :contratista_id";
                                
            $stmt_ops = oci_parse($conn, $sql_update_ops);
            oci_bind_by_name($stmt_ops, ':numero_contrato', $datos['numero_contrato']);
            oci_bind_by_name($stmt_ops, ':anio_pro', $datos['anio_pro']);
            oci_bind_by_name($stmt_ops, ':numero_pro', $datos['numero_pro']);
            oci_bind_by_name($stmt_ops, ':contratista_id', $datos['contratista_id']);
            
            $r_ops = oci_execute($stmt_ops, OCI_NO_AUTO_COMMIT);
            oci_free_statement($stmt_ops);
        }
        
        // Confirmar la transacción
        oci_commit($conn);
        
        // Liberar recursos
        oci_free_statement($stmt);
        oci_close($conn);
        
        return $numero_acta;
        
    } catch (Exception $e) {
        error_log("Error en crearActaContratista: " . $e->getMessage());
        return ['error' => "Error general al crear acta para contratista: " . $e->getMessage()];
    }
}
?>