<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
require_once __DIR__ . '/db_config.php';

// --- DATA FETCHING FUNCTIONS ---

function getCategorias() {
    require __DIR__ . '/db_config.php';
    $conn = new mysqli($servername, $username, $password, $dbname);
    if ($conn->connect_error) return [];
    
    $sql = "SELECT id, nombre FROM categorias ORDER BY nombre ASC";
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $stmt->store_result();
    $stmt->bind_result($id, $nombre);
    
    $items = [];
    while ($stmt->fetch()) {
        $items[] = ['id' => $id, 'nombre' => $nombre];
    }
    $stmt->close();
    $conn->close();
    return $items;
}

function getNiveles() {
    require __DIR__ . '/db_config.php';
    $conn = new mysqli($servername, $username, $password, $dbname);
    if ($conn->connect_error) return [];

    $sql = "SELECT id, nombre FROM niveles ORDER BY nombre ASC";
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $stmt->store_result();
    $stmt->bind_result($id, $nombre);

    $items = [];
    while ($stmt->fetch()) {
        $items[] = ['id' => $id, 'nombre' => $nombre];
    }
    $stmt->close();
    $conn->close();
    return $items;
}

function getSubcategoriasStats() {
    require __DIR__ . '/db_config.php';
    $conn = new mysqli($servername, $username, $password, $dbname);
    if ($conn->connect_error) return [];

    $sql = "
        SELECT 
            s.id AS subcategoria_id,
            c.nombre AS categoria, 
            n.nombre AS nivel,
            s.nombre AS subcategoria, 
            s.permite_validacion_externa,
            (SELECT COUNT(p.id) FROM palabras p WHERE p.subcategoria_id = s.id) AS cantidad_palabras
        FROM 
            subcategorias s
        JOIN 
            categorias c ON s.categoria_id = c.id
        LEFT JOIN
            niveles n ON s.nivel_id = n.id
        ORDER BY 
            c.nombre, n.nombre, s.nombre;
    ";
    
    $stmt = $conn->prepare($sql);
    if (!$stmt || !$stmt->execute()) {
        // En caso de error, devuelve un array con el error para mostrarlo
        return [['error' => $conn->error]];
    }

    $stmt->store_result();
    $stmt->bind_result($subcategoria_id, $categoria, $nivel, $subcategoria, $permite_validacion_externa, $cantidad_palabras);

    $stats = [];
    while ($stmt->fetch()) {
        $stats[] = [
            'subcategoria_id' => $subcategoria_id,
            'categoria' => $categoria,
            'nivel' => $nivel,
            'subcategoria' => $subcategoria,
            'permite_validacion_externa' => $permite_validacion_externa,
            'cantidad_palabras' => $cantidad_palabras
        ];
    }
    $stmt->close();
    $conn->close();
    return $stats;
}

// --- ACTION HANDLING (To be implemented) ---
// ...

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Contenido - Tutti Quanti</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif; background-color: #f4f7f9; margin: 0; padding: 2rem; }
        .main-container { margin: auto; width: 100%; max-width: 900px; }
        .container { background-color: #fff; padding: 2rem; border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.08); margin-bottom: 2rem; }
        h1, h2 { color: #3F2A56; text-align: center; margin-bottom: 1.5rem; }
        table { width: 100%; border-collapse: collapse; font-size: 1rem; }
        thead { background-color: #3F2A56; color: white; }
        th, td { padding: 12px 15px; border: 1px solid #e0e0e0; text-align: left; vertical-align: middle; }
        tbody tr:nth-child(even) { background-color: #f9f9f9; }
        tbody tr:hover { background-color: #f1f1f1; }
        .actions-cell { text-align: center; white-space: nowrap; }
        .actions-cell a, .actions-cell button { font-size: 0.85rem; padding: 8px 12px; margin: 0 4px; border-radius: 5px; cursor: pointer; border: none; text-decoration: none; display: inline-block; }
        .actions-cell a i, .actions-cell button i { margin-right: 5px; }
        .btn-edit { background-color: #ffc107; color: #333; }
        .btn-delete { background-color: #dc3545; color: white; }
        .btn-manage { background-color: #17a2b8; color: white; }
    </style>
</head>
<body>

<div id="main-view">
    <div class="main-container">
        <h1>Panel de Gestión (Versión PHP)</h1>
        
        <div class="container">
            <h2>Subcategorías</h2>
            <table id="subcategorias-table">
                <thead><tr><th>Categoría</th><th>Nivel</th><th>Subcategoría</th><th>Palabras</th><th style="text-align:center;">Valid. Externa</th><th class="actions-cell">Acciones</th></tr></thead>
                <tbody>
                    <?php 
                    $subcategorias = getSubcategoriasStats();
                    if (isset($subcategorias[0]['error'])) {
                        echo '<tr><td colspan="6" style="color:red;text-align:center;">Error al cargar datos: '.htmlspecialchars($subcategorias[0]['error']).'</td></tr>';
                    } elseif (empty($subcategorias)) {
                        echo '<tr><td colspan="6" style="text-align:center;">No hay datos para mostrar.</td></tr>';
                    } else {
                        foreach ($subcategorias as $item) {
                            $checkedAttribute = $item['permite_validacion_externa'] == 1 ? 'checked' : '';
                            echo "<tr>";
                            echo "<td>" . htmlspecialchars($item['categoria']) . "</td>";
                            echo "<td>" . htmlspecialchars($item['nivel']) . "</td>";
                            echo "<td>" . htmlspecialchars($item['subcategoria']) . "</td>";
                            echo "<td style='text-align:center;'>" . htmlspecialchars($item['cantidad_palabras']) . "</td>";
                            echo "<td style='text-align:center;'><input type='checkbox' disabled " . $checkedAttribute . "></td>";
                            echo "<td class='actions-cell'>";
                            echo "<a href='#' class='btn-manage'><i class='fas fa-tasks'></i> Gestionar</a> ";
                            echo "<a href='#' class='btn-edit'><i class='fas fa-pencil-alt'></i></a> ";
                            echo "<a href='#' class='btn-delete'><i class='fas fa-trash-alt'></i></a>";
                            echo "</td>";
                            echo "</tr>";
                        }
                    }
                    ?>
                </tbody>
            </table>
        </div>

        <div class="container">
            <h2>Niveles</h2>
            <table id="niveles-table">
                <thead><tr><th>Nombre</th><th class="actions-cell">Acciones</th></tr></thead>
                <tbody>
                    <?php
                    $niveles = getNiveles();
                    foreach ($niveles as $item) {
                        echo "<tr>";
                        echo "<td>" . htmlspecialchars($item['nombre']) . "</td>";
                        echo "<td class='actions-cell'>";
                        echo "<a href='#' class='btn-edit'><i class='fas fa-pencil-alt'></i> Editar</a> ";
                        echo "<a href='#' class='btn-delete'><i class='fas fa-trash-alt'></i> Eliminar</a>";
                        echo "</td>";
                        echo "</tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>

        <div class="container">
            <h2>Categorías Principales</h2>
            <table id="categorias-table">
                <thead><tr><th>Nombre</th><th class="actions-cell">Acciones</th></tr></thead>
                <tbody>
                    <?php
                    $categorias = getCategorias();
                    foreach ($categorias as $item) {
                        echo "<tr>";
                        echo "<td>" . htmlspecialchars($item['nombre']) . "</td>";
                        echo "<td class='actions-cell'>";
                        echo "<a href='#' class='btn-edit'><i class='fas fa-pencil-alt'></i> Editar</a> ";
                        echo "<a href='#' class='btn-delete'><i class='fas fa-trash-alt'></i> Eliminar</a>";
                        echo "</td>";
                        echo "</tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

</body>
</html>
