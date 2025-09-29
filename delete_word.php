<?php
/**
 * delete_word.php
 * 
 * Este script maneja la eliminación de una palabra específica de la base de datos.
 * Recibe el ID de la palabra a eliminar a través de una petición POST con JSON.
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

// Obtener y validar el ID de la palabra del cuerpo de la petición
$input = json_decode(file_get_contents('php://input'), true);
$word_id = isset($input['word_id']) ? (int)$input['word_id'] : 0;

if ($word_id === 0) {
    send_json_error('ID de palabra inválido.');
}

// Iniciar la conexión a la BD
$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    http_response_code(500);
    send_json_error('Error de conexión a la base de datos.');
}

// Preparar y ejecutar la sentencia de eliminación
$stmt = $conn->prepare("DELETE FROM palabras WHERE id = ?");
if (!$stmt) {
    send_json_error("Error al preparar la eliminación.");
}

$stmt->bind_param("i", $word_id);

if ($stmt->execute()) {
    // Verificar si alguna fila fue realmente eliminada
    if ($stmt->affected_rows > 0) {
        echo json_encode(['success' => true, 'message' => 'Palabra eliminada correctamente.']);
    } else {
        send_json_error('No se encontró la palabra para eliminar.');
    }
} else {
    send_json_error("Error al ejecutar la eliminación: " . $stmt->error);
}

$stmt->close();
$conn->close();
?>
