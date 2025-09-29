<?php
/**
 * edit_word.php
 * 
 * Este script maneja la actualización del texto de una palabra existente.
 * Recibe el ID de la palabra y el nuevo texto a través de una petición POST con JSON.
 * Opcionalmente, verifica que la nueva palabra no exista ya en la misma subcategoría.
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
$word_id = isset($input['word_id']) ? (int)$input['word_id'] : 0;
$new_palabra = isset($input['new_palabra']) ? trim($input['new_palabra']) : '';

if ($word_id === 0 || empty($new_palabra)) {
    send_json_error('Parámetros inválidos. Se requiere ID y el nuevo nombre de la palabra.');
}

// Iniciar la conexión a la BD
$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    http_response_code(500);
    send_json_error('Error de conexión a la base de datos.');
}
$conn->set_charset("utf8");

// Paso 1: (Opcional pero recomendado) Verificar si la nueva palabra ya existe en la misma subcategoría.
// Esto previene tener la misma palabra dos veces en la misma lista.
$stmt_check = $conn->prepare("SELECT id FROM palabras WHERE palabra = ? AND subcategoria_id = (SELECT subcategoria_id FROM palabras WHERE id = ?) AND id != ?");
if (!$stmt_check) send_json_error("Error al preparar la verificación de duplicados.");
$stmt_check->bind_param("sii", $new_palabra, $word_id, $word_id);
$stmt_check->execute();
if ($stmt_check->get_result()->fetch_assoc()) {
    $stmt_check->close();
    send_json_error("Esa palabra ya existe en esta subcategoría.");
}
$stmt_check->close();

// Paso 2: Proceder con la actualización del nombre de la palabra.
$stmt_update = $conn->prepare("UPDATE palabras SET palabra = ? WHERE id = ?");
if (!$stmt_update) send_json_error("Error al preparar la actualización.");
$stmt_update->bind_param("si", $new_palabra, $word_id);

if ($stmt_update->execute()) {
    if ($stmt_update->affected_rows > 0) {
        echo json_encode(['success' => true, 'message' => 'Palabra actualizada correctamente.']);
    } else {
        send_json_error('No se encontró la palabra o el nombre no cambió.');
    }
} else {
    send_json_error("Error al ejecutar la actualización: " . $stmt_update->error);
}

$stmt_update->close();
$conn->close();
?>
