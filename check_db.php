<?php
/**
 * check_db.php
 * 
 * Este es un script de diagnóstico simple para verificar si la conexión a la base de datos
 * se puede establecer correctamente utilizando las credenciales de `db_config.php`.
 * Devuelve un mensaje de éxito o error en formato HTML.
 */

header('Content-Type: text/html; charset=utf-8');
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/db_config.php';
echo "<h1>Prueba de Conexión a la Base de Datos</h1>";

// Intentar conectar
$conn = new mysqli($servername, $username, $password, $dbname);

// Comprobar conexión y mostrar resultado
if ($conn->connect_error) {
    echo "<p style='color: red; font-weight: bold;'>Error al conectar a la base de datos: </p>";
    echo "<p>" . $conn->connect_error . "</p>";
} else {
    echo "<p style='color: green; font-weight: bold;'>¡Conexión a la base de datos '" . $dbname . "' fue exitosa!</p>";
    $conn->close();
}
?>