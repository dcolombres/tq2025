<?php
/**
 * edit_item.php
 * 
 * Este script maneja la actualización del nombre de un ítem estructural (categoría, nivel o subcategoría).
 * Recibe el tipo de ítem, su ID y el nuevo nombre a través de una petición POST.
 * Antes de actualizar, verifica que el nuevo nombre no esté ya en uso por otro ítem del mismo tipo.
 */

header('Content-Type: application/json');
error_reporting(E_ALL);
ini_set('display_errors', 1);

// --- Conexión a la Base de Datos ---
require_once __DIR__ . '/db_config.php';

// --- Funciones de Ayuda ---
function send_error($message) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $message]);
    exit;
}

// --- Lógica Principal ---

// Validar que la petición sea de tipo POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    send_error('Método no permitido.');
}

// Obtener y validar los datos del formulario POST
$type = isset($_POST['type']) ? $_POST['type'] : '';
$id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
$name = isset($_POST['name']) ? trim($_POST['name']) : '';

if (empty($type) || $id === 0 || empty($name)) {
    send_error('Parámetros inválidos (se requiere tipo, ID y nombre).');
}

// Iniciar la conexión a la BD
$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    http_response_code(500);
    send_error('Error de conexión a la base de datos.');
}

// Determinar la tabla correcta a modificar según el tipo de ítem
$table_name = '';
switch ($type) {
    case 'categoria':
        $table_name = 'categorias';
        break;
    case 'nivel':
        $table_name = 'niveles';
        break;
    case 'subcategory':
        $table_name = 'subcategorias';
        break;
    default:
        send_error('Tipo de elemento no soportado.');
}

// Paso 1: Verificar si el nuevo nombre ya existe en otro ítem del mismo tipo para evitar duplicados.
$stmt_check = $conn->prepare("SELECT id FROM {$table_name} WHERE nombre = ? AND id != ?");
if (!$stmt_check) send_error("Error al preparar la verificación de duplicados en {$table_name}.");
$stmt_check->bind_param("si", $name, $id);
$stmt_check->execute();
if ($stmt_check->get_result()->fetch_assoc()) {
    $stmt_check->close();
    send_error("Ya existe otro ítem en '{$table_name}' con ese nombre.");
}
$stmt_check->close();

// Paso 2: Si el nombre es único, proceder con la actualización.
$stmt_update = $conn->prepare("UPDATE {$table_name} SET nombre = ? WHERE id = ?");
if (!$stmt_update) send_error("Error al preparar la actualización en {$table_name}.");
$stmt_update->bind_param("si", $name, $id);

if ($stmt_update->execute()) {
    if ($stmt_update->affected_rows > 0) {
        echo json_encode(['success' => true, 'message' => ucfirst($type) . ' actualizado correctamente.']);
    } else {
        // Esto puede pasar si el nombre enviado es el mismo que ya existía.
        send_error('No se encontró el ítem o el nombre no cambió.');
    }
} else {
    send_error("Error al ejecutar la actualización: " . $stmt_update->error);
}
$stmt_update->close();

$conn->close();
?>
