# BugBoard - Plugin de WordPress

## Descripción
BugBoard es un plugin de WordPress para la gestión de tareas y bugs. Proporciona un sistema de gestión de tareas con un tablero personalizado en el panel de administración.

## Características

### Funcionalidades Actuales
- **Menú de Administración**: Menú principal "BugBoard" con icono de bug
- **Submenú Tablero**: Vista del tablero (en construcción)
- **Custom Post Type**: "Tareas" (slug: bug) para gestionar las tareas
- **Interfaz en Español**: Todos los textos están en español

### Custom Post Type "Tareas"
- **Visibilidad**: Solo en el panel de administración (no público)
- **Campos soportados**: Título, Editor, Autor
- **Icono**: dashicons-bug
- **Sin REST API**: No disponible para exportación

## Instalación

1. Copia la carpeta `bugboard` a `/wp-content/plugins/`
2. Activa el plugin desde el panel de administración de WordPress
3. El menú "BugBoard" aparecerá en el menú lateral del admin

## Estructura del Plugin

```
bugboard/
├── bugboard.php          # Archivo principal del plugin
├── assets/
│   └── css/
│       └── admin.css     # Estilos del admin
└── README.md             # Este archivo
```

## Uso

### Acceso al Plugin
1. Ve al panel de administración de WordPress
2. Busca el menú "BugBoard" en el menú lateral
3. Haz clic en "Tablero" para ver la vista del tablero

### Gestión de Tareas
1. En el menú lateral, busca "Tareas"
2. Puedes crear, editar y gestionar las tareas desde ahí

## Desarrollo

### Hooks Utilizados
- `init`: Para registrar el Custom Post Type
- `admin_menu`: Para añadir menús al admin
- `admin_enqueue_scripts`: Para cargar estilos
- `plugins_loaded`: Para inicializar el plugin

### Funciones Principales
- `BugBoard::register_bug_post_type()`: Registra el CPT
- `BugBoard::add_admin_menu()`: Añade los menús
- `BugBoard::bugboard_tablero_page()`: Renderiza la página del tablero

## Versión
1.0.0

## Licencia
GPL v2 or later 