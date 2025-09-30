<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/db_config.php';

$message = '';

// --- PROCESAR FORMULARIO ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $subcategoria_id = isset($_POST['subcategoria_id']) ? (int)$_POST['subcategoria_id'] : 0;
    $palabras_str = isset($_POST['palabras']) ? trim($_POST['palabras']) : '';

    if ($subcategoria_id > 0 && !empty($palabras_str)) {
        $conn = new mysqli($servername, $username, $password, $dbname);
        if ($conn->connect_error) {
            $message = "<p class='status-error'>Error de Conexión: " . $conn->connect_error . "</p>";
        } else {
            $palabras_array = array_filter(array_unique(array_map('trim', explode(',', $palabras_str))));
            
            if (!empty($palabras_array)) {
                $sql = "INSERT IGNORE INTO palabras (subcategoria_id, palabra, letra) VALUES (?, ?, ?)";
                $stmt = $conn->prepare($sql);
                
                if ($stmt) {
                    $inserted_count = 0;
                    $conn->begin_transaction();
                    foreach ($palabras_array as $palabra) {
                        if (empty($palabra)) continue;
                        $letra = mb_substr(strtoupper($palabra), 0, 1);
                        $stmt->bind_param("iss", $subcategoria_id, $palabra, $letra);
                        $stmt->execute();
                        if ($stmt->affected_rows > 0) {
                            $inserted_count++;
                        }
                    }
                    $conn->commit();
                    $stmt->close();

                    $total_count = count($palabras_array);
                    $skipped_count = $total_count - $inserted_count;
                    $message = "<p class='status-success'>Proceso completado. Palabras nuevas insertadas: $inserted_count.";
                    if ($skipped_count > 0) {
                        $message .= " Palabras duplicadas omitidas: $skipped_count.</p>";
                    } else {
                        $message .= "</p>";
                    }
                } else {
                    $message = "<p class='status-error'>Error al preparar la consulta.</p>";
                }
            }
            $conn->close();
        }
    } else {
        $message = "<p class='status-error'>Por favor, selecciona una subcategoría e ingresa palabras.</p>";
    }
}

// --- FUNCIÓN PARA CARGAR SUBCATEGORÍAS ---
function get_subcategorias_options() {
    global $servername, $username, $password, $dbname;
    $conn = new mysqli($servername, $username, $password, $dbname);
    if ($conn->connect_error) {
        return '<option value="">Error al conectar a la BD</option>';
    }

    $sql = "SELECT s.id, s.nombre, n.nombre as nivel_nombre, c.nombre as categoria_nombre
            FROM subcategorias s
            JOIN niveles n ON s.nivel_id = n.id
            JOIN categorias c ON s.categoria_id = c.id
            ORDER BY c.nombre, n.nombre, s.nombre";
    
    $stmt = $conn->prepare($sql);
    if (!$stmt || !$stmt->execute()) {
        // Si la consulta falla, muestra el error directamente en el dropdown
        return '<option value="">ERROR: ' . htmlspecialchars($conn->error) . '</option>';
    }

    $stmt->store_result();
    $stmt->bind_result($id, $nombre, $nivel_nombre, $categoria_nombre);

    $options = '<option value="">Selecciona una subcategoría</option>';
    if ($stmt->num_rows > 0) {
        while ($stmt->fetch()) {
            $display_name = htmlspecialchars("$categoria_nombre > $nivel_nombre > $nombre");
            $options .= "<option value=\"" . htmlspecialchars($id) . "\">$display_name</option>";
        }
    } else {
        $options = '<option value="">No hay subcategorías disponibles</option>';
    }
    
    $stmt->close();
    $conn->close();
    return $options;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inserción Masiva de Palabras (Método Alternativo)</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif; background-color: #f4f7f9; margin: 0; padding: 2rem; }
        .main-container { margin: auto; width: 100%; max-width: 700px; }
        .container { background-color: #fff; padding: 2rem; border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.08); }
        h1 { color: #333; text-align: center; margin-top: 0; margin-bottom: 1.5rem; }
        .form-group { margin-bottom: 1rem; }
        label { display: block; margin-bottom: 0.5rem; color: #555; font-weight: 600; }
        select, textarea { width: 100%; padding: 0.75rem; border: 1px solid #ddd; border-radius: 4px; box-sizing: border-box; font-size: 1rem; }
        button { width: 100%; padding: 0.85rem; background-color: #28a745; color: white; border: none; border-radius: 4px; font-size: 1.1rem; font-weight: 600; cursor: pointer; transition: background-color 0.2s; }
        button:hover { background-color: #218838; }
        .status-message { margin-top: 1rem; text-align: center; font-weight: 600; padding: 0.75rem; border-radius: 4px; }
        .status-success { background-color: #d4edda; color: #155724; }
        .status-error { background-color: #f8d7da; color: #721c24; }
    </style>
</head>
<body>

<div class="main-container">
    <div class="container">
        <h1>Inserción Masiva (Alternativa)</h1>
        <p>Este método es más robusto y no depende de JavaScript. Si hay un error en el servidor, debería mostrarse directamente aquí.</p>
        
        <?php if (!empty($message)): ?>
            <div class="status-message"><?php echo $message; ?></div>
        <?php endif; ?>

        <form method="POST" action="bulk_insert.php">
            <div class="form-group">
                <label for="subcategoria">1. Selecciona la Subcategoría:</label>
                <select id="subcategoria" name="subcategoria_id" required>
                    <?php echo get_subcategorias_options(); ?>
                </select>
            </div>
            <div class="form-group">
                <label for="palabras">2. Ingresa las Palabras (separadas por coma):</label>
                <textarea id="palabras" name="palabras" required placeholder="Ej: Palabra1, Palabra2, Palabra3" rows="10"></textarea>
            </div>
            <button type="submit">Insertar Palabras</button>
        </form>
    </div>
</div>

</body>
</html>
