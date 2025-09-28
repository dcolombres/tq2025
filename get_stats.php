<?php
header('Content-Type: application/json');
error_reporting(E_ALL);
ini_set('display_errors', 1);

// --- Configuración de la Base de Datos Local ---
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "tuttiquanti";
// -----------------------------------------

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    http_response_code(500);
    die(json_encode(["error" => "Conexión fallida: " . $conn->connect_error]));
}

// Consulta para obtener las estadísticas
$sql = "
    SELECT 
        s.id AS subcategoria_id,
        c.nombre AS categoria, 
        n.nombre AS nivel,
        s.nombre AS subcategoria, 
        s.permite_validacion_externa,
        COUNT(p.id) AS cantidad_palabras
    FROM 
        Subcategorias s
    JOIN 
        Categorias c ON s.categoria_id = c.id
    LEFT JOIN
        Niveles n ON s.nivel_id = n.id
    LEFT JOIN 
        Palabras p ON s.id = p.subcategoria_id
    GROUP BY 
        s.id, c.nombre, n.nombre, s.nombre, s.permite_validacion_externa
    ORDER BY 
        c.nombre, n.nombre, s.nombre;
";

$result = $conn->query($sql);

$stats = [];
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $stats[] = $row;
    }
}

echo json_encode($stats);

$conn->close();
?>
