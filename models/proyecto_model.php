<?php
// Incluir el archivo de conexión
require_once 'config/database.php';

/**
 * Cuenta el número de proyectos suscritos (donde SITUACION = 8)
 * @return int Número de proyectos
 */


 
//PROYECTOS EN SUSCRIPCIÓN
function contarProyectosSuscritos() {
    try {
        // Obtener conexión usando la función correcta de tu archivo database.php
        $conn = conectarOracle();
        
        // Consulta SQL para contar proyectos con SITUACION = 8
        $sql = "SELECT COUNT(*) AS total FROM PROYECTO WHERE SITUACION = 8 AND ESTADO = 'A'";
        
        // Preparar y ejecutar la consulta
        $stid = oci_parse($conn, $sql);
        if (!$stid) {
            $e = oci_error($conn);
            error_log("Error al preparar consulta: " . $e['message']);
            return 0;
        }
        
        $r = oci_execute($stid);
        if (!$r) {
            $e = oci_error($stid);
            error_log("Error al ejecutar consulta: " . $e['message']);
            return 0;
        }
        
        // Obtener resultado
        $row = oci_fetch_assoc($stid);
        $total = isset($row['TOTAL']) ? $row['TOTAL'] : 0;
        
        // Liberar recursos
        oci_free_statement($stid);
        oci_close($conn);
        
        return $total;
        
    } catch (Exception $e) {
        error_log("Error en contarProyectosSuscritos: " . $e->getMessage());
        return 0;
    }
}

//PROYECTOS EN SUSCRITOS EN EL AÑO ACTUAL
function contarProyectosSuscritosAnioActual() {
    try {
        // Obtener conexión
        $conn = conectarOracle();
        
        // Consulta SQL para contar proyectos suscritos en el año actual con estado = 'A'
        $sql = "SELECT COUNT(*) AS total 
                FROM PROYECTO 
                WHERE EXTRACT(YEAR FROM FECHA_SUSCRIPCION) = EXTRACT(YEAR FROM SYSDATE)
                AND ESTADO = 'A'";
        
        // Preparar y ejecutar la consulta
        $stid = oci_parse($conn, $sql);
        if (!$stid) {
            $e = oci_error($conn);
            error_log("Error al preparar consulta: " . $e['message']);
            return 0;
        }
        
        $r = oci_execute($stid);
        if (!$r) {
            $e = oci_error($stid);
            error_log("Error al ejecutar consulta: " . $e['message']);
            return 0;
        }
        
        // Obtener resultado
        $row = oci_fetch_assoc($stid);
        $total = isset($row['TOTAL']) ? $row['TOTAL'] : 0;
        
        // Liberar recursos
        oci_free_statement($stid);
        oci_close($conn);
        
        return $total;
        
    } catch (Exception $e) {
        error_log("Error en contarProyectosSuscritosAnioActual: " . $e->getMessage());
        return 0; // Valor por defecto en caso de error
    }
}



 //VALOR DE PROYECTOS EN SUSCRITOS EN EL AÑO ACTUAL 
 function obtenerValorProyectosSuscritosAnioActual() {
    try {
        // Obtener conexión
        $conn = conectarOracle();
        
        // Consulta SQL para sumar el valor de proyectos suscritos en el año actual con estado = 'A'
        $sql = "SELECT SUM(VALOR) AS total 
                FROM PROYECTO 
                WHERE EXTRACT(YEAR FROM FECHA_SUSCRIPCION) = EXTRACT(YEAR FROM SYSDATE)
                AND ESTADO = 'A'";
        
        // Preparar y ejecutar la consulta
        $stid = oci_parse($conn, $sql);
        if (!$stid) {
            $e = oci_error($conn);
            error_log("Error al preparar consulta: " . $e['message']);
            return 0;
        }
        
        $r = oci_execute($stid);
        if (!$r) {
            $e = oci_error($stid);
            error_log("Error al ejecutar consulta: " . $e['message']);
            return 0;
        }
        
        // Obtener resultado
        $row = oci_fetch_assoc($stid);
        $total = isset($row['TOTAL']) ? $row['TOTAL'] : 0;
        
        // Liberar recursos
        oci_free_statement($stid);
        oci_close($conn);
        
        return $total;
        
    } catch (Exception $e) {
        error_log("Error en obtenerValorProyectosSuscritosAnioActual: " . $e->getMessage());
        return 0; // Valor por defecto en caso de error
    }
}



//PROYECTOS EN EJECUCIÓN
function contarProyectosEjecucion() {
    try {
        // Obtener conexión usando la función correcta de tu archivo database.php
        $conn = conectarOracle();
        
        // Consulta SQL para contar proyectos con SITUACION = 14
        $sql = "SELECT COUNT(*) AS total FROM PROYECTO WHERE SITUACION = 14 AND ESTADO = 'A'";
        
        // Preparar y ejecutar la consulta
        $stid = oci_parse($conn, $sql);
        if (!$stid) {
            $e = oci_error($conn);
            error_log("Error al preparar consulta: " . $e['message']);
            return 0;
        }
        
        $r = oci_execute($stid);
        if (!$r) {
            $e = oci_error($stid);
            error_log("Error al ejecutar consulta: " . $e['message']);
            return 0;
        }
        
        // Obtener resultado
        $row = oci_fetch_assoc($stid);
        $total = isset($row['TOTAL']) ? $row['TOTAL'] : 0;
        
        // Liberar recursos
        oci_free_statement($stid);
        oci_close($conn);
        
        return $total;
        
    } catch (Exception $e) {
        error_log("Error en contarProyectosEjecucion: " . $e->getMessage());
        return 0; // Valor por defecto en caso de error
    }
}
/**
 * Obtiene la suma de valor de proyectos agrupados por año de suscripción
 * @param int $numAnios Número de años a consultar (últimos N años)
 * @return array Datos agrupados por año
 */
//DATOS PARA GRÁFICA POR AÑOS
function obtenerValoresPorAnio($numAnios = 10) {
    try {
        // Obtener conexión
        $conn = conectarOracle();
        
        // Consulta SQL para obtener suma de valores por año
        $sql = "SELECT 
                    EXTRACT(YEAR FROM FECHA_SUSCRIPCION) AS anio,
                    SUM(VALOR) AS valor_total
                FROM 
                    PROYECTO
                WHERE 
                    EXTRACT(YEAR FROM FECHA_SUSCRIPCION) >= (EXTRACT(YEAR FROM SYSDATE) - :num_anios)
                    AND ESTADO = 'A'
                GROUP BY 
                    EXTRACT(YEAR FROM FECHA_SUSCRIPCION)
                ORDER BY 
                    anio ASC";
        
        // Preparar consulta
        $stid = oci_parse($conn, $sql);
        if (!$stid) {
            $e = oci_error($conn);
            error_log("Error al preparar consulta: " . $e['message']);
            return [];
        }
        
        // Vincular parámetro
        oci_bind_by_name($stid, ":num_anios", $numAnios);
        
        // Ejecutar consulta
        $r = oci_execute($stid);
        if (!$r) {
            $e = oci_error($stid);
            error_log("Error al ejecutar consulta: " . $e['message']);
            return [];
        }
        
        // Procesar resultados
        $anios = [];
        $valores = [];
        
        while ($row = oci_fetch_assoc($stid)) {
            $anios[] = $row['ANIO'];
            $valores[] = floatval($row['VALOR_TOTAL']);
        }
        
        // Liberar recursos
        oci_free_statement($stid);
        oci_close($conn);
        
        return [
            'anios' => $anios,
            'valores' => $valores
        ];
        
    } catch (Exception $e) {
        error_log("Error en obtenerValoresPorAnio: " . $e->getMessage());
        
        // Retornar datos de ejemplo en caso de error
        return [
            'anios' => ['2020', '2021', '2022', '2023', '2024'],
            'valores' => [1500000, 2200000, 1800000, 2500000, 3100000]
        ];
    }
}



function obtenerCantidadProyectosPorAnio($numAnios = 10) {
    try {
        // Obtener conexión
        $conn = conectarOracle();
        
        // Consulta SQL para obtener cantidad de proyectos por año
        $sql = "SELECT 
                    EXTRACT(YEAR FROM FECHA_SUSCRIPCION) AS anio,
                    COUNT(*) AS cantidad
                FROM 
                    PROYECTO
                WHERE 
                    EXTRACT(YEAR FROM FECHA_SUSCRIPCION) >= (EXTRACT(YEAR FROM SYSDATE) - :num_anios)
                    AND ESTADO = 'A'
                GROUP BY 
                    EXTRACT(YEAR FROM FECHA_SUSCRIPCION)
                ORDER BY 
                    anio ASC";
        
        // Preparar consulta
        $stid = oci_parse($conn, $sql);
        if (!$stid) {
            $e = oci_error($conn);
            error_log("Error al preparar consulta: " . $e['message']);
            return [];
        }
        
        // Vincular parámetro
        oci_bind_by_name($stid, ":num_anios", $numAnios);
        
        // Ejecutar consulta
        $r = oci_execute($stid);
        if (!$r) {
            $e = oci_error($stid);
            error_log("Error al ejecutar consulta: " . $e['message']);
            return [];
        }
        
        // Procesar resultados
        $anios = [];
        $cantidades = [];
        
        while ($row = oci_fetch_assoc($stid)) {
            $anios[] = $row['ANIO'];
            $cantidades[] = intval($row['CANTIDAD']);
        }
        
        // Liberar recursos
        oci_free_statement($stid);
        oci_close($conn);
        
        return [
            'anios' => $anios,
            'cantidades' => $cantidades
        ];
        
    } catch (Exception $e) {
        error_log("Error en obtenerCantidadProyectosPorAnio: " . $e->getMessage());
        
        // Retornar datos de ejemplo en caso de error
        return [
            'anios' => ['2015', '2016', '2017', '2018', '2019', '2020', '2021', '2022', '2023', '2024'],
            'cantidades' => [5, 8, 10, 12, 9, 15, 20, 18, 25, 22]
        ];
    }
}



function obtenerProyectosEntidades() {
    try {
        // Obtener conexión
        $conn = conectarOracle();
        
        // Consulta SQL modificada para seleccionar solo las columnas necesarias
        $sql = "SELECT 
                    p.NOMBRE as nombre_proyecto, 
                    en.NUMERO_PRO_ENTIDAD as codigo, 
                    e.DESCRIPCION as nombre_entidad,
                    p.FECHA_INICIO as fecha_inicio,
                    p.FECHA_TERMINA as fecha_fin
                FROM 
                    PROYECTO p, 
                    ENTIDAD e, 
                    ENTE_SUSCRIPTOR en
                WHERE 
                    en.NUMERO_PRO = p.NUMERO_PRO 
                    AND e.CODIGO = en.ENTIDAD
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
                'nombre_proyecto' => $row['NOMBRE_PROYECTO'],
                'codigo' => $row['CODIGO'],
                'nombre_entidad' => $row['NOMBRE_ENTIDAD'],
                'fecha_inicio' => $row['FECHA_INICIO'],
                'fecha_fin' => $row['FECHA_FIN']
            ];
        }
        
        // Liberar recursos
        oci_free_statement($stid);
        oci_close($conn);
        
        return $proyectos;
        
    } catch (Exception $e) {
        error_log("Error en obtenerProyectosEntidades: " . $e->getMessage());
        
        // Retornar datos de ejemplo en caso de error
        return [
            [
                'nombre_proyecto' => 'Desarrollo de Software Educativo',
                'codigo' => 'PRY-2024-01',
                'nombre_entidad' => 'Ministerio de Educación',
                'fecha_inicio' => '2024-01-15',
                'fecha_fin' => '2024-12-30'
            ],
            [
                'nombre_proyecto' => 'Infraestructura Vial Norte',
                'codigo' => 'PRY-2023-15',
                'nombre_entidad' => 'Alcaldía de Bogotá',
                'fecha_inicio' => '2023-06-10',
                'fecha_fin' => '2024-08-15'
            ],
            [
                'nombre_proyecto' => 'Sistema de Gestión Ambiental',
                'codigo' => 'PRY-2023-28',
                'nombre_entidad' => 'Gobernación del Valle',
                'fecha_inicio' => '2023-09-20',
                'fecha_fin' => '2024-03-30'
            ]
        ];
    }
}

function obtenerProyectosPorEntidad() {
    try {
        // Obtener conexión
        $conn = conectarOracle();
        
        // Consulta SQL para contar proyectos por entidad
        $sql = "SELECT 
                    e.DESCRIPCION as entidad,
                    COUNT(p.NUMERO_PRO) as cantidad
                FROM 
                    PROYECTO p
                    JOIN ENTE_SUSCRIPTOR en ON en.NUMERO_PRO = p.NUMERO_PRO
                    JOIN ENTIDAD e ON e.CODIGO = en.ENTIDAD
                WHERE 
                    p.ESTADO = 'A'
                GROUP BY 
                    e.DESCRIPCION
                ORDER BY 
                    cantidad DESC";
        
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
        $proyectosPorEntidad = [];
        
        while ($row = oci_fetch_assoc($stid)) {
            $proyectosPorEntidad[] = [
                'entidad' => $row['ENTIDAD'],
                'cantidad' => $row['CANTIDAD']
            ];
        }
        
        // Liberar recursos
        oci_free_statement($stid);
        oci_close($conn);
        
        return $proyectosPorEntidad;
        
    } catch (Exception $e) {
        error_log("Error en obtenerProyectosPorEntidad: " . $e->getMessage());
        
        // Retornar datos de ejemplo en caso de error
        return [
            ['entidad' => 'Ministerio de Educación', 'cantidad' => 15],
            ['entidad' => 'Alcaldía de Bogotá', 'cantidad' => 12],
            ['entidad' => 'Gobernación del Valle', 'cantidad' => 8],
            ['entidad' => 'Secretaría de Educación', 'cantidad' => 7],
            ['entidad' => 'Instituto de Desarrollo Urbano', 'cantidad' => 5]
        ];
    }
}


function obtenerTodosProyectos() {
    try {
        // Obtener conexión
        $conn = conectarOracle();
        
        // Consulta SQL
        $sql = "SELECT 
                    p.ANIO_PRO as anio,
                    en.NUMERO_PRO_ENTIDAD as numero,
                    p.NOMBRE as nombre,
                    e.DESCRIPCION as entidad,
                    s.DESCRIPCION as situacion,
                    p.NUMERO_PRO as id
                FROM 
                    PROYECTO p, 
                    ENTIDAD e, 
                    ENTE_SUSCRIPTOR en, 
                    SITUACION s 
                WHERE
                    en.NUMERO_PRO = p.NUMERO_PRO AND
                    e.CODIGO = en.ENTIDAD AND
                    s.CODIGO = p.SITUACION AND
                    p.ESTADO = 'A'
                ORDER BY 
                    p.ANIO_PRO DESC, 
                    en.NUMERO_PRO_ENTIDAD ASC";
        
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
                'anio' => $row['ANIO'],
                'numero' => $row['NUMERO'],
                'nombre' => $row['NOMBRE'],
                'entidad' => $row['ENTIDAD'],
                'situacion' => $row['SITUACION'],
                'id' => $row['ID']
            ];
        }
        
        // Liberar recursos
        oci_free_statement($stid);
        oci_close($conn);
        
        return $proyectos;
        
    } catch (Exception $e) {
        error_log("Error en obtenerTodosProyectos: " . $e->getMessage());
        
        // Datos de ejemplo en caso de error
        return [
            [
                'anio' => 2024,
                'numero' => 'PRY-2024-001',
                'nombre' => 'Desarrollo Sistema de Gestión Documental',
                'entidad' => 'Ministerio de Educación Nacional',
                'situacion' => 'En ejecución',
                'id' => 1
            ],
            [
                'anio' => 2023,
                'numero' => 'PRY-2023-052',
                'nombre' => 'Implementación Plataforma de Capacitación Virtual',
                'entidad' => 'Secretaría de Educación Distrital',
                'situacion' => 'Finalizado',
                'id' => 2
            ],
            [
                'anio' => 2023,
                'numero' => 'PRY-2023-018',
                'nombre' => 'Estudio de Factibilidad Infraestructura Vial',
                'entidad' => 'Instituto de Desarrollo Urbano',
                'situacion' => 'Suscrito',
                'id' => 3
            ]
        ];
    }
}

/**
 * Obtiene la lista de años disponibles para los proyectos
 * @return array Lista de años
 */
function obtenerListaAnios() {
    try {
        // Obtener conexión
        $conn = conectarOracle();
        
        // Consulta SQL
        $sql = "SELECT DISTINCT ANIO_PRO as anio
                FROM PROYECTO
                WHERE ESTADO = 'A'
                ORDER BY ANIO_PRO DESC";
        
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
        $anios = [];
        
        while ($row = oci_fetch_assoc($stid)) {
            $anios[] = $row['ANIO'];
        }
        
        // Liberar recursos
        oci_free_statement($stid);
        oci_close($conn);
        
        return $anios;
        
    } catch (Exception $e) {
        error_log("Error en obtenerListaAnios: " . $e->getMessage());
        
        // Datos de ejemplo en caso de error
        return [2022, 2023, 2024];
    }
}

/**
 * Obtiene la lista de entidades disponibles
 * @return array Lista de entidades
 */
function obtenerListaEntidades() {
    try {
        // Obtener conexión
        $conn = conectarOracle();
        
        // Consulta SQL
        $sql = "SELECT DISTINCT e.DESCRIPCION as entidad
                FROM ENTIDAD e
                JOIN ENTE_SUSCRIPTOR en ON e.CODIGO = en.ENTIDAD
                JOIN PROYECTO p ON en.NUMERO_PRO = p.NUMERO_PRO
                WHERE p.ESTADO = 'A'
                ORDER BY e.DESCRIPCION";
        
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
            $entidades[] = $row['ENTIDAD'];
        }
        
        // Liberar recursos
        oci_free_statement($stid);
        oci_close($conn);
        
        return $entidades;
        
    } catch (Exception $e) {
        error_log("Error en obtenerListaEntidades: " . $e->getMessage());
        
        // Datos de ejemplo en caso de error
        return [
            'Ministerio de Educación Nacional', 
            'Secretaría de Educación Distrital', 
            'Instituto de Desarrollo Urbano', 
            'Secretaría Distrital de Ambiente', 
            'Alcaldía Mayor de Bogotá'
        ];
    }
}

/**
 * Obtiene la lista de situaciones disponibles
 * @return array Lista de situaciones
 */
function obtenerListaSituaciones() {
    try {
        // Obtener conexión
        $conn = conectarOracle();
        
        // Consulta SQL
        $sql = "SELECT DISTINCT s.DESCRIPCION as situacion
                FROM SITUACION s
                JOIN PROYECTO p ON s.CODIGO = p.SITUACION
                WHERE p.ESTADO = 'A'
                ORDER BY s.DESCRIPCION";
        
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
            $situaciones[] = $row['SITUACION'];
        }
        
        // Liberar recursos
        oci_free_statement($stid);
        oci_close($conn);
        
        return $situaciones;
        
    } catch (Exception $e) {
        error_log("Error en obtenerListaSituaciones: " . $e->getMessage());
        
        // Datos de ejemplo en caso de error
        return ['En ejecución', 'Finalizado', 'Suscrito'];
    }
}

function obtenerProyectoDetalle($id) {
    try {
        // Obtener conexión
        $conn = conectarOracle();
        
        // Consulta SQL que mantiene la estructura original que ya funciona
        $sql = "SELECT 
                    p.NUMERO_PRO as numero_pro,
                    p.ANIO_PRO as anio_pro,
                    p.NOMBRE as nombre,
                    p.OBJETO as objeto,
                    p.FINANCIACION as financiacion,
                    p.TIPO_PROYECTO as tipo_proyecto,
                    p.MODALIDAD as modalidad,
                    p.VALOR as valor,
                    p.APORTE_UD as aporte_ud,
                    p.FECHA_INICIO as fecha_inicio,
                    p.FECHA_TERMINA as fecha_termina,
                    p.SITUACION as situacion,
                    p.BENEFICIARIOS as beneficiarios,
                    p.OBSERVACIONES as observaciones,
                    p.ESTADO as estado,
                    p.CODIGO_TESORAL as codigo_tesoral,
                    p.CODIGO_BANCARIO as codigo_bancario,
                    p.FECHA_SUSCRIPCION as fecha_suscripcion,
                    p.USUARIO as usuario,
                    p.UNIDAD as unidad,
                    p.ORDENADOR_GASTO as ordenador_gasto,
                    p.ANIO as anio,
                    p.MES as mes,
                    p.DIA as dia,
                    p.HORA as hora,
                    p.TIPO_CUENTA as tipo_cuenta,
                    p.NUMERO_CUENTA as numero_cuenta,
                    p.REFERENCIA_UD as referencia_ud,
                    p.DATOS_ADICIONALES as datos_adicionales,
                    p.COORDINADOR as coordinador,
                    p.EMAIL_MAIN as email_main,
                    p.EMAIL_SECUNDARIOS as email_secundarios,
                    p.ENLACE_SECOP as enlace_secop,
                    p.SUPERVISOR as supervisor,
                    p.ACTO_ADMINISTRATIVO as acto_administrativo,
                    (SELECT es.NUMERO_PRO_ENTIDAD FROM ENTE_SUSCRIPTOR es WHERE es.NUMERO_PRO = p.NUMERO_PRO AND ROWNUM = 1) as numero_pro_entidad,
                    (SELECT e.DESCRIPCION FROM ENTIDAD e JOIN ENTE_SUSCRIPTOR es ON e.CODIGO = es.ENTIDAD WHERE es.NUMERO_PRO = p.NUMERO_PRO AND ROWNUM = 1) as entidad
                FROM 
                    PROYECTO p
                WHERE 
                    p.NUMERO_PRO = :id";
        
        // Preparar la consulta
        $stmt = oci_parse($conn, $sql);
        if (!$stmt) {
            $e = oci_error($conn);
            error_log("Error al preparar consulta: " . $e['message']);
            return null;
        }
        
        // Vincular parámetros
        oci_bind_by_name($stmt, ':id', $id);
        
        // Ejecutar la consulta
        $r = oci_execute($stmt);
        if (!$r) {
            $e = oci_error($stmt);
            error_log("Error al ejecutar consulta: " . $e['message']);
            return null;
        }
        
        // Obtener resultados
        $proyecto = oci_fetch_assoc($stmt);
        
        // Convertir claves a minúsculas (para mantener consistencia)
        if ($proyecto) {
            $resultado = array();
            foreach ($proyecto as $key => $value) {
                $resultado[strtolower($key)] = $value;
            }
            $proyecto = $resultado;
            
            // Ahora intentamos obtener las descripciones adicionales sin romper la consulta principal
            
            // 1. Obtener descripción de Financiación
            if (isset($proyecto['financiacion']) && $proyecto['financiacion']) {
                try {
                    $sqlFinanciacion = "SELECT DESCRIPCION FROM FINANCIACION WHERE CODIGO = :codigo";
                    $stmtFinanciacion = oci_parse($conn, $sqlFinanciacion);
                    oci_bind_by_name($stmtFinanciacion, ':codigo', $proyecto['financiacion']);
                    $rFinanciacion = @oci_execute($stmtFinanciacion);
                    
                    if ($rFinanciacion) {
                        $rowFinanciacion = oci_fetch_assoc($stmtFinanciacion);
                        if ($rowFinanciacion) {
                            $proyecto['financiacion_descripcion'] = $rowFinanciacion['DESCRIPCION'];
                        }
                    }
                    @oci_free_statement($stmtFinanciacion);
                } catch (Exception $e) {
                    // Si algo falla, simplemente continuamos sin la descripción
                }
            }
            
            // 2. Obtener descripción de Modalidad
            if (isset($proyecto['modalidad']) && $proyecto['modalidad']) {
                try {
                    $sqlModalidad = "SELECT DESCRIPCION FROM MODALIDAD WHERE CODIGO = :codigo";
                    $stmtModalidad = oci_parse($conn, $sqlModalidad);
                    oci_bind_by_name($stmtModalidad, ':codigo', $proyecto['modalidad']);
                    $rModalidad = @oci_execute($stmtModalidad);
                    
                    if ($rModalidad) {
                        $rowModalidad = oci_fetch_assoc($stmtModalidad);
                        if ($rowModalidad) {
                            $proyecto['modalidad_descripcion'] = $rowModalidad['DESCRIPCION'];
                        }
                    }
                    @oci_free_statement($stmtModalidad);
                } catch (Exception $e) {
                    // Si algo falla, simplemente continuamos sin la descripción
                }
            }
            
            // 3. Obtener descripción de Situación
            if (isset($proyecto['situacion']) && $proyecto['situacion']) {
                try {
                    $sqlSituacion = "SELECT DESCRIPCION FROM SITUACION WHERE CODIGO = :codigo";
                    $stmtSituacion = oci_parse($conn, $sqlSituacion);
                    oci_bind_by_name($stmtSituacion, ':codigo', $proyecto['situacion']);
                    $rSituacion = @oci_execute($stmtSituacion);
                    
                    if ($rSituacion) {
                        $rowSituacion = oci_fetch_assoc($stmtSituacion);
                        if ($rowSituacion) {
                            $proyecto['situacion_descripcion'] = $rowSituacion['DESCRIPCION'];
                        }
                    }
                    @oci_free_statement($stmtSituacion);
                } catch (Exception $e) {
                    // Si algo falla, simplemente continuamos sin la descripción
                }
            }
            
            // 4. Obtener descripción de Unidad
            if (isset($proyecto['unidad']) && $proyecto['unidad']) {
                try {
                    $sqlUnidad = "SELECT DESCRIPCION FROM UNIDAD WHERE CODIGO = :codigo";
                    $stmtUnidad = oci_parse($conn, $sqlUnidad);
                    oci_bind_by_name($stmtUnidad, ':codigo', $proyecto['unidad']);
                    $rUnidad = @oci_execute($stmtUnidad);
                    
                    if ($rUnidad) {
                        $rowUnidad = oci_fetch_assoc($stmtUnidad);
                        if ($rowUnidad) {
                            $proyecto['unidad_descripcion'] = $rowUnidad['DESCRIPCION'];
                        }
                    }
                    @oci_free_statement($stmtUnidad);
                } catch (Exception $e) {
                    // Si algo falla, simplemente continuamos sin la descripción
                }
            }
            
            // 5. Obtener nombre del coordinador si existe
            if (isset($proyecto['coordinador']) && $proyecto['coordinador']) {
                try {
                    $sqlCoordinador = "SELECT NOMBRE FROM PERSONA WHERE PERSONA = :codigo";
                    $stmtCoordinador = oci_parse($conn, $sqlCoordinador);
                    oci_bind_by_name($stmtCoordinador, ':codigo', $proyecto['coordinador']);
                    $rCoordinador = @oci_execute($stmtCoordinador);
                    
                    if ($rCoordinador) {
                        $rowCoordinador = oci_fetch_assoc($stmtCoordinador);
                        if ($rowCoordinador) {
                            $proyecto['coordinador_nombre'] = $rowCoordinador['NOMBRE'];
                        }
                    }
                    @oci_free_statement($stmtCoordinador);
                } catch (Exception $e) {
                    // Si algo falla, simplemente continuamos sin el nombre
                }
            }
        }
        
        // Liberar recursos
        oci_free_statement($stmt);
        oci_close($conn);
        
        return $proyecto ? $proyecto : null;
    } catch (Exception $e) {
        error_log('Error en obtenerProyectoDetalle: ' . $e->getMessage());
        return null;
    }
}




/**
 * Obtiene las actas relacionadas con un proyecto específico
 * @param int $proyectoId ID del proyecto
 * @return array Lista de actas
 */
function obtenerActasProyecto($proyectoId) {
    try {
        // Obtener conexión
        $conn = conectarOracle();
        
        // Extraer ANIO_PRO y NUMERO_PRO del proyecto
        $sqlProy = "SELECT ANIO_PRO, NUMERO_PRO FROM PROYECTO WHERE NUMERO_PRO = :id";
        $stidProy = oci_parse($conn, $sqlProy);
        oci_bind_by_name($stidProy, ':id', $proyectoId);
        oci_execute($stidProy);
        $rowProy = oci_fetch_assoc($stidProy);
        
        if (!$rowProy) {
            return [];
        }
        
        $anio_pro = $rowProy['ANIO_PRO'];
        $numero_pro = $rowProy['NUMERO_PRO'];
        
        // Consulta SQL para obtener actas
        $sql = "SELECT 
                    a.ANIO_PRO,
                    a.NUMERO_PRO,
                    a.NUMERO_ACTA,
                    a.TIPO_ACTA,
                    t.DESCRIPCION as TIPO_DESCRIPCION,
                    a.FECHA_ACTA,
                    a.OBSERVA,
                    a.ESTADO
                FROM 
                    ACTA_PROYECTO a
                LEFT JOIN 
                    TIPO_ACTA t ON a.TIPO_ACTA = t.CODIGO
                WHERE 
                    a.ANIO_PRO = :anio_pro
                    AND a.NUMERO_PRO = :numero_pro
                    AND a.ESTADO = 'A'
                ORDER BY 
                    a.NUMERO_ACTA ASC";  // Ordenar por NUMERO_ACTA de menor a mayor
        
        // Preparar consulta
        $stid = oci_parse($conn, $sql);
        if (!$stid) {
            $e = oci_error($conn);
            error_log("Error al preparar consulta de actas: " . $e['message']);
            return [];
        }
        
        // Vincular parámetros
        oci_bind_by_name($stid, ':anio_pro', $anio_pro);
        oci_bind_by_name($stid, ':numero_pro', $numero_pro);
        
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
        error_log("Error en obtenerActasProyecto: " . $e->getMessage());
        return [];
    }
}



/**
 * Obtiene los documentos relacionados con un proyecto específico
 * @param int $proyectoId ID del proyecto
 * @return array Lista de documentos
 */
function obtenerDocumentosProyecto($proyectoId) {
    try {
        // Obtener conexión
        $conn = conectarOracle();
        
        // Extraer ANIO_PRO y NUMERO_PRO del proyecto
        $sqlProy = "SELECT ANIO_PRO, NUMERO_PRO FROM PROYECTO WHERE NUMERO_PRO = :id";
        $stidProy = oci_parse($conn, $sqlProy);
        oci_bind_by_name($stidProy, ':id', $proyectoId);
        oci_execute($stidProy);
        $rowProy = oci_fetch_assoc($stidProy);
        
        if (!$rowProy) {
            return [];
        }
        
        $anio_pro = $rowProy['ANIO_PRO'];
        $numero_pro = $rowProy['NUMERO_PRO'];
        
        // Consulta SQL para obtener documentos
        $sql = "SELECT 
                    d.ANIO_PRO,
                    d.NUMERO_PRO,
                    d.NUMERO_DOC,
                    d.TIPO_DOC,
                    t.DESCRIPCION as TIPO_DESCRIPCION,
                    d.FECHA_DOC,
                    d.ARCHIVO,
                    d.ESTADO
                FROM 
                    DOCUMENTO_PROYECTO d
                LEFT JOIN 
                    TIPO_DOCUMENTO t ON d.TIPO_DOC = t.CODIGO
                WHERE 
                    d.ANIO_PRO = :anio_pro
                    AND d.NUMERO_PRO = :numero_pro
                    AND d.ESTADO = 'A'
                ORDER BY 
                    d.FECHA_DOC DESC, d.NUMERO_DOC DESC";
        
        // Preparar consulta
        $stid = oci_parse($conn, $sql);
        if (!$stid) {
            $e = oci_error($conn);
            error_log("Error al preparar consulta de documentos: " . $e['message']);
            return [];
        }
        
        // Vincular parámetros
        oci_bind_by_name($stid, ':anio_pro', $anio_pro);
        oci_bind_by_name($stid, ':numero_pro', $numero_pro);
        
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
        error_log("Error en obtenerDocumentosProyecto: " . $e->getMessage());
        return [];
    }
}

/**
 * Obtiene el documento asociado a un acta de proyecto
 * @param int $proyectoId ID del proyecto
 * @param int $numeroActa Número del acta
 * @param int $tipoActa Tipo de acta
 * @return array|null Datos del documento o null si no existe
 */
function obtenerDocumentoActa($proyectoId, $numeroActa, $tipoActa) {
    try {
        // Obtener conexión
        $conn = conectarOracle();
        
        // Extraer ANIO_PRO y NUMERO_PRO del proyecto
        $sqlProy = "SELECT ANIO_PRO, NUMERO_PRO FROM PROYECTO WHERE NUMERO_PRO = :id";
        $stidProy = oci_parse($conn, $sqlProy);
        oci_bind_by_name($stidProy, ':id', $proyectoId);
        oci_execute($stidProy);
        $rowProy = oci_fetch_assoc($stidProy);
        
        if (!$rowProy) {
            return null;
        }
        
        $anio_pro = $rowProy['ANIO_PRO'];
        $numero_pro = $rowProy['NUMERO_PRO'];
        
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
        oci_bind_by_name($stid, ':numero_pro', $numero_pro);
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
        error_log("Error en obtenerDocumentoActa: " . $e->getMessage());
        return null;
    }
}

/**
 * Actualiza los datos completos de un proyecto existente
 * Incluye actualizaciones en todas las tablas relacionadas
 * @param array $datos Datos completos del proyecto
 * @return bool True si se actualizó correctamente, False en caso de error
 */
function actualizarProyectoCompleto($datos) {
    try {
        // Obtener conexión
        $conn = conectarOracle();
        
        // Iniciar transacción
        oci_set_action($conn, 'actualizarProyectoCompleto');
        
        // 1. Actualizar tabla PROYECTO
        $sql = "UPDATE PROYECTO SET 
                    NOMBRE = :nombre,
                    OBJETO = :objeto,
                    TIPO_PROYECTO = :tipo_proyecto,
                    MODALIDAD = :modalidad,
                    FINANCIACION = :financiacion,
                    VALOR = :valor,
                    APORTE_UD = :aporte_ud,
                    FECHA_INICIO = TO_DATE(:fecha_inicio, 'YYYY-MM-DD'),
                    FECHA_TERMINA = TO_DATE(:fecha_termina, 'YYYY-MM-DD'),
                    FECHA_SUSCRIPCION = TO_DATE(:fecha_suscripcion, 'YYYY-MM-DD'),
                    SITUACION = :situacion,
                    BENEFICIARIOS = :beneficiarios,
                    OBSERVACIONES = :observaciones,
                    CODIGO_TESORAL = :codigo_tesoral,
                    CODIGO_BANCARIO = :codigo_bancario,
                    TIPO_CUENTA = :tipo_cuenta,
                    NUMERO_CUENTA = :numero_cuenta,
                    REFERENCIA_UD = :referencia_ud,
                    UNIDAD = :unidad,
                    COORDINADOR = :coordinador,
                    EMAIL_MAIN = :email_main,
                    EMAIL_SECUNDARIOS = :email_secundarios,
                    SUPERVISOR = :supervisor,
                    ENLACE_SECOP = :enlace_secop,
                    ACTO_ADMINISTRATIVO = :acto_administrativo,
                    DATOS_ADICIONALES = :datos_adicionales
                WHERE NUMERO_PRO = :id";
        
        // Preparar consulta
        $stmt = oci_parse($conn, $sql);
        if (!$stmt) {
            $e = oci_error($conn);
            error_log("Error al preparar consulta de actualización: " . $e['message']);
            return false;
        }
        
        // Vincular parámetros
        oci_bind_by_name($stmt, ':nombre', $datos['nombre']);
        oci_bind_by_name($stmt, ':objeto', $datos['objeto']);
        oci_bind_by_name($stmt, ':tipo_proyecto', $datos['tipo_proyecto']);
        oci_bind_by_name($stmt, ':modalidad', $datos['modalidad']);
        oci_bind_by_name($stmt, ':financiacion', $datos['financiacion']);
        oci_bind_by_name($stmt, ':valor', $datos['valor']);
        oci_bind_by_name($stmt, ':aporte_ud', $datos['aporte_ud']);
        oci_bind_by_name($stmt, ':fecha_inicio', $datos['fecha_inicio']);
        oci_bind_by_name($stmt, ':fecha_termina', $datos['fecha_termina']);
        oci_bind_by_name($stmt, ':fecha_suscripcion', $datos['fecha_suscripcion']);
        oci_bind_by_name($stmt, ':situacion', $datos['situacion']);
        oci_bind_by_name($stmt, ':beneficiarios', $datos['beneficiarios']);
        oci_bind_by_name($stmt, ':observaciones', $datos['observaciones']);
        oci_bind_by_name($stmt, ':codigo_tesoral', $datos['codigo_tesoral']);
        oci_bind_by_name($stmt, ':codigo_bancario', $datos['codigo_bancario']);
        oci_bind_by_name($stmt, ':tipo_cuenta', $datos['tipo_cuenta']);
        oci_bind_by_name($stmt, ':numero_cuenta', $datos['numero_cuenta']);
        oci_bind_by_name($stmt, ':referencia_ud', $datos['referencia_ud']);
        oci_bind_by_name($stmt, ':unidad', $datos['unidad']);
        oci_bind_by_name($stmt, ':coordinador', $datos['coordinador']);
        oci_bind_by_name($stmt, ':email_main', $datos['email_main']);
        oci_bind_by_name($stmt, ':email_secundarios', $datos['email_secundarios']);
        oci_bind_by_name($stmt, ':supervisor', $datos['supervisor']);
        oci_bind_by_name($stmt, ':enlace_secop', $datos['enlace_secop']);
        oci_bind_by_name($stmt, ':acto_administrativo', $datos['acto_administrativo']);
        oci_bind_by_name($stmt, ':datos_adicionales', $datos['datos_adicionales']);
        oci_bind_by_name($stmt, ':id', $datos['id']);
        
        // Ejecutar consulta
        $r = oci_execute($stmt, OCI_NO_AUTO_COMMIT);
        if (!$r) {
            $e = oci_error($stmt);
            error_log("Error al ejecutar consulta de actualización: " . $e['message']);
            oci_rollback($conn);
            return false;
        }
        
        // 2. Actualizar entidad en ENTE_SUSCRIPTOR si se proporcionó una entidad principal
        if (!empty($datos['entidad_principal'])) {
            // Verificar si ya existe un registro para esta entidad
            $sqlCheckEnte = "SELECT COUNT(*) AS existe FROM ENTE_SUSCRIPTOR 
                            WHERE NUMERO_PRO = :id AND ANIO_PRO = :anio_pro AND ENTIDAD = :entidad";
            
            $stmtCheckEnte = oci_parse($conn, $sqlCheckEnte);
            oci_bind_by_name($stmtCheckEnte, ':id', $datos['id']);
            oci_bind_by_name($stmtCheckEnte, ':anio_pro', $datos['anio_pro']);
            oci_bind_by_name($stmtCheckEnte, ':entidad', $datos['entidad_principal']);
            oci_execute($stmtCheckEnte);
            
            $rowCheck = oci_fetch_assoc($stmtCheckEnte);
            $existeEnte = $rowCheck['EXISTE'] > 0;
            
            if ($existeEnte) {
                // Actualizar el registro existente
                $sqlUpdateEnte = "UPDATE ENTE_SUSCRIPTOR 
                                SET NUMERO_PRO_ENTIDAD = :numero_pro_entidad
                                WHERE NUMERO_PRO = :id AND ANIO_PRO = :anio_pro AND ENTIDAD = :entidad";
                
                $stmtUpdateEnte = oci_parse($conn, $sqlUpdateEnte);
                
                // Asumimos que el número de proyecto de la entidad es el mismo que ya tiene
                $sqlGetNumeroEntidad = "SELECT NUMERO_PRO_ENTIDAD FROM ENTE_SUSCRIPTOR 
                                      WHERE NUMERO_PRO = :id AND ANIO_PRO = :anio_pro AND ROWNUM = 1";
                $stmtGetNumero = oci_parse($conn, $sqlGetNumeroEntidad);
                oci_bind_by_name($stmtGetNumero, ':id', $datos['id']);
                oci_bind_by_name($stmtGetNumero, ':anio_pro', $datos['anio_pro']);
                oci_execute($stmtGetNumero);
                $rowNumero = oci_fetch_assoc($stmtGetNumero);
                $numero_pro_entidad = $rowNumero['NUMERO_PRO_ENTIDAD'];
                
                oci_bind_by_name($stmtUpdateEnte, ':numero_pro_entidad', $numero_pro_entidad);
                oci_bind_by_name($stmtUpdateEnte, ':id', $datos['id']);
                oci_bind_by_name($stmtUpdateEnte, ':anio_pro', $datos['anio_pro']);
                oci_bind_by_name($stmtUpdateEnte, ':entidad', $datos['entidad_principal']);
                
                $rUpdateEnte = oci_execute($stmtUpdateEnte, OCI_NO_AUTO_COMMIT);
                if (!$rUpdateEnte) {
                    $e = oci_error($stmtUpdateEnte);
                    error_log("Error al actualizar ENTE_SUSCRIPTOR: " . $e['message']);
                    oci_rollback($conn);
                    return false;
                }
            } else {
                // Insertar un nuevo registro
                $sqlInsertEnte = "INSERT INTO ENTE_SUSCRIPTOR (ANIO_PRO, NUMERO_PRO, ENTIDAD, NUMERO_PRO_ENTIDAD)
                                VALUES (:anio_pro, :id, :entidad, :numero_pro_entidad)";
                
                // Obtener el número actual del proyecto para la entidad
                $sqlGetNumeroEntidad = "SELECT NUMERO_PRO_ENTIDAD FROM ENTE_SUSCRIPTOR 
                                      WHERE NUMERO_PRO = :id AND ANIO_PRO = :anio_pro AND ROWNUM = 1";
                $stmtGetNumero = oci_parse($conn, $sqlGetNumeroEntidad);
                oci_bind_by_name($stmtGetNumero, ':id', $datos['id']);
                oci_bind_by_name($stmtGetNumero, ':anio_pro', $datos['anio_pro']);
                oci_execute($stmtGetNumero);
                $rowNumero = oci_fetch_assoc($stmtGetNumero);
                $numero_pro_entidad = $rowNumero['NUMERO_PRO_ENTIDAD'];
                
                $stmtInsertEnte = oci_parse($conn, $sqlInsertEnte);
                oci_bind_by_name($stmtInsertEnte, ':anio_pro', $datos['anio_pro']);
                oci_bind_by_name($stmtInsertEnte, ':id', $datos['id']);
                oci_bind_by_name($stmtInsertEnte, ':entidad', $datos['entidad_principal']);
                oci_bind_by_name($stmtInsertEnte, ':numero_pro_entidad', $numero_pro_entidad);
                
                $rInsertEnte = oci_execute($stmtInsertEnte, OCI_NO_AUTO_COMMIT);
                if (!$rInsertEnte) {
                    $e = oci_error($stmtInsertEnte);
                    error_log("Error al insertar en ENTE_SUSCRIPTOR: " . $e['message']);
                    oci_rollback($conn);
                    return false;
                }
            }
        }
        
        // Confirmar todas las transacciones
        oci_commit($conn);
        
        // Liberar recursos
        oci_free_statement($stmt);
        oci_close($conn);
        
        return true;
        
    } catch (Exception $e) {
        error_log("Error en actualizarProyectoCompleto: " . $e->getMessage());
        return false;
    }
}

/**
 * Obtiene la lista de todos los tipos de proyecto
 * @return array Lista de tipos de proyecto
 */
function obtenerTiposProyecto() {
    try {
        // Obtener conexión
        $conn = conectarOracle();
        
        // Consulta SQL
        $sql = "SELECT CODIGO as codigo, DESCRIPCION as descripcion
                FROM TIPO_PROYECTO
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
        error_log("Error en obtenerTiposProyecto: " . $e->getMessage());
        
        // Datos de ejemplo en caso de error
        return [
            ['codigo' => 1, 'descripcion' => 'Investigación'],
            ['codigo' => 2, 'descripcion' => 'Desarrollo'],
            ['codigo' => 3, 'descripcion' => 'Consultoría'],
            ['codigo' => 4, 'descripcion' => 'Extensión']
        ];
    }
}

/**
 * Obtiene la lista de todas las modalidades
 * @return array Lista de modalidades
 */
function obtenerModalidades() {
    try {
        // Obtener conexión
        $conn = conectarOracle();
        
        // Consulta SQL
        $sql = "SELECT CODIGO as codigo, DESCRIPCION as descripcion
                FROM MODALIDAD
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
        $modalidades = [];
        
        while ($row = oci_fetch_assoc($stid)) {
            $modalidades[] = [
                'codigo' => $row['CODIGO'],
                'descripcion' => $row['DESCRIPCION']
            ];
        }
        
        // Liberar recursos
        oci_free_statement($stid);
        oci_close($conn);
        
        return $modalidades;
        
    } catch (Exception $e) {
        error_log("Error en obtenerModalidades: " . $e->getMessage());
        
        // Datos de ejemplo en caso de error
        return [
            ['codigo' => 1, 'descripcion' => 'Contratación Directa'],
            ['codigo' => 2, 'descripcion' => 'Licitación Pública'],
            ['codigo' => 3, 'descripcion' => 'Convocatoria']
        ];
    }
}

/**
 * Obtiene la lista de todas las financiaciones
 * @return array Lista de financiaciones
 */
function obtenerFinanciaciones() {
    try {
        // Obtener conexión
        $conn = conectarOracle();
        
        // Consulta SQL
        $sql = "SELECT CODIGO as codigo, DESCRIPCION as descripcion
                FROM FINANCIACION
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
        $financiaciones = [];
        
        while ($row = oci_fetch_assoc($stid)) {
            $financiaciones[] = [
                'codigo' => $row['CODIGO'],
                'descripcion' => $row['DESCRIPCION']
            ];
        }
        
        // Liberar recursos
        oci_free_statement($stid);
        oci_close($conn);
        
        return $financiaciones;
        
    } catch (Exception $e) {
        error_log("Error en obtenerFinanciaciones: " . $e->getMessage());
        
        // Datos de ejemplo en caso de error
        return [
            ['codigo' => 1, 'descripcion' => 'Propia'],
            ['codigo' => 2, 'descripcion' => 'Cofinanciación'],
            ['codigo' => 3, 'descripcion' => 'Externa']
        ];
    }
}

/**
 * Obtiene la lista de todas las situaciones
 * @return array Lista de situaciones
 */
function obtenerSituaciones() {
    try {
        // Obtener conexión
        $conn = conectarOracle();
        
        // Consulta SQL
        $sql = "SELECT CODIGO as codigo, DESCRIPCION as descripcion
                FROM SITUACION
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
        error_log("Error en obtenerSituaciones: " . $e->getMessage());
        
        // Datos de ejemplo en caso de error
        return [
            ['codigo' => 8, 'descripcion' => 'Suscrito'],
            ['codigo' => 14, 'descripcion' => 'En Ejecución'],
            ['codigo' => 19, 'descripcion' => 'Finalizado']
        ];
    }
}

/**
 * Obtiene la lista de todas las unidades
 * @return array Lista de unidades
 */
function obtenerUnidades() {
    try {
        // Obtener conexión
        $conn = conectarOracle();
        
        // Consulta SQL
        $sql = "SELECT CODIGO as codigo, DESCRIPCION as descripcion
                FROM UNIDAD
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
        $unidades = [];
        
        while ($row = oci_fetch_assoc($stid)) {
            $unidades[] = [
                'codigo' => $row['CODIGO'],
                'descripcion' => $row['DESCRIPCION']
            ];
        }
        
        // Liberar recursos
        oci_free_statement($stid);
        oci_close($conn);
        
        return $unidades;
        
    } catch (Exception $e) {
        error_log("Error en obtenerUnidades: " . $e->getMessage());
        
        // Datos de ejemplo en caso de error
        return [
            ['codigo' => 1, 'descripcion' => 'Facultad de Ingeniería'],
            ['codigo' => 2, 'descripcion' => 'Facultad de Ciencias'],
            ['codigo' => 3, 'descripcion' => 'Facultad de Artes']
        ];
    }
}

/**
 * Obtiene la lista de todas las entidades
 * @return array Lista de entidades
 */
function obtenerEntidades() {
    try {
        // Obtener conexión
        $conn = conectarOracle();
        
        // Consulta SQL
        $sql = "SELECT CODIGO as codigo, DESCRIPCION as descripcion
                FROM ENTIDAD
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
        $entidades = [];
        
        while ($row = oci_fetch_assoc($stid)) {
            $entidades[] = [
                'codigo' => $row['CODIGO'],
                'descripcion' => $row['DESCRIPCION']
            ];
        }
        
        // Liberar recursos
        oci_free_statement($stid);
        oci_close($conn);
        
        return $entidades;
        
    } catch (Exception $e) {
        error_log("Error en obtenerEntidades: " . $e->getMessage());
        
        // Datos de ejemplo en caso de error
        return [
            ['codigo' => 1, 'descripcion' => 'Ministerio de Educación'],
            ['codigo' => 2, 'descripcion' => 'Alcaldía de Bogotá'],
            ['codigo' => 3, 'descripcion' => 'Universidad Nacional']
        ];
    }
}

/**
 * Obtiene la lista de coordinadores disponibles
 * @return array Lista de coordinadores
 */
function obtenerCoordinadores() {
    try {
        // Obtener conexión
        $conn = conectarOracle();
        
        // Consulta SQL para obtener coordinadores desde CONTRATISTA
        $sql = "SELECT 
                    c.IDENTIFICACION as codigo,
                    TRIM(
                        CASE 
                            WHEN c.TIPO_PERSONA = 'N' THEN 
                                NVL(c.NOMBRE1, '') || ' ' || NVL(c.NOMBRE2, '') || ' ' || 
                                NVL(c.APELLIDO1, '') || ' ' || NVL(c.APELLIDO2, '')
                            ELSE 
                                NVL(c.RAZON_SOCIAL, '')
                        END
                    ) as nombre
                FROM 
                    CONTRATISTA c
                INNER JOIN
                    COORDINADOR co ON c.IDENTIFICACION = co.CONTRATISTA
                WHERE 
                    c.ESTADO = 'A' AND co.ESTADO = 'A'
                ORDER BY 
                    nombre";
        
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
        $coordinadores = [];
        
        while ($row = oci_fetch_assoc($stid)) {
            $coordinadores[] = [
                'codigo' => $row['CODIGO'],
                'nombre' => $row['NOMBRE']
            ];
        }
        
        // Liberar recursos
        oci_free_statement($stid);
        oci_close($conn);
        
        return $coordinadores;
        
    } catch (Exception $e) {
        error_log("Error en obtenerCoordinadores: " . $e->getMessage());
        
        // Datos de ejemplo en caso de error
        return [
            ['codigo' => 1, 'nombre' => 'Juan Pérez'],
            ['codigo' => 2, 'nombre' => 'María López'],
            ['codigo' => 3, 'nombre' => 'Carlos Rodríguez']
        ];
    }
}

/**
 * Obtiene las entidades asociadas a un proyecto
 * @param int $proyectoId ID del proyecto
 * @return array Lista de entidades asociadas
 */
function obtenerEntidadesProyecto($proyectoId) {
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
            return [];
        }
        
        $anio_pro = $rowAnio['ANIO_PRO'];
        
        // Consulta SQL para obtener entidades asociadas
        $sql = "SELECT 
                    es.ANIO_PRO,
                    es.NUMERO_PRO,
                    es.ENTIDAD,
                    es.NUMERO_PRO_ENTIDAD,
                    e.DESCRIPCION as entidad_descripcion
                FROM 
                    ENTE_SUSCRIPTOR es
                    JOIN ENTIDAD e ON es.ENTIDAD = e.CODIGO
                WHERE 
                    es.NUMERO_PRO = :id
                    AND es.ANIO_PRO = :anio_pro";
        
        // Preparar consulta
        $stid = oci_parse($conn, $sql);
        if (!$stid) {
            $e = oci_error($conn);
            error_log("Error al preparar consulta: " . $e['message']);
            return [];
        }
        
        // Vincular parámetros
        oci_bind_by_name($stid, ':id', $proyectoId);
        oci_bind_by_name($stid, ':anio_pro', $anio_pro);
        
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
            // Convertir claves a minúsculas
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
        error_log("Error en obtenerEntidadesProyecto: " . $e->getMessage());
        return [];
    }
}






/**
 * Actualiza los datos de un proyecto existente
 * @param int $proyectoId ID del proyecto a actualizar
 * @param string $nombre Nombre del proyecto
 * @param string $objeto Objeto del proyecto
 * @param string $fechaInicio Fecha de inicio (YYYY-MM-DD)
 * @param string $fechaTermina Fecha de terminación (YYYY-MM-DD)
 * @param int $situacion Situación del proyecto
 * @param float $valor Valor total del proyecto
 * @param float $aporteUd Aporte de la Universidad Distrital
 * @param string $observaciones Observaciones del proyecto
 * @return bool True si se actualizó correctamente, False en caso de error
 */
function actualizarProyecto($proyectoId, $nombre, $objeto, $fechaInicio, $fechaTermina, $situacion, $valor, $aporteUd, $observaciones) {
    try {
        // Obtener conexión
        $conn = conectarOracle();
        
        // Preparar la consulta SQL de actualización
        $sql = "UPDATE PROYECTO SET 
                    NOMBRE = :nombre,
                    OBJETO = :objeto,
                    FECHA_INICIO = TO_DATE(:fecha_inicio, 'YYYY-MM-DD'),
                    FECHA_TERMINA = TO_DATE(:fecha_termina, 'YYYY-MM-DD'),
                    SITUACION = :situacion,
                    VALOR = :valor,
                    APORTE_UD = :aporte_ud,
                    OBSERVACIONES = :observaciones
                WHERE NUMERO_PRO = :id";
        
        // Preparar la consulta
        $stmt = oci_parse($conn, $sql);
        if (!$stmt) {
            $e = oci_error($conn);
            error_log("Error al preparar consulta de actualización: " . $e['message']);
            return false;
        }
        
        // Vincular los parámetros
        oci_bind_by_name($stmt, ':nombre', $nombre);
        oci_bind_by_name($stmt, ':objeto', $objeto);
        oci_bind_by_name($stmt, ':fecha_inicio', $fechaInicio);
        oci_bind_by_name($stmt, ':fecha_termina', $fechaTermina);
        oci_bind_by_name($stmt, ':situacion', $situacion);
        oci_bind_by_name($stmt, ':valor', $valor);
        oci_bind_by_name($stmt, ':aporte_ud', $aporteUd);
        oci_bind_by_name($stmt, ':observaciones', $observaciones);
        oci_bind_by_name($stmt, ':id', $proyectoId);
        
        // Ejecutar la consulta
        $result = oci_execute($stmt);
        if (!$result) {
            $e = oci_error($stmt);
            error_log("Error al ejecutar consulta de actualización: " . $e['message']);
            return false;
        }
        
        // Confirmar la transacción
        oci_commit($conn);
        
        // Liberar recursos
        oci_free_statement($stmt);
        oci_close($conn);
        
        return true;
    } catch (Exception $e) {
        error_log('Error en actualizarProyecto: ' . $e->getMessage());
        return false;
    }
}

/**
 * Función auxiliar para verificar si el usuario tiene permisos de edición
 * Esta función puede ser adaptada según el sistema de permisos existente
 * @return bool True si tiene permisos, False si no
 */
function tienePermisosEdicion() {
    // Por ahora, permitimos edición a todos los usuarios
    // Aquí se puede implementar la lógica de verificación de permisos
    return true;
}


/**
 * Obtiene la lista de todos los bancos
 * @return array Lista de bancos
 */
function obtenerBancos() {
    try {
        // Obtener conexión
        $conn = conectarOracle();
        
        // Consulta SQL
        $sql = "SELECT CODIGO as codigo, DESCRIPCION as descripcion
                FROM BANCO
                ORDER BY DESCRIPCION";
        
        // Preparar consulta
        $stid = oci_parse($conn, $sql);
        if (!$stid) {
            $e = oci_error($conn);
            error_log("Error al preparar consulta de bancos: " . $e['message']);
            return [];
        }
        
        // Ejecutar consulta
        $r = oci_execute($stid);
        if (!$r) {
            $e = oci_error($stid);
            error_log("Error al ejecutar consulta de bancos: " . $e['message']);
            return [];
        }
        
        // Procesar resultados
        $bancos = [];
        
        while ($row = oci_fetch_assoc($stid)) {
            $bancos[] = [
                'codigo' => $row['CODIGO'],
                'descripcion' => $row['DESCRIPCION']
            ];
        }
        
        // Liberar recursos
        oci_free_statement($stid);
        oci_close($conn);
        
        return $bancos;
        
    } catch (Exception $e) {
        error_log("Error en obtenerBancos: " . $e->getMessage());
        
        // Datos de ejemplo en caso de error
        return [
            ['codigo' => 1, 'descripcion' => 'Banco de Bogotá'],
            ['codigo' => 2, 'descripcion' => 'Bancolombia'],
            ['codigo' => 3, 'descripcion' => 'Davivienda'],
            ['codigo' => 4, 'descripcion' => 'BBVA']
        ];
    }
}

/**
 * Inserta un registro en la tabla EJECUTOR
 * @param array $datos Datos del ejecutor
 * @return bool True si se insertó correctamente, False en caso contrario
 */
function insertarEjecutor($datos) {
    try {
        // Obtener conexión
        $conn = conectarOracle();
        
        // Preparar la consulta SQL
        $sql = "INSERT INTO EJECUTOR (
                    ANIO_PRO, NUMERO_PRO, UNIDAD, APORTE_EJECUTOR, 
                    SUPERVISOR, FECHA_INICIAL, FECHA_FINAL, ESTADO
                ) VALUES (
                    :anio_pro, :numero_pro, :unidad, :aporte_ejecutor,
                    :supervisor, TO_DATE(:fecha_inicial, 'YYYY-MM-DD'),
                    TO_DATE(:fecha_final, 'YYYY-MM-DD'), 'A'
                )";
        
        // Preparar statement
        $stmt = oci_parse($conn, $sql);
        if (!$stmt) {
            $e = oci_error($conn);
            error_log("Error al preparar inserción en EJECUTOR: " . $e['message']);
            return false;
        }
        
        // Vincular parámetros
        oci_bind_by_name($stmt, ':anio_pro', $datos['anio_pro']);
        oci_bind_by_name($stmt, ':numero_pro', $datos['numero_pro']);
        oci_bind_by_name($stmt, ':unidad', $datos['unidad']);
        oci_bind_by_name($stmt, ':aporte_ejecutor', $datos['aporte_ejecutor']);
        oci_bind_by_name($stmt, ':supervisor', $datos['supervisor']);
        oci_bind_by_name($stmt, ':fecha_inicial', $datos['fecha_inicial']);
        oci_bind_by_name($stmt, ':fecha_final', $datos['fecha_final']);
        
        // Ejecutar la consulta
        $resultado = oci_execute($stmt);
        
        // Liberar recursos
        oci_free_statement($stmt);
        oci_close($conn);
        
        return $resultado;
        
    } catch (Exception $e) {
        error_log("Error en insertarEjecutor: " . $e->getMessage());
        return false;
    }
}









/**
 * Obtiene todos los tipos de acta disponibles
 * @return array Lista de tipos de acta
 */
function obtenerTiposActa() {
    try {
        // Obtener conexión
        $conn = conectarOracle();
        
        // Consulta SQL
        $sql = "SELECT CODIGO, DESCRIPCION FROM TIPO_ACTA ORDER BY CODIGO";
        
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
        return [];
    }
}

/**
 * Obtiene el siguiente número de acta disponible para un proyecto
 * @param int $anio_pro Año del proyecto
 * @param int $numero_pro Número del proyecto
 * @return int Siguiente número de acta
 */
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

/**
 * Obtiene el siguiente número de documento disponible para un proyecto
 * @param int $anio_pro Año del proyecto
 * @param int $numero_pro Número del proyecto
 * @return int Siguiente número de documento
 */
function obtenerSiguienteNumeroDocumento($anio_pro, $numero_pro) {
    try {
        // Obtener conexión
        $conn = conectarOracle();
        
        // Consulta SQL para obtener el máximo número de documento
        $sql = "SELECT NVL(MAX(NUMERO_DOC), 0) + 1 AS siguiente_numero 
                FROM DOCUMENTO_PROYECTO 
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
        error_log("Error en obtenerSiguienteNumeroDocumento: " . $e->getMessage());
        return 1; // Por defecto, empezar desde 1
    }
}

/**
 * Obtiene el tipo de acta por su código
 * @param int $codigo Código del tipo de acta
 * @return array|null Datos del tipo de acta o null si no existe
 */
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



/**
 * Crea una nueva acta de proyecto y sube el documento asociado al servidor remoto
 * @param array $datos Datos del acta
 * @param string $archivo_tmp Ruta temporal del archivo
 * @return int|array ID del acta creada o array con error
 */
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
            
            // Configuración para la conexión SSH2
            $servidor = '200.69.103.17';
            $puerto = 22;
            $ssh_usuario = 'oracle';
            $ssh_password = 'QDameco2016';
            $ruta_destino = '/var/www/html/idexud/siexud/actasproy/upload/';
            
            // Comprobar que existe la extensión SSH2
            if (function_exists('ssh2_connect')) {
                // Conectar al servidor
                $conexion = ssh2_connect($servidor, $puerto);
                
                if ($conexion) {
                    // Autenticar
                    if (ssh2_auth_password($conexion, $ssh_usuario, $ssh_password)) {
                        // Iniciar SFTP
                        $sftp = ssh2_sftp($conexion);
                        
                        // Ruta completa de destino
                        $ruta_completa = $ruta_destino . $nombre_archivo;
                        
                        // Abrir flujo para escritura
                        $flujo_remoto = fopen("ssh2.sftp://{$sftp}{$ruta_completa}", 'w');
                        
                        if ($flujo_remoto) {
                            // Leer archivo local
                            $contenido = file_get_contents($archivo_tmp);
                            
                            // Escribir en archivo remoto
                            if (fwrite($flujo_remoto, $contenido) !== false) {
                                // Subida exitosa, ahora insertar en DOCUMENTO_PROYECTO
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
                                error_log("Error al escribir el archivo en el servidor remoto");
                                oci_rollback($conn);
                                return ['error' => "Error al escribir el archivo en el servidor remoto"];
                            }
                            
                            fclose($flujo_remoto);
                        } else {
                            error_log("No se pudo abrir el archivo en el servidor remoto");
                            oci_rollback($conn);
                            return ['error' => "No se pudo abrir el archivo en el servidor remoto"];
                        }
                    } else {
                        error_log("Error de autenticación SSH");
                        oci_rollback($conn);
                        return ['error' => "Error de autenticación con el servidor de archivos"];
                    }
                } else {
                    error_log("No se pudo conectar al servidor SSH");
                    oci_rollback($conn);
                    return ['error' => "No se pudo conectar al servidor de archivos"];
                }
            } else {
                error_log("La extensión SSH2 no está instalada en PHP");
                
                // Plan alternativo: guardar localmente
                $dir_local = 'uploads/actas/';
                if (!is_dir($dir_local)) {
                    mkdir($dir_local, 0755, true);
                }
                
                $ruta_local = $dir_local . $nombre_archivo;
                
                if (move_uploaded_file($archivo_tmp, $ruta_local)) {
                    // Guardar en la base de datos
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
                    if (!$r_doc) {
                        $e = oci_error($stmt_doc);
                        error_log("Error al insertar en DOCUMENTO_PROYECTO: " . $e['message']);
                        oci_rollback($conn);
                        @unlink($ruta_local);
                        return ['error' => "Error al insertar en DOCUMENTO_PROYECTO: " . $e['message']];
                    }
                    
                    oci_free_statement($stmt_doc);
                } else {
                    oci_rollback($conn);
                    return ['error' => "Error al mover el archivo. La extensión SSH2 no está disponible y el guardado local falló."];
                }
            }
        } else {
            error_log("No se proporcionó un archivo o el archivo no existe");
            oci_rollback($conn);
            return ['error' => "No se proporcionó un archivo o el archivo no existe"];
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




?>