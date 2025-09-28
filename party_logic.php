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

if ($mode === 'party') {
    // --- PARTY MODE LOGIC ---

    // 1. Find the ID of the 'Party' main category
    $category_name = 'Party'; // Correct case
    $stmt_cat = $conn->prepare("SELECT id FROM categorias WHERE nombre = ?");
    if (!$stmt_cat) die(json_encode(['error' => 'Prepare failed for category lookup']));
    $stmt_cat->bind_param("s", $category_name);
    $stmt_cat->execute();
    $result_cat = $stmt_cat->get_result();
    if ($result_cat->num_rows === 0) {
        http_response_code(404);
        die(json_encode(["error" => "La categoría principal 'Party' no fue encontrada."]));
    }
    $party_category_id = $result_cat->fetch_assoc()['id'];
    $stmt_cat->close();

    // 2. Count rows in that category
    $count_stmt = $conn->prepare("SELECT COUNT(*) as total FROM subcategorias WHERE categoria_id = ?");
    if (!$count_stmt) die(json_encode(['error' => 'Prepare failed for party count']));
    $count_stmt->bind_param("i", $party_category_id);
    $count_stmt->execute();
    $total_rows = $count_stmt->get_result()->fetch_assoc()['total'];
    $count_stmt->close();

    if ($total_rows == 0) die(json_encode(["error" => "No hay subcategorías en el modo Party."]));

    // 3. Fetch a random row from that category
    $random_offset = rand(0, $total_rows - 1);
    $stmt = $conn->prepare("SELECT s.nombre AS subcategoria, n.nombre as nivel FROM subcategorias s JOIN niveles n ON s.nivel_id = n.id WHERE s.categoria_id = ? LIMIT 1 OFFSET ?");
    if (!$stmt) die(json_encode(['error' => 'Prepare failed for party select']));
    $stmt->bind_param("ii", $party_category_id, $random_offset);

} else {
    // --- TUTTI MODE LOGIC ---

    // 1. Count all rows
    $count_result = $conn->query("SELECT COUNT(*) as total FROM subcategorias");
    $total_rows = $count_result->fetch_assoc()['total'];
    if ($total_rows == 0) die(json_encode(['error' => 'No hay subcategorías en la base de datos.']));

    // 2. Fetch a random row from all categories
    $random_offset = rand(0, $total_rows - 1);
    $stmt = $conn->prepare("SELECT s.nombre AS subcategoria, n.nombre as nivel FROM subcategorias s JOIN niveles n ON s.nivel_id = n.id LIMIT 1 OFFSET ?");
    if (!$stmt) die(json_encode(['error' => 'Prepare failed for tutti select']));
    $stmt->bind_param("i", $random_offset);
}

// --- Execute and Return Result (common for both modes) ---
$stmt->execute();
$result = $stmt->get_result();

if ($result && $result->num_rows > 0) {
    echo json_encode($result->fetch_assoc());
} else {
    http_response_code(500);
    echo json_encode(["error" => "Error inesperado al obtener la subcategoría aleatoria."]);
}

$stmt->close();
$conn->close();
?>