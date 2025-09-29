<?php
/**
 * get_items.php
 * 
 * Este es un script genérico para obtener una lista de ítems de la base de datos.
 * Utiliza un parámetro GET 'type' para determinar qué tipo de ítems devolver 
 * (ej. 'categoria', 'nivel').
 * Es utilizado por la página de gestión para poblar las tablas de categorías y niveles.
 */

header('Content-Type: application/json');
error_reporting(E_ALL);
ini_set('display_errors', 1);

// --- Conexión a la Base de Datos ---
require_once __DIR__ . '/db_config.php';

// --- Funciones de Ayuda ---
function send_error($message) {
    http_response_code(400);
    echo json_encode(['error' => $message]);
    exit;
}

// --- Lógica Principal ---

// Obtener y validar el tipo de ítem solicitado desde la URL
$type = isset($_GET['type']) ? $_GET['type'] : '';
if (empty($type)) {
    send_error('El tipo de item no fue especificado.');
}

// Iniciar la conexión a la BD
$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    http_response_code(500);
    send_error('Error de conexión a la base de datos.');
}

// Construir la consulta SQL apropiada según el tipo de ítem
$sql = '';
switch ($type) {
    case 'categoria':
        $sql = "SELECT id, nombre FROM categorias ORDER BY nombre ASC";
        break;
    case 'nivel':
        $sql = "SELECT id, nombre FROM niveles ORDER BY nombre ASC";
        break;
    default:
        send_error('Tipo de item no soportado.');
        break;
}

// Ejecutar la consulta y obtener los resultados
$result = $conn->query($sql);

// Procesar los resultados y guardarlos en un array
$items = [];
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $items[] = $row;
    }
}

// Devolver el array de ítems como JSON
echo json_encode($items);

$conn->close();
?>
