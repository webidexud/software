<?php
/**
 * Funciones mejoradas para la gestión de actas de proyecto
 * Incluye la actualización de actas existentes y sus documentos asociados
 */

// Incluir el archivo de conexión si no está incluido
if (!function_exists('conectarOracle')) {
    require_once 'config/database.php';
}

// Incluir el modelo de actas si no está incluido
if (!function_exists('subirArchivoSFTP')) {
    require_once 'models/actas_proyecto_model.php';
}

/**
 * Actualiza un acta existente y su documento asociado sin crear registros duplicados
 * Maneja 3 escenarios:
 * 1. Cambio de tipo de acta sin cambio de documento (renombrar archivo)
 * 2. Cambio de documento sin cambio de tipo de acta (reemplazar archivo)
 * 3. Cambio de tipo y documento (eliminar archivo antiguo y crear uno nuevo)
 * 
 * @param array $datos Datos del acta a actualizar
 * @param string $archivo_tmp Ruta temporal del archivo subido (opcional)
 * @return bool|array True si fue exitoso, o array con error si falló
 */
function actualizarActaMejorado($datos, $archivo_tmp = null) {
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
        oci_set_action($conn, 'actualizarActaMejorado');
        
        // 1. Obtener información del acta original para comparar
        $sql_acta_original = "SELECT TIPO_ACTA as tipo_acta_original FROM ACTA_PROYECTO 
                            WHERE ANIO_PRO = :anio_pro 
                            AND NUMERO_PRO = :numero_pro 
                            AND NUMERO_ACTA = :numero_acta";
        
        $stmt_acta_original = oci_parse($conn, $sql_acta_original);
        if (!$stmt_acta_original) {
            $e = oci_error($conn);
            error_log("Error al preparar consulta de acta original: " . $e['message']);
            return ['error' => $e['message']];
        }
        
        oci_bind_by_name($stmt_acta_original, ':anio_pro', $datos['anio_pro']);
        oci_bind_by_name($stmt_acta_original, ':numero_pro', $datos['numero_pro']);
        oci_bind_by_name($stmt_acta_original, ':numero_acta', $datos['numero_acta']);
        
        $r_acta_original = oci_execute($stmt_acta_original);
        if (!$r_acta_original) {
            $e = oci_error($stmt_acta_original);
            error_log("Error al ejecutar consulta de acta original: " . $e['message']);
            return ['error' => $e['message']];
        }
        
        $row_acta_original = oci_fetch_assoc($stmt_acta_original);
        if (!$row_acta_original) {
            oci_free_statement($stmt_acta_original);
            return ['error' => 'El acta no existe'];
        }
        
        $tipo_acta_original = $row_acta_original['TIPO_ACTA_ORIGINAL'];
        oci_free_statement($stmt_acta_original);
        
        // Verificar si cambió el tipo de acta
        $tipo_acta_cambio = ($tipo_acta_original != $datos['tipo_acta']);
        
        // Verificar si se subió un nuevo documento
        $nuevo_documento = ($archivo_tmp && file_exists($archivo_tmp));
        
        error_log("Tipo de acta original: $tipo_acta_original, Nuevo tipo: {$datos['tipo_acta']}");
        error_log("¿Cambió tipo de acta?: " . ($tipo_acta_cambio ? "SÍ" : "NO"));
        error_log("¿Nuevo documento?: " . ($nuevo_documento ? "SÍ" : "NO"));
        
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
        
        // 3. Manejar el documento según cada caso
        
        // Primero, verificar si existe un documento asociado
        $sql_check_doc = "SELECT COUNT(*) AS existe, MAX(ARCHIVO) AS archivo_actual, MAX(TIPO_DOC) AS tipo_doc_actual 
                        FROM DOCUMENTO_PROYECTO 
                        WHERE ANIO_PRO = :anio_pro 
                        AND NUMERO_PRO = :numero_pro 
                        AND NUMERO_DOC = :numero_acta";
        
        $stmt_check_doc = oci_parse($conn, $sql_check_doc);
        oci_bind_by_name($stmt_check_doc, ':anio_pro', $datos['anio_pro']);
        oci_bind_by_name($stmt_check_doc, ':numero_pro', $datos['numero_pro']);
        oci_bind_by_name($stmt_check_doc, ':numero_acta', $datos['numero_acta']);
        oci_execute($stmt_check_doc);
        
        $row_doc = oci_fetch_assoc($stmt_check_doc);
        $existe_documento = $row_doc['EXISTE'] > 0;
        $archivo_actual = $row_doc['ARCHIVO_ACTUAL'];
        $tipo_doc_actual = $row_doc['TIPO_DOC_ACTUAL'];
        
        error_log("Verificación de documento existente: " . ($existe_documento ? "SÍ EXISTE" : "NO EXISTE"));
        if ($existe_documento) {
            error_log("Nombre del archivo actual: $archivo_actual");
            error_log("Tipo de documento actual: $tipo_doc_actual");
        }
        
        oci_free_statement($stmt_check_doc);
        
        // Si no existe documento y no hay nuevo archivo, no hay nada que hacer con documentos
        if (!$existe_documento && !$nuevo_documento) {
            error_log("No existe documento y no hay nuevo archivo. No se realizan acciones con documentos.");
            oci_commit($conn);
            oci_free_statement($stid);
            oci_close($conn);
            return true;
        }
        
        // A. Cambio de tipo sin cambio de documento (renombrar)
        if ($tipo_acta_cambio && !$nuevo_documento && $existe_documento) {
            error_log("CASO A: Cambio de tipo de acta sin cambio de documento");
            
            // Generar nuevo nombre de archivo basado en el nuevo tipo
            $tipo_acta_info = obtenerTipoActaPorCodigo($datos['tipo_acta']);
            $tipo_acta_desc = $tipo_acta_info ? strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $tipo_acta_info['descripcion'])) : 'acta';
            
            // Mantener la extensión original
            $extension = pathinfo($archivo_actual, PATHINFO_EXTENSION);
            $nuevo_nombre = $datos['anio_pro'] . '_' . $datos['numero_pro'] . '_' . $tipo_acta_desc . '_' . $datos['numero_acta'] . '.' . $extension;
            
            error_log("Renombrando archivo de '$archivo_actual' a '$nuevo_nombre'");
            
            // Actualizar el registro en DOCUMENTO_PROYECTO
            $sql_update_doc = "UPDATE DOCUMENTO_PROYECTO SET 
                                TIPO_DOC = :tipo_doc,
                                FECHA_DOC = TO_DATE(:fecha_doc, 'DD/MM/YYYY'),
                                ARCHIVO = :nuevo_nombre,
                                USUARIO = :usuario
                            WHERE 
                                ANIO_PRO = :anio_pro 
                                AND NUMERO_PRO = :numero_pro 
                                AND NUMERO_DOC = :numero_acta";
            
            $stmt_update_doc = oci_parse($conn, $sql_update_doc);
            if (!$stmt_update_doc) {
                $e = oci_error($conn);
                error_log("Error al preparar consulta de actualización de documento: " . $e['message']);
                oci_rollback($conn);
                return ['error' => $e['message']];
            }
            
            oci_bind_by_name($stmt_update_doc, ':tipo_doc', $datos['tipo_acta']);
            oci_bind_by_name($stmt_update_doc, ':fecha_doc', $fecha_acta);
            oci_bind_by_name($stmt_update_doc, ':nuevo_nombre', $nuevo_nombre);
            oci_bind_by_name($stmt_update_doc, ':usuario', $datos['usuario']);
            oci_bind_by_name($stmt_update_doc, ':anio_pro', $datos['anio_pro']);
            oci_bind_by_name($stmt_update_doc, ':numero_pro', $datos['numero_pro']);
            oci_bind_by_name($stmt_update_doc, ':numero_acta', $datos['numero_acta']);
            
            $r_update_doc = oci_execute($stmt_update_doc, OCI_NO_AUTO_COMMIT);
            if (!$r_update_doc) {
                $e = oci_error($stmt_update_doc);
                error_log("Error al actualizar documento: " . $e['message']);
                oci_rollback($conn);
                return ['error' => $e['message']];
            }
            
            oci_free_statement($stmt_update_doc);
            
            // Renombrar archivo en el servidor remoto
            $resultado_renombrar = renombrarArchivoRemoto($archivo_actual, $nuevo_nombre);
            if (!$resultado_renombrar) {
                error_log("Error al renombrar archivo en el servidor remoto");
                oci_rollback($conn);
                return ['error' => "Error al renombrar archivo en el servidor remoto"];
            }
        }
        // B. Sin cambio de tipo pero con nuevo documento (reemplazar)
        else if (!$tipo_acta_cambio && $nuevo_documento && $existe_documento) {
            error_log("CASO B: Sin cambio de tipo pero con nuevo documento");
            
            // Usar el mismo nombre de archivo existente
            $nombre_archivo = $archivo_actual;
            
            // Eliminar el archivo existente
            eliminarArchivoRemoto($archivo_actual);
            
            // Subir el nuevo archivo con el mismo nombre
            error_log("Reemplazando archivo: $nombre_archivo");
            $resultado_subida = subirArchivoSFTP($archivo_tmp, $nombre_archivo);
            
            if ($resultado_subida !== true) {
                error_log("Error al subir el nuevo archivo: " . $resultado_subida);
                oci_rollback($conn);
                return ['error' => "Error al subir el nuevo archivo: " . $resultado_subida];
            }
            
            // Actualizar la fecha en DOCUMENTO_PROYECTO
            $sql_update_fecha = "UPDATE DOCUMENTO_PROYECTO SET 
                                FECHA_DOC = TO_DATE(:fecha_doc, 'DD/MM/YYYY'),
                                USUARIO = :usuario
                            WHERE 
                                ANIO_PRO = :anio_pro 
                                AND NUMERO_PRO = :numero_pro 
                                AND NUMERO_DOC = :numero_acta";
            
            $stmt_update_fecha = oci_parse($conn, $sql_update_fecha);
            oci_bind_by_name($stmt_update_fecha, ':fecha_doc', $fecha_acta);
            oci_bind_by_name($stmt_update_fecha, ':usuario', $datos['usuario']);
            oci_bind_by_name($stmt_update_fecha, ':anio_pro', $datos['anio_pro']);
            oci_bind_by_name($stmt_update_fecha, ':numero_pro', $datos['numero_pro']);
            oci_bind_by_name($stmt_update_fecha, ':numero_acta', $datos['numero_acta']);
            
            $r_update_fecha = oci_execute($stmt_update_fecha, OCI_NO_AUTO_COMMIT);
            if (!$r_update_fecha) {
                $e = oci_error($stmt_update_fecha);
                error_log("Error al actualizar fecha de documento: " . $e['message']);
                oci_rollback($conn);
                return ['error' => $e['message']];
            }
            
            oci_free_statement($stmt_update_fecha);
        }
        // C. Cambio de tipo y nuevo documento (eliminar y crear nuevo)
        else if ($tipo_acta_cambio && $nuevo_documento && $existe_documento) {
            error_log("CASO C: Cambio de tipo y nuevo documento");
            
            // Eliminar el archivo existente
            eliminarArchivoRemoto($archivo_actual);
            
            // Generar nuevo nombre de archivo
            $tipo_acta_info = obtenerTipoActaPorCodigo($datos['tipo_acta']);
            $tipo_acta_desc = $tipo_acta_info ? strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $tipo_acta_info['descripcion'])) : 'acta';
            $extension = strtolower(pathinfo($_FILES['archivo']['name'], PATHINFO_EXTENSION));
            $nuevo_nombre = $datos['anio_pro'] . '_' . $datos['numero_pro'] . '_' . $tipo_acta_desc . '_' . $datos['numero_acta'] . '.' . $extension;
            
            // Subir el nuevo archivo con el nuevo nombre
            error_log("Subiendo nuevo archivo: $nuevo_nombre");
            $resultado_subida = subirArchivoSFTP($archivo_tmp, $nuevo_nombre);
            
            if ($resultado_subida !== true) {
                error_log("Error al subir el nuevo archivo: " . $resultado_subida);
                oci_rollback($conn);
                return ['error' => "Error al subir el nuevo archivo: " . $resultado_subida];
            }
            
            // Actualizar DOCUMENTO_PROYECTO con nuevo tipo y archivo
            $sql_update_doc = "UPDATE DOCUMENTO_PROYECTO SET 
                                TIPO_DOC = :tipo_doc,
                                FECHA_DOC = TO_DATE(:fecha_doc, 'DD/MM/YYYY'),
                                ARCHIVO = :nuevo_nombre,
                                USUARIO = :usuario
                            WHERE 
                                ANIO_PRO = :anio_pro 
                                AND NUMERO_PRO = :numero_pro 
                                AND NUMERO_DOC = :numero_acta";
            
            $stmt_update_doc = oci_parse($conn, $sql_update_doc);
            oci_bind_by_name($stmt_update_doc, ':tipo_doc', $datos['tipo_acta']);
            oci_bind_by_name($stmt_update_doc, ':fecha_doc', $fecha_acta);
            oci_bind_by_name($stmt_update_doc, ':nuevo_nombre', $nuevo_nombre);
            oci_bind_by_name($stmt_update_doc, ':usuario', $datos['usuario']);
            oci_bind_by_name($stmt_update_doc, ':anio_pro', $datos['anio_pro']);
            oci_bind_by_name($stmt_update_doc, ':numero_pro', $datos['numero_pro']);
            oci_bind_by_name($stmt_update_doc, ':numero_acta', $datos['numero_acta']);
            
            $r_update_doc = oci_execute($stmt_update_doc, OCI_NO_AUTO_COMMIT);
            if (!$r_update_doc) {
                $e = oci_error($stmt_update_doc);
                error_log("Error al actualizar documento: " . $e['message']);
                oci_rollback($conn);
                return ['error' => $e['message']];
            }
            
            oci_free_statement($stmt_update_doc);
        }
        // D. No existe documento pero hay nuevo documento (insertar)
        else if ($nuevo_documento && !$existe_documento) {
            error_log("CASO D: No existe documento pero hay nuevo documento");
            
            // Generar nombre para el nuevo archivo
            $tipo_acta_info = obtenerTipoActaPorCodigo($datos['tipo_acta']);
            $tipo_acta_desc = $tipo_acta_info ? strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $tipo_acta_info['descripcion'])) : 'acta';
            $extension = strtolower(pathinfo($_FILES['archivo']['name'], PATHINFO_EXTENSION));
            $nombre_archivo = $datos['anio_pro'] . '_' . $datos['numero_pro'] . '_' . $tipo_acta_desc . '_' . $datos['numero_acta'] . '.' . $extension;
            
            // Subir el archivo
            error_log("Subiendo archivo nuevo (no existía documento): $nombre_archivo");
            $resultado_subida = subirArchivoSFTP($archivo_tmp, $nombre_archivo);
            
            if ($resultado_subida !== true) {
                error_log("Error al subir archivo nuevo: " . $resultado_subida);
                oci_rollback($conn);
                return ['error' => "Error al subir archivo nuevo: " . $resultado_subida];
            }
            
            // Insertar en DOCUMENTO_PROYECTO
            $sql_insert_doc = "INSERT INTO DOCUMENTO_PROYECTO (
                                ANIO_PRO, NUMERO_PRO, NUMERO_DOC, TIPO_DOC,
                                FECHA_DOC, ARCHIVO, ESTADO, USUARIO
                            ) VALUES (
                                :anio_pro, :numero_pro, :numero_doc, :tipo_doc,
                                TO_DATE(:fecha_doc, 'DD/MM/YYYY'), :archivo, 'A', :usuario
                            )";
            
            $stmt_insert_doc = oci_parse($conn, $sql_insert_doc);
            oci_bind_by_name($stmt_insert_doc, ':anio_pro', $datos['anio_pro']);
            oci_bind_by_name($stmt_insert_doc, ':numero_pro', $datos['numero_pro']);
            oci_bind_by_name($stmt_insert_doc, ':numero_doc', $datos['numero_acta']);
            oci_bind_by_name($stmt_insert_doc, ':tipo_doc', $datos['tipo_acta']);
            oci_bind_by_name($stmt_insert_doc, ':fecha_doc', $fecha_acta);
            oci_bind_by_name($stmt_insert_doc, ':archivo', $nombre_archivo);
            oci_bind_by_name($stmt_insert_doc, ':usuario', $datos['usuario']);
            
            $r_insert_doc = oci_execute($stmt_insert_doc, OCI_NO_AUTO_COMMIT);
            if (!$r_insert_doc) {
                $e = oci_error($stmt_insert_doc);
                error_log("Error al insertar documento: " . $e['message']);
                oci_rollback($conn);
                return ['error' => $e['message']];
            }
            
            oci_free_statement($stmt_insert_doc);
        }
        // Otros casos no necesitan acción específica para documentos
        
        // Confirmar transacción
        oci_commit($conn);
        
        // Limpiar archivos temporales locales si existen
        if ($existe_documento) {
            $ruta_local_anterior = 'uploads/actas/' . $archivo_actual;
            if (file_exists($ruta_local_anterior)) {
                @unlink($ruta_local_anterior);
                error_log("Eliminada copia local de archivo: $ruta_local_anterior");
            }
        }
        
        // Liberar recursos
        oci_free_statement($stid);
        oci_close($conn);
        
        return true;
        
    } catch (Exception $e) {
        error_log("Error en actualizarActaMejorado: " . $e->getMessage());
        return ['error' => "Error general: " . $e->getMessage()];
    }
}

/**
 * Función auxiliar para eliminar archivos remotos
 * @param string $nombre_archivo Nombre del archivo a eliminar
 * @return bool True si se eliminó o no existía, False si hubo error
 */
function eliminarArchivoRemoto($nombre_archivo) {
    // Utilizamos la función ssh2_connect directamente en lugar de redeclarar la función completa
    $sftp_host = '200.69.103.17';
    $sftp_user = 'oracle';
    $sftp_pass = 'QDameco2016';
    $sftp_port = 22;
    $dir_destino_remoto = '/var/www/html/idexud/siexud/actasproy/upload/';
    
    try {
        error_log("Intentando eliminar archivo remoto: $nombre_archivo");
        
        $connection = @ssh2_connect($sftp_host, $sftp_port);
        if (!$connection) {
            error_log("No se pudo conectar al servidor SSH: $sftp_host:$sftp_port");
            return false;
        }
        
        if (!@ssh2_auth_password($connection, $sftp_user, $sftp_pass)) {
            error_log("Error de autenticación con el usuario: $sftp_user");
            return false;
        }
        
        $sftp = @ssh2_sftp($connection);
        if (!$sftp) {
            error_log("No se pudo inicializar el subsistema SFTP");
            return false;
        }
        
        $remote_file = $dir_destino_remoto . $nombre_archivo;
        $sftp_path = "ssh2.sftp://{$sftp}{$remote_file}";
        
        if (@file_exists($sftp_path)) {
            $resultado = @ssh2_sftp_unlink($sftp, $remote_file);
            if ($resultado) {
                error_log("¡ÉXITO! Archivo eliminado del servidor remoto: $remote_file");
                return true;
            } else {
                error_log("¡ERROR! No se pudo eliminar el archivo del servidor remoto: $remote_file");
                return false;
            }
        } else {
            error_log("El archivo no existe en el servidor remoto: $remote_file");
            return true; // Consideramos éxito si el archivo no existe
        }
    } catch (Exception $e) {
        error_log("Error en eliminarArchivoRemoto: " . $e->getMessage());
        return false;
    }
}

/**
 * Función para renombrar un archivo en el servidor remoto
 * @param string $nombre_original Nombre original del archivo
 * @param string $nombre_nuevo Nuevo nombre del archivo
 * @return bool True si tuvo éxito, False en caso contrario
 */
function renombrarArchivoRemoto($nombre_original, $nombre_nuevo) {
    $sftp_host = '200.69.103.17';
    $sftp_user = 'oracle';
    $sftp_pass = 'QDameco2016';
    $sftp_port = 22;
    $dir_destino_remoto = '/var/www/html/idexud/siexud/actasproy/upload/';
    
    try {
        error_log("Intentando renombrar archivo remoto de '$nombre_original' a '$nombre_nuevo'");
        
        $connection = @ssh2_connect($sftp_host, $sftp_port);
        if (!$connection) {
            error_log("No se pudo conectar al servidor SSH: $sftp_host:$sftp_port");
            return false;
        }
        
        if (!@ssh2_auth_password($connection, $sftp_user, $sftp_pass)) {
            error_log("Error de autenticación con el usuario: $sftp_user");
            return false;
        }
        
        $sftp = @ssh2_sftp($connection);
        if (!$sftp) {
            error_log("No se pudo inicializar el subsistema SFTP");
            return false;
        }
        
        $ruta_original = $dir_destino_remoto . $nombre_original;
        $ruta_nueva = $dir_destino_remoto . $nombre_nuevo;
        
        // Verificar si el archivo original existe
        $sftp_ruta_original = "ssh2.sftp://{$sftp}{$ruta_original}";
        if (!@file_exists($sftp_ruta_original)) {
            error_log("El archivo original no existe en el servidor: $ruta_original");
            return false;
        }
        
        // Ejecutar comando SSH para renombrar el archivo
        $comando = "mv '$ruta_original' '$ruta_nueva'";
        $stream = ssh2_exec($connection, $comando);
        
        if (!$stream) {
            error_log("Error al ejecutar comando para renombrar el archivo");
            return false;
        }
        
        // Esperar a que termine el comando
        stream_set_blocking($stream, true);
        $output = stream_get_contents($stream);
        fclose($stream);
        
        // Verificar si el archivo con el nuevo nombre existe
        $sftp_ruta_nueva = "ssh2.sftp://{$sftp}{$ruta_nueva}";
        if (@file_exists($sftp_ruta_nueva)) {
            error_log("¡ÉXITO! Archivo renombrado correctamente: $ruta_nueva");
            return true;
        } else {
            error_log("¡ERROR! No se pudo verificar que el archivo fue renombrado");
            return false;
        }
    } catch (Exception $e) {
        error_log("Error en renombrarArchivoRemoto: " . $e->getMessage());
        return false;
    }
}
?>