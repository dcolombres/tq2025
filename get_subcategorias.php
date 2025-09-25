<?php
header('Content-Type: application/json');
error_reporting(E_ALL);
ini_set('display_errors', 1);

// --- Configuración de la Base de Datos Local ---
$servername = "localhost";
$username = "root"; // Usuario por defecto en XAMPP
$password = ""; // Contraseña por defecto en XAMPP es vacía
$dbname = "tuttiquanti";
// -----------------------------------------

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
    $sql = "SELECT id, nombre FROM Subcategorias WHERE categoria_id = ?";
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
    // Si no se proporciona un categoria_id, podríamos devolver todas las subcategorías o un error.
    // Por ahora, devolvemos un array vacío o un mensaje de error.
    // Para el formulario, es mejor devolver todas las subcategorías si no hay filtro.
    $sql = "SELECT id, nombre, categoria_id FROM Subcategorias ORDER BY nombre ASC";
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