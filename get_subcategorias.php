<?php
header('Content-Type: application/json');
require_once __DIR__ . '/db_config.php';

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    http_response_code(500);
    die(json_encode(['error' => 'Conexión fallida: ' . $conn->connect_error]));
}

$get_all = isset($_GET['all']) && $_GET['all'] === 'true';
$categoria_id = isset($_GET['categoria_id']) ? (int)$_GET['categoria_id'] : 0;

if ($get_all) {
    // CORREGIDO: La tabla `categorias` se une a través de `subcategorias.categoria_id`
    $sql = "SELECT s.id, s.nombre, n.nombre as nivel_nombre, c.nombre as categoria_nombre
            FROM subcategorias s
            JOIN niveles n ON s.nivel_id = n.id
            JOIN categorias c ON s.categoria_id = c.id
            ORDER BY c.nombre, n.nombre, s.nombre";
    $stmt = $conn->prepare($sql);
} elseif ($categoria_id > 0) {
    $sql = "SELECT id, nombre, permite_validacion_externa FROM subcategorias WHERE categoria_id = ? ORDER BY nombre ASC";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $categoria_id);
} else {
    echo json_encode([]);
    $conn->close();
    exit;
}

if (!$stmt) {
    http_response_code(500);
    die(json_encode(['error' => 'Error en la preparación de la consulta: ' . $conn->error]));
}

$stmt->execute();
$result = $stmt->get_result();
$subcategorias = [];

if ($result) {
    while ($row = $result->fetch_assoc()) {
        $subcategorias[] = $row;
    }
}

echo json_encode($subcategorias);

$stmt->close();
$conn->close();
?>