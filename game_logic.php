<?php
header('Content-Type: application/json');
error_reporting(E_ALL);
ini_set('display_errors', 1);

// --- Database Connection ---
$servername = "localhost";
$username = "tuttiquanti";
$password = "tuttiquanti";
$dbname = "tuttiquanti";

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
/**
 * Validates a word using the Wikipedia API.
 *
 * @param string $word The word to validate.
 * @param string $gameCategory The name of the game's subcategory for context.
 * @return bool True if the word is valid and relevant, false otherwise.
 */
function validateWithWikipedia($word, $gameCategory) {
    $apiUrl = "https://es.wikipedia.org/w/api.php";

    // --- Step 1: Search for the article to get the exact title ---
    $searchParams = http_build_query([
        "action" => "query",
        "list" => "search",
        "srsearch" => $word,
        "format" => "json",
        "srlimit" => 1,
        "utf8" => 1
    ]);

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $apiUrl . "?" . $searchParams);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $searchResult = curl_exec($ch);
    curl_close($ch);

    $searchData = json_decode($searchResult, true);

    if (empty($searchData['query']['search'])) {
        return false; // No article found
    }
    $pageTitle = $searchData['query']['search'][0]['title'];

    // --- Step 2: Get the categories for that article ---
    $categoryParams = http_build_query([
        "action" => "query",
        "titles" => $pageTitle,
        "prop" => "categories",
        "format" => "json",
        "cllimit" => "max",
        "utf8" => 1
    ]);

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $apiUrl . "?" . $categoryParams);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $categoryResult = curl_exec($ch);
    curl_close($ch);

    $categoryData = json_decode($categoryResult, true);
    $pages = $categoryData['query']['pages'];
    $pageId = array_key_first($pages);

    if (!isset($pages[$pageId]['categories'])) {
        return false; // Article has no categories
    }

    // --- Step 3: Check if any Wikipedia category matches the game category ---
    foreach ($pages[$pageId]['categories'] as $category) {
        $wikiCategoryTitle = str_replace("Categoría:", "", $category['title']);
        // Use case-insensitive comparison to see if the game category is part of the Wikipedia category
        if (stripos($wikiCategoryTitle, $gameCategory) !== false) {
            return true; // Found a relevant category
        }
    }

    return false; // No relevant categories found
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

    $firstChar = mb_substr($word, 0, 1, 'UTF-8');
    if (strcasecmp($firstChar, $letter) !== 0) {
        echo json_encode(['status' => 'INCORRECTO', 'explanation' => 'La palabra no comienza con la letra correcta.']);
        return;
    }

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
            'explanation' => "Palabra válida: " . $dbWord,
            'source' => 'Base de Datos'
        ]);
    } else {
        // Word not found in DB. Proceed with external validation or typo checking.

        if ($allows_external_validation) {
            // --- External Validation Flow ---

            // 1. Get Subcategory Name for context
            $stmt_cat = $conn->prepare("SELECT nombre FROM Subcategorias WHERE id = ?");
            $stmt_cat->bind_param("i", $subcategoryId);
            $stmt_cat->execute();
            $result_cat = $stmt_cat->get_result();
            $subcategoryName = $result_cat->fetch_assoc()['nombre'] ?? '';
            $stmt_cat->close();

            if ($subcategoryName) {
                // 2. Wikipedia Validation
                if (validateWithWikipedia($word, $subcategoryName)) {
                    echo json_encode([
                        'status' => 'VALIDADO_EXTERNAMENTE',
                        'explanation' => 'Palabra validada por Wikipedia.',
                        'source' => 'Wikipedia'
                    ]);
                    return;
                }
            }

            // 3. RAE Validation
            $rae_api_url = "http://api.rae-api.com./word/" . urlencode(strtolower($word));
            $ch_rae = curl_init($rae_api_url);
            curl_setopt($ch_rae, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch_rae, CURLOPT_FOLLOWLOCATION, true);
            curl_exec($ch_rae);
            $http_code = curl_getinfo($ch_rae, CURLINFO_HTTP_CODE);
            curl_close($ch_rae);

            if ($http_code == 200) {
                echo json_encode([
                    'status' => 'VALIDADO_EXTERNAMENTE',
                    'explanation' => 'Palabra encontrada en la RAE (relevancia no verificada).',
                    'source' => 'RAE'
                ]);
                return;
            }
        }

        // --- Typo Checking (Levenshtein) as a last resort ---
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
        $stmt_similar->close();

        if ($best_match !== null) {
            echo json_encode([
                'status' => 'MAL_ESCRITO',
                'explanation' => 'Casi! Quisiste decir: ' . $best_match . '?'
            ]);
        } else {
            // Nothing worked, the word is incorrect.
            echo json_encode([
                'status' => 'INCORRECTO',
                'explanation' => 'La palabra no se encontró en la base de datos ni en fuentes externas.'
            ]);
        }
    }
    $stmt->close();
}

$conn->close();
?>
