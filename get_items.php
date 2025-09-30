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

// --- Conexión a la Base de Datos ---
require_once __DIR__ . '/db_config.php';

// --- Funciones de Ayuda ---
function send_error($message) {
    http_response_code(400);
    echo json_encode(['error' => $message]);
    exit;
}

// --- Lógica Principal ---

$type = isset($_GET['type']) ? $_GET['type'] : '';
if (empty($type)) {
    send_error('El tipo de item no fue especificado.');
}

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    http_response_code(500);
    send_error('Error de conexión a la base de datos.');
}

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

// Refactor para no usar get_result()
$stmt = $conn->prepare($sql);
if (!$stmt) {
    http_response_code(500);
    send_error('Error al preparar la consulta: ' . $conn->error);
}

if (!$stmt->execute()) {
    http_response_code(500);
    send_error('Error al ejecutar la consulta: ' . $stmt->error);
}

$stmt->store_result();
$stmt->bind_result($id, $nombre);

$items = [];
while ($stmt->fetch()) {
    $items[] = ['id' => $id, 'nombre' => $nombre];
}

$stmt->close();
$conn->close();

echo json_encode($items);
?>
