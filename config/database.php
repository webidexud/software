<?php
// Configuración de conexión a Oracle
$host = "200.69.103.17";
$puerto = "1521";
$sid = "xe";
$usuario = "INGE1";
$password = "IdexUD.Contreras";

// Cadena de conexión TNS
$tns = "(DESCRIPTION =
    (ADDRESS_LIST =
      (ADDRESS = (PROTOCOL = TCP)(HOST = $host)(PORT = $puerto))
    )
    (CONNECT_DATA =
      (SID = $sid)
    )
  )";

// Función para establecer la conexión
function conectarOracle() {
  global $usuario, $password, $tns;
  
  // Intentar conectar con tiempo de espera
  $conn = null;
  $intentos = 0;
  $max_intentos = 3;
  
  while ($intentos < $max_intentos && !$conn) {
      $conn = @oci_connect($usuario, $password, $tns, 'AL32UTF8');
      if (!$conn) {
          $intentos++;
          if ($intentos < $max_intentos) {
              sleep(2); // Esperar 2 segundos entre intentos
          }
      }
  }
  
  if (!$conn) {
      $e = oci_error();
      // Registrar el error pero mostrar un mensaje más amigable
      error_log('Error de conexión Oracle: ' . $e['message']);
      throw new Exception('Error de conexión a la base de datos. Por favor, intente nuevamente más tarde.');
  }
  
  return $conn;
}