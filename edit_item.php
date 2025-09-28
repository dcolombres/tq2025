<?php
header('Content-Type: application/json');
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'db_config.php';

function send_error($message) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $message]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    send_error('Método no permitido.');
}

$type = isset($_POST['type']) ? $_POST['type'] : '';
$id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
$name = isset($_POST['name']) ? trim($_POST['name']) : '';

if (empty($type) || $id === 0 || empty($name)) {
    send_error('Parámetros inválidos.');
}

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    http_response_code(500);
    send_error('Error de conexión a la base de datos.');
}

$table_name = '';
switch ($type) {
    case 'categoria':
        $table_name = 'Categorias';
        break;
    case 'nivel':
        $table_name = 'Niveles';
        break;
    case 'subcategory':
        $table_name = 'Subcategorias';
        break;
    default:
        send_error('Tipo de elemento no soportado.');
}

// Verificar si el nuevo nombre ya existe en otro ítem del mismo tipo
$stmt_check = $conn->prepare("SELECT id FROM {$table_name} WHERE nombre = ? AND id != ?");
if (!$stmt_check) send_error("Error al preparar la verificación de duplicados en {$table_name}.");
$stmt_check->bind_param("si", $name, $id);
$stmt_check->execute();
if ($stmt_check->get_result()->fetch_assoc()) {
    $stmt_check->close();
    send_error("Ya existe otro ítem en '{$table_name}' con ese nombre.");
}
$stmt_check->close();

// Proceder con la actualización
$stmt_update = $conn->prepare("UPDATE {$table_name} SET nombre = ? WHERE id = ?");
if (!$stmt_update) send_error("Error al preparar la actualización en {$table_name}.");
$stmt_update->bind_param("si", $name, $id);

if ($stmt_update->execute()) {
    if ($stmt_update->affected_rows > 0) {
        echo json_encode(['success' => true, 'message' => ucfirst($type) . ' actualizado correctamente.']);
    } else {
        send_error('No se encontró el ítem o el nombre no cambió.');
    }
} else {
    send_error("Error al ejecutar la actualización: " . $stmt_update->error);
}
$stmt_update->close();

$conn->close();
?>
