<?php
/**
 * Funciones específicas para el manejo de proyectos individuales
 * 
 * Este archivo contiene funciones específicas para obtener información
 * adicional o realizar cálculos sobre proyectos individuales
 */

// Incluir el archivo de conexión si no está incluido
if (!function_exists('conectarOracle')) {
    require_once 'config/database.php';
}

/**
 * Calcula la duración de un proyecto en años, meses y días
 * 
 * @param string $fecha_inicio Fecha de inicio del proyecto (YYYY-MM-DD)
 * @param string $fecha_termina Fecha de finalización del proyecto (YYYY-MM-DD)
 * @return array Array con anio, mes, dia y total de días de duración
 */
function calcularDuracionProyecto($fecha_inicio, $fecha_termina) {
    try {
        // Convertir fechas a objetos DateTime
        $inicio = new DateTime($fecha_inicio);
        $fin = new DateTime($fecha_termina);
        
        // Calcular la diferencia entre fechas
        $diff = $inicio->diff($fin);
        
        // Calcular el total de días
        $total_dias = $diff->days;
        
        // Obtener años, meses y días
        $anios = $diff->y;
        $meses = $diff->m;
        $dias = $diff->d;
        
        // Devolver los resultados con nombres de claves que coincidan con las columnas de la BD
        return array(
            'anios' => $anios,      // Se seguirá usando para mostrar en la interfaz
            'meses' => $meses,      // Se seguirá usando para mostrar en la interfaz
            'dias' => $dias,        // Se seguirá usando para mostrar en la interfaz
            'total_dias' => $total_dias,
            'texto' => formatearDuracionTexto($anios, $meses, $dias),
            // Estos son los que se usarán para la inserción en la BD:
            'anio' => $anios,      // Para la columna ANIO
            'mes' => $meses,       // Para la columna MES
            'dia' => $dias         // Para la columna DIA
        );
    } catch (Exception $e) {
        error_log("Error al calcular duración del proyecto: " . $e->getMessage());
        return array(
            'anios' => 0,
            'meses' => 0,
            'dias' => 0,
            'total_dias' => 0,
            'texto' => 'No disponible',
            'anio' => 0,      // Para la columna ANIO
            'mes' => 0,       // Para la columna MES
            'dia' => 0        // Para la columna DIA
        );
    }
}

/**
 * Formatea la duración como texto legible
 * 
 * @param int $anios Años de duración
 * @param int $meses Meses de duración
 * @param int $dias Días de duración
 * @return string Texto formateado con la duración
 */
function formatearDuracionTexto($anios, $meses, $dias) {
    $partes = array();
    
    if ($anios > 0) {
        $partes[] = $anios . ' ' . ($anios == 1 ? 'año' : 'años');
    }
    
    if ($meses > 0) {
        $partes[] = $meses . ' ' . ($meses == 1 ? 'mes' : 'meses');
    }
    
    if ($dias > 0) {
        $partes[] = $dias . ' ' . ($dias == 1 ? 'día' : 'días');
    }
    
    if (empty($partes)) {
        return '0 días';
    }
    
    return implode(', ', $partes);
}

/**
 * Actualiza los campos de duración en la tabla PROYECTO
 * 
 * @param int $proyecto_id ID del proyecto
 * @param array $duracion Array con años, meses y días de duración
 * @return bool True si se actualizó correctamente, False en caso contrario
 */
function actualizarDuracionProyecto($proyecto_id, $duracion) {
    try {
        // Obtener conexión
        $conn = conectarOracle();
        
        // Preparar la consulta SQL
        $sql = "UPDATE PROYECTO 
                SET ANIO = :anio, 
                    MES = :mes, 
                    DIA = :dia 
                WHERE NUMERO_PRO = :id";
        
        // Preparar statement
        $stmt = oci_parse($conn, $sql);
        
        // Vincular parámetros
        oci_bind_by_name($stmt, ':anio', $duracion['anio']);
        oci_bind_by_name($stmt, ':mes', $duracion['mes']);
        oci_bind_by_name($stmt, ':dia', $duracion['dia']);
        oci_bind_by_name($stmt, ':id', $proyecto_id);
        
        // Ejecutar la consulta
        $resultado = oci_execute($stmt);
        
        // Liberar recursos
        oci_free_statement($stmt);
        oci_close($conn);
        
        return $resultado;
        
    } catch (Exception $e) {
        error_log("Error al actualizar duración del proyecto: " . $e->getMessage());
        return false;
    }
}
?>