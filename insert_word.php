<?php
/**
 * insert_word.php
 * 
 * Este script maneja la inserción de una o más palabras en una subcategoría específica.
 * Recibe los datos desde un formulario, incluyendo el ID de la subcategoría y un string
 * de palabras separadas por coma. Procesa cada palabra y la inserta en la BD.
 */

header('Content-Type: application/json');
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Forza a MySQLi a lanzar excepciones para un mejor manejo de errores en bloques try-catch
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

// --- Conexión a la Base de Datos ---
require_once __DIR__ . '/db_config.php';

// --- Funciones de Ayuda ---
function send_error($message) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $message]);
    exit;
}

// --- Lógica Principal ---

// Validar que la petición sea de tipo POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    send_error('Método no permitido.');
}

// Obtener y validar los datos del formulario
$palabras_str = isset($_POST['palabras']) ? trim($_POST['palabras']) : '';
$subcategoria_id = isset($_POST['subcategoria_id']) ? (int)$_POST['subcategoria_id'] : 0;

if (empty($palabras_str) || $subcategoria_id === 0) {
    send_error('Debes seleccionar una subcategoría e ingresar al menos una palabra.');
}

$conn = null;
try {
    // Iniciar conexión y transacción para asegurar la integridad de los datos
    $conn = new mysqli($servername, $username, $password, $dbname);
    $conn->begin_transaction();

    // Obtener la categoria_id de la subcategoría (se necesita para la tabla 'palabras')
    $categoria_id = null;
    $stmt_cat = $conn->prepare("SELECT categoria_id FROM subcategorias WHERE id = ?");
    $stmt_cat->bind_param("i", $subcategoria_id);
    $stmt_cat->execute();
    $stmt_cat->store_result();
    $stmt_cat->bind_result($categoria_id);
    
    if (!$stmt_cat->fetch()) {
        throw new Exception('La subcategoría seleccionada no es válida.');
    }
    $stmt_cat->close();

    // Procesar e insertar cada palabra
    $palabras_arr = array_filter(array_map('trim', explode(',', $palabras_str)));
    $created_count = 0;
    $skipped_count = 0;

    foreach ($palabras_arr as $palabra) {
        try {
            $stmt_insert = $conn->prepare("INSERT INTO palabras (palabra, letra, subcategoria_id, categoria_id) VALUES (?, ?, ?, ?)");
            $letra = mb_strtoupper(mb_substr($palabra, 0, 1, 'UTF-8'), 'UTF-8');
            $stmt_insert->bind_param("ssii", $palabra, $letra, $subcategoria_id, $categoria_id);
            $stmt_insert->execute();
            $created_count++;
            $stmt_insert->close();
        } catch (mysqli_sql_exception $e) {
            if ($e->getCode() == 1062) { // 1062 = Duplicate entry for a UNIQUE index
                $skipped_count++; // La palabra ya existía, la omitimos
            } else {
                throw $e; // Si es otro tipo de error, lo relanzamos para que lo capture el catch principal
            }
        }
    }
    
    // Si todo fue bien, confirmar la transacción
    $conn->commit();

    $message = "Proceso completado. Palabras insertadas: $created_count. Omitidas (duplicadas): $skipped_count.";
    echo json_encode(['success' => true, 'message' => $message]);

} catch (Exception $e) {
    // Si algo falla, revertir todos los cambios de la transacción
    if ($conn && $conn->ping()) {
        $conn->rollback();
    }
    send_error("Error de base de datos: " . $e->getMessage());
}

// Cerrar la conexión
if ($conn) {
    $conn->close();
}
?>