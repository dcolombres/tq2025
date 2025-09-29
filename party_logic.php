<?php
header('Content-Type: application/json');
error_reporting(0);
ini_set('display_errors', 0);

require 'db_config.php';

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    http_response_code(500);
    die(json_encode(["error" => "Connection failed: " . $conn->connect_error]));
}
$conn->set_charset("utf8");

// --- Get Parameters ---
$mode = $_GET['mode'] ?? 'party';
$exclude_ids_str = $_GET['exclude'] ?? '';

$exclude_ids = [];
if (!empty($exclude_ids_str)) {
    $exclude_ids = array_map('intval', explode(',', $exclude_ids_str));
}

// --- Build Query based on Mode ---
$sql = "SELECT s.id, s.nombre AS subcategoria, n.nombre as nivel FROM subcategorias s JOIN niveles n ON s.nivel_id = n.id";
$params = [];
$types = '';

$where_clauses = [];

// Mode-specific WHERE clause
if ($mode === 'party') {
    $category_name = 'Party';
    $stmt_cat = $conn->prepare("SELECT id FROM categorias WHERE nombre = ?");
    $stmt_cat->bind_param("s", $category_name);
    $stmt_cat->execute();
    $stmt_cat->store_result();
    if ($stmt_cat->num_rows > 0) {
        $stmt_cat->bind_result($party_category_id);
        $stmt_cat->fetch();
        $where_clauses[] = "s.categoria_id = ?";
        $params[] = $party_category_id;
        $types .= 'i';
    }
    $stmt_cat->close();
}

// Exclusion WHERE clause
if (!empty($exclude_ids)) {
    $placeholders = implode(',', array_fill(0, count($exclude_ids), '?'));
    $where_clauses[] = "s.id NOT IN ($placeholders)";
    foreach ($exclude_ids as $ex_id) {
        $params[] = $ex_id;
        $types .= 'i';
    }
}

if (!empty($where_clauses)) {
    $sql .= " WHERE " . implode(' AND ', $where_clauses);
}

$sql .= " ORDER BY RAND() LIMIT 1";

// --- Execute Query ---
$stmt = $conn->prepare($sql);

if (!$stmt) {
    http_response_code(500);
    die(json_encode(['error' => 'Prepare failed: ' . $conn->error, 'sql' => $sql]));
}

if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();
$stmt->store_result();

if ($stmt->num_rows > 0) {
    $stmt->bind_result($id, $subcategoria, $nivel);
    $stmt->fetch();
    echo json_encode(['id' => $id, 'subcategoria' => $subcategoria, 'nivel' => $nivel]);
} else {
    // If no rows are found (maybe all were excluded), try again without exclusion
    $fallback_sql = str_contains($sql, "NOT IN") ? substr($sql, 0, strpos($sql, "AND s.id NOT IN")) . " ORDER BY RAND() LIMIT 1" : $sql;
    $fallback_stmt = $conn->prepare($fallback_sql);
    if($mode === 'party') $fallback_stmt->bind_param("i", $party_category_id);
    $fallback_stmt->execute();
    $fallback_stmt->store_result();
    $fallback_stmt->bind_result($id, $subcategoria, $nivel);
    $fallback_stmt->fetch();
    echo json_encode(['id' => $id, 'subcategoria' => $subcategoria, 'nivel' => $nivel]);
    $fallback_stmt->close();
}

$stmt->close();
$conn->close();
?>