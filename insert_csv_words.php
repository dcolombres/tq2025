<?php
header('Content-Type: application/json');
require_once __DIR__ . '/db_config.php';

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    http_response_code(500);
    die(json_encode(['success' => false, 'message' => 'Conexión fallida: ' . $conn->connect_error]));
}

$subcategoria_id = isset($_POST['subcategoria_id']) ? (int)$_POST['subcategoria_id'] : 0;
$palabras_str = isset($_POST['palabras']) ? trim($_POST['palabras']) : '';

if ($subcategoria_id <= 0 || empty($palabras_str)) {
    http_response_code(400);
    die(json_encode(['success' => false, 'message' => 'Faltan datos. Asegúrate de seleccionar una subcategoría e ingresar palabras.']));
}

$palabras_array = array_map('trim', explode(',', $palabras_str));
$palabras_array = array_filter($palabras_array, function($value) { return !empty($value); });
$palabras_array = array_unique($palabras_array);

if (empty($palabras_array)) {
    http_response_code(400);
    die(json_encode(['success' => false, 'message' => 'No se encontraron palabras válidas para insertar.']));
}

$conn->begin_transaction();

try {
    // La tabla `palabras` requiere `subcategoria_id`, `palabra` y `letra`.
    // La columna `validada` no existe en la estructura que revisé.
    // Usamos INSERT IGNORE para evitar errores si una palabra duplicada se intenta insertar (requiere un índice UNIQUE en `palabra` y `subcategoria_id` para funcionar).
    $sql = "INSERT IGNORE INTO palabras (subcategoria_id, palabra, letra) VALUES (?, ?, ?)";
    $stmt = $conn->prepare($sql);

    if (!$stmt) {
        throw new Exception('Error en la preparación de la consulta: ' . $conn->error);
    }

    $inserted_count = 0;
    foreach ($palabras_array as $palabra) {
        // Extraer la primera letra de la palabra
        $letra = mb_substr(strtoupper($palabra), 0, 1);
        
        $stmt->bind_param("iss", $subcategoria_id, $palabra, $letra);
        $stmt->execute();
        if ($stmt->affected_rows > 0) {
            $inserted_count++;
        }
    }

    $stmt->close();
    $conn->commit();

    $total_count = count($palabras_array);
    $skipped_count = $total_count - $inserted_count;

    $message = "Proceso completado. Palabras nuevas insertadas: $inserted_count.";
    if ($skipped_count > 0) {
        $message .= " Palabras duplicadas omitidas: $skipped_count.";
    }

    echo json_encode(['success' => true, 'message' => $message]);

} catch (Exception $e) {
    $conn->rollback();
    http_response_code(500);
    // Devolvemos el mensaje de error de la excepción para depuración
    echo json_encode(['success' => false, 'message' => 'Error en la base de datos: ' . $e->getMessage()]);
}

$conn->close();
?>
