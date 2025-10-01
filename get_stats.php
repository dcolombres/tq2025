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
error_reporting(0);
ini_set('display_errors', 0);

// --- Conexión a la Base de Datos ---
require_once __DIR__ . '/db_config.php';
$conn = new mysqli($servername, $username, $password, $dbname);

// Verificar la conexión
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

$result = $conn->query($sql);

// --- Procesamiento de Resultados ---
$stats = [];
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $stats[] = $row;
    }
}

// --- Respuesta JSON ---
echo json_encode($stats);

$conn->close();
?>