<?php
/**
 * Consulta Natural - Interfaz de Consultas en Lenguaje Natural
 * 
 * Esta página permite realizar consultas a la base de datos utilizando lenguaje natural
 * Utiliza procesamiento de lenguaje natural para extraer entidades, fechas y criterios
 */

// Incluir modelos de base de datos
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/proyecto_model.php';
require_once __DIR__ . '/../models/ai_model.php';

class NaturalLanguageQueryProcessor {
    private $conn;
    private $entidades = [];
    private $situaciones = [];
    private $aniosDisponibles = [];

    public function __construct() {
        // Establecer conexión a la base de datos
        $this->conn = conectarOracle();
        
        // Cargar datos maestros para mejorar la interpretación
        $this->entidades = $this->cargarEntidades();
        $this->situaciones = $this->cargarSituaciones();
        $this->aniosDisponibles = $this->cargarAnios();
    }

    /**
     * Cargar lista de entidades para facilitar la búsqueda
     */
    private function cargarEntidades() {
        try {
            $sql = "SELECT CODIGO, DESCRIPCION FROM ENTIDAD ORDER BY DESCRIPCION";
            $stid = oci_parse($this->conn, $sql);
            oci_execute($stid);
            
            $entidades = [];
            while ($row = oci_fetch_assoc($stid)) {
                $entidades[] = [
                    'codigo' => $row['CODIGO'],
                    'descripcion' => $row['DESCRIPCION'],
                    'palabras_clave' => $this->extraerPalabrasClave($row['DESCRIPCION'] ?? '')
                ];
            }
            
            oci_free_statement($stid);
            return $entidades;
        } catch (Exception $e) {
            error_log("Error cargando entidades: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Cargar lista de situaciones (estados de proyectos)
     */
    private function cargarSituaciones() {
        try {
            $sql = "SELECT CODIGO, DESCRIPCION FROM SITUACION ORDER BY CODIGO";
            $stid = oci_parse($this->conn, $sql);
            oci_execute($stid);
            
            $situaciones = [];
            while ($row = oci_fetch_assoc($stid)) {
                $situaciones[] = [
                    'codigo' => $row['CODIGO'],
                    'descripcion' => $row['DESCRIPCION']
                ];
            }
            
            oci_free_statement($stid);
            return $situaciones;
        } catch (Exception $e) {
            error_log("Error cargando situaciones: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Cargar años disponibles en proyectos
     */
    private function cargarAnios() {
        try {
            $sql = "SELECT DISTINCT ANIO_PRO FROM PROYECTO ORDER BY ANIO_PRO DESC";
            $stid = oci_parse($this->conn, $sql);
            oci_execute($stid);
            
            $anios = [];
            while ($row = oci_fetch_assoc($stid)) {
                $anios[] = $row['ANIO_PRO'];
            }
            
            oci_free_statement($stid);
            return $anios;
        } catch (Exception $e) {
            error_log("Error cargando años: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Extraer palabras clave de un texto para mejorar las búsquedas
     */
    private function extraerPalabrasClave($texto) {
        if (empty($texto)) {
            return [];
        }
        
        // Eliminar palabras comunes (artículos, preposiciones, etc.)
        $stopwords = ['de', 'del', 'la', 'las', 'los', 'el', 'en', 'y', 'para', 'por', 'con', 'a'];
        
        $palabras = preg_split('/\s+/', strtolower($texto));
        $palabrasFiltradas = [];
        
        foreach ($palabras as $palabra) {
            $palabra = trim($palabra);
            if (strlen($palabra) > 3 && !in_array($palabra, $stopwords)) {
                $palabrasFiltradas[] = $palabra;
            }
        }
        
        return $palabrasFiltradas;
    }

    /**
     * Convertir consulta en lenguaje natural a consulta SQL y ejecutarla
     * @param string $query Consulta en lenguaje natural
     * @return array Resultados de la consulta
     */
    public function processNaturalQuery($query) {
        try {
            if (empty($query)) {
                throw new Exception("La consulta está vacía");
            }
            
            // Procesamiento preliminar de la consulta
            $queryInfo = $this->analizarConsulta($query);
            
            // Generar consulta SQL basada en los parámetros extraídos
            $sqlQuery = $this->generarConsultaSQL($queryInfo);
            
            // Agregar información sobre la consulta generada para depuración
            $queryInfo['sql_generado'] = $sqlQuery['query'];
            
            // Ejecutar la consulta
            $results = $this->executeQuery($sqlQuery['query'], $sqlQuery['params']);
            
            // Añadir información de la consulta para mostrar
            $results['query_info'] = $queryInfo;
            
            return $results;
        } catch (Exception $e) {
            error_log("Error en procesamiento de consulta: " . $e->getMessage());
            return ['error' => $e->getMessage()];
        }
    }

    /**
     * Analizar consulta en lenguaje natural para extraer parámetros
     * @param string $query Consulta en lenguaje natural
     * @return array Parámetros extraídos
     */
    private function analizarConsulta($query) {
        // Verificar que la consulta no es nula
        if ($query === null) {
            $query = '';
        }
        
        // Normalizar la consulta
        $query = mb_strtolower(trim($query));
        
        // Estructura para almacenar los parámetros extraídos
        $params = [
            'anio' => null,
            'entidad' => null,
            'entidad_codigo' => null,
            'situacion' => null,
            'situacion_codigo' => null,
            'valor_min' => null,
            'valor_max' => null,
            'fecha_inicio' => null,
            'fecha_fin' => null,
            'texto_original' => $query,
            'modo' => 'basico' // Modo de consulta: basico o avanzado
        ];
        
        // Extraer año
        if (preg_match('/(?:año|anio|del)\s+(\d{4})|(\d{4})/', $query, $matches)) {
            $params['anio'] = !empty($matches[1]) ? $matches[1] : $matches[2];
        }
        
        // Buscar patrones para rango de años
        if (preg_match('/(?:entre|desde)\s+(\d{4})\s+(?:y|hasta)\s+(\d{4})/', $query, $matches)) {
            $params['anio_inicio'] = $matches[1];
            $params['anio_fin'] = $matches[2];
            $params['modo'] = 'avanzado';
        }
        
        // Extraer entidad
        $entidadEncontrada = $this->buscarEntidadEnTexto($query);
        if ($entidadEncontrada) {
            $params['entidad'] = $entidadEncontrada['descripcion'];
            $params['entidad_codigo'] = $entidadEncontrada['codigo'];
        }
        
        // Extraer situación/estado
        $situacionEncontrada = $this->buscarSituacionEnTexto($query);
        if ($situacionEncontrada) {
            $params['situacion'] = $situacionEncontrada['descripcion'];
            $params['situacion_codigo'] = $situacionEncontrada['codigo'];
        } else if (strpos($query, 'suscrito') !== false || strpos($query, 'suscritos') !== false) {
            // Asignar estado "Suscrito" si se menciona explícitamente
            foreach ($this->situaciones as $situacion) {
                if (strtolower($situacion['descripcion']) == 'suscrito') {
                    $params['situacion'] = $situacion['descripcion'];
                    $params['situacion_codigo'] = $situacion['codigo'];
                    break;
                }
            }
        } else if (strpos($query, 'ejecuci') !== false) {
            // Asignar estado "En Ejecución" si se menciona
            foreach ($this->situaciones as $situacion) {
                if (strpos(strtolower($situacion['descripcion']), 'ejecuci') !== false) {
                    $params['situacion'] = $situacion['descripcion'];
                    $params['situacion_codigo'] = $situacion['codigo'];
                    break;
                }
            }
        }
        
        // Extraer rango de valor
        if (preg_match('/valor\s+(?:mayor|superior)\s+(?:a|que)\s+(\d+(?:[\.,]\d+)?)\s*(?:mil|millon(?:es)?)?/u', $query, $matches)) {
            $valor = floatval(str_replace(',', '.', $matches[1]));
            if (strpos($query, 'mil') !== false) {
                $valor *= 1000;
            } else if (strpos($query, 'millon') !== false) {
                $valor *= 1000000;
            }
            $params['valor_min'] = $valor;
            $params['modo'] = 'avanzado';
        }
        
        if (preg_match('/valor\s+(?:menor|inferior)\s+(?:a|que)\s+(\d+(?:[\.,]\d+)?)\s*(?:mil|millon(?:es)?)?/u', $query, $matches)) {
            $valor = floatval(str_replace(',', '.', $matches[1]));
            if (strpos($query, 'mil') !== false) {
                $valor *= 1000;
            } else if (strpos($query, 'millon') !== false) {
                $valor *= 1000000;
            }
            $params['valor_max'] = $valor;
            $params['modo'] = 'avanzado';
        }
        
        return $params;
    }
    
    /**
     * Buscar entidad en el texto de la consulta
     */
    private function buscarEntidadEnTexto($texto) {
        if (empty($texto)) {
            return null;
        }
        
        // Preparar el texto para búsqueda
        $texto = mb_strtolower($texto);
        
        // 1. Intentar primero coincidencia exacta o casi exacta
        foreach ($this->entidades as $entidad) {
            $descripcion = mb_strtolower($entidad['descripcion']);
            
            // Verificar si la descripción de la entidad está en el texto
            if (strpos($texto, $descripcion) !== false) {
                return $entidad;
            }
            
            // Verificar versiones abreviadas o alternativas comunes
            $nombreCorto = preg_replace('/^(ministerio|instituto|agencia|secretaría|gobernación|alcaldía)\s+de\s+/i', '', $descripcion);
            if (strlen($nombreCorto) > 5 && strpos($texto, $nombreCorto) !== false) {
                return $entidad;
            }
        }
        
        // 2. Buscar por palabras clave
        $mejorCoincidencia = null;
        $maxPalabrasCoindicentes = 0;
        
        foreach ($this->entidades as $entidad) {
            $palabrasCoincidentes = 0;
            
            foreach ($entidad['palabras_clave'] as $palabra) {
                if (strpos($texto, $palabra) !== false) {
                    $palabrasCoincidentes++;
                }
            }
            
            // Si encontramos al menos 2 palabras clave coincidentes y es la mejor coincidencia hasta ahora
            if ($palabrasCoincidentes >= 2 && $palabrasCoincidentes > $maxPalabrasCoindicentes) {
                $mejorCoincidencia = $entidad;
                $maxPalabrasCoindicentes = $palabrasCoincidentes;
            }
        }
        
        return $mejorCoincidencia;
    }
    
    /**
     * Buscar situación en el texto de la consulta
     */
    private function buscarSituacionEnTexto($texto) {
        if (empty($texto)) {
            return null;
        }
        
        $texto = mb_strtolower($texto);
        
        foreach ($this->situaciones as $situacion) {
            $descripcion = mb_strtolower($situacion['descripcion']);
            
            if (strpos($texto, $descripcion) !== false) {
                return $situacion;
            }
        }
        
        return null;
    }

    /**
     * Generar consulta SQL basada en los parámetros extraídos
     * @param array $params Parámetros extraídos de la consulta
     * @return array Consulta SQL y parámetros
     */
    private function generarConsultaSQL($params) {
        // Base de la consulta
        $sql = "SELECT 
                p.NUMERO_PRO, 
                p.ANIO_PRO,
                p.NOMBRE, 
                p.OBJETO,
                p.VALOR,
                p.FECHA_INICIO,
                p.FECHA_TERMINA,
                s.DESCRIPCION as SITUACION,
                e.DESCRIPCION as ENTIDAD,
                p.NUMERO_PRO as ID
            FROM 
                PROYECTO p
            JOIN 
                ENTE_SUSCRIPTOR es ON p.NUMERO_PRO = es.NUMERO_PRO
            JOIN 
                ENTIDAD e ON es.ENTIDAD = e.CODIGO
            JOIN 
                SITUACION s ON p.SITUACION = s.CODIGO
            WHERE 
                p.ESTADO = 'A'";
        
        $sqlParams = [];
        
        // Añadir condiciones según los parámetros encontrados
        if (!empty($params['anio'])) {
            $sql .= " AND p.ANIO_PRO = :anio";
            $sqlParams['anio'] = $params['anio'];
        }
        
        if (!empty($params['anio_inicio']) && !empty($params['anio_fin'])) {
            $sql .= " AND p.ANIO_PRO BETWEEN :anio_inicio AND :anio_fin";
            $sqlParams['anio_inicio'] = $params['anio_inicio'];
            $sqlParams['anio_fin'] = $params['anio_fin'];
        }
        
        if (!empty($params['entidad_codigo'])) {
            $sql .= " AND es.ENTIDAD = :entidad_codigo";
            $sqlParams['entidad_codigo'] = $params['entidad_codigo'];
        }
        
        if (!empty($params['situacion_codigo'])) {
            $sql .= " AND p.SITUACION = :situacion_codigo";
            $sqlParams['situacion_codigo'] = $params['situacion_codigo'];
        }
        
        if (!empty($params['valor_min'])) {
            $sql .= " AND p.VALOR >= :valor_min";
            $sqlParams['valor_min'] = $params['valor_min'];
        }
        
        if (!empty($params['valor_max'])) {
            $sql .= " AND p.VALOR <= :valor_max";
            $sqlParams['valor_max'] = $params['valor_max'];
        }
        
        // Ordenar resultados
        $sql .= " ORDER BY p.ANIO_PRO DESC, p.FECHA_INICIO DESC";
        
        // Limitar resultados - IMPORTANTE: Oracle usa FETCH FIRST, no LIMIT
        $sql .= " FETCH FIRST 50 ROWS ONLY";
        
        return [
            'query' => $sql,
            'params' => $sqlParams
        ];
    }

    /**
     * Ejecutar consulta SQL con parámetros
     * @param string $sqlQuery Consulta SQL a ejecutar
     * @param array $params Parámetros de la consulta
     * @return array Resultados de la consulta
     */
    private function executeQuery($sqlQuery, $params = []) {
        try {
            // Guardar la consulta
            $results = [
                'sql' => $sqlQuery,
                'params' => $params,
                'data' => []
            ];
            
            // Preparar la consulta
            $stid = oci_parse($this->conn, $sqlQuery);
            if (!$stid) {
                $e = oci_error($this->conn);
                throw new Exception("Error al preparar consulta: " . $e['message']);
            }

            // Vincular parámetros
            foreach ($params as $key => $value) {
                oci_bind_by_name($stid, ":$key", $params[$key]);
            }

            // Ejecutar la consulta
            $r = oci_execute($stid);
            if (!$r) {
                $e = oci_error($stid);
                throw new Exception("Error al ejecutar consulta: " . $e['message']);
            }

            // Obtener resultados
            while ($row = oci_fetch_assoc($stid)) {
                // Formatear fechas
                if (isset($row['FECHA_INICIO'])) {
                    $row['FECHA_INICIO_FORMATEADA'] = $this->formatearFecha($row['FECHA_INICIO']);
                }
                if (isset($row['FECHA_TERMINA'])) {
                    $row['FECHA_TERMINA_FORMATEADA'] = $this->formatearFecha($row['FECHA_TERMINA']);
                }
                
                // Formatear valores monetarios
                if (isset($row['VALOR'])) {
                    $row['VALOR_FORMATEADO'] = $this->formatearMoneda($row['VALOR']);
                }
                
                $results['data'][] = $row;
            }

            // Liberar recursos
            oci_free_statement($stid);

            return $results;
        } catch (Exception $e) {
            error_log("Error en ejecución de consulta: " . $e->getMessage());
            return ['error' => $e->getMessage()];
        }
    }
    
    /**
     * Formatear fecha para mostrar
     */
    private function formatearFecha($fecha) {
        if (!$fecha) return '';
        
        try {
            $date = new DateTime($fecha);
            return $date->format('d/m/Y');
        } catch (Exception $e) {
            return $fecha;
        }
    }
    
    /**
     * Formatear valor monetario
     */
    private function formatearMoneda($valor) {
        if (!$valor) return '$0';
        
        return '$' . number_format($valor, 0, '.', ',');
    }

    /**
     * Formatear resultados para mostrar como HTML
     * @param array $results Resultados de la consulta
     * @return string HTML formateado
     */
    public function formatResults($results) {
        if (isset($results['error'])) {
            return '<div class="alert alert-danger">' . htmlspecialchars($results['error']) . '</div>';
        }
        
        if (empty($results['data'])) {
            return '<div class="alert alert-warning">No se encontraron resultados que coincidan con su consulta.</div>';
        }
        
        // Mostrar información sobre la consulta interpretada
        $html = '<div class="mb-4">';
        if (isset($results['query_info'])) {
            $info = $results['query_info'];
            $html .= '<div class="card border bg-light">';
            $html .= '<div class="card-body">';
            $html .= '<h6 class="mb-2">Parámetros interpretados:</h6>';
            $html .= '<ul class="mb-0">';
            
            if (!empty($info['anio'])) {
                $html .= '<li><strong>Año:</strong> ' . htmlspecialchars($info['anio']) . '</li>';
            }
            
            if (!empty($info['anio_inicio']) && !empty($info['anio_fin'])) {
                $html .= '<li><strong>Periodo:</strong> ' . htmlspecialchars($info['anio_inicio']) . ' a ' . htmlspecialchars($info['anio_fin']) . '</li>';
            }
            
            if (!empty($info['entidad'])) {
                $html .= '<li><strong>Entidad:</strong> ' . htmlspecialchars($info['entidad']) . '</li>';
            }
            
            if (!empty($info['situacion'])) {
                $html .= '<li><strong>Situación:</strong> ' . htmlspecialchars($info['situacion']) . '</li>';
            }
            
            if (!empty($info['valor_min'])) {
                $html .= '<li><strong>Valor mínimo:</strong> $' . number_format($info['valor_min'], 0, '.', ',') . '</li>';
            }
            
            if (!empty($info['valor_max'])) {
                $html .= '<li><strong>Valor máximo:</strong> $' . number_format($info['valor_max'], 0, '.', ',') . '</li>';
            }
            
            $html .= '</ul>';
            $html .= '</div>';
            $html .= '</div>';
        }
        $html .= '</div>';
        
        // Número de resultados encontrados
        $html .= '<div class="alert alert-info">';
        $html .= '<i class="fas fa-info-circle me-2"></i> Se encontraron <strong>' . count($results['data']) . '</strong> proyectos que coinciden con su consulta.';
        $html .= '</div>';
        
        // Tabla de resultados
        $html .= '<div class="table-responsive">';
        $html .= '<table class="table table-striped table-hover">';
        
        // Encabezados
        $html .= '<thead>';
        $html .= '<tr>';
        $html .= '<th>Código</th>';
        $html .= '<th>Nombre</th>';
        $html .= '<th>Entidad</th>';
        $html .= '<th>Situación</th>';
        $html .= '<th>Valor</th>';
        $html .= '<th>Fecha inicio</th>';
        $html .= '<th>Fecha fin</th>';
        $html .= '<th class="text-center">Acciones</th>';
        $html .= '</tr>';
        $html .= '</thead>';
        
        // Filas
        $html .= '<tbody>';
        foreach ($results['data'] as $row) {
            $html .= '<tr>';
            $html .= '<td>' . (isset($row['ANIO_PRO']) ? $row['ANIO_PRO'] . '-' : '') . htmlspecialchars($row['NUMERO_PRO']) . '</td>';
            $html .= '<td style="max-width: 300px;">' . htmlspecialchars($row['NOMBRE']) . '</td>';
            $html .= '<td>' . htmlspecialchars($row['ENTIDAD']) . '</td>';
            
            // Aplicar estilo según situación
            $situacionClass = '';
            if (isset($row['SITUACION'])) {
                $situacion = strtolower($row['SITUACION']);
                if (strpos($situacion, 'ejecuc') !== false) {
                    $situacionClass = 'bg-info text-white';
                } else if (strpos($situacion, 'suscrit') !== false) {
                    $situacionClass = 'bg-warning';
                } else if (strpos($situacion, 'finaliz') !== false) {
                    $situacionClass = 'bg-success text-white';
                }
            }
            
            $html .= '<td><span class="badge ' . $situacionClass . '">' . htmlspecialchars($row['SITUACION'] ?? 'N/D') . '</span></td>';
            
            $html .= '<td>' . htmlspecialchars($row['VALOR_FORMATEADO'] ?? $row['VALOR']) . '</td>';
            $html .= '<td>' . htmlspecialchars($row['FECHA_INICIO_FORMATEADA'] ?? $row['FECHA_INICIO']) . '</td>';
            $html .= '<td>' . htmlspecialchars($row['FECHA_TERMINA_FORMATEADA'] ?? $row['FECHA_TERMINA']) . '</td>';
            
            // Botones de acción
            $html .= '<td class="text-center">';
            $html .= '<a href="main.php?page=proyecto_individual&id=' . $row['ID'] . '" class="btn btn-sm btn-primary">';
            $html .= '<i class="fas fa-eye"></i> Ver';
            $html .= '</a>';
            $html .= '</td>';
            
            $html .= '</tr>';
        }
        $html .= '</tbody>';
        
        $html .= '</table>';
        $html .= '</div>';
        
        return $html;
    }

    /**
     * Destructor para cerrar la conexión a la base de datos
     */
    public function __destruct() {
        if ($this->conn) {
            oci_close($this->conn);
        }
    }
}

// Procesar solicitud de consulta
$queryProcessor = null;
$results = null;
$errorMessage = null;
$consulta_ejemplo = "Por ejemplo: \"dame los proyectos del año 2024 suscritos con la Agencia Nacional de Tierras\"";

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['natural_query'])) {
    try {
        $queryProcessor = new NaturalLanguageQueryProcessor();
        $naturalQuery = trim($_POST['natural_query']);
        
        // Procesar consulta y obtener resultados
        $results = $queryProcessor->processNaturalQuery($naturalQuery);
        
        // Verificar si hay un error
        if (isset($results['error'])) {
            $errorMessage = $results['error'];
            $results = null;
        }
    } catch (Exception $e) {
        $errorMessage = "Error en el procesamiento de consulta: " . $e->getMessage();
    }
}
?>
<!-- Diseño de la página de Consulta Natural -->
<div class="container-fluid py-4">
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header pb-0">
                    <h4 class="card-title">Consulta en Lenguaje Natural</h4>
                    <p class="text-sm mb-0">Realice consultas sobre proyectos usando lenguaje cotidiano</p>
                </div>
                <div class="card-body">
                    <form method="POST" action="">
                        <div class="row">
                            <div class="col-md-9 mb-3">
                                <div class="form-group">
                                    <textarea 
                                        class="form-control" 
                                        id="natural_query" 
                                        name="natural_query" 
                                        rows="3" 
                                        placeholder="<?php echo $consulta_ejemplo; ?>"
                                        required><?php echo isset($_POST['natural_query']) ? htmlspecialchars($_POST['natural_query']) : ''; ?></textarea>
                                    <small class="form-text text-muted mt-1">
                                        Puede preguntar por año, entidad, estado (suscrito, en ejecución, etc.) o valor.
                                    </small>
                                </div>
                            </div>
                            <div class="col-md-3 d-flex align-items-end">
                                <button type="submit" class="btn btn-primary w-100">
                                    <i class="fas fa-search me-2"></i> Consultar
                                </button>
                            </div>
                        </div>
                    </form>
                    
                    <div class="row mt-3">
                        <div class="col-12">
                            <div class="accordion" id="accordionExample">
                                <div class="accordion-item">
                                    <h2 class="accordion-header" id="headingOne">
                                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseOne" aria-expanded="false" aria-controls="collapseOne">
                                            <i class="fas fa-question-circle me-2"></i> 
                                            <strong>¿Cómo realizar consultas efectivas?</strong>
                                        </button>
                                    </h2>
                                    <div id="collapseOne" class="accordion-collapse collapse" aria-labelledby="headingOne" data-bs-parent="#accordionExample">
                                        <div class="accordion-body">
                                            <p>Puedes realizar consultas como:</p>
                                            <ul>
                                                <li><strong>Por año:</strong> "Proyectos del año 2024"</li>
                                                <li><strong>Por entidad:</strong> "Mostrar proyectos con el Ministerio de Educación"</li>
                                                <li><strong>Por situación:</strong> "Proyectos en ejecución"</li>
                                                <li><strong>Combinaciones:</strong> "Proyectos suscritos del 2023 con la Alcaldía de Bogotá"</li>
                                                <li><strong>Por valor:</strong> "Proyectos con valor mayor a 500 millones"</li>
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Área de resultados -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header pb-0">
                    <h4 class="card-title">Resultados</h4>
                </div>
                <div class="card-body">
                    <?php if ($errorMessage): ?>
                        <div class="alert alert-danger">
                            <?php echo htmlspecialchars($errorMessage); ?>
                        </div>
                    <?php endif; ?>

                    <?php 
                    if ($results !== null) {
                        echo $queryProcessor->formatResults($results);
                    } else if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                        echo '<div class="alert alert-warning">No se pudieron procesar los resultados.</div>';
                    } else {
                        echo '<div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i> Ingrese una consulta en lenguaje natural para comenzar la búsqueda.
                            <hr>
                            <p class="mb-0"><strong>Ejemplos de consultas:</strong></p>
                            <ul class="mb-0 consulta-ejemplos">
                                <li>Mostrar proyectos del 2024</li>
                                <li>Proyectos con el Ministerio de Educación</li>
                                <li>Proyectos en ejecución con valor mayor a 100 millones</li>
                                <li>Proyectos suscritos del año 2024 con la Agencia Nacional de Tierras</li>
                            </ul>
                        </div>';
                    }
                    ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Script para mejorar la experiencia del usuario -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Enfocar el campo de consulta automáticamente
    const queryInput = document.getElementById('natural_query');
    if (queryInput) {
        queryInput.focus();
    }
    
    // Validación del formulario
    const form = document.querySelector('form');
    if (form) {
        form.addEventListener('submit', function(e) {
            const query = queryInput.value.trim();
            if (query.length < 3) {
                e.preventDefault();
                alert('Por favor, ingrese una consulta más detallada.');
                queryInput.focus();
            }
        });
    }
    
    // Función para agregar ejemplos de consulta al hacer clic
    const addQueryExampleClickHandlers = function() {
        const examples = document.querySelectorAll('.consulta-ejemplos li');
        if (examples) {
            examples.forEach(example => {
                example.style.cursor = 'pointer';
                example.title = 'Haga clic para usar este ejemplo';
                example.addEventListener('click', function() {
                    if (queryInput) {
                        queryInput.value = this.textContent.trim();
                        queryInput.focus();
                        // Opcional: enviar el formulario automáticamente
                        // form.submit();
                    }
                });
            });
        }
    };
    
    addQueryExampleClickHandlers();
    
    // Agregar clase para resaltar ejemplos al pasar el mouse
    const highlightExamples = function() {
        const examples = document.querySelectorAll('.consulta-ejemplos li');
        if (examples) {
            examples.forEach(example => {
                example.addEventListener('mouseenter', function() {
                    this.style.backgroundColor = '#f0f7ff';
                    this.style.padding = '3px 6px';
                    this.style.borderRadius = '4px';
                    this.style.transition = 'all 0.2s ease';
                });
                example.addEventListener('mouseleave', function() {
                    this.style.backgroundColor = 'transparent';
                    this.style.padding = '0';
                });
            });
        }
    };
    
    highlightExamples();
});
</script>

<style>
    .consulta-ejemplos li {
        margin-bottom: 5px;
        transition: all 0.2s ease;
    }
    .badge {
        font-size: 0.75rem;
        padding: 0.5rem 0.75rem;
    }
</style>