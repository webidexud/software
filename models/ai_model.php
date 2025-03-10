<?php
/**
 * Funciones para el procesamiento de documentos con extracción de información
 */

// Incluir el procesador de documentos
require_once __DIR__ . '/document_processor/DocumentProcessor.php';

/**
 * Procesa un documento para extraer información
 * @param string $filePath Ruta al archivo a procesar
 * @return array Datos extraídos del documento
 */
function procesarDocumentoConIA($filePath) {
    try {
        // Verificar que el archivo existe
        if (!file_exists($filePath)) {
            throw new Exception("El archivo no existe");
        }
        
        // Crear instancia del procesador de documentos
        $processor = new DocumentProcessor();
        
        // Procesar el documento
        $resultado = $processor->processDocument($filePath);
        
        if (isset($resultado['error'])) {
            throw new Exception($resultado['error']);
        }
        
        return $resultado;
    } catch (Exception $e) {
        error_log("Error en procesarDocumentoConIA: " . $e->getMessage());
        return array('error' => $e->getMessage());
    }
}

function buscarEntidadPorNombre($nombreEntidad) {
    try {
        // Obtener conexión
        $conn = conectarOracle();
        
        // Primero intentar una búsqueda exacta
        $sql = "SELECT CODIGO FROM ENTIDAD WHERE UPPER(DESCRIPCION) = UPPER(:nombre)";
        $stmt = oci_parse($conn, $sql);
        oci_bind_by_name($stmt, ':nombre', $nombreEntidad);
        $r = oci_execute($stmt);
        
        $row = oci_fetch_assoc($stmt);
        if ($row) {
            oci_free_statement($stmt);
            oci_close($conn);
            return intval($row['CODIGO']);
        }
        
        // Si no hay coincidencia exacta, intentar búsqueda parcial
        oci_free_statement($stmt);
        
        // Intentar búsqueda por palabra clave
        $palabras = explode(" ", $nombreEntidad);
        
        // Filtrar palabras muy cortas o comunes
        $palabras = array_filter($palabras, function($palabra) {
            return strlen($palabra) > 3 && !in_array(strtolower($palabra), ['para', 'entre', 'desde', 'hasta', 'como', 'donde', 'cuando', 'porque', 'entonces', 'aunque']);
        });
        
        if (count($palabras) > 0) {
            $condiciones = [];
            foreach ($palabras as $palabra) {
                $condiciones[] = "UPPER(DESCRIPCION) LIKE UPPER('%' || :palabra$i || '%')";
                $i++;
            }
            
            $sql = "SELECT CODIGO FROM ENTIDAD WHERE " . implode(" OR ", $condiciones);
            $stmt = oci_parse($conn, $sql);
            
            $i = 0;
            foreach ($palabras as $palabra) {
                oci_bind_by_name($stmt, ":palabra$i", $palabra);
                $i++;
            }
            
            $r = oci_execute($stmt);
            $row = oci_fetch_assoc($stmt);
            
            oci_free_statement($stmt);
            oci_close($conn);
            
            return ($row) ? intval($row['CODIGO']) : 0;
        }
        
        oci_close($conn);
        return 0;
        
    } catch (Exception $e) {
        error_log("Error en buscarEntidadPorNombre: " . $e->getMessage());
        return 0;
    }
}
?>