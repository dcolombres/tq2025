# Changelog

Todas las modificaciones notables a este proyecto serán documentadas en este archivo.

El formato está basado en [Keep a Changelog](https://keepachangelog.com/en/1.0.0/).

## [Unreleased]

### Added
- **Modo Editor:**
  - Permite incorporar palabras que se usaron para jugar una vez finalizada la ronda.
  - Requiere que la URL del modo editor esté activa.
- **Accesos Directos:**
  - Se incorporaron accesos directos a `gestion.html` y `TQinsert.html`.
- **Sistema de Validación Externa (Tolerancia a Errores de Tipeo):**
  - Se implementó una lógica de tolerancia para errores de tipeo menores en `game_logic.php`.
  - Si una palabra no se encuentra en la base de datos, el sistema ahora busca palabras similares usando la distancia de Levenshtein.
  - Las palabras con errores menores (hasta 2 caracteres de diferencia) se marcan con el nuevo estado `MAL_ESCRITO`.
  - Se añadió la puntuación correspondiente en `script.js`, otorgando **7 puntos** a las palabras `MAL_ESCRITO`.
  - Las casillas de palabras con este estado ahora se muestran con un fondo amarillo para feedback visual.

- **Sistema de Validación por API Externa (Concepto y Lógica):**
  - Se añadió una columna `permite_validacion_externa` a la tabla `Subcategorias` en la base de datos para controlar qué categorías pueden usar validación externa.
  - Se actualizó `game_logic.php` para leer esta bandera y aplicar la lógica de validación externa solo cuando esté permitido.
  - Se introdujo un nuevo estado `VALIDADO_EXTERNAMENTE` para palabras que no están en la base de datos local pero son consideradas válidas por un servicio externo.
  - Se ajustó `script.js` para otorgar **5 puntos** a estas palabras y colorear la casilla de verde.

- **Documentación de Código:**
  - Se añadieron bloques de comentarios detallados en `game_logic.php` explicando el flujo de validación externa de dos pasos (existencia y relevancia) para facilitar el trabajo de futuros desarrolladores.

- **Archivo de Changelog:**
  - Se creó este archivo (`changelog.md`) para documentar el historial de cambios del proyecto.

### Changed
- **Integración de API de la RAE:**
  - Se reemplazó la simulación de validación externa en `game_logic.php` con una llamada real a la API no oficial de la RAE (`api.rae-api.com`).

### Fixed
- **Problemas de Conexión en Entorno Local (Debugging):**
  - Se reemplazó la función `get_headers` por una implementación más robusta con `cURL` en `game_logic.php` para mejorar la compatibilidad con entornos locales como XAMPP.
  - Se corrigió un problema de resolución de DNS (`Could not resolve host`) causado por sufijos de dominio locales. La URL de la API fue modificada para usar un Nombre de Dominio Completo (FQDN) añadiendo un punto al final del dominio (ej. `api.rae-api.com.`).
  - Como último paso de diagnóstico, se cambió el protocolo de la API de `https` a `http` para descartar problemas de configuración de SSL en XAMPP.