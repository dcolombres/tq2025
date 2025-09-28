# Guía del Modo Editor

Esta guía explica cómo utilizar el "Modo Editor" para añadir palabras nuevas a la base de datos del juego Tutti Quanti.

## 1. Activación del Modo Editor

Para activar el modo editor, debes agregar `?editor=true` al final de la URL en la página principal del juego.

**Ejemplo:**
- Si tu URL es: `http://localhost/TQ2025/`
- Debes cambiarla a: `http://localhost/TQ2025/?editor=true`

Una vez activo, verás dos nuevos botones en la parte superior de la pantalla: **"Gestión"** e **"Insertar"**, que te darán acceso rápido a las herramientas de administración de palabras.

## 2. Cómo Añadir Palabras Después de una Partida

El propósito principal del modo editor es facilitar la incorporación de palabras válidas que el sistema marcó como incorrectas durante una partida.

**Pasos:**

1.  **Juega una ronda normal:** Inicia y completa una partida en cualquier categoría.
2.  **Revisa los resultados:** Al finalizar el juego (ya sea por tiempo o manualmente), aparecerá la pantalla de resultados.
3.  **Identifica palabras para añadir:** Busca las palabras que fueron marcadas como "INCORRECTO". Si consideras que la palabra es correcta pero simplemente no estaba en la base de datos, verás un botón al lado que dice **"Aceptar y Añadir"**.
4.  **Añade la palabra:** Haz clic en el botón "Aceptar y Añadir". La palabra se guardará automáticamente en la subcategoría correspondiente a la ronda que acabas de jugar.
5.  **Confirmación:** El botón cambiará su texto a "¡Añadida!" y la puntuación de la partida se recalculará como si la palabra hubiera sido correcta desde el principio.

---
**Importante:** Usa esta función con responsabilidad. Asegúrate de que la palabra es ortográficamente correcta y pertenece a la categoría antes de añadirla para mantener la calidad de la base de datos.
