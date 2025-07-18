# BugBoard - Plugin de WordPress

Un tablero Kanban moderno para gestión de tareas y bugs en WordPress.

## Características

- **Tablero Kanban** con columnas: Por Hacer, En Progreso, En Revisión, Completado
- **Drag & Drop** fluido para mover tareas entre columnas
- **Gestión de tareas** con título, descripción, prioridad, asignado y fecha límite
- **Interfaz moderna** y responsiva
- **Actualización optimista** de la UI
- **Sincronización automática** con el servidor
- **Sistema de usuarios** integrado con WordPress

## Instalación

1. Sube la carpeta `bugboard` al directorio `/wp-content/plugins/` de tu instalación de WordPress
2. Activa el plugin a través del menú 'Plugins' en WordPress
3. Accede al tablero desde el menú 'BugBoard' en el panel de administración

## Estructura de Base de Datos

El plugin crea las siguientes tablas personalizadas:

### `wp_bugboard_tasks`
- `id` - ID único de la tarea
- `title` - Título de la tarea
- `description` - Descripción detallada
- `status` - Estado actual (por-hacer, en-progreso, en-revision, completado)
- `priority` - Prioridad (baja, media, alta, critica)
- `assignee_id` - ID del usuario asignado
- `author_id` - ID del usuario que creó la tarea
- `created_at` - Fecha de creación
- `updated_at` - Fecha de última actualización
- `due_date` - Fecha límite (opcional)
- `estimated_hours` - Horas estimadas (opcional)

### `wp_bugboard_comments`
- `id` - ID único del comentario
- `task_id` - ID de la tarea
- `user_id` - ID del usuario que comentó
- `comment` - Contenido del comentario
- `created_at` - Fecha de creación

### `wp_bugboard_attachments`
- `id` - ID único del archivo
- `task_id` - ID de la tarea
- `filename` - Nombre del archivo
- `filepath` - Ruta del archivo
- `filesize` - Tamaño del archivo
- `mime_type` - Tipo MIME
- `uploaded_at` - Fecha de subida

## Uso

### Crear una nueva tarea
1. Haz clic en el botón "+" en cualquier columna
2. Completa el formulario con los datos de la tarea
3. Haz clic en "Guardar"

### Mover una tarea
1. Haz clic y arrastra la tarea a la columna deseada
2. Suelta la tarea en la nueva ubicación
3. El estado se actualizará automáticamente

### Editar una tarea
1. Haz clic en el botón de editar (✏️) en la tarea
2. Modifica los datos en el modal
3. Haz clic en "Guardar"

### Eliminar una tarea
1. Haz clic en el botón de eliminar (🗑️) en la tarea
2. Confirma la eliminación

## Desarrollo

### Estructura de archivos
```
bugboard/
├── bugboard.php              # Archivo principal del plugin
├── includes/
│   ├── class-bugboard-activator.php    # Activación y creación de tablas
│   ├── class-bugboard-tasks.php        # Manejo de tareas
│   ├── class-bugboard-ajax.php         # Manejadores AJAX
│   ├── class-bugboard-admin.php        # Interfaz de administración
│   └── class-bugboard-tablero.php      # Tablero Kanban
├── assets/
│   ├── css/
│   │   └── admin.css         # Estilos del admin
│   └── js/
│       └── tablero.js        # JavaScript del tablero
└── README.md                 # Este archivo
```

### Hooks AJAX disponibles
- `bugboard_get_tasks` - Obtener todas las tareas
- `bugboard_get_task` - Obtener una tarea específica
- `bugboard_create_task` - Crear una nueva tarea
- `bugboard_update_task` - Actualizar una tarea
- `bugboard_delete_task` - Eliminar una tarea
- `bugboard_update_task_status` - Actualizar estado de tarea
- `bugboard_get_users` - Obtener usuarios disponibles

## Requisitos

- WordPress 5.0 o superior
- PHP 7.4 o superior
- MySQL 5.6 o superior

## Licencia

GPL v2 o posterior

## Autor

Tu Nombre - [tu-sitio.com](https://tu-sitio.com)

## Changelog

### 1.0.0
- Versión inicial
- Tablero Kanban funcional
- Drag & drop implementado
- Gestión completa de tareas
- Interfaz moderna y responsiva 