<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TQ MODO PARTY</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #3b096dff;
            margin: 0;
            padding: 20px;
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            color: #333;
        }

        .container {
            background: white;
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            text-align: center;
            max-width: 500px;
            width: 100%;
        }

        .timer {
            font-size: 2.5em;
            font-weight: bold;
            margin-bottom: 20px;
            font-family: 'Courier New', monospace;
        }

        .timer.urgent {
            color: #e74c3c;
            animation: pulse 1s infinite;
        }

        @keyframes pulse {
            0% { opacity: 1; }
            50% { opacity: 0.5; }
            100% { opacity: 1; }
        }

        .card {
            background: #f8f9fa;
            border: 2px solid #dee2e6;
            border-radius: 10px;
            padding: 20px;
            margin: 20px 0;
            min-height: 120px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            transition: all 0.4s ease;
            overflow: hidden;
        }

        .card.flip {
            transform: rotateY(360deg);
            background: #e3f2fd;
            border-color: #2196f3;
        }

        .level-display {
            margin-bottom: 8px;
        }

        .level-label {
            font-size: 0.8em;
            font-weight: bold;
            padding: 4px 10px;
            border-radius: 50px;
            color: white;
            text-transform: uppercase;
        }

        .level-facil { background-color: #28a745; }
        .level-medio { background-color: #007bff; }
        .level-dificil { background-color: #fd7e14; }
        .level-experto { background-color: #dc3545; }
        .level-default { background-color: #6c757d; }

        .subcategory-display {
            font-size: 1.5em;
            font-weight: bold;
        }

        .btn-random {
            background: #D90077;
            color: white;
            border: none;
            padding: 15px 30px;
            font-size: 1.2em;
            border-radius: 50px;
            cursor: pointer;
            transition: all 0.3s ease;
            margin: 10px 0;
        }

        .btn-random:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(217, 0, 119, 0.4);
        }

        .btn-random:disabled {
            background: #ccc;
            cursor: not-allowed;
            transform: none;
            box-shadow: none;
        }

        .game-over {
            color: #e74c3c;
            font-size: 1.5em;
            font-weight: bold;
            margin: 20px 0;
        }

        .instructions {
            color: #666;
            margin-top: 20px;
            font-size: 0.9em;
        }

        /* Toggle Switch Styles */
        .mode-switch-container {
            display: flex;
            justify-content: center;
            align-items: center;
            margin-top: 20px;
            font-size: 0.9em;
        }
        .switch {
            position: relative;
            display: inline-block;
            width: 50px;
            height: 24px;
            margin: 0 10px;
        }
        .switch input { display: none; }
        .slider {
            position: absolute;
            cursor: pointer;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: #ccc;
            transition: .4s;
            border-radius: 24px;
        }
        .slider:before {
            position: absolute;
            content: "";
            height: 16px;
            width: 16px;
            left: 4px;
            bottom: 4px;
            background-color: white;
            transition: .4s;
            border-radius: 50%;
        }
        input:checked + .slider {
            background-color: #3d0e7aff;
        }
        input:checked + .slider:before {
            transform: translateX(26px);
        }

        .logo-container {
            margin-bottom: 1rem;
        }

        .logo {
            max-width: 75px;
            height: auto;
        }

        .logo-container a {
            text-decoration: none;
        }

    </style>
</head>
<body>
    <div class="container">
        <div class="logo-container">
            <a href="index.html">
                <img src="https://moroarte.com/wp-content/uploads/2023/09/logoTUTTIQUANTI-154x300.png" alt="Tutti Quanti Logo" class="logo">
            </a>
        </div>
        <h1>🎯 PARTY</h1>
        
        <div class="timer" id="timer">02:30</div>
        
        <div class="card" id="card">
            <div class="level-display" id="level-display">NIVEL</div>
            <div class="subcategory-display" id="subcategory-display">Presiona el botón para comenzar</div>
        </div>

        <button class="btn-random" id="randomBtn">
            🎲 Obtener Subcategoría
        </button>

        <div id="gameOverMessage" style="display: none;">
            <div class="game-over">⏰ ¡Se acabó el tiempo!</div>
        </div>

        <div class="mode-switch-container">
            <span>Modo Party</span>
            <label class="switch">
                <input type="checkbox" id="mode-toggle">
                <span class="slider"></span>
            </label>
            <span>Modo Tutti</span>
        </div>

        <div class="instructions">
            Usa el interruptor para elegir el set de categorías. ¡Presiona el botón para un nuevo desafío!
        </div>
    </div>

    <script>
        // --- DOM Elements ---
        const timerElement = document.getElementById('timer');
        const randomBtn = document.getElementById('randomBtn');
        const card = document.getElementById('card');
        const levelDisplay = document.getElementById('level-display');
        const subcategoryDisplay = document.getElementById('subcategory-display');
        const gameOverMessage = document.getElementById('gameOverMessage');
        const modeToggle = document.getElementById('mode-toggle');

        // --- Game State ---
        let timeLeft = 150;
        let timerInterval = null;
        let rouletteInterval = null;

        // --- Functions ---
        function updateTimerDisplay() {
            const minutes = Math.floor(timeLeft / 60);
            const seconds = timeLeft % 60;
            timerElement.textContent = `${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;
        }

        function getLevelClass(levelName) {
            const normalizedLevel = levelName.toLowerCase();
            if (normalizedLevel.includes('fácil')) return 'level-facil';
            if (normalizedLevel.includes('medio')) return 'level-medio';
            if (normalizedLevel.includes('difícil')) return 'level-dificil';
            if (normalizedLevel.includes('experto')) return 'level-experto';
            return 'level-default';
        }

        function startTimer() {
            clearInterval(timerInterval);
            timeLeft = 150;
            updateTimerDisplay();
            timerElement.classList.remove('urgent');
            gameOverMessage.style.display = 'none';
            randomBtn.disabled = false;

            timerInterval = setInterval(() => {
                timeLeft--;
                updateTimerDisplay();

                if (timeLeft <= 30) {
                    timerElement.classList.add('urgent');
                }

                if (timeLeft < 0) {
                    clearInterval(timerInterval);
                    timerElement.textContent = "00:00";
                    randomBtn.disabled = true;
                    gameOverMessage.style.display = 'block';
                }
            }, 1000);
        }

        function runRouletteEffect(callback) {
            const dummyCategories = ['Países de Asia', 'Marcas de Autos', 'Personajes de Disney', 'Verbos en Inglés', 'Capitales del Mundo', 'Animales Marinos', 'Instrumentos Musicales', 'Deportes Olímpicos', 'Héroes de Marvel'];
            let i = 0;
            
            rouletteInterval = setInterval(() => {
                subcategoryDisplay.textContent = dummyCategories[i % dummyCategories.length];
                i++;
            }, 100);

            setTimeout(() => {
                clearInterval(rouletteInterval);
                callback();
            }, 2000); // Run roulette for 2 seconds
        }

        // --- History Management ---
        const RECENT_HISTORY_KEY = 'partyModeHistory';
        const MAX_HISTORY_SIZE = 10;

        function getRecentHistory() {
            const history = sessionStorage.getItem(RECENT_HISTORY_KEY);
            return history ? JSON.parse(history) : [];
        }

        function addToRecentHistory(id) {
            let history = getRecentHistory();
            history.unshift(id); // Add new id to the front
            // Remove duplicates and slice to max size
            history = [...new Set(history)].slice(0, MAX_HISTORY_SIZE);
            sessionStorage.setItem(RECENT_HISTORY_KEY, JSON.stringify(history));
        }

        async function getRandomCategory() {
            randomBtn.disabled = true;
            levelDisplay.innerHTML = '';
            card.classList.remove('flip');

            runRouletteEffect(async () => {
                try {
                    const gameMode = modeToggle.checked ? 'all' : 'party';
                    const recentIds = getRecentHistory();
                    const excludeQuery = recentIds.length > 0 ? `&exclude=${recentIds.join(',')}` : '';

                    const response = await fetch(`party_logic.php?mode=${gameMode}${excludeQuery}`);
                    
                    if (!response.ok) {
                        const errorData = await response.json();
                        throw new Error(errorData.error || `Error del servidor: ${response.status}`);
                    }
                    const data = await response.json();

                    // Add to history if a valid category was returned
                    if (data && data.id) {
                        addToRecentHistory(data.id);
                    }

                    // Update UI with real data
                    const levelClass = getLevelClass(data.nivel || '');
                    levelDisplay.innerHTML = `<span class="level-label ${levelClass}">${data.nivel || 'NIVEL'}</span>`;
                    subcategoryDisplay.textContent = data.subcategoria || 'Error';
                    card.classList.add('flip');
                    
                    // Start the game timer only on the first press
                    if (!timerInterval) {
                        startTimer();
                    }

                } catch (error) {
                    subcategoryDisplay.textContent = 'Error al cargar';
                    levelDisplay.innerHTML = '<span class="level-label level-default">Intenta de nuevo</span>';
                    alert('No se pudo obtener la categoría: ' + error.message);
                } finally {
                    randomBtn.disabled = false;
                    randomBtn.innerHTML = '🎲 Próximo Desafío';
                }
            });
        }

        // --- Event Listeners ---
        randomBtn.addEventListener('click', getRandomCategory);

        // --- Initial State ---
        updateTimerDisplay();

    </script>
</body>