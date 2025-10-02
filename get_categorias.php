<?php
header('Content-Type: application/json');
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/db_config.php';

$mode = $_GET['mode'] ?? '';
if (empty($mode)) {
    http_response_code(400);
    die(json_encode(['error' => 'El parámetro "mode" es requerido.']));
}

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    http_response_code(500);
    die(json_encode(["error" => "Fallo de conexión: " . $conn->connect_error]));
}

$sql = "
    SELECT s.id, s.nombre, n.nombre, s.permite_validacion_externa 
    FROM subcategorias s
    JOIN categorias c ON s.categoria_id = c.id
    LEFT JOIN niveles n ON s.nivel_id = n.id
    WHERE c.nombre = ?
    ORDER BY RAND()
    LIMIT 1
";

$stmt = $conn->prepare($sql);
if (!$stmt) {
    http_response_code(500);
    die(json_encode(['error' => 'Error al preparar la consulta: ' . $conn->error]));
}

$stmt->bind_param("s", $mode);

if (!$stmt->execute()) {
    http_response_code(500);
    die(json_encode(['error' => 'Error al ejecutar la consulta: ' . $stmt->error]));
}

$stmt->store_result();

if ($stmt->num_rows > 0) {
    $stmt->bind_result($id_subcategoria, $nombre_categoria, $nivel, $permite_api);
    $stmt->fetch();
    
    $subcategory = [
        'id_subcategoria' => $id_subcategoria,
        'nombre_categoria' => $nombre_categoria,
        'nivel' => $nivel,
        'permite_api' => (bool)$permite_api
    ];
    echo json_encode($subcategory);
} else {
    http_response_code(404);
    echo json_encode(['error' => 'No se encontraron subcategorías para este modo de juego.']);
}

$stmt->close();
$conn->close();
?>