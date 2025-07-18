<?php
/**
 * Clase para manejar las peticiones AJAX del BugBoard
 */
class BugBoard_Ajax {

    /**
     * Constructor
     */
    public function __construct() {
        add_action('wp_ajax_bugboard_get_tasks', array($this, 'get_tasks'));
        add_action('wp_ajax_bugboard_get_task', array($this, 'get_task'));
        add_action('wp_ajax_bugboard_create_task', array($this, 'create_task'));
        add_action('wp_ajax_bugboard_update_task', array($this, 'update_task'));
        add_action('wp_ajax_bugboard_delete_task', array($this, 'delete_task'));
        add_action('wp_ajax_bugboard_update_task_status', array($this, 'update_task_status'));
        add_action('wp_ajax_bugboard_get_users', array($this, 'get_users'));
    }

    /**
     * Obtener todas las tareas
     */
    public function get_tasks() {
        // Verificar nonce
        if (!wp_verify_nonce($_POST['nonce'], 'bugboard_nonce')) {
            wp_die('Nonce inválido');
        }

        // Verificar permisos
        if (!current_user_can('manage_options')) {
            wp_die('Permisos insuficientes');
        }

        try {
            $tasks = BugBoard_Tasks::get_all_tasks();
            
            // Formatear tareas para el frontend
            $formatted_tasks = array();
            foreach ($tasks as $task) {
                $formatted_tasks[] = BugBoard_Tasks::format_task_for_frontend($task);
            }
            
            wp_send_json_success($formatted_tasks);
        } catch (Exception $e) {
            wp_send_json_error(array('message' => $e->getMessage()));
        }
    }

    /**
     * Obtener una tarea específica
     */
    public function get_task() {
        // Verificar nonce
        if (!wp_verify_nonce($_POST['nonce'], 'bugboard_nonce')) {
            wp_die('Nonce inválido');
        }

        // Verificar permisos
        if (!current_user_can('manage_options')) {
            wp_die('Permisos insuficientes');
        }

        $task_id = intval($_POST['task_id']);
        
        if (!$task_id) {
            wp_send_json_error(array('message' => 'ID de tarea inválido'));
        }

        try {
            $task = BugBoard_Tasks::get_task($task_id);
            
            if (!$task) {
                wp_send_json_error(array('message' => 'Tarea no encontrada'));
            }
            
            $formatted_task = BugBoard_Tasks::format_task_for_frontend($task);
            wp_send_json_success($formatted_task);
        } catch (Exception $e) {
            wp_send_json_error(array('message' => $e->getMessage()));
        }
    }

    /**
     * Crear una nueva tarea
     */
    public function create_task() {
        // Verificar nonce
        if (!wp_verify_nonce($_POST['nonce'], 'bugboard_nonce')) {
            wp_die('Nonce inválido');
        }

        // Verificar permisos
        if (!current_user_can('manage_options')) {
            wp_die('Permisos insuficientes');
        }

        $task_data = array(
            'title' => sanitize_text_field($_POST['title']),
            'description' => sanitize_textarea_field($_POST['description']),
            'status' => sanitize_text_field($_POST['status']),
            'priority' => sanitize_text_field($_POST['priority']),
            'assignee_id' => !empty($_POST['assignee']) ? intval($_POST['assignee']) : null,
            'due_date' => !empty($_POST['due_date']) ? sanitize_text_field($_POST['due_date']) : null,
            'estimated_hours' => !empty($_POST['estimated_hours']) ? floatval($_POST['estimated_hours']) : null
        );

        try {
            $task_id = BugBoard_Tasks::create_task($task_data);
            
            if (is_wp_error($task_id)) {
                wp_send_json_error(array('message' => $task_id->get_error_message()));
            }
            
            // Obtener la tarea creada
            $task = BugBoard_Tasks::get_task($task_id);
            $formatted_task = BugBoard_Tasks::format_task_for_frontend($task);
            
            wp_send_json_success(array(
                'message' => 'Tarea creada correctamente',
                'task' => $formatted_task
            ));
        } catch (Exception $e) {
            wp_send_json_error(array('message' => $e->getMessage()));
        }
    }

    /**
     * Actualizar una tarea
     */
    public function update_task() {
        // Verificar nonce
        if (!wp_verify_nonce($_POST['nonce'], 'bugboard_nonce')) {
            wp_die('Nonce inválido');
        }

        // Verificar permisos
        if (!current_user_can('manage_options')) {
            wp_die('Permisos insuficientes');
        }

        $task_id = intval($_POST['task_id']);
        
        if (!$task_id) {
            wp_send_json_error(array('message' => 'ID de tarea inválido'));
        }

        $task_data = array(
            'title' => sanitize_text_field($_POST['title']),
            'description' => sanitize_textarea_field($_POST['description']),
            'status' => sanitize_text_field($_POST['status']),
            'priority' => sanitize_text_field($_POST['priority']),
            'assignee_id' => !empty($_POST['assignee']) ? intval($_POST['assignee']) : null,
            'due_date' => !empty($_POST['due_date']) ? sanitize_text_field($_POST['due_date']) : null,
            'estimated_hours' => !empty($_POST['estimated_hours']) ? floatval($_POST['estimated_hours']) : null
        );

        try {
            $result = BugBoard_Tasks::update_task($task_id, $task_data);
            
            if (is_wp_error($result)) {
                wp_send_json_error(array('message' => $result->get_error_message()));
            }
            
            // Obtener la tarea actualizada
            $task = BugBoard_Tasks::get_task($task_id);
            $formatted_task = BugBoard_Tasks::format_task_for_frontend($task);
            
            wp_send_json_success(array(
                'message' => 'Tarea actualizada correctamente',
                'task' => $formatted_task
            ));
        } catch (Exception $e) {
            wp_send_json_error(array('message' => $e->getMessage()));
        }
    }

    /**
     * Eliminar una tarea
     */
    public function delete_task() {
        // Verificar nonce
        if (!wp_verify_nonce($_POST['nonce'], 'bugboard_nonce')) {
            wp_die('Nonce inválido');
        }

        // Verificar permisos
        if (!current_user_can('manage_options')) {
            wp_die('Permisos insuficientes');
        }

        $task_id = intval($_POST['task_id']);
        
        if (!$task_id) {
            wp_send_json_error(array('message' => 'ID de tarea inválido'));
        }

        try {
            $result = BugBoard_Tasks::delete_task($task_id);
            
            if (is_wp_error($result)) {
                wp_send_json_error(array('message' => $result->get_error_message()));
            }
            
            wp_send_json_success(array('message' => 'Tarea eliminada correctamente'));
        } catch (Exception $e) {
            wp_send_json_error(array('message' => $e->getMessage()));
        }
    }

    /**
     * Actualizar el estado de una tarea
     */
    public function update_task_status() {
        // Verificar nonce
        if (!wp_verify_nonce($_POST['nonce'], 'bugboard_nonce')) {
            wp_die('Nonce inválido');
        }

        // Verificar permisos
        if (!current_user_can('manage_options')) {
            wp_die('Permisos insuficientes');
        }

        $task_id = intval($_POST['task_id']);
        $new_status = sanitize_text_field($_POST['status']);
        
        if (!$task_id) {
            wp_send_json_error(array('message' => 'ID de tarea inválido'));
        }

        try {
            $result = BugBoard_Tasks::update_task_status($task_id, $new_status);
            
            if (is_wp_error($result)) {
                wp_send_json_error(array('message' => $result->get_error_message()));
            }
            
            wp_send_json_success(array('message' => 'Estado actualizado correctamente'));
        } catch (Exception $e) {
            wp_send_json_error(array('message' => $e->getMessage()));
        }
    }

    /**
     * Obtener usuarios disponibles
     */
    public function get_users() {
        // Verificar nonce
        if (!wp_verify_nonce($_POST['nonce'], 'bugboard_nonce')) {
            wp_die('Nonce inválido');
        }

        // Verificar permisos
        if (!current_user_can('manage_options')) {
            wp_die('Permisos insuficientes');
        }

        try {
            $users = BugBoard_Tasks::get_available_users();
            wp_send_json_success($users);
        } catch (Exception $e) {
            wp_send_json_error(array('message' => $e->getMessage()));
        }
    }
} 