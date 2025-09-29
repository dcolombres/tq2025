<?php
header('Content-Type: application/json');
error_reporting(0);
ini_set('display_errors', 0);
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

require_once __DIR__ . '/db_config.php';

function send_error($message) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $message]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    send_error('Método no permitido.');
}

$categoria_nombre = isset($_POST['categoria_principal']) ? trim($_POST['categoria_principal']) : '';
$nivel_nombre = isset($_POST['nivel']) ? trim($_POST['nivel']) : '';
$subcategoria_str = isset($_POST['subcategoria']) ? trim($_POST['subcategoria']) : '';

if (empty($categoria_nombre) || empty($nivel_nombre) || empty($subcategoria_str)) {
    send_error('Todos los campos son obligatorios.');
}

$conn = null;
try {
    $conn = new mysqli($servername, $username, $password, $dbname);
    $conn->begin_transaction();

    // --- Step 1: Get or Create Main Category ---
    $categoria_id = null;
    $stmt = $conn->prepare("SELECT id FROM categorias WHERE nombre = ?");
    $stmt->bind_param("s", $categoria_nombre);
    $stmt->execute();
    $stmt->store_result();
    $stmt->bind_result($categoria_id);
    if (!$stmt->fetch()) {
        $stmt->close();
        $stmt_insert = $conn->prepare("INSERT INTO categorias (nombre) VALUES (?)");
        $stmt_insert->bind_param("s", $categoria_nombre);
        $stmt_insert->execute();
        $categoria_id = $conn->insert_id;
        $stmt_insert->close();
    } else {
        $stmt->close();
    }

    // --- Step 2: Get or Create Level ---
    $nivel_id = null;
    $stmt = $conn->prepare("SELECT id FROM niveles WHERE nombre = ?");
    $stmt->bind_param("s", $nivel_nombre);
    $stmt->execute();
    $stmt->store_result();
    $stmt->bind_result($nivel_id);
    if (!$stmt->fetch()) {
        $stmt->close();
        $stmt_insert = $conn->prepare("INSERT INTO niveles (nombre) VALUES (?)");
        $stmt_insert->bind_param("s", $nivel_nombre);
        $stmt_insert->execute();
        $nivel_id = $conn->insert_id;
        $stmt_insert->close();
    } else {
        $stmt->close();
    }

    // --- Step 3: Create Subcategories ---
    $subcategoria_nombres = array_filter(array_map('trim', explode(',', $subcategoria_str)));
    $created_count = 0;
    $skipped_count = 0;

    foreach ($subcategoria_nombres as $subcategoria_nombre) {
        $stmt_check = $conn->prepare("SELECT id FROM subcategorias WHERE nombre = ? AND categoria_id = ? AND nivel_id = ?");
        $stmt_check->bind_param("sii", $subcategoria_nombre, $categoria_id, $nivel_id);
        $stmt_check->execute();
        $stmt_check->store_result();
        if ($stmt_check->fetch()) {
            $skipped_count++;
        } else {
            $stmt_insert = $conn->prepare("INSERT INTO subcategorias (nombre, categoria_id, nivel_id) VALUES (?, ?, ?)");
            $stmt_insert->bind_param("sii", $subcategoria_nombre, $categoria_id, $nivel_id);
            $stmt_insert->execute();
            $created_count++;
            $stmt_insert->close();
        }
        $stmt_check->close();
    }

    $conn->commit();
    $message = "Proceso completado. Subcategorías creadas: $created_count. Omitidas (duplicadas): $skipped_count.";
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