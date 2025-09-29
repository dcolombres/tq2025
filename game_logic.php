<?php
header('Content-Type: application/json');
error_reporting(0);
ini_set('display_errors', 0);

require 'db_config.php';

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    http_response_code(500);
    die(json_encode(["error" => "Connection failed: " . $conn->connect_error]));
}
$conn->set_charset("utf8");

$action = $_GET['action'] ?? '';

if ($action === 'validate_word') {
    $input = json_decode(file_get_contents('php://input'), true);
    validateWord($conn, $input);
} else {
    http_response_code(400);
    echo json_encode(["error" => "Invalid action specified."]);
}

function validateWithWikipedia($word, $gameCategory) {
    $apiUrl = "https://es.wikipedia.org/w/api.php";
    $userAgent = 'TuttiQuantiGame/1.0 (https://www.tuttiquanti.com; game@tuttiquanti.com)';

    // Step 1: Search for the article
    $searchParams = http_build_query([
        "action" => "query", "list" => "search", "srsearch" => $word,
        "format" => "json", "srlimit" => 1, "utf8" => 1
    ]);
    $ch = curl_init($apiUrl . "?" . $searchParams);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_USERAGENT, $userAgent);
    $searchResult = curl_exec($ch);
    curl_close($ch);
    $searchData = json_decode($searchResult, true);

    if (empty($searchData['query']['search'])) return false;
    $pageTitle = $searchData['query']['search'][0]['title'];

    // Step 2: Get categories for the article
    $categoryParams = http_build_query([
        "action" => "query", "titles" => $pageTitle, "prop" => "categories",
        "format" => "json", "cllimit" => "max", "utf8" => 1
    ]);
    $ch = curl_init($apiUrl . "?" . $categoryParams);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_USERAGENT, $userAgent);
    $categoryResult = curl_exec($ch);
    curl_close($ch);
    $categoryData = json_decode($categoryResult, true);
    $pages = $categoryData['query']['pages'] ?? [];
    $pageId = array_key_first($pages);

    if (!isset($pages[$pageId]['categories'])) return false;

    foreach ($pages[$pageId]['categories'] as $category) {
        $wikiCategoryTitle = str_replace("Categoría:", "", $category['title']);
        if (stripos($wikiCategoryTitle, $gameCategory) !== false) {
            return true;
        }
    }
    return false;
}

function validateWord($conn, $input) {
    $word = $input['word'] ?? '';
    $letter = $input['letter'] ?? '';
    $subcategoryId = $input['subcategory_id'] ?? 0;
    $allows_external_validation = $input['allows_external_validation'] ?? false;

    if (empty($word)) {
        echo json_encode(['status' => 'VACIO', 'explanation' => 'Sin respuesta', 'source' => 'Sistema']);
        return;
    }

    $firstChar = mb_substr($word, 0, 1, 'UTF-8');
    if (strcasecmp($firstChar, $letter) !== 0) {
        echo json_encode(['status' => 'INCORRECTO', 'explanation' => 'No comienza con la letra correcta.', 'source' => 'Sistema']);
        return;
    }

    // 1. Check internal DB
    $stmt = $conn->prepare("SELECT palabra FROM palabras WHERE subcategoria_id = ? AND palabra = ?");
    $stmt->bind_param("is", $subcategoryId, $word);
    $stmt->execute();
    $stmt->store_result();
    if ($stmt->num_rows > 0) {
        echo json_encode(['status' => 'CORRECTO', 'explanation' => "Palabra válida: " . $word, 'source' => 'Base de Datos']);
        $stmt->close();
        return;
    }
    $stmt->close();

    // 2. External validation if allowed
    if ($allows_external_validation) {
        $stmt_cat = $conn->prepare("SELECT nombre FROM subcategorias WHERE id = ?");
        $stmt_cat->bind_param("i", $subcategoryId);
        $stmt_cat->execute();
        $stmt_cat->store_result();
        $stmt_cat->bind_result($subcategoryName);
        $stmt_cat->fetch();
        $stmt_cat->close();

        if ($subcategoryName && validateWithWikipedia($word, $subcategoryName)) {
            echo json_encode(['status' => 'VALIDADO_EXTERNAMENTE', 'explanation' => 'Palabra validada por Wikipedia.', 'source' => 'Wikipedia']);
            return;
        }
    }

    // 3. Levenshtein typo check as a last resort
    $stmt_similar = $conn->prepare("SELECT palabra FROM palabras WHERE subcategoria_id = ? AND palabra LIKE ?");
    $like_letter = $letter . '%';
    $stmt_similar->bind_param("is", $subcategoryId, $like_letter);
    $stmt_similar->execute();
    $stmt_similar->store_result();
    $stmt_similar->bind_result($db_word);

    $best_match = null;
    $min_distance = 3;
    while ($stmt_similar->fetch()) {
        $distance = levenshtein(strtolower($word), strtolower($db_word));
        if ($distance < $min_distance) {
            $min_distance = $distance;
            $best_match = $db_word;
        }
    }
    $stmt_similar->close();

    if ($best_match !== null) {
        echo json_encode(['status' => 'MAL_ESCRITO', 'explanation' => 'Casi! Quisiste decir: ' . $best_match . '?', 'source' => 'Sistema']);
    } else {
        echo json_encode(['status' => 'INCORRECTO', 'explanation' => 'La palabra no se encontró.', 'source' => 'Sistema']);
    }
}

$conn->close();
?>