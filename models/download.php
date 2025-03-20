
<?php
// Archivo: download.php - Para gestionar la descarga de documentos

// Verificar que se haya proporcionado un nombre de archivo
if (!isset($_GET['file']) || empty($_GET['file'])) {
    header('HTTP/1.0 404 Not Found');
    exit('Archivo no especificado');
}

// Obtener el nombre del archivo
$filename = basename($_GET['file']);

// Ruta al directorio de documentos (ajusta esto según tu estructura)
$file_path = 'documents/' . $filename;

// Verificar que el archivo existe y es legible
if (!file_exists($file_path) || !is_readable($file_path)) {
    header('HTTP/1.0 404 Not Found');
    exit('Archivo no encontrado o inaccesible');
}

// Obtener información del archivo
$file_size = filesize($file_path);
$file_extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

// Establecer el tipo MIME adecuado según la extensión
switch ($file_extension) {
    case 'pdf':
        $content_type = 'application/pdf';
        break;
    case 'doc':
        $content_type = 'application/msword';
        break;
    case 'docx':
        $content_type = 'application/vnd.openxmlformats-officedocument.wordprocessingml.document';
        break;
    case 'xls':
        $content_type = 'application/vnd.ms-excel';
        break;
    case 'xlsx':
        $content_type = 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet';
        break;
    case 'jpg':
    case 'jpeg':
        $content_type = 'image/jpeg';
        break;
    case 'png':
        $content_type = 'image/png';
        break;
    default:
        $content_type = 'application/octet-stream';
}

// Preparar cabeceras HTTP para la descarga
header('Content-Description: File Transfer');
header('Content-Type: ' . $content_type);
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Content-Transfer-Encoding: binary');
header('Content-Length: ' . $file_size);
header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
header('Expires: 0');
header('Pragma: public');

// Limpiar el buffer de salida
ob_clean();
flush();

// Leer y enviar el archivo
readfile($file_path);
exit;
?>