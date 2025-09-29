<?php
header('Content-Type: application/json');
require_once __DIR__ . '/db_config.php';

// Silenciar errores para un entorno de producción
error_reporting(0);
ini_set('display_errors', 0);

if (!isset($_GET['mode']) || empty($_GET['mode'])) {
    http_response_code(400);
    echo json_encode(['error' => 'El parámetro "mode" es requerido.']);
    exit;
}

$mode = $_GET['mode'];
$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    http_response_code(500);
    echo json_encode(["error" => "Fallo de conexión: " . $conn->connect_error]);
    exit;
}

// 1. Buscar el ID de la categoría por su nombre
$id_categoria = null;
$stmt = $conn->prepare("SELECT id FROM categorias WHERE nombre = ?");
$stmt->bind_param("s", $mode);
$stmt->execute();
$stmt->store_result();
$stmt->bind_result($id_categoria);
$stmt->fetch();
$stmt->close();

if (is_null($id_categoria)) {
    http_response_code(404);
    echo json_encode(['error' => 'El modo de juego no fue encontrado.']);
    $conn->close();
    exit;
}

// 2. Seleccionar una subcategoría aleatoria con JOIN para obtener el nivel
$id_subcategoria = null;
$nombre_categoria = null;
$nivel = null;
$permite_api = null;

$sql = "SELECT s.id, s.nombre, n.nombre, s.permite_validacion_externa 
        FROM subcategorias s 
        LEFT JOIN niveles n ON s.nivel_id = n.id 
        WHERE s.categoria_id = ? 
        ORDER BY RAND() 
        LIMIT 1";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id_categoria);
$stmt->execute();
$stmt->store_result();
$stmt->bind_result($id_subcategoria, $nombre_categoria, $nivel, $permite_api);
$stmt->fetch();

if ($stmt->num_rows > 0) {
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