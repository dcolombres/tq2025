<?php
header('Content-Type: application/json');
error_reporting(0);
ini_set('display_errors', 0);

require_once __DIR__ . '/db_config.php';
$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    http_response_code(500);
    die(json_encode(["error" => "Conexión fallida."]));
}

$categoria_id = isset($_GET['categoria_id']) ? (int)$_GET['categoria_id'] : 0;

if ($categoria_id > 0) {
    $sql = "SELECT id, nombre, permite_validacion_externa FROM subcategorias WHERE categoria_id = ? ORDER BY nombre ASC";
    $stmt = $conn->prepare($sql);
    
    if (!$stmt) {
        http_response_code(500);
        die(json_encode(["error" => "Error en la preparación de la consulta."]));
    }

    $stmt->bind_param("i", $categoria_id);
    $stmt->execute();
    $stmt->store_result();
    
    $stmt->bind_result($id, $nombre, $permite_validacion);

    $subcategorias = [];
    while ($stmt->fetch()) {
        $subcategorias[] = [
            'id' => $id,
            'nombre' => $nombre,
            'permite_validacion_externa' => $permite_validacion
        ];
    }
    
    echo json_encode($subcategorias);
    
    $stmt->close();

} else {
    echo json_encode([]);
}

$conn->close();
?>