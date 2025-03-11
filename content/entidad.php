<?php
/**
 * Página de gestión de entidades
 * 
 * Esta página permite agregar, editar y eliminar entidades que pueden ser asociadas a proyectos
 */

// Verificar si el archivo modelo existe
if (file_exists('models/entidad_model.php')) {
    // Incluir el modelo de entidad
    include_once 'models/entidad_model.php';
    
    // Obtener todas las entidades
    $entidades = function_exists('obtenerTodasEntidades') ? obtenerTodasEntidades() : [];
} else {
    // Si no encuentra el modelo, usar datos de ejemplo
    $entidades = [
        [
            'codigo' => 1,
            'descripcion' => 'Ministerio de Educación Nacional',
            'orden' => 1,
            'nit' => '899999001-7'
        ],
        [
            'codigo' => 2,
            'descripcion' => 'Secretaría de Educación Distrital',
            'orden' => 3,
            'nit' => '899999061-9'
        ],
        [
            'codigo' => 3,
            'descripcion' => 'Gobernación del Valle',
            'orden' => 2,
            'nit' => '890399029-5'
        ]
    ];
}

// Procesar formulario si se envió
$mensaje = '';
$tipoMensaje = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'agregar' && function_exists('agregarEntidad')) {
        // Recoger datos del formulario
        $descripcion = isset($_POST['descripcion']) ? trim($_POST['descripcion']) : '';
        $orden = isset($_POST['orden']) ? intval($_POST['orden']) : 0;
        $nit = isset($_POST['nit']) ? trim($_POST['nit']) : '';
        $direccion = isset($_POST['direccion']) ? trim($_POST['direccion']) : '';
        $telefono = isset($_POST['telefono']) ? trim($_POST['telefono']) : '';
        $pagina_web = isset($_POST['pagina_web']) ? trim($_POST['pagina_web']) : '';
        $correo = isset($_POST['correo']) ? trim($_POST['correo']) : '';
        
        // Validaciones básicas
        if (empty($descripcion)) {
            $mensaje = 'La descripción de la entidad es obligatoria';
            $tipoMensaje = 'danger';
        } elseif ($orden < 1 || $orden > 6) {
            $mensaje = 'El orden debe estar entre 1 y 6';
            $tipoMensaje = 'danger';
        } else {
            // Intentar agregar la entidad
            $resultado = agregarEntidad($descripcion, $orden, $nit, $direccion, $telefono, $pagina_web, $correo);
            
            if ($resultado) {
                $mensaje = 'Entidad agregada correctamente';
                $tipoMensaje = 'success';
                
                // Recargar la lista de entidades
                $entidades = obtenerTodasEntidades();
            } else {
                $mensaje = 'Error al agregar la entidad';
                $tipoMensaje = 'danger';
            }
        }
    } elseif ($_POST['action'] === 'editar' && function_exists('actualizarEntidad')) {
        // Proceso similar para editar
        $codigo = isset($_POST['codigo']) ? intval($_POST['codigo']) : 0;
        $descripcion = isset($_POST['descripcion']) ? trim($_POST['descripcion']) : '';
        $orden = isset($_POST['orden']) ? intval($_POST['orden']) : 0;
        $nit = isset($_POST['nit']) ? trim($_POST['nit']) : '';
        $direccion = isset($_POST['direccion']) ? trim($_POST['direccion']) : '';
        $telefono = isset($_POST['telefono']) ? trim($_POST['telefono']) : '';
        $pagina_web = isset($_POST['pagina_web']) ? trim($_POST['pagina_web']) : '';
        $correo = isset($_POST['correo']) ? trim($_POST['correo']) : '';
        
        if ($codigo <= 0) {
            $mensaje = 'Código de entidad inválido';
            $tipoMensaje = 'danger';
        } elseif (empty($descripcion)) {
            $mensaje = 'La descripción de la entidad es obligatoria';
            $tipoMensaje = 'danger';
        } elseif ($orden < 1 || $orden > 6) {
            $mensaje = 'El orden debe estar entre 1 y 6';
            $tipoMensaje = 'danger';
        } else {
            $resultado = actualizarEntidad($codigo, $descripcion, $orden, $nit, $direccion, $telefono, $pagina_web, $correo);
            
            if ($resultado) {
                $mensaje = 'Entidad actualizada correctamente';
                $tipoMensaje = 'success';
                $entidades = obtenerTodasEntidades();
            } else {
                $mensaje = 'Error al actualizar la entidad';
                $tipoMensaje = 'danger';
            }
        }
    } elseif ($_POST['action'] === 'eliminar' && function_exists('eliminarEntidad')) {
        $codigo = isset($_POST['codigo']) ? intval($_POST['codigo']) : 0;
        
        if ($codigo <= 0) {
            $mensaje = 'Código de entidad inválido';
            $tipoMensaje = 'danger';
        } else {
            $resultado = eliminarEntidad($codigo);
            
            if ($resultado) {
                $mensaje = 'Entidad eliminada correctamente';
                $tipoMensaje = 'success';
                $entidades = obtenerTodasEntidades();
            } else {
                $mensaje = 'Error al eliminar la entidad. Puede estar en uso en proyectos existentes.';
                $tipoMensaje = 'danger';
            }
        }
    } elseif ($_POST['action'] === 'ver' && isset($_POST['codigo']) && function_exists('obtenerEntidadPorCodigo')) {
        // Esta acción se procesa con JavaScript, no aquí
    }
}

// Cargar entidad para editar si se solicita
$entidadEditar = null;
if (isset($_GET['editar']) && is_numeric($_GET['editar']) && function_exists('obtenerEntidadPorCodigo')) {
    $entidadEditar = obtenerEntidadPorCodigo(intval($_GET['editar']));
}

// Cargar entidad para ver si se solicita
$entidadVer = null;
if (isset($_GET['ver']) && is_numeric($_GET['ver']) && function_exists('obtenerEntidadPorCodigo')) {
    $entidadVer = obtenerEntidadPorCodigo(intval($_GET['ver']));
}

// Función para obtener el nombre de orden según el valor
function getNombreOrden($orden) {
    $nombres = [
        1 => 'NACIONAL',
        2 => 'DEPARTAMENTAL',
        3 => 'DISTRITAL',
        4 => 'MUNICIPAL',
        5 => 'PRIVADO',
        6 => 'INTERNACIONAL'
    ];
    
    return isset($nombres[$orden]) ? $nombres[$orden] : '';
}
?>

<!-- Estilos adicionales específicos para esta página -->
<style>
.card {
    box-shadow: 0 0 10px rgba(0, 0, 0, 0.05);
    margin-bottom: 24px;
}
.form-card {
    border-top: 3px solid #5e72e4;
}
.table-card {
    border-top: 3px solid #11cdef;
}
.table thead th {
    font-size: 0.75rem;
    text-transform: uppercase;
    font-weight: 700;
    color: #8392AB;
    background-color: #f8f9fa;
    position: sticky;
    top: 0;
    z-index: 10;
}
.table-container {
    border-radius: 0.5rem;
    overflow: hidden;
    border: 1px solid #f0f0f0;
}
.action-button {
    transition: all 0.2s;
    margin: 0 2px;
    white-space: nowrap;
}
.action-button:hover {
    transform: translateY(-1px);
}
.order-badge {
    font-size: 0.65rem;
    padding: 0.25rem 0.5rem;
    border-radius: 0.25rem;
    font-weight: 600;
}
.order-1 {
    background-color: rgba(66, 135, 245, 0.1);
    color: #4287f5;
}
.order-2 {
    background-color: rgba(46, 204, 113, 0.1);
    color: #2ecc71;
}
.order-3 {
    background-color: rgba(155, 89, 182, 0.1);
    color: #9b59b6;
}
.order-4 {
    background-color: rgba(243, 156, 18, 0.1);
    color: #f39c12;
}
.order-5 {
    background-color: rgba(231, 76, 60, 0.1);
    color: #e74c3c;
}
.order-6 {
    background-color: rgba(52, 152, 219, 0.1);
    color: #3498db;
}
.page-description {
    color: #344767;
    background-color: rgba(242, 242, 242, 0.8);
    padding: 8px 15px;
    border-radius: 5px;
    margin-bottom: 15px;
    display: inline-block;
}
</style>

<!-- Sección de Encabezado -->
<div class="row mb-4">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h4 class="font-weight-bolder mb-0">Gestión de Entidades</h4>
                <p class="page-description">
                    <i class="fas fa-info-circle me-1"></i> Administre las entidades que pueden ser asociadas a proyectos
                </p>
            </div>
            <div>
                <a href="main.php?page=proyecto" class="btn btn-outline-primary btn-sm">
                    <i class="fas fa-arrow-left me-2"></i> Volver a Proyectos
                </a>
            </div>
        </div>
    </div>
</div>

<!-- Mensaje de alerta si existe -->
<?php if (!empty($mensaje)): ?>
<div class="row mb-4">
    <div class="col-12">
        <div class="alert alert-<?php echo $tipoMensaje; ?> alert-dismissible fade show" role="alert">
            <?php echo $mensaje; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Formulario de Agregar/Editar -->
<div class="row">
    <div class="col-12">
        <div class="card form-card">
            <div class="card-header p-3">
                <h6 class="mb-0"><?php echo $entidadEditar ? 'Editar Entidad' : 'Agregar Entidad'; ?></h6>
            </div>
            <div class="card-body p-3">
                <form id="entidadForm" method="POST" action="" class="row">
                    <input type="hidden" name="action" value="<?php echo $entidadEditar ? 'editar' : 'agregar'; ?>">
                    <?php if ($entidadEditar): ?>
                    <input type="hidden" name="codigo" value="<?php echo $entidadEditar['codigo']; ?>">
                    <?php endif; ?>
                    
                    <div class="col-md-4 mb-3">
                        <label for="descripcion" class="form-control-label">Nombre de la Entidad *</label>
                        <input type="text" class="form-control" id="descripcion" name="descripcion" required maxlength="255" 
                               placeholder="Nombre de la entidad" value="<?php echo $entidadEditar ? htmlspecialchars($entidadEditar['descripcion']) : ''; ?>">
                        <small class="form-text text-muted">Nombre completo de la entidad</small>
                    </div>
                    
                    <div class="col-md-4 mb-3">
                        <label for="orden" class="form-control-label">Tipo de Entidad *</label>
                        <select class="form-control" id="orden" name="orden" required>
                            <option value="">Seleccione...</option>
                            <option value="1" <?php echo ($entidadEditar && $entidadEditar['orden'] == 1) ? 'selected' : ''; ?>>1 - NACIONAL</option>
                            <option value="2" <?php echo ($entidadEditar && $entidadEditar['orden'] == 2) ? 'selected' : ''; ?>>2 - DEPARTAMENTAL</option>
                            <option value="3" <?php echo ($entidadEditar && $entidadEditar['orden'] == 3) ? 'selected' : ''; ?>>3 - DISTRITAL</option>
                            <option value="4" <?php echo ($entidadEditar && $entidadEditar['orden'] == 4) ? 'selected' : ''; ?>>4 - MUNICIPAL</option>
                            <option value="5" <?php echo ($entidadEditar && $entidadEditar['orden'] == 5) ? 'selected' : ''; ?>>5 - PRIVADO</option>
                            <option value="6" <?php echo ($entidadEditar && $entidadEditar['orden'] == 6) ? 'selected' : ''; ?>>6 - INTERNACIONAL</option>
                        </select>
                        <small class="form-text text-muted">Categoría a la que pertenece la entidad</small>
                    </div>
                    
                    <div class="col-md-4 mb-3">
                        <label for="nit" class="form-control-label">NIT</label>
                        <input type="text" class="form-control" id="nit" name="nit" maxlength="100" 
                               placeholder="NIT de la entidad" value="<?php echo $entidadEditar ? htmlspecialchars($entidadEditar['nit']) : ''; ?>">
                        <small class="form-text text-muted">Número de identificación tributaria</small>
                    </div>
                    
                    <div class="col-md-6 mb-3">
                        <label for="direccion" class="form-control-label">Dirección</label>
                        <input type="text" class="form-control" id="direccion" name="direccion" maxlength="200" 
                               placeholder="Dirección de la entidad" value="<?php echo $entidadEditar && isset($entidadEditar['direccion']) ? htmlspecialchars($entidadEditar['direccion']) : ''; ?>">
                        <small class="form-text text-muted">Ubicación física de la entidad</small>
                    </div>
                    
                    <div class="col-md-6 mb-3">
                        <label for="telefono" class="form-control-label">Teléfono</label>
                        <input type="text" class="form-control" id="telefono" name="telefono" maxlength="100" 
                               placeholder="Teléfono de contacto" value="<?php echo $entidadEditar && isset($entidadEditar['telefono']) ? htmlspecialchars($entidadEditar['telefono']) : ''; ?>">
                        <small class="form-text text-muted">Número telefónico de contacto</small>
                    </div>
                    
                    <div class="col-md-6 mb-3">
                        <label for="pagina_web" class="form-control-label">Página Web</label>
                        <input type="url" class="form-control" id="pagina_web" name="pagina_web" maxlength="200" 
                               placeholder="https://ejemplo.com" value="<?php echo $entidadEditar && isset($entidadEditar['pagina_web']) ? htmlspecialchars($entidadEditar['pagina_web']) : ''; ?>">
                        <small class="form-text text-muted">URL del sitio web de la entidad</small>
                    </div>
                    
                    <div class="col-md-6 mb-3">
                        <label for="correo" class="form-control-label">Correo Electrónico</label>
                        <input type="email" class="form-control" id="correo" name="correo" maxlength="200" 
                               placeholder="contacto@ejemplo.com" value="<?php echo $entidadEditar && isset($entidadEditar['correo']) ? htmlspecialchars($entidadEditar['correo']) : ''; ?>">
                        <small class="form-text text-muted">Dirección de correo electrónico de contacto</small>
                    </div>
                    
                    <div class="col-12 text-end">
                        <?php if ($entidadEditar): ?>
                        <a href="main.php?page=entidad" class="btn btn-outline-secondary">
                            <i class="fas fa-times me-2"></i> Cancelar
                        </a>
                        <?php endif; ?>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-2"></i> <?php echo $entidadEditar ? 'Actualizar' : 'Guardar'; ?>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Tabla de Entidades -->
<div class="row">
    <div class="col-12">
        <div class="card table-card">
            <div class="card-header p-3">
                <div class="row">
                    <div class="col-md-8">
                        <h6 class="mb-0">Listado de Entidades</h6>
                    </div>
                    <div class="col-md-4">
                        <div class="input-group input-group-sm">
                            <span class="input-group-text"><i class="fas fa-search"></i></span>
                            <input type="text" class="form-control" id="buscarEntidad" placeholder="Buscar entidad...">
                        </div>
                    </div>
                </div>
            </div>
            <div class="card-body px-0 pt-0 pb-0">
                <div class="table-container">
                    <div style="max-height: 400px; overflow-y: auto;">
                        <table class="table table-striped table-hover align-items-center mb-0" id="tablaEntidades" style="width: 100%; table-layout: fixed;">
                            <thead class="bg-light sticky-top">
                                <tr>
                                    <th class="text-center" style="width: 8%;">Código</th>
                                    <th style="width: 42%;">Nombre de la Entidad</th>
                                    <th class="text-center" style="width: 15%;">Tipo</th>
                                    <th style="width: 15%;">NIT</th>
                                    <th class="text-center" style="width: 20%;">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if(empty($entidades)): ?>
                                <tr>
                                    <td colspan="5" class="text-center py-3">No se encontraron entidades</td>
                                </tr>
                                <?php else: ?>
                                <?php foreach($entidades as $entidad): ?>
                                <tr class="entidad-row">
                                    <td class="text-center"><?php echo $entidad['codigo']; ?></td>
                                    <td class="entidad-descripcion text-truncate" title="<?php echo htmlspecialchars($entidad['descripcion']); ?>">
                                        <?php echo htmlspecialchars($entidad['descripcion']); ?>
                                    </td>
                                    <td class="text-center">
                                        <span class="order-badge order-<?php echo $entidad['orden']; ?>">
                                            <?php echo getNombreOrden($entidad['orden']); ?>
                                        </span>
                                    </td>
                                    <td class="text-truncate" title="<?php echo htmlspecialchars($entidad['nit'] ?: '-'); ?>">
                                        <?php echo htmlspecialchars($entidad['nit'] ?: '-'); ?>
                                    </td>
                                    <td class="text-center">
                                        <a href="main.php?page=entidad&ver=<?php echo $entidad['codigo']; ?>" class="btn btn-sm btn-success action-button">
                                            <i class="fas fa-eye"></i> Ver
                                        </a>
                                        <a href="main.php?page=entidad&editar=<?php echo $entidad['codigo']; ?>" class="btn btn-sm btn-info action-button">
                                            <i class="fas fa-edit"></i> Editar
                                        </a>
                                        <button type="button" class="btn btn-sm btn-danger action-button" 
                                                onclick="confirmarEliminar(<?php echo $entidad['codigo']; ?>, '<?php echo htmlspecialchars(addslashes($entidad['descripcion'])); ?>')">
                                            <i class="fas fa-trash"></i> Eliminar
                                        </button>
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

<!-- Modal de Confirmación para Eliminar -->
<div class="modal fade" id="eliminarModal" tabindex="-1" aria-labelledby="eliminarModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="eliminarModalLabel">Confirmar Eliminación</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>¿Está seguro que desea eliminar la entidad: <strong id="entidad-a-eliminar"></strong>?</p>
                <p class="text-danger">Esta acción no se puede deshacer.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <form method="POST" action="">
                    <input type="hidden" name="action" value="eliminar">
                    <input type="hidden" name="codigo" id="codigo-eliminar">
                    <button type="submit" class="btn btn-danger">Eliminar</button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Modal de Detalles para Ver -->
<?php if ($entidadVer): ?>
<div class="modal fade" id="verModal" tabindex="-1" aria-labelledby="verModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title" id="verModalLabel">Detalles de la Entidad</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <h6 class="text-muted mb-2">Información General</h6>
                        <table class="table table-sm table-borderless">
                            <tr>
                                <th width="40%">Código:</th>
                                <td><?php echo $entidadVer['codigo']; ?></td>
                            </tr>
                            <tr>
                                <th>Nombre de la Entidad:</th>
                                <td><?php echo htmlspecialchars($entidadVer['descripcion']); ?></td>
                            </tr>
                            <tr>
                                <th>Tipo:</th>
                                <td><?php echo $entidadVer['orden'] . ' - ' . getNombreOrden($entidadVer['orden']); ?></td>
                            </tr>
                            <tr>
                                <th>NIT:</th>
                                <td><?php echo htmlspecialchars($entidadVer['nit'] ?: '-'); ?></td>
                            </tr>
                        </table>
                    </div>
                    <div class="col-md-6 mb-3">
                        <h6 class="text-muted mb-2">Información de Contacto</h6>
                        <table class="table table-sm table-borderless">
                            <tr>
                                <th width="40%">Dirección:</th>
                                <td><?php echo isset($entidadVer['direccion']) ? htmlspecialchars($entidadVer['direccion']) : '-'; ?></td>
                            </tr>
                            <tr>
                                <th>Teléfono:</th>
                                <td><?php echo isset($entidadVer['telefono']) ? htmlspecialchars($entidadVer['telefono']) : '-'; ?></td>
                            </tr>
                            <tr>
                                <th>Página Web:</th>
                                <td>
                                    <?php if (isset($entidadVer['pagina_web']) && $entidadVer['pagina_web']): ?>
                                        <a href="<?php echo strpos($entidadVer['pagina_web'], 'http') === 0 ? htmlspecialchars($entidadVer['pagina_web']) : 'https://' . htmlspecialchars($entidadVer['pagina_web']); ?>" target="_blank">
                                            <?php echo htmlspecialchars($entidadVer['pagina_web']); ?>
                                        </a>
                                    <?php else: ?>
                                        -
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <tr>
                                <th>Correo:</th>
                                <td>
                                    <?php if (isset($entidadVer['correo']) && $entidadVer['correo']): ?>
                                        <a href="mailto:<?php echo htmlspecialchars($entidadVer['correo']); ?>">
                                            <?php echo htmlspecialchars($entidadVer['correo']); ?>
                                        </a>
                                    <?php else: ?>
                                        -
                                    <?php endif; ?>
                                </td>
                            </tr>
                        </table>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <a href="main.php?page=entidad" class="btn btn-secondary">Cerrar</a>
                <a href="main.php?page=entidad&editar=<?php echo $entidadVer['codigo']; ?>" class="btn btn-info">
                    <i class="fas fa-edit"></i> Editar Entidad
                </a>
            </div>
        </div>
    </div>
</div>

<script>
    // Mostrar el modal de ver automáticamente
    document.addEventListener('DOMContentLoaded', function() {
        var verModal = new bootstrap.Modal(document.getElementById('verModal'));
        verModal.show();
    });
</script>
<?php endif; ?>

<!-- Scripts específicos para esta página -->
<script>
    // Función para confirmar eliminación
    function confirmarEliminar(codigo, descripcion) {
        document.getElementById('entidad-a-eliminar').textContent = descripcion;
        document.getElementById('codigo-eliminar').value = codigo;
        var modal = new bootstrap.Modal(document.getElementById('eliminarModal'));
        modal.show();
    }
    
    // Filtro de búsqueda
    document.addEventListener('DOMContentLoaded', function() {
        const buscarEntidadInput = document.getElementById('buscarEntidad');
        if (buscarEntidadInput) {
            buscarEntidadInput.addEventListener('input', function() {
                const busqueda = this.value.toLowerCase();
                const filas = document.querySelectorAll('.entidad-row');
                
                filas.forEach(fila => {
                    const descripcion = fila.querySelector('.entidad-descripcion').textContent.toLowerCase();
                    
                    if (descripcion.includes(busqueda)) {
                        fila.style.display = '';
                    } else {
                        fila.style.display = 'none';
                    }
                });
            });
        }
    });
</script>