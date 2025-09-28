      <?php
      header('Content-Type: text/html; charset=utf-8');
      ini_set('display_errors', 1);
      error_reporting(E_ALL);

      // Mismos datos de conexión que en tu juego
      $servername = "localhost";
      $username = "tuttiquanti";
      $password = "tuttiquanti";
      $dbname = "tuttiquanti";

      echo "<h1>Prueba de Conexión a la Base de Datos</h1>";

      // Intentar conectar
      $conn = new mysqli($servername, $username, $password, $dbname);

      // Comprobar conexión
      if ($conn->connect_error) {
          echo "<p style='color: red; font-weight: bold;'>Error al conectar a la base de datos: </p>";
          echo "<p>" . $conn->connect_error . "</p>";
      } else {
          echo "<p style='color: green; font-weight: bold;'>¡Conexión a la base de datos '" . $dbname . 
  "' fue exitosa!</p>";
          $conn->close();
      }
      ?>