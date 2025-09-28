<?php

header('Content-Type: text/html; charset=utf-8');
ini_set('display_errors', 1);
error_reporting(E_ALL);

echo "<h1>Prueba de Conexión Externa con cURL</h1>";

// URLs de prueba
$test_urls = array(
    "https://34.120.147.123",
    "https://httpbin.org/get"
);

foreach ($test_urls as $test_url) {
    echo "<h2>Probando conexión a: " . htmlspecialchars($test_url) . "</h2>";

    $ch = curl_init($test_url);

    // Configuración mejorada de cURL
    curl_setopt_array($ch, array(
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => 0,
        CURLOPT_VERBOSE => true,
        CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)',
        CURLOPT_HTTPHEADER => array(
            'Accept: application/json',
            'Accept-Language: es-ES,es;q=0.9',
            'Connection: keep-alive'
        )
    ));

    // Buffer para capturar información verbose
    $verbose = fopen('php://temp', 'w+');
    curl_setopt($ch, CURLOPT_STDERR, $verbose);

    // Ejecutar la petición
    $response = curl_exec($ch);

    // Información detallada de diagnóstico
    echo "<h3>Información de Diagnóstico:</h3>";
    echo "<pre>";
    echo "Version cURL: " . curl_version()['version'] . "\n";
    echo "Version SSL: " . curl_version()['ssl_version'] . "\n";
    echo "IP Local: " . $_SERVER['SERVER_ADDR'] . "\n";
    echo "Protocolo: " . (strpos($test_url, 'https') === 0 ? 'HTTPS' : 'HTTP') . "\n";
    echo "</pre>";

    // Comprobar si hubo errores
    if (curl_errno($ch)) {
        $error_msg = curl_error($ch);
        echo "<p style='color: red; font-weight: bold;'>Error de cURL: </p>";
        echo "<p>" . htmlspecialchars($error_msg) . "</p>";
        
        // Mostrar información verbose en caso de error
        rewind($verbose);
        $verboseLog = stream_get_contents($verbose);
        echo "<h3>Detalles de la conexión:</h3>";
        echo "<pre>" . htmlspecialchars($verboseLog) . "</pre>";
    } else {
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        if ($http_code >= 200 && $http_code < 300) {
            echo "<p style='color: green; font-weight: bold;'>¡Conexión exitosa!</p>";
        } else {
            echo "<p style='color: orange; font-weight: bold;'>Conexión establecida pero con código HTTP: " . $http_code . "</p>";
        }
        echo "<p>Respuesta (primeros 200 caracteres):</p>";
        echo "<pre>" . htmlspecialchars(substr($response, 0, 200)) . "...</pre>";
    }

    // Mostrar información detallada de la transferencia
    $info = curl_getinfo($ch);
    echo "<h3>Información de la transferencia:</h3>";
    echo "<pre>";
    print_r($info);
    echo "</pre>";

    echo "<hr>";

    curl_close($ch);
    fclose($verbose);
}

// Información del sistema
echo "<h2>Información del Sistema:</h2>";
echo "<pre>";
echo "PHP Version: " . phpversion() . "\n";
echo "Sistema Operativo: " . PHP_OS . "\n";
echo "Servidor Web: " . $_SERVER['SERVER_SOFTWARE'] . "\n";
echo "Extensiones PHP cargadas:\n";
print_r(get_loaded_extensions());
echo "</pre>";
?>