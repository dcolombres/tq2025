<?php
/**
 * get_words.php
 * 
 * Este script devuelve una lista de todas las palabras que pertenecen a una subcategoría específica.
 * Recibe el ID de la subcategoría a través de un parámetro GET 'subcategoria_id'.
 * Es utilizado por la página de gestión para mostrar las palabras cuando se selecciona "Gestionar".
 */

header('Content-Type: application/json');
error_reporting(E_ALL);
ini_set('display_errors', 1);

// --- Conexión a la Base de Datos ---
require_once __DIR__ . '/db_config.php';

// --- Lógica Principal ---

// Obtener y validar el ID de la subcategoría desde la URL
$subcategoria_id = isset($_GET['subcategoria_id']) ? (int)$_GET['subcategoria_id'] : 0;

if ($subcategoria_id === 0) {
    http_response_code(400);
    echo json_encode(['error' => 'ID de subcategoría no proporcionado.']);
    exit;
}

// Iniciar la conexión a la BD
$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    http_response_code(500);
    die(json_encode(['error' => 'Error de conexión a la base de datos: ' . $conn->connect_error]));
}
$conn->set_charset("utf8");

// Preparar y ejecutar la consulta para obtener las palabras
$sql = "SELECT id, palabra FROM palabras WHERE subcategoria_id = ? ORDER BY palabra ASC";
$stmt = $conn->prepare($sql);
if (!$stmt) {
    http_response_code(500);
    die(json_encode(['error' => 'Error al preparar la consulta: ' . $conn->error]));
}

$stmt->bind_param("i", $subcategoria_id);
$stmt->execute();
$result = $stmt->get_result();

// Procesar los resultados y guardarlos en un array
$words = [];
while ($row = $result->fetch_assoc()) {
    $words[] = $row;
}

$stmt->close();
$conn->close();

// Devolver el array de palabras como JSON
echo json_encode($words);
?>
