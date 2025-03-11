<?php
// Incluir el archivo de conexión
require_once 'config/database.php';

/**
 * Obtiene todas las entidades
 * @return array Lista de entidades
 */
function obtenerTodasEntidades() {
    try {
        // Obtener conexión
        $conn = conectarOracle();
        
        // Consulta SQL
        $sql = "SELECT 
                    e.CODIGO as codigo,
                    e.DESCRIPCION as descripcion,
                    e.ORDEN as orden,
                    e.NIT as nit
                FROM 
                    ENTIDAD e
                ORDER BY 
                    e.DESCRIPCION ASC";
        
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
        $entidades = [];
        
        while ($row = oci_fetch_assoc($stid)) {
            // Convertir claves a minúsculas (para mantener consistencia)
            $entidad = array();
            foreach ($row as $key => $value) {
                $entidad[strtolower($key)] = $value;
            }
            $entidades[] = $entidad;
        }
        
        // Liberar recursos
        oci_free_statement($stid);
        oci_close($conn);
        
        return $entidades;
        
    } catch (Exception $e) {
        error_log("Error en obtenerTodasEntidades: " . $e->getMessage());
        
        // Retornar array vacío en caso de error
        return [];
    }
}

/**
 * Agrega una nueva entidad
 * @param string $descripcion Nombre de la entidad
 * @param int $orden Tipo de entidad (1-NACIONAL, 2-DEPARTAMENTAL, etc.)
 * @param string $nit NIT de la entidad (opcional)
 * @param string $direccion Dirección de la entidad (opcional)
 * @param string $telefono Teléfono de la entidad (opcional)
 * @param string $pagina_web Página web de la entidad (opcional)
 * @param string $correo Correo electrónico de la entidad (opcional)
 * @return bool True si se agregó correctamente, False en caso de error
 */
function agregarEntidad($descripcion, $orden, $nit = null, $direccion = null, $telefono = null, $pagina_web = null, $correo = null) {
    try {
        // Obtener conexión
        $conn = conectarOracle();
        
        // Iniciar transacción
        oci_set_action($conn, 'agregarEntidad');
        
        // Obtener el siguiente valor para CODIGO
        $stid = oci_parse($conn, "SELECT MAX(CODIGO) + 1 AS nuevo_codigo FROM ENTIDAD");
        oci_execute($stid);
        $row = oci_fetch_assoc($stid);
        $codigo = $row['NUEVO_CODIGO'];
        
        // Si no hay registros previos, iniciar desde 1
        if (!$codigo) {
            $codigo = 1;
        }
        
        // Consulta SQL para insertar en ENTIDAD
        $sql = "INSERT INTO ENTIDAD (CODIGO, DESCRIPCION, ORDEN, NIT) 
                VALUES (:codigo, :descripcion, :orden, :nit)";
        
        // Preparar consulta
        $stid = oci_parse($conn, $sql);
        if (!$stid) {
            $e = oci_error($conn);
            error_log("Error al preparar consulta de inserción en ENTIDAD: " . $e['message']);
            return false;
        }
        
        // Vincular parámetros
        oci_bind_by_name($stid, ":codigo", $codigo);
        oci_bind_by_name($stid, ":descripcion", $descripcion);
        oci_bind_by_name($stid, ":orden", $orden);
        oci_bind_by_name($stid, ":nit", $nit);
        
        // Ejecutar consulta
        $r = oci_execute($stid, OCI_NO_AUTO_COMMIT);
        if (!$r) {
            $e = oci_error($stid);
            error_log("Error al ejecutar consulta de inserción en ENTIDAD: " . $e['message']);
            oci_rollback($conn);
            return false;
        }
        
        // Consulta SQL para insertar en ENTIDAD_CONTACTO
        $sqlContacto = "INSERT INTO ENTIDAD_CONTACTO (CODIGO, DIRECCION, TELEFONO, PAGINA_WEB, CORREO) 
                        VALUES (:codigo, :direccion, :telefono, :pagina_web, :correo)";
        
        // Preparar consulta
        $stidContacto = oci_parse($conn, $sqlContacto);
        if (!$stidContacto) {
            $e = oci_error($conn);
            error_log("Error al preparar consulta de inserción en ENTIDAD_CONTACTO: " . $e['message']);
            oci_rollback($conn);
            return false;
        }
        
        // Vincular parámetros
        oci_bind_by_name($stidContacto, ":codigo", $codigo);
        oci_bind_by_name($stidContacto, ":direccion", $direccion);
        oci_bind_by_name($stidContacto, ":telefono", $telefono);
        oci_bind_by_name($stidContacto, ":pagina_web", $pagina_web);
        oci_bind_by_name($stidContacto, ":correo", $correo);
        
        // Ejecutar consulta
        $rContacto = oci_execute($stidContacto, OCI_NO_AUTO_COMMIT);
        if (!$rContacto) {
            $e = oci_error($stidContacto);
            error_log("Error al ejecutar consulta de inserción en ENTIDAD_CONTACTO: " . $e['message']);
            oci_rollback($conn);
            return false;
        }
        
        // Confirmar transacción
        $commit = oci_commit($conn);
        
        // Liberar recursos
        oci_free_statement($stid);
        oci_free_statement($stidContacto);
        oci_close($conn);
        
        return $commit;
        
    } catch (Exception $e) {
        error_log("Error en agregarEntidad: " . $e->getMessage());
        return false;
    }
}

/**
 * Actualiza una entidad existente y sus datos de contacto
 * @param int $codigo Código de la entidad a actualizar
 * @param string $descripcion Nuevo nombre de la entidad
 * @param int $orden Nuevo tipo de entidad
 * @param string $nit Nuevo NIT de la entidad (opcional)
 * @param string $direccion Nueva dirección de la entidad (opcional)
 * @param string $telefono Nuevo teléfono de la entidad (opcional)
 * @param string $pagina_web Nueva página web de la entidad (opcional)
 * @param string $correo Nuevo correo electrónico de la entidad (opcional)
 * @return bool True si se actualizó correctamente, False en caso de error
 */
function actualizarEntidad($codigo, $descripcion, $orden, $nit = null, $direccion = null, $telefono = null, $pagina_web = null, $correo = null) {
    try {
        // Obtener conexión
        $conn = conectarOracle();
        
        // Iniciar transacción
        oci_set_action($conn, 'actualizarEntidad');
        
        // Consulta SQL para actualizar la entidad
        $sql = "UPDATE ENTIDAD 
                SET DESCRIPCION = :descripcion,
                    ORDEN = :orden,
                    NIT = :nit
                WHERE CODIGO = :codigo";
        
        // Preparar consulta
        $stid = oci_parse($conn, $sql);
        if (!$stid) {
            $e = oci_error($conn);
            error_log("Error al preparar consulta de actualización en ENTIDAD: " . $e['message']);
            return false;
        }
        
        // Vincular parámetros
        oci_bind_by_name($stid, ":descripcion", $descripcion);
        oci_bind_by_name($stid, ":orden", $orden);
        oci_bind_by_name($stid, ":nit", $nit);
        oci_bind_by_name($stid, ":codigo", $codigo);
        
        // Ejecutar consulta
        $r = oci_execute($stid, OCI_NO_AUTO_COMMIT);
        if (!$r) {
            $e = oci_error($stid);
            error_log("Error al ejecutar consulta de actualización en ENTIDAD: " . $e['message']);
            oci_rollback($conn);
            return false;
        }
        
        // Verificar si existe un registro en ENTIDAD_CONTACTO para este código
        $stidCheck = oci_parse($conn, "SELECT COUNT(*) AS existe FROM ENTIDAD_CONTACTO WHERE CODIGO = :codigo");
        oci_bind_by_name($stidCheck, ":codigo", $codigo);
        oci_execute($stidCheck);
        $row = oci_fetch_assoc($stidCheck);
        $existe = $row['EXISTE'] > 0;
        
        if ($existe) {
            // Si existe, actualizar
            $sqlContacto = "UPDATE ENTIDAD_CONTACTO 
                            SET DIRECCION = :direccion,
                                TELEFONO = :telefono,
                                PAGINA_WEB = :pagina_web,
                                CORREO = :correo
                            WHERE CODIGO = :codigo";
        } else {
            // Si no existe, insertar
            $sqlContacto = "INSERT INTO ENTIDAD_CONTACTO (CODIGO, DIRECCION, TELEFONO, PAGINA_WEB, CORREO) 
                            VALUES (:codigo, :direccion, :telefono, :pagina_web, :correo)";
        }
        
        // Preparar consulta de contacto
        $stidContacto = oci_parse($conn, $sqlContacto);
        if (!$stidContacto) {
            $e = oci_error($conn);
            error_log("Error al preparar consulta en ENTIDAD_CONTACTO: " . $e['message']);
            oci_rollback($conn);
            return false;
        }
        
        // Vincular parámetros
        oci_bind_by_name($stidContacto, ":codigo", $codigo);
        oci_bind_by_name($stidContacto, ":direccion", $direccion);
        oci_bind_by_name($stidContacto, ":telefono", $telefono);
        oci_bind_by_name($stidContacto, ":pagina_web", $pagina_web);
        oci_bind_by_name($stidContacto, ":correo", $correo);
        
        // Ejecutar consulta
        $rContacto = oci_execute($stidContacto, OCI_NO_AUTO_COMMIT);
        if (!$rContacto) {
            $e = oci_error($stidContacto);
            error_log("Error al ejecutar consulta en ENTIDAD_CONTACTO: " . $e['message']);
            oci_rollback($conn);
            return false;
        }
        
        // Confirmar transacción
        $commit = oci_commit($conn);
        
        // Liberar recursos
        oci_free_statement($stid);
        oci_free_statement($stidContacto);
        oci_close($conn);
        
        return $commit;
        
    } catch (Exception $e) {
        error_log("Error en actualizarEntidad: " . $e->getMessage());
        return false;
    }
}

/**
 * Elimina una entidad y sus datos de contacto
 * @param int $codigo Código de la entidad a eliminar
 * @return bool True si se eliminó correctamente, False en caso de error
 */
function eliminarEntidad($codigo) {
    try {
        // Obtener conexión
        $conn = conectarOracle();
        
        // Iniciar transacción
        oci_set_action($conn, 'eliminarEntidad');
        
        // Primero verificar si la entidad está siendo usada en proyectos
        $stid = oci_parse($conn, "SELECT COUNT(*) AS total FROM ENTE_SUSCRIPTOR WHERE ENTIDAD = :codigo");
        oci_bind_by_name($stid, ":codigo", $codigo);
        oci_execute($stid);
        $row = oci_fetch_assoc($stid);
        
        // Si la entidad está relacionada con proyectos, no permitir eliminar
        if ($row['TOTAL'] > 0) {
            error_log("No se puede eliminar la entidad $codigo porque está siendo usada en proyectos");
            return false;
        }
        
        // Eliminar primero los datos de contacto (para mantener integridad referencial)
        $sqlContacto = "DELETE FROM ENTIDAD_CONTACTO WHERE CODIGO = :codigo";
        $stidContacto = oci_parse($conn, $sqlContacto);
        if (!$stidContacto) {
            $e = oci_error($conn);
            error_log("Error al preparar consulta de eliminación en ENTIDAD_CONTACTO: " . $e['message']);
            return false;
        }
        
        oci_bind_by_name($stidContacto, ":codigo", $codigo);
        $rContacto = oci_execute($stidContacto, OCI_NO_AUTO_COMMIT);
        if (!$rContacto) {
            $e = oci_error($stidContacto);
            error_log("Error al ejecutar consulta de eliminación en ENTIDAD_CONTACTO: " . $e['message']);
            oci_rollback($conn);
            return false;
        }
        
        // Ahora eliminar la entidad
        $sql = "DELETE FROM ENTIDAD WHERE CODIGO = :codigo";
        
        // Preparar consulta
        $stid = oci_parse($conn, $sql);
        if (!$stid) {
            $e = oci_error($conn);
            error_log("Error al preparar consulta de eliminación en ENTIDAD: " . $e['message']);
            oci_rollback($conn);
            return false;
        }
        
        // Vincular parámetros
        oci_bind_by_name($stid, ":codigo", $codigo);
        
        // Ejecutar consulta
        $r = oci_execute($stid, OCI_NO_AUTO_COMMIT);
        if (!$r) {
            $e = oci_error($stid);
            error_log("Error al ejecutar consulta de eliminación en ENTIDAD: " . $e['message']);
            oci_rollback($conn);
            return false;
        }
        
        // Confirmar transacción
        $commit = oci_commit($conn);
        
        // Liberar recursos
        oci_free_statement($stid);
        oci_free_statement($stidContacto);
        oci_close($conn);
        
        return $commit;
        
    } catch (Exception $e) {
        error_log("Error en eliminarEntidad: " . $e->getMessage());
        return false;
    }
}

/**
 * Obtiene una entidad por su código con sus datos de contacto
 * @param int $codigo Código de la entidad
 * @return array|null Datos de la entidad o null si no existe
 */
function obtenerEntidadPorCodigo($codigo) {
    try {
        // Obtener conexión
        $conn = conectarOracle();
        
        // Consulta SQL que une ENTIDAD con ENTIDAD_CONTACTO
        $sql = "SELECT 
                    e.CODIGO as codigo,
                    e.DESCRIPCION as descripcion,
                    e.ORDEN as orden,
                    e.NIT as nit,
                    c.DIRECCION as direccion,
                    c.TELEFONO as telefono,
                    c.PAGINA_WEB as pagina_web,
                    c.CORREO as correo
                FROM 
                    ENTIDAD e
                LEFT JOIN 
                    ENTIDAD_CONTACTO c ON e.CODIGO = c.CODIGO
                WHERE 
                    e.CODIGO = :codigo";
        
        // Preparar consulta
        $stid = oci_parse($conn, $sql);
        if (!$stid) {
            $e = oci_error($conn);
            error_log("Error al preparar consulta: " . $e['message']);
            return null;
        }
        
        // Vincular parámetros
        oci_bind_by_name($stid, ":codigo", $codigo);
        
        // Ejecutar consulta
        $r = oci_execute($stid);
        if (!$r) {
            $e = oci_error($stid);
            error_log("Error al ejecutar consulta: " . $e['message']);
            return null;
        }
        
        // Obtener resultado
        $row = oci_fetch_assoc($stid);
        
        // Convertir claves a minúsculas (para mantener consistencia)
        if ($row) {
            $entidad = array();
            foreach ($row as $key => $value) {
                $entidad[strtolower($key)] = $value;
            }
        } else {
            $entidad = null;
        }
        
        // Liberar recursos
        oci_free_statement($stid);
        oci_close($conn);
        
        return $entidad;
        
    } catch (Exception $e) {
        error_log("Error en obtenerEntidadPorCodigo: " . $e->getMessage());
        return null;
    }
}
?>