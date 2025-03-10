<?php
/**
 * Página de gestión de proyectos
 * 
 * Esta página muestra la lista de proyectos activos con filtros y opciones de gestión
 */

// Verificar si el archivo modelo existe
if (file_exists('models/proyecto_model.php')) {
    // Incluir el modelo de proyecto
    include_once 'models/proyecto_model.php';
    
    // Obtener todos los proyectos
    $proyectos = function_exists('obtenerTodosProyectos') ? obtenerTodosProyectos() : [];
    
    // Obtener listas para filtros
    $anios = function_exists('obtenerListaAnios') ? obtenerListaAnios() : [];
    $entidades = function_exists('obtenerListaEntidades') ? obtenerListaEntidades() : [];
    $situaciones = function_exists('obtenerListaSituaciones') ? obtenerListaSituaciones() : [];
} else {
    // Si no encuentra el modelo, usar datos de ejemplo
    $proyectos = [
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
    
    // Crear listas de ejemplo para filtros
    $anios = [2022, 2023, 2024];
    $entidades = ['Ministerio de Educación Nacional', 'Secretaría de Educación Distrital', 'Instituto de Desarrollo Urbano', 'Secretaría Distrital de Ambiente', 'Alcaldía Mayor de Bogotá'];
    $situaciones = ['En ejecución', 'Finalizado', 'Suscrito'];
}
?>

<!-- Estilos adicionales específicos para esta página -->
<style>
.card {
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
    border-radius: 10px;
    border: none;
    margin-bottom: 24px;
    overflow: hidden;
}
.card-header {
    background-color: #f8f9fa;
    border-bottom: 1px solid rgba(0, 0, 0, 0.05);
    padding: 15px 20px;
}
.btn-add-entity {
    transition: all 0.3s;
    font-weight: 600;
    letter-spacing: 0.2px;
}
.btn-add-entity:hover {
    transform: translateY(-3px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
}
.filter-form label {
    font-size: 0.75rem;
    font-weight: 600;
    color: #67748e;
    margin-bottom: 0.3rem;
}
.action-button {
    transition: all 0.2s;
    margin: 0 2px;
    padding: 0.4rem 0.65rem;
    font-size: 0.75rem;
    font-weight: 500;
}
.action-button:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
}
.action-button i {
    margin-right: 3px;
}
.table-container {
    border-radius: 8px;
    border: 1px solid #f0f0f0;
    margin-top: 6px;
}
.table {
    table-layout: fixed;
    width: 100%;
    margin-bottom: 0;
}
.table thead th {
    border-top: none;
    font-size: 0.65rem;
    text-transform: uppercase;
    font-weight: 700;
    color: #8392AB;
    padding-top: 0.75rem;
    padding-bottom: 0.75rem;
    position: sticky;
    top: 0;
    background-color: #f8f9fa;
    z-index: 10;
    vertical-align: middle;
    border-bottom: 2px solid #e9ecef;
}
.table td {
    font-size: 0.75rem;
    padding: 0.65rem 0.65rem;
    vertical-align: middle;
}
.status-badge {
    font-size: 0.65rem;
    padding: 0.3rem 0.5rem;
    border-radius: 6px;
    font-weight: 600;
    white-space: nowrap;
    display: inline-block;
    min-width: 80px;
    text-align: center;
}
.status-ejecucion {
    background-color: rgba(66, 135, 245, 0.15);
    color: #4287f5;
}
.status-finalizado {
    background-color: rgba(46, 204, 113, 0.15);
    color: #2ecc71;
}
.status-suscrito {
    background-color: rgba(243, 156, 18, 0.15);
    color: #f39c12;
}
.text-wrap {
    white-space: normal;
    word-break: break-word;
    hyphens: auto;
}
.page-header {
    background-color: #fff;
    padding: 20px;
    border-radius: 10px;
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.05);
    margin-bottom: 24px;
}
.proyecto-row:hover {
    background-color: rgba(94, 114, 228, 0.05);
}
.form-control, .btn {
    box-shadow: none !important;
}
.form-control:focus {
    border-color: #5e72e4;
}
.form-select, .form-control {
    font-size: 0.875rem;
    padding: 0.5rem 0.75rem;
}
.btn-filter {
    background-color: #5e72e4;
    color: white;
    border: none;
    font-weight: 600;
    transition: all 0.2s;
}
.btn-filter:hover {
    background-color: #4a5cd0;
    transform: translateY(-2px);
}
.btn-reset {
    color: #344767;
    border: 1px solid #d1d9e6;
    background-color: white;
    font-weight: 600;
    transition: all 0.2s;
}
.btn-reset:hover {
    background-color: #f8f9fa;
}
</style>

<!-- Sección de Encabezado y Botón de Agregar -->
<div class="page-header">
    <div class="row align-items-center">
        <div class="col-lg-8">
            <h4 class="font-weight-bolder mb-0">Gestión de Proyectos</h4>
            <p class="text-sm mb-0">
                Administre todos los proyectos activos de la plataforma
            </p>
        </div>
        <div class="col-lg-4 text-end">
        <a href="main.php?page=crear_proyecto" class="btn btn-primary btn-add-entity">
            <i class="fas fa-plus me-2"></i> Nuevo Proyecto
        </a>
        </div>
    </div>
</div>

<!-- Sección de Filtros -->
<div class="row mb-4">
    <div class="col-12">
        <div class="card">
            <div class="card-header p-3">
                <h6 class="mb-0">Filtros de Búsqueda</h6>
            </div>
            <div class="card-body p-3">
                <form class="filter-form" id="filtroProyectos">
                    <div class="row g-3">
                        <div class="col-md-2">
                            <div class="form-group">
                                <label for="filtroAnio">Año</label>
                                <select class="form-select form-select-sm" id="filtroAnio">
                                    <option value="">Todos</option>
                                    <?php foreach($anios as $anio): ?>
                                    <option value="<?php echo $anio; ?>"><?php echo $anio; ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="form-group">
                                <label for="filtroNumero">Número</label>
                                <input type="text" class="form-control form-control-sm" id="filtroNumero" placeholder="Buscar...">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label for="filtroNombre">Nombre del Proyecto</label>
                                <input type="text" class="form-control form-control-sm" id="filtroNombre" placeholder="Buscar...">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label for="filtroEntidad">Entidad Contratante</label>
                                <select class="form-select form-select-sm" id="filtroEntidad">
                                    <option value="">Todas</option>
                                    <?php foreach($entidades as $entidad): ?>
                                    <option value="<?php echo $entidad; ?>"><?php echo $entidad; ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="form-group">
                                <label for="filtroSituacion">Situación</label>
                                <select class="form-select form-select-sm" id="filtroSituacion">
                                    <option value="">Todas</option>
                                    <?php foreach($situaciones as $situacion): ?>
                                    <option value="<?php echo $situacion; ?>"><?php echo $situacion; ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="row mt-3">
                        <div class="col-md-12 text-end">
                            <button type="reset" class="btn btn-sm btn-reset me-2">
                                <i class="fas fa-eraser me-1"></i> Limpiar
                            </button>
                            <button type="button" id="btnFiltrar" class="btn btn-sm btn-filter">
                                <i class="fas fa-filter me-1"></i> Filtrar
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Sección de Tabla de Proyectos -->
<div class="row">
    <div class="col-12">
        <div class="card mb-4">
            <div class="card-header p-3">
                <div class="row">
                    <div class="col-md-6">
                        <h6 class="mb-0">Listado de Proyectos</h6>
                    </div>
                    <div class="col-md-6 text-end">
                        <span class="text-xs">Mostrando <span id="contador-registros">0</span> de <?php echo count($proyectos); ?> proyectos</span>
                    </div>
                </div>
            </div>
            <div class="card-body px-0 pt-0 pb-0">
                <div class="table-container">
                    <div class="table-responsive" style="max-height: 500px; overflow-y: auto;">
                        <table class="table table-striped table-hover align-items-center mb-0" id="tablaProyectos">
                            <thead class="bg-light sticky-top">
                                <tr>
                                    <th class="text-center px-2" style="width: 8%;">Año</th>
                                    <th class="px-2" style="width: 12%;">Número</th>
                                    <th class="px-2" style="width: 35%;">Nombre del Proyecto</th>
                                    <th class="px-2" style="width: 25%;">Entidad Contratante</th>
                                    <th class="text-center px-2" style="width: 10%;">Situación</th>
                                    <th class="text-center px-2" style="width: 10%;">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if(empty($proyectos)): ?>
                                <tr>
                                    <td colspan="6" class="text-center py-3">No se encontraron proyectos</td>
                                </tr>
                                <?php else: ?>
                                <?php foreach($proyectos as $proyecto): ?>
                                <tr class="proyecto-row">
                                    <td class="text-center align-middle">
                                        <span class="text-xs font-weight-bold proyecto-anio"><?php echo $proyecto['anio']; ?></span>
                                    </td>
                                    <td class="align-middle">
                                        <span class="text-xs font-weight-bold proyecto-numero"><?php echo $proyecto['numero']; ?></span>
                                    </td>
                                    <td class="align-middle">
                                        <div class="d-flex px-1">
                                            <div>
                                                <span class="text-xs font-weight-bold proyecto-nombre text-wrap" style="display: inline-block; max-width: 100%;"><?php echo $proyecto['nombre']; ?></span>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="align-middle">
                                        <span class="text-xs font-weight-bold proyecto-entidad text-wrap" style="display: inline-block; max-width: 100%;"><?php echo $proyecto['entidad']; ?></span>
                                    </td>
                                    <td class="align-middle text-center">
                                        <?php 
                                            $statusClass = 'status-suscrito';
                                            if (strtolower($proyecto['situacion']) == 'en ejecución' || strtolower($proyecto['situacion']) == 'en ejecucion') {
                                                $statusClass = 'status-ejecucion';
                                            } elseif (strtolower($proyecto['situacion']) == 'finalizado') {
                                                $statusClass = 'status-finalizado';
                                            }
                                        ?>
                                        <span class="status-badge <?php echo $statusClass; ?> proyecto-situacion"><?php echo $proyecto['situacion']; ?></span>
                                    </td>
                                    <td class="text-center align-middle">
                                    <a href="main.php?page=proyecto_individual&id=<?php echo $proyecto['id']; ?>" class="btn btn-sm btn-primary action-button">
                                        <i class="fas fa-eye"></i> Ver
                                    </a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Script para el filtrado de proyectos -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    const tablaProyectos = document.getElementById('tablaProyectos');
    const filasProyectos = document.querySelectorAll('.proyecto-row');
    const contadorRegistros = document.getElementById('contador-registros');
    const btnFiltrar = document.getElementById('btnFiltrar');
    const filtroForm = document.getElementById('filtroProyectos');
    
    // Establecer el contador inicial
    contadorRegistros.textContent = filasProyectos.length;
    
    // Función para aplicar filtros
    function aplicarFiltros() {
        const filtroAnio = document.getElementById('filtroAnio').value.toLowerCase();
        const filtroNumero = document.getElementById('filtroNumero').value.toLowerCase();
        const filtroNombre = document.getElementById('filtroNombre').value.toLowerCase();
        const filtroEntidad = document.getElementById('filtroEntidad').value.toLowerCase();
        const filtroSituacion = document.getElementById('filtroSituacion').value.toLowerCase();
        
        let contadorVisible = 0;
        
        filasProyectos.forEach(fila => {
            const anio = fila.querySelector('.proyecto-anio').textContent.toLowerCase();
            const numero = fila.querySelector('.proyecto-numero').textContent.toLowerCase();
            const nombre = fila.querySelector('.proyecto-nombre').textContent.toLowerCase();
            const entidad = fila.querySelector('.proyecto-entidad').textContent.toLowerCase();
            const situacion = fila.querySelector('.proyecto-situacion').textContent.toLowerCase();
            
            // Verificar si la fila cumple con todos los filtros
            const coincideAnio = filtroAnio === '' || anio.includes(filtroAnio);
            const coincideNumero = filtroNumero === '' || numero.includes(filtroNumero);
            const coincideNombre = filtroNombre === '' || nombre.includes(filtroNombre);
            const coincideEntidad = filtroEntidad === '' || entidad.includes(filtroEntidad);
            const coincideSituacion = filtroSituacion === '' || situacion.includes(filtroSituacion);
            
            // Si cumple con todos los filtros, mostrar la fila
            if (coincideAnio && coincideNumero && coincideNombre && coincideEntidad && coincideSituacion) {
                fila.style.display = 'table-row';
                contadorVisible++;
            } else {
                fila.style.display = 'none';
            }
        });
        
        // Actualizar contador
        contadorRegistros.textContent = contadorVisible;
    }
    
    // Evento de clic para el botón filtrar
    btnFiltrar.addEventListener('click', aplicarFiltros);
    
    // Evento para limpiar filtros
    filtroForm.addEventListener('reset', function() {
        // Dar tiempo a que el formulario se reinicie
        setTimeout(function() {
            aplicarFiltros();
        }, 10);
    });
    
    // Aplicar filtros al cargar la página
    aplicarFiltros();
});
</script>