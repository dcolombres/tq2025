<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
require_once __DIR__ . '/db_config.php';

// --- AJAX ENDPOINT: GET SUBCATEGORIAS ---
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['categoria_id'])) {
    header('Content-Type: application/json');
    $categoria_id = (int)$_GET['categoria_id'];
    $conn = new mysqli($servername, $username, $password, $dbname);
    if ($conn->connect_error) {
        http_response_code(500);
        die(json_encode(['error' => 'DB connection failed']));
    }
    $sql = "SELECT id, nombre FROM subcategorias WHERE categoria_id = ? ORDER BY nombre ASC";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $categoria_id);
    $stmt->execute();
    $stmt->store_result();
    $stmt->bind_result($id, $nombre);
    $subcategorias = [];
    while ($stmt->fetch()) {
        $subcategorias[] = ['id' => $id, 'nombre' => $nombre];
    }
    $stmt->close();
    $conn->close();
    echo json_encode($subcategorias);
    exit; // Terminate script after sending JSON
}


// --- FORM PROCESSING & PAGE DISPLAY LOGIC ---

// --- GLOBAL VARIABLES ---
$message = '';
$message_type = ''; // 'success' or 'error'

// --- HELPER FUNCTIONS ---
function getCategoriasOptions() {
    global $servername, $username, $password, $dbname;
    $conn = new mysqli($servername, $username, $password, $dbname);
    if ($conn->connect_error) return '<option value="">Error DB</option>';
    $sql = "SELECT id, nombre FROM categorias ORDER BY nombre ASC";
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $stmt->store_result();
    $stmt->bind_result($id, $nombre);
    $options = '<option value="">Selecciona una categoría</option>';
    while ($stmt->fetch()) {
        $options .= sprintf('<option value="%d">%s</option>', htmlspecialchars($id), htmlspecialchars($nombre));
    }
    $stmt->close();
    $conn->close();
    return $options;
}

// --- ACTION ROUTER ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $conn = new mysqli($servername, $username, $password, $dbname);
    if ($conn->connect_error) {
        $message = 'Error de conexión a la base de datos.';
        $message_type = 'error';
    } else {
        if (isset($_POST['form_action'])) {
            if ($_POST['form_action'] === 'insert_words') {
                // Logic from insert_word.php
                $palabras_str = isset($_POST['palabras']) ? trim($_POST['palabras']) : '';
                $subcategoria_id = isset($_POST['subcategoria_id']) ? (int)$_POST['subcategoria_id'] : 0;
                if (empty($palabras_str) || $subcategoria_id === 0) {
                    $message = 'Debes seleccionar una subcategoría e ingresar al menos una palabra.';
                    $message_type = 'error';
                } else {
                    $conn->begin_transaction();
                    try {
                        $palabras_arr = array_filter(array_unique(array_map('trim', explode(',', $palabras_str))));
                        $sql = "INSERT IGNORE INTO palabras (subcategoria_id, palabra, letra) VALUES (?, ?, ?)";
                        $stmt = $conn->prepare($sql);
                        $inserted_count = 0;
                        foreach ($palabras_arr as $palabra) {
                            if(empty($palabra)) continue;
                            $letra = mb_strtoupper(mb_substr($palabra, 0, 1, 'UTF-8'), 'UTF-8');
                            $stmt->bind_param("iss", $subcategoria_id, $palabra, $letra);
                            $stmt->execute();
                            if ($stmt->affected_rows > 0) $inserted_count++;
                        }
                        $conn->commit();
                        $stmt->close();
                        $skipped_count = count($palabras_arr) - $inserted_count;
                        $message = "Proceso completado. Palabras nuevas: $inserted_count. Duplicadas omitidas: $skipped_count.";
                        $message_type = 'success';
                    } catch (Exception $e) {
                        $conn->rollback();
                        $message = "Error de base de datos: " . $e->getMessage();
                        $message_type = 'error';
                    }
                }
            } elseif ($_POST['form_action'] === 'create_structure') {
                // Logic from create_structure.php
                $categoria_nombre = isset($_POST['categoria_principal']) ? trim($_POST['categoria_principal']) : '';
                $nivel_nombre = isset($_POST['nivel']) ? trim($_POST['nivel']) : '';
                $subcategoria_str = isset($_POST['subcategoria']) ? trim($_POST['subcategoria']) : '';
                if (empty($categoria_nombre) || empty($nivel_nombre) || empty($subcategoria_str)) {
                    $message = 'Todos los campos para crear estructura son obligatorios.';
                    $message_type = 'error';
                } else {
                     $conn->begin_transaction();
                    try {
                        // Step 1: Get/Create Categoria
                        $stmt = $conn->prepare("SELECT id FROM categorias WHERE nombre = ?");
                        $stmt->bind_param("s", $categoria_nombre);
                        $stmt->execute(); $stmt->store_result(); $stmt->bind_result($categoria_id);
                        if (!$stmt->fetch()) { $stmt->close(); $stmt = $conn->prepare("INSERT INTO categorias (nombre) VALUES (?)"); $stmt->bind_param("s", $categoria_nombre); $stmt->execute(); $categoria_id = $conn->insert_id; } $stmt->close();
                        // Step 2: Get/Create Nivel
                        $stmt = $conn->prepare("SELECT id FROM niveles WHERE nombre = ?");
                        $stmt->bind_param("s", $nivel_nombre);
                        $stmt->execute(); $stmt->store_result(); $stmt->bind_result($nivel_id);
                        if (!$stmt->fetch()) { $stmt->close(); $stmt = $conn->prepare("INSERT INTO niveles (nombre) VALUES (?)"); $stmt->bind_param("s", $nivel_nombre); $stmt->execute(); $nivel_id = $conn->insert_id; } $stmt->close();
                        // Step 3: Create Subcategorias
                        $subcategoria_nombres = array_filter(array_map('trim', explode(',', $subcategoria_str)));
                        $created_count = 0; $skipped_count = 0;
                        $stmt_check = $conn->prepare("SELECT id FROM subcategorias WHERE nombre = ? AND categoria_id = ? AND nivel_id = ?");
                        $stmt_insert = $conn->prepare("INSERT INTO subcategorias (nombre, categoria_id, nivel_id) VALUES (?, ?, ?)");
                        foreach ($subcategoria_nombres as $sub_nombre) {
                            $stmt_check->bind_param("sii", $sub_nombre, $categoria_id, $nivel_id);
                            $stmt_check->execute(); $stmt_check->store_result();
                            if ($stmt_check->fetch()) { $skipped_count++; } else { $stmt_insert->bind_param("sii", $sub_nombre, $categoria_id, $nivel_id); $stmt_insert->execute(); $created_count++; }
                        }
                        $stmt_check->close(); $stmt_insert->close();
                        $conn->commit();
                        $message = "Estructura creada. Subcategorías nuevas: $created_count. Omitidas: $skipped_count.";
                        $message_type = 'success';
                    } catch (Exception $e) {
                        $conn->rollback();
                        $message = "Error de base de datos: " . $e->getMessage();
                        $message_type = 'error';
                    }
                }
            }
        }
        $conn->close();
    }
}

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Administración de Contenido - Tutti Quanti</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif; background-color: #f4f7f9; margin: 0; padding: 2rem; }
        .main-container { margin: auto; width: 100%; max-width: 700px; }
        .container { background-color: #fff; padding: 2rem; border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.08); margin-bottom: 1.5rem; }
        h1, h2 { color: #333; text-align: center; margin-top: 0; margin-bottom: 1.5rem; }
        .form-group { margin-bottom: 1rem; }
        label { display: block; margin-bottom: 0.5rem; color: #555; font-weight: 600; }
        input[type="text"], select, textarea { width: 100%; padding: 0.75rem; border: 1px solid #ddd; border-radius: 4px; box-sizing: border-box; font-size: 1rem; }
        select:disabled { background-color: #eee; }
        button { width: 100%; padding: 0.85rem; background-color: #007bff; color: white; border: none; border-radius: 4px; font-size: 1.1rem; font-weight: 600; cursor: pointer; transition: background-color 0.2s; }
        button:hover { background-color: #0056b3; }
        .status-message { margin-bottom: 1.5rem; text-align: center; font-weight: 600; padding: 0.75rem; border-radius: 4px; }
        .status-success { background-color: #d4edda; color: #155724; }
        .status-error { background-color: #f8d7da; color: #721c24; }
        .collapsible-header { background-color: #f0f0f0; padding: 1rem; border-radius: 8px; cursor: pointer; display: flex; justify-content: space-between; align-items: center; margin-top: 1.5rem; }
        .collapsible-header h2 { margin: 0; font-size: 1.2rem; text-align: left; }
        .collapsible-header .icon::before { content: '►'; font-size: 0.8em; }
        .collapsible-header.active .icon::before { transform: rotate(90deg); }
        .collapsible-content { padding: 1.5rem; border: 1px solid #f0f0f0; border-top: none; border-radius: 0 0 8px 8px; display: none; }
    </style>
</head>
<body>

<div class="main-container">
    <?php if (!empty($message)): ?>
        <div class="status-message status-<?php echo $message_type; ?>"><?php echo htmlspecialchars($message); ?></div>
    <?php endif; ?>

    <div class="container">
        <h1>Insertar Palabras</h1>
        <form id="insert-form" method="POST" action="insert.php">
            <input type="hidden" name="form_action" value="insert_words">
            <div class="form-group">
                <label for="categoria">1. Selecciona la Categoría Principal:</label>
                <select id="categoria" name="categoria" required>
                    <?php echo getCategoriasOptions(); ?>
                </select>
            </div>
            <div class="form-group">
                <label for="subcategoria">2. Selecciona la Subcategoría:</label>
                <select id="subcategoria" name="subcategoria_id" required disabled>
                    <option value="">Selecciona una categoría principal primero</option>
                </select>
            </div>
            <div class="form-group">
                <label for="palabras">3. Ingresa las Palabras (separadas por coma):</label>
                <textarea id="palabras" name="palabras" required placeholder="Ej: Argentina, Alemania, Francia" rows="5"></textarea>
            </div>
            <button type="submit">Insertar Palabras</button>
        </form>
    </div>

    <div class="collapsible-header" data-target="#create-structure-content">
        <h2><span class="icon"></span> Crear Nueva Estructura (Avanzado)</h2>
    </div>
    <div id="create-structure-content" class="collapsible-content">
        <p>Usa este formulario para crear nuevas categorías, niveles y subcategorías. Si escribes un nombre que ya existe, el sistema lo reutilizará.</p>
        <form id="create-structure-form" method="POST" action="insert.php">
            <input type="hidden" name="form_action" value="create_structure">
            <div class="form-group">
                <label for="new_categoria">Nombre de la Categoría Principal:</label>
                <input type="text" id="new_categoria" name="categoria_principal" required placeholder="Ej: CLASICO, EXPANSION 2024">
            </div>
            <div class="form-group">
                <label for="new_nivel">Nombre del Nivel:</label>
                <input type="text" id="new_nivel" name="nivel" required placeholder="Ej: NIVEL 7, DEPORTES ACUATICOS">
            </div>
            <div class="form-group">
                <label for="new_subcategoria">Nuevas Subcategorías (separadas por coma):</label>
                <textarea id="new_subcategoria" name="subcategoria" required placeholder="Ej: NATACION, WATERPOLO, REMO" rows="3"></textarea>
            </div>
            <button type="submit">Crear Estructura</button>
        </form>
    </div>

</div>

<script>
    // --- Lógica para Secciones Colapsables ---
    document.querySelectorAll('.collapsible-header').forEach(header => {
        header.addEventListener('click', () => {
            const content = document.querySelector(header.dataset.target);
            header.classList.toggle('active');
            content.style.display = content.style.display === "block" ? "none" : "block";
        });
    });

    // --- Lógica para el dropdown de subcategorías ---
    const categoriaSelect = document.getElementById('categoria');
    const subcategoriaSelect = document.getElementById('subcategoria');

    categoriaSelect.addEventListener('change', () => {
        const categoriaId = categoriaSelect.value;
        subcategoriaSelect.innerHTML = '<option value="">Cargando...</option>';
        subcategoriaSelect.disabled = true;

        if (!categoriaId) {
            subcategoriaSelect.innerHTML = '<option value="">Selecciona una categoría principal primero</option>';
            return;
        }

        // The AJAX call now points to the same file, which will act as an endpoint.
        fetch(`insert.php?categoria_id=${categoriaId}`)
            .then(response => response.json())
            .then(subcategorias => {
                subcategoriaSelect.innerHTML = '<option value="">Selecciona una subcategoría</option>';
                if (subcategorias.length > 0) {
                    subcategorias.forEach(subcat => {
                        const option = document.createElement('option');
                        option.value = subcat.id;
                        option.textContent = subcat.nombre;
                        subcategoriaSelect.appendChild(option);
                    });
                    subcategoriaSelect.disabled = false;
                } else {
                    subcategoriaSelect.innerHTML = '<option value="">No hay subcategorías para esta categoría</option>';
                }
            })
            .catch(error => {
                console.error('Error cargando subcategorías:', error);
                subcategoriaSelect.innerHTML = '<option value="">Error al cargar subcategorías</option>';
            });
    });
</script>

</body>
</html>
