<?php
header('Content-Type: application/json');
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/db_config.php';

function send_error($message) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $message]);
    exit;
}

$type = isset($_GET['type']) ? $_GET['type'] : '';
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (empty($type) || $id === 0) {
    send_error('Parámetros inválidos.');
}

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    http_response_code(500);
    send_error('Error de conexión a la base de datos.');
}

$conn->begin_transaction();
try {
    switch ($type) {
        case 'subcategory':
            // Eliminar palabras asociadas
            $stmt = $conn->prepare("DELETE FROM palabras WHERE subcategoria_id = ?");
            if (!$stmt) throw new Exception('Error al preparar la eliminación de palabras.');
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $stmt->close();

            // Eliminar la subcategoría
            $stmt = $conn->prepare("DELETE FROM subcategorias WHERE id = ?");
            if (!$stmt) throw new Exception('Error al preparar la eliminación de la subcategoría.');
            $stmt->bind_param("i", $id);
            $stmt->execute();
            if ($stmt->affected_rows === 0) throw new Exception('No se encontró la subcategoría para eliminar.');
            $stmt->close();
            break;

        case 'categoria':
        case 'nivel':
            $col_name = ($type === 'categoria') ? 'categoria_id' : 'nivel_id';
            $table_name = ($type === 'categoria') ? 'categorias' : 'niveles';

            // 1. Encontrar todas las subcategorías afectadas
            $stmt = $conn->prepare("SELECT id FROM subcategorias WHERE {$col_name} = ?");
            if (!$stmt) throw new Exception("Error al preparar la búsqueda de subcategorías afectadas.");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $result = $stmt->get_result();
            $subcat_ids = [];
            while ($row = $result->fetch_assoc()) {
                $subcat_ids[] = $row['id'];
            }
            $stmt->close();

            // 2. Si hay subcategorías, eliminar sus palabras
            if (!empty($subcat_ids)) {
                $placeholders = implode(',', array_fill(0, count($subcat_ids), '?'));
                $types = str_repeat('i', count($subcat_ids));
                $stmt_palabras = $conn->prepare("DELETE FROM palabras WHERE subcategoria_id IN ({$placeholders})");
                if (!$stmt_palabras) throw new Exception("Error al preparar la eliminación de palabras en cascada.");
                $stmt_palabras->bind_param($types, ...$subcat_ids);
                $stmt_palabras->execute();
                $stmt_palabras->close();
            }

            // 3. Eliminar las subcategorías afectadas
            $stmt_sub = $conn->prepare("DELETE FROM subcategorias WHERE {$col_name} = ?");
            if (!$stmt_sub) throw new Exception("Error al preparar la eliminación de subcategorías en cascada.");
            $stmt_sub->bind_param("i", $id);
            $stmt_sub->execute();
            $stmt_sub->close();

            // 4. Eliminar la categoría/nivel principal
            $stmt_main = $conn->prepare("DELETE FROM {$table_name} WHERE id = ?");
            if (!$stmt_main) throw new Exception("Error al preparar la eliminación del ítem principal.");
            $stmt_main->bind_param("i", $id);
            $stmt_main->execute();
            if ($stmt_main->affected_rows === 0) throw new Exception("No se encontró el ítem principal para eliminar.");
            $stmt_main->close();
            break;

        default:
            throw new Exception('Tipo de elemento no soportado.');
    }

    $conn->commit();
    echo json_encode(['success' => true, 'message' => ucfirst($type) . ' y todos sus datos asociados eliminados correctamente.']);

} catch (Exception $e) {
    $conn->rollback();
    send_error($e->getMessage());
}

$conn->close();
?>
