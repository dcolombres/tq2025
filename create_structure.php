<?php
/**
 * create_structure.php
 * 
 * Este script maneja la creación de la estructura de categorías, niveles y subcategorías.
 * Recibe los nombres desde un formulario y, si no existen, los crea en la base de datos.
 * Está diseñado para ser robusto, reutilizando ítems existentes si coinciden los nombres.
 */

header('Content-Type: application/json');
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Forza a MySQLi a lanzar excepciones en lugar de errores silenciosos para un mejor manejo de errores.
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
$categoria_nombre = isset($_POST['categoria_principal']) ? trim($_POST['categoria_principal']) : '';
$nivel_nombre = isset($_POST['nivel']) ? trim($_POST['nivel']) : '';
$subcategoria_str = isset($_POST['subcategoria']) ? trim($_POST['subcategoria']) : '';

if (empty($categoria_nombre) || empty($nivel_nombre) || empty($subcategoria_str)) {
    send_error('Todos los campos son obligatorios.');
}

$conn = null;
try {
    // Iniciar conexión y transacción
    $conn = new mysqli($servername, $username, $password, $dbname);
    $conn->begin_transaction();

    // --- Paso 1: Obtener o Crear la Categoría Principal ---
    $stmt = $conn->prepare("SELECT id FROM categorias WHERE nombre = ?");
    $stmt->bind_param("s", $categoria_nombre);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        $categoria_id = $row['id'];
    } else {
        $stmt_insert = $conn->prepare("INSERT INTO categorias (nombre) VALUES (?)");
        $stmt_insert->bind_param("s", $categoria_nombre);
        $stmt_insert->execute();
        $categoria_id = $conn->insert_id;
        $stmt_insert->close();
    }
    $stmt->close();

    // --- Paso 2: Obtener o Crear el Nivel ---
    $stmt = $conn->prepare("SELECT id FROM niveles WHERE nombre = ?");
    $stmt->bind_param("s", $nivel_nombre);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        $nivel_id = $row['id'];
    } else {
        $stmt_insert = $conn->prepare("INSERT INTO niveles (nombre) VALUES (?)");
        $stmt_insert->bind_param("s", $nivel_nombre);
        $stmt_insert->execute();
        $nivel_id = $conn->insert_id;
        $stmt_insert->close();
    }
    $stmt->close();

    // --- Paso 3: Crear las Subcategorías ---
    $subcategoria_nombres = array_filter(array_map('trim', explode(',', $subcategoria_str)));
    $created_count = 0;
    $skipped_count = 0;

    foreach ($subcategoria_nombres as $subcategoria_nombre) {
        // Primero, verificar si ya existe esta combinación exacta para no crear duplicados
        $stmt_check = $conn->prepare("SELECT id FROM subcategorias WHERE nombre = ? AND categoria_id = ? AND nivel_id = ?");
        $stmt_check->bind_param("sii", $subcategoria_nombre, $categoria_id, $nivel_id);
        $stmt_check->execute();
        if ($stmt_check->get_result()->fetch_assoc()) {
            $skipped_count++; // Si existe, se omite
        } else {
            // Si no existe, se inserta
            $stmt_insert = $conn->prepare("INSERT INTO subcategorias (nombre, categoria_id, nivel_id) VALUES (?, ?, ?)");
            $stmt_insert->bind_param("sii", $subcategoria_nombre, $categoria_id, $nivel_id);
            $stmt_insert->execute();
            $created_count++;
            $stmt_insert->close();
        }
        $stmt_check->close();
    }

    // Si todo fue bien, confirmar la transacción
    $conn->commit();
    $message = "Proceso completado. Subcategorías creadas: $created_count. Omitidas (duplicadas): $skipped_count.";
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
