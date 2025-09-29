<?php
     /**
      * debug_subcategorias.php
      * Script de depuración para identificar problemas con las subcategorías
      */

     header('Content-Type: application/json');
     error_reporting(E_ALL);
     ini_set('display_errors', 1);

     // --- Conexión a la Base de Datos ---
     require_once __DIR__ . '/db_config.php';

     $debug_info = [];

     try {
         $conn = new mysqli($servername, $username, $password, $dbname);

         if ($conn->connect_error) {
             $debug_info['connection_error'] = $conn->connect_error;
             echo json_encode($debug_info);
             exit;
         }

         $debug_info['connection'] = 'SUCCESS';

         // Verificar si existen las tablas
         $tables = ['categorias', 'niveles', 'subcategorias', 'palabras'];
         foreach ($tables as $table) {
             $result = $conn->query("SHOW TABLES LIKE '$table'");
             $debug_info['tables'][$table] = $result && $result->num_rows > 0 ? 'EXISTS' : 'MISSING';
         }

         // Contar registros en cada tabla
         foreach ($tables as $table) {
             if ($debug_info['tables'][$table] === 'EXISTS') {
                 $result = $conn->query("SELECT COUNT(*) as count FROM $table");
                 $row = $result->fetch_assoc();
                 $debug_info['counts'][$table] = $row['count'];
             }
         }

         // Verificar estructura de subcategorias
         if ($debug_info['tables']['subcategorias'] === 'EXISTS') {
             $result = $conn->query("DESCRIBE subcategorias");
             $debug_info['subcategorias_structure'] = [];
             while ($row = $result->fetch_assoc()) {
                 $debug_info['subcategorias_structure'][] = $row;
             }
         }

         // Probar la consulta completa de get_stats.php
         $sql = "
             SELECT
                 s.id AS subcategoria_id,
                 c.nombre AS categoria,
                 n.nombre AS nivel,
                 s.nombre AS subcategoria,
                 s.permite_validacion_externa,
                 COUNT(p.id) AS cantidad_palabras
             FROM
                 subcategorias s
             JOIN
                 categorias c ON s.categoria_id = c.id
             LEFT JOIN
                 niveles n ON s.nivel_id = n.id
             LEFT JOIN
                 palabras p ON s.id = p.subcategoria_id
             GROUP BY
                 s.id, c.nombre, n.nombre, s.nombre, s.permite_validacion_externa
             ORDER BY
                 c.nombre, n.nombre, s.nombre
             LIMIT 5
         ";

         $result = $conn->query($sql);

         if ($result) {
             $debug_info['query_result'] = 'SUCCESS';
             $debug_info['sample_data'] = [];
             while ($row = $result->fetch_assoc()) {
                 $debug_info['sample_data'][] = $row;
             }
         } else {
             $debug_info['query_result'] = 'ERROR';
             $debug_info['query_error'] = $conn->error;
         }

     } catch (Exception $e) {
         $debug_info['exception'] = $e->getMessage();
     }

     echo json_encode($debug_info, JSON_PRETTY_PRINT);

     if (isset($conn)) {
         $conn->close();
     }
     ?>