<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

header('Content-Type: application/json');

require_once __DIR__ . '/db_config.php';
$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    http_response_code(500);
    die(json_encode(["error" => "Conexión fallida: " . $conn->connect_error]));
}

$sql = "
    SELECT 
        s.id AS subcategoria_id,
        c.nombre AS categoria, 
        n.nombre AS nivel,
        s.nombre AS subcategoria, 
        s.permite_validacion_externa,
        COUNT(p.id) AS cantidad_palabras
    FROM 
        subcategorias s
    JOIN 
        categorias c ON s.categoria_id = c.id
    LEFT JOIN
        niveles n ON s.nivel_id = n.id
    LEFT JOIN 
        palabras p ON s.id = p.subcategoria_id
    GROUP BY 
        s.id, c.nombre, n.nombre, s.nombre, s.permite_validacion_externa
    ORDER BY 
        c.nombre, n.nombre, s.nombre;
";

$stmt = $conn->prepare($sql);
if (!$stmt) {
    http_response_code(500);
    die(json_encode(['error' => 'Fallo en prepare(): ' . $conn->error]));
}

if (!$stmt->execute()) {
    http_response_code(500);
    die(json_encode(['error' => 'Fallo en execute(): ' . $stmt->error]));
}

if (!$stmt->store_result()) {
    http_response_code(500);
    die(json_encode(['error' => 'Fallo en store_result(): ' . $stmt->error]));
}

$stmt->bind_result($subcategoria_id, $categoria, $nivel, $subcategoria, $permite_validacion_externa, $cantidad_palabras);

$stats = [];
while ($stmt->fetch()) {
    $stats[] = [
        'subcategoria_id' => $subcategoria_id,
        'categoria' => $categoria,
        'nivel' => $nivel,
        'subcategoria' => $subcategoria,
        'permite_validacion_externa' => $permite_validacion_externa,
        'cantidad_palabras' => $cantidad_palabras
    ];
}

$stmt->close();
$conn->close();

echo json_encode($stats);
?>