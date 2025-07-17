<?php
/**
 * Clase para manejar el tablero Kanban de BugBoard
 */

if (!defined('ABSPATH')) {
    exit;
}

class BugBoard_Tablero {
    
    /**
     * Constructor
     */
    public function __construct() {
        error_log('BugBoard: Inicializando clase BugBoard_Tablero');
        
        // Registrar hooks AJAX
        add_action('wp_ajax_bugboard_update_task_status', array($this, 'update_task_status'));
        add_action('wp_ajax_bugboard_get_tasks', array($this, 'get_tasks'));
        add_action('wp_ajax_nopriv_bugboard_get_tasks', array($this, 'get_tasks'));
        
        error_log('BugBoard: Hooks AJAX registrados');
    }
    
    /**
     * Registrar hooks AJAX (método separado para llamar desde el archivo principal)
     */
    public function register_ajax_hooks() {
        error_log('BugBoard: Registrando hooks AJAX...');
        
        add_action('wp_ajax_bugboard_update_task_status', array($this, 'update_task_status'));
        add_action('wp_ajax_bugboard_get_tasks', array($this, 'get_tasks'));
        add_action('wp_ajax_nopriv_bugboard_get_tasks', array($this, 'get_tasks'));
        
        // Función de prueba AJAX
        add_action('wp_ajax_bugboard_test', array($this, 'test_ajax'));
        add_action('wp_ajax_nopriv_bugboard_test', array($this, 'test_ajax'));
        
        // Función para obtener una tarea específica
        add_action('wp_ajax_bugboard_get_task', array($this, 'get_task'));
        add_action('wp_ajax_nopriv_bugboard_get_task', array($this, 'get_task'));
        
        // Función de prueba para get_task
        add_action('wp_ajax_bugboard_test_get_task', array($this, 'test_get_task'));
        add_action('wp_ajax_nopriv_bugboard_test_get_task', array($this, 'test_get_task'));
        
        // Función para eliminar una tarea
        add_action('wp_ajax_bugboard_delete_task', array($this, 'delete_task'));
        add_action('wp_ajax_nopriv_bugboard_delete_task', array($this, 'delete_task'));
        
        error_log('BugBoard: Hooks AJAX registrados correctamente');
    }
    
    /**
     * Función de prueba AJAX
     */
    public function test_ajax() {
        error_log('BugBoard: test_ajax() llamada');
        wp_send_json_success(array('message' => 'AJAX funciona correctamente'));
    }
    
    /**
     * Función de prueba para get_task
     */
    public function test_get_task() {
        error_log('BugBoard: test_get_task() llamada');
        error_log('BugBoard: POST data: ' . print_r($_POST, true));
        wp_send_json_success(array('message' => 'get_task hook registrado correctamente'));
    }
    
    /**
     * Obtener una tarea específica
     */
    public function get_task() {
        try {
            error_log('BugBoard: get_task() llamada');
            
            // Verificar nonce
            if (!wp_verify_nonce($_POST['nonce'], 'bugboard_nonce')) {
                wp_send_json_error(array('message' => 'Nonce inválido'));
                return;
            }
            
            // Obtener ID de la tarea
            $task_id = intval($_POST['task_id']);
            if (!$task_id) {
                wp_send_json_error(array('message' => 'ID de tarea inválido'));
                return;
            }
            
            // Obtener la tarea
            $task = get_post($task_id);
            if (!$task || $task->post_type !== 'bug') {
                wp_send_json_error(array('message' => 'Tarea no encontrada'));
                return;
            }
            
            // Obtener metadatos
            $status = get_post_meta($task_id, '_bugboard_status', true);
            $priority = get_post_meta($task_id, '_bugboard_priority', true);
            $assignee = get_post_meta($task_id, '_bugboard_assignee', true);
            
            // Valores por defecto si no existen
            if (empty($status)) $status = 'por-hacer';
            if (empty($priority)) $priority = 'media';
            
            // Obtener información del autor de forma segura
            $author_name = '';
            if ($task->post_author) {
                $author = get_userdata($task->post_author);
                $author_name = $author ? $author->display_name : 'Usuario desconocido';
            }
            
            // Obtener fecha de forma segura
            $date = '';
            if ($task->post_date) {
                $date = date('d/m/Y', strtotime($task->post_date));
            }
            
            $task_data = array(
                'id' => $task_id,
                'title' => $task->post_title,
                'description' => $task->post_content,
                'status' => $status,
                'priority' => $priority,
                'assignee' => $assignee,
                'author' => $author_name,
                'date' => $date
            );
            
            wp_send_json_success($task_data);
            
        } catch (Exception $e) {
            error_log('BugBoard: Error en get_task: ' . $e->getMessage());
            wp_send_json_error(array('message' => 'Error interno: ' . $e->getMessage()));
        }
    }
    
    /**
     * Eliminar una tarea
     */
    public function delete_task() {
        try {
            error_log('BugBoard: delete_task() llamada');
            
            // Verificar nonce
            if (!wp_verify_nonce($_POST['nonce'], 'bugboard_nonce')) {
                wp_send_json_error(array('message' => 'Nonce inválido'));
                return;
            }
            
            // Obtener ID de la tarea
            $task_id = intval($_POST['task_id']);
            if (!$task_id) {
                wp_send_json_error(array('message' => 'ID de tarea inválido'));
                return;
            }
            
            // Verificar que la tarea existe y es del tipo correcto
            $task = get_post($task_id);
            if (!$task || $task->post_type !== 'bug') {
                wp_send_json_error(array('message' => 'Tarea no encontrada'));
                return;
            }
            
            // Eliminar la tarea
            $result = wp_delete_post($task_id, true); // true = eliminar permanentemente
            
            if ($result) {
                error_log('BugBoard: Tarea eliminada correctamente');
                wp_send_json_success(array('message' => 'Tarea eliminada correctamente'));
            } else {
                error_log('BugBoard: Error al eliminar tarea');
                wp_send_json_error(array('message' => 'Error al eliminar la tarea'));
            }
            
        } catch (Exception $e) {
            error_log('BugBoard: Error en delete_task: ' . $e->getMessage());
            wp_send_json_error(array('message' => 'Error interno: ' . $e->getMessage()));
        }
    }
    
    /**
     * Renderizar el tablero Kanban
     */
    public function render_tablero() {
        ?>
        <div class="wrap">
            <h1>Tablero de Tareas</h1>
            <button id="debug-load-tasks" class="button">Debug: Cargar Tareas</button>
            <button id="debug-test-ajax" class="button">Debug: Probar AJAX</button>
            <button id="debug-test-get-task" class="button">Debug: Probar Get Task</button>
            <div id="debug-info" style="margin: 10px 0; padding: 10px; background: #f0f0f0; border-radius: 4px;"></div>
            
            <div class="bugboard-tablero">
                <div class="bugboard-columns">
                    <!-- Columna: Por Hacer -->
                    <div class="bugboard-column" data-status="por-hacer">
                        <div class="bugboard-column-header">
                            <h3>Por Hacer</h3>
                            <span class="task-count">0</span>
                        </div>
                        <div class="bugboard-tasks" id="por-hacer-tasks">
                            <!-- Las tareas se cargarán aquí -->
                        </div>
                        <button class="add-task-btn" data-status="por-hacer">+ Añadir Tarea</button>
                    </div>
                    
                    <!-- Columna: En Progreso -->
                    <div class="bugboard-column" data-status="en-progreso">
                        <div class="bugboard-column-header">
                            <h3>En Progreso</h3>
                            <span class="task-count">0</span>
                        </div>
                        <div class="bugboard-tasks" id="en-progreso-tasks">
                            <!-- Las tareas se cargarán aquí -->
                        </div>
                        <button class="add-task-btn" data-status="en-progreso">+ Añadir Tarea</button>
                    </div>
                    
                    <!-- Columna: En Revisión -->
                    <div class="bugboard-column" data-status="en-revision">
                        <div class="bugboard-column-header">
                            <h3>En Revisión</h3>
                            <span class="task-count">0</span>
                        </div>
                        <div class="bugboard-tasks" id="en-revision-tasks">
                            <!-- Las tareas se cargarán aquí -->
                        </div>
                        <button class="add-task-btn" data-status="en-revision">+ Añadir Tarea</button>
                    </div>
                    
                    <!-- Columna: Completado -->
                    <div class="bugboard-column" data-status="completado">
                        <div class="bugboard-column-header">
                            <h3>Completado</h3>
                            <span class="task-count">0</span>
                        </div>
                        <div class="bugboard-tasks" id="completado-tasks">
                            <!-- Las tareas se cargarán aquí -->
                        </div>
                        <button class="add-task-btn" data-status="completado">+ Añadir Tarea</button>
                    </div>
                </div>
            </div>
            
            <!-- Modal para añadir/editar tareas -->
            <div id="task-modal" class="bugboard-modal">
                <div class="bugboard-modal-content">
                    <span class="bugboard-modal-close">&times;</span>
                    <h2 id="modal-title">Añadir Nueva Tarea</h2>
                    <form id="task-form">
                        <input type="hidden" id="task-id" name="task_id" value="">
                        <input type="hidden" id="task-status" name="task_status" value="">
                        
                        <div class="form-group">
                            <label for="task-title">Título de la Tarea</label>
                            <input type="text" id="task-title" name="task_title" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="task-description">Descripción</label>
                            <textarea id="task-description" name="task_description" rows="4"></textarea>
                        </div>
                        
                        <div class="form-group">
                            <label for="task-priority">Prioridad</label>
                            <select id="task-priority" name="task_priority">
                                <option value="baja">Baja</option>
                                <option value="media">Media</option>
                                <option value="alta">Alta</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="task-assignee">Asignado a</label>
                            <select id="task-assignee" name="task_assignee">
                                <option value="">Sin asignar</option>
                                <?php
                                $users = get_users(array('role__in' => array('administrator', 'editor')));
                                foreach ($users as $user) {
                                    echo '<option value="' . $user->ID . '">' . $user->display_name . '</option>';
                                }
                                ?>
                            </select>
                        </div>
                        
                        <div class="form-actions">
                            <button type="submit" class="button button-primary">Guardar Tarea</button>
                            <button type="button" class="button" onclick="closeTaskModal()">Cancelar</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        
        <script>
        // Variables globales para el tablero
        var bugboardAjaxUrl = '<?php echo admin_url('admin-ajax.php'); ?>';
        var bugboardNonce = '<?php echo wp_create_nonce('bugboard_nonce'); ?>';
        console.log('BugBoard Debug - AJAX URL:', bugboardAjaxUrl);
        console.log('BugBoard Debug - Nonce:', bugboardNonce);
        </script>
        <?php
    }
    
    /**
     * Obtener tareas para el tablero
     */
    public function get_tasks() {
        try {
            // Debug: log de la función
            error_log('BugBoard: get_tasks() llamada');
            
            // Verificar que se recibieron los datos POST
            if (!isset($_POST['nonce'])) {
                error_log('BugBoard: No se recibió nonce');
                wp_send_json_error(array('message' => 'No se recibió nonce'));
                return;
            }
            
            // Verificar nonce
            if (!wp_verify_nonce($_POST['nonce'], 'bugboard_nonce')) {
                error_log('BugBoard: Nonce inválido');
                wp_send_json_error(array('message' => 'Nonce inválido'));
                return;
            }
            
            error_log('BugBoard: Nonce válido, procediendo...');
            
            // Obtener todas las tareas del tipo 'bug'
            $tasks = get_posts(array(
                'post_type' => 'bug',
                'post_status' => 'publish',
                'numberposts' => -1
            ));
            
            error_log('BugBoard: Encontradas ' . count($tasks) . ' tareas');
            
            $formatted_tasks = array();
            foreach ($tasks as $task) {
                $status = get_post_meta($task->ID, '_bugboard_status', true);
                $priority = get_post_meta($task->ID, '_bugboard_priority', true);
                $assignee = get_post_meta($task->ID, '_bugboard_assignee', true);
                
                // Si no tiene estado asignado, asignar 'por-hacer' por defecto
                if (empty($status)) {
                    $status = 'por-hacer';
                    update_post_meta($task->ID, '_bugboard_status', $status);
                }
                
                // Si no tiene prioridad asignada, asignar 'media' por defecto
                if (empty($priority)) {
                    $priority = 'media';
                    update_post_meta($task->ID, '_bugboard_priority', $priority);
                }
                
                $formatted_tasks[] = array(
                    'id' => $task->ID,
                    'title' => $task->post_title,
                    'description' => $task->post_content,
                    'status' => $status,
                    'priority' => $priority,
                    'assignee' => $assignee,
                    'author' => get_the_author_meta('display_name', $task->post_author),
                    'date' => get_the_date('d/m/Y', $task->ID)
                );
            }
            
            error_log('BugBoard: Enviando ' . count($formatted_tasks) . ' tareas formateadas');
            wp_send_json_success($formatted_tasks);
            
        } catch (Exception $e) {
            error_log('BugBoard: Error en get_tasks: ' . $e->getMessage());
            wp_send_json_error(array('message' => 'Error interno: ' . $e->getMessage()));
        }
    }
    
    /**
     * Actualizar estado de una tarea
     */
    public function update_task_status() {
        check_ajax_referer('bugboard_nonce', 'nonce');
        
        $task_id = intval($_POST['task_id']);
        $new_status = sanitize_text_field($_POST['status']);
        
        if (update_post_meta($task_id, '_bugboard_status', $new_status)) {
            wp_send_json_success(array('message' => 'Estado actualizado correctamente'));
        } else {
            wp_send_json_error(array('message' => 'Error al actualizar el estado'));
        }
    }
    
    /**
     * Crear o actualizar una tarea
     */
    public function save_task($task_data) {
        $task_id = isset($task_data['task_id']) ? intval($task_data['task_id']) : 0;
        
        $post_data = array(
            'post_title' => sanitize_text_field($task_data['task_title']),
            'post_content' => wp_kses_post($task_data['task_description']),
            'post_type' => 'bug',
            'post_status' => 'publish'
        );
        
        if ($task_id > 0) {
            $post_data['ID'] = $task_id;
            $post_id = wp_update_post($post_data);
        } else {
            $post_id = wp_insert_post($post_data);
        }
        
        if ($post_id) {
            // Guardar metadatos
            update_post_meta($post_id, '_bugboard_status', sanitize_text_field($task_data['task_status']));
            update_post_meta($post_id, '_bugboard_priority', sanitize_text_field($task_data['task_priority']));
            update_post_meta($post_id, '_bugboard_assignee', sanitize_text_field($task_data['task_assignee']));
            
            return $post_id;
        }
        
        return false;
    }
} 