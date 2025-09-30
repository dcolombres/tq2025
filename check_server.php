<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

header('Content-Type: text/html; charset=utf-8');

echo "<h1>Diagnóstico del Servidor</h1>";

// --- 1. Verificar existencia de db_config.php ---
echo "<h2>Paso 1: Verificando `db_config.php`</h2>";
$config_file = __DIR__ . '/db_config.php';

if (file_exists($config_file) && is_readable($config_file)) {
    echo "<p style='color: green; font-weight: bold;'>✔ El archivo `db_config.php` existe y es legible.</p>";
    
    // --- 2. Incluir el archivo y verificar variables ---
    echo "<h2>Paso 2: Cargando credenciales</h2>";
    require_once $config_file;
    
    $credentials_ok = true;
    if (!isset($servername) || empty($servername)) {
        echo "<p style='color: red;'>❌ La variable <code>\$servername</code> no está definida o está vacía en `db_config.php`.</p>";
        $credentials_ok = false;
    }
    if (!isset($username) || empty($username)) {
        echo "<p style='color: red;'>❌ La variable <code>\$username</code> no está definida o está vacía.</p>";
        $credentials_ok = false;
    }
    if (!isset($password)) { // password can be empty
        echo "<p style='color: red;'>❌ La variable <code>\$password</code> no está definida.</p>";
        $credentials_ok = false;
    }
    if (!isset($dbname) || empty($dbname)) {
        echo "<p style='color: red;'>❌ La variable <code>\$dbname</code> no está definida o está vacía.</p>";
        $credentials_ok = false;
    }

    if ($credentials_ok) {
        echo "<p style='color: green; font-weight: bold;'>✔ Todas las variables de conexión existen.</p>";
        
        // --- 3. Intentar conexión a la BD ---
        echo "<h2>Paso 3: Intentando conectar a la base de datos</h2>";
        
        // Ocultar errores de mysqli para dar un mensaje personalizado
        mysqli_report(MYSQLI_REPORT_OFF);
        $conn = new mysqli($servername, $username, $password, $dbname);

        if ($conn->connect_error) {
            echo "<p style='color: red; font-weight: bold;'>❌ Error de Conexión a la Base de Datos</p>";
            echo "<p><strong>Mensaje:</strong> (" . $conn->connect_errno . ") " . $conn->connect_error . "</p>";
            echo "<p><strong>Recomendaciones:</strong></p>";
            echo "<ul>";
            echo "<li>Verifica que el nombre del servidor (<code>$servername</code>), el usuario (<code>$username</code>) y la contraseña sean correctos para la base de datos de tu <strong>servidor</strong>.</li>";
            echo "<li>Asegúrate de que el usuario <code>$username</code> tenga permisos para acceder a la base de datos <code>$dbname</code>.</li>";
            echo "<li>Confirma que el servidor de base de datos está en ejecución y accesible.</li>";
            echo "</ul>";
        } else {
            echo "<p style='color: green; font-weight: bold;'>✔ ¡Conexión a la base de datos exitosa!</p>";
            $conn->close();
        }
    } else {
        echo "<p style='color: red; font-weight: bold;'>No se puede continuar con la prueba de conexión porque faltan credenciales.</p>";
    }

} else {
    echo "<p style='color: red; font-weight: bold;'>❌ Error Crítico: El archivo `db_config.php` no se encuentra o no se puede leer.</p>";
    echo "<p>Este es el problema más probable. Debes crear el archivo <code>db_config.php</code> en el directorio raíz de la aplicación en tu servidor y llenarlo con las credenciales de la base de datos del servidor.</p>";
}

echo "<hr>";
echo "<p><em>Diagnóstico finalizado.</em></p>";
?>