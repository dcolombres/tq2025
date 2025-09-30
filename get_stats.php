<?php
/**
 * get_stats.php
 * 
 * Este script se conecta a la base de datos para obtener una lista completa de todas las
 * subcategorías y sus estadísticas asociadas, como la categoría principal, el nivel
 * y la cantidad de palabras que contienen.
 * 
 * Devuelve los datos en formato JSON.
 */

header('Content-Type: application/json');

// --- Conexión a la Base de Datos ---
require_once __DIR__ . '/db_config.php';
$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    http_response_code(500);
    die(json_encode(["error" => "Conexión fallida a la base de datos."]));
}

// --- Consulta Principal ---
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

// Refactor para no usar get_result()
$stmt = $conn->prepare($sql);
if (!$stmt) {
    http_response_code(500);
    die(json_encode(['error' => 'Error al preparar la consulta: ' . $conn->error]));
}

if (!$stmt->execute()) {
    http_response_code(500);
    die(json_encode(['error' => 'Error al ejecutar la consulta: ' . $stmt->error]));
}

$stmt->store_result();
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