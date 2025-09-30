<?php
header('Content-Type: application/json');
require_once __DIR__ . '/db_config.php';

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    http_response_code(500);
    die(json_encode(['error' => 'Conexión fallida: ' . $conn->connect_error]));
}

$get_all = isset($_GET['all']) && $_GET['all'] === 'true';

if ($get_all) {
    $sql = "SELECT s.id, s.nombre, n.nombre as nivel_nombre, c.nombre as categoria_nombre
            FROM subcategorias s
            JOIN niveles n ON s.nivel_id = n.id
            JOIN categorias c ON s.categoria_id = c.id
            ORDER BY c.nombre, n.nombre, s.nombre";
    $stmt = $conn->prepare($sql);

    if (!$stmt) {
        http_response_code(500);
        die(json_encode(['error' => 'Error en la preparación de la consulta (prepare): ' . $conn->error]));
    }

    if (!$stmt->execute()) {
        http_response_code(500);
        die(json_encode(['error' => 'Error en la ejecución de la consulta (execute): ' . $stmt->error]));
    }

    // Refactor to avoid get_result()
    $stmt->store_result();
    
    if ($stmt->num_rows > 0) {
        $stmt->bind_result($id, $nombre, $nivel_nombre, $categoria_nombre);
        
        $subcategorias = [];
        while ($stmt->fetch()) {
            $subcategorias[] = [
                'id' => $id,
                'nombre' => $nombre,
                'nivel_nombre' => $nivel_nombre,
                'categoria_nombre' => $categoria_nombre
            ];
        }
        echo json_encode($subcategorias);
    } else {
        echo json_encode([]);
    }
    
    $stmt->close();

} else {
    // This part is for the old functionality, refactoring it as well for consistency.
    $categoria_id = isset($_GET['categoria_id']) ? (int)$_GET['categoria_id'] : 0;
    if ($categoria_id > 0) {
        $sql = "SELECT id, nombre, permite_validacion_externa FROM subcategorias WHERE categoria_id = ? ORDER BY nombre ASC";
        $stmt = $conn->prepare($sql);
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
}

$conn->close();
?>