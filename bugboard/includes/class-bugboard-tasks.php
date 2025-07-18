<?php
/**
 * Clase para manejar las tareas del BugBoard
 */
class BugBoard_Tasks {

    /**
     * Obtener todas las tareas
     */
    public static function get_all_tasks() {
        global $wpdb;
        
        $table_tasks = $wpdb->prefix . 'bugboard_tasks';
        $table_users = $wpdb->users;
        
        $query = "
            SELECT 
                t.id,
                t.title,
                t.description,
                t.status,
                t.priority,
                t.assignee_id,
                t.author_id,
                t.created_at,
                t.updated_at,
                t.due_date,
                t.estimated_hours,
                assignee.display_name as assignee_name,
                author.display_name as author_name
            FROM $table_tasks t
            LEFT JOIN $table_users assignee ON t.assignee_id = assignee.ID
            LEFT JOIN $table_users author ON t.author_id = author.ID
            ORDER BY t.created_at DESC
        ";
        
        return $wpdb->get_results($query, ARRAY_A);
    }

    /**
     * Obtener una tarea específica
     */
    public static function get_task($task_id) {
        global $wpdb;
        
        $table_tasks = $wpdb->prefix . 'bugboard_tasks';
        $table_users = $wpdb->users;
        
        $query = $wpdb->prepare("
            SELECT 
                t.id,
                t.title,
                t.description,
                t.status,
                t.priority,
                t.assignee_id,
                t.author_id,
                t.created_at,
                t.updated_at,
                t.due_date,
                t.estimated_hours,
                assignee.display_name as assignee_name,
                author.display_name as author_name
            FROM $table_tasks t
            LEFT JOIN $table_users assignee ON t.assignee_id = assignee.ID
            LEFT JOIN $table_users author ON t.author_id = author.ID
            WHERE t.id = %d
        ", $task_id);
        
        return $wpdb->get_row($query, ARRAY_A);
    }

    /**
     * Crear una nueva tarea
     */
    public static function create_task($data) {
        global $wpdb;
        
        $table_tasks = $wpdb->prefix . 'bugboard_tasks';
        
        $defaults = array(
            'title' => '',
            'description' => '',
            'status' => 'por-hacer',
            'priority' => 'media',
            'assignee_id' => null,
            'author_id' => get_current_user_id(),
            'due_date' => null,
            'estimated_hours' => null
        );
        
        $data = wp_parse_args($data, $defaults);
        
        // Validar datos requeridos
        if (empty($data['title'])) {
            return new WP_Error('title_required', 'El título es obligatorio');
        }
        
        // Insertar la tarea
        $result = $wpdb->insert(
            $table_tasks,
            $data,
            array('%s', '%s', '%s', '%s', '%d', '%d', '%s', '%f')
        );
        
        if ($result === false) {
            return new WP_Error('insert_failed', 'Error al crear la tarea');
        }
        
        return $wpdb->insert_id;
    }

    /**
     * Actualizar una tarea
     */
    public static function update_task($task_id, $data) {
        global $wpdb;
        
        $table_tasks = $wpdb->prefix . 'bugboard_tasks';
        
        // Validar que la tarea existe
        $existing_task = self::get_task($task_id);
        if (!$existing_task) {
            return new WP_Error('task_not_found', 'Tarea no encontrada');
        }
        
        // Actualizar la tarea
        $result = $wpdb->update(
            $table_tasks,
            $data,
            array('id' => $task_id),
            null,
            array('%d')
        );
        
        if ($result === false) {
            return new WP_Error('update_failed', 'Error al actualizar la tarea');
        }
        
        return true;
    }

    /**
     * Eliminar una tarea
     */
    public static function delete_task($task_id) {
        global $wpdb;
        
        $table_tasks = $wpdb->prefix . 'bugboard_tasks';
        
        // Validar que la tarea existe
        $existing_task = self::get_task($task_id);
        if (!$existing_task) {
            return new WP_Error('task_not_found', 'Tarea no encontrada');
        }
        
        // Eliminar la tarea
        $result = $wpdb->delete(
            $table_tasks,
            array('id' => $task_id),
            array('%d')
        );
        
        if ($result === false) {
            return new WP_Error('delete_failed', 'Error al eliminar la tarea');
        }
        
        return true;
    }

    /**
     * Actualizar el estado de una tarea
     */
    public static function update_task_status($task_id, $new_status) {
        $valid_statuses = array('por-hacer', 'en-progreso', 'en-revision', 'completado');
        
        if (!in_array($new_status, $valid_statuses)) {
            return new WP_Error('invalid_status', 'Estado no válido');
        }
        
        return self::update_task($task_id, array('status' => $new_status));
    }

    /**
     * Obtener tareas por estado
     */
    public static function get_tasks_by_status($status) {
        global $wpdb;
        
        $table_tasks = $wpdb->prefix . 'bugboard_tasks';
        $table_users = $wpdb->users;
        
        $query = $wpdb->prepare("
            SELECT 
                t.id,
                t.title,
                t.description,
                t.status,
                t.priority,
                t.assignee_id,
                t.author_id,
                t.created_at,
                t.updated_at,
                t.due_date,
                t.estimated_hours,
                assignee.display_name as assignee_name,
                author.display_name as author_name
            FROM $table_tasks t
            LEFT JOIN $table_users assignee ON t.assignee_id = assignee.ID
            LEFT JOIN $table_users author ON t.author_id = author.ID
            WHERE t.status = %s
            ORDER BY t.created_at DESC
        ", $status);
        
        return $wpdb->get_results($query, ARRAY_A);
    }

    /**
     * Obtener estadísticas de tareas
     */
    public static function get_task_stats() {
        global $wpdb;
        
        $table_tasks = $wpdb->prefix . 'bugboard_tasks';
        
        $query = "
            SELECT 
                status,
                COUNT(*) as count
            FROM $table_tasks
            GROUP BY status
        ";
        
        $results = $wpdb->get_results($query, ARRAY_A);
        
        $stats = array(
            'por-hacer' => 0,
            'en-progreso' => 0,
            'en-revision' => 0,
            'completado' => 0,
            'total' => 0
        );
        
        foreach ($results as $row) {
            $stats[$row['status']] = (int) $row['count'];
            $stats['total'] += (int) $row['count'];
        }
        
        return $stats;
    }

    /**
     * Obtener usuarios disponibles para asignación
     */
    public static function get_available_users() {
        $users = get_users(array(
            'orderby' => 'display_name',
            'order' => 'ASC'
        ));
        
        $available_users = array();
        foreach ($users as $user) {
            $available_users[] = array(
                'id' => $user->ID,
                'name' => $user->display_name,
                'email' => $user->user_email
            );
        }
        
        return $available_users;
    }

    /**
     * Formatear datos de tarea para el frontend
     */
    public static function format_task_for_frontend($task) {
        return array(
            'id' => $task['id'],
            'title' => $task['title'],
            'description' => $task['description'],
            'status' => $task['status'],
            'priority' => $task['priority'],
            'assignee_id' => $task['assignee_id'],
            'assignee_name' => $task['assignee_name'] ?: 'Sin asignar',
            'author_id' => $task['author_id'],
            'author_name' => $task['author_name'],
            'created_at' => $task['created_at'],
            'updated_at' => $task['updated_at'],
            'due_date' => $task['due_date'],
            'estimated_hours' => $task['estimated_hours']
        );
    }
} 