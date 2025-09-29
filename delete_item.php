<?php
/**
 * delete_item.php
 * 
 * Este script maneja la eliminación de ítems estructurales como categorías, niveles y subcategorías.
 * Es una operación delicada, ya que la eliminación de una categoría o nivel principal
 * resulta en la eliminación en cascada de todas las subcategorías y palabras asociadas.
 * Utiliza transacciones para asegurar la integridad de la base de datos.
 */

header('Content-Type: application/json');
error_reporting(E_ALL);
ini_set('display_errors', 1);

// --- Conexión a la Base de Datos ---
require_once __DIR__ . '/db_config.php';

// --- Funciones de Ayuda ---
function send_error($message) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $message]);
    exit;
}

// --- Lógica Principal ---

// Obtener y validar parámetros de la URL
$type = isset($_GET['type']) ? $_GET['type'] : '';
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (empty($type) || $id === 0) {
    send_error('Parámetros inválidos (se requiere tipo e ID).');
}

// Iniciar conexión
$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    http_response_code(500);
    send_error('Error de conexión a la base de datos.');
}

// Iniciar una transacción para asegurar que todas las operaciones se completen con éxito o ninguna lo haga.
$conn->begin_transaction();
try {
    switch ($type) {
        case 'subcategory':
            // Si es una subcategoría, el proceso es más simple.
            // 1. Eliminar todas las palabras que pertenecen a esta subcategoría.
            $stmt = $conn->prepare("DELETE FROM palabras WHERE subcategoria_id = ?");
            if (!$stmt) throw new Exception('Error al preparar la eliminación de palabras.');
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $stmt->close();

            // 2. Eliminar la subcategoría misma.
            $stmt = $conn->prepare("DELETE FROM subcategorias WHERE id = ?");
            if (!$stmt) throw new Exception('Error al preparar la eliminación de la subcategoría.');
            $stmt->bind_param("i", $id);
            $stmt->execute();
            if ($stmt->affected_rows === 0) throw new Exception('No se encontró la subcategoría para eliminar.');
            $stmt->close();
            break;

        case 'categoria':
        case 'nivel':
            // Si es una categoría o nivel principal, la eliminación es en cascada.
            $col_name = ($type === 'categoria') ? 'categoria_id' : 'nivel_id';
            $table_name = ($type === 'categoria') ? 'categorias' : 'niveles';

            // 1. Encontrar todas las subcategorías que serán afectadas.
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

            // 2. Si se encontraron subcategorías, eliminar todas sus palabras.
            if (!empty($subcat_ids)) {
                $placeholders = implode(',', array_fill(0, count($subcat_ids), '?'));
                $types = str_repeat('i', count($subcat_ids));
                $stmt_palabras = $conn->prepare("DELETE FROM palabras WHERE subcategoria_id IN ({$placeholders})");
                if (!$stmt_palabras) throw new Exception("Error al preparar la eliminación de palabras en cascada.");
                $stmt_palabras->bind_param($types, ...$subcat_ids);
                $stmt_palabras->execute();
                $stmt_palabras->close();
            }

            // 3. Eliminar las subcategorías afectadas.
            $stmt_sub = $conn->prepare("DELETE FROM subcategorias WHERE {$col_name} = ?");
            if (!$stmt_sub) throw new Exception("Error al preparar la eliminación de subcategorías en cascada.");
            $stmt_sub->bind_param("i", $id);
            $stmt_sub->execute();
            $stmt_sub->close();

            // 4. Finalmente, eliminar la categoría o nivel principal.
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

    // Si todas las operaciones fueron exitosas, confirmar la transacción.
    $conn->commit();
    echo json_encode(['success' => true, 'message' => ucfirst($type) . ' y todos sus datos asociados eliminados correctamente.']);

} catch (Exception $e) {
    // Si algo falló, revertir todos los cambios para mantener la integridad de la BD.
    $conn->rollback();
    send_error($e->getMessage());
}

$conn->close();
?>
