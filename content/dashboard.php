<?php
// Verificar si el archivo modelo existe
if (file_exists('models/proyecto_model.php')) {
    // Intentar incluir el modelo de proyecto
    include_once 'models/proyecto_model.php';
    
    // Verificar si las funciones existen
    if (function_exists('contarProyectosSuscritos')) {
        // Obtener el conteo de proyectos suscritos
        $proyectosSuscritos = contarProyectosSuscritos();
        $proyectosEjecucion = function_exists('contarProyectosEjecucion') ? contarProyectosEjecucion() : 0;
        $proyectosSuscritosAnioActual = function_exists('contarProyectosSuscritosAnioActual') ? contarProyectosSuscritosAnioActual() : 0;
        $valorProyectosSuscritosAnioActual = function_exists('obtenerValorProyectosSuscritosAnioActual') ? obtenerValorProyectosSuscritosAnioActual() : 0;
        

        
        // Obtener otros datos para el dashboard
        $proyectosActivos = function_exists('contarProyectosActivos') ? contarProyectosActivos() : 42;
        $entidades = function_exists('contarEntidades') ? contarEntidades() : 28;
        $presupuestoTotal = function_exists('obtenerPresupuestoTotal') ? obtenerPresupuestoTotal() : 2300000;
    } else {
        // Valores por defecto si las funciones no existen
        $proyectosSuscritos = 24;
        $proyectosActivos = 42;
        $entidades = 28;
        $presupuestoTotal = 2300000;
    }
} else {
    // Valores por defecto si el archivo no existe
    $proyectosSuscritos = 24;
    $proyectosActivos = 42;
    $entidades = 28;
    $presupuestoTotal = 2300000;
}
?>

<!-- Dashboard Content -->
<div class="row">
  <div class="col-xl-3 col-sm-6 mb-xl-0 mb-4">
    <div class="card">
      <div class="card-body p-3">
        <div class="row">
          <div class="col-8">
            <div class="numbers">
              <p class="text-sm mb-0 text-uppercase font-weight-bold">Proyectos Suscritos</p>
              <h5 class="font-weight-bolder"><?php echo $proyectosSuscritos; ?></h5>
              <p class="mb-0">
                <span class="text-success text-sm font-weight-bolder">Según estado SIEXUD</span>
              </p>
            </div>
          </div>
          
        </div>
      </div>
    </div>
  </div>
  <div class="col-xl-3 col-sm-6 mb-xl-0 mb-4">
    <div class="card">
        <div class="card-body p-3">
        <div class="row">
            <div class="col-8">
            <div class="numbers">
                <p class="text-sm mb-0 text-uppercase font-weight-bold">Proyectos En Ejecución</p>
                <h5 class="font-weight-bolder"><?php echo $proyectosEjecucion; ?></h5>
                <p class="mb-0">
                <span class="text-info text-sm font-weight-bolder">Situación 14 - SIEXUD</span>
                </p>
            </div>
            </div>
            
        </div>
        </div>
    </div>
    </div>
    <div class="col-xl-3 col-sm-6 mb-xl-0 mb-4">
    <div class="card">
        <div class="card-body p-3">
        <div class="row">
            <div class="col-8">
            <div class="numbers">
                <p class="text-sm mb-0 text-uppercase font-weight-bold">Proyectos Suscritos <?php echo date('Y'); ?></p>
                <h5 class="font-weight-bolder"><?php echo $proyectosSuscritosAnioActual; ?></h5>
                <p class="mb-0">
                <span class="text-success text-sm font-weight-bolder">Estado Activo</span>
                </p>
            </div>
            </div>
        </div>
        </div>
    </div>
    </div>
  <div class="col-xl-3 col-sm-6 mb-xl-0 mb-4">
  <div class="card">
    <div class="card-body p-3">
      <div class="row">
        <div class="col-8">
          <div class="numbers">
            <p class="text-sm mb-0 text-uppercase font-weight-bold">Valor Suscrito <?php echo date('Y'); ?></p>
            <h5 class="font-weight-bolder">$<?php echo number_format($valorProyectosSuscritosAnioActual, 2); ?></h5>
            <p class="mb-0">
              <span class="text-success text-sm font-weight-bolder">Proyectos Activos</span>
            </p>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
</div>




<?php
// Obtener datos para la gráfica
$datosGrafica = function_exists('obtenerValoresPorAnio') ? obtenerValoresPorAnio(10) : [];



// Convertir los datos a formato JSON para usar en JavaScript
$aniosJSON = json_encode($datosGrafica['anios']);
$valoresJSON = json_encode($datosGrafica['valores']);
?>

<!-- Contenedor de la gráfica -->
<div class="row mt-4">
  <div class="col-12">
    <div class="card z-index-2">
      <div class="card-header pb-0 pt-3 bg-transparent">
        <h6 class="text-capitalize">Valor Suscrito por Año</h6>
        <p class="text-sm mb-0">
          <i class="fa fa-arrow-up text-success"></i>
          <span class="font-weight-bold">Evolución del valor</span> de proyectos suscritos
        </p>
      </div>
      <div class="card-body p-3">
        <div class="chart">
          <canvas id="chart-years" class="chart-canvas" height="300"></canvas>
        </div>
      </div>
    </div>
  </div>
</div>


<?php
// Obtener datos para la gráfica de cantidad de proyectos
$datosProyectos = function_exists('obtenerCantidadProyectosPorAnio') ? obtenerCantidadProyectosPorAnio(10) : [];

// Si no hay datos (o hubo un error), usar datos de ejemplo
if (empty($datosProyectos) || empty($datosProyectos['anios'])) {
    $datosProyectos = [
        'anios' => ['2015', '2016', '2017', '2018', '2019', '2020', '2021', '2022', '2023', '2024'],
        'cantidades' => [5, 8, 10, 12, 9, 15, 20, 18, 25, 22]
    ];
}

// Convertir los datos a formato JSON para usar en JavaScript
$aniosProyectosJSON = json_encode($datosProyectos['anios']);
$cantidadesJSON = json_encode($datosProyectos['cantidades']);

// Obtener datos para la tabla de entidades
$proyectosPorEntidad = function_exists('obtenerProyectosPorEntidad') ? obtenerProyectosPorEntidad() : [
    ['entidad' => 'Ministerio de Educación', 'cantidad' => 15],
    ['entidad' => 'Alcaldía de Bogotá', 'cantidad' => 12],
    ['entidad' => 'Gobernación del Valle', 'cantidad' => 8],
    ['entidad' => 'Secretaría de Educación', 'cantidad' => 7],
    ['entidad' => 'Instituto de Desarrollo Urbano', 'cantidad' => 5]
];
?>

<!-- Contenedor de las dos gráficas lado a lado -->
<div class="row mt-4">
  <div class="col-lg-6">
    <div class="card z-index-2">
      <div class="card-header pb-0 pt-3 bg-transparent">
        <h6 class="text-capitalize">Cantidad de Proyectos por Año</h6>
        <p class="text-sm mb-0">
          <i class="fa fa-chart-bar text-primary"></i>
          <span class="font-weight-bold">Evolución histórica</span> de proyectos suscritos
        </p>
      </div>
      <div class="card-body p-3">
        <div class="chart">
          <canvas id="chart-projects" class="chart-canvas" height="300"></canvas>
        </div>
      </div>
    </div>
  </div>
  
  <!-- Tabla de Proyectos por Entidad -->
  <div class="col-lg-6">
    <div class="card z-index-2">
      <div class="card-header pb-0 pt-3 bg-transparent">
        <h6 class="text-capitalize">Proyectos por Entidad</h6>
        <p class="text-sm mb-0">
          <i class="fa fa-building text-info"></i>
          <span class="font-weight-bold">Distribución</span> por entidad contratante
        </p>
      </div>
      <div class="card-body p-3">
        <div class="table-responsive" style="max-height: 300px;">
          <table class="table table-sm align-items-center mb-0">
            <thead>
              <tr>
                <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Entidad</th>
                <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2 text-center">Proyectos</th>
                <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2 text-center">%</th>
              </tr>
            </thead>
            <tbody>
              <?php 
              // Calcular el total de proyectos para el porcentaje
              $totalProyectos = array_sum(array_column($proyectosPorEntidad, 'cantidad'));
              
              // Mostrar solo las primeras 10 entidades
              $contador = 0;
              foreach ($proyectosPorEntidad as $entidad): 
                $contador++;
                if ($contador > 10) break; // Limitar a 10 entidades
                
                // Calcular porcentaje
                $porcentaje = ($totalProyectos > 0) ? ($entidad['cantidad'] / $totalProyectos) * 100 : 0;
              ?>
              <tr>
                <td>
                  <div class="d-flex px-2 py-1">
                    <div class="d-flex flex-column justify-content-center">
                      <h6 class="mb-0 text-xs"><?php echo htmlspecialchars($entidad['entidad']); ?></h6>
                    </div>
                  </div>
                </td>
                <td class="text-center">
                  <span class="text-xs font-weight-bold"><?php echo $entidad['cantidad']; ?></span>
                </td>
                <td class="text-center">
                  <div class="d-flex align-items-center justify-content-center">
                    <span class="me-2 text-xs font-weight-bold"><?php echo number_format($porcentaje, 1); ?>%</span>
                    <div>
                      <div class="progress">
                        <div class="progress-bar bg-gradient-info" role="progressbar" aria-valuenow="<?php echo $porcentaje; ?>" aria-valuemin="0" aria-valuemax="100" style="width: <?php echo $porcentaje; ?>%;"></div>
                      </div>
                    </div>
                  </div>
                </td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>



<?php
// Obtener datos de proyectos y entidades
$proyectosEntidades = function_exists('obtenerProyectosEntidades') ? obtenerProyectosEntidades() : [];

// Si no hay datos, usar datos de ejemplo
if (empty($proyectosEntidades)) {
    $proyectosEntidades = [
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

// Función para formatear fechas
function formatearFecha($fecha) {
    if (!$fecha) return "No definida";
    
    $date = new DateTime($fecha);
    return $date->format('d/m/Y');
}
?>

<!-- Tabla de Proyectos y Entidades - Con scroll y filtros -->
<div class="row mt-4">
  <div class="col-12">
    <div class="card">
      <div class="card-header p-2">
        <div class="d-flex justify-content-between align-items-center">
          <h6 class="mb-0 text-sm">Proyectos Activos y Entidades</h6>
          <button type="button" class="btn btn-xs bg-gradient-primary mb-0 py-1 px-2">
            <i class="fas fa-file-export fa-sm"></i> Exportar
          </button>
        </div>
      </div>
      
      <!-- Sección de filtros -->
      <div class="card-header p-2 border-top">
        <div class="row g-2">
          <div class="col-md-3">
            <div class="input-group input-group-sm">
              <span class="input-group-text" id="basic-addon1">Proyecto</span>
              <input type="text" class="form-control form-control-sm" id="filtroProyecto" placeholder="Buscar...">
            </div>
          </div>
          <div class="col-md-3">
            <div class="input-group input-group-sm">
              <span class="input-group-text" id="basic-addon2">Entidad</span>
              <input type="text" class="form-control form-control-sm" id="filtroEntidad" placeholder="Buscar...">
            </div>
          </div>
          <div class="col-md-3">
            <div class="input-group input-group-sm">
              <span class="input-group-text" id="basic-addon3">Desde</span>
              <input type="date" class="form-control form-control-sm" id="filtroFechaInicio">
            </div>
          </div>
          <div class="col-md-3">
            <div class="input-group input-group-sm">
              <span class="input-group-text" id="basic-addon4">Hasta</span>
              <input type="date" class="form-control form-control-sm" id="filtroFechaFin">
            </div>
          </div>
        </div>
      </div>
      
      <div class="card-body px-0 py-0">
        <div class="table-responsive" style="max-height: 400px; overflow-y: auto;">
          <table class="table table-sm table-striped align-items-center mb-0" id="tablaProyectos">
            <thead style="position: sticky; top: 0; background-color: white; z-index: 10;">
              <tr>
                <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2">Proyecto</th>
                <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2">Código</th>
                <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2">Entidad</th>
                <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2">Fecha Inicio</th>
                <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2">Fecha Fin</th>
                <th class="text-secondary opacity-7 ps-2" width="30"></th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($proyectosEntidades as $proyecto): ?>
              <tr class="fila-proyecto">
                <td class="ps-2">
                  <p class="text-xs font-weight-bold mb-0 text-wrap nombre-proyecto" style="max-width: 250px;"><?php echo htmlspecialchars($proyecto['nombre_proyecto']); ?></p>
                </td>
                <td>
                  <p class="text-xs mb-0 codigo-proyecto"><?php echo htmlspecialchars($proyecto['codigo']); ?></p>
                </td>
                <td>
                  <p class="text-xs mb-0 text-wrap nombre-entidad" style="max-width: 200px;"><?php echo htmlspecialchars($proyecto['nombre_entidad']); ?></p>
                </td>
                <td>
                  <p class="text-xs mb-0 fecha-inicio" data-fecha="<?php echo $proyecto['fecha_inicio']; ?>"><?php echo formatearFecha($proyecto['fecha_inicio']); ?></p>
                </td>
                <td>
                  <p class="text-xs mb-0 fecha-fin" data-fecha="<?php echo $proyecto['fecha_fin']; ?>"><?php echo formatearFecha($proyecto['fecha_fin']); ?></p>
                </td>
                <td>
                  <a href="javascript:;" class="text-secondary" data-toggle="tooltip" title="Ver detalles">
                    <i class="fas fa-eye fa-sm"></i>
                  </a>
                </td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
        <div class="card-footer p-2 d-flex justify-content-between align-items-center">
          <small class="text-muted">Mostrando <span id="contadorRegistros"><?php echo count($proyectosEntidades); ?></span> de <?php echo count($proyectosEntidades); ?> proyectos</small>
          <button id="btnLimpiarFiltros" class="btn btn-outline-secondary btn-xs">Limpiar filtros</button>
        </div>
      </div>
    </div>
  </div>
</div>



<!-- Script específico para esta página -->
<script>
document.addEventListener("DOMContentLoaded", function() {
    var ctx = document.getElementById("chart-years").getContext("2d");
    
    // Crear gradientes para el área bajo la línea
    var gradientFill = ctx.createLinearGradient(0, 400, 0, 50);
    gradientFill.addColorStop(0, "rgba(204, 102, 40, 0)");
    gradientFill.addColorStop(1, "rgba(204, 102, 40, 0.3)");
    
    // Datos de la gráfica (obtenidos de PHP)
    var anios = <?php echo $aniosJSON; ?>;
    var valores = <?php echo $valoresJSON; ?>;
    
    // Formatear valores para mostrar en miles o millones
    var tooltipValores = valores.map(function(valor) {
        return new Intl.NumberFormat('es-CO', { 
            style: 'currency', 
            currency: 'COP',
            maximumFractionDigits: 0 
        }).format(valor);
    });
    
    // Configurar la gráfica
    new Chart(ctx, {
        type: "line",
        data: {
            labels: anios,
            datasets: [{
                label: "Valor Suscrito",
                tension: 0.4, // Curvatura de la línea
                borderWidth: 3,
                pointRadius: 4,
                pointBackgroundColor: "#CC6628",
                pointBorderColor: "#FFF",
                pointBorderWidth: 2,
                pointHoverRadius: 6,
                pointHoverBorderWidth: 2,
                pointHoverBackgroundColor: "#fff",
                pointHoverBorderColor: "#CC6628",
                backgroundColor: gradientFill,
                borderColor: "#CC6628",
                fill: true,
                data: valores
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
                },
                tooltip: {
                    backgroundColor: "rgba(0, 0, 0, 0.7)",
                    titleColor: "#fff",
                    bodyColor: "#fff",
                    bodyFont: {
                        size: 14
                    },
                    titleFont: {
                        size: 16,
                        weight: 'bold'
                    },
                    padding: 12,
                    cornerRadius: 8,
                    caretPadding: 10,
                    caretSize: 8,
                    callbacks: {
                        label: function(context) {
                            return tooltipValores[context.dataIndex];
                        }
                    }
                }
            },
            interaction: {
                intersect: false,
                mode: 'index',
            },
            scales: {
                y: {
                    grid: {
                        drawBorder: false,
                        display: true,
                        drawOnChartArea: true,
                        drawTicks: false,
                        borderDash: [5, 5],
                        color: 'rgba(0, 0, 0, 0.05)'
                    },
                    ticks: {
                        display: true,
                        padding: 10,
                        color: '#666',
                        font: {
                            size: 12,
                            family: "'Open Sans', sans-serif",
                        },
                        callback: function(value) {
                            // Formatear los valores del eje Y
                            if (value >= 1000000) {
                                return '$' + (value / 1000000).toFixed(1) + 'M';
                            } else if (value >= 1000) {
                                return '$' + (value / 1000).toFixed(1) + 'K';
                            } else {
                                return '$' + value;
                            }
                        }
                    }
                },
                x: {
                    grid: {
                        drawBorder: false,
                        display: false,
                        drawOnChartArea: false,
                        drawTicks: false,
                    },
                    ticks: {
                        display: true,
                        padding: 10,
                        color: '#666',
                        font: {
                            size: 12,
                            family: "'Open Sans', sans-serif",
                        }
                    }
                },
            },
            elements: {
                line: {
                    tension: 0.4, // Hace la línea más curva
                    borderWidth: 3
                }
            }
        },
    });
});
</script>

<!-- Script para la nueva gráfica de barras -->
<script>
document.addEventListener("DOMContentLoaded", function() {
    var ctx2 = document.getElementById("chart-projects").getContext("2d");
    
    // Crear gradientes para las barras
    var gradientBar = ctx2.createLinearGradient(0, 0, 0, 350);
    gradientBar.addColorStop(0, "rgba(88, 103, 195, 0.9)");
    gradientBar.addColorStop(1, "rgba(88, 103, 195, 0.4)");
    
    // Datos de la gráfica (obtenidos de PHP)
    var anios = <?php echo $aniosProyectosJSON; ?>;
    var cantidades = <?php echo $cantidadesJSON; ?>;
    
    // Configurar la gráfica
    new Chart(ctx2, {
        type: "bar",
        data: {
            labels: anios,
            datasets: [{
                label: "Proyectos Suscritos",
                backgroundColor: gradientBar,
                borderColor: "rgba(88, 103, 195, 1)",
                borderWidth: 1,
                borderRadius: 6,
                maxBarThickness: 35,
                data: cantidades
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
                },
                tooltip: {
                    backgroundColor: "rgba(0, 0, 0, 0.7)",
                    titleColor: "#fff",
                    bodyColor: "#fff",
                    bodyFont: {
                        size: 14
                    },
                    titleFont: {
                        size: 16,
                        weight: 'bold'
                    },
                    padding: 12,
                    cornerRadius: 8,
                    caretPadding: 10,
                    caretSize: 8,
                    callbacks: {
                        label: function(context) {
                            return context.dataset.label + ": " + context.raw + " proyectos";
                        }
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    grid: {
                        drawBorder: false,
                        display: true,
                        drawOnChartArea: true,
                        drawTicks: false,
                        borderDash: [5, 5],
                        color: 'rgba(0, 0, 0, 0.05)'
                    },
                    ticks: {
                        display: true,
                        padding: 10,
                        color: '#666',
                        font: {
                            size: 12,
                            family: "'Open Sans', sans-serif",
                        },
                        stepSize: 5
                    }
                },
                x: {
                    grid: {
                        drawBorder: false,
                        display: false,
                        drawOnChartArea: false,
                        drawTicks: false,
                    },
                    ticks: {
                        display: true,
                        padding: 10,
                        color: '#666',
                        font: {
                            size: 12,
                            family: "'Open Sans', sans-serif",
                        }
                    }
                },
            },
            animation: {
                duration: 1500
            }
        },
    });
});
</script>
<!-- Script para filtrado de la tabla -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Referencias a los elementos de filtro
    const filtroProyecto = document.getElementById('filtroProyecto');
    const filtroEntidad = document.getElementById('filtroEntidad');
    const filtroFechaInicio = document.getElementById('filtroFechaInicio');
    const filtroFechaFin = document.getElementById('filtroFechaFin');
    const btnLimpiarFiltros = document.getElementById('btnLimpiarFiltros');
    const contadorRegistros = document.getElementById('contadorRegistros');
    
    // Función para aplicar filtros
    function aplicarFiltros() {
        const filas = document.querySelectorAll('.fila-proyecto');
        let contador = 0;
        
        filas.forEach(fila => {
            const nombreProyecto = fila.querySelector('.nombre-proyecto').textContent.toLowerCase();
            const nombreEntidad = fila.querySelector('.nombre-entidad').textContent.toLowerCase();
            const fechaInicioEl = fila.querySelector('.fecha-inicio');
            const fechaFinEl = fila.querySelector('.fecha-fin');
            
            const fechaInicio = fechaInicioEl ? fechaInicioEl.getAttribute('data-fecha') : '';
            const fechaFin = fechaFinEl ? fechaFinEl.getAttribute('data-fecha') : '';
            
            // Aplicar filtros
            const filtroProyectoValor = filtroProyecto.value.toLowerCase();
            const filtroEntidadValor = filtroEntidad.value.toLowerCase();
            const filtroFechaInicioValor = filtroFechaInicio.value;
            const filtroFechaFinValor = filtroFechaFin.value;
            
            let mostrar = true;
            
            if (filtroProyectoValor && !nombreProyecto.includes(filtroProyectoValor)) {
                mostrar = false;
            }
            
            if (filtroEntidadValor && !nombreEntidad.includes(filtroEntidadValor)) {
                mostrar = false;
            }
            
            if (filtroFechaInicioValor && fechaInicio && fechaInicio < filtroFechaInicioValor) {
                mostrar = false;
            }
            
            if (filtroFechaFinValor && fechaFin && fechaFin > filtroFechaFinValor) {
                mostrar = false;
            }
            
            // Mostrar u ocultar fila
            if (mostrar) {
                fila.style.display = '';
                contador++;
            } else {
                fila.style.display = 'none';
            }
        });
        
        // Actualizar contador
        contadorRegistros.textContent = contador;
    }
    
    // Registrar eventos de filtro
    filtroProyecto.addEventListener('input', aplicarFiltros);
    filtroEntidad.addEventListener('input', aplicarFiltros);
    filtroFechaInicio.addEventListener('change', aplicarFiltros);
    filtroFechaFin.addEventListener('change', aplicarFiltros);
    
    // Limpiar filtros
    btnLimpiarFiltros.addEventListener('click', function() {
        filtroProyecto.value = '';
        filtroEntidad.value = '';
        filtroFechaInicio.value = '';
        filtroFechaFin.value = '';
        aplicarFiltros();
    });
});
</script>