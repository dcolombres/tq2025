# Changelog

Todas las modificaciones notables a este proyecto serán documentadas en este archivo.

El formato está basado en [Keep a Changelog](https://keepachangelog.com/en/1.0.0/).

## [Unreleased]

### Added
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

### Fixed
- **Compatibilidad con Servidor de Producción (Linux):**
  - Se estandarizaron todos los nombres de tablas a minúsculas en las consultas SQL para asegurar la compatibilidad.
  - Se eliminaron las credenciales de base de datos locales de los scripts y se centralizó el uso de `db_config.php` en todo el proyecto.
- **Modo Party - Aleatoriedad:**
  - Se reemplazó `ORDER BY RAND()` por un método de `COUNT` y `OFFSET` para evitar problemas de caché y asegurar una aleatoriedad real en cada petición.
  - Se corrigió un bug que causaba que el "Modo Party" seleccionara categorías del modo "Tutti".

### Changed
- **Refactorización y Documentación:**
  - Se han revisado, estandarizado y documentado todos los archivos `.php` del backend para mejorar la legibilidad y el mantenimiento futuro.