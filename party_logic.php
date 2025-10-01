<?php
header('Content-Type: application/json');
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/db_config.php';

// --- Get Parameters ---
$mode = $_GET['mode'] ?? 'party';
$exclude_ids_str = $_GET['exclude'] ?? '';

$exclude_ids = [];
if (!empty($exclude_ids_str)) {
    $exclude_ids = array_map('intval', explode(',', $exclude_ids_str));
}

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    http_response_code(500);
    die(json_encode(["error" => "Connection failed: " . $conn->connect_error]));
}
$conn->set_charset("utf8");

// --- Build Query --- 
$base_sql = "SELECT s.id, s.nombre AS subcategoria, n.nombre as nivel FROM subcategorias s JOIN niveles n ON s.nivel_id = n.id";
$where_clauses = [];
$params = [];
$types = '';

if ($mode === 'party') {
    $where_clauses[] = "s.categoria_id = (SELECT id FROM categorias WHERE nombre = 'Party' LIMIT 1)";
}

if (!empty($exclude_ids)) {
    $placeholders = implode(',', array_fill(0, count($exclude_ids), '?'));
    $where_clauses[] = "s.id NOT IN ($placeholders)";
    foreach ($exclude_ids as $ex_id) {
        $params[] = $ex_id;
        $types .= 'i';
    }
}

$sql = $base_sql;
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
    // Fallback: If no category is found (e.g., all excluded), try again without exclusion.
    $fallback_sql = $base_sql;
    if ($mode === 'party') {
        $fallback_sql .= " WHERE s.categoria_id = (SELECT id FROM categorias WHERE nombre = 'Party' LIMIT 1)";
    }
    $fallback_sql .= " ORDER BY RAND() LIMIT 1";
    $fallback_stmt = $conn->prepare($fallback_sql);
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