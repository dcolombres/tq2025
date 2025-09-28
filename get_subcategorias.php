<?php
header('Content-Type: application/json');
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/db_config.php';

// Crear conexión
$conn = new mysqli($servername, $username, $password, $dbname);

// Verificar conexión
if ($conn->connect_error) {
    http_response_code(500);
    die(json_encode(["error" => "Conexión fallida: " . $conn->connect_error]));
}

// Obtener el ID de la categoría de los parámetros GET
$categoria_id = isset($_GET['categoria_id']) ? (int)$_GET['categoria_id'] : 0;

if ($categoria_id > 0) {
    // Preparar la consulta para evitar inyección SQL
    $sql = "SELECT id, nombre FROM subcategorias WHERE categoria_id = ?";
    $stmt = $conn->prepare($sql);
    
    if (!$stmt) {
        http_response_code(500);
        die(json_encode(["error" => "Error en la preparación de la consulta: " . $conn->error]));
    }

    $stmt->bind_param("i", $categoria_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $subcategorias = [];
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $subcategorias[] = $row;
        }
    }
    
    echo json_encode($subcategorias);
    
    $stmt->close();

} else {
    // Si no se proporciona un categoria_id, devolvemos un array vacío.
    echo json_encode([]);
}

$conn->close();
?>