<?php
header('Content-Type: application/json');
error_reporting(E_ALL);
ini_set('display_errors', 1);

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

// --- Configuración de la Base de Datos Local ---
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "tuttiquanti";
// -----------------------------------------

// Función para enviar una respuesta de error y terminar el script
function send_error($message) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $message]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    send_error('Método no permitido.');
}

// Obtener datos del formulario (ahora es un textarea llamado 'palabras')
$palabras_str = isset($_POST['palabras']) ? trim($_POST['palabras']) : '';
$subcategoria_id = isset($_POST['subcategoria_id']) ? (int)$_POST['subcategoria_id'] : 0;

if (empty($palabras_str) || $subcategoria_id === 0) {
    send_error('Debes seleccionar una subcategoría e ingresar al menos una palabra.');
}

$conn = null;
try {
    $conn = new mysqli($servername, $username, $password, $dbname);
    $conn->begin_transaction();

    // Obtener la categoria_id de la subcategoría (se hace una vez)
    $categoria_id = null;
    $stmt_cat = $conn->prepare("SELECT categoria_id FROM Subcategorias WHERE id = ?");
    $stmt_cat->bind_param("i", $subcategoria_id);
    $stmt_cat->execute();
    $result_cat = $stmt_cat->get_result();
    if ($row_cat = $result_cat->fetch_assoc()) {
        $categoria_id = $row_cat['categoria_id'];
    } else {
        throw new Exception('La subcategoría seleccionada no es válida.');
    }
    $stmt_cat->close();

    // Preparar la sentencia de inserción se hará DENTRO del bucle para máxima robustez
    $palabras_arr = array_filter(array_map('trim', explode(',', $palabras_str)));
    $created_count = 0;
    $skipped_count = 0;

    foreach ($palabras_arr as $palabra) {
        try {
            $stmt_insert = $conn->prepare("INSERT INTO Palabras (palabra, letra, subcategoria_id, categoria_id) VALUES (?, ?, ?, ?)");
            $letra = mb_strtoupper(mb_substr($palabra, 0, 1, 'UTF-8'), 'UTF-8');
            $stmt_insert->bind_param("ssii", $palabra, $letra, $subcategoria_id, $categoria_id);
            $stmt_insert->execute();
            $created_count++;
            $stmt_insert->close();
        } catch (mysqli_sql_exception $e) {
            if ($e->getCode() == 1062) { // 1062 = Duplicate entry
                $skipped_count++;
            } else {
                throw $e; // Relanzar cualquier otro error
            }
        }
    }
    
    $conn->commit();

    $message = "Proceso completado. Palabras insertadas: $created_count. Omitidas (duplicadas): $skipped_count.";
    echo json_encode(['success' => true, 'message' => $message]);

} catch (Exception $e) {
    if ($conn && $conn->ping()) {
        $conn->rollback();
    }
    send_error("Error de base de datos: " . $e->getMessage());
}

if ($conn) {
    $conn->close();
}
?>