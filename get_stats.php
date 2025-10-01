<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

header('Content-Type: application/json');

require_once __DIR__ . '/db_config.php';
$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    http_response_code(500);
    die(json_encode(['error' => 'Conexión fallida: ' . $conn->connect_error]));
}

// --- DIAGNOSTIC QUERY ---
$sql = "SELECT 1";
$stmt = $conn->prepare($sql);

if (!$stmt) {
    http_response_code(500);
    die(json_encode(['error' => 'Fallo en prepare() para la consulta de diagnóstico.', 'details' => $conn->error]));
}

if (!$stmt->execute()) {
    http_response_code(500);
    die(json_encode(['error' => 'Fallo en execute() para la consulta de diagnóstico.', 'details' => $stmt->error]));
}

$stmt->store_result();
$stmt->bind_result($one);
$stmt->fetch();

// Si llegamos aquí, el script y la conexión funcionan. El problema es la consulta SQL original.
die(json_encode(['success' => true, 'message' => 'La consulta de diagnóstico se ejecutó correctamente.', 'data' => $one]));

$stmt->close();
$conn->close();
?>