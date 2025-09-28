<?php
header('Content-Type: application/json');
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/db_config.php';

function send_json_error($message) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $message]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    send_json_error('Método no permitido.');
}

$input = json_decode(file_get_contents('php://input'), true);

$subcategoria_id = isset($input['subcategoria_id']) ? (int)$input['subcategoria_id'] : 0;
$palabra = isset($input['palabra']) ? trim($input['palabra']) : '';

if ($subcategoria_id === 0 || empty($palabra)) {
    send_json_error('Parámetros inválidos. Se requiere subcategoría y palabra.');
}

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    http_response_code(500);
    send_json_error('Error de conexión a la base de datos.');
}
$conn->set_charset("utf8");

// Verificar si la palabra ya existe en esa subcategoría
$stmt_check = $conn->prepare("SELECT id FROM Palabras WHERE subcategoria_id = ? AND palabra = ?");
if (!$stmt_check) send_json_error("Error al preparar la verificación de duplicados.");
$stmt_check->bind_param("is", $subcategoria_id, $palabra);
$stmt_check->execute();
if ($stmt_check->get_result()->fetch_assoc()) {
    $stmt_check->close();
    send_json_error("La palabra '{$palabra}' ya existe en esta subcategoría.");
}
$stmt_check->close();

// Insertar la nueva palabra
$stmt_insert = $conn->prepare("INSERT INTO Palabras (subcategoria_id, palabra) VALUES (?, ?)");
if (!$stmt_insert) send_json_error("Error al preparar la inserción.");
$stmt_insert->bind_param("is", $subcategoria_id, $palabra);

if ($stmt_insert->execute()) {
    $new_word_id = $conn->insert_id;
    echo json_encode(['success' => true, 'message' => 'Palabra añadida correctamente.', 'new_word_id' => $new_word_id]);
} else {
    send_json_error("Error al añadir la palabra: " . $stmt_insert->error);
}

$stmt_insert->close();
$conn->close();
?>
