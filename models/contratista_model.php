<?php
/**
 * Modelo para gestionar contratistas y su asociación a proyectos
 * Trabaja principalmente con las tablas CONTRATOS_OPS y PROYECTO_OPS
 */

// Incluir el archivo de conexión si no está incluido
if (!function_exists('conectarOracle')) {
    require_once 'config/database.php';
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
        
        // Datos de ejemplo en caso de error
        return [
            ['codigo' => 1, 'descripcion' => 'Natural'],
            ['codigo' => 2, 'descripcion' => 'Jurídica']
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
        
        // Datos de ejemplo en caso de error
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
        
        // Datos de ejemplo en caso de error
        return [
            ['codigo' => 1, 'descripcion' => 'Prestación de Servicios'],
            ['codigo' => 2, 'descripcion' => 'Orden de Servicios'],
            ['codigo' => 3, 'descripcion' => 'Contrato de Trabajo']
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
        
        // Datos de ejemplo en caso de error
        return [
            ['codigo' => 1, 'descripcion' => 'Tiempo Completo'],
            ['codigo' => 2, 'descripcion' => 'Medio Tiempo'],
            ['codigo' => 3, 'descripcion' => 'Por Horas']
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
        error_log("Error en obtenerTipologias: " . $e->getMessage());
        
        // Datos de ejemplo en caso de error
        return [
            ['codigo' => 1, 'descripcion' => 'Investigación'],
            ['codigo' => 2, 'descripcion' => 'Docencia'],
            ['codigo' => 3, 'descripcion' => 'Extensión']
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
            ['codigo' => 2, 'descripcion' => 'Facultad de Ciencias'],
            ['codigo' => 3, 'descripcion' => 'Facultad de Artes']
        ];
    }
}

/**
 * Obtiene la lista de proyectos curriculares
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
            ['codigo' => 3, 'descripcion' => 'Administración de Empresas']
        ];
    }
}

/**
 * Busca un contratista existente por su identificación
 * @param string $identificacion Número de identificación del contratista
 * @return array|null Datos del contratista o null si no existe
 */
function obtenerContratistaExistente($identificacion) {
    try {
        // Obtener conexión
        $conn = conectarOracle();
        
        // Consulta SQL
        $sql = "SELECT 
                    IDENTIFICACION, NOMBRE1, NOMBRE2, APELLIDO1, APELLIDO2,
                    TIPO_PERSONA, TIPO_NACIONALIDAD, FECHA_NACIMIENTO,
                    DIRECCION, TEL_FIJO, TEL_CELULAR, CORREO, GENERO, ESTADO
                FROM 
                    CONTRATOS_OPS
                WHERE 
                    IDENTIFICACION = :id
                    AND ESTADO = 'A'";
        
        // Preparar consulta
        $stid = oci_parse($conn, $sql);
        if (!$stid) {
            $e = oci_error($conn);
            error_log("Error al preparar consulta: " . $e['message']);
            return null;
        }
        
        // Vincular parámetros - AQUÍ ESTÁ EL PROBLEMA - Asegúrate de que usas :id como en la consulta
        oci_bind_by_name($stid, ':id', $identificacion);
        
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
        error_log("Error en obtenerContratistaExistente: " . $e->getMessage());
        return null;
    }
}

/**
 * Actualiza la asociación de un contratista a un proyecto
 * @param array $datos Datos de la asociación
 * @return bool True si se actualizó correctamente, False en caso contrario
 */
function actualizarAsociacionContratista($datos) {
    try {
        // Obtener conexión
        $conn = conectarOracle();
        
        // Calcular duración
        $fecha_inicio = new DateTime($datos['fecha_inicio']);
        $fecha_termina = new DateTime($datos['fecha_terminacion']);
        $duracion = $fecha_inicio->diff($fecha_termina);
        $anio = $duracion->y;
        $mes = $duracion->m;
        $dia = $duracion->d;
        
        // Consulta SQL para actualizar en PROYECTO_OPS
        $sql = "UPDATE PROYECTO_OPS SET
                    TIPO_CONTRATO = :tipo_contrato,
                    TIPO_VINCULACION = :tipo_vinculacion,
                    TIPOLOGIA = :tipologia,
                    TIPO_PROCESO = :tipo_proceso,
                    FACULTAD = :facultad,
                    PROYECTO_CURRICULAR = :proyecto_curricular,
                    OBJETO = :objeto,
                    FECHA_SUSCRIPCION = TO_DATE(:fecha_suscripcion, 'YYYY-MM-DD'),
                    FECHA_INICIO = TO_DATE(:fecha_inicio, 'YYYY-MM-DD'),
                    FECHA_TERMINACION = TO_DATE(:fecha_terminacion, 'YYYY-MM-DD'),
                    VALOR = :valor,
                    ANIO = :anio,
                    MES = :mes,
                    DIA = :dia
                WHERE 
                    ANIO_PRO = :anio_pro
                    AND NUMERO_PRO = :numero_pro
                    AND IDENTIFICACION = :identificacion
                    AND NUMERO_CONTRATO = :numero_contrato";
        
        // Preparar consulta
        $stid = oci_parse($conn, $sql);
        if (!$stid) {
            $e = oci_error($conn);
            error_log("Error al preparar consulta de actualización: " . $e['message']);
            return false;
        }
        
        // Vincular parámetros
        oci_bind_by_name($stid, ':tipo_contrato', $datos['tipo_contrato']);
        oci_bind_by_name($stid, ':tipo_vinculacion', $datos['tipo_vinculacion']);
        oci_bind_by_name($stid, ':tipologia', $datos['tipologia']);
        oci_bind_by_name($stid, ':tipo_proceso', $datos['tipo_proceso']);
        oci_bind_by_name($stid, ':facultad', $datos['facultad']);
        oci_bind_by_name($stid, ':proyecto_curricular', $datos['proyecto_curricular']);
        oci_bind_by_name($stid, ':objeto', $datos['objeto']);
        oci_bind_by_name($stid, ':fecha_suscripcion', $datos['fecha_suscripcion']);
        oci_bind_by_name($stid, ':fecha_inicio', $datos['fecha_inicio']);
        oci_bind_by_name($stid, ':fecha_terminacion', $datos['fecha_terminacion']);
        oci_bind_by_name($stid, ':valor', $datos['valor']);
        oci_bind_by_name($stid, ':anio', $anio);
        oci_bind_by_name($stid, ':mes', $mes);
        oci_bind_by_name($stid, ':dia', $dia);
        oci_bind_by_name($stid, ':anio_pro', $datos['anio_pro']);
        oci_bind_by_name($stid, ':numero_pro', $datos['numero_pro']);
        oci_bind_by_name($stid, ':identificacion', $datos['identificacion']);
        oci_bind_by_name($stid, ':numero_contrato', $datos['numero_contrato']);
        
        // Ejecutar consulta
        $r = oci_execute($stid);
        if (!$r) {
            $e = oci_error($stid);
            error_log("Error al ejecutar consulta de actualización: " . $e['message']);
            return false;
        }
        
        // Confirmar transacción
        $commit = oci_commit($conn);
        
        // Liberar recursos
        oci_free_statement($stid);
        oci_close($conn);
        
        return $commit;
        
    } catch (Exception $e) {
        error_log("Error en actualizarAsociacionContratista: " . $e->getMessage());
        return false;
    }
}

/**
 * Elimina la asociación de un contratista a un proyecto
 * @param string $identificacion Identificación del contratista
 * @param int $numero_pro Número del proyecto
 * @param int $anio_pro Año del proyecto
 * @param int $numero_contrato Número del contrato
 * @return bool True si se eliminó correctamente, False en caso contrario
 */
function eliminarAsociacionContratista($identificacion, $numero_pro, $anio_pro, $numero_contrato) {
    try {
        // Obtener conexión
        $conn = conectarOracle();
        
        // Consulta SQL para actualizar el estado a inactivo (no eliminamos físicamente)
        $sql = "UPDATE PROYECTO_OPS SET
                    ESTADO = 'I'
                WHERE 
                    ANIO_PRO = :anio_pro
                    AND NUMERO_PRO = :numero_pro
                    AND IDENTIFICACION = :identificacion
                    AND NUMERO_CONTRATO = :numero_contrato";
        
        // Preparar consulta
        $stid = oci_parse($conn, $sql);
        if (!$stid) {
            $e = oci_error($conn);
            error_log("Error al preparar consulta de eliminación: " . $e['message']);
            return false;
        }
        
        // Vincular parámetros
        oci_bind_by_name($stid, ':anio_pro', $anio_pro);
        oci_bind_by_name($stid, ':numero_pro', $numero_pro);
        oci_bind_by_name($stid, ':identificacion', $identificacion);
        oci_bind_by_name($stid, ':numero_contrato', $numero_contrato);
        
        // Ejecutar consulta
        $r = oci_execute($stid);
        if (!$r) {
            $e = oci_error($stid);
            error_log("Error al ejecutar consulta de eliminación: " . $e['message']);
            return false;
        }
        
        // Confirmar transacción
        $commit = oci_commit($conn);
        
        // Liberar recursos
        oci_free_statement($stid);
        oci_close($conn);
        
        return $commit;
        
    } catch (Exception $e) {
        error_log("Error en eliminarAsociacionContratista: " . $e->getMessage());
        return false;
    }
}

/**
 * Obtiene los detalles de la asociación de un contratista a un proyecto
 * @param string $identificacion Identificación del contratista
 * @param int $numero_pro Número del proyecto
 * @param int $anio_pro Año del proyecto
 * @param int $numero_contrato Número del contrato (opcional)
 * @return array|null Datos de la asociación o null si no existe
 */
function obtenerDetalleAsociacionContratista($identificacion, $numero_pro, $anio_pro, $numero_contrato = null) {
    try {
        // Obtener conexión
        $conn = conectarOracle();
        
        // Consulta SQL
        $sql = "SELECT 
                    p.ANIO_PRO, p.NUMERO_PRO, p.IDENTIFICACION, p.NUMERO_CONTRATO,
                    p.CODIGO_ENTIDAD, p.VIGENCIA_FISCAL, p.TIPO_REGISTRO, p.TIPO_MODIFICACION,
                    p.TIPO_CONFIGURACION, p.TIPOLOGIA, p.TIPO_PROCESO, p.NUMERO_PUBLICACION,
                    p.FECHA_PUBLICACION, p.TIPO_CONTRATO, p.TIPO_VINCULACION, p.FACULTAD,
                    p.PROYECTO_CURRICULAR, p.OBJETO, p.FECHA_SUSCRIPCION, p.FECHA_INICIO,
                    p.FECHA_TERMINACION, p.VALOR, p.TIPO_MONEDA, p.TIPO_TASA,
                    p.ANIO, p.MES, p.DIA, p.HORA, p.SITUACION_CONTRATO, 
                    p.OBSERVACIONES, p.UNIDAD, p.ESTADO,
                    tc.DESCRIPCION as TIPO_CONTRATO_DESC,
                    tv.DESCRIPCION as TIPO_VINCULACION_DESC,
                    tt.DESCRIPCION as TIPOLOGIA_DESC,
                    f.DESCRIPCION as FACULTAD_DESC,
                    pc.DESCRIPCION as PROYECTO_CURRICULAR_DESC
                FROM 
                    PROYECTO_OPS p
                LEFT JOIN 
                    TIPO_CONTRATO tc ON p.TIPO_CONTRATO = tc.CODIGO
                LEFT JOIN 
                    VINCULACION tv ON p.TIPO_VINCULACION = tv.CODIGO
                LEFT JOIN 
                    SIV_TIPOLOGIA tt ON p.TIPOLOGIA = tt.CODIGO
                LEFT JOIN 
                    FACULTAD f ON p.FACULTAD = f.CODIGO
                LEFT JOIN 
                    PROYECTO_CURRICULAR pc ON p.PROYECTO_CURRICULAR = pc.CODIGO
                WHERE 
                    p.ANIO_PRO = :anio_pro
                    AND p.NUMERO_PRO = :numero_pro
                    AND p.IDENTIFICACION = :identificacion
                    AND p.ESTADO = 'A'";
        
        // Añadir filtro de número de contrato si se proporciona
        if ($numero_contrato) {
            $sql .= " AND p.NUMERO_CONTRATO = :numero_contrato";
        }
        
        // Preparar consulta
        $stid = oci_parse($conn, $sql);
        if (!$stid) {
            $e = oci_error($conn);
            error_log("Error al preparar consulta: " . $e['message']);
            return null;
        }
        
        // Vincular parámetros
        oci_bind_by_name($stid, ':anio_pro', $anio_pro);
        oci_bind_by_name($stid, ':numero_pro', $numero_pro);
        oci_bind_by_name($stid, ':identificacion', $identificacion);
        if ($numero_contrato) {
            oci_bind_by_name($stid, ':numero_contrato', $numero_contrato);
        }
        
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
            $asociacion = array();
            foreach ($row as $key => $value) {
                $asociacion[strtolower($key)] = $value;
            }
        } else {
            $asociacion = null;
        }
        
        // Liberar recursos
        oci_free_statement($stid);
        oci_close($conn);
        
        return $asociacion;
        
    } catch (Exception $e) {
        error_log("Error en obtenerDetalleAsociacionContratista: " . $e->getMessage());
        return null;
    }
}

/**
 * Obtiene todas las asociaciones de un contratista a proyectos
 * @param string $identificacion Identificación del contratista
 * @return array Lista de asociaciones
 */
function obtenerAsociacionesContratista($identificacion) {
    try {
        // Obtener conexión
        $conn = conectarOracle();
        
        // Consulta SQL
        $sql = "SELECT 
                    p.ANIO_PRO, p.NUMERO_PRO, p.IDENTIFICACION, p.NUMERO_CONTRATO,
                    p.OBJETO, p.FECHA_INICIO, p.FECHA_TERMINACION, p.VALOR,
                    tc.DESCRIPCION as TIPO_CONTRATO_DESC,
                    pr.NOMBRE as NOMBRE_PROYECTO,
                    e.DESCRIPCION as ENTIDAD_DESC
                FROM 
                    PROYECTO_OPS p
                LEFT JOIN 
                    TIPO_CONTRATO tc ON p.TIPO_CONTRATO = tc.CODIGO
                LEFT JOIN 
                    PROYECTO pr ON p.NUMERO_PRO = pr.NUMERO_PRO AND p.ANIO_PRO = pr.ANIO_PRO
                LEFT JOIN 
                    ENTE_SUSCRIPTOR es ON pr.NUMERO_PRO = es.NUMERO_PRO AND pr.ANIO_PRO = es.ANIO_PRO
                LEFT JOIN 
                    ENTIDAD e ON es.ENTIDAD = e.CODIGO
                WHERE 
                    p.IDENTIFICACION = :identificacion
                    AND p.ESTADO = 'A'
                ORDER BY 
                    p.FECHA_INICIO DESC";
        
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
        $asociaciones = [];
        
        while ($row = oci_fetch_assoc($stid)) {
            // Convertir claves a minúsculas
            $asociacion = array();
            foreach ($row as $key => $value) {
                $asociacion[strtolower($key)] = $value;
            }
            $asociaciones[] = $asociacion;
        }
        
        // Liberar recursos
        oci_free_statement($stid);
        oci_close($conn);
        
        return $asociaciones;
        
    } catch (Exception $e) {
        error_log("Error en obtenerAsociacionesContratista: " . $e->getMessage());
        return [];
    }
}

/**
 * Obtiene el siguiente número de contrato disponible para un proyecto y contratista
 * @param int $anio_pro Año del proyecto
 * @param int $numero_pro Número del proyecto
 * @return int Siguiente número de contrato
 */
function obtenerSiguienteNumeroContrato($anio_pro, $numero_pro) {
    try {
        // Obtener conexión
        $conn = conectarOracle();
        
        // Consulta SQL para obtener el máximo número de contrato
        $sql = "SELECT NVL(MAX(NUMERO_CONTRATO), 0) + 1 AS siguiente_numero 
                FROM PROYECTO_OPS 
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
        error_log("Error en obtenerSiguienteNumeroContrato: " . $e->getMessage());
        return 1; // Por defecto, empezar desde 1
    }
}

?> 