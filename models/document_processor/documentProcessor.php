<?php
require __DIR__ . '/../../vendor/autoload.php';

use Smalot\PdfParser\Parser;
use Vanderlee\Sentence\Sentence;

class DocumentProcessor {
    /**
     * Extrae texto de un documento PDF
     * @param string $filePath Ruta al archivo PDF
     * @return string Texto extraído
     */
    public function extractTextFromPdf($filePath) {
        try {
            $parser = new Parser();
            $pdf = $parser->parseFile($filePath);
            $text = $pdf->getText();
            return $text;
        } catch (Exception $e) {
            error_log("Error extrayendo texto del PDF: " . $e->getMessage());
            return "";
        }
    }
    
    /**
     * Procesa un documento y extrae información relevante
     * @param string $filePath Ruta al documento
     * @return array Información extraída del documento
     */
    public function processDocument($filePath) {
        // Si es un texto plano, usar directamente
        if (strtolower(pathinfo($filePath, PATHINFO_EXTENSION)) === 'txt') {
            $text = file_get_contents($filePath);
        } else {
            // Para PDF u otros, extraer texto
            $text = $this->extractTextFromPdf($filePath);
        }

        // Si no se puede extraer texto, intentar procesar la consulta directamente
        if (empty($text)) {
            $text = file_get_contents($filePath);
        }

        // Si aún está vacío, devolver error
        if (empty($text)) {
            return ['error' => 'No se pudo extraer texto del documento'];
        }

        // Inicializar datos para extracción
        $data = [
            'nombre' => '',
            'objeto' => '',
            'entidad_principal' => 0,
            'entidad_nombre' => '',
            'valor' => 0,
            'fecha_inicio' => '',
            'fecha_termina' => '',
            'sql_query' => ''
        ];

        // Intentar generar consulta SQL basada en el texto
        $sqlResult = $this->generateSQLFromText($text);
        
        // Si se generó una consulta SQL, agregarla a los datos
        if (!empty($sqlResult['query'])) {
            $data['sql_query'] = $sqlResult['query'];
            
            // Agregar parámetros adicionales si están presentes
            if (!empty($sqlResult['params'])) {
                $data = array_merge($data, $sqlResult['params']);
            }
        }

        // Extraer información adicional
        $data = array_merge($data, $this->extractQueryDetails($text));

        return $data;
    }

    /**
     * Generar consulta SQL basada en el texto de entrada
     * @param string $text Texto de entrada
     * @return array Consulta SQL y parámetros
     */
    private function generateSQLFromText($text) {
        // Convertir texto a minúsculas para procesamiento
        $text = strtolower($text);

        // Patrones de consultas con mayor especificidad
        $patterns = [
            // Proyectos con valor mayor que
            [
                'pattern' => '/proyectos\s+con\s+valor\s+(mayor|superiores?)\s+(?:a|que)\s+(\d+)(?:\s*(?:mil|millón|millones))?/u',
                'generator' => function($matches) {
                    $valor = $matches[2];
                    // Convertir a número adecuado si incluye términos como "mil" o "millón"
                    if (strpos($matches[0], 'mil') !== false) {
                        $valor *= 1000;
                    } elseif (strpos($matches[0], 'millón') !== false || strpos($matches[0], 'millones') !== false) {
                        $valor *= 1000000;
                    }

                    return [
                        'query' => "SELECT p.NUMERO_PRO, p.NOMBRE, p.OBJETO, p.VALOR, p.FECHA_INICIO, p.FECHA_TERMINA, s.DESCRIPCION as SITUACION, e.DESCRIPCION as ENTIDAD, p.NUMERO_PRO as ID 
                                    FROM PROYECTO p 
                                    JOIN ENTE_SUSCRIPTOR es ON p.NUMERO_PRO = es.NUMERO_PRO 
                                    JOIN ENTIDAD e ON es.ENTIDAD = e.CODIGO 
                                    JOIN SITUACION s ON p.SITUACION = s.CODIGO 
                                    WHERE p.VALOR > :valor AND p.ESTADO = 'A'
                                    ORDER BY p.ANIO_PRO DESC, p.FECHA_INICIO DESC",
                        'params' => ['valor' => $valor]
                    ];
                }
            ],
            
            // Proyectos en un año específico
            [
                'pattern' => '/proyectos\s+(?:del|en)\s+año\s+(\d{4})/u',
                'generator' => function($matches) {
                    $anio = $matches[1];
                    return [
                        'query' => "SELECT p.NUMERO_PRO, p.NOMBRE, p.OBJETO, p.VALOR, p.FECHA_INICIO, p.FECHA_TERMINA, s.DESCRIPCION as SITUACION, e.DESCRIPCION as ENTIDAD, p.NUMERO_PRO as ID 
                                    FROM PROYECTO p 
                                    JOIN ENTE_SUSCRIPTOR es ON p.NUMERO_PRO = es.NUMERO_PRO 
                                    JOIN ENTIDAD e ON es.ENTIDAD = e.CODIGO 
                                    JOIN SITUACION s ON p.SITUACION = s.CODIGO 
                                    WHERE p.ANIO_PRO = :anio AND p.ESTADO = 'A'
                                    ORDER BY p.FECHA_INICIO DESC",
                        'params' => ['anio' => $anio]
                    ];
                }
            ],
            
            // Proyectos de una entidad específica (mejorado)
            [
                'pattern' => '/proyectos\s+(?:de|con|por|para)\s+(?:la|el)?\s*([a-záéíóúñ\s]+)(?=[\s,\.;]|$)/ui',
                'generator' => function($matches) {
                    $entidad = trim($matches[1]);
                    return [
                        'query' => "SELECT p.NUMERO_PRO, p.NOMBRE, p.OBJETO, p.VALOR, p.FECHA_INICIO, p.FECHA_TERMINA, s.DESCRIPCION as SITUACION, e.DESCRIPCION as ENTIDAD, p.NUMERO_PRO as ID 
                                    FROM PROYECTO p 
                                    JOIN ENTE_SUSCRIPTOR es ON p.NUMERO_PRO = es.NUMERO_PRO 
                                    JOIN ENTIDAD e ON es.ENTIDAD = e.CODIGO 
                                    JOIN SITUACION s ON p.SITUACION = s.CODIGO 
                                    WHERE UPPER(e.DESCRIPCION) LIKE UPPER(:entidad) AND p.ESTADO = 'A'
                                    ORDER BY p.ANIO_PRO DESC, p.FECHA_INICIO DESC",
                        'params' => ['entidad' => '%' . $entidad . '%']
                    ];
                }
            ],
            
            // Proyectos entre fechas
            [
                'pattern' => '/proyectos\s+(?:desde|entre)\s+(\d{4})\s+(?:y|hasta)\s+(\d{4})/u',
                'generator' => function($matches) {
                    $anioInicio = $matches[1];
                    $anioFin = $matches[2];
                    return [
                        'query' => "SELECT p.NUMERO_PRO, p.NOMBRE, p.OBJETO, p.VALOR, p.FECHA_INICIO, p.FECHA_TERMINA, s.DESCRIPCION as SITUACION, e.DESCRIPCION as ENTIDAD, p.NUMERO_PRO as ID 
                                    FROM PROYECTO p 
                                    JOIN ENTE_SUSCRIPTOR es ON p.NUMERO_PRO = es.NUMERO_PRO 
                                    JOIN ENTIDAD e ON es.ENTIDAD = e.CODIGO 
                                    JOIN SITUACION s ON p.SITUACION = s.CODIGO 
                                    WHERE p.ANIO_PRO BETWEEN :anio_inicio AND :anio_fin AND p.ESTADO = 'A'
                                    ORDER BY p.ANIO_PRO DESC, p.FECHA_INICIO DESC",
                        'params' => [
                            'anio_inicio' => $anioInicio, 
                            'anio_fin' => $anioFin
                        ]
                    ];
                }
            ],
            
            // Proyectos por situación (suscrito, en ejecución, etc.)
            [
                'pattern' => '/proyectos\s+(?:en\s+)?(\w+ción|suscritos?)/ui',
                'generator' => function($matches) {
                    $situacion = trim($matches[1]);
                    $codigoSituacion = null;
                    
                    // Mapear términos comunes a códigos de situación
                    if (preg_match('/^(suscri)/i', $situacion)) {
                        $codigoSituacion = 8; // Asumiendo que 8 es el código para "Suscrito"
                    } else if (preg_match('/^(ejecu)/i', $situacion)) {
                        $codigoSituacion = 14; // Asumiendo que 14 es el código para "En Ejecución"
                    }
                    
                    if ($codigoSituacion) {
                        return [
                            'query' => "SELECT p.NUMERO_PRO, p.NOMBRE, p.OBJETO, p.VALOR, p.FECHA_INICIO, p.FECHA_TERMINA, s.DESCRIPCION as SITUACION, e.DESCRIPCION as ENTIDAD, p.NUMERO_PRO as ID 
                                        FROM PROYECTO p 
                                        JOIN ENTE_SUSCRIPTOR es ON p.NUMERO_PRO = es.NUMERO_PRO 
                                        JOIN ENTIDAD e ON es.ENTIDAD = e.CODIGO 
                                        JOIN SITUACION s ON p.SITUACION = s.CODIGO 
                                        WHERE p.SITUACION = :situacion_codigo AND p.ESTADO = 'A'
                                        ORDER BY p.ANIO_PRO DESC, p.FECHA_INICIO DESC",
                            'params' => ['situacion_codigo' => $codigoSituacion]
                        ];
                    }
                    
                    // Si no se pudo mapear a un código específico, buscar por descripción
                    return [
                        'query' => "SELECT p.NUMERO_PRO, p.NOMBRE, p.OBJETO, p.VALOR, p.FECHA_INICIO, p.FECHA_TERMINA, s.DESCRIPCION as SITUACION, e.DESCRIPCION as ENTIDAD, p.NUMERO_PRO as ID 
                                    FROM PROYECTO p 
                                    JOIN ENTE_SUSCRIPTOR es ON p.NUMERO_PRO = es.NUMERO_PRO 
                                    JOIN ENTIDAD e ON es.ENTIDAD = e.CODIGO 
                                    JOIN SITUACION s ON p.SITUACION = s.CODIGO 
                                    WHERE UPPER(s.DESCRIPCION) LIKE UPPER(:situacion) AND p.ESTADO = 'A'
                                    ORDER BY p.ANIO_PRO DESC, p.FECHA_INICIO DESC",
                        'params' => ['situacion' => '%' . $situacion . '%']
                    ];
                }
            ],
            
            // Combinación de año y entidad
            [
                'pattern' => '/proyectos\s+(?:del|en)\s+año\s+(\d{4})\s+(?:de|con|por|para)\s+(?:la|el)?\s*([a-záéíóúñ\s]+)(?=[\s,\.;]|$)/ui',
                'generator' => function($matches) {
                    $anio = $matches[1];
                    $entidad = trim($matches[2]);
                    return [
                        'query' => "SELECT p.NUMERO_PRO, p.NOMBRE, p.OBJETO, p.VALOR, p.FECHA_INICIO, p.FECHA_TERMINA, s.DESCRIPCION as SITUACION, e.DESCRIPCION as ENTIDAD, p.NUMERO_PRO as ID 
                                    FROM PROYECTO p 
                                    JOIN ENTE_SUSCRIPTOR es ON p.NUMERO_PRO = es.NUMERO_PRO 
                                    JOIN ENTIDAD e ON es.ENTIDAD = e.CODIGO 
                                    JOIN SITUACION s ON p.SITUACION = s.CODIGO 
                                    WHERE p.ANIO_PRO = :anio AND UPPER(e.DESCRIPCION) LIKE UPPER(:entidad) AND p.ESTADO = 'A'
                                    ORDER BY p.FECHA_INICIO DESC",
                        'params' => [
                            'anio' => $anio,
                            'entidad' => '%' . $entidad . '%'
                        ]
                    ];
                }
            ],
            
            // Combinación de año, situación y entidad
            [
                'pattern' => '/proyectos\s+(\w+ción|suscritos?)\s+(?:del|en)\s+año\s+(\d{4})\s+(?:de|con|por|para)\s+(?:la|el)?\s*([a-záéíóúñ\s]+)(?=[\s,\.;]|$)/ui',
                'generator' => function($matches) {
                    $situacion = trim($matches[1]);
                    $anio = $matches[2];
                    $entidad = trim($matches[3]);
                    
                    $codigoSituacion = null;
                    
                    // Mapear términos comunes a códigos de situación
                    if (preg_match('/^(suscri)/i', $situacion)) {
                        $codigoSituacion = 8; // Asumiendo que 8 es el código para "Suscrito"
                    } else if (preg_match('/^(ejecu)/i', $situacion)) {
                        $codigoSituacion = 14; // Asumiendo que 14 es el código para "En Ejecución"
                    }
                    
                    if ($codigoSituacion) {
                        return [
                            'query' => "SELECT p.NUMERO_PRO, p.NOMBRE, p.OBJETO, p.VALOR, p.FECHA_INICIO, p.FECHA_TERMINA, s.DESCRIPCION as SITUACION, e.DESCRIPCION as ENTIDAD, p.NUMERO_PRO as ID 
                                        FROM PROYECTO p 
                                        JOIN ENTE_SUSCRIPTOR es ON p.NUMERO_PRO = es.NUMERO_PRO 
                                        JOIN ENTIDAD e ON es.ENTIDAD = e.CODIGO 
                                        JOIN SITUACION s ON p.SITUACION = s.CODIGO 
                                        WHERE p.ANIO_PRO = :anio AND p.SITUACION = :situacion_codigo AND UPPER(e.DESCRIPCION) LIKE UPPER(:entidad) AND p.ESTADO = 'A'
                                        ORDER BY p.FECHA_INICIO DESC",
                            'params' => [
                                'anio' => $anio,
                                'situacion_codigo' => $codigoSituacion,
                                'entidad' => '%' . $entidad . '%'
                            ]
                        ];
                    }
                    
                    // Si no se pudo mapear a un código específico
                    return [
                        'query' => "SELECT p.NUMERO_PRO, p.NOMBRE, p.OBJETO, p.VALOR, p.FECHA_INICIO, p.FECHA_TERMINA, s.DESCRIPCION as SITUACION, e.DESCRIPCION as ENTIDAD, p.NUMERO_PRO as ID 
                                    FROM PROYECTO p 
                                    JOIN ENTE_SUSCRIPTOR es ON p.NUMERO_PRO = es.NUMERO_PRO 
                                    JOIN ENTIDAD e ON es.ENTIDAD = e.CODIGO 
                                    JOIN SITUACION s ON p.SITUACION = s.CODIGO 
                                    WHERE p.ANIO_PRO = :anio AND UPPER(s.DESCRIPCION) LIKE UPPER(:situacion) AND UPPER(e.DESCRIPCION) LIKE UPPER(:entidad) AND p.ESTADO = 'A'
                                    ORDER BY p.FECHA_INICIO DESC",
                        'params' => [
                            'anio' => $anio,
                            'situacion' => '%' . $situacion . '%',
                            'entidad' => '%' . $entidad . '%'
                        ]
                    ];
                }
            ]
        ];

        // Probar cada patrón
        foreach ($patterns as $patternInfo) {
            if (preg_match($patternInfo['pattern'], $text, $matches)) {
                return $patternInfo['generator']($matches);
            }
        }

        // Consulta por defecto si no se encuentra un patrón específico
        return [
            'query' => "SELECT p.NUMERO_PRO, p.NOMBRE, p.OBJETO, p.VALOR, p.FECHA_INICIO, p.FECHA_TERMINA, s.DESCRIPCION as SITUACION, e.DESCRIPCION as ENTIDAD, p.NUMERO_PRO as ID 
                        FROM PROYECTO p 
                        JOIN ENTE_SUSCRIPTOR es ON p.NUMERO_PRO = es.NUMERO_PRO 
                        JOIN ENTIDAD e ON es.ENTIDAD = e.CODIGO 
                        JOIN SITUACION s ON p.SITUACION = s.CODIGO 
                        WHERE p.ESTADO = 'A' 
                        ORDER BY p.ANIO_PRO DESC, p.FECHA_INICIO DESC
                        FETCH FIRST 50 ROWS ONLY",
            'params' => []
        ];
    }

    /**
     * Extraer detalles adicionales de la consulta
     * @param string $text Texto de la consulta
     * @return array Detalles extraídos
     */
    private function extractQueryDetails($text) {
        $details = [];

        // Extraer nombre de proyecto si es posible
        if (preg_match('/proyecto\s+(?:llamado|denominado)?\s*"?([^".\n]+)/i', $text, $matches)) {
            $details['nombre'] = trim($matches[1]);
        }

        // Intentar extraer valor de proyecto
        if (preg_match('/valor\s+(?:de)?\s*(\d+(?:[\.,]\d+)?)\s*(?:mil|millón|millones)?/i', $text, $matches)) {
            $valor = floatval(str_replace(',', '.', $matches[1]));
            if (strpos($text, 'mil') !== false) {
                $valor *= 1000;
            } elseif (strpos($text, 'millón') !== false || strpos($text, 'millones') !== false) {
                $valor *= 1000000;
            }
            $details['valor'] = $valor;
        }

        // Extraer fechas si es posible
        $datePatterns = [
            '/(?:desde|inicio)\s+(?:el)?\s*(\d{1,2}[\/-]\d{1,2}[\/-]\d{2,4})/i',
            '/(?:hasta|termina)\s+(?:el)?\s*(\d{1,2}[\/-]\d{1,2}[\/-]\d{2,4})/i'
        ];

        foreach ($datePatterns as $index => $pattern) {
            if (preg_match($pattern, $text, $matches)) {
                $date = $this->parseDate($matches[1]);
                $key = $index === 0 ? 'fecha_inicio' : 'fecha_termina';
                $details[$key] = $date;
            }
        }
        
        // Extraer entidad
        if (preg_match('/(?:con|de|por)\s+(?:la|el)?\s+([a-záéíóúñ\s]+)(?:nacional|departamental|distrital|municipal)?(?=[\s,\.;]|$)/ui', $text, $matches)) {
            $details['entidad_nombre'] = trim($matches[1]);
        }

        return $details;
    }

    /**
     * Convierte una fecha en texto a formato Y-m-d
     * @param string $dateString Fecha en texto
     * @return string Fecha en formato Y-m-d o cadena vacía si no se puede parsear
     */
    private function parseDate($dateString) {
        // Intentar con strtotime para formatos comunes
        $timestamp = strtotime($dateString);
        if ($timestamp !== false) {
            return date('Y-m-d', $timestamp);
        }
        
        // Formatos adicionales de fecha
        $formats = [
            'd/m/Y', 'd-m-Y', 
            'm/d/Y', 'm-d-Y', 
            'Y-m-d'
        ];
        
        foreach ($formats as $format) {
            $date = \DateTime::createFromFormat($format, $dateString);
            if ($date && $date->format($format) === $dateString) {
                return $date->format('Y-m-d');
            }
        }
        
        return '';
    }
}