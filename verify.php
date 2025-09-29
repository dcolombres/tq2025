<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

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
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/58.0.3029.110 Safari/537.36');
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
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/58.0.3029.110 Safari/537.36');
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

function testWikipediaValidation($word, $category) {
    echo "<h3>Verificando '{$word}' en la categoría '{$category}' con Wikipedia...</h3>";
    $result = validateWithWikipedia($word, $category);
    if ($result) {
        echo "<p style='color:green;'>'{$word}' es una palabra válida en la categoría '{$category}' según Wikipedia.</p>";
    } else {
        echo "<p style='color:red;'>'{$word}' NO es una palabra válida en la categoría '{$category}' según Wikipedia.</p>";
    }
}

function testRAEValidation($word) {
    echo "<h3>Verificando '{$word}' con la RAE...</h3>";
    $rae_url = "https://dle.rae.es/?w=" . urlencode(strtolower($word));
    $ch_rae = curl_init($rae_url);
    curl_setopt($ch_rae, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch_rae, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch_rae, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/58.0.3029.110 Safari/537.36');
    $response = curl_exec($ch_rae);
    $http_code = curl_getinfo($ch_rae, CURLINFO_HTTP_CODE);
    curl_close($ch_rae);

    if ($http_code == 200) {
        $dom = new DOMDocument();
        @$dom->loadHTML($response);
        $xpath = new DOMXPath($dom);
        $not_found_node = $xpath->query('//h2[@class="c-page-header__title"]');

        if ($not_found_node->length > 0 && stripos($not_found_node[0]->nodeValue, "no está en el Diccionario") !== false) {
            echo "<p style='color:red;'>'{$word}' NO fue encontrada en la RAE.</p>";
        } else {
            echo "<p style='color:green;'>'{$word}' fue encontrada en la RAE.</p>";
        }
    } else {
        echo "<p style='color:red;'>'{$word}' NO fue encontrada en la RAE (Error HTTP: {$http_code}).</p>";
    }
}

// --- Pruebas ---

testWikipediaValidation('Argentina', 'Países');
testRAEValidation('casa');

echo "<hr>";

testWikipediaValidation('asdasdasd', 'Países');
testRAEValidation('asdasdasd');

?>