<?php
$isEditor = isset($_GET['editor']) && $_GET['editor'] === 'true';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>TUTTI QUANTI - The Alphabet Challenge</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://fonts.googleapis.com/css2?family=Lato:wght@400;700;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="style.css">
    <style>
        .editor-links {
            text-align: center;
            padding: 10px;
            background-color: #3F2A56;
            margin-top: 20px;
        }
        .editor-links a {
            color: white;
            margin: 0 15px;
            text-decoration: none;
            font-weight: bold;
        }
        .editor-links a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>

<div class="game-container">
    <!-- Selección de Categoría Principal -->
    <div id="category-selection-container" class="category-selector">
        <div class="logo-container"><img src="https://moroarte.com/wp-content/uploads/2023/09/logoTUTTIQUANTI-154x300.png" alt="Tutti Quanti Logo" class="logo"></div>
        <h2>Elegí un modo de juego</h2>
        <div id="category-buttons" class="category-buttons">
            <!-- Los botones de categoría se cargarán aquí dinámicamente -->
        </div>
        <button id="instructions-button" class="instructions-trigger"><i class="fas fa-info-circle"></i></button>
    </div>

    <!-- Encabezado del Juego -->
    <div class="game-header">
        <div class="category-display">
            <span class="label-mode"></span>
            <span class="label-level"></span>
            <span class="category-text"></span>
        </div>
        <div class="timer">02:40</div>
        <div class="round-counter"></div>
        <div class="header-controls">
            <button id="new-round-button"><i class="fas fa-redo"></i> Nueva ronda</button>
            <button id="change-mode-button"><i class="fas fa-exchange-alt"></i> Cambiar Modo</button>
            <button id="instructions-button-header" class="instructions-trigger"><i class="fas fa-info-circle"></i></button>
        </div>
    </div>

    <!-- Grilla del Alfabeto -->
    <div class="alphabet-grid"></div>

    <!-- Botón de Finalizar -->
    <button id="finishButton" class="finish-button"><i class="fas fa-check-circle"></i> TUTTI QUANTI</button>

    <!-- Contenedor de Resultados (Oculto) -->
    <div class="results-container" id="resultsContainer">
        <h2 class="results-title">Resultados</h2>
        <div class="word-list" id="wordList"></div>
    </div>
</div>

<!-- Historial de Puntuaciones -->
<div id="history-log-container"></div>

<div class="footer">
    <div class="copyright">
        Tutti Quanti (2022) Derechos Reservados - Moro Colombres - <a href="https://www.moroarte.com">www.moroarte.com</a>
    </div>
</div>

<?php if ($isEditor): ?>
<div class="editor-links">
    <a href="gestion.php" target="_blank">Gestionar Contenido</a>
    <a href="insert.php" target="_blank">Insertar Palabras</a>
</div>
<?php endif; ?>

<!-- Modal de Resultados Finales -->
<div id="resultsModal" class="modal">
  <div class="modal-content">
    <span class="close-modal">&times;</span>
    <div id="modalResults"></div>
  </div>
</div>

<!-- Generic Alert Modal -->
<div id="alert-modal" class="modal">
    <div class="modal-content">
        <span class="close-modal">&times;</span>
        <h3 id="alert-modal-title" style="color: var(--primary-color); margin-top: 0;">Aviso</h3>
        <p id="alert-modal-text" style="line-height: 1.6;"></p>
    </div>
</div>

<!-- Loading Modal -->
<div id="loading-modal" class="modal">
    <div class="modal-content" style="text-align: center;">
        <h2 id="loading-message">Verificando respuestas...</h2>
        <i class="fas fa-spinner fa-spin" style="font-size: 48px; color: var(--primary-color); margin-top: 20px;"></i>
    </div>
</div>

<!-- Modal de Instrucciones -->
<div id="instructions-modal" class="modal">
    <div class="modal-content">
        <span class="close-modal">&times;</span>
        <h3>INSTRUCCIONES DEL JUEGO</h3>
        <p>
            1. Elegí un <b>modo de juego</b> para empezar. Se te asignará una categoría al azar dentro de ese modo y comenzará la cuenta regresiva.<br><br>
            2. Intentá completar una palabra para cada letra del abecedario. Tu respuesta será validada de varias formas:<br>
            &emsp;• Primero, se busca en la <b>Base de Datos</b> interna del juego.<br>
            &emsp;• Si no se encuentra y la categoría lo permite, se consulta a fuentes externas como <b>Wikipedia</b> (para verificar la palabra y su relevancia) y la <b>RAE</b>.<br>
            &emsp;• Si todo falla, el sistema busca si fue un pequeño <b>error de tipeo</b> de una palabra ya existente.<br><br>
            3. El sistema de puntos es el siguiente:<br>
            &emsp;• <b>Palabra Perfecta</b> (en la Base de Datos): <b>+10 puntos</b><br>
            &emsp;• <b>Casi Correcta</b> (error de tipeo menor): <b>+7 puntos</b><br>
            &emsp;• <b>Validada Externamente</b> (por Wikipedia/RAE): <b>+5 puntos</b><br>
            &emsp;• <b>Incorrecta</b> (no encontrada): <b>-3 puntos</b><br>
            &emsp;• <b>Vacía</b>: <b>- 3 puntos</b><br><br>
            4. Presioná <b>TUTTI QUANTI</b> cuando termines. ¡Cuanto más rápido, más puntos de bonus por tiempo!<br><br>
            5. Al final, podrás revisar la fuente de validación para cada una de tus respuestas (Base de Datos, Wikipedia, etc.).
        </p>
    </div>
</div>

<script src="script.js"></script> 
</body>
</html>