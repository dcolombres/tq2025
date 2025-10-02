# Changelog

Todas las modificaciones notables a este proyecto serán documentadas en este archivo.

El formato está basado en [Keep a Changelog](https://keepachangelog.com/en/1.0.0/).

## [2025-10-01]

### Added
- **Panel de Gestión (`gestion.php`):**
  - Creado un panel de control centralizado para toda la base de datos.
  - Permite Crear, Leer, Actualizar y Eliminar (CRUD) Categorías, Niveles y Subcategorías.
  - Incluye una vista para gestionar (añadir, editar, eliminar) las palabras específicas de cada subcategoría.
  - Funcionalidad para activar/desactivar la validación externa por subcategoría.
- **Herramienta de Inserción (`insert.php`):**
  - Unificada la funcionalidad de inserción de palabras.
  - Permite tanto la carga masiva de palabras separadas por comas como la visualización y gestión de las palabras ya existentes en una subcategoría.

### Changed
- **Arquitectura General:** Se refactorizó gran parte del proyecto a un modelo de scripts PHP auto-contenidos y auto-enviables, eliminando la dependencia de múltiples archivos AJAX que causaban problemas en el servidor de producción.
- **`index.php`:** Convertido a PHP para manejar el "modo editor" y para cargar dinámicamente las categorías de juego sin AJAX, solucionando un error crítico que impedía iniciar partidas.

### Fixed
- **Compatibilidad con Servidor:** Solucionado el error fatal y persistente que impedía la ejecución de scripts en el servidor. La causa raíz era una combinación de problemas de alcance de variables y la falta del driver `mysqlnd`. La nueva arquitectura soluciona esto.
- **Errores de Sintaxis:** Corregidos múltiples errores de sintaxis en PHP que causaban que las páginas no se cargaran.
- **Consistencia de UI:** Unificado el tamaño del logo y los enlaces en `party.php` e `index.php`.

### Removed
- Se eliminaron más de 10 archivos obsoletos, incluyendo `gestion.html`, `TQinsert.html`, `bulk_insert.php`, `csvwords.html`, y todos los scripts de backend individuales (`get_items.php`, `create_structure.php`, etc.), centralizando la lógica en los nuevos archivos principales.

## [Unreleased]

### Added
- **Carga Masiva de Palabras**:
  - Se ha creado la página `csvwords.html` para permitir la inserción masiva de palabras en una subcategoría específica mediante una lista separada por comas.
  - El sistema calcula y muestra el número de palabras nuevas insertadas y las duplicadas que fueron omitidas.
- Sistema de juego por rondas (6 rondas por partida completa) con contador visible.
- Historial de puntuaciones entre rondas con total general.
- Se añade un botón "Nueva Ronda" al finalizar el juego para permitir reiniciar sin recargar la página.
- **Modo Party:**
  - Se crea una nueva interfaz (`party.php`) para un modo de juego verbal adaptado a móviles.
  - Incluye un generador de categorías aleatorias con temporizador.
  - Permite elegir entre un conjunto de categorías "Party" o todas las del juego.
- **Gestión de Validación Externa:**
  - Se añade un interruptor en `gestion.html` para activar o desactivar la validación por API para cada subcategoría.
- **Modo Editor:**
  - Permite incorporar palabras que se usaron para jugar una vez finalizada la ronda.
  - Requiere que la URL del modo editor esté activa.
- **Accesos Directos:**
  - Se incorporaron accesos directos a `gestion.html` y `TQinsert.html`.
- **Sistema de Validación por API Externa (Wikipedia y RAE):**
  - Se integra la API de Wikipedia para verificar existencia y relevancia de palabras.
  - Se mejora el flujo de validación a: Base de Datos -> Wikipedia -> RAE -> Levenshtein (error de tipeo).

### Changed
- **Sistema de Puntuación:** Las respuestas correctas ahora otorgan 15 puntos y las vacías restan 3 puntos.
- **Botón "TUTTI QUANTI":** Ahora requiere que pase 1 minuto o se completen 15 respuestas para poder finalizar la ronda.
- **Interfaz:** Se reemplazaron todas las alertas del navegador por modales personalizados.
- **Refactorización y Documentación:**
  - Se han revisado, estandarizado y documentado todos los archivos `.php` del backend para mejorar la legibilidad y el mantenimiento futuro.

### Fixed
- **Carga Masiva de Palabras**:
  - Se corrigió un error que impedía la inserción de palabras debido a una consulta SQL incorrecta en `insert_csv_words.php`.
  - Se solucionó un error de "Duplicate entry" al implementar `INSERT IGNORE`, permitiendo que el script omita palabras ya existentes sin detenerse.
- **Obtención de Subcategorías**:
  - Se reparó un error en `get_subcategorias.php` que causaba un fallo en la carga del desplegable en `csvwords.html` debido a una consulta SQL con un `JOIN` incorrecto.
- **Páginas de Administración:** Corregida la incompatibilidad con la base de datos en los scripts (`get_stats.php`, `create_structure.php`) que impedían el funcionamiento de `TQinsert.html`.
- **Modo Party:** Se implementó una lógica para evitar la repetición de las últimas 10 subcategorías.
- **Estabilidad General:** Se corrigieron numerosos bugs críticos que impedían la carga de categorías, el inicio de las rondas y el cálculo de la puntuación final.
- **Compatibilidad:** Se refactorizaron todos los scripts del backend para eliminar el uso de `get_result()` y asegurar la compatibilidad con diferentes entornos de PHP/MySQL.
- **Validación Externa (RAE):**
  - Se reemplazó la API no oficial de la RAE (que dejó de funcionar) por un método de web scraping directo sobre `dle.rae.es` para validar palabras.
- **Validación Externa (Wikipedia):**
  - Se añadió un `User-Agent` a las peticiones cURL para evitar errores de conexión (403 Forbidden).
- **Compatibilidad con Servidor de Producción (Linux):**
  - Se estandarizaron todos los nombres de tablas a minúsculas en las consultas SQL para asegurar la compatibilidad.
  - Se eliminaron las credenciales de base de datos locales de los scripts y se centralizó el uso de `db_config.php` en todo el proyecto.
- **Modo Party - Aleatoriedad:**
  - Se reemplazó `ORDER BY RAND()` por un método de `COUNT` y `OFFSET` para evitar problemas de caché y asegurar una aleatoriedad real en cada petición.
  - Se corrigió un bug que causaba que el "Modo Party" seleccionara categorías del modo "Tutti".