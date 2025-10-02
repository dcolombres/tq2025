<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
require_once __DIR__ . '/db_config.php';

$message = '';
$message_type = 'success';

// --- ACTION HANDLING ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['form_action'])) {
    $conn = new mysqli($servername, $username, $password, $dbname);
    if ($conn->connect_error) {
        $message = "Error de Conexión: " . $conn->connect_error;
        $message_type = 'error';
    } else {
        $action = $_POST['form_action'];
        try {
            $conn->begin_transaction();
            if ($action === 'update_validation') {
                $subcategoria_id = (int)($_POST['subcategoria_id'] ?? 0);
                $is_checked = isset($_POST['is_checked']) ? 1 : 0;
                if ($subcategoria_id > 0) {
                    $stmt = $conn->prepare("UPDATE subcategorias SET permite_validacion_externa = ? WHERE id = ?");
                    $stmt->bind_param("ii", $is_checked, $subcategoria_id);
                    $stmt->execute();
                    $stmt->close();
                    $message = "Estado de validación actualizado.";
                }
            } elseif ($action === 'delete') {
                $item_id = (int)($_POST['item_id'] ?? 0);
                $item_type = $_POST['item_type'] ?? '';
                if ($item_id > 0 && !empty($item_type)) {
                    if ($item_type === 'subcategoria') {
                        $stmt = $conn->prepare("DELETE FROM palabras WHERE subcategoria_id = ?"); $stmt->bind_param("i", $item_id); $stmt->execute(); $stmt->close();
                        $stmt = $conn->prepare("DELETE FROM subcategorias WHERE id = ?"); $stmt->bind_param("i", $item_id); $stmt->execute(); $stmt->close();
                    } elseif ($item_type === 'nivel') {
                        $sub_ids = []; $stmt_find = $conn->prepare("SELECT id FROM subcategorias WHERE nivel_id = ?"); $stmt_find->bind_param("i", $item_id); $stmt_find->execute(); $stmt_find->store_result(); $stmt_find->bind_result($sub_id); while($stmt_find->fetch()){$sub_ids[]=$sub_id;} $stmt_find->close();
                        if(!empty($sub_ids)){ $in = implode(',', array_fill(0, count($sub_ids), '?')); $stmt = $conn->prepare("DELETE FROM palabras WHERE subcategoria_id IN ($in)"); $stmt->bind_param(str_repeat('i', count($sub_ids)), ...$sub_ids); $stmt->execute(); $stmt->close();}
                        $stmt = $conn->prepare("DELETE FROM subcategorias WHERE nivel_id = ?"); $stmt->bind_param("i", $item_id); $stmt->execute(); $stmt->close();
                        $stmt = $conn->prepare("DELETE FROM niveles WHERE id = ?"); $stmt->bind_param("i", $item_id); $stmt->execute(); $stmt->close();
                    } elseif ($item_type === 'categoria') {
                        $sub_ids = []; $stmt_find = $conn->prepare("SELECT id FROM subcategorias WHERE categoria_id = ?"); $stmt_find->bind_param("i", $item_id); $stmt_find->execute(); $stmt_find->store_result(); $stmt_find->bind_result($sub_id); while($stmt_find->fetch()){$sub_ids[]=$sub_id;} $stmt_find->close();
                        if(!empty($sub_ids)){ $in = implode(',', array_fill(0, count($sub_ids), '?')); $stmt = $conn->prepare("DELETE FROM palabras WHERE subcategoria_id IN ($in)"); $stmt->bind_param(str_repeat('i', count($sub_ids)), ...$sub_ids); $stmt->execute(); $stmt->close();}
                        $stmt = $conn->prepare("DELETE FROM subcategorias WHERE categoria_id = ?"); $stmt->bind_param("i", $item_id); $stmt->execute(); $stmt->close();
                        $stmt = $conn->prepare("DELETE FROM categorias WHERE id = ?"); $stmt->bind_param("i", $item_id); $stmt->execute(); $stmt->close();
                    }
                    $message = "Elemento eliminado correctamente.";
                }
            } elseif ($action === 'update') {
                $item_id = (int)($_POST['item_id'] ?? 0);
                $item_type = $_POST['item_type'] ?? '';
                $item_name = trim($_POST['item_name'] ?? '');
                if ($item_id > 0 && !empty($item_type) && !empty($item_name)) {
                    $table_name = ($item_type === 'subcategoria') ? 'subcategorias' : $item_type . 's';
                    $stmt = $conn->prepare("UPDATE $table_name SET nombre = ? WHERE id = ?");
                    $stmt->bind_param("si", $item_name, $item_id);
                    $stmt->execute();
                    $stmt->close();
                    $message = "Nombre actualizado.";
                }
            } elseif ($action === 'create_categoria') {
                $item_name = trim($_POST['item_name'] ?? '');
                if (!empty($item_name)) {
                    $stmt = $conn->prepare("INSERT INTO categorias (nombre) VALUES (?)"); $stmt->bind_param("s", $item_name); $stmt->execute(); $stmt->close();
                    $message = "Categoría creada.";
                }
            } elseif ($action === 'create_nivel') {
                $item_name = trim($_POST['item_name'] ?? '');
                if (!empty($item_name)) {
                    $stmt = $conn->prepare("INSERT INTO niveles (nombre) VALUES (?)"); $stmt->bind_param("s", $item_name); $stmt->execute(); $stmt->close();
                    $message = "Nivel creado.";
                }
            } elseif ($action === 'create_subcategoria') {
                $item_name = trim($_POST['item_name'] ?? '');
                $categoria_id = (int)($_POST['categoria_id'] ?? 0);
                $nivel_id = (int)($_POST['nivel_id'] ?? 0);
                if (!empty($item_name) && $categoria_id > 0 && $nivel_id > 0) {
                    $stmt = $conn->prepare("INSERT INTO subcategorias (nombre, categoria_id, nivel_id) VALUES (?, ?, ?)");
                    $stmt->bind_param("sii", $item_name, $categoria_id, $nivel_id);
                    $stmt->execute();
                    $stmt->close();
                    $message = "Subcategoría creada.";
                }
            } elseif ($action === 'add_word') {
                $subcategoria_id = (int)($_POST['subcategoria_id'] ?? 0);
                $palabra = trim($_POST['palabra'] ?? '');
                if ($subcategoria_id > 0 && !empty($palabra)) {
                    $letra = mb_strtoupper(mb_substr($palabra, 0, 1, 'UTF-8'), 'UTF-8');
                    $stmt = $conn->prepare("INSERT IGNORE INTO palabras (subcategoria_id, palabra, letra) VALUES (?, ?, ?)");
                    $stmt->bind_param("iss", $subcategoria_id, $palabra, $letra);
                    $stmt->execute();
                    $message = ($stmt->affected_rows > 0) ? "Palabra añadida." : "La palabra ya existía.";
                    $stmt->close();
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
        $redirect_url = strtok($_SERVER["REQUEST_URI"], '?');
        if (isset($_POST['subcategoria_id']) && in_array($_POST['form_action'], ['add_word', 'delete_word', 'update_word'])) {
            $redirect_url .= "?action=manage&subcategoria_id=" . (int)$_POST['subcategoria_id'];
        }
        header("Location: " . $redirect_url);
        exit();
    }
}

function getItem($type, $id) { require __DIR__ . '/db_config.php'; $conn = new mysqli($servername, $username, $password, $dbname); if ($conn->connect_error) return null; $table_name = ($type === 'subcategoria') ? 'subcategorias' : $type . 's'; $stmt = $conn->prepare("SELECT nombre FROM $table_name WHERE id = ?"); $stmt->bind_param("i", $id); $stmt->execute(); $stmt->store_result(); $stmt->bind_result($nombre); $item = null; if($stmt->fetch()) { $item = ['id' => $id, 'type' => $type, 'nombre' => $nombre]; } $stmt->close(); $conn->close(); return $item; }
function getCategorias() { require __DIR__ . '/db_config.php'; $conn = new mysqli($servername, $username, $password, $dbname); if ($conn->connect_error) return []; $sql = "SELECT id, nombre FROM categorias ORDER BY nombre ASC"; $stmt = $conn->prepare($sql); $stmt->execute(); $stmt->store_result(); $stmt->bind_result($id, $nombre); $items = []; while ($stmt->fetch()) { $items[] = ['id' => $id, 'nombre' => $nombre]; } $stmt->close(); $conn->close(); return $items; }
function getNiveles() { require __DIR__ . '/db_config.php'; $conn = new mysqli($servername, $username, $password, $dbname); if ($conn->connect_error) return []; $sql = "SELECT id, nombre FROM niveles ORDER BY nombre ASC"; $stmt = $conn->prepare($sql); $stmt->execute(); $stmt->store_result(); $stmt->bind_result($id, $nombre); $items = []; while ($stmt->fetch()) { $items[] = ['id' => $id, 'nombre' => $nombre]; } $stmt->close(); $conn->close(); return $items; }
function getSubcategoriasStats() { require __DIR__ . '/db_config.php'; $conn = new mysqli($servername, $username, $password, $dbname); if ($conn->connect_error) return [['error' => $conn->connect_error]]; $sql = "SELECT s.id AS subcategoria_id, c.nombre AS categoria, n.nombre AS nivel, s.nombre AS subcategoria, s.permite_validacion_externa, (SELECT COUNT(p.id) FROM palabras p WHERE p.subcategoria_id = s.id) AS cantidad_palabras FROM subcategorias s JOIN categorias c ON s.categoria_id = c.id LEFT JOIN niveles n ON s.nivel_id = n.id ORDER BY c.nombre, n.nombre, s.nombre;"; $stmt = $conn->prepare($sql); if (!$stmt || !$stmt->execute()) { return [['error' => $conn->error]]; } $stmt->store_result(); $stmt->bind_result($subcategoria_id, $categoria, $nivel, $subcategoria, $permite_validacion_externa, $cantidad_palabras); $stats = []; while ($stmt->fetch()) { $stats[] = ['subcategoria_id' => $subcategoria_id, 'categoria' => $categoria, 'nivel' => $nivel, 'subcategoria' => $subcategoria, 'permite_validacion_externa' => $permite_validacion_externa, 'cantidad_palabras' => $cantidad_palabras]; } $stmt->close(); $conn->close(); return $stats; }
function getWordsForSubcategory($subcategoria_id) { require __DIR__ . '/db_config.php'; $conn = new mysqli($servername, $username, $password, $dbname); if ($conn->connect_error) return []; $sql = "SELECT id, palabra FROM palabras WHERE subcategoria_id = ? ORDER BY palabra ASC"; $stmt = $conn->prepare($sql); $stmt->bind_param("i", $subcategoria_id); $stmt->execute(); $stmt->store_result(); $stmt->bind_result($id, $palabra); $words = []; while ($stmt->fetch()) { $words[] = ['id' => $id, 'palabra' => $palabra]; } $stmt->close(); $conn->close(); return $words; }

$view = $_GET['action'] ?? 'main';

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Gestión DB - Tutti Quanti</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="style.css">
    <style> body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif; background-color: #f4f7f9; margin: 0; padding: 2rem; } .main-container { margin: auto; width: 100%; max-width: 900px; } .container { background-color: #fff; padding: 2rem; border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.08); margin-bottom: 2rem; } h1, h2 { color: #3F2A56; text-align: center; margin-bottom: 1.5rem; } .page-header { text-align: center; margin-bottom: 2rem; } .page-header .logo { margin-bottom: 1rem; } table { width: 100%; border-collapse: collapse; font-size: 1rem; } thead { background-color: #3F2A56; color: white; } th, td { padding: 12px 15px; border: 1px solid #e0e0e0; text-align: left; vertical-align: middle; } tbody tr:nth-child(even) { background-color: #f9f9f9; } tbody tr:hover { background-color: #f1f1f1; } .actions-cell { text-align: center; white-space: nowrap; } .actions-cell a, .actions-cell button { font-size: 0.85rem; padding: 8px 12px; margin: 0 4px; border-radius: 5px; cursor: pointer; border: none; text-decoration: none; display: inline-block; } .actions-cell a i, .actions-cell button i { margin-right: 5px; } .btn-edit { background-color: #ffc107; color: #333; } .btn-delete { background-color: #dc3545; color: white; } .btn-manage { background-color: #17a2b8; color: white; } .status-message { margin-bottom: 1.5rem; text-align: center; font-weight: 600; padding: 0.75rem; border-radius: 4px; } .status-success { background-color: #d4edda; color: #155724; } .status-error { background-color: #f8d7da; color: #721c24; } </style>
</head>
<body>
    <div class="main-container">
        <div class="page-header"><a href="index.php"><img src="https://moroarte.com/wp-content/uploads/2023/09/logoTUTTIQUANTI-154x300.png" alt="Tutti Quanti Logo" class="logo"></a><h1>Gestion de la DB tuttiquanti.sql</h1></div>
        <?php if (!empty($message)): ?><div class="status-message status-<?php echo $message_type; ?>"><?php echo htmlspecialchars($message); ?></div><?php endif; ?>

        <?php if ($view === 'edit'): 
            $item = getItem($_GET['type'] ?? '', (int)($_GET['id'] ?? 0));
            if ($item && isset($item['nombre'])):
        ?>
            <div class="container"><h2>Editando: <?php echo htmlspecialchars(ucfirst($item['type']) . ' - ' . $item['nombre']); ?></h2><form method="POST"><input type="hidden" name="form_action" value="update"><input type="hidden" name="item_id" value="<?php echo $item['id']; ?>"><input type="hidden" name="item_type" value="<?php echo $item['type']; ?>"><div class="form-group"><label for="item_name">Nuevo Nombre:</label><input type="text" name="item_name" value="<?php echo htmlspecialchars($item['nombre']); ?>" required style="width:100%; padding:10px; font-size:1rem;"></div><button type="submit" style="width:100%; padding:10px; font-size:1rem;">Guardar Cambios</button><a href="gestion.php" style="display:block; text-align:center; margin-top:1rem;">Cancelar</a></form></div>
        <?php else: echo "<p>Elemento no encontrado.</p><a href='gestion.php'>Volver</a>"; endif; ?>

        <?php elseif ($view === 'manage'):
            $subcategoria_id = (int)($_GET['subcategoria_id'] ?? 0);
            $subcategoria = getItem('subcategoria', $subcategoria_id);
            $words = getWordsForSubcategory($subcategoria_id);
            if (!$subcategoria) { die("Subcategoría no encontrada."); }
        ?>
            <div class="container">
                <p><a href="gestion.php">&larr; Volver al panel principal</a></p>
                <h2>Gestionando Palabras para: <strong><?php echo htmlspecialchars($subcategoria['nombre']); ?></strong></h2>
                <form method="POST" style="margin-bottom: 1.5rem; display:flex; gap:10px;"><input type="hidden" name="form_action" value="add_word"><input type="hidden" name="subcategoria_id" value="<?php echo $subcategoria_id; ?>"><input type="text" name="palabra" placeholder="Añadir nueva palabra..." required style="flex-grow:1;"><button type="submit"><i class="fas fa-plus"></i> Añadir</button></form>
                <table id="words-table">
                    <thead><tr><th>Palabra</th><th class="actions-cell">Acciones</th></tr></thead>
                    <tbody>
                        <?php if (empty($words)): ?>
                            <tr><td colspan="2" style="text-align:center;">No hay palabras en esta subcategoría.</td></tr>
                        <?php else: foreach($words as $word):
                        ?>
                        <tr>
                            <td><form method="POST" style="display:flex; gap:10px;"><input type="hidden" name="form_action" value="update_word"><input type="hidden" name="word_id" value="<?php echo $word['id']; ?>"><input type="hidden" name="subcategoria_id" value="<?php echo $subcategoria_id; ?>"><input type="text" name="palabra" value="<?php echo htmlspecialchars($word['palabra']); ?>" style="flex-grow:1;"><button type="submit" class="btn-edit"><i class="fas fa-save"></i></button></form></td>
                            <td class="actions-cell"><form method="POST" style="display:inline;" onsubmit="return confirm('¿Eliminar esta palabra?');"><input type="hidden" name="form_action" value="delete_word"><input type="hidden" name="word_id" value="<?php echo $word['id']; ?>"><input type="hidden" name="subcategoria_id" value="<?php echo $subcategoria_id; ?>"><button type="submit" class="btn-delete"><i class="fas fa-trash-alt"></i></button></form></td>
                        </tr>
                        <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
        <?php else: // Main View ?>
        <div class="container"><h2>Subcategorías</h2><form method="POST" style="margin-bottom: 1.5rem; border: 1px solid #eee; padding: 1rem; border-radius: 8px;"><input type="hidden" name="form_action" value="create_subcategoria"><div style="display:flex; gap:10px; margin-bottom:10px;"><select name="categoria_id" required style="flex:1;"><option value="">Seleccionar Categoría</option><?php foreach(getCategorias() as $cat) { printf('<option value="%d">%s</option>', $cat['id'], htmlspecialchars($cat['nombre'])); } ?></select><select name="nivel_id" required style="flex:1;"><option value="">Seleccionar Nivel</option><?php foreach(getNiveles() as $niv) { printf('<option value="%d">%s</option>', $niv['id'], htmlspecialchars($niv['nombre'])); } ?></select></div><div style="display:flex; gap:10px;"><input type="text" name="item_name" placeholder="Nombre de la nueva subcategoría" required style="flex-grow:1;"><button type="submit"><i class="fas fa-plus"></i> Crear Subcategoría</button></div></form><table id="subcategorias-table"><thead><tr><th>Categoría</th><th>Nivel</th><th>Subcategoría</th><th>Palabras</th><th style="text-align:center;">Valid. Externa</th><th class="actions-cell">Acciones</th></tr></thead><tbody><?php $subcategorias = getSubcategoriasStats(); if (isset($subcategorias[0]['error'])) { echo '<tr><td colspan="6" style="color:red;text-align:center;">Error al cargar datos: '.htmlspecialchars($subcategorias[0]['error']).'</td></tr>'; } else foreach ($subcategorias as $item) { $checkedAttribute = $item['permite_validacion_externa'] == 1 ? 'checked' : ''; echo "<tr><td>" . htmlspecialchars($item['categoria'] ?: '(Vacío)') . "</td><td>" . htmlspecialchars($item['nivel'] ?: '(Sin Nivel)') . "</td><td>" . htmlspecialchars($item['subcategoria'] ?: '(Vacío)') . "</td><td style='text-align:center;'>" . htmlspecialchars($item['cantidad_palabras']) . "</td><td style='text-align:center;'><form method='POST' style='margin:0;'><input type='hidden' name='form_action' value='update_validation'><input type='hidden' name='subcategoria_id' value='" . htmlspecialchars($item['subcategoria_id']) . "'><input type='checkbox' " . $checkedAttribute . " name='is_checked' onchange='this.form.submit()'></form></td><td class='actions-cell'"; echo "<a href='gestion.php?action=manage&subcategoria_id=" . htmlspecialchars($item['subcategoria_id']) . "' class='btn-manage'><i class='fas fa-tasks'></i> Gestionar</a> "; echo "<a href='gestion.php?action=edit&type=subcategoria&id=" . htmlspecialchars($item['subcategoria_id']) . "' class='btn-edit'><i class='fas fa-pencil-alt'></i></a> "; $confirm_message = sprintf("return confirm('¿Seguro que quieres eliminar la subcategoría \'%s\' y todas sus palabras?');", addslashes(htmlspecialchars($item['subcategoria'], ENT_QUOTES))); echo sprintf('<form method="POST" style="display:inline;" onsubmit="%s"><input type="hidden" name="form_action" value="delete"><input type="hidden" name="item_type" value="subcategoria"><input type="hidden" name="item_id" value="%s"><button type="submit" class="btn-delete"><i class="fas fa-trash-alt"></i></button></form>', $confirm_message, htmlspecialchars($item['subcategoria_id'])); echo '</td></tr>'; } ?></tbody></table></div>
        <div class="container"><h2>Niveles</h2><form method="POST" style="margin-bottom: 1.5rem; display:flex; gap:10px;"><input type="hidden" name="form_action" value="create_nivel"><input type="text" name="item_name" placeholder="Nombre del nuevo nivel" required style="flex-grow:1;"><button type="submit"><i class="fas fa-plus"></i> Crear</button></form><table id="niveles-table"><thead><tr><th>Nombre</th><th class="actions-cell">Acciones</th></tr></thead><tbody><?php $niveles = getNiveles(); foreach ($niveles as $item) { echo "<tr><td>" . htmlspecialchars($item['nombre']) . "</td><td class='actions-cell'"; echo "<a href='gestion.php?action=edit&type=nivel&id=" . htmlspecialchars($item['id']) . "' class='btn-edit'><i class='fas fa-pencil-alt'></i> Editar</a> "; $confirm_message = sprintf("return confirm('ATENCIÓN: ¿Seguro que quieres eliminar el nivel \'%s\'? Se borrarán TODAS las subcategorías y palabras que contenga.');", addslashes(htmlspecialchars($item['nombre'], ENT_QUOTES))); echo sprintf('<form method="POST" style="display:inline;" onsubmit="%s"><input type="hidden" name="form_action" value="delete"><input type="hidden" name="item_type" value="nivel"><input type="hidden" name="item_id" value="%s"><button type="submit" class="btn-delete"><i class="fas fa-trash-alt"></i> Eliminar</button></form>', $confirm_message, htmlspecialchars($item['id'])); echo "</td></tr>"; } ?></tbody></table></div>
        <div class="container"><h2>Categorías Principales</h2><form method="POST" style="margin-bottom: 1.5rem; display:flex; gap:10px;"><input type="hidden" name="form_action" value="create_categoria"><input type="text" name="item_name" placeholder="Nombre de la nueva categoría" required style="flex-grow:1;"><button type="submit"><i class="fas fa-plus"></i> Crear</button></form><table id="categorias-table"><thead><tr><th>Nombre</th><th class="actions-cell">Acciones</th></tr></thead><tbody><?php $categorias = getCategorias(); foreach ($categorias as $item) { echo "<tr><td>" . htmlspecialchars($item['nombre']) . "</td><td class='actions-cell'"; echo "<a href='gestion.php?action=edit&type=categoria&id=" . htmlspecialchars($item['id']) . "' class='btn-edit'><i class='fas fa-pencil-alt'></i> Editar</a> "; $confirm_message = sprintf("return confirm('ATENCIÓN: ¿Seguro que quieres eliminar la categoría \'%s\'? Se borrarán TODAS las subcategorías y palabras que contenga.');", addslashes(htmlspecialchars($item['nombre'], ENT_QUOTES))); echo sprintf('<form method="POST" style="display:inline;" onsubmit="%s"><input type="hidden" name="form_action" value="delete"><input type="hidden" name="item_type" value="categoria"><input type="hidden" name="item_id" value="%s"><button type="submit" class="btn-delete"><i class="fas fa-trash-alt"></i> Eliminar</button></form>', $confirm_message, htmlspecialchars($item['id'])); echo "</td></tr>"; } ?></tbody></table></div>
        <?php endif; ?>
    </div>
</body>
</html>