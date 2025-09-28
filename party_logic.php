<?php
header('Content-Type: application/json');
error_reporting(E_ALL);
ini_set('display_errors', 1);

require 'db_config.php';

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    http_response_code(500);
    die(json_encode(["error" => "Connection failed: " . $conn->connect_error]));
}
$conn->set_charset("utf8");

// Determine game mode: 'party' for specific categories, or 'all' for everything.
$mode = $_GET['mode'] ?? 'party'; 

$sql = "";
$stmt = null;

if ($mode === 'party') {
    // 1. Find the ID of the 'PARTY' main category
    $category_name = 'PARTY';
    $stmt_cat = $conn->prepare("SELECT id FROM categorias WHERE nombre = ?");
    $stmt_cat->bind_param("s", $category_name);
    $stmt_cat->execute();
    $result_cat = $stmt_cat->get_result();

    if ($result_cat->num_rows === 0) {
        http_response_code(404);
        die(json_encode(["error" => "La categoría principal 'PARTY' no fue encontrada en la base de datos."]));
    }
    $party_category_id = $result_cat->fetch_assoc()['id'];
    $stmt_cat->close();

    // 2. Fetch a random subcategory from the 'PARTY' main category
    $sql = "SELECT s.nombre AS subcategoria, n.nombre as nivel 
            FROM subcategorias s
            JOIN niveles n ON s.nivel_id = n.id
            WHERE s.categoria_id = ? 
            ORDER BY RAND() 
            LIMIT 1";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $party_category_id);

} else { // 'all' mode
    // Fetch a random subcategory from the entire database
    $sql = "SELECT s.nombre AS subcategoria, n.nombre as nivel 
            FROM subcategorias s
            JOIN niveles n ON s.nivel_id = n.id
            ORDER BY RAND() 
            LIMIT 1";
    $stmt = $conn->prepare($sql);
}

if (!$stmt) {
    http_response_code(500);
    die(json_encode(['error' => 'Error al preparar la consulta: ' . $conn->error]));
}

$stmt->execute();
$result = $stmt->get_result();

if ($result && $result->num_rows > 0) {
    $random_item = $result->fetch_assoc();
    echo json_encode($random_item);
} else {
    http_response_code(404);
    echo json_encode(["error" => "No se encontraron subcategorías para el modo seleccionado."]);
}

$stmt->close();
$conn->close();
?>