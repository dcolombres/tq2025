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
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $subcategorias[] = $row;
        }
        echo json_encode($subcategorias);
    } else {
        if ($conn->error) {
            die(json_encode(["error" => "DEBUG: Error de base de datos después de la consulta: " . $conn->error]));
        } else {
            die(json_encode(["debug_message" => "DEBUG: La consulta se ejecutó correctamente pero no devolvió filas para el categoria_id: " . $categoria_id]));
        }
    }
    
    $stmt->close();

} else {
    // Si no se proporciona un categoria_id, podríamos devolver todas las subcategorías o un error.
    // Por ahora, devolvemos un array vacío o un mensaje de error.
    // Para el formulario, es mejor devolver todas las subcategorías si no hay filtro.
    $sql = "SELECT id, nombre, categoria_id FROM subcategorias ORDER BY nombre ASC";
    $result = $conn->query($sql);

    $subcategorias = [];
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $subcategorias[] = $row;
        }
    }
    echo json_encode($subcategorias);
}

$conn->close();
?>