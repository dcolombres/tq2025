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

$mode = $_GET['mode'] ?? 'party';
$party_category_id = null;

// --- Step 1: Count the total number of eligible rows ---
$count_sql = "";
if ($mode === 'party') {
    $category_name = 'PARTY';
    $stmt_cat = $conn->prepare("SELECT id FROM categorias WHERE nombre = ?");
    $stmt_cat->bind_param("s", $category_name);
    $stmt_cat->execute();
    $result_cat = $stmt_cat->get_result();
    if ($result_cat->num_rows === 0) {
        http_response_code(404);
        die(json_encode(["error" => "La categoría principal 'PARTY' no fue encontrada."]));
    }
    $party_category_id = $result_cat->fetch_assoc()['id'];
    $stmt_cat->close();
    
    $count_sql = "SELECT COUNT(*) as total FROM subcategorias WHERE categoria_id = ?";
    $count_stmt = $conn->prepare($count_sql);
    $count_stmt->bind_param("i", $party_category_id);
} else {
    $count_sql = "SELECT COUNT(*) as total FROM subcategorias";
    $count_stmt = $conn->prepare($count_sql);
}

$count_stmt->execute();
$count_result = $count_stmt->get_result()->fetch_assoc();
$total_rows = $count_result['total'];
$count_stmt->close();

if ($total_rows == 0) {
    http_response_code(404);
    die(json_encode(["error" => "No se encontraron subcategorías para el modo seleccionado."]));
}

// --- Step 2: Generate a random offset ---
$random_offset = rand(0, $total_rows - 1);

// --- Step 3: Fetch the single random row using the offset ---
$sql = "";
if ($mode === 'party') {
    $sql = "SELECT s.nombre AS subcategoria, n.nombre as nivel 
            FROM subcategorias s JOIN niveles n ON s.nivel_id = n.id
            WHERE s.categoria_id = ? 
            LIMIT 1 OFFSET ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $party_category_id, $random_offset);
} else {
    $sql = "SELECT s.nombre AS subcategoria, n.nombre as nivel 
            FROM subcategorias s JOIN niveles n ON s.nivel_id = n.id
            LIMIT 1 OFFSET ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $random_offset);
}

if (!$stmt) {
    http_response_code(500);
    die(json_encode(['error' => 'Error al preparar la consulta final: ' . $conn->error]));
}

$stmt->execute();
$result = $stmt->get_result();

if ($result && $result->num_rows > 0) {
    echo json_encode($result->fetch_assoc());
} else {
    http_response_code(500); // Should not happen if count > 0, but as a fallback
    echo json_encode(["error" => "Error inesperado al obtener la subcategoría aleatoria."]);
}

$stmt->close();
$conn->close();
?>