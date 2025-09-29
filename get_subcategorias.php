<?php
/**
 * get_subcategorias.php
 * 
 * Este script devuelve una lista de subcategorías en formato JSON.
 * Si se proporciona un parámetro GET 'categoria_id', filtra las subcategorías
 * que pertenecen a esa categoría principal.
 * Si no se proporciona, devuelve una lista vacía.
 */

header('Content-Type: application/json');
error_reporting(E_ALL);
ini_set('display_errors', 1);

// --- Conexión a la Base de Datos ---
require_once __DIR__ . '/db_config.php';
$conn = new mysqli($servername, $username, $password, $dbname);

// Verificar la conexión
if ($conn->connect_error) {
    http_response_code(500);
    die(json_encode(["error" => "Conexión fallida a la base de datos."]));
}

// --- Lógica Principal ---

// Obtener el ID de la categoría de los parámetros de la URL
$categoria_id = isset($_GET['categoria_id']) ? (int)$_GET['categoria_id'] : 0;

// Si se especificó un ID de categoría, buscar sus subcategorías
if ($categoria_id > 0) {
    
    // Preparar la consulta para evitar inyección SQL
    $sql = "SELECT id, nombre, permite_validacion_externa FROM subcategorias WHERE categoria_id = ? ORDER BY nombre ASC";
    $stmt = $conn->prepare($sql);
    
    if (!$stmt) {
        http_response_code(500);
        die(json_encode(["error" => "Error en la preparación de la consulta: " . $conn->error]));
    }

    // Ejecutar la consulta
    $stmt->bind_param("i", $categoria_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    // Procesar los resultados y guardarlos en un array
    $subcategorias = [];
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $subcategorias[] = $row;
        }
    }
    
    // Devolver el array de subcategorías como JSON
    echo json_encode($subcategorias);
    
    $stmt->close();

} else {
    // Si no se proporciona un categoria_id, devolver un array JSON vacío.
    echo json_encode([]);
}

$conn->close();
?>