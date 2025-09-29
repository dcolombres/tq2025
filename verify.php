<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once 'game_logic.php';



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