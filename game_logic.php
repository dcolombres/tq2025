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
    $sql = "SELECT id, nombre FROM Subcategorias ORDER BY RAND() LIMIT 1";
    $result = $conn->query($sql);

    if ($result && $result->num_rows > 0) {
        $category = $result->fetch_assoc();
        echo json_encode($category);
    } else {
        echo json_encode(["error" => "No categories found."]);
    }
}

/**
 * Validates a word against the database for a given subcategory and letter.
 */
function validateWord($conn) {
    $input = json_decode(file_get_contents('php://input'), true);

    $word = $input['word'] ?? '';
    $letter = $input['letter'] ?? '';
    $subcategoryId = $input['subcategory_id'] ?? 0;

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
        echo json_encode([
            'status' => 'INCORRECTO', 
            'explanation' => 'La palabra no se encontró en esta categoría.'
        ]);
    }
    $stmt->close();
}

$conn->close();
?>
