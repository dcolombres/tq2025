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
    let currentMode = null;
    let roundsHistory = [];
    let loadingInterval;
    let currentRound = 0;
    const totalRounds = 6;

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
        gestionButton.href = 'gestion.php';
        gestionButton.target = '_blank';
        gestionButton.className = 'editor-button';
        gestionButton.innerHTML = '<i class="fas fa-cog"></i> Gestión';

        const insertButton = document.createElement('a');
        insertButton.href = 'insert.php';
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

    // --- EVENT LISTENERS SETUP ---
    function setupEventListeners() {
        categoryButtonsContainer.addEventListener('click', e => {
            if (e.target.tagName === 'BUTTON') {
                if (e.target.dataset.mode === 'party') {
                    window.location.href = 'party.php';
                } else {
                    selectedMainCategoryId = e.target.dataset.id;
                    selectedMainCategoryName = e.target.dataset.name;
                    startGame(selectedMainCategoryName);
                }
            }
        });

    document.getElementById('change-mode-button').addEventListener('click', () => {
        window.location.reload();
    });

        document.getElementById('new-round-button').addEventListener('click', () => {
            if (currentMode) {
                startGame(currentMode);
            }
        });

        finishButton.addEventListener('click', () => endGame(true));

        document.body.addEventListener('click', e => {
            if (e.target === resultsModal) resultsModal.classList.remove('show');
            if (e.target === instructionsModal) instructionsModal.classList.remove('show');
            if (e.target.matches('.close-modal')) e.target.closest('.modal').classList.remove('show');
            if (e.target.closest('.instructions-trigger')) instructionsModal.classList.add('show');
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
    function resetGame() {
        if (timer) clearInterval(timer);
        isGameActive = false;
        totalPoints = 0;
        if (document.querySelector('.timer')) document.querySelector('.timer').textContent = '02:40';
        
        const inputs = document.querySelectorAll('.letter-input');
        inputs.forEach(input => {
            input.value = '';
            input.disabled = false;
            input.className = 'letter-input';
        });

        const finishButton = document.getElementById('finishButton');
        if (finishButton) {
            finishButton.disabled = false;
        }
        document.getElementById('resultsContainer').style.display = 'none';
    }

    function startGame(mode) {
        currentMode = mode;
        document.getElementById('category-selection-container').style.display = 'none';
        document.querySelector('.game-header').style.display = 'flex';
        currentRound++;
        const roundCounter = document.querySelector('.round-counter');
        roundCounter.textContent = `Ronda ${currentRound} de ${totalRounds}`;
        roundCounter.style.display = 'block';
        document.querySelector('.alphabet-grid').style.display = 'grid';
        document.getElementById('finishButton').style.display = 'block';

        fetch(`index.php?action=get_subcategory&mode=${mode}`)
            .then(response => response.json())
            .then(data => {
                if (data.error) {
                    showCustomAlert(data.error, 'Error al Cargar');
                    document.getElementById('category-selection-container').style.display = 'block';
                    document.querySelector('.game-header').style.display = 'none';
                    document.querySelector('.alphabet-grid').style.display = 'none';
                    document.getElementById('finishButton').style.display = 'none';
                    return;
                }
                selectedCategory = data;
                const labelMode = document.querySelector('.label-mode');
                if (labelMode) labelMode.textContent = mode;

                const labelLevel = document.querySelector('.label-level');
                if (labelLevel) labelLevel.textContent = selectedCategory.nivel;

                const categoryText = document.querySelector('.category-text');
                if (categoryText) categoryText.textContent = selectedCategory.nombre_categoria;

                currentSubcategoryId = data.id_subcategoria;
                currentCategoryAllowsExternalValidation = !!data.permite_api;
                
                resetGame();
                startTimer(160);
            })
            .catch(error => {
                console.error('Error al obtener la categoría:', error);
                showCustomAlert('No se pudo cargar una categoría. Inténtalo de nuevo.', 'Error de Red');
            });
    }

    function softResetGame() {
        resultsModal.classList.remove('show');
        startGame(currentMode);
    }

    function hardResetGame() {
        resultsModal.classList.remove('show');
        document.getElementById('resultsContainer').style.display = 'none';
        categorySelectionContainer.style.display = 'block';
        gameHeader.style.display = 'none';
        alphabetGrid.style.display = 'none';
        finishButton.style.display = 'none';
        document.querySelector('.round-counter').style.display = 'none';
        currentRound = 0;
        isGameActive = false;
        if(timer) clearInterval(timer);
        selectedMainCategoryId = null;
        currentSubcategoryId = null;
        if (document.querySelector('.timer')) document.querySelector('.timer').textContent = '02:40';
        if (document.querySelector('.label-mode')) document.querySelector('.label-mode').textContent = '';
        if (document.querySelector('.label-level')) document.querySelector('.label-level').textContent = '';
        if (document.querySelector('.category-text')) document.querySelector('.category-text').textContent = 'Elige modo...';
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
        if (earlyFinish) {
            const inputs = document.querySelectorAll('.letter-input');
            const filledInputs = Array.from(inputs).filter(input => input.value.trim() !== '').length;
            const timeConditionMet = timeLeft <= 100;
            const answersConditionMet = filledInputs >= 15;

            if (!timeConditionMet && !answersConditionMet) {
                showCustomAlert('Debés esperar 1 minuto o rellenar un mínimo de 15 palabras para activar el TUTTI QUANTI.

Recordá que las respuestas incorrectas y vacías restan puntos.');
                return;
            }
        }

        clearInterval(timer);
        isGameActive = false;
        document.querySelectorAll('.letter-input').forEach(input => input.disabled = true);
        const loadingModal = document.getElementById('loading-modal');
        const loadingMessage = document.getElementById('loading-message');
        const messages = ["Verificando respuestas...", "Validando con fuentes externas...", "Calculando puntuación..."];
        let messageIndex = 0;

        loadingModal.classList.add('show');
        loadingMessage.textContent = messages[messageIndex];

        loadingInterval = setInterval(() => {
            messageIndex = (messageIndex + 1) % messages.length;
            loadingMessage.textContent = messages[messageIndex];
        }, 1500);

        calculateScore(earlyFinish)
            .catch(error => {
                console.error("Error calculating score:", error);
                showCustomAlert("Hubo un error al calcular los resultados. Por favor, intentá de nuevo.", "Error de Cálculo");
            })
            .finally(() => {
                clearInterval(loadingInterval);
                loadingModal.classList.remove('show');
            });
    }

    async function calculateScore(earlyFinish = false) {
        if (currentSubcategoryId === null) {
            console.error("Critical error: currentSubcategoryId is null in calculateScore.");
            return;
        }

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
                case 'CORRECTO': points = 15; validWords++; break;
                case 'MAL_ESCRITO': points = 7; validWords++; break;
                case 'VALIDADO_EXTERNAMENTE': points = 5; validWords++; break;
                case 'INCORRECTO': points = -3; break;
                case 'VACIO': points = -3; break;
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
            
            const pointsText = `${result.points > 0 ? '+' : ''}${result.points} pts`;
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
            <button id="play-again-button" class="finish-button" style="margin: 20px auto 0 auto;"><i class="fas fa-redo"></i> Próxima Ronda</button>
        `;
        resultsModal.classList.add('show');

        if (currentRound >= totalRounds) {
            const playAgainButton = document.getElementById('play-again-button');
            if (playAgainButton) {
                playAgainButton.style.display = 'none';
            }
            const modalTitle = modalResults.querySelector('.results-title');
            if (modalTitle) {
                modalTitle.textContent = '¡Juego Completo!';
            }
        }

        const roundData = {
            mode: document.querySelector('.label-mode').textContent,
            level: selectedCategory.nivel,
            subcategory: selectedCategory.nombre_categoria,
            points: totalPoints
        };
        roundsHistory.push(roundData);
        renderHistoryLog();
    }

    function renderHistoryLog() {
        const historyContainer = document.getElementById('history-log-container');
        if (!roundsHistory.length) return;

        historyContainer.style.display = 'block';

        let grandTotal = 0;
        let tableRows = '';

        roundsHistory.forEach(round => {
            grandTotal += round.points;
            tableRows += `
                <tr>
                    <td>${round.mode}</td>
                    <td>${round.level}</td>
                    <td>${round.subcategory}</td>
                    <td><strong>${round.points}</strong></td>
                </tr>
            `;
        });

        historyContainer.innerHTML = `
            <h2>Historial de Puntuaciones</h2>
            <table class="history-table">
                <thead>
                    <tr>
                        <th>Modo</th>
                        <th>Nivel</th>
                        <th>Subcategoría</th>
                        <th>Puntos</th>
                    </tr>
                </thead>
                <tbody>
                    ${tableRows}
                </tbody>
            </table>
            <div class="history-total">
                Puntuación Total: ${grandTotal}
            </div>
        `;
    }

    function showCustomAlert(message, title = 'Aviso') {
        const alertModal = document.getElementById('alert-modal');
        const alertTitle = document.getElementById('alert-modal-title');
        const alertText = document.getElementById('alert-modal-text');

        alertTitle.textContent = title;
        alertText.innerHTML = message.replace(/\n/g, '<br>');

        alertModal.classList.add('show');
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

            button.textContent = '¡Añadida!';
            button.disabled = true;
            const wordItem = button.closest('.word-item');
            wordItem.classList.remove('points-negative');
            wordItem.classList.add('points-10');
            
            totalPoints += 13;
            document.querySelector('#modalResults p strong').textContent = totalPoints;

            const small = wordItem.querySelector('small');
            small.innerHTML = `Palabra añadida a la base de datos.<br>Fuente: Base de Datos | +10 pts`;

        } catch (error) {
            showCustomAlert('Error al añadir la palabra: ' + error.message, 'Error');
        }
    }

    initializeApp();
});