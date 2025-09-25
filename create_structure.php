<?php
header('Content-Type: application/json');
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Forza a MySQLi a lanzar excepciones en lugar de errores silenciosos
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

// --- Configuración de la Base de Datos Local ---
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "tuttiquanti";
// -----------------------------------------

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

    // Get or Create Categoria
    $stmt = $conn->prepare("SELECT id FROM Categorias WHERE nombre = ?");
    $stmt->bind_param("s", $categoria_nombre);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        $categoria_id = $row['id'];
    } else {
        $stmt_insert = $conn->prepare("INSERT INTO Categorias (nombre) VALUES (?)");
        $stmt_insert->bind_param("s", $categoria_nombre);
        $stmt_insert->execute();
        $categoria_id = $conn->insert_id;
        $stmt_insert->close();
    }
    $stmt->close();

    // Get or Create Nivel
    $stmt = $conn->prepare("SELECT id FROM Niveles WHERE nombre = ?");
    $stmt->bind_param("s", $nivel_nombre);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        $nivel_id = $row['id'];
    } else {
        $stmt_insert = $conn->prepare("INSERT INTO Niveles (nombre) VALUES (?)");
        $stmt_insert->bind_param("s", $nivel_nombre);
        $stmt_insert->execute();
        $nivel_id = $conn->insert_id;
        $stmt_insert->close();
    }
    $stmt->close();

    // Create Subcategories
    $subcategoria_nombres = array_filter(array_map('trim', explode(',', $subcategoria_str)));
    $created_count = 0;
    $skipped_count = 0;

    foreach ($subcategoria_nombres as $subcategoria_nombre) {
        $stmt_check = $conn->prepare("SELECT id FROM Subcategorias WHERE nombre = ? AND categoria_id = ? AND nivel_id = ?");
        $stmt_check->bind_param("sii", $subcategoria_nombre, $categoria_id, $nivel_id);
        $stmt_check->execute();
        if ($stmt_check->get_result()->fetch_assoc()) {
            $skipped_count++;
        } else {
            $stmt_insert = $conn->prepare("INSERT INTO Subcategorias (nombre, categoria_id, nivel_id) VALUES (?, ?, ?)");
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
