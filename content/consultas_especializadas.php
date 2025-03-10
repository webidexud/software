<?php
/**
 * Consultas Especializadas
 * Este archivo contiene consultas especializadas para el sistema de gestión de actas y proyectos
 * Optimizado para rendimiento y mantenibilidad
 */

class ConsultasEspecializadas {
    private $db;
    private $logger;
    private $config;

    /**
     * Constructor de la clase
     * @param PDO $conexion Conexión a la base de datos
     * @param Logger $logger Opcional: Sistema de logging
     * @param array $config Opcional: Configuración adicional
     */
    public function __construct($conexion, $logger = null, $config = []) {
        $this->db = $conexion;
        $this->logger = $logger;
        $this->config = $config;
        
        // Configurar errores PDO
        $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    }

    /**
     * Registra actividad en el sistema de logs si está disponible
     */
    private function log($mensaje, $nivel = 'info', $contexto = []) {
        if ($this->logger) {
            $this->logger->log($nivel, $mensaje, $contexto);
        }
    }
    
    /**
     * Obtiene proyectos con filtros específicos
     * @param array $filtros Arreglo asociativo con los filtros a aplicar
     * @param int $pagina Número de página para paginación
     * @param int $porPagina Elementos por página
     * @return array Proyectos que cumplen con los filtros
     */
    public function obtenerProyectosFiltrados($filtros = [], $pagina = 1, $porPagina = 20) {
        $offset = ($pagina - 1) * $porPagina;
        
        $sql = "SELECT p.id, p.nombre, p.fecha_inicio, p.fecha_fin, p.estado, 
                       p.presupuesto, p.prioridad, p.porcentaje_avance,
                       u.nombre as responsable, u.email as email_responsable,
                       d.nombre as departamento
                FROM proyectos p 
                LEFT JOIN usuarios u ON p.id_responsable = u.id 
                LEFT JOIN departamentos d ON p.id_departamento = d.id
                WHERE 1=1";
        
        $params = [];
        $conditions = [];
        
        // Filtros básicos
        if (!empty($filtros['vencidos_solo']) && $filtros['vencidos_solo']) {
            $sql .= " AND a.fecha_limite < CURDATE()";
        }
        
        if (!empty($filtros['proximos_solo']) && $filtros['proximos_solo']) {
            $sql .= " AND a.fecha_limite >= CURDATE() AND a.fecha_limite <= DATE_ADD(CURDATE(), INTERVAL 7 DAY)";
        }
        
        $sql .= " ORDER BY a.fecha_limite ASC";
        
        if (!empty($filtros['limite'])) {
            $sql .= " LIMIT " . (int)$filtros['limite'];
        }
        
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            $this->log("Error al obtener acuerdos pendientes: " . $e->getMessage(), 'error', ['id_usuario' => $idUsuario]);
            return [];
        }
    }
    
    /**
     * Obtiene proyectos por departamento
     * @param int $idDepartamento ID del departamento
     * @param string $estado Estado de los proyectos (opcional)
     * @return array Proyectos del departamento
     */
    public function obtenerProyectosPorDepartamento($idDepartamento, $estado = null) {
        $sql = "SELECT p.id, p.nombre, p.fecha_inicio, p.fecha_fin, p.estado, 
                       p.presupuesto, p.porcentaje_avance,
                       u.nombre as responsable
                FROM proyectos p 
                LEFT JOIN usuarios u ON p.id_responsable = u.id 
                WHERE p.id_departamento = ?";
        
        $params = [$idDepartamento];
        
        if (!empty($estado)) {
            $sql .= " AND p.estado = ?";
            $params[] = $estado;
        }
        
        $sql .= " ORDER BY p.fecha_inicio DESC";
        
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            $this->log("Error al obtener proyectos por departamento: " . $e->getMessage(), 'error', ['id_departamento' => $idDepartamento]);
            return [];
        }
    }
    
    /**
     * Obtiene informes de rendimiento por proyecto
     * @param int $idProyecto ID del proyecto
     * @param string $periodo Periodo para el informe (semana, mes, trimestre, año)
     * @return array Datos del informe
     */
    public function obtenerInformeRendimientoProyecto($idProyecto, $periodo = 'mes') {
        try {
            // Obtener datos básicos del proyecto
            $sqlProyecto = "SELECT id, nombre, fecha_inicio, fecha_fin, presupuesto, 
                                   porcentaje_avance, estado
                            FROM proyectos 
                            WHERE id = ?";
            
            $stmtProyecto = $this->db->prepare($sqlProyecto);
            $stmtProyecto->execute([$idProyecto]);
            $proyecto = $stmtProyecto->fetch(PDO::FETCH_ASSOC);
            
            if (!$proyecto) {
                return ['error' => 'Proyecto no encontrado'];
            }
            
            // Configurar período para consultas
            $formatoFecha = "";
            switch ($periodo) {
                case 'semana':
                    $formatoFecha = "%x-%v"; // Año-Semana
                    $agruparPor = "YEARWEEK(fecha, 1)";
                    break;
                case 'mes':
                    $formatoFecha = "%Y-%m"; // Año-Mes
                    $agruparPor = "YEAR(fecha), MONTH(fecha)";
                    break;
                case 'trimestre':
                    $formatoFecha = "%Y-%c"; // Año-Trimestre
                    $agruparPor = "YEAR(fecha), QUARTER(fecha)";
                    break;
                case 'año':
                    $formatoFecha = "%Y"; // Año
                    $agruparPor = "YEAR(fecha)";
                    break;
                default:
                    $formatoFecha = "%Y-%m-%d"; // Año-Mes-Día
                    $agruparPor = "DATE(fecha)";
                    break;
            }
            
            // Obtener progreso a lo largo del tiempo
            $sqlProgreso = "SELECT DATE_FORMAT(fecha, '$formatoFecha') as periodo, 
                                   porcentaje_avance
                            FROM historico_avance 
                            WHERE id_proyecto = ? 
                            ORDER BY fecha ASC";
            
            $stmtProgreso = $this->db->prepare($sqlProgreso);
            $stmtProgreso->execute([$idProyecto]);
            $datosProgreso = $stmtProgreso->fetchAll(PDO::FETCH_ASSOC);
            
            // Obtener actividad de actas
            $sqlActas = "SELECT DATE_FORMAT(fecha, '$formatoFecha') as periodo, 
                                COUNT(*) as total_actas
                         FROM actas 
                         WHERE id_proyecto = ? 
                         GROUP BY $agruparPor
                         ORDER BY MIN(fecha) ASC";
            
            $stmtActas = $this->db->prepare($sqlActas);
            $stmtActas->execute([$idProyecto]);
            $datosActas = $stmtActas->fetchAll(PDO::FETCH_ASSOC);
            
            // Obtener acuerdos completados vs pendientes
            $sqlAcuerdos = "SELECT DATE_FORMAT(a.fecha_limite, '$formatoFecha') as periodo, 
                                   a.estado, 
                                   COUNT(*) as total
                            FROM acuerdos a
                            JOIN actas ac ON a.id_acta = ac.id
                            WHERE ac.id_proyecto = ?
                            GROUP BY $agruparPor, a.estado
                            ORDER BY MIN(a.fecha_limite) ASC";
            
            $stmtAcuerdos = $this->db->prepare($sqlAcuerdos);
            $stmtAcuerdos->execute([$idProyecto]);
            $datosAcuerdosBrutos = $stmtAcuerdos->fetchAll(PDO::FETCH_ASSOC);
            
            // Procesar datos de acuerdos para formato más útil
            $datosAcuerdos = [];
            foreach ($datosAcuerdosBrutos as $dato) {
                if (!isset($datosAcuerdos[$dato['periodo']])) {
                    $datosAcuerdos[$dato['periodo']] = [
                        'periodo' => $dato['periodo'],
                        'completados' => 0,
                        'pendientes' => 0,
                        'vencidos' => 0
                    ];
                }
                
                if ($dato['estado'] == 'completado') {
                    $datosAcuerdos[$dato['periodo']]['completados'] = $dato['total'];
                } else if ($dato['estado'] == 'pendiente') {
                    $datosAcuerdos[$dato['periodo']]['pendientes'] = $dato['total'];
                } else if ($dato['estado'] == 'vencido') {
                    $datosAcuerdos[$dato['periodo']]['vencidos'] = $dato['total'];
                }
            }
            $datosAcuerdos = array_values($datosAcuerdos);
            
            // Obtener usuarios más activos
            $sqlUsuariosActivos = "SELECT u.id, u.nombre, COUNT(a.id) as total_acuerdos,
                                          SUM(CASE WHEN a.estado = 'completado' THEN 1 ELSE 0 END) as completados,
                                          SUM(CASE WHEN a.estado = 'pendiente' THEN 1 ELSE 0 END) as pendientes,
                                          SUM(CASE WHEN a.fecha_limite < CURDATE() AND a.estado = 'pendiente' THEN 1 ELSE 0 END) as vencidos
                                   FROM usuarios u
                                   JOIN acuerdos a ON u.id = a.id_responsable
                                   JOIN actas ac ON a.id_acta = ac.id
                                   WHERE ac.id_proyecto = ?
                                   GROUP BY u.id
                                   ORDER BY total_acuerdos DESC
                                   LIMIT 10";
            
            $stmtUsuariosActivos = $this->db->prepare($sqlUsuariosActivos);
            $stmtUsuariosActivos->execute([$idProyecto]);
            $usuariosActivos = $stmtUsuariosActivos->fetchAll(PDO::FETCH_ASSOC);
            
            // Consolidar todos los datos en un solo informe
            $informe = [
                'proyecto' => $proyecto,
                'progreso' => $datosProgreso,
                'actas' => $datosActas,
                'acuerdos' => $datosAcuerdos,
                'usuarios_activos' => $usuariosActivos,
                'periodo' => $periodo
            ];
            
            return $informe;
        } catch (PDOException $e) {
            $this->log("Error al generar informe: " . $e->getMessage(), 'error', ['id_proyecto' => $idProyecto]);
            return ['error' => 'Error al generar informe'];
        }
    }
    
    /**
     * Obtiene datos para tablero de control (dashboard)
     * @return array Datos para el dashboard
     */
    public function obtenerDatosDashboard() {
        try {
            $dashboard = [
                'estadisticas' => $this->obtenerEstadisticas('mes'),
                'proyectos_recientes' => $this->obtenerProyectosRecientes(5),
                'actas_recientes' => $this->obtenerActasRecientes(5),
                'acuerdos_proximos' => $this->obtenerAcuerdosProximos(5),
                'actividades_recientes' => $this->obtenerActividadesRecientes(10)
            ];
            
            return $dashboard;
        } catch (PDOException $e) {
            $this->log("Error al obtener datos de dashboard: " . $e->getMessage(), 'error');
            return [];
        }
    }
    
    /**
     * Obtiene proyectos recientes
     * @param int $limite Límite de registros
     * @return array Proyectos recientes
     */
    public function obtenerProyectosRecientes($limite = 5) {
        $sql = "SELECT p.id, p.nombre, p.fecha_inicio, p.estado, p.porcentaje_avance,
                       u.nombre as responsable
                FROM proyectos p
                LEFT JOIN usuarios u ON p.id_responsable = u.id
                ORDER BY p.fecha_creacion DESC
                LIMIT ?";
        
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$limite]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            $this->log("Error al obtener proyectos recientes: " . $e->getMessage(), 'error');
            return [];
        }
    }
    
    /**
     * Obtiene actas recientes
     * @param int $limite Límite de registros
     * @return array Actas recientes
     */
    public function obtenerActasRecientes($limite = 5) {
        $sql = "SELECT a.id, a.titulo, a.fecha, a.tipo, a.estado,
                       p.id as id_proyecto, p.nombre as proyecto
                FROM actas a
                JOIN proyectos p ON a.id_proyecto = p.id
                ORDER BY a.fecha DESC
                LIMIT ?";
        
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$limite]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            $this->log("Error al obtener actas recientes: " . $e->getMessage(), 'error');
            return [];
        }
    }
    
    /**
     * Obtiene acuerdos próximos a vencer
     * @param int $limite Límite de registros
     * @return array Acuerdos próximos
     */
    public function obtenerAcuerdosProximos($limite = 5) {
        $sql = "SELECT a.id, a.descripcion, a.fecha_limite, a.estado, a.prioridad,
                       u.nombre as responsable,
                       ac.id as id_acta, ac.titulo as acta,
                       p.id as id_proyecto, p.nombre as proyecto
                FROM acuerdos a
                JOIN usuarios u ON a.id_responsable = u.id
                JOIN actas ac ON a.id_acta = ac.id
                JOIN proyectos p ON ac.id_proyecto = p.id
                WHERE a.estado = 'pendiente' AND a.fecha_limite >= CURDATE()
                ORDER BY a.fecha_limite ASC
                LIMIT ?";
        
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$limite]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            $this->log("Error al obtener acuerdos próximos: " . $e->getMessage(), 'error');
            return [];
        }
    }
    
    /**
     * Obtiene estado general de un proyecto
     * @param int $idProyecto ID del proyecto
     * @return array Estado general del proyecto
     */
    public function obtenerEstadoGeneralProyecto($idProyecto) {
        try {
            // Información básica del proyecto
            $sqlProyecto = "SELECT p.*, u.nombre as responsable, d.nombre as departamento
                           FROM proyectos p
                           LEFT JOIN usuarios u ON p.id_responsable = u.id
                           LEFT JOIN departamentos d ON p.id_departamento = d.id
                           WHERE p.id = ?";
            
            $stmtProyecto = $this->db->prepare($sqlProyecto);
            $stmtProyecto->execute([$idProyecto]);
            $proyecto = $stmtProyecto->fetch(PDO::FETCH_ASSOC);
            
            if (!$proyecto) {
                return ['error' => 'Proyecto no encontrado'];
            }
            
            // Conteo de actas por tipo
            $sqlActasPorTipo = "SELECT tipo, COUNT(*) as total
                               FROM actas
                               WHERE id_proyecto = ?
                               GROUP BY tipo";
            
            $stmtActasPorTipo = $this->db->prepare($sqlActasPorTipo);
            $stmtActasPorTipo->execute([$idProyecto]);
            $actasPorTipo = $stmtActasPorTipo->fetchAll(PDO::FETCH_ASSOC);
            
            // Estado de acuerdos
            $sqlAcuerdos = "SELECT a.estado, COUNT(*) as total
                           FROM acuerdos a
                           JOIN actas ac ON a.id_acta = ac.id
                           WHERE ac.id_proyecto = ?
                           GROUP BY a.estado";
            
            $stmtAcuerdos = $this->db->prepare($sqlAcuerdos);
            $stmtAcuerdos->execute([$idProyecto]);
            $acuerdosPorEstado = $stmtAcuerdos->fetchAll(PDO::FETCH_ASSOC);
            
            // Participantes más activos
            $sqlParticipantes = "SELECT u.id, u.nombre, COUNT(DISTINCT pa.id_acta) as total_actas
                                FROM participantes_acta pa
                                JOIN actas a ON pa.id_acta = a.id
                                JOIN usuarios u ON pa.id_usuario = u.id
                                WHERE a.id_proyecto = ?
                                GROUP BY u.id
                                ORDER BY total_actas DESC
                                LIMIT 5";
            
            $stmtParticipantes = $this->db->prepare($sqlParticipantes);
            $stmtParticipantes->execute([$idProyecto]);
            $participantesActivos = $stmtParticipantes->fetchAll(PDO::FETCH_ASSOC);
            
            // Calcular días restantes o días de retraso
            $fechaActual = new DateTime();
            $fechaFin = new DateTime($proyecto['fecha_fin']);
            $diferencia = $fechaActual->diff($fechaFin);
            
            if ($fechaActual > $fechaFin && $proyecto['estado'] != 'finalizado') {
                $proyecto['dias_retraso'] = $diferencia->days;
                $proyecto['retrasado'] = true;
            } else {
                $proyecto['dias_restantes'] = $diferencia->days;
                $proyecto['retrasado'] = false;
            }
            
            // Consolidar información
            return [
                'proyecto' => $proyecto,
                'actas_por_tipo' => $actasPorTipo,
                'acuerdos_por_estado' => $acuerdosPorEstado,
                'participantes_activos' => $participantesActivos
            ];
            
        } catch (PDOException $e) {
            $this->log("Error al obtener estado del proyecto: " . $e->getMessage(), 'error', ['id_proyecto' => $idProyecto]);
            return ['error' => 'Error al obtener estado del proyecto'];
        }
    }
    
    /**
     * Exporta datos a formato CSV
     * @param string $tipo Tipo de datos a exportar (proyectos, actas, acuerdos)
     * @param array $filtros Filtros a aplicar
     * @return string Contenido CSV
     */
    public function exportarCSV($tipo, $filtros = []) {
        $datos = [];
        $cabeceras = [];
        
        switch ($tipo) {
            case 'proyectos':
                $cabeceras = ['ID', 'Nombre', 'Fecha Inicio', 'Fecha Fin', 'Estado', 'Responsable', 'Departamento', 'Presupuesto', 'Avance (%)'];
                $resultados = $this->obtenerProyectosFiltrados($filtros);
                if (isset($resultados['proyectos'])) {
                    $datos = $resultados['proyectos'];
                }
                break;
                
            case 'actas':
                $cabeceras = ['ID', 'Título', 'Fecha', 'Tipo', 'Estado', 'Proyecto', 'Creador'];
                if (!empty($filtros['id_proyecto'])) {
                    $datos = $this->obtenerActasProyecto($filtros['id_proyecto'], $filtros);
                } else {
                    // Implementar búsqueda general de actas
                    $sql = "SELECT a.id, a.titulo, a.fecha, a.tipo, a.estado,
                                  p.nombre as proyecto, u.nombre as creador
                           FROM actas a
                           JOIN proyectos p ON a.id_proyecto = p.id
                           JOIN usuarios u ON a.id_creador = u.id
                           WHERE 1=1";
                    
                    $params = [];
                    
                    if (!empty($filtros['tipo'])) {
                        $sql .= " AND a.tipo = ?";
                        $params[] = $filtros['tipo'];
                    }
                    
                    if (!empty($filtros['estado'])) {
                        $sql .= " AND a.estado = ?";
                        $params[] = $filtros['estado'];
                    }
                    
                    $sql .= " ORDER BY a.fecha DESC";
                    
                    $stmt = $this->db->prepare($sql);
                    $stmt->execute($params);
                    $datos = $stmt->fetchAll(PDO::FETCH_ASSOC);
                }
                break;
                
            case 'acuerdos':
                $cabeceras = ['ID', 'Descripción', 'Fecha Límite', 'Estado', 'Prioridad', 'Responsable', 'Acta', 'Proyecto'];
                $sql = "SELECT a.id, a.descripcion, a.fecha_limite, a.estado, a.prioridad,
                              u.nombre as responsable, ac.titulo as acta, p.nombre as proyecto
                       FROM acuerdos a
                       JOIN usuarios u ON a.id_responsable = u.id
                       JOIN actas ac ON a.id_acta = ac.id
                       JOIN proyectos p ON ac.id_proyecto = p.id
                       WHERE 1=1";
                
                $params = [];
                
                if (!empty($filtros['estado'])) {
                    $sql .= " AND a.estado = ?";
                    $params[] = $filtros['estado'];
                }
                
                if (!empty($filtros['id_proyecto'])) {
                    $sql .= " AND ac.id_proyecto = ?";
                    $params[] = $filtros['id_proyecto'];
                }
                
                if (!empty($filtros['id_responsable'])) {
                    $sql .= " AND a.id_responsable = ?";
                    $params[] = $filtros['id_responsable'];
                }
                
                $sql .= " ORDER BY a.fecha_limite ASC";
                
                $stmt = $this->db->prepare($sql);
                $stmt->execute($params);
                $datos = $stmt->fetchAll(PDO::FETCH_ASSOC);
                break;
                
            default:
                return "Tipo de exportación no válido";
        }
        
        // Generar CSV
        $output = fopen('php://temp', 'r+');
        fputcsv($output, $cabeceras);
        
        foreach ($datos as $fila) {
            // Filtrar solo las columnas que necesitamos
            $filaFiltrada = [];
            foreach ($cabeceras as $cabecera) {
                $clave = strtolower(str_replace(' ', '_', str_replace('(%)', '', $cabecera)));
                $filaFiltrada[] = isset($fila[$clave]) ? $fila[$clave] : '';
            }
            fputcsv($output, $filaFiltrada);
        }
        
        rewind($output);
        $csv = stream_get_contents($output);
        fclose($output);
        
        return $csv;
    }
    
    /**
     * Obtener notificaciones pendientes para un usuario
     * @param int $idUsuario ID del usuario
     * @param bool $soloNoLeidas Obtener solo notificaciones no leídas
     * @return array Notificaciones pendientes
     */
    public function obtenerNotificacionesUsuario($idUsuario, $soloNoLeidas = true) {
        $sql = "SELECT n.id, n.tipo, n.mensaje, n.fecha, n.leido, n.url,
                       n.id_referencia, n.entidad_referencia
                FROM notificaciones n
                WHERE n.id_usuario = ?";
        
        if ($soloNoLeidas) {
            $sql .= " AND n.leido = 0";
        }
        
        $sql .= " ORDER BY n.fecha DESC";
        
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$idUsuario]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            $this->log("Error al obtener notificaciones: " . $e->getMessage(), 'error', ['id_usuario' => $idUsuario]);
            return [];
        }
    }
    
    /**
     * Genera una consulta personalizada dinámica basada en criterios
     * @param string $tabla Tabla principal para la consulta
     * @param array $campos Campos a seleccionar
     * @param array $joins Joins a realizar (formato: ['tabla' => ['campo_origen', 'campo_destino']])
     * @param array $filtros Filtros a aplicar
     * @param string $ordenCampo Campo para ordenar
     * @param string $ordenDir Dirección de ordenamiento (ASC/DESC)
     * @param int $limite Límite de registros
     * @param int $offset Offset para paginación
     * @return array Resultados de la consulta
     */
    public function consultaDinamica($tabla, $campos = ['*'], $joins = [], $filtros = [], $ordenCampo = null, $ordenDir = 'ASC', $limite = null, $offset = null) {
        try {
            // Construir SELECT
            $select = implode(', ', $campos);
            $sql = "SELECT $select FROM $tabla";
            
            // Añadir JOINs
            foreach ($joins as $joinTabla => $joinCampos) {
                $sql .= " LEFT JOIN $joinTabla ON $tabla.{$joinCampos[0]} = $joinTabla.{$joinCampos[1]}";
            }
            
            // Añadir filtros
            $params = [];
            $where = [];
            
            foreach ($filtros as $campo => $valor) {
                if (is_array($valor) && isset($valor['operador'])) {
                    // Filtro con operador personalizado: ['campo' => ['operador' => '>=', 'valor' => 100]]
                    $where[] = "$campo {$valor['operador']} ?";
                    $params[] = $valor['valor'];
                } else if (is_array($valor) && count($valor) > 0) {
                    // Filtro IN: ['campo' => [1, 2, 3]]
                    $placeholders = implode(', ', array_fill(0, count($valor), '?'));
                    $where[] = "$campo IN ($placeholders)";
                    $params = array_merge($params, $valor);
                } else if ($valor === null) {
                    // Filtro IS NULL
                    $where[] = "$campo IS NULL";
                } else {
                    // Filtro estándar de igualdad
                    $where[] = "$campo = ?";
                    $params[] = $valor;
                }
            }
            
            if (!empty($where)) {
                $sql .= " WHERE " . implode(' AND ', $where);
            }
            
            // Ordenamiento
            if ($ordenCampo) {
                $sql .= " ORDER BY $ordenCampo $ordenDir";
            }
            
            // Límite y offset
            if ($limite !== null) {
                $sql .= " LIMIT ?";
                $params[] = $limite;
                
                if ($offset !== null) {
                    $sql .= " OFFSET ?";
                    $params[] = $offset;
                }
            }
            
            // Ejecutar consulta
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            $this->log("Error en consulta dinámica: " . $e->getMessage(), 'error', [
                'tabla' => $tabla,
                'filtros' => json_encode($filtros)
            ]);
            return [];
        }
    }
    
    /**
     * Obtener calendario de eventos de proyecto
     * @param int $idProyecto ID del proyecto
     * @param string $fechaInicio Fecha de inicio (formato Y-m-d)
     * @param string $fechaFin Fecha de fin (formato Y-m-d)
     * @return array Eventos del calendario
     */
    public function obtenerCalendarioProyecto($idProyecto, $fechaInicio = null, $fechaFin = null) {
        try {
            $eventos = [];
            $params = [$idProyecto];
            
            // Condición de fechas
            $condicionFecha = "";
            if ($fechaInicio && $fechaFin) {
                $condicionFecha = " AND (
                    (ac.fecha >= ? AND ac.fecha <= ?) OR
                    (a.fecha_limite >= ? AND a.fecha_limite <= ?)
                )";
                $params[] = $fechaInicio;
                $params[] = $fechaFin;
                $params[] = $fechaInicio;
                $params[] = $fechaFin;
            }
            
            // Obtener eventos de actas
            $sqlActas = "SELECT 
                            ac.id, 
                            ac.titulo as titulo, 
                            ac.fecha as fecha, 
                            ac.tipo, 
                            'acta' as tipo_evento,
                            ac.estado,
                            p.nombre as proyecto
                        FROM actas ac
                        JOIN proyectos p ON ac.id_proyecto = p.id
                        WHERE ac.id_proyecto = ? $condicionFecha
                        ORDER BY ac.fecha";
            
            $stmtActas = $this->db->prepare($sqlActas);
            $stmtActas->execute($params);
            $eventosActas = $stmtActas->fetchAll(PDO::FETCH_ASSOC);
            
            foreach ($eventosActas as $evento) {
                $eventos[] = [
                    'id' => 'acta_' . $evento['id'],
                    'title' => $evento['titulo'] . ' (' . $evento['tipo'] . ')',
                    'start' => $evento['fecha'],
                    'end' => $evento['fecha'],
                    'allDay' => true,
                    'backgroundColor' => $this->obtenerColorEventoCalendario('acta', $evento['estado']),
                    'tipo' => 'acta',
                    'estado' => $evento['estado'],
                    'proyecto' => $evento['proyecto'],
                    'detalle_id' => $evento['id']
                ];
            }
            
            // Obtener eventos de acuerdos
            $paramsAcuerdos = [$idProyecto];
            $condicionFechaAcuerdos = "";
            
            if ($fechaInicio && $fechaFin) {
                $condicionFechaAcuerdos = " AND a.fecha_limite >= ? AND a.fecha_limite <= ?";
                $paramsAcuerdos[] = $fechaInicio;
                $paramsAcuerdos[] = $fechaFin;
            }
            
            $sqlAcuerdos = "SELECT 
                                a.id, 
                                a.descripcion as titulo, 
                                a.fecha_limite as fecha, 
                                a.estado, 
                                a.prioridad,
                                'acuerdo' as tipo_evento,
                                u.nombre as responsable,
                                ac.titulo as acta,
                                p.nombre as proyecto
                            FROM acuerdos a
                            JOIN actas ac ON a.id_acta = ac.id
                            JOIN proyectos p ON ac.id_proyecto = p.id
                            JOIN usuarios u ON a.id_responsable = u.id
                            WHERE ac.id_proyecto = ? $condicionFechaAcuerdos
                            ORDER BY a.fecha_limite";
            
            $stmtAcuerdos = $this->db->prepare($sqlAcuerdos);
            $stmtAcuerdos->execute($paramsAcuerdos);
            $eventosAcuerdos = $stmtAcuerdos->fetchAll(PDO::FETCH_ASSOC);
            
            foreach ($eventosAcuerdos as $evento) {
                $eventos[] = [
                    'id' => 'acuerdo_' . $evento['id'],
                    'title' => $evento['titulo'] . ' (' . $evento['responsable'] . ')',
                    'start' => $evento['fecha'],
                    'end' => $evento['fecha'],
                    'allDay' => true,
                    'backgroundColor' => $this->obtenerColorEventoCalendario('acuerdo', $evento['estado'], $evento['prioridad']),
                    'tipo' => 'acuerdo',
                    'estado' => $evento['estado'],
                    'prioridad' => $evento['prioridad'],
                    'responsable' => $evento['responsable'],
                    'acta' => $evento['acta'],
                    'proyecto' => $evento['proyecto'],
                    'detalle_id' => $evento['id']
                ];
            }
            
            // Agregar fechas del proyecto
            $sqlProyecto = "SELECT id, nombre, fecha_inicio, fecha_fin FROM proyectos WHERE id = ?";
            $stmtProyecto = $this->db->prepare($sqlProyecto);
            $stmtProyecto->execute([$idProyecto]);
            $proyecto = $stmtProyecto->fetch(PDO::FETCH_ASSOC);
            
            if ($proyecto) {
                // Evento de inicio de proyecto
                $eventos[] = [
                    'id' => 'proyecto_inicio_' . $proyecto['id'],
                    'title' => 'Inicio: ' . $proyecto['nombre'],
                    'start' => $proyecto['fecha_inicio'],
                    'end' => $proyecto['fecha_inicio'],
                    'allDay' => true,
                    'backgroundColor' => '#00C853', // Verde
                    'tipo' => 'proyecto',
                    'subtipo' => 'inicio',
                    'detalle_id' => $proyecto['id']
                ];
                
                // Evento de fin de proyecto
                $eventos[] = [
                    'id' => 'proyecto_fin_' . $proyecto['id'],
                    'title' => 'Fin: ' . $proyecto['nombre'],
                    'start' => $proyecto['fecha_fin'],
                    'end' => $proyecto['fecha_fin'],
                    'allDay' => true,
                    'backgroundColor' => '#FF5722', // Naranja
                    'tipo' => 'proyecto',
                    'subtipo' => 'fin',
                    'detalle_id' => $proyecto['id']
                ];
            }
            
            return $eventos;
        } catch (PDOException $e) {
            $this->log("Error al obtener calendario: " . $e->getMessage(), 'error', ['id_proyecto' => $idProyecto]);
            return [];
        }
    }
    
    /**
     * Obtiene color para evento de calendario según su tipo y estado
     * @param string $tipo Tipo de evento
     * @param string $estado Estado actual
     * @param string $prioridad Prioridad (opcional)
     * @return string Código de color en hexadecimal
     */
    private function obtenerColorEventoCalendario($tipo, $estado, $prioridad = null) {
        switch ($tipo) {
            case 'acta':
                switch ($estado) {
                    case 'pendiente': return '#FF9800'; // Naranja
                    case 'aprobada': return '#4CAF50'; // Verde
                    case 'rechazada': return '#F44336'; // Rojo
                    default: return '#2196F3'; // Azul
                }
                break;
                
            case 'acuerdo':
                if ($estado == 'completado') {
                    return '#4CAF50'; // Verde
                } else if ($estado == 'vencido') {
                    return '#F44336'; // Rojo
                } else {
                    // Pendiente, color según prioridad
                    switch ($prioridad) {
                        case 'alta': return '#FF5722'; // Naranja oscuro
                        case 'media': return '#FF9800'; // Naranja
                        case 'baja': return '#FFC107'; // Amarillo
                        default: return '#2196F3'; // Azul
                    }
                }
                break;
                
            default:
                return '#9E9E9E'; // Gris
        }
    }
    
    /**
     * Genera un informe comparativo entre proyectos
     * @param array $idsProyectos Array con IDs de proyectos a comparar
     * @return array Datos comparativos
     */
    public function compararProyectos($idsProyectos) {
        if (empty($idsProyectos) || !is_array($idsProyectos)) {
            return ['error' => 'Se requiere al menos un proyecto para comparar'];
        }
        
        try {
            $placeholders = implode(',', array_fill(0, count($idsProyectos), '?'));
            
            // Obtener datos básicos de los proyectos
            $sqlProyectos = "SELECT id, nombre, fecha_inicio, fecha_fin, 
                                    estado, presupuesto, porcentaje_avance
                             FROM proyectos 
                             WHERE id IN ($placeholders)";
                             
            $stmtProyectos = $this->db->prepare($sqlProyectos);
            $stmtProyectos->execute($idsProyectos);
            $proyectos = $stmtProyectos->fetchAll(PDO::FETCH_ASSOC);
            
            $comparativa = [
                'proyectos' => $proyectos,
                'detalle_proyectos' => []
            ];
            
            // Para cada proyecto, obtener estadísticas detalladas
            foreach ($proyectos as $proyecto) {
                $idProyecto = $proyecto['id'];
                
                // Contar actas por tipo
                $sqlActas = "SELECT tipo, COUNT(*) as total
                             FROM actas
                             WHERE id_proyecto = ?
                             GROUP BY tipo";
                             
                $stmtActas = $this->db->prepare($sqlActasas);
                $stmtActas->execute([$idProyecto]);
                $actasPorTipo = $stmtActas->fetchAll(PDO::FETCH_ASSOC);
                
                // Contar acuerdos por estado
                $sqlAcuerdos = "SELECT a.estado, COUNT(*) as total
                               FROM acuerdos a
                               JOIN actas ac ON a.id_acta = ac.id
                               WHERE ac.id_proyecto = ?
                               GROUP BY a.estado";
                               
                $stmtAcuerdos = $this->db->prepare($sqlAcuerdos);
                $stmtAcuerdos->execute([$idProyecto]);
                $acuerdosPorEstado = $stmtAcuerdos->fetchAll(PDO::FETCH_ASSOC);
                
                // Calcular duración del proyecto en días
                $fechaInicio = new DateTime($proyecto['fecha_inicio']);
                $fechaFin = new DateTime($proyecto['fecha_fin']);
                $duracion = $fechaInicio->diff($fechaFin)->days;
                
                // Calcular indicadores
                $sqlIndicadores = "SELECT 
                                      COUNT(DISTINCT a.id) as total_actas,
                                      COUNT(DISTINCT ac.id) as total_acuerdos,
                                      AVG(DATEDIFF(a.fecha, p.fecha_inicio)) as promedio_dias_primera_acta,
                                      SUM(CASE WHEN ac.estado = 'completado' THEN 1 ELSE 0 END) / COUNT(ac.id) * 100 as porcentaje_acuerdos_completados
                                  FROM proyectos p
                                  LEFT JOIN actas a ON p.id = a.id_proyecto
                                  LEFT JOIN acuerdos ac ON a.id = ac.id_acta
                                  WHERE p.id = ?
                                  GROUP BY p.id";
                                  
                $stmtIndicadores = $this->db->prepare($sqlIndicadores);
                $stmtIndicadores->execute([$idProyecto]);
                $indicadores = $stmtIndicadores->fetch(PDO::FETCH_ASSOC);
                
                // Consolidar estadísticas por proyecto
                $comparativa['detalle_proyectos'][$idProyecto] = [
                    'actas_por_tipo' => $actasPorTipo,
                    'acuerdos_por_estado' => $acuerdosPorEstado,
                    'duracion_dias' => $duracion,
                    'indicadores' => $indicadores
                ];
            }
            
            return $comparativa;
        } catch (PDOException $e) {
            $this->log("Error al comparar proyectos: " . $e->getMessage(), 'error', ['ids' => implode(',', $idsProyectos)]);
            return ['error' => 'Error al comparar proyectos'];
        }
    }
    
    /**
     * Obtiene recomendaciones basadas en datos históricos
     * @param int $idProyecto ID del proyecto (opcional)
     * @param int $idUsuario ID del usuario (opcional)
     * @return array Recomendaciones
     */
    public function obtenerRecomendaciones($idProyecto = null, $idUsuario = null) {
        $recomendaciones = [];
        
        try {
            // Recomendaciones generales basadas en proyectos similares
            if ($idProyecto) {
                // Obtener tipo de proyecto actual
                $sqlTipoProyecto = "SELECT tipo FROM proyectos WHERE id = ?";
                $stmtTipoProyecto = $this->db->prepare($sqlTipoProyecto);
                $stmtTipoProyecto->execute([$idProyecto]);
                $tipoProyecto = $stmtTipoProyecto->fetch(PDO::FETCH_COLUMN);
                
                if ($tipoProyecto) {
                    // Encontrar tipos de actas más comunes en proyectos similares
                    $sqlTiposActa = "SELECT a.tipo, COUNT(*) as total
                                    FROM actas a
                                    JOIN proyectos p ON a.id_proyecto = p.id
                                    WHERE p.tipo = ? AND p.id != ?
                                    GROUP BY a.tipo
                                    ORDER BY total DESC
                                    LIMIT 3";
                                    
                    $stmtTiposActa = $this->db->prepare($sqlTiposActa);
                    $stmtTiposActa->execute([$tipoProyecto, $idProyecto]);
                    $tiposActaRecomendados = $stmtTiposActa->fetchAll(PDO::FETCH_ASSOC);
                    
                    if (!empty($tiposActaRecomendados)) {
                        $recomendaciones['tipos_acta'] = [
                            'mensaje' => 'Basado en proyectos similares, estos tipos de acta son comunes:',
                            'datos' => $tiposActaRecomendados
                        ];
                    }
                    
                    // Identificar acuerdos pendientes con prioridad alta
                    $sqlAcuerdosPrioritarios = "SELECT a.descripcion, a.fecha_limite, u.nombre as responsable
                                              FROM acuerdos a
                                              JOIN actas ac ON a.id_acta = ac.id
                                              JOIN usuarios u ON a.id_responsable = u.id
                                              WHERE ac.id_proyecto = ? AND a.estado = 'pendiente' 
                                              AND a.prioridad = 'alta' AND a.fecha_limite <= DATE_ADD(CURDATE(), INTERVAL 7 DAY)
                                              ORDER BY a.fecha_limite ASC";
                                              
                    $stmtAcuerdosPrioritarios = $this->db->prepare($sqlAcuerdosPrioritarios);
                    $stmtAcuerdosPrioritarios->execute([$idProyecto]);
                    $acuerdosPrioritarios = $stmtAcuerdosPrioritarios->fetchAll(PDO::FETCH_ASSOC);
                    
                    if (!empty($acuerdosPrioritarios)) {
                        $recomendaciones['acuerdos_prioritarios'] = [
                            'mensaje' => 'Acuerdos prioritarios que requieren atención pronto:',
                            'datos' => $acuerdosPrioritarios
                        ];
                    }
                }
            }
            
            // Recomendaciones para usuario específico
            if ($idUsuario) {
                // Acuerdos vencidos del usuario
                $sqlAcuerdosVencidos = "SELECT a.descripcion, a.fecha_limite, ac.titulo as acta, p.nombre as proyecto
                                       FROM acuerdos a
                                       JOIN actas ac ON a.id_acta = ac.id
                                       JOIN proyectos p ON ac.id_proyecto = p.id
                                       WHERE a.id_responsable = ? AND a.estado = 'pendiente' 
                                       AND a.fecha_limite < CURDATE()
                                       ORDER BY a.fecha_limite ASC";
                                       
                $stmtAcuerdosVencidos = $this->db->prepare($sqlAcuerdosVencidos);
                $stmtAcuerdosVencidos->execute([$idUsuario]);
                $acuerdosVencidos = $stmtAcuerdosVencidos->fetchAll(PDO::FETCH_ASSOC);
                
                if (!empty($acuerdosVencidos)) {
                    $recomendaciones['acuerdos_vencidos'] = [
                        'mensaje' => 'Tienes los siguientes acuerdos vencidos:',
                        'datos' => $acuerdosVencidos
                    ];
                }
                
                // Próximas actas donde el usuario participa
                $sqlProximasActas = "SELECT a.titulo, a.fecha, a.tipo, p.nombre as proyecto
                                    FROM actas a
                                    JOIN participantes_acta pa ON a.id = pa.id_acta
                                    JOIN proyectos p ON a.id_proyecto = p.id
                                    WHERE pa.id_usuario = ? AND a.fecha >= CURDATE()
                                    ORDER BY a.fecha ASC
                                    LIMIT 5";
                                    
                $stmtProximasActas = $this->db->prepare($sqlProximasActas);
                $stmtProximasActas->execute([$idUsuario]);
                $proximasActas = $stmtProximasActas->fetchAll(PDO::FETCH_ASSOC);
                
                if (!empty($proximasActas)) {
                    $recomendaciones['proximas_actas'] = [
                        'mensaje' => 'Próximas reuniones en tu agenda:',
                        'datos' => $proximasActas
                    ];
                }
            }
            
            // Recomendaciones generales del sistema
            // Proyectos con baja actividad reciente
            $sqlProyectosBajaActividad = "SELECT p.id, p.nombre, p.porcentaje_avance, 
                                            MAX(a.fecha) as ultima_acta, 
                                            DATEDIFF(CURDATE(), MAX(a.fecha)) as dias_sin_actividad
                                         FROM proyectos p
                                         LEFT JOIN actas a ON p.id = a.id_proyecto
                                         WHERE p.estado = 'activo'
                                         GROUP BY p.id
                                         HAVING dias_sin_actividad > 30
                                         ORDER BY dias_sin_actividad DESC
                                         LIMIT 5";
                                         
            $stmtProyectosBajaActividad = $this->db->prepare($sqlProyectosBajaActividad);
            $stmtProyectosBajaActividad->execute();
            $proyectosBajaActividad = $stmtProyectosBajaActividad->fetchAll(PDO::FETCH_ASSOC);
            
            if (!empty($proyectosBajaActividad)) {
                $recomendaciones['proyectos_baja_actividad'] = [
                    'mensaje' => 'Proyectos activos con baja actividad reciente:',
                    'datos' => $proyectosBajaActividad
                ];
            }
            
            return $recomendaciones;
        } catch (PDOException $e) {
            $this->log("Error al obtener recomendaciones: " . $e->getMessage(), 'error');
            return ['error' => 'Error al generar recomendaciones'];
        }
    }
    
    /**
     * Registra una nueva actividad en el log
     * @param string $accion Descripción de la acción
     * @param string $tabla Tabla afectada
     * @param int $idRegistro ID del registro afectado
     * @param int $idUsuario ID del usuario que realizó la acción
     * @param array $detalles Detalles adicionales (opcional)
     * @return bool Éxito de la operación
     */
    public function registrarActividad($accion, $tabla, $idRegistro, $idUsuario, $detalles = []) {
        try {
            $sql = "INSERT INTO actividad_log (accion, tabla_afectada, registro_afectado, id_usuario, ip, detalles, fecha)
                    VALUES (?, ?, ?, ?, ?, ?, NOW())";
                    
            $ip = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : 'desconocida';
            $detallesJson = !empty($detalles) ? json_encode($detalles) : null;
            
            $stmt = $this->db->prepare($sql);
            $resultado = $stmt->execute([$accion, $tabla, $idRegistro, $idUsuario, $ip, $detallesJson]);
            
            return $resultado;
        } catch (PDOException $e) {
            $this->log("Error al registrar actividad: " . $e->getMessage(), 'error', [
                'accion' => $accion,
                'tabla' => $tabla,
                'id_registro' => $idRegistro
            ]);
            return false;
        }
    }
    
    /**
     * Genera una notificación para un usuario
     * @param int $idUsuario ID del usuario destinatario
     * @param string $tipo Tipo de notificación
     * @param string $mensaje Mensaje de la notificación
     * @param string $entidadReferencia Entidad relacionada (proyecto, acta, acuerdo)
     * @param int $idReferencia ID de la entidad relacionada
     * @param string $url URL opcional para redirección
     * @return bool Éxito de la operación
     */
    public function generarNotificacion($idUsuario, $tipo, $mensaje, $entidadReferencia, $idReferencia, $url = null) {
        try {
            $sql = "INSERT INTO notificaciones (id_usuario, tipo, mensaje, entidad_referencia, id_referencia, url, fecha, leido)
                    VALUES (?, ?, ?, ?, ?, ?, NOW(), 0)";
                    
            $stmt = $this->db->prepare($sql);
            $resultado = $stmt->execute([$idUsuario, $tipo, $mensaje, $entidadReferencia, $idReferencia, $url]);
            
            return $resultado;
        } catch (PDOException $e) {
            $this->log("Error al generar notificación: " . $e->getMessage(), 'error', [
                'id_usuario' => $idUsuario,
                'tipo' => $tipo,
                'mensaje' => $mensaje
            ]);
            return false;
        }
    }
    
    /**
     * Obtiene el historial de cambios de un elemento
     * @param string $tabla Tabla del elemento
     * @param int $idElemento ID del elemento
     * @return array Historial de cambios
     */
    public function obtenerHistorialCambios($tabla, $idElemento) {
        try {
            $sql = "SELECT h.id, h.fecha, h.id_usuario, h.tipo_cambio, h.campo, 
                           h.valor_anterior, h.valor_nuevo, u.nombre as usuario
                    FROM historial_cambios h
                    LEFT JOIN usuarios u ON h.id_usuario = u.id
                    WHERE h.tabla = ? AND h.id_registro = ?
                    ORDER BY h.fecha DESC";
                    
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$tabla, $idElemento]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            $this->log("Error al obtener historial: " . $e->getMessage(), 'error', [
                'tabla' => $tabla,
                'id_elemento' => $idElemento
            ]);
            return [];
        }
    }
    
    /**
     * Registra un cambio en el historial
     * @param string $tabla Tabla del elemento
     * @param int $idRegistro ID del elemento
     * @param int $idUsuario ID del usuario que realizó el cambio
     * @param string $tipoCambio Tipo de cambio (crear, actualizar, eliminar)
     * @param string $campo Campo modificado
     * @param mixed $valorAnterior Valor anterior
     * @param mixed $valorNuevo Valor nuevo
     * @return bool Éxito de la operación
     */
    public function registrarCambio($tabla, $idRegistro, $idUsuario, $tipoCambio, $campo = null, $valorAnterior = null, $valorNuevo = null) {
        try {
            $sql = "INSERT INTO historial_cambios (tabla, id_registro, id_usuario, tipo_cambio, campo, valor_anterior, valor_nuevo, fecha)
                    VALUES (?, ?, ?, ?, ?, ?, ?, NOW())";
                    
            $stmt = $this->db->prepare($sql);
            $resultado = $stmt->execute([
                $tabla, 
                $idRegistro, 
                $idUsuario, 
                $tipoCambio, 
                $campo, 
                $valorAnterior !== null ? (is_array($valorAnterior) ? json_encode($valorAnterior) : $valorAnterior) : null,
                $valorNuevo !== null ? (is_array($valorNuevo) ? json_encode($valorNuevo) : $valorNuevo) : null
            ]);
            
            return $resultado;
        } catch (PDOException $e) {
            $this->log("Error al registrar cambio: " . $e->getMessage(), 'error', [
                'tabla' => $tabla,
                'id_registro' => $idRegistro,
                'tipo_cambio' => $tipoCambio
            ]);
            return false;
        }
    }
    
    /**
     * Genera reportes en diferentes formatos
     * @param string $tipo Tipo de reporte
     * @param array $datos Datos para el reporte
     * @param string $formato Formato de salida (html, pdf, excel)
     * @param array $opciones Opciones adicionales
     * @return mixed Contenido del reporte o ruta del archivo generado
     */
    public function generarReporte($tipo, $datos, $formato = 'html', $opciones = []) {
        try {
            $contenido = '';
            
            // Generar contenido según el tipo de reporte
            switch ($tipo) {
                case 'proyecto':
                    $contenido = $this->generarReporteProyecto($datos, $formato);
                    break;
                    
                case 'actas':
                    $contenido = $this->generarReporteActas($datos, $formato);
                    break;
                    
                case 'acuerdos':
                    $contenido = $this->generarReporteAcuerdos($datos, $formato);
                    break;
                    
                case 'estadisticas':
                    $contenido = $this->generarReporteEstadisticas($datos, $formato);
                    break;
                    
                default:
                    return ['error' => 'Tipo de reporte no válido'];
            }
            
            // Si es HTML, devolver directamente
            if ($formato === 'html') {
                return $contenido;
            }
            
            // Generar archivo en el formato solicitado
            $nombreArchivo = 'reporte_' . $tipo . '_' . date('Ymd_His');
            $rutaTemporal = sys_get_temp_dir() . '/' . $nombreArchivo;
            
            switch ($formato) {
                case 'pdf':
                    // Aquí iría la lógica para generar PDF (requiere librería externa)
                    // Por ejemplo, usando mPDF, TCPDF, o similar
                    $rutaTemporal .= '.pdf';
                    break;
                    
                case 'excel':
                    // Aquí iría la lógica para generar Excel (requiere librería externa)
                    // Por ejemplo, usando PhpSpreadsheet
                    $rutaTemporal .= '.xlsx';
                    break;
                    
                case 'csv':
                    $rutaTemporal .= '.csv';
                    file_put_contents($rutaTemporal, $contenido);
                    break;
            }
            
            return [
                'ruta' => $rutaTemporal,
                'nombre' => basename($rutaTemporal)
            ];
            
        } catch (Exception $e) {
            $this->log("Error al generar reporte: " . $e->getMessage(), 'error', [
                'tipo' => $tipo,
                'formato' => $formato
            ]);
            return ['error' => 'Error al generar reporte'];
        }
    }
    
    /**
     * Genera el contenido HTML o CSV para el reporte de proyecto
     * @param array $datos Datos del proyecto
     * @param string $formato Formato de salida
     * @return string Contenido del reporte
     */
    private function generarReporteProyecto($datos, $formato) {
        // Implementación básica para HTML y CSV
        // En un caso real, se usaría un sistema de plantillas más robusto
        
        if ($formato === 'csv') {
            $output = fopen('php://temp', 'r+');
            fputcsv($output, ['Campo', 'Valor']);
            
            foreach ($datos as $campo => $valor) {
                if (!is_array($valor)) {
                    fputcsv($output, [$campo, $valor]);
                }
            }
            
            rewind($output);
            $csv = stream_get_contents($output);
            fclose($output);
            
            return $csv;
        }
        
        // Por defecto, generar HTML
        $html = '<div class="reporte-proyecto">';
        $html .= '<h1>Reporte de Proyecto: ' . htmlspecialchars($datos['nombre']) . '</h1>';
        $html .= '<div class="datos-basicos">';
        $html .= '<h2>Datos Básicos</h2>';
        $html .= '<table class="tabla-datos">';
        
        $camposBasicos = ['id', 'nombre', 'fecha_inicio', 'fecha_fin', 'estado', 'responsable', 'departamento', 'presupuesto', 'porcentaje_avance'];
        
        foreach ($camposBasicos as $campo) {
            if (isset($datos[$campo])) {
                $html .= '<tr>';
                $html .= '<th>' . ucfirst(str_replace('_', ' ', $campo)) . '</th>';
                $html .= '<td>' . htmlspecialchars($datos[$campo]) . '</td>';
                $html .= '</tr>';
            }
        }
        
        $html .= '</table>';
        $html .= '</div>';
        
        // Si hay datos de actas, mostrarlos
        if (!empty($datos['actas'])) {
            $html .= '<div class="seccion-actas">';
            $html .= '<h2>Actas del Proyecto</h2>';
            $html .= '<table class="tabla-actas">';
            $html .= '<thead><tr><th>Título</th><th>Fecha</th><th>Tipo</th><th>Estado</th></tr></thead>';
            $html .= '<tbody>';
            
            foreach ($datos['actas'] as $acta) {
                $html .= '<tr>';
                $html .= '<td>' . htmlspecialchars($acta['titulo']) . '</td>';
                $html .= '<td>' . htmlspecialchars($acta['fecha']) . '</td>';
                $html .= '<td>' . htmlspecialchars($acta['tipo']) . '</td>';
                $html .= '<td>' . htmlspecialchars($acta['estado']) . '</td>';
                $html .= '</tr>';
            }
            
            $html .= '</tbody>';
            $html .= '</table>';
            $html .= '</div>';
        }
        
        // Si hay datos de acuerdos, mostrarlos
        if (!empty($datos['acuerdos'])) {
            $html .= '<div class="seccion-acuerdos">';
            $html .= '<h2>Acuerdos del Proyecto</h2>';
            $html .= '<table class="tabla-acuerdos">';
            $html .= '<thead><tr><th>Descripción</th><th>Fecha Límite</th><th>Estado</th><th>Responsable</th></tr></thead>';
            $html .= '<tbody>';
            
            foreach ($datos['acuerdos'] as $acuerdo) {
                $html .= '<tr>';
                $html .= '<td>' . htmlspecialchars($acuerdo['descripcion']) . '</td>';
                $html .= '<td>' . htmlspecialchars($acuerdo['fecha_limite']) . '</td>';
                $html .= '<td>' . htmlspecialchars($acuerdo['estado']) . '</td>';
                $html .= '<td>' . htmlspecialchars($acuerdo['responsable']) . '</td>';
                $html .= '</tr>';
            }
            
            $html .= '</tbody>';
            $html .= '</table>';
            $html .= '</div>';
        }
        
        $html .= '<div class="fecha-generacion">';
        $html .= '<p>Reporte generado el: ' . date('Y-m-d H:i:s') . '</p>';
        $html .= '</div>';
        
        $html .= '</div>';
        
        return $html;
    }
    
    /**
     * Genera el contenido HTML o CSV para el reporte de actas
     * @param array $datos Datos de actas
     * @param string $formato Formato de salida
     * @return string Contenido del reporte
     */
    private function generarReporteActas($datos, $formato) {
        // Implementación similar a la de proyectos pero adaptada para actas
        // Se omite el detalle por brevedad
        return "Contenido del reporte de actas";
    }
    
    /**
     * Genera el contenido HTML o CSV para el reporte de acuerdos
     * @param array $datos Datos de acuerdos
     * @param string $formato Formato de salida
     * @return string Contenido del reporte
     */
    private function generarReporteAcuerdos($datos, $formato) {
        // Implementación similar a la de proyectos pero adaptada para acuerdos
        // Se omite el detalle por brevedad
        return "Contenido del reporte de acuerdos";
    }
    
    /**
     * Genera el contenido HTML o CSV para el reporte de estadísticas
     * @param array $datos Datos estadísticos
     * @param string $formato Formato de salida
     * @return string Contenido del reporte
     */
    private function generarReporteEstadisticas($datos, $formato) {
        // Implementación similar a la de proyectos pero adaptada para estadísticas
        // Se omite el detalle por brevedad
        return "Contenido del reporte de estadísticas";
    }
    
    /**
     * Método de utilidad para ejecutar consultas SQL personalizadas de forma segura
     * @param string $sql Consulta SQL
     * @param array $params Parámetros para la consulta preparada
     * @param bool $fetchAll Obtener todos los resultados o solo el primero
     * @return mixed Resultados de la consulta
     */
    public function ejecutarConsulta($sql, $params = [], $fetchAll = true) {
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            
            if ($fetchAll) {
                return $stmt->fetchAll(PDO::FETCH_ASSOC);
            } else {
                return $stmt->fetch(PDO::FETCH_ASSOC);
            }
        } catch (PDOException $e) {
            $this->log("Error en consulta personalizada: " . $e->getMessage(), 'error', [
                'sql' => $sql,
                'params' => json_encode($params)
            ]);
            return false;
        }
    }
    
    /**
     * Cierra la conexión a la base de datos
     */
    public function cerrarConexion() {
        $this->db = null;
    }
}
?>