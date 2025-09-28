document.addEventListener('DOMContentLoaded', () => {
    // --- GLOBAL STATE ---
    let selectedMainCategoryId = null;
    let selectedMainCategoryName = null;
    let currentSubcategoryId = null;
    let currentCategoryAllowsExternalValidation = false;
    let timer;
    let timeLeft = 160;
    let isGameActive = false;
    let isEditorMode = false;
    let totalPoints = 0;

    // --- DOM ELEMENTS ---
    const categorySelectionContainer = document.getElementById('category-selection-container');
    const categoryButtonsContainer = document.getElementById('category-buttons');
    const gameHeader = document.querySelector('.game-header');
    const alphabetGrid = document.querySelector('.alphabet-grid');
    const finishButton = document.getElementById('finishButton');
    const categoryDisplay = document.querySelector('.category-display');
    const resultsModal = document.getElementById('resultsModal');
    const instructionsModal = document.getElementById('instructions-modal');

    // --- INITIALIZATION ---
    function initializeApp() {
        const urlParams = new URLSearchParams(window.location.search);
        isEditorMode = urlParams.get('editor') === 'true';

        hardResetGame();
        generateAlphabetGrid();
        setupEventListeners();
        if (isEditorMode) addEditorButtons();
    }

    function addEditorButtons() {
        const controls = document.querySelector('.header-controls');
        const gestionButton = document.createElement('a');
        gestionButton.href = 'gestion.html';
        gestionButton.target = '_blank';
        gestionButton.className = 'editor-button';
        gestionButton.innerHTML = '<i class="fas fa-cog"></i> Gestión';

        const insertButton = document.createElement('a');
        insertButton.href = 'TQinsert.html';
        insertButton.target = '_blank';
        insertButton.className = 'editor-button';
        insertButton.innerHTML = '<i class="fas fa-plus-square"></i> Insertar';
        
        controls.appendChild(gestionButton);
        controls.appendChild(insertButton);
    }

    function generateAlphabetGrid() {
        const alphabet = 'ABCDEFGHIJKLMNÑOPQRSTUVWXYZ'.split('');
        alphabetGrid.innerHTML = '';
        alphabet.forEach(letter => {
            const container = document.createElement('div');
            container.className = 'letter-container';
            container.innerHTML = `<div class="letter">${letter}</div><input type="text" class="letter-input" placeholder="Palabra con ${letter}..." disabled>`;
            alphabetGrid.appendChild(container);
        });
    }

    function loadMainCategories() {
        fetch('get_items.php?type=categoria')
            .then(res => res.json())
            .then(categories => {
                categoryButtonsContainer.innerHTML = '';
                categories.forEach(category => {
                    const button = document.createElement('button');
                    button.textContent = category.nombre;
                    button.dataset.id = category.id;
                    button.dataset.name = category.nombre;
                    if (category.nombre.toUpperCase() === 'PARTY') {
                        button.dataset.mode = 'party';
                    }
                    categoryButtonsContainer.appendChild(button);
                });
            })
            .catch(err => console.error("Failed to load main categories:", err));
    }

    // --- EVENT LISTENERS SETUP ---
    function setupEventListeners() {
        categoryButtonsContainer.addEventListener('click', e => {
            if (e.target.tagName === 'BUTTON') {
                if (e.target.dataset.mode === 'party') {
                    window.location.href = 'party.php';
                } else {
                    selectedMainCategoryId = e.target.dataset.id;
                    selectedMainCategoryName = e.target.dataset.name;
                    startGame();
                }
            }
        });

        finishButton.addEventListener('click', () => {
            if (isGameActive) endGame(true);
        });

        document.body.addEventListener('click', e => {
            if (e.target === resultsModal) resultsModal.classList.remove('show');
            if (e.target === instructionsModal) instructionsModal.classList.remove('show');
            if (e.target.matches('.close-modal')) e.target.closest('.modal').classList.remove('show');
            if (e.target.id === 'instructions-button') instructionsModal.classList.add('show');
            if (e.target.id === 'change-mode-button') hardResetGame();
            if (e.target.id === 'play-again-button') softResetGame();
            if (e.target.matches('.btn-accept-word')) acceptAndAddWord(e.target);
        });

        alphabetGrid.addEventListener('keydown', e => {
            if (e.key === 'Enter' && e.target.classList.contains('letter-input')) {
                e.preventDefault();
                const inputs = Array.from(document.querySelectorAll('.letter-input:not([disabled])'));
                const currentIndex = inputs.indexOf(e.target);
                const nextInput = inputs[currentIndex + 1] || inputs[0];
                if (nextInput) nextInput.focus();
            }
        });
    }

    // --- GAME FLOW & STATE MANAGEMENT ---
    async function startGame() {
        categorySelectionContainer.style.display = 'none';
        gameHeader.style.display = 'flex';
        alphabetGrid.style.display = 'grid';
        finishButton.style.display = 'block';
        document.getElementById('resultsContainer').style.display = 'none';
        document.getElementById('wordList').innerHTML = '';

        try {
            const response = await fetch(`game_logic.php?action=get_random_category&categoria_id=${selectedMainCategoryId}`);
            if (!response.ok) throw new Error(`Server error: ${response.statusText}`);
            const category = await response.json();
            if (category.error) {
                alert('Error al cargar la categoría: ' + category.error);
                hardResetGame();
                return;
            }

            categoryDisplay.innerHTML = `${selectedMainCategoryName} / ${category.nivel_nombre} / ${category.nombre}`;
            currentSubcategoryId = category.id;
            currentCategoryAllowsExternalValidation = !!category.permite_validacion_externa;

            startTimer();
            document.querySelectorAll('.letter-input').forEach(input => { input.disabled = false; input.value = ''; });
            document.querySelectorAll('.letter-input')[0].focus();
        } catch (error) {
            console.error('Failed to fetch category:', error);
            alert('No se pudo conectar con el servidor para obtener una categoría.');
            hardResetGame();
        }
    }

    function softResetGame() {
        resultsModal.classList.remove('show');
        startGame();
    }

    function hardResetGame() {
        resultsModal.classList.remove('show');
        document.getElementById('resultsContainer').style.display = 'none';
        categorySelectionContainer.style.display = 'block';
        gameHeader.style.display = 'none';
        alphabetGrid.style.display = 'none';
        finishButton.style.display = 'none';
        isGameActive = false;
        if(timer) clearInterval(timer);
        selectedMainCategoryId = null;
        currentSubcategoryId = null;
        document.querySelector('.score').textContent = 'Puntos: 0';
        document.querySelector('.timer').textContent = '02:40';
        categoryDisplay.innerHTML = 'CATEGORÍA';
        loadMainCategories();
    }

    function startTimer() {
        if (timer) clearInterval(timer);
        isGameActive = true;
        timeLeft = 160;
        updateTimerDisplay();
        timer = setInterval(() => {
            timeLeft--;
            updateTimerDisplay();
            if (timeLeft <= 0) endGame();
        }, 1000);
    }

    function updateTimerDisplay() {
        const minutes = Math.floor(timeLeft / 60);
        const seconds = timeLeft % 60;
        document.querySelector('.timer').textContent = `${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;
    }

    function endGame(earlyFinish = false) {
        clearInterval(timer);
        isGameActive = false;
        document.querySelectorAll('.letter-input').forEach(input => input.disabled = true);
        calculateScore(earlyFinish).catch(error => {
            console.error("Error calculating score:", error);
            alert("Hubo un error al calcular los resultados. Por favor, intenta de nuevo.");
        });
    }

    async function calculateScore(earlyFinish = false) {
        if (currentSubcategoryId === null) return;
        const inputs = document.querySelectorAll('.letter-input');
        totalPoints = 0;
        let validWords = 0;

        const validationPromises = Array.from(inputs).map(input => {
            const word = input.value.trim();
            const letter = input.previousElementSibling.textContent.toLowerCase();
            return validateWord(word, letter);
        });

        const resultsData = await Promise.all(validationPromises);

        resultsData.forEach((validation, index) => {
            let points = 0;
            switch (validation.status) {
                case 'CORRECTO': points = 10; validWords++; break;
                case 'MAL_ESCRITO': points = 7; validWords++; break;
                case 'VALIDADO_EXTERNAMENTE': points = 5; validWords++; break;
                case 'INCORRECTO': points = -3; break;
            }
            totalPoints += points;
            validation.word = inputs[index].value.trim();
            validation.letter = inputs[index].previousElementSibling.textContent;
            validation.points = points;
        });

        if (earlyFinish) {
            const timeBonus = timeLeft * 2;
            totalPoints += timeBonus;
            resultsData.push({ status: 'BONUS', explanation: `Tiempo restante: ${timeLeft}s`, points: timeBonus });
        }

        document.querySelector('.score').textContent = `Puntos: ${totalPoints}`;
        showResultsModal(resultsData);
    }

    async function validateWord(word, letter) {
        if (!word) return { status: 'VACIO', explanation: 'Sin respuesta', source: 'Sistema' };
        if (!word.toLowerCase().startsWith(letter.toLowerCase())) return { status: 'INCORRECTO', explanation: 'No comienza con la letra correcta', source: 'Sistema' };
        try {
            const response = await fetch(`game_logic.php?action=validate_word`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ word, letter, subcategory_id: currentSubcategoryId, allows_external_validation: currentCategoryAllowsExternalValidation })
            });
            if (!response.ok) throw new Error(`Server error: ${response.statusText}`);
            return await response.json();
        } catch (error) {
            console.error('Validation error:', error);
            return { status: 'ERROR', explanation: 'Error de conexión al validar', source: 'Sistema' };
        }
    }

    function showResultsModal(results) {
        let resultsHTML = '';
        results.forEach(result => {
            if (result.status === 'BONUS') {
                resultsHTML += `<div class="word-item points-10"><strong>¡BONUS POR TUTTI QUANTI!</strong><br><small>${result.explanation} | Bonus: +${result.points} pts</small></div>`;
                return;
            }

            let pointsClass = 'points-zero';
            if (result.points > 7) pointsClass = 'points-10';
            else if (result.points > 5) pointsClass = 'points-7';
            else if (result.points > 0) pointsClass = 'points-5';
            else if (result.points < 0) pointsClass = 'points-negative';
            
            const pointsText = result.status === 'VACIO' ? '0 pts' : `${result.points > 0 ? '+' : ''}${result.points} pts`;
            let editorButton = '';
            if (isEditorMode && result.status === 'INCORRECTO' && result.word) {
                editorButton = `<button class="btn-accept-word" data-word="${result.word}" data-subcatid="${currentSubcategoryId}">Aceptar y Añadir</button>`;
            }

            resultsHTML += `
                <div class="word-item ${pointsClass}" id="result-word-${result.letter}">
                    ${result.letter.toUpperCase()}: ${result.word || '(vacío)'}<br>
                    <small>${result.explanation || 'Sin respuesta'}<br>Fuente: ${result.source || 'N/A'} | ${pointsText}</small>
                    ${editorButton}
                </div>
            `;
        });

        const modalResults = document.getElementById('modalResults');
        modalResults.innerHTML = `
            <h2 class="results-title">Juego Terminado</h2>
            <p style="text-align: center; color: var(--accent-color); margin-top: -15px; margin-bottom: 20px;">${categoryDisplay.innerHTML}</p>
            <div class="word-list">${resultsHTML}</div>
            <hr style="margin: 20px 0; border: 1px solid var(--secondary-color);">
            <p style="text-align: center; font-size: 1.5rem; font-weight: bold; color: var(--primary-color);">Puntuación Final: <strong>${totalPoints}</strong></p>
            <button id="play-again-button" class="finish-button" style="margin: 20px auto 0 auto;"><i class="fas fa-redo"></i> Jugar de Nuevo</button>
        `;
        resultsModal.classList.add('show');
    }

    async function acceptAndAddWord(button) {
        const word = button.dataset.word;
        const subcatId = button.dataset.subcatid;

        try {
            const response = await fetch('add_word.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ palabra: word, subcategoria_id: subcatId })
            });
            const data = await response.json();
            if (!data.success) throw new Error(data.message || 'Failed to add word.');

            // Update UI
            button.textContent = '¡Añadida!';
            button.disabled = true;
            const wordItem = button.closest('.word-item');
            wordItem.classList.remove('points-negative');
            wordItem.classList.add('points-10');
            
            // Recalculate score: remove -3, add +10 -> net change is +13
            totalPoints += 13;
            document.querySelector('.score').textContent = `Puntos: ${totalPoints}`;
            document.querySelector('#modalResults p strong').textContent = totalPoints;

            const small = wordItem.querySelector('small');
            small.innerHTML = `Palabra añadida a la base de datos.<br>Fuente: Base de Datos | +10 pts`;

        } catch (error) {
            alert('Error al añadir la palabra: ' + error.message);
        }
    }

    // --- START THE APP ---
    initializeApp();
});
