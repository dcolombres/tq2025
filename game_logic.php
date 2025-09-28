<?php
header('Content-Type: application/json');
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/db_config.php';

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die(json_encode(["error" => "Connection failed: " . $conn->connect_error]));
}
$conn->set_charset("utf8");

// --- Logic Router ---
$action = $_GET['action'] ?? '';

if ($action === 'get_random_category') {
    getRandomCategory($conn);
} elseif ($action === 'validate_word') {
    validateWord($conn);
} else {
    echo json_encode(["error" => "Invalid action specified."]);
}

// --- Functions ---

/**
 * Fetches a random subcategory from the database.
 */
function getRandomCategory($conn) {
    $categoria_id = isset($_GET['categoria_id']) ? (int)$_GET['categoria_id'] : 0;

    if ($categoria_id === 0) {
        http_response_code(400);
        echo json_encode(["error" => "ID de categoría principal no proporcionado."]);
        exit;
    }

    $sql = "SELECT s.id, s.nombre, s.permite_validacion_externa, n.nombre as nivel_nombre 
            FROM Subcategorias s
            JOIN Niveles n ON s.nivel_id = n.id
            WHERE s.categoria_id = ? 
            ORDER BY RAND() 
            LIMIT 1";
    
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        http_response_code(500);
        die(json_encode(['error' => 'Error al preparar la consulta: ' . $conn->error]));
    }

    $stmt->bind_param("i", $categoria_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result && $result->num_rows > 0) {
        $category = $result->fetch_assoc();
        echo json_encode($category);
    } else {
        echo json_encode(["error" => "No se encontraron subcategorías para la categoría principal seleccionada."]);
    }
    $stmt->close();
}

/**
 * Validates a word against the database for a given subcategory and letter.
 */
function validateWord($conn) {
    $input = json_decode(file_get_contents('php://input'), true);

    $word = $input['word'] ?? '';
    $letter = $input['letter'] ?? '';
    $subcategoryId = $input['subcategory_id'] ?? 0;
    $allows_external_validation = $input['allows_external_validation'] ?? false;

    if (empty($word)) {
        echo json_encode(['status' => 'VACIO', 'explanation' => 'Sin respuesta']);
        return;
    }

    // Normalize letter and first character of the word for comparison
    $firstChar = mb_substr($word, 0, 1, 'UTF-8');
    if (strcasecmp($firstChar, $letter) !== 0) {
        echo json_encode(['status' => 'INCORRECTO', 'explanation' => 'La palabra no comienza con la letra correcta.']);
        return;
    }

    // Prepare statement to check if the word exists for the given subcategory
    $sql = "SELECT palabra FROM Palabras WHERE subcategoria_id = ? AND palabra = ?";
    $stmt = $conn->prepare($sql);
    
    if (!$stmt) {
        die(json_encode(["error" => "Error preparing statement: " . $conn->error]));
    }

    $stmt->bind_param("is", $subcategoryId, $word);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $dbWord = $result->fetch_assoc()['palabra'];
        echo json_encode([
            'status' => 'CORRECTO', 
            'explanation' => "Palabra válida: " . $dbWord
        ]);
    } else {
        // Word not found, check for typos (Levenshtein distance)
        $sql_similar = "SELECT palabra FROM Palabras WHERE subcategoria_id = ? AND palabra LIKE ?";
        $stmt_similar = $conn->prepare($sql_similar);
        if (!$stmt_similar) {
            die(json_encode(["error" => "Error preparing similar word statement: " . $conn->error]));
        }
        
        $like_letter = $letter . '%';
        $stmt_similar->bind_param("is", $subcategoryId, $like_letter);
        $stmt_similar->execute();
        $result_similar = $stmt_similar->get_result();

        $best_match = null;
        $min_distance = 3; // Allow up to 2 typos

        while ($row = $result_similar->fetch_assoc()) {
            $distance = levenshtein(strtolower($word), strtolower($row['palabra']));
            if ($distance < $min_distance) {
                $min_distance = $distance;
                $best_match = $row['palabra'];
            }
        }

        if ($best_match !== null) {
            echo json_encode([
                'status' => 'MAL_ESCRITO',
                'explanation' => 'Casi! Quisiste decir: ' . $best_match . '?'
            ]);
        } else {
            // Final check: external validation if allowed by the category
            if ($allows_external_validation) {
                
                // =====================================================================================
                // DOCUMENTACIÓN PARA FUTURO DESARROLLADOR: INICIO
                // =====================================================================================

                // PASO 1: VERIFICAR EXISTENCIA DE LA PALABRA EN LA RAE (usando cURL)
                // Se usa cURL por ser más robusto que get_headers en entornos locales como XAMPP.
                
                $rae_api_url = "http://api.rae-api.com./word/" . urlencode(strtolower($word)); // Se convierte a minúsculas y se añade un punto al dominio.
                
                $ch = curl_init($rae_api_url);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
                curl_exec($ch);
                $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                curl_close($ch);

                if ($http_code == 200) {
                    // La palabra existe en la RAE. Ahora procedemos a verificar la relevancia.

                    // PASO 2: VERIFICAR RELEVANCIA CON UN MODELO DE LENGUAJE (IA)
                    // Esta sección debe ser implementada con una llamada a un servicio como Gemini, GPT, etc.
                    // El objetivo es preguntarle al modelo si la palabra es contextualmente correcta para la categoría.
                    
                    // EJEMPLO DE IMPLEMENTACIÓN FUTURA:
                    // 1. Obtener el nombre de la categoría de la base de datos usando $subcategoryId.
                    // 2. Construir un prompt: "En la categoría 'Animales', ¿es la palabra 'árbol' una respuesta válida? Responde solo con un JSON: {\"esValida\": boolean}"
                    // 3. Realizar la llamada a la API del modelo de lenguaje con ese prompt.
                    // 4. Analizar la respuesta JSON y si "esValida" es true, devolver el estado VALIDADO_EXTERNAMENTE.

                    // POR AHORA, SIMULAMOS QUE LA RESPUESTA SIEMPRE ES RELEVANTE PARA DEMOSTRAR EL FLUJO.
                    echo json_encode([
                        'status' => 'VALIDADO_EXTERNAMENTE',
                        'explanation' => 'Palabra OK por RAE (relevancia simulada).'
                    ]);

                } else {
                    // La palabra no fue encontrada en el diccionario de la RAE (código HTTP no fue 200).
                    echo json_encode([
                        'status' => 'INCORRECTO',
                        'explanation' => 'La palabra no supero las verificaciones de la base de datos ni de fuentes externas'
                    ]);
                }

                // =====================================================================================
                // DOCUMENTACIÓN PARA FUTURO DESARROLLADOR: FIN
                // =====================================================================================

            } else {
                // La validación externa no está permitida para esta categoría.
                echo json_encode([
                    'status' => 'INCORRECTO',
                    'explanation' => 'La palabra no se encontró en esta categoría.'
                ]);
            }
        }
        $stmt_similar->close();
    }
    $stmt->close();
}

$conn->close();
?>
