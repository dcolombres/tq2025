<?php
// test_logic.php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

header('Content-Type: application/json');

require_once __DIR__ . '/db_config.php';
$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    http_response_code(500);
    die(json_encode(['error' => 'Conexión fallida en test_logic.php: ' . $conn->connect_error]));
}

// Esta es la consulta y lógica EXACTA que funciona en bulk_insert.php
$sql = "
    SELECT 
        s.id AS subcategoria_id,
        c.nombre AS categoria, 
        n.nombre AS nivel,
        s.nombre AS subcategoria, 
        s.permite_validacion_externa
    FROM 
        subcategorias s
    JOIN 
        categorias c ON s.categoria_id = c.id
    LEFT JOIN
        niveles n ON s.nivel_id = n.id
    ORDER BY 
        c.nombre, n.nombre, s.nombre;
";

$stmt = $conn->prepare($sql);
if (!$stmt) {
    http_response_code(500);
    die(json_encode(['error' => 'Fallo en prepare() en test_logic.php', 'details' => $conn->error]));
}

if (!$stmt->execute()) {
    http_response_code(500);
    die(json_encode(['error' => 'Fallo en execute() en test_logic.php', 'details' => $stmt->error]));
}

$stmt->store_result();
$stmt->bind_result($subcategoria_id, $categoria, $nivel, $subcategoria, $permite_validacion_externa);

$stats = [];
while ($stmt->fetch()) {
    $stats[] = [
        'subcategoria_id' => $subcategoria_id,
        'categoria' => $categoria,
        'nivel' => $nivel,
        'subcategoria' => $subcategoria,
        'permite_validacion_externa' => $permite_validacion_externa,
        'cantidad_palabras' => 0 // Devolvemos 0 temporalmente
    ];
}

$stmt->close();
$conn->close();

echo json_encode($stats);
?>