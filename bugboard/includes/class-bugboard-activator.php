<?php
/**
 * Clase para manejar la activación del plugin
 */
class BugBoard_Activator {

    /**
     * Crear tablas personalizadas al activar el plugin
     */
    public static function activate() {
        self::create_tables();
        self::insert_default_data();
    }

    /**
     * Crear las tablas personalizadas
     */
    private static function create_tables() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        // Tabla principal de tareas
        $table_tasks = $wpdb->prefix . 'bugboard_tasks';
        $sql_tasks = "CREATE TABLE $table_tasks (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            title varchar(255) NOT NULL,
            description text,
            status varchar(50) NOT NULL DEFAULT 'por-hacer',
            priority varchar(20) NOT NULL DEFAULT 'media',
            assignee_id bigint(20) UNSIGNED,
            author_id bigint(20) UNSIGNED NOT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            due_date datetime NULL,
            estimated_hours decimal(5,2) NULL,
            PRIMARY KEY (id),
            KEY status (status),
            KEY priority (priority),
            KEY assignee_id (assignee_id),
            KEY author_id (author_id),
            FOREIGN KEY (assignee_id) REFERENCES {$wpdb->users}(ID) ON DELETE SET NULL,
            FOREIGN KEY (author_id) REFERENCES {$wpdb->users}(ID) ON DELETE CASCADE
        ) $charset_collate;";

        // Tabla de comentarios
        $table_comments = $wpdb->prefix . 'bugboard_comments';
        $sql_comments = "CREATE TABLE $table_comments (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            task_id mediumint(9) NOT NULL,
            user_id bigint(20) UNSIGNED NOT NULL,
            comment text NOT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY task_id (task_id),
            KEY user_id (user_id),
            FOREIGN KEY (task_id) REFERENCES $table_tasks(id) ON DELETE CASCADE,
            FOREIGN KEY (user_id) REFERENCES {$wpdb->users}(ID) ON DELETE CASCADE
        ) $charset_collate;";

        // Tabla de archivos adjuntos
        $table_attachments = $wpdb->prefix . 'bugboard_attachments';
        $sql_attachments = "CREATE TABLE $table_attachments (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            task_id mediumint(9) NOT NULL,
            filename varchar(255) NOT NULL,
            filepath varchar(500) NOT NULL,
            filesize bigint(20) UNSIGNED NOT NULL,
            mime_type varchar(100) NOT NULL,
            uploaded_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY task_id (task_id),
            FOREIGN KEY (task_id) REFERENCES $table_tasks(id) ON DELETE CASCADE
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        
        dbDelta($sql_tasks);
        dbDelta($sql_comments);
        dbDelta($sql_attachments);
        
        // Guardar versión de la base de datos
        update_option('bugboard_db_version', '1.0.0');
    }

    /**
     * Insertar datos por defecto
     */
    private static function insert_default_data() {
        global $wpdb;
        
        $table_tasks = $wpdb->prefix . 'bugboard_tasks';
        $current_user_id = get_current_user_id();
        
        // Insertar algunas tareas de ejemplo
        $sample_tasks = array(
            array(
                'title' => 'Configurar el plugin BugBoard',
                'description' => 'Instalar y configurar todas las funcionalidades del plugin',
                'status' => 'completado',
                'priority' => 'alta',
                'assignee_id' => $current_user_id,
                'author_id' => $current_user_id
            ),
            array(
                'title' => 'Implementar drag and drop',
                'description' => 'Agregar funcionalidad de arrastrar y soltar para las tareas',
                'status' => 'en-progreso',
                'priority' => 'alta',
                'assignee_id' => $current_user_id,
                'author_id' => $current_user_id
            ),
            array(
                'title' => 'Diseñar interfaz de usuario',
                'description' => 'Crear una interfaz moderna y responsiva',
                'status' => 'por-hacer',
                'priority' => 'media',
                'assignee_id' => $current_user_id,
                'author_id' => $current_user_id
            )
        );
        
        foreach ($sample_tasks as $task) {
            $wpdb->insert(
                $table_tasks,
                $task,
                array('%s', '%s', '%s', '%s', '%d', '%d')
            );
        }
    }

    /**
     * Desinstalar el plugin
     */
    public static function uninstall() {
        global $wpdb;
        
        // Eliminar tablas
        $wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}bugboard_attachments");
        $wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}bugboard_comments");
        $wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}bugboard_tasks");
        
        // Eliminar opciones
        delete_option('bugboard_db_version');
    }
} 