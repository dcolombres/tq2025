let currentCategoryId = null;

let timer;
let timeLeft = 160;
let isGameActive = false;

function initializeGame() {
  return true;
}

async function validateWord(word, category, letter, subcategoryId) {
  if (!word) {
    return { status: 'VACIO', explanation: 'Sin respuesta', source: 'Sistema' };
  }

  if (!word.toLowerCase().startsWith(letter.toLowerCase())) {
    return { status: 'INCORRECTO', source: 'Sistema', explanation: 'La palabra no comienza con la letra correcta' };
  }

  try {
    const response = await fetch(`game_logic.php?action=validate_word`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json'
      },
      body: JSON.stringify({
        word: word,
        letter: letter,
        subcategory_id: subcategoryId
      })
    });
    
    if (!response.ok) {
        throw new Error(`Server error: ${response.statusText}`);
    }

    const validation = await response.json();
    validation.source = 'Base de Datos'; // Add source for consistency
    return validation;

  } catch (error) {
    console.error('Validation error:', error);
    return { status: 'ERROR', source: 'Sistema', explanation: 'Error de conexión al validar' };
  }
}

const alphabet = 'ABCDEFGHIJKLMNÑOPQRSTUVWXYZ'.split('');
const alphabetGrid = document.querySelector('.alphabet-grid');
alphabet.forEach(letter => {
  const container = document.createElement('div');
  container.className = 'letter-container';
  const letterDiv = document.createElement('div');
  letterDiv.className = 'letter';
  letterDiv.textContent = letter;
  const input = document.createElement('input');
  input.type = 'text';
  input.className = 'letter-input';
  input.placeholder = `Palabra con ${letter}...`;
  input.disabled = true;
  container.appendChild(letterDiv);
  container.appendChild(input);
  alphabetGrid.appendChild(container);
});

document.querySelectorAll('.letter-input').forEach((input, index, inputs) => {
  input.addEventListener('keydown', e => {
    if (e.key === 'Enter') {
      e.preventDefault();
      const nextInputs = Array.from(inputs).slice(index + 1);
      const nextEnabledInput = nextInputs.find(input => !input.disabled);
      if (nextEnabledInput) {
        nextEnabledInput.focus();
      } else {
        const firstEnabledInput = Array.from(inputs).find(input => !input.disabled);
        if (firstEnabledInput) {
          firstEnabledInput.focus();
        }
      }
    }
  });
});

document.getElementById('spinButton').addEventListener('click', async () => {
  if (!initializeGame()) return;

  const categoryDisplay = document.querySelector('.category-display');
  
  try {
    const response = await fetch('game_logic.php?action=get_random_category');
    if (!response.ok) {
        throw new Error(`Server error: ${response.statusText}`);
    }
    const category = await response.json();

    if (category.error) {
        alert('Error al cargar la categoría: ' + category.error);
        return;
    }

    categoryDisplay.textContent = category.nombre;
    currentCategoryId = category.id; // Store the ID for validation

    if (timer) {
      clearInterval(timer);
    }
    isGameActive = true;
    timeLeft = 160;
    updateTimerDisplay();
    document.querySelectorAll('.letter-input').forEach(input => {
      input.disabled = false;
      input.value = '';
    });
    timer = setInterval(() => {
      timeLeft--;
      updateTimerDisplay();
      if (timeLeft <= 0) {
        endGame();
      }
    }, 1000);
  } catch (error) {
      console.error('Failed to fetch category:', error);
      alert('No se pudo conectar con el servidor para obtener una categoría.');
  }
});

document.getElementById('finishButton').addEventListener('click', () => {
  if (!isGameActive) return;
  if (isGameActive) {
    const timeBonus = timeLeft * 2;
    endGame(true);
  }
});

function updateTimerDisplay() {
  const minutes = Math.floor(timeLeft / 60);
  const seconds = timeLeft % 60;
  document.querySelector('.timer').textContent = `${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;
}

function endGame(earlyFinish = false) {
  clearInterval(timer);
  isGameActive = false;
  document.querySelectorAll('.letter-input').forEach(input => {
    input.disabled = true;
  });
  calculateScore(earlyFinish).catch(error => {
    console.error("Error calculating score:", error);
    alert("Hubo un error al calcular los resultados. Por favor, intenta de nuevo.");
  });
}

async function calculateScore(earlyFinish = false) {
  if (currentCategoryId === null) {
      alert("Por favor, presiona 'RANDOM' para obtener una categoría antes de terminar.");
      return;
  }
    
  try {
    const inputs = document.querySelectorAll('.letter-input');
    const resultsContainer = document.getElementById('resultsContainer');
    const wordList = document.getElementById('wordList');
    const categoryDisplay = document.querySelector('.category-display');
    if (!resultsContainer || !wordList || !categoryDisplay || !inputs) {
      console.error("Required DOM elements not found");
      return;
    }
    let totalPoints = 0;
    let validWords = 0;
    const categoryName = categoryDisplay.textContent;
    wordList.innerHTML = '';

    const results = await Promise.all(Array.from(inputs).map(async input => {
      try {
        const word = input.value.trim();
        const letter = input.previousElementSibling.textContent.toLowerCase();
        if (word === '') {
          return {
            word,
            letter,
            status: 'VACIO',
            source: 'N/A',
            explanation: 'Sin respuesta',
            points: 0
          };
        }
        
        const validation = await validateWord(word, categoryName, letter, currentCategoryId);
        let points = 0;
        switch (validation.status) {
          case 'CORRECTO':
            points = 10;
            validWords++;
            break;
          case 'INCORRECTO':
            points = -3;
            break;
        }
        totalPoints += points;
        return {
          word,
          letter,
          status: validation.status,
          source: validation.source,
          explanation: validation.explanation,
          points
        };
      } catch (error) {
        console.error('Error validating word:', error);
        return {
          word: input.value.trim(),
          letter: input.previousElementSibling.textContent.toLowerCase(),
          status: 'ERROR',
          source: 'Sistema',
          explanation: 'Error en la validación',
          points: 0
        };
      }
    }));

    results.forEach(result => {
      const wordItem = document.createElement('div');
      let pointsClass = '';
      if (result.points === 10) pointsClass = 'points-10';
      else if (result.points === 5) pointsClass = 'points-5';
      else if (result.points === 0) pointsClass = 'points-zero';
      else if (result.points < 0) pointsClass = 'points-negative';
      wordItem.className = `word-item ${pointsClass}`;
      const pointsText = result.status === 'VACIO' ? '0 pts' : `${result.points > 0 ? '+' : ''}${result.points} pts`;
      wordItem.innerHTML = `
        ${result.letter.toUpperCase()}: ${result.word || '(vacío)'}<br>
        <small>
          ${result.status !== 'VACIO' ? result.explanation : 'Sin respuesta'}<br>
          Fuente: ${result.source} | ${pointsText}
        </small>
      `;
      wordList.appendChild(wordItem);
      const input = document.querySelector(`.letter-input[placeholder="Palabra con ${result.letter.toUpperCase()}..."]`);
      input.style.backgroundColor = result.status === 'CORRECTO' ? 'rgba(137, 207, 240, 0.3)' : result.status === 'MAL_ESCRITO' ? 'rgba(255, 255, 0, 0.2)' : result.status === 'INCORRECTO' ? 'rgba(255, 0, 255, 0.1)' : 'white';
    });

    if (earlyFinish) {
      const timeBonus = timeLeft * 2;
      const bonusItem = document.createElement('div');
      bonusItem.className = 'word-item word-correct';
      bonusItem.innerHTML = `
        <strong>¡BONUS POR TUTTI QUANTI!</strong><br>
        <small>
          Tiempo restante: ${timeLeft} segundos<br>
          Bonus: +${timeBonus} puntos
        </small>
      `;
      wordList.appendChild(bonusItem);
      totalPoints += timeBonus;
    }

    document.querySelector('.score').textContent = `Palabras correctas: ${validWords} | Puntos totales: ${totalPoints}`;
    resultsContainer.style.display = 'block';
    showResults({
      totalPoints,
      validWords,
      results,
      category: categoryName
    });
    alert(`¡Juego terminado!\nPalabras correctas: ${validWords}\nPuntos totales: ${totalPoints}`);
  } catch (error) {
    console.error('Error in score calculation:', error);
    alert('Hubo un error al calcular los resultados. Por favor, intenta de nuevo.');
  }
}

function showResults(gameResults) {
  const modalResults = document.getElementById('modalResults');
  const modal = document.getElementById('resultsModal');
  if (!modalResults || !modal) {
    console.error("Modal elements not found");
    return;
  }
  modalResults.innerHTML = `
    <h2 class="results-title">Resultados</h2>
    <div class="results-summary">
      <p>Categoría: ${gameResults.category || 'N/A'}</p>
      <p>Palabras correctas: ${gameResults.validWords || 0}</p>
      <p>Puntuación: ${gameResults.totalPoints || 0}</p>
    </div>
    <div class="word-list">
      ${(gameResults.results || []).map(result => {
    let pointsClass = '';
    if (result.points === 10) pointsClass = 'points-10';
    else if (result.points === 5) pointsClass = 'points-5';
    else if (result.points === 0) pointsClass = 'points-zero';
    else if (result.points < 0) pointsClass = 'points-negative';
    return `
          <div class="word-item ${pointsClass}">
            ${result.letter?.toUpperCase() || ''}: ${result.word || '(vacío)'}<br>
            <small>
              ${result.status !== 'VACIO' ? result.explanation : 'Sin respuesta'}<br>
              Estado: ${result.status || 'N/A'}<br>
              Fuente: ${result.source || 'N/A'}<br>
              Puntos: ${result.points || 0}
            </small>
          </div>
        `;
  }).join('')}
    </div>
  `;
  modal.classList.add('show');
}

document.querySelector('.close-modal').addEventListener('click', () => {
  document.getElementById('resultsModal').classList.remove('show');
});

window.addEventListener('click', e => {
  const modal = document.getElementById('resultsModal');
  if (e.target === modal) {
    modal.classList.remove('show');
  }
});
