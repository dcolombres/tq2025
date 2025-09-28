<?php
header('Content-Type: application/json');
error_reporting(E_ALL);
ini_set('display_errors', 1);

require 'db_config.php';

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    http_response_code(500);
    die(json_encode(["success" => false, "message" => "Connection failed: " . $conn->connect_error]));
}

$data = json_decode(file_get_contents('php://input'), true);

$subcategoryId = $data['subcategoria_id'] ?? null;
$isChecked = $data['is_checked'] ?? null;

if ($subcategoryId === null || $isChecked === null) {
    http_response_code(400);
    die(json_encode(["success" => false, "message" => "Datos incompletos."]));
}

$newValue = $isChecked ? 1 : 0;

$sql = "UPDATE Subcategorias SET permite_validacion_externa = ? WHERE id = ?";
$stmt = $conn->prepare($sql);

if (!$stmt) {
    http_response_code(500);
    die(json_encode(["success" => false, "message" => "Error al preparar la consulta: " . $conn->error]));
}

$stmt->bind_param("ii", $newValue, $subcategoryId);

if ($stmt->execute()) {
    echo json_encode(["success" => true, "message" => "Configuración actualizada."]);
} else {
    http_response_code(500);
    echo json_encode(["success" => false, "message" => "Error al actualizar: " . $stmt->error]);
}

$stmt->close();
$conn->close();
?>