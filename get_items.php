<?php
header('Content-Type: application/json');
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/db_config.php';

function send_error($message) {
    http_response_code(400);
    echo json_encode(['error' => $message]);
    exit;
}

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
        $sql = "SELECT id, nombre FROM Categorias ORDER BY nombre ASC";
        break;
    case 'nivel':
        $sql = "SELECT id, nombre FROM Niveles ORDER BY nombre ASC";
        break;
    default:
        send_error('Tipo de item no soportado.');
        break;
}

$result = $conn->query($sql);

$items = [];
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $items[] = $row;
    }
}

echo json_encode($items);

$conn->close();
?>
