<?php
// Change content type to HTML for debugging
header('Content-Type: text/html; charset=utf-8');
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<pre>";
echo "--- INICIANDO DEBUG EN get_subcategorias.php ---\n\n";

echo "Versión de PHP: " . PHP_VERSION . "\n";

require_once __DIR__ . '/db_config.php';
echo "db_config.php cargado.\n";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("ERROR DE CONEXIÓN: " . $conn->connect_error);
}
echo "Conexión a la BD exitosa. Usuario: $username, BD: $dbname\n\n";

$categoria_id = isset($_GET['categoria_id']) ? (int)$_GET['categoria_id'] : 0;
echo "ID de Categoría recibido: $categoria_id\n\n";

if ($categoria_id > 0) {
    $sql = "SELECT id, nombre FROM subcategorias WHERE categoria_id = ?";
    echo "SQL a preparar: $sql\n";

    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        die("ERROR en prepare(): " . $conn->error);
    }
    echo "Prepare() exitoso.\n";

    $stmt->bind_param("i", $categoria_id);
    echo "Bind exitoso.\n";

    if (!$stmt->execute()) {
        die("ERROR en execute(): " . $stmt->error);
    }
    echo "Execute() exitoso.\n";

    $result = $stmt->get_result();
    if (!$result) {
        die("ERROR en get_result(): " . $stmt->error);
    }
    echo "get_result() exitoso.\n\n";

    echo "Número de filas encontradas: " . $result->num_rows . "\n\n";

    if ($result->num_rows > 0) {
        echo "Resultados:\n";
        while ($row = $result->fetch_assoc()) {
            print_r($row);
        }
    } else {
        echo "La consulta no devolvió resultados.\n";
    }
    
    $stmt->close();
} else {
    echo "No se proporcionó un categoria_id válido.\n";
}

$conn->close();
echo "\n--- FIN DEL DEBUG ---";
echo "</pre>";
?>