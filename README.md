# Tutti Quanti - The Alphabet Challenge

Un juego de palabras basado en el clásico "Tutti Frutti" o "Basta", donde los jugadores deben encontrar palabras para cada letra del abecedario que correspondan a una categoría aleatoria, todo bajo la presión del tiempo.

Este proyecto ha sido refactorizado para utilizar una arquitectura cliente-servidor, con un frontend dinámico en JavaScript y un backend en PHP que gestiona la lógica del juego y la interacción con una base de datos MySQL.

## Características Principales

- **Juego Dinámico**: Cada partida presenta una categoría aleatoria obtenida desde la base de datos.
- **Sistema de Puntuación**: Suma 10 puntos por respuesta correcta y resta 3 por cada una incorrecta.
- **Bonus por Tiempo**: Finalizar el juego antes de que acabe el tiempo otorga puntos extra.
- **Validación Centralizada**: Las respuestas se validan en el servidor contra una base de datos propia, garantizando seguridad y consistencia.
- **Interfaz Moderna**: Un diseño limpio y responsivo, con animaciones y feedback visual claro para el usuario.
- **Gestión de Contenido**: Incluye un formulario para insertar nuevas palabras y categorías en la base de datos.

## Tecnologías Utilizadas

- **Frontend**: HTML5, CSS3, JavaScript (ES6+)
- **Backend**: PHP
- **Base de Datos**: MySQL

## Estructura del Proyecto

```
D:/TQ2025/
├── index.html            # Estructura principal del juego.
├── style.css             # Estilos y diseño visual de la interfaz.
├── script.js             # Lógica del frontend (interacción, temporizador, llamadas al backend).
├── game_logic.php        # Backend principal: obtiene categorías y valida palabras.
├── TQinsert.html         # Formulario para añadir nuevas palabras a la BD.
├── insert_word.php       # Script que procesa la inserción de palabras.
├── get_subcategorias.php # Script para obtener subcategorías (usado en TQinsert.html).
├── crearBD.txt           # (Placeholder) Debería contener el script SQL para la BD.
└── README.md             # Este archivo.
```

## Instalación y Puesta en Marcha

Sigue estos pasos para configurar el proyecto en un entorno de desarrollo local.

### 1. Prerrequisitos

- Un servidor web local con soporte para PHP (ej. XAMPP, WAMP, MAMP).
- Un servidor de base de datos MySQL.

### 2. Base de Datos

1.  Crea una nueva base de datos en MySQL. El nombre utilizado en los scripts es `moroarte_tutti_quanti`, pero puedes cambiarlo.
2.  Crea las siguientes tablas:

    **Tabla `Categorias`**
    ```sql
    CREATE TABLE Categorias (
        id INT AUTO_INCREMENT PRIMARY KEY,
        nombre VARCHAR(255) NOT NULL
    );
    ```

    **Tabla `Subcategorias`**
    ```sql
    CREATE TABLE Subcategorias (
        id INT AUTO_INCREMENT PRIMARY KEY,
        nombre VARCHAR(255) NOT NULL,
        categoria_id INT,
        FOREIGN KEY (categoria_id) REFERENCES Categorias(id)
    );
    ```

    **Tabla `Palabras`**
    ```sql
    CREATE TABLE Palabras (
        id INT AUTO_INCREMENT PRIMARY KEY,
        palabra VARCHAR(255) NOT NULL,
        letra CHAR(2) NOT NULL, -- Se usa CHAR(2) para contemplar letras como 'Ñ'
        subcategoria_id INT,
        categoria_id INT, -- Aunque redundante, está presente en los scripts de inserción
        FOREIGN KEY (subcategoria_id) REFERENCES Subcategorias(id),
        FOREIGN KEY (categoria_id) REFERENCES Categorias(id)
    );
    ```

### 3. Configuración

1.  Clona o descarga los archivos del proyecto en el directorio de tu servidor web (ej. `htdocs` en XAMPP).
2.  Abre los siguientes archivos PHP y actualiza las credenciales de conexión a tu base de datos:
    - `game_logic.php`
    - `get_subcategorias.php`
    - `insert_word.php`

    Busca estas líneas y modifícalas según tu configuración:
    ```php
    $servername = "localhost";
    $username = "tu_usuario_de_bd";
    $password = "tu_contraseña_de_bd";
    $dbname = "tu_nombre_de_bd";
    ```

### 4. Poblar la Base de Datos

- Usa el archivo `TQinsert.html` para empezar a añadir categorías, subcategorías y palabras a tu base de datos.

## Cómo Jugar

1.  Abre `index.html` en tu navegador.
2.  Haz clic en **"RANDOM"** para que se te asigne una categoría al azar y comience la cuenta regresiva.
3.  Completa la mayor cantidad de campos posibles, uno por cada letra del abecedario.
4.  Cuando termines (o cuando decidas parar), presiona el botón **"TUTTI QUANTI"** para finalizar el juego y calcular tu puntuación.
5.  Revisa tus resultados, incluyendo el desglose de puntos y la validación de cada palabra.

## Hoja de Ruta (Roadmap)

- [ ] **Mejorar la Interfaz de Resultados**: Hacer la pantalla de resultados más gráfica e interactiva.
- [ ] **Modo Party (App Mobile)**: Desarrollar una aplicación móvil que consuma la misma API de `game_logic.php` para permitir un modo de juego multijugador.
- [ ] **Sistema de Usuarios**: Añadir la capacidad de registrarse y guardar puntuaciones históricas.
- [ ] **Más Variedad de Juego**: Implementar niveles de dificultad que afecten el tiempo o la complejidad de las categorías.

## Autor

- **Moro Colombres** - [www.moroarte.com](https://www.moroarte.com)
