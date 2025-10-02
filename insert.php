<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
require_once __DIR__ . '/db_config.php';

$message = '';
$message_type = 'success';
$selected_subcategoria_id = (int)($_GET['subcategoria_id'] ?? 0);

// --- ACTION HANDLING ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['form_action'])) {
    $conn = new mysqli($servername, $username, $password, $dbname);
    if ($conn->connect_error) {
        $message = "Error de Conexión: " . $conn->connect_error;
        $message_type = 'error';
    } else {
        $conn->set_charset("utf8mb4");
        $action = $_POST['form_action'];
        $subcategoria_id_post = (int)($_POST['subcategoria_id'] ?? 0);
        try {
            $conn->begin_transaction();
            if ($action === 'bulk_add') {
                $palabras_str = trim($_POST['palabras'] ?? '');
                if ($subcategoria_id_post > 0 && !empty($palabras_str)) {
                    $palabras_arr = array_filter(array_unique(array_map('trim', explode(',', $palabras_str))));
                    $stmt = $conn->prepare("INSERT IGNORE INTO palabras (subcategoria_id, palabra, letra) VALUES (?, ?, ?)");
                    $inserted_count = 0;
                    foreach ($palabras_arr as $palabra) {
                        if(empty($palabra)) continue;
                        $letra = mb_strtoupper(mb_substr($palabra, 0, 1, 'UTF-8'), 'UTF-8');
                        $stmt->bind_param("iss", $subcategoria_id_post, $palabra, $letra);
                        $stmt->execute();
                        if ($stmt->affected_rows > 0) $inserted_count++;
                    }
                    $stmt->close();
                    $skipped_count = count($palabras_arr) - $inserted_count;
                    $message = "Proceso completado. Palabras nuevas: $inserted_count. Duplicadas omitidas: $skipped_count.";
                }
            } elseif ($action === 'delete_word') {
                $word_id = (int)($_POST['word_id'] ?? 0);
                if ($word_id > 0) {
                    $stmt = $conn->prepare("DELETE FROM palabras WHERE id = ?");
                    $stmt->bind_param("i", $word_id);
                    $stmt->execute();
                    $stmt->close();
                    $message = "Palabra eliminada.";
                }
            } elseif ($action === 'update_word') {
                $word_id = (int)($_POST['word_id'] ?? 0);
                $palabra = trim($_POST['palabra'] ?? '');
                if ($word_id > 0 && !empty($palabra)) {
                    $letra = mb_strtoupper(mb_substr($palabra, 0, 1, 'UTF-8'), 'UTF-8');
                    $stmt = $conn->prepare("UPDATE palabras SET palabra = ?, letra = ? WHERE id = ?");
                    $stmt->bind_param("ssi", $palabra, $letra, $word_id);
                    $stmt->execute();
                    $stmt->close();
                    $message = "Palabra actualizada.";
                }
            }
            $conn->commit();
        } catch (Exception $e) {
            if ($conn->ping()) $conn->rollback();
            $message = "Error en la operación: " . $e->getMessage();
            $message_type = 'error';
        }
        if ($conn->ping()) $conn->close();
        header("Location: " . $_SERVER['PHP_SELF'] . '?subcategoria_id=' . $subcategoria_id_post);
        exit();
    }
}

function get_subcategorias_options($selected_id) { 
    require __DIR__ . '/db_config.php'; 
    $conn = new mysqli($servername, $username, $password, $dbname); 
    if ($conn->connect_error) return '<option value="">Error DB</option>'; 
    $conn->set_charset("utf8mb4"); 
    $sql = "SELECT s.id, s.nombre, n.nombre as nivel_nombre, c.nombre as categoria_nombre FROM subcategorias s LEFT JOIN niveles n ON s.nivel_id = n.id LEFT JOIN categorias c ON s.categoria_id = c.id ORDER BY c.nombre, n.nombre, s.nombre";
    $stmt = $conn->prepare($sql); 
    if (!$stmt || !$stmt->execute()) { return '<option value="">ERROR</option>'; }
    $stmt->store_result();
    $stmt->bind_result($id, $nombre, $nivel_nombre, $categoria_nombre);
    $options = '<option value="">Selecciona una subcategoría...</option>';
    while ($stmt->fetch()) {
        $display_name = htmlspecialchars("$categoria_nombre > $nivel_nombre > $nombre");
        $selected_attr = ($id == $selected_id) ? ' selected' : '';
        $options .= sprintf('<option value="%d"%s>%s</option>', $id, $selected_attr, $display_name);
    }
    $stmt->close(); $conn->close(); return $options;
}
function getWordsForSubcategory($id) { require __DIR__ . '/db_config.php'; $conn = new mysqli($servername, $username, $password, $dbname); if ($conn->connect_error) return []; $conn->set_charset("utf8mb4"); $sql = "SELECT id, palabra FROM palabras WHERE subcategoria_id = ? ORDER BY palabra ASC"; $stmt = $conn->prepare($sql); $stmt->bind_param("i", $id); $stmt->execute(); $stmt->store_result(); $stmt->bind_result($word_id, $palabra); $words = []; while ($stmt->fetch()) { $words[] = ['id' => $word_id, 'palabra' => $palabra]; } $stmt->close(); $conn->close(); return $words; }

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Gestión de Palabras</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="style.css">
    <style> body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif; background-color: #f4f7f9; margin: 0; padding: 2rem; } .main-container { margin: auto; width: 100%; max-width: 800px; } .container { background-color: #fff; padding: 2rem; border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.08); margin-bottom: 2rem; } h1, h2 { color: #3F2A56; text-align: center; margin-bottom: 1.5rem; } .status-message { margin-bottom: 1.5rem; text-align: center; font-weight: 600; padding: 0.75rem; border-radius: 4px; } .status-success { background-color: #d4edda; color: #155724; } .status-error { background-color: #f8d7da; color: #721c24; } </style>
</head>
<body>
<div class="main-container">
    <div class="page-header"><a href="index.php"><img src="https://moroarte.com/wp-content/uploads/2023/09/logoTUTTIQUANTI-154x300.png" alt="Tutti Quanti Logo" class="logo"></a><h1>Gestión de Palabras</h1></div>
    <?php if (!empty($message)): ?><div class="status-message status-<?php echo $message_type; ?>"><?php echo htmlspecialchars($message); ?></div><?php endif; ?>
    <div class="container">
        <h2>1. Selecciona una Subcategoría</h2>
        <form method="GET" action="insert.php">
            <select name="subcategoria_id" onchange="this.form.submit()" style="width:100%; padding:10px; font-size:1.1rem;">
                <?php echo get_subcategorias_options($selected_subcategoria_id); ?>
            </select>
        </form>
    </div>

    <?php if ($selected_subcategoria_id): 
        $words = getWordsForSubcategory($selected_subcategoria_id);
    ?>
        <div class="container">
            <h2>2. Añadir Palabras en Lote</h2>
            <form method="POST"><input type="hidden" name="form_action" value="bulk_add"><input type="hidden" name="subcategoria_id" value="<?php echo $selected_subcategoria_id; ?>"><textarea name="palabras" rows="5" placeholder="Palabra1, Palabra2, ..." style="width:100%; padding:10px; font-size:1rem; margin-bottom:10px;"></textarea><button type="submit">Añadir Palabras</button></form>
        </div>
        <div class="container">
            <h2>3. Palabras Existentes (<?php echo count($words); ?>)</h2>
            <table style="width:100%; border-collapse:collapse;">
                <thead><tr style="background-color:#3F2A56; color:white;"><th style="padding:12px;">Palabra</th><th style="width:100px; text-align:center; padding:12px;">Acciones</th></tr></thead>
                <tbody>
                    <?php if(empty($words)): ?>
                        <tr><td colspan="2" style="text-align:center; padding:20px;">No hay palabras para esta subcategoría.</td></tr>
                    <?php else: foreach($words as $word): ?>
                        <tr>
                            <td style="padding:8px;"><form method="POST" style="display:flex; gap:10px;"><input type="hidden" name="form_action" value="update_word"><input type="hidden" name="word_id" value="<?php echo $word['id']; ?>"><input type="hidden" name="subcategoria_id" value="<?php echo $selected_subcategoria_id; ?>"><input type="text" name="palabra" value="<?php echo htmlspecialchars($word['palabra']); ?>" style="flex-grow:1; padding:8px;"><button type="submit" class="btn-edit" style="padding:8px;"><i class="fas fa-save"></i></button></form></td>
                            <td style="text-align:center; padding:8px;"><form method="POST" style="display:inline;" onsubmit="return confirm('¿Eliminar esta palabra?');"><input type="hidden" name="form_action" value="delete_word"><input type="hidden" name="word_id" value="<?php echo $word['id']; ?>"><input type="hidden" name="subcategoria_id" value="<?php echo $selected_subcategoria_id; ?>"><button type="submit" class="btn-delete"><i class="fas fa-trash-alt"></i></button></form></td>
                        </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>
</body>
</html>