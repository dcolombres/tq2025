document.addEventListener('DOMContentLoaded', () => {
    // --- GLOBAL STATE ---
    let selectedMainCategoryId = null;
    let selectedMainCategoryName = null;
    let currentSubcategoryId = null;
    let currentCategoryAllowsExternalValidation = false;
    let timer;
    let timeLeft = 160;
    let isGameActive = false;

    // --- DOM ELEMENTS ---
    const categorySelectionContainer = document.getElementById('category-selection-container');
    const categoryButtonsContainer = document.getElementById('category-buttons');
    const gameHeader = document.querySelector('.game-header');
    const alphabetGrid = document.querySelector('.alphabet-grid');
    const finishButton = document.getElementById('finishButton');
    const categoryDisplay = document.querySelector('.category-display');
    const resultsContainer = document.getElementById('resultsContainer');
    const resultsModal = document.getElementById('resultsModal');
    const instructionsModal = document.getElementById('instructions-modal');

    // --- INITIALIZATION ---
    function initializeApp() {
        hardResetGame(); // Start with a hard reset
        generateAlphabetGrid();
        setupEventListeners();
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
                    categoryButtonsContainer.appendChild(button);
                });
            })
            .catch(err => console.error("Failed to load main categories:", err));
    }

    // --- EVENT LISTENERS SETUP ---
    function setupEventListeners() {
        // Listener for main category selection
        categoryButtonsContainer.addEventListener('click', e => {
            if (e.target.tagName === 'BUTTON') {
                selectedMainCategoryId = e.target.dataset.id;
                selectedMainCategoryName = e.target.dataset.name;
                startGame();
            }
        });

        // Listener for finishing the game
        finishButton.addEventListener('click', () => {
            if (isGameActive) endGame(true);
        });

        // Listener for modals and other buttons
        document.body.addEventListener('click', e => {
            // Close modals
            if (e.target === resultsModal) resultsModal.classList.remove('show');
            if (e.target === instructionsModal) instructionsModal.classList.remove('show');
            if (e.target.matches('.close-modal')) {
                e.target.closest('.modal').classList.remove('show');
            }

            // Instructions button
            if (e.target.id === 'instructions-button') instructionsModal.classList.add('show');

            // Change Mode button
            if (e.target.id === 'change-mode-button') hardResetGame();

            // Play Again button (inside results modal)
            if (e.target.id === 'play-again-button') softResetGame();
        });

        // Listener for Enter key on inputs
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
        resultsContainer.style.display = 'none';
        document.getElementById('wordList').innerHTML = '';

        try {
            const response = await fetch(`game_logic.php?action=get_random_category&categoria_id=${selectedMainCategoryId}`);
            if (!response.ok) throw new Error(`Server error: ${response.statusText}`);
            
            const category = await response.json();
            if (category.error) {
                alert('Error al cargar la categoría: ' + category.error);
                hardResetGame(); // Go back to selection on error
                return;
            }

            categoryDisplay.innerHTML = `${selectedMainCategoryName} / ${category.nivel_nombre} / ${category.nombre}`;
            currentSubcategoryId = category.id;
            currentCategoryAllowsExternalValidation = !!category.permite_validacion_externa;

            startTimer();
            document.querySelectorAll('.letter-input').forEach(input => {
                input.disabled = false;
                input.value = '';
            });
            document.querySelectorAll('.letter-input')[0].focus();

        } catch (error) {
            console.error('Failed to fetch category:', error);
            alert('No se pudo conectar con el servidor para obtener una categoría.');
            hardResetGame();
        }
    }

    function softResetGame() {
        resultsModal.classList.remove('show');
        startGame(); // Re-roll subcategory within the same main category
    }

    function hardResetGame() {
        resultsModal.classList.remove('show');
        resultsContainer.style.display = 'none';
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
        let totalPoints = 0;
        let validWords = 0;

        const validationPromises = Array.from(inputs).map(input => {
            const word = input.value.trim();
            const letter = input.previousElementSibling.textContent.toLowerCase();
            return validateWord(word, categoryDisplay.textContent, letter, currentSubcategoryId, currentCategoryAllowsExternalValidation);
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
            // Attach word and letter info for rendering
            validation.word = inputs[index].value.trim();
            validation.letter = inputs[index].previousElementSibling.textContent;
            validation.points = points;
        });

        if (earlyFinish) {
            const timeBonus = timeLeft * 2;
            totalPoints += timeBonus;
            // Add a pseudo-result for the bonus
            resultsData.push({ status: 'BONUS', explanation: `Tiempo restante: ${timeLeft}s`, points: timeBonus });
        }

        document.querySelector('.score').textContent = `Puntos: ${totalPoints}`;
        showResultsModal(totalPoints, resultsData, categoryDisplay.innerHTML);
    }

    async function validateWord(word, category, letter, subcategoryId, allows_external_validation) {
        if (!word) return { status: 'VACIO', explanation: 'Sin respuesta', source: 'Sistema' };
        if (!word.toLowerCase().startsWith(letter.toLowerCase())) return { status: 'INCORRECTO', explanation: 'No comienza con la letra correcta', source: 'Sistema' };

        try {
            const response = await fetch(`game_logic.php?action=validate_word`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ word, letter, subcategory_id: subcategoryId, allows_external_validation })
            });
            if (!response.ok) throw new Error(`Server error: ${response.statusText}`);
            return await response.json();
        } catch (error) {
            console.error('Validation error:', error);
            return { status: 'ERROR', explanation: 'Error de conexión al validar', source: 'Sistema' };
        }
    }

    function showResultsModal(totalPoints, results, categoryName) {
        const modalResults = document.getElementById('modalResults');
        resultsModal.classList.add('show');

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

            resultsHTML += `
                <div class="word-item ${pointsClass}">
                    ${result.letter.toUpperCase()}: ${result.word || '(vacío)'}<br>
                    <small>${result.explanation || 'Sin respuesta'}<br>Fuente: ${result.source || 'N/A'} | ${pointsText}</small>
                </div>
            `;
        });

        modalResults.innerHTML = `
            <h2 class="results-title">Juego Terminado</h2>
            <p style="text-align: center; color: var(--accent-color); margin-top: -15px; margin-bottom: 20px;">${categoryName || ''}</p>
            <div class="word-list">${resultsHTML}</div>
            <hr style="margin: 20px 0; border: 1px solid var(--secondary-color);">
            <p style="text-align: center; font-size: 1.5rem; font-weight: bold; color: var(--primary-color);">Puntuación Final: <strong>${totalPoints}</strong></p>
            <button id="play-again-button" class="finish-button" style="margin: 20px auto 0 auto;"><i class="fas fa-redo"></i> Jugar de Nuevo</button>
        `;
    }

    // --- START THE APP ---
    initializeApp();
});
