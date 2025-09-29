<?php
header('Content-Type: application/json');

$response = [];

// 1. Verificar si el archivo de configuración existe
if (!file_exists(__DIR__ . '/db_config.php')) {
    http_response_code(500);
    $response['status'] = 'error';
    $response['message'] = 'CRÍTICO: El archivo db_config.php no existe.';
    echo json_encode($response);
    exit;
}

require_once __DIR__ . '/db_config.php';

// 2. Verificar si las variables están definidas en el archivo
if (!isset($servername, $username, $password, $dbname)) {
    http_response_code(500);
    $response['status'] = 'error';
    $response['message'] = 'ERROR: Una o más variables (servername, username, password, dbname) no están definidas en db_config.php.';
    echo json_encode($response);
    exit;
}

// 3. Intentar la conexión a la base de datos
// Desactivar los errores de mysqli para manejarlos manualmente
mysqli_report(MYSQLI_REPORT_OFF);
$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    http_response_code(500);
    $response['status'] = 'error';
    $response['message'] = 'FALLO DE CONEXIÓN: No se pudo conectar a la base de datos.';
    $response['details'] = [
        'error_message' => $conn->connect_error,
        'server' => $servername,
        'user' => $username,
        'database' => $dbname
    ];
} else {
    $response['status'] = 'success';
    $response['message'] = 'ÉXITO: La conexión a la base de datos se ha establecido correctamente.';
    $conn->close();
}

echo json_encode($response);
?>