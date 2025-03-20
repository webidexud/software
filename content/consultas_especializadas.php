<?php
/**
 * Centro de Consultas Especializadas
 * 
 * Este módulo proporciona un centro unificado para realizar consultas avanzadas
 * sobre proyectos, entidades y otros datos del sistema con filtros complejos,
 * visualizaciones interactivas y exportación de resultados.
 */

// Incluir los modelos necesarios
if (file_exists('models/proyecto_model.php')) {
    include_once 'models/proyecto_model.php';
}
if (file_exists('models/entidad_model.php')) {
    include_once 'models/entidad_model.php';
}

// Obtener datos para los filtros
$anios = function_exists('obtenerListaAnios') ? obtenerListaAnios() : [date('Y') - 2, date('Y') - 1, date('Y')];
$entidades = function_exists('obtenerListaEntidades') ? obtenerListaEntidades() : [];
$situaciones = function_exists('obtenerListaSituaciones') ? obtenerListaSituaciones() : [];

// Tipos de consultas disponibles
$tipos_consulta = [
    'proyectos_activos' => 'Proyectos Activos',
    'proyectos_por_anio' => 'Proyectos por Año',
    'proyectos_por_entidad' => 'Proyectos por Entidad',
    'proyectos_por_situacion' => 'Proyectos por Situación',
    'valor_por_anio' => 'Valor por Año',
    'rendimiento_por_entidad' => 'Rendimiento por Entidad',
    'proyectos_vencimiento' => 'Proyectos por Vencer',
    'distribucion_geografica' => 'Distribución Geográfica',
    'proyectos_supervisor' => 'Proyectos por Supervisor',
    'analisis_financiero' => 'Análisis Financiero'
];

// Procesar la solicitud de consulta
$resultados = [];
$tipo_consulta_actual = '';
$graficos_data = [];
$filtros_aplicados = [];

// Función para obtener datos de ejemplo (se reemplazará con consultas reales)
function obtenerDatosConsulta($tipo, $filtros = []) {
    $resultados = [];
    
    switch ($tipo) {
        case 'proyectos_activos':
            // Intentar usar la función real
            if (function_exists('obtenerTodosProyectos')) {
                $resultados['datos'] = obtenerTodosProyectos();
                $resultados['columnas'] = ['ID', 'Año', 'Número', 'Nombre', 'Entidad', 'Situación'];
            } else {
                // Datos de ejemplo
                $resultados['datos'] = [
                    ['id' => 1, 'anio' => 2024, 'numero' => 'PRY-2024-001', 'nombre' => 'Desarrollo Sistema de Gestión Documental', 'entidad' => 'Ministerio de Educación Nacional', 'situacion' => 'En ejecución'],
                    ['id' => 2, 'anio' => 2023, 'numero' => 'PRY-2023-052', 'nombre' => 'Implementación Plataforma de Capacitación Virtual', 'entidad' => 'Secretaría de Educación Distrital', 'situacion' => 'Finalizado'],
                    ['id' => 3, 'anio' => 2023, 'numero' => 'PRY-2023-018', 'nombre' => 'Estudio de Factibilidad Infraestructura Vial', 'entidad' => 'Instituto de Desarrollo Urbano', 'situacion' => 'Suscrito']
                ];
                $resultados['columnas'] = ['ID', 'Año', 'Número', 'Nombre', 'Entidad', 'Situación'];
            }
            break;
            
        case 'proyectos_por_anio':
            // Intentar usar la función real
            if (function_exists('obtenerCantidadProyectosPorAnio')) {
                $datos = obtenerCantidadProyectosPorAnio();
                $resultados['grafico'] = [
                    'tipo' => 'bar',
                    'etiquetas' => $datos['anios'], 
                    'series' => [
                        ['nombre' => 'Cantidad de Proyectos', 'datos' => $datos['cantidades']]
                    ]
                ];
                
                // Preparar datos para tabla
                $datosTabla = [];
                foreach ($datos['anios'] as $index => $anio) {
                    $datosTabla[] = [
                        'anio' => $anio,
                        'cantidad' => $datos['cantidades'][$index]
                    ];
                }
                $resultados['datos'] = $datosTabla;
                $resultados['columnas'] = ['Año', 'Cantidad de Proyectos'];
            } else {
                // Datos de ejemplo
                $anios = [2019, 2020, 2021, 2022, 2023, 2024];
                $cantidades = [12, 15, 20, 18, 22, 17];
                
                $resultados['grafico'] = [
                    'tipo' => 'bar',
                    'etiquetas' => $anios,
                    'series' => [
                        ['nombre' => 'Cantidad de Proyectos', 'datos' => $cantidades]
                    ]
                ];
                
                // Preparar datos para tabla
                $datosTabla = [];
                foreach ($anios as $index => $anio) {
                    $datosTabla[] = [
                        'anio' => $anio,
                        'cantidad' => $cantidades[$index]
                    ];
                }
                $resultados['datos'] = $datosTabla;
                $resultados['columnas'] = ['Año', 'Cantidad de Proyectos'];
            }
            break;
            
        case 'proyectos_por_entidad':
            // Intentar usar la función real
            if (function_exists('obtenerProyectosPorEntidad')) {
                $proyectosPorEntidad = obtenerProyectosPorEntidad();
                
                // Preparar datos para el gráfico
                $entidades = array_column($proyectosPorEntidad, 'entidad');
                $cantidades = array_column($proyectosPorEntidad, 'cantidad');
                
                $resultados['grafico'] = [
                    'tipo' => 'pie',
                    'etiquetas' => $entidades,
                    'series' => $cantidades
                ];
                
                $resultados['datos'] = $proyectosPorEntidad;
                $resultados['columnas'] = ['Entidad', 'Cantidad de Proyectos'];
            } else {
                // Datos de ejemplo
                $entidades = ['Ministerio de Educación', 'Alcaldía de Bogotá', 'Gobernación del Valle', 'Secretaría de Educación', 'Instituto de Desarrollo Urbano'];
                $cantidades = [15, 12, 8, 7, 5];
                
                $resultados['grafico'] = [
                    'tipo' => 'pie',
                    'etiquetas' => $entidades,
                    'series' => $cantidades
                ];
                
                $datosTabla = [];
                foreach ($entidades as $index => $entidad) {
                    $datosTabla[] = [
                        'entidad' => $entidad,
                        'cantidad' => $cantidades[$index]
                    ];
                }
                $resultados['datos'] = $datosTabla;
                $resultados['columnas'] = ['Entidad', 'Cantidad de Proyectos'];
            }
            break;
            
        case 'proyectos_por_situacion':
            // Datos de ejemplo
            $situaciones = ['En ejecución', 'Finalizado', 'Suscrito', 'Suspendido', 'En liquidación'];
            $cantidades = [25, 30, 15, 5, 10];
            
            $resultados['grafico'] = [
                'tipo' => 'donut',
                'etiquetas' => $situaciones,
                'series' => $cantidades
            ];
            
            $datosTabla = [];
            foreach ($situaciones as $index => $situacion) {
                $datosTabla[] = [
                    'situacion' => $situacion,
                    'cantidad' => $cantidades[$index]
                ];
            }
            $resultados['datos'] = $datosTabla;
            $resultados['columnas'] = ['Situación', 'Cantidad de Proyectos'];
            break;
            
        case 'valor_por_anio':
            // Intentar usar la función real
            if (function_exists('obtenerValoresPorAnio')) {
                $datos = obtenerValoresPorAnio();
                $resultados['grafico'] = [
                    'tipo' => 'line',
                    'etiquetas' => $datos['anios'],
                    'series' => [
                        ['nombre' => 'Valor Total (COP)', 'datos' => $datos['valores']]
                    ]
                ];
                
                // Preparar datos para tabla
                $datosTabla = [];
                foreach ($datos['anios'] as $index => $anio) {
                    $datosTabla[] = [
                        'anio' => $anio,
                        'valor' => $datos['valores'][$index]
                    ];
                }
                $resultados['datos'] = $datosTabla;
                $resultados['columnas'] = ['Año', 'Valor Total (COP)'];
            } else {
                // Datos de ejemplo
                $anios = [2019, 2020, 2021, 2022, 2023, 2024];
                $valores = [1500000000, 1800000000, 2200000000, 2500000000, 2800000000, 3100000000];
                
                $resultados['grafico'] = [
                    'tipo' => 'line',
                    'etiquetas' => $anios,
                    'series' => [
                        ['nombre' => 'Valor Total (COP)', 'datos' => $valores]
                    ]
                ];
                
                $datosTabla = [];
                foreach ($anios as $index => $anio) {
                    $datosTabla[] = [
                        'anio' => $anio,
                        'valor' => $valores[$index]
                    ];
                }
                $resultados['datos'] = $datosTabla;
                $resultados['columnas'] = ['Año', 'Valor Total (COP)'];
            }
            break;
            
        case 'rendimiento_por_entidad':
            // Datos de ejemplo
            $entidades = ['Ministerio de Educación', 'Alcaldía de Bogotá', 'Gobernación del Valle', 'IDU', 'SED'];
            $valores = [280000000, 190000000, 320000000, 150000000, 230000000];
            $cantidades = [6, 4, 5, 3, 5];
            
            // Calcular promedio por proyecto
            $promedios = [];
            foreach ($valores as $i => $valor) {
                $promedios[] = $cantidades[$i] > 0 ? $valor / $cantidades[$i] : 0;
            }
            
            $resultados['grafico'] = [
                'tipo' => 'complex',
                'etiquetas' => $entidades,
                'series' => [
                    ['nombre' => 'Valor Total (Millones)', 'tipo' => 'column', 'datos' => array_map(function($v) { return $v / 1000000; }, $valores)],
                    ['nombre' => 'Cantidad Proyectos', 'tipo' => 'column', 'datos' => $cantidades],
                    ['nombre' => 'Promedio por Proyecto (Millones)', 'tipo' => 'line', 'datos' => array_map(function($v) { return $v / 1000000; }, $promedios)]
                ]
            ];
            
            $datosTabla = [];
            foreach ($entidades as $index => $entidad) {
                $datosTabla[] = [
                    'entidad' => $entidad,
                    'valor_total' => $valores[$index],
                    'cantidad' => $cantidades[$index],
                    'promedio' => $promedios[$index]
                ];
            }
            $resultados['datos'] = $datosTabla;
            $resultados['columnas'] = ['Entidad', 'Valor Total (COP)', 'Cantidad Proyectos', 'Promedio por Proyecto'];
            break;
            
        case 'proyectos_vencimiento':
            // Datos de ejemplo
            $results = [
                ['id' => 101, 'numero' => 'PRY-2023-034', 'nombre' => 'Desarrollo Plataforma de Seguimiento Académico', 'fecha_inicio' => '2023-04-15', 'fecha_fin' => '2024-03-29', 'dias_restantes' => 19],
                ['id' => 215, 'numero' => 'PRY-2022-089', 'nombre' => 'Implementación Sistema de Gestión Ambiental', 'fecha_inicio' => '2022-08-10', 'fecha_fin' => '2024-04-12', 'dias_restantes' => 33],
                ['id' => 183, 'numero' => 'PRY-2023-042', 'nombre' => 'Investigación sobre Biodiversidad Local', 'fecha_inicio' => '2023-05-22', 'fecha_fin' => '2024-05-20', 'dias_restantes' => 71],
                ['id' => 196, 'numero' => 'PRY-2023-078', 'nombre' => 'Estudio Socioeconómico Región Central', 'fecha_inicio' => '2023-09-05', 'fecha_fin' => '2024-04-05', 'dias_restantes' => 26],
                ['id' => 208, 'numero' => 'PRY-2022-125', 'nombre' => 'Modernización Infraestructura Educativa', 'fecha_inicio' => '2022-12-01', 'fecha_fin' => '2024-03-15', 'dias_restantes' => 5]
            ];
            
            $resultados['datos'] = $results;
            $resultados['columnas'] = ['ID', 'Número', 'Nombre', 'Fecha Inicio', 'Fecha Fin', 'Días Restantes'];
            break;
            
        case 'distribucion_geografica':
            // Datos de ejemplo
            $departamentos = ['Bogotá D.C.', 'Antioquia', 'Valle del Cauca', 'Atlántico', 'Santander', 'Otros'];
            $cantidades = [25, 12, 8, 5, 4, 10];
            
            $resultados['grafico'] = [
                'tipo' => 'map',
                'regiones' => $departamentos,
                'cantidades' => $cantidades
            ];
            
            $datosTabla = [];
            foreach ($departamentos as $index => $departamento) {
                $datosTabla[] = [
                    'departamento' => $departamento,
                    'cantidad' => $cantidades[$index],
                    'porcentaje' => round(($cantidades[$index] / array_sum($cantidades)) * 100, 1)
                ];
            }
            $resultados['datos'] = $datosTabla;
            $resultados['columnas'] = ['Departamento', 'Cantidad de Proyectos', 'Porcentaje'];
            break;
            
        case 'proyectos_supervisor':
            // Datos de ejemplo
            $supervisores = ['Juan Pérez', 'María López', 'Carlos Rodríguez', 'Ana Martínez', 'Pedro González'];
            $proyectos = [8, 12, 6, 9, 7];
            
            $resultados['grafico'] = [
                'tipo' => 'horizontal-bar',
                'etiquetas' => $supervisores,
                'series' => [
                    ['nombre' => 'Proyectos Asignados', 'datos' => $proyectos]
                ]
            ];
            
            $datosTabla = [];
            foreach ($supervisores as $index => $supervisor) {
                $datosTabla[] = [
                    'supervisor' => $supervisor,
                    'cantidad' => $proyectos[$index]
                ];
            }
            $resultados['datos'] = $datosTabla;
            $resultados['columnas'] = ['Supervisor', 'Cantidad de Proyectos'];
            break;
            
        case 'analisis_financiero':
            // Datos de ejemplo para análisis financiero trimestral
            $trimestres = ['2023-Q1', '2023-Q2', '2023-Q3', '2023-Q4', '2024-Q1'];
            $ingresos = [450000000, 520000000, 480000000, 580000000, 620000000];
            $gastos = [410000000, 460000000, 450000000, 520000000, 570000000];
            
            // Calcular saldos
            $saldos = [];
            foreach ($ingresos as $i => $ingreso) {
                $saldos[] = $ingreso - $gastos[$i];
            }
            
            $resultados['grafico'] = [
                'tipo' => 'complex',
                'etiquetas' => $trimestres,
                'series' => [
                    ['nombre' => 'Ingresos (Millones)', 'tipo' => 'column', 'datos' => array_map(function($v) { return $v / 1000000; }, $ingresos)],
                    ['nombre' => 'Gastos (Millones)', 'tipo' => 'column', 'datos' => array_map(function($v) { return $v / 1000000; }, $gastos)],
                    ['nombre' => 'Saldo (Millones)', 'tipo' => 'line', 'datos' => array_map(function($v) { return $v / 1000000; }, $saldos)]
                ]
            ];
            
            $datosTabla = [];
            foreach ($trimestres as $index => $trimestre) {
                $datosTabla[] = [
                    'trimestre' => $trimestre,
                    'ingresos' => $ingresos[$index],
                    'gastos' => $gastos[$index],
                    'saldo' => $saldos[$index]
                ];
            }
            $resultados['datos'] = $datosTabla;
            $resultados['columnas'] = ['Trimestre', 'Ingresos (COP)', 'Gastos (COP)', 'Saldo (COP)'];
            break;
            
        default:
            // No se ha seleccionado tipo de consulta
            break;
    }
    
    return $resultados;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['tipo_consulta'])) {
    $tipo_consulta_actual = $_POST['tipo_consulta'];
    
    // Recoger los filtros aplicados
    $filtros = [];
    
    if (isset($_POST['anio']) && !empty($_POST['anio'])) {
        $filtros['anio'] = $_POST['anio'];
        $filtros_aplicados[] = "Año: " . $_POST['anio'];
    }
    
    if (isset($_POST['entidad']) && !empty($_POST['entidad'])) {
        $filtros['entidad'] = $_POST['entidad'];
        $filtros_aplicados[] = "Entidad: " . $_POST['entidad'];
    }
    
    if (isset($_POST['situacion']) && !empty($_POST['situacion'])) {
        $filtros['situacion'] = $_POST['situacion'];
        $filtros_aplicados[] = "Situación: " . $_POST['situacion'];
    }
    
    if (isset($_POST['fecha_desde']) && !empty($_POST['fecha_desde'])) {
        $filtros['fecha_desde'] = $_POST['fecha_desde'];
        $filtros_aplicados[] = "Desde: " . date('d/m/Y', strtotime($_POST['fecha_desde']));
    }
    
    if (isset($_POST['fecha_hasta']) && !empty($_POST['fecha_hasta'])) {
        $filtros['fecha_hasta'] = $_POST['fecha_hasta'];
        $filtros_aplicados[] = "Hasta: " . date('d/m/Y', strtotime($_POST['fecha_hasta']));
    }
    
    if (isset($_POST['valor_min']) && !empty($_POST['valor_min'])) {
        $filtros['valor_min'] = (float)str_replace([',', '.'], '', $_POST['valor_min']);
        $filtros_aplicados[] = "Valor mínimo: $" . number_format($_POST['valor_min'], 0, ',', '.');
    }
    
    if (isset($_POST['valor_max']) && !empty($_POST['valor_max'])) {
        $filtros['valor_max'] = (float)str_replace([',', '.'], '', $_POST['valor_max']);
        $filtros_aplicados[] = "Valor máximo: $" . number_format($_POST['valor_max'], 0, ',', '.');
    }
    
    if (isset($_POST['texto_busqueda']) && !empty($_POST['texto_busqueda'])) {
        $filtros['texto_busqueda'] = $_POST['texto_busqueda'];
        $filtros_aplicados[] = "Búsqueda: " . $_POST['texto_busqueda'];
    }
    
    // Obtener los resultados según el tipo de consulta y filtros
    $resultados = obtenerDatosConsulta($tipo_consulta_actual, $filtros);
}
?>

<!-- Estilos específicos para el Centro de Consultas -->
<style>
.consulta-card {
    border-radius: 10px;
    overflow: hidden;
    box-shadow: 0 0 35px rgba(140, 152, 164, 0.125);
    border: none;
    margin-bottom: 24px;
    background: white;
    transition: all 0.2s;
}

.consulta-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 10px 35px rgba(140, 152, 164, 0.225);
}

.consulta-card .card-body {
    padding: 1.5rem;
}

.consulta-card .card-title {
    font-weight: 600;
    margin-bottom: 0.75rem;
    color: #344767;
    font-size: 1.1rem;
}

.consulta-card .card-description {
    color: #67748e;
    font-size: 0.875rem;
    margin-bottom: 1rem;
}

.consulta-card .card-icon {
    width: 50px;
    height: 50px;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.25rem;
    color: #fff;
    margin-bottom: 1rem;
    background: linear-gradient(310deg, #7928ca, #ff0080);
}

.icon-proyectos { background: linear-gradient(310deg, #2152ff, #21d4fd); }
.icon-financiero { background: linear-gradient(310deg, #f53939, #fbcf33); }
.icon-entidades { background: linear-gradient(310deg, #2aeeff, #2152ff); }
.icon-tiempo { background: linear-gradient(310deg, #17ad37, #98ec2d); }
.icon-geografico { background: linear-gradient(310deg, #ff667c, #ea0606); }
.icon-analisis { background: linear-gradient(310deg, #627594, #a8b8d8); }

.consulta-options {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: 24px;
}

.filter-card {
    background: #f8f9fa;
    border: 1px solid #f0f0f0;
    border-radius: 10px;
    padding: 1.5rem;
    margin-bottom: 24px;
}

.filter-title {
    font-weight: 600;
    margin-bottom: 1rem;
    color: #344767;
    font-size: 1rem;
    display: flex;
    align-items: center;
}

.filter-title i {
    margin-right: 8px;
    color: #5e72e4;
}

.filter-form {
    display: flex;
    flex-wrap: wrap;
    gap: 12px;
}

.filter-form .form-group {
    flex: 1 1 200px;
}

.results-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1rem;
}

.results-title {
    font-weight: 600;
    color: #344767;
    font-size: 1.1rem;
    margin: 0;
}

.results-actions {
    display: flex;
    gap: 8px;
}

.results-card {
    background: white;
    border-radius: 10px;
    box-shadow: 0 0 20px rgba(140, 152, 164, 0.1);
    margin-bottom: 24px;
    padding: 1.5rem;
}

.chart-container {
    height: 400px;
    margin-bottom: 1.5rem;
}

.table-container {
    overflow-x: auto;
    margin-bottom: 1rem;
}

.custom-table {
    width: 100%;
    border-collapse: collapse;
}

.custom-table th {
    background: #f8f9fa;
    color: #344767;
    font-weight: 600;
    padding: 0.75rem;
    text-align: left;
    border-bottom: 2px solid #e9ecef;
    white-space: nowrap;
}

.custom-table td {
    padding: 0.75rem;
    border-bottom: 1px solid #e9ecef;
    vertical-align: middle;
}

.custom-table tr:hover {
    background-color: rgba(94, 114, 228, 0.05);
}

.badge-filter {
    display: inline-block;
    padding: 6px 12px;
    background: rgba(94, 114, 228, 0.1);
    border-radius: 30px;
    color: #5e72e4;
    font-size: 0.75rem;
    font-weight: 600;
    margin-right: 8px;
    margin-bottom: 8px;
}

.badge-filter i {
    margin-left: 5px;
    cursor: pointer;
}

.search-header {
    position: relative;
    margin-bottom: 32px;
    padding: 24px;
    border-radius: 10px;
    background: linear-gradient(310deg, #141727, #3a416f);
    color: white;
}

.search-header h4 {
    margin-bottom: 16px;
    font-weight: 700;
}

.search-header p {
    margin-bottom: 16px;
    opacity: 0.8;
}

.global-search {
    display: flex;
    width: 100%;
}

.global-search input {
    flex-grow: 1;
    border-radius: 30px 0 0 30px;
    border: none;
    padding: 12px 20px;
    font-size: 1rem;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
}

.global-search button {
    border-radius: 0 30px 30px 0;
    border: none;
    background: #5e72e4;
    color: white;
    font-weight: 600;
    padding: 0 24px;
    cursor: pointer;
    transition: all 0.2s;
}

.global-search button:hover {
    background: #4a5cd0;
}

.tabs-container {
    margin-bottom: 1.5rem;
}

.nav-tabs {
    border-bottom: 1px solid #e9ecef;
    margin-bottom: 1.5rem;
}

.nav-tabs .nav-link {
    border: none;
    border-bottom: 2px solid transparent;
    color: #67748e;
    font-weight: 600;
    padding: 0.75rem 1rem;
    margin-right: 1rem;
    transition: all 0.2s;
}

.nav-tabs .nav-link.active {
    border-bottom: 2px solid #5e72e4;
    color: #5e72e4;
}

@media (max-width: 768px) {
    .consulta-options {
        grid-template-columns: 1fr;
    }
    
    .filter-form {
        flex-direction: column;
    }
    
    .results-header {
        flex-direction: column;
        align-items: flex-start;
    }
    
    .results-actions {
        margin-top: 1rem;
    }
}
</style>

<div class="container-fluid py-4">
    <!-- Encabezado de búsqueda global -->
    <div class="search-header">
        <div class="row align-items-center">
            <div class="col-md-8">
                <h4><i class="fas fa-search-plus me-2"></i>Centro de Consultas Especializadas</h4>
                <p>Obtén información detallada y análisis avanzados de todos los proyectos y entidades del sistema.</p>
            </div>
            <div class="col-md-4">
                <form method="post" action="">
                    <div class="global-search">
                        <input type="text" name="texto_busqueda" placeholder="Buscar en todos los proyectos..." value="<?php echo isset($_POST['texto_busqueda']) ? htmlspecialchars($_POST['texto_busqueda']) : ''; ?>">
                        <button type="submit" name="tipo_consulta" value="proyectos_activos">
                            <i class="fas fa-search me-1"></i> Buscar
                        </button>
                    </div>
                    <input type="hidden" name="busqueda_global" value="1">
                </form>
            </div>
        </div>
    </div>

    <!-- Opciones de consulta -->
    <?php if (empty($tipo_consulta_actual)): ?>
    <h5 class="font-weight-bolder mb-3 mt-4">Seleccione un tipo de consulta</h5>
    <div class="consulta-options">
        <!-- Proyectos Activos -->
        <div class="consulta-card">
            <div class="card-body">
                <div class="card-icon icon-proyectos">
                    <i class="fas fa-project-diagram"></i>
                </div>
                <h5 class="card-title">Proyectos Activos</h5>
                <p class="card-description">
                    Consulta todos los proyectos activos en el sistema con filtros avanzados.
                </p>
                <form method="post" action="">
                    <input type="hidden" name="tipo_consulta" value="proyectos_activos">
                    <button type="submit" class="btn btn-sm btn-primary">
                        <i class="fas fa-arrow-right me-1"></i> Consultar
                    </button>
                </form>
            </div>
        </div>
        
        <!-- Proyectos por Año -->
        <div class="consulta-card">
            <div class="card-body">
                <div class="card-icon icon-tiempo">
                    <i class="fas fa-calendar-alt"></i>
                </div>
                <h5 class="card-title">Proyectos por Año</h5>
                <p class="card-description">
                    Visualiza la distribución de proyectos por año de suscripción.
                </p>
                <form method="post" action="">
                    <input type="hidden" name="tipo_consulta" value="proyectos_por_anio">
                    <button type="submit" class="btn btn-sm btn-primary">
                        <i class="fas fa-arrow-right me-1"></i> Consultar
                    </button>
                </form>
            </div>
        </div>
        
        <!-- Proyectos por Entidad -->
        <div class="consulta-card">
            <div class="card-body">
                <div class="card-icon icon-entidades">
                    <i class="fas fa-building"></i>
                </div>
                <h5 class="card-title">Proyectos por Entidad</h5>
                <p class="card-description">
                    Analiza la distribución de proyectos por entidad contratante.
                </p>
                <form method="post" action="">
                    <input type="hidden" name="tipo_consulta" value="proyectos_por_entidad">
                    <button type="submit" class="btn btn-sm btn-primary">
                        <i class="fas fa-arrow-right me-1"></i> Consultar
                    </button>
                </form>
            </div>
        </div>
        
        <!-- Proyectos por Situación -->
        <div class="consulta-card">
            <div class="card-body">
                <div class="card-icon" style="background: linear-gradient(310deg, #c1a556, #927510);">
                    <i class="fas fa-tasks"></i>
                </div>
                <h5 class="card-title">Proyectos por Situación</h5>
                <p class="card-description">
                    Visualiza la distribución de proyectos según su situación actual.
                </p>
                <form method="post" action="">
                    <input type="hidden" name="tipo_consulta" value="proyectos_por_situacion">
                    <button type="submit" class="btn btn-sm btn-primary">
                        <i class="fas fa-arrow-right me-1"></i> Consultar
                    </button>
                </form>
            </div>
        </div>
        
        <!-- Valor por Año -->
        <div class="consulta-card">
            <div class="card-body">
                <div class="card-icon icon-financiero">
                    <i class="fas fa-chart-line"></i>
                </div>
                <h5 class="card-title">Valor por Año</h5>
                <p class="card-description">
                    Analiza la evolución del valor total de proyectos a lo largo del tiempo.
                </p>
                <form method="post" action="">
                    <input type="hidden" name="tipo_consulta" value="valor_por_anio">
                    <button type="submit" class="btn btn-sm btn-primary">
                        <i class="fas fa-arrow-right me-1"></i> Consultar
                    </button>
                </form>
            </div>
        </div>
        
        <!-- Rendimiento por Entidad -->
        <div class="consulta-card">
            <div class="card-body">
                <div class="card-icon icon-analisis">
                    <i class="fas fa-analytics"></i>
                </div>
                <h5 class="card-title">Rendimiento por Entidad</h5>
                <p class="card-description">
                    Compara el rendimiento financiero y cantidad de proyectos por entidad.
                </p>
                <form method="post" action="">
                    <input type="hidden" name="tipo_consulta" value="rendimiento_por_entidad">
                    <button type="submit" class="btn btn-sm btn-primary">
                        <i class="fas fa-arrow-right me-1"></i> Consultar
                    </button>
                </form>
            </div>
        </div>
        
        <!-- Proyectos por Vencer -->
        <div class="consulta-card">
            <div class="card-body">
                <div class="card-icon" style="background: linear-gradient(310deg, #f5365c, #f56036);">
                    <i class="fas fa-hourglass-half"></i>
                </div>
                <h5 class="card-title">Proyectos por Vencer</h5>
                <p class="card-description">
                    Lista de proyectos próximos a vencer ordenados por días restantes.
                </p>
                <form method="post" action="">
                    <input type="hidden" name="tipo_consulta" value="proyectos_vencimiento">
                    <button type="submit" class="btn btn-sm btn-primary">
                        <i class="fas fa-arrow-right me-1"></i> Consultar
                    </button>
                </form>
            </div>
        </div>
        
        <!-- Distribución Geográfica -->
        <div class="consulta-card">
            <div class="card-body">
                <div class="card-icon icon-geografico">
                    <i class="fas fa-map-marked-alt"></i>
                </div>
                <h5 class="card-title">Distribución Geográfica</h5>
                <p class="card-description">
                    Visualiza la distribución geográfica de los proyectos por departamento.
                </p>
                <form method="post" action="">
                    <input type="hidden" name="tipo_consulta" value="distribucion_geografica">
                    <button type="submit" class="btn btn-sm btn-primary">
                        <i class="fas fa-arrow-right me-1"></i> Consultar
                    </button>
                </form>
            </div>
        </div>
        
        <!-- Proyectos por Supervisor -->
        <div class="consulta-card">
            <div class="card-body">
                <div class="card-icon" style="background: linear-gradient(310deg, #2dce89, #2dcecc);">
                    <i class="fas fa-user-tie"></i>
                </div>
                <h5 class="card-title">Proyectos por Supervisor</h5>
                <p class="card-description">
                    Analiza la distribución de proyectos por supervisor asignado.
                </p>
                <form method="post" action="">
                    <input type="hidden" name="tipo_consulta" value="proyectos_supervisor">
                    <button type="submit" class="btn btn-sm btn-primary">
                        <i class="fas fa-arrow-right me-1"></i> Consultar
                    </button>
                </form>
            </div>
        </div>
        
        <!-- Análisis Financiero -->
        <div class="consulta-card">
            <div class="card-body">
                <div class="card-icon" style="background: linear-gradient(310deg, #344767, #5974a2);">
                    <i class="fas fa-dollar-sign"></i>
                </div>
                <h5 class="card-title">Análisis Financiero</h5>
                <p class="card-description">
                    Analiza la evolución de ingresos y gastos por periodos.
                </p>
                <form method="post" action="">
                    <input type="hidden" name="tipo_consulta" value="analisis_financiero">
                    <button type="submit" class="btn btn-sm btn-primary">
                        <i class="fas fa-arrow-right me-1"></i> Consultar
                    </button>
                </form>
            </div>
        </div>
    </div>
    <?php else: ?>
    
    <!-- Filtros para la consulta seleccionada -->
    <div class="filter-card">
        <h5 class="filter-title">
            <i class="fas fa-filter"></i> Filtros para consulta: <span class="text-primary"><?php echo $tipos_consulta[$tipo_consulta_actual]; ?></span>
        </h5>
        
        <form method="post" action="" class="filter-form">
            <input type="hidden" name="tipo_consulta" value="<?php echo $tipo_consulta_actual; ?>">
            
            <?php if (in_array($tipo_consulta_actual, ['proyectos_activos', 'proyectos_por_anio', 'valor_por_anio'])): ?>
            <div class="form-group">
                <label for="anio" class="form-label">Año</label>
                <select class="form-select form-select-sm" id="anio" name="anio">
                    <option value="">Todos</option>
                    <?php foreach ($anios as $anio): ?>
                    <option value="<?php echo $anio; ?>" <?php echo (isset($_POST['anio']) && $_POST['anio'] == $anio) ? 'selected' : ''; ?>>
                        <?php echo $anio; ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <?php endif; ?>
            
            <?php if (in_array($tipo_consulta_actual, ['proyectos_activos', 'proyectos_por_entidad', 'rendimiento_por_entidad'])): ?>
            <div class="form-group">
                <label for="entidad" class="form-label">Entidad</label>
                <select class="form-select form-select-sm" id="entidad" name="entidad">
                    <option value="">Todas</option>
                    <?php foreach ($entidades as $entidad): ?>
                    <option value="<?php echo htmlspecialchars($entidad); ?>" <?php echo (isset($_POST['entidad']) && $_POST['entidad'] == $entidad) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($entidad); ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <?php endif; ?>
            
            <?php if (in_array($tipo_consulta_actual, ['proyectos_activos', 'proyectos_por_situacion'])): ?>
            <div class="form-group">
                <label for="situacion" class="form-label">Situación</label>
                <select class="form-select form-select-sm" id="situacion" name="situacion">
                    <option value="">Todas</option>
                    <?php foreach ($situaciones as $situacion): ?>
                    <option value="<?php echo htmlspecialchars($situacion); ?>" <?php echo (isset($_POST['situacion']) && $_POST['situacion'] == $situacion) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($situacion); ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <?php endif; ?>
            
            <?php if (in_array($tipo_consulta_actual, ['proyectos_activos', 'proyectos_vencimiento'])): ?>
            <div class="form-group">
                <label for="fecha_desde" class="form-label">Fecha Desde</label>
                <input type="date" class="form-control form-control-sm" id="fecha_desde" name="fecha_desde" 
                       value="<?php echo isset($_POST['fecha_desde']) ? $_POST['fecha_desde'] : ''; ?>">
            </div>
            
            <div class="form-group">
                <label for="fecha_hasta" class="form-label">Fecha Hasta</label>
                <input type="date" class="form-control form-control-sm" id="fecha_hasta" name="fecha_hasta" 
                       value="<?php echo isset($_POST['fecha_hasta']) ? $_POST['fecha_hasta'] : ''; ?>">
            </div>
            <?php endif; ?>
            
            <?php if (in_array($tipo_consulta_actual, ['proyectos_activos', 'valor_por_anio', 'rendimiento_por_entidad'])): ?>
            <div class="form-group">
                <label for="valor_min" class="form-label">Valor Mínimo</label>
                <input type="text" class="form-control form-control-sm" id="valor_min" name="valor_min" 
                       value="<?php echo isset($_POST['valor_min']) ? $_POST['valor_min'] : ''; ?>"
                       placeholder="Ej: 1.000.000">
            </div>
            
            <div class="form-group">
                <label for="valor_max" class="form-label">Valor Máximo</label>
                <input type="text" class="form-control form-control-sm" id="valor_max" name="valor_max" 
                       value="<?php echo isset($_POST['valor_max']) ? $_POST['valor_max'] : ''; ?>"
                       placeholder="Ej: 100.000.000">
            </div>
            <?php endif; ?>
            
            <div class="form-group">
                <label for="texto_busqueda" class="form-label">Texto a buscar</label>
                <input type="text" class="form-control form-control-sm" id="texto_busqueda" name="texto_busqueda" 
                       value="<?php echo isset($_POST['texto_busqueda']) ? htmlspecialchars($_POST['texto_busqueda']) : ''; ?>"
                       placeholder="Buscar...">
            </div>
            
            <div class="form-group d-flex justify-content-end">
                <button type="submit" class="btn btn-sm btn-primary">
                    <i class="fas fa-search me-1"></i> Aplicar Filtros
                </button>
                
                <a href="main.php?page=consultas_especializadas" class="btn btn-sm btn-outline-secondary ms-2">
                    <i class="fas fa-times me-1"></i> Limpiar
                </a>
            </div>
        </form>
        
        <?php if (!empty($filtros_aplicados)): ?>
        <div class="mt-3">
            <div class="d-flex flex-wrap">
                <?php foreach ($filtros_aplicados as $filtro): ?>
                <span class="badge-filter">
                    <?php echo htmlspecialchars($filtro); ?>
                </span>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>
    
    <!-- Resultados de la consulta -->
    <div class="results-card">
        <div class="results-header">
            <h5 class="results-title">
                <i class="fas fa-chart-bar me-2"></i>
                Resultados: <?php echo $tipos_consulta[$tipo_consulta_actual]; ?>
            </h5>
            
            <div class="results-actions">
                <button type="button" class="btn btn-sm btn-outline-primary" onclick="exportToExcel()">
                    <i class="fas fa-file-excel me-1"></i> Excel
                </button>
                
                <button type="button" class="btn btn-sm btn-outline-danger" onclick="exportToPDF()">
                    <i class="fas fa-file-pdf me-1"></i> PDF
                </button>
                
                <button type="button" class="btn btn-sm btn-outline-info" onclick="printResults()">
                    <i class="fas fa-print me-1"></i> Imprimir
                </button>
            </div>
        </div>
        
        <div class="tabs-container">
            <ul class="nav nav-tabs" id="resultsTabs" role="tablist">
                <?php if (isset($resultados['grafico'])): ?>
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="chart-tab" data-bs-toggle="tab" data-bs-target="#chart-panel" type="button" role="tab">
                        <i class="fas fa-chart-bar me-1"></i> Gráfico
                    </button>
                </li>
                <?php endif; ?>
                
                <?php if (isset($resultados['datos'])): ?>
                <li class="nav-item" role="presentation">
                    <button class="nav-link <?php echo !isset($resultados['grafico']) ? 'active' : ''; ?>" id="table-tab" data-bs-toggle="tab" data-bs-target="#table-panel" type="button" role="tab">
                        <i class="fas fa-table me-1"></i> Tabla
                    </button>
                </li>
                <?php endif; ?>
                
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="summary-tab" data-bs-toggle="tab" data-bs-target="#summary-panel" type="button" role="tab">
                        <i class="fas fa-list-alt me-1"></i> Resumen
                    </button>
                </li>
            </ul>
            
            <div class="tab-content" id="resultsTabContent">
                <?php if (isset($resultados['grafico'])): ?>
                <div class="tab-pane fade show active" id="chart-panel" role="tabpanel">
                    <div class="chart-container">
                        <div id="mainChart" style="width: 100%; height: 100%;"></div>
                    </div>
                </div>
                <?php endif; ?>
                
                <?php if (isset($resultados['datos'])): ?>
                <div class="tab-pane fade <?php echo !isset($resultados['grafico']) ? 'show active' : ''; ?>" id="table-panel" role="tabpanel">
                    <div class="table-container">
                        <table class="custom-table" id="resultTable">
                            <thead>
                                <tr>
                                    <?php foreach ($resultados['columnas'] as $columna): ?>
                                    <th><?php echo htmlspecialchars($columna); ?></th>
                                    <?php endforeach; ?>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($resultados['datos'] as $fila): ?>
                                <tr>
                                    <?php foreach ($fila as $key => $valor): ?>
                                    <td>
                                        <?php 
                                        // Formato especial para valores monetarios
                                        if (strpos(strtolower($key), 'valor') !== false || strpos(strtolower($key), 'ingreso') !== false || strpos(strtolower($key), 'gasto') !== false || strpos(strtolower($key), 'saldo') !== false || strpos(strtolower($key), 'promedio') !== false) {
                                            echo '$ ' . number_format($valor, 0, ',', '.');
                                        } 
                                        // Formato para fechas
                                        elseif (strpos(strtolower($key), 'fecha') !== false && strtotime($valor)) {
                                            echo date('d/m/Y', strtotime($valor));
                                        }
                                        // Formato para días restantes (colorear según urgencia)
                                        elseif ($key === 'dias_restantes') {
                                            $clase = '';
                                            if ($valor <= 15) $clase = 'text-danger fw-bold';
                                            elseif ($valor <= 30) $clase = 'text-warning fw-bold';
                                            echo '<span class="' . $clase . '">' . $valor . ' días</span>';
                                        }
                                        // Valores normales
                                        else {
                                            echo is_string($valor) ? htmlspecialchars($valor) : $valor;
                                        }
                                        ?>
                                    </td>
                                    <?php endforeach; ?>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <?php if (count($resultados['datos']) > 0): ?>
                    <div class="text-end text-muted mt-2">
                        <small>Total: <?php echo count($resultados['datos']); ?> registros</small>
                    </div>
                    <?php else: ?>
                    <div class="alert alert-info">
                        No se encontraron datos para los criterios seleccionados.
                    </div>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
                
                <div class="tab-pane fade" id="summary-panel" role="tabpanel">
                    <div class="row">
                        <?php if (!empty($resultados['datos'])): ?>
                        <div class="col-md-6">
                            <div class="card shadow-sm">
                                <div class="card-body">
                                    <h6 class="card-title"><i class="fas fa-list me-2"></i>Datos generales</h6>
                                    <ul class="list-group list-group-flush">
                                        <li class="list-group-item d-flex justify-content-between">
                                            <span>Total registros:</span>
                                            <strong><?php echo count($resultados['datos']); ?></strong>
                                        </li>
                                        <?php 
                                        // Cálculos específicos según el tipo de consulta
                                        if ($tipo_consulta_actual == 'proyectos_activos'): 
                                            $total_en_ejecucion = 0;
                                            $total_finalizados = 0;
                                            $total_suscritos = 0;
                                            
                                            foreach ($resultados['datos'] as $fila) {
                                                $situacion = strtolower($fila['situacion']);
                                                if (strpos($situacion, 'ejecución') !== false || strpos($situacion, 'ejecucion') !== false) {
                                                    $total_en_ejecucion++;
                                                } elseif (strpos($situacion, 'finalizado') !== false) {
                                                    $total_finalizados++;
                                                } elseif (strpos($situacion, 'suscrito') !== false) {
                                                    $total_suscritos++;
                                                }
                                            }
                                        ?>
                                        <li class="list-group-item d-flex justify-content-between">
                                            <span>En ejecución:</span>
                                            <strong><?php echo $total_en_ejecucion; ?></strong>
                                        </li>
                                        <li class="list-group-item d-flex justify-content-between">
                                            <span>Finalizados:</span>
                                            <strong><?php echo $total_finalizados; ?></strong>
                                        </li>
                                        <li class="list-group-item d-flex justify-content-between">
                                            <span>Suscritos:</span>
                                            <strong><?php echo $total_suscritos; ?></strong>
                                        </li>
                                        <?php elseif (in_array($tipo_consulta_actual, ['valor_por_anio', 'analisis_financiero'])): 
                                            // Calcular sumatoria si hay campo valor
                                            $total_valor = 0;
                                            foreach ($resultados['datos'] as $fila) {
                                                if (isset($fila['valor'])) {
                                                    $total_valor += $fila['valor'];
                                                }
                                                if (isset($fila['ingresos'])) {
                                                    $total_ingresos = 0;
                                                    $total_gastos = 0;
                                                    foreach ($resultados['datos'] as $fila) {
                                                        $total_ingresos += $fila['ingresos'];
                                                        $total_gastos += $fila['gastos'];
                                                    }
                                                }
                                            }
                                            
                                            if ($total_valor > 0):
                                        ?>
                                        <li class="list-group-item d-flex justify-content-between">
                                            <span>Valor total:</span>
                                            <strong>$ <?php echo number_format($total_valor, 0, ',', '.'); ?></strong>
                                        </li>
                                        <?php endif;
                                        
                                            if (isset($total_ingresos)):
                                        ?>
                                        <li class="list-group-item d-flex justify-content-between">
                                            <span>Total ingresos:</span>
                                            <strong>$ <?php echo number_format($total_ingresos, 0, ',', '.'); ?></strong>
                                        </li>
                                        <li class="list-group-item d-flex justify-content-between">
                                            <span>Total gastos:</span>
                                            <strong>$ <?php echo number_format($total_gastos, 0, ',', '.'); ?></strong>
                                        </li>
                                        <li class="list-group-item d-flex justify-content-between">
                                            <span>Balance:</span>
                                            <strong class="<?php echo ($total_ingresos - $total_gastos) >= 0 ? 'text-success' : 'text-danger'; ?>">
                                                $ <?php echo number_format($total_ingresos - $total_gastos, 0, ',', '.'); ?>
                                            </strong>
                                        </li>
                                        <?php endif;
                                        endif; ?>
                                    </ul>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="card shadow-sm">
                                <div class="card-body">
                                    <h6 class="card-title"><i class="fas fa-filter me-2"></i>Filtros aplicados</h6>
                                    <?php if (!empty($filtros_aplicados)): ?>
                                    <ul class="list-group list-group-flush">
                                        <?php foreach ($filtros_aplicados as $filtro): ?>
                                        <li class="list-group-item">
                                            <i class="fas fa-check-circle text-success me-2"></i>
                                            <?php echo htmlspecialchars($filtro); ?>
                                        </li>
                                        <?php endforeach; ?>
                                    </ul>
                                    <?php else: ?>
                                    <p class="text-muted mb-0">No se aplicaron filtros a esta consulta.</p>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        <?php else: ?>
                        <div class="col-12">
                            <div class="alert alert-warning">
                                <i class="fas fa-exclamation-triangle me-2"></i>
                                No hay datos disponibles para generar un resumen.
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Botón para volver -->
    <div class="text-center mb-4">
        <a href="main.php?page=consultas_especializadas" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-left me-1"></i> Volver a Todas las Consultas
        </a>
    </div>
    <?php endif; ?>
</div>

<!-- Dependencias para ApexCharts -->
<script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>

<!-- Scripts para la exportación -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.17.0/xlsx.full.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.4.0/jspdf.umd.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.20/jspdf.plugin.autotable.min.js"></script>

<!-- Script para la inicialización de gráficos y exportaciones -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    <?php if (isset($resultados['grafico'])): ?>
    // Inicializar gráfico
    initChart();
    <?php endif; ?>
    
    // Inicializar tooltips de Bootstrap
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    var tooltipList = tooltipTriggerList.map(function(tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
    
    // Formatear campos numéricos de moneda
    formatCurrencyFields();
});

// Función para inicializar el gráfico basado en el tipo
function initChart() {
    <?php if (isset($resultados['grafico'])): ?>
    var chartType = '<?php echo $resultados['grafico']['tipo']; ?>';
    var chartElement = document.getElementById('mainChart');
    
    if (!chartElement) return;
    
    var options = {};
    
    switch (chartType) {
        case 'bar':
            options = {
                chart: {
                    type: 'bar',
                    height: 380,
                    toolbar: {
                        show: true,
                        tools: {
                            download: true,
                            selection: true,
                            zoom: true,
                            zoomin: true,
                            zoomout: true,
                            pan: true
                        }
                    },
                    animations: {
                        enabled: true,
                        easing: 'easeinout',
                        speed: 800
                    }
                },
                plotOptions: {
                    bar: {
                        horizontal: false,
                        columnWidth: '55%',
                        borderRadius: 4,
                        dataLabels: {
                            position: 'top'
                        }
                    }
                },
                dataLabels: {
                    enabled: true,
                    formatter: function(val) {
                        return val;
                    },
                    offsetY: -20,
                    style: {
                        fontSize: '12px',
                        colors: ["#304758"]
                    }
                },
                colors: ['#5e72e4'],
                series: <?php echo json_encode($resultados['grafico']['series']); ?>,
                xaxis: {
                    categories: <?php echo json_encode($resultados['grafico']['etiquetas']); ?>,
                    position: 'bottom',
                    labels: {
                        offsetY: 0
                    },
                    axisBorder: {
                        show: false
                    },
                    axisTicks: {
                        show: false
                    },
                    tooltip: {
                        enabled: true,
                    }
                },
                yaxis: {
                    labels: {
                        formatter: function(val) {
                            return val.toFixed(0);
                        }
                    }
                },
                title: {
                    text: '<?php echo $tipos_consulta[$tipo_consulta_actual]; ?>',
                    align: 'center',
                    style: {
                        fontSize: '18px',
                        fontWeight: 'bold',
                        color: '#263238'
                    }
                },
                grid: {
                    borderColor: '#e7e7e7',
                    row: {
                        colors: ['#f3f3f3', 'transparent'],
                        opacity: 0.5
                    }
                },
                tooltip: {
                    y: {
                        formatter: function(val) {
                            return val;
                        }
                    }
                }
            };
            break;
        
        case 'pie':
            options = {
                chart: {
                    type: 'pie',
                    height: 380,
                    toolbar: {
                        show: true
                    },
                    animations: {
                        enabled: true,
                        easing: 'easeinout',
                        speed: 800,
                        animateGradually: {
                            enabled: true,
                            delay: 150
                        },
                        dynamicAnimation: {
                            enabled: true,
                            speed: 350
                        }
                    }
                },
                series: <?php echo json_encode($resultados['grafico']['series']); ?>,
                labels: <?php echo json_encode($resultados['grafico']['etiquetas']); ?>,
                colors: ['#5e72e4', '#f5365c', '#2dce89', '#fb6340', '#11cdef', '#8965e0', '#f53939', '#adb5bd'],
                title: {
                    text: '<?php echo $tipos_consulta[$tipo_consulta_actual]; ?>',
                    align: 'center',
                    style: {
                        fontSize: '18px',
                        fontWeight: 'bold',
                        color: '#263238'
                    }
                },
                dataLabels: {
                    enabled: true,
                    formatter: function(val, opts) {
                        return opts.w.config.series[opts.seriesIndex] + ' (' + val.toFixed(1) + '%)';
                    },
                    style: {
                        fontSize: '14px',
                        fontFamily: 'Helvetica, Arial, sans-serif',
                        fontWeight: 'bold'
                    },
                    dropShadow: {
                        enabled: false
                    }
                },
                legend: {
                    position: 'bottom',
                    horizontalAlign: 'center',
                    fontSize: '14px',
                    markers: {
                        width: 12,
                        height: 12,
                        strokeWidth: 0,
                        radius: 12
                    }
                },
                tooltip: {
                    y: {
                        formatter: function(val) {
                            return val;
                        }
                    }
                },
                responsive: [{
                    breakpoint: 480,
                    options: {
                        chart: {
                            width: 300
                        },
                        legend: {
                            position: 'bottom'
                        }
                    }
                }]
            };
            break;
        
        case 'donut':
            options = {
                chart: {
                    type: 'donut',
                    height: 380,
                    toolbar: {
                        show: true
                    },
                    animations: {
                        enabled: true,
                        easing: 'easeinout',
                        speed: 800,
                        animateGradually: {
                            enabled: true,
                            delay: 150
                        },
                        dynamicAnimation: {
                            enabled: true,
                            speed: 350
                        }
                    }
                },
                series: <?php echo json_encode($resultados['grafico']['series']); ?>,
                labels: <?php echo json_encode($resultados['grafico']['etiquetas']); ?>,
                colors: ['#5e72e4', '#f5365c', '#2dce89', '#fb6340', '#11cdef'],
                title: {
                    text: '<?php echo $tipos_consulta[$tipo_consulta_actual]; ?>',
                    align: 'center',
                    style: {
                        fontSize: '18px',
                        fontWeight: 'bold',
                        color: '#263238'
                    }
                },
                plotOptions: {
                    pie: {
                        donut: {
                            size: '55%',
                            labels: {
                                show: true,
                                total: {
                                    show: true,
                                    label: 'Total',
                                    formatter: function(w) {
                                        return w.globals.seriesTotals.reduce((a, b) => a + b, 0);
                                    }
                                }
                            }
                        }
                    }
                },
                dataLabels: {
                    enabled: true,
                    formatter: function(val, opts) {
                        return opts.w.config.series[opts.seriesIndex] + ' (' + val.toFixed(1) + '%)';
                    }
                },
                legend: {
                    position: 'bottom',
                    horizontalAlign: 'center'
                },
                responsive: [{
                    breakpoint: 480,
                    options: {
                        chart: {
                            width: 300
                        },
                        legend: {
                            position: 'bottom'
                        }
                    }
                }]
            };
            break;
        
        case 'line':
            options = {
                chart: {
                    type: 'line',
                    height: 380,
                    toolbar: {
                        show: true
                    },
                    zoom: {
                        enabled: true
                    },
                    animations: {
                        enabled: true,
                        easing: 'linear',
                        dynamicAnimation: {
                            speed: 1000
                        }
                    }
                },
                stroke: {
                    curve: 'smooth',
                    width: 3
                },
                series: <?php echo json_encode($resultados['grafico']['series']); ?>,
                xaxis: {
                    categories: <?php echo json_encode($resultados['grafico']['etiquetas']); ?>,
                    labels: {
                        rotate: -45,
                        rotateAlways: false
                    }
                },
                title: {
                    text: '<?php echo $tipos_consulta[$tipo_consulta_actual]; ?>',
                    align: 'center',
                    style: {
                        fontSize: '18px',
                        fontWeight: 'bold',
                        color: '#263238'
                    }
                },
                colors: ['#5e72e4'],
                markers: {
                    size: 6,
                    colors: ['#5e72e4'],
                    strokeColors: '#fff',
                    strokeWidth: 2,
                    hover: {
                        size: 8
                    }
                },
                yaxis: {
                    title: {
                        text: 'Valor'
                    },
                    labels: {
                        formatter: function(val) {
                            return val >= 1000000 ? (val / 1000000).toFixed(1) + 'M' : val.toFixed(0);
                        }
                    }
                },
                grid: {
                    borderColor: '#e7e7e7',
                    row: {
                        colors: ['#f3f3f3', 'transparent'],
                        opacity: 0.5
                    }
                },
                legend: {
                    position: 'top',
                    horizontalAlign: 'right'
                }
            };
            break;
        
        case 'horizontal-bar':
            options = {
                chart: {
                    type: 'bar',
                    height: 380,
                    toolbar: {
                        show: true
                    }
                },
                plotOptions: {
                    bar: {
                        horizontal: true,
                        barHeight: '50%',
                        distributed: false,
                        borderRadius: 4,
                        dataLabels: {
                            position: 'bottom'
                        }
                    }
                },
                colors: ['#5e72e4'],
                dataLabels: {
                    enabled: true,
                    textAnchor: 'start',
                    style: {
                        colors: ['#fff']
                    },
                    formatter: function(val, opt) {
                        return val;
                    },
                    offsetX: 0
                },
                series: <?php echo json_encode($resultados['grafico']['series']); ?>,
                xaxis: {
                    categories: <?php echo json_encode($resultados['grafico']['etiquetas']); ?>
                },
                title: {
                    text: '<?php echo $tipos_consulta[$tipo_consulta_actual]; ?>',
                    align: 'center',
                    style: {
                        fontSize: '18px',
                        fontWeight: 'bold',
                        color: '#263238'
                    }
                },
                tooltip: {
                    theme: 'dark',
                    y: {
                        formatter: function(val) {
                            return val;
                        }
                    }
                }
            };
            break;
        
        case 'complex':
            options = {
                chart: {
                    type: 'line',
                    height: 380,
                    stacked: false,
                    toolbar: {
                        show: true
                    }
                },
                dataLabels: {
                    enabled: false
                },
                series: <?php echo json_encode($resultados['grafico']['series']); ?>,
                xaxis: {
                    categories: <?php echo json_encode($resultados['grafico']['etiquetas']); ?>
                },
                yaxis: [
                    {
                        axisTicks: {
                            show: true,
                        },
                        axisBorder: {
                            show: true,
                            color: '#5e72e4'
                        },
                        labels: {
                            style: {
                                colors: '#5e72e4',
                            }
                        },
                        title: {
                            text: "Millones",
                            style: {
                                color: '#5e72e4',
                            }
                        }
                    },
                    {
                        opposite: true,
                        axisTicks: {
                            show: true,
                        },
                        axisBorder: {
                            show: true,
                            color: '#f5365c'
                        },
                        labels: {
                            style: {
                                colors: '#f5365c',
                            },
                        },
                        title: {
                            text: "Unidades",
                            style: {
                                color: '#f5365c',
                            }
                        }
                    }
                ],
                colors: ['#5e72e4', '#f5365c', '#2dce89'],
                tooltip: {
                    fixed: {
                        enabled: true,
                        position: 'topLeft',
                        offsetY: 30,
                        offsetX: 60
                    }
                },
                legend: {
                    horizontalAlign: 'center',
                    offsetX: 40
                },
                title: {
                    text: '<?php echo $tipos_consulta[$tipo_consulta_actual]; ?>',
                    align: 'center',
                    style: {
                        fontSize: '18px',
                        fontWeight: 'bold',
                        color: '#263238'
                    }
                }
            };
            break;
        
        case 'map':
            // Para mapas necesitaríamos una librería específica para Colombia
            // En este ejemplo usamos un gráfico de barras como alternativa
            options = {
                chart: {
                    type: 'bar',
                    height: 380,
                    toolbar: {
                        show: true
                    }
                },
                plotOptions: {
                    bar: {
                        horizontal: false,
                        columnWidth: '55%',
                        endingShape: 'rounded'
                    }
                },
                dataLabels: {
                    enabled: true
                },
                series: [{
                    name: 'Proyectos',
                    data: <?php echo json_encode($resultados['grafico']['cantidades']); ?>
                }],
                xaxis: {
                    categories: <?php echo json_encode($resultados['grafico']['regiones']); ?>
                },
                colors: ['#11cdef'],
                title: {
                    text: 'Distribución Geográfica de Proyectos',
                    align: 'center',
                    style: {
                        fontSize: '18px',
                        fontWeight: 'bold',
                        color: '#263238'
                    }
                },
                fill: {
                    type: 'gradient',
                    gradient: {
                        shade: 'light',
                        type: "horizontal",
                        shadeIntensity: 0.25,
                        gradientToColors: undefined,
                        inverseColors: true,
                        opacityFrom: 0.85,
                        opacityTo: 0.85,
                        stops: [50, 0, 100]
                    }
                }
            };
            break;
        
        default:
            break;
    }
    
    var chart = new ApexCharts(chartElement, options);
    chart.render();
    <?php endif; ?>
}

// Función para formatear campos de moneda
function formatCurrencyFields() {
    var currencyInputs = document.querySelectorAll('#valor_min, #valor_max');
    
    currencyInputs.forEach(function(input) {
        input.addEventListener('blur', function() {
            var value = this.value.replace(/\D/g, '');
            if (value) {
                this.value = new Intl.NumberFormat('es-CO').format(value);
            }
        });
        
        input.addEventListener('focus', function() {
            this.value = this.value.replace(/\D/g, '');
        });
    });
}

// Función para exportar a Excel
function exportToExcel() {
    var table = document.getElementById('resultTable');
    if (!table) {
        alert('No hay datos para exportar');
        return;
    }
    
    var workbook = XLSX.utils.book_new();
    var worksheet = XLSX.utils.table_to_sheet(table);
    
    // Ajustar el ancho de las columnas
    var wscols = [];
    for (var i = 0; i < table.rows[0].cells.length; i++) {
        wscols.push({ wch: 20 });
    }
    worksheet['!cols'] = wscols;
    
    XLSX.utils.book_append_sheet(workbook, worksheet, 'Consulta');
    
    // Nombre del archivo con la fecha actual
    var fileName = 'Consulta_' + '<?php echo $tipos_consulta[$tipo_consulta_actual]; ?>' + '_' + 
                  new Date().toISOString().split('T')[0] + '.xlsx';
    
    XLSX.writeFile(workbook, fileName);
}

// Función para exportar a PDF
function exportToPDF() {
    var table = document.getElementById('resultTable');
    if (!table) {
        alert('No hay datos para exportar');
        return;
    }
    
    // Generar título en base al tipo de consulta
    var title = 'Consulta: <?php echo $tipos_consulta[$tipo_consulta_actual]; ?>';
    var date = new Date().toLocaleString();
    
    var doc = new jspdf.jsPDF();
    
    // Agregar título
    doc.setFontSize(16);
    doc.text(title, 14, 15);
    
    // Agregar fecha
    doc.setFontSize(10);
    doc.text('Fecha de generación: ' + date, 14, 22);
    
    // Agregar filtros
    <?php if (!empty($filtros_aplicados)): ?>
    doc.setFontSize(11);
    doc.text('Filtros aplicados:', 14, 30);
    var yPos = 35;
    <?php foreach ($filtros_aplicados as $index => $filtro): ?>
    doc.setFontSize(9);
    doc.text('- <?php echo addslashes($filtro); ?>', 16, yPos);
    yPos += 5;
    <?php endforeach; ?>
    <?php endif; ?>
    
    // Agregar tabla
    doc.autoTable({
        html: '#resultTable',
        startY: <?php echo empty($filtros_aplicados) ? 30 : 'yPos + 5'; ?>,
        theme: 'grid',
        headStyles: {
            fillColor: [94, 114, 228],
            textColor: 255,
            fontStyle: 'bold'
        },
        alternateRowStyles: {
            fillColor: [240, 240, 240]
        },
        margin: { top: 10 }
    });
    
    // Guardar el PDF
    var fileName = 'Consulta_' + '<?php echo $tipos_consulta[$tipo_consulta_actual]; ?>' + '_' + 
                  new Date().toISOString().split('T')[0] + '.pdf';
    doc.save(fileName);
}

// Función para imprimir resultados
function printResults() {
    var printContents = document.getElementById('resultTable').outerHTML;
    var originalContents = document.body.innerHTML;
    
    // Crear el contenido para imprimir
    var printPage = document.createElement('div');
    
    // Añadir título
    var title = document.createElement('h2');
    title.textContent = 'Consulta: <?php echo $tipos_consulta[$tipo_consulta_actual]; ?>';
    printPage.appendChild(title);
    
    // Añadir fecha
    var date = document.createElement('p');
    date.textContent = 'Fecha de generación: ' + new Date().toLocaleString();
    printPage.appendChild(date);
    
    // Añadir filtros
    <?php if (!empty($filtros_aplicados)): ?>
    var filterTitle = document.createElement('h4');
    filterTitle.textContent = 'Filtros aplicados:';
    printPage.appendChild(filterTitle);
    
    var filterList = document.createElement('ul');
    <?php foreach ($filtros_aplicados as $filtro): ?>
    var filterItem = document.createElement('li');
    filterItem.textContent = '<?php echo addslashes($filtro); ?>';
    filterList.appendChild(filterItem);
    <?php endforeach; ?>
    printPage.appendChild(filterList);
    <?php endif; ?>
    
    // Añadir tabla
    printPage.innerHTML += printContents;
    
    // Aplicar estilos
    var style = document.createElement('style');
    style.innerHTML = `
        body { font-family: Arial, sans-serif; padding: 20px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
        h2 { margin-bottom: 5px; }
        p { margin-top: 0; margin-bottom: 15px; color: #666; }
        ul { margin-bottom: 20px; }
    `;
    
    // Configurar la ventana de impresión
    var printWindow = window.open('', '_blank');
    printWindow.document.write('<html><head><title>Resultados de Consulta</title>');
    printWindow.document.write(style.outerHTML);
    printWindow.document.write('</head><body>');
    printWindow.document.write(printPage.innerHTML);
    printWindow.document.write('</body></html>');
    
    printWindow.document.close();
    printWindow.focus();
    
    // Imprimir después de cargar todo el contenido
    printWindow.onload = function() {
        printWindow.print();
        printWindow.close();
    };
}