<?php
/**
 * add_word.php
 * 
 * Este script maneja la adición de una única palabra a una subcategoría.
 * Es utilizado por el "Modo Editor" para añadir palabras válidas sobre la marcha.
 * Recibe el ID de la subcategoría y la palabra a través de una petición POST con JSON.
 * Verifica si la palabra ya existe antes de insertarla.
 */

header('Content-Type: application/json');
error_reporting(E_ALL);
ini_set('display_errors', 1);

// --- Conexión a la Base de Datos ---
require_once __DIR__ . '/db_config.php';

// --- Funciones de Ayuda ---
function send_json_error($message) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $message]);
    exit;
}

// --- Lógica Principal ---

// Validar que la petición sea de tipo POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    send_json_error('Método no permitido.');
}

// Obtener y validar los datos de entrada (JSON)
$input = json_decode(file_get_contents('php://input'), true);
$subcategoria_id = isset($input['subcategoria_id']) ? (int)$input['subcategoria_id'] : 0;
$palabra = isset($input['palabra']) ? trim($input['palabra']) : '';

if ($subcategoria_id === 0 || empty($palabra)) {
    send_json_error('Parámetros inválidos. Se requiere subcategoría y palabra.');
}

// Iniciar la conexión a la BD
$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    http_response_code(500);
    send_json_error('Error de conexión a la base de datos.');
}
$conn->set_charset("utf8");

// Paso 1: Verificar si la palabra ya existe en esa subcategoría para evitar duplicados
$stmt_check = $conn->prepare("SELECT id FROM palabras WHERE subcategoria_id = ? AND palabra = ?");
if (!$stmt_check) send_json_error("Error al preparar la verificación de duplicados.");
$stmt_check->bind_param("is", $subcategoria_id, $palabra);
$stmt_check->execute();
if ($stmt_check->get_result()->fetch_assoc()) {
    $stmt_check->close();
    send_json_error("La palabra '{$palabra}' ya existe en esta subcategoría.");
}
$stmt_check->close();

// Paso 2: Insertar la nueva palabra si no existe
$stmt_insert = $conn->prepare("INSERT INTO palabras (subcategoria_id, palabra) VALUES (?, ?)");
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
