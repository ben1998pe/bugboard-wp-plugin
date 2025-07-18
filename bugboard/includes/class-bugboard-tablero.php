<?php
/**
 * Clase para manejar el tablero Kanban
 */

if (!defined('ABSPATH')) {
    exit;
}

class BugBoard_Tablero {

    /**
     * Constructor
     */
    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
    }

    /**
     * Añadir menú en el admin
     */
    public function add_admin_menu() {
        // Menú principal BugBoard
        add_menu_page(
            'BugBoard', // Título de la página
            'BugBoard', // Título del menú
            'manage_options', // Capacidad requerida
            'bugboard', // Slug del menú
            array($this, 'bugboard_main_page'), // Función callback
            'dashicons-bug', // Icono
            30 // Posición en el menú
        );
        
        // Submenú Tablero
        add_submenu_page(
            'bugboard', // Parent slug
            'Tablero', // Título de la página
            'Tablero', // Título del submenú
            'manage_options', // Capacidad requerida
            'bugboard-tablero', // Slug del submenú
            array($this, 'bugboard_tablero_page') // Función callback
        );
    }

    /**
     * Página principal de BugBoard
     */
    public function bugboard_main_page() {
        ?>
        <div class="wrap">
            <h1>BugBoard</h1>
            <p>Bienvenido al plugin BugBoard para gestión de tareas.</p>
            
            <div class="bugboard-dashboard">
                <h2>Estadísticas</h2>
                <?php $this->display_stats(); ?>
            </div>
        </div>
        <?php
    }

    /**
     * Mostrar estadísticas
     */
    private function display_stats() {
        $stats = BugBoard_Tasks::get_task_stats();
        $current_user_id = get_current_user_id();
        $user_stats = $this->get_user_task_stats($current_user_id);
        $current_user = wp_get_current_user();
        ?>
        
        <!-- Estadísticas Generales -->
        <div class="bugboard-stats-section">
            <h3>📊 Estadísticas Generales</h3>
            <div class="bugboard-stats">
                <div class="stat-card">
                    <h3>Por Hacer</h3>
                    <span class="stat-number"><?php echo $stats['por-hacer']; ?></span>
                </div>
                <div class="stat-card">
                    <h3>En Progreso</h3>
                    <span class="stat-number"><?php echo $stats['en-progreso']; ?></span>
                </div>
                <div class="stat-card">
                    <h3>En Revisión</h3>
                    <span class="stat-number"><?php echo $stats['en-revision']; ?></span>
                </div>
                <div class="stat-card">
                    <h3>Completado</h3>
                    <span class="stat-number"><?php echo $stats['completado']; ?></span>
                </div>
                <div class="stat-card total">
                    <h3>Total</h3>
                    <span class="stat-number"><?php echo $stats['total']; ?></span>
                </div>
            </div>
        </div>

        <!-- Estadísticas del Usuario Actual -->
        <div class="bugboard-stats-section">
            <h3>👤 Mis Tareas - <?php echo esc_html($current_user->display_name); ?></h3>
            <div class="bugboard-user-stats">
                <div class="user-stat-card">
                    <div class="stat-icon">📋</div>
                    <div class="stat-content">
                        <h4>Total Asignadas</h4>
                        <span class="stat-number"><?php echo $user_stats['total']; ?></span>
                    </div>
                </div>
                <div class="user-stat-card pending">
                    <div class="stat-icon">⏳</div>
                    <div class="stat-content">
                        <h4>Pendientes</h4>
                        <span class="stat-number"><?php echo $user_stats['pending']; ?></span>
                    </div>
                </div>
                <div class="user-stat-card in-progress">
                    <div class="stat-icon">🔄</div>
                    <div class="stat-content">
                        <h4>En Progreso</h4>
                        <span class="stat-number"><?php echo $user_stats['in_progress']; ?></span>
                    </div>
                </div>
                <div class="user-stat-card completed">
                    <div class="stat-icon">✅</div>
                    <div class="stat-content">
                        <h4>Completadas</h4>
                        <span class="stat-number"><?php echo $user_stats['completed']; ?></span>
                    </div>
                </div>
                <div class="user-stat-card urgent">
                    <div class="stat-icon">🚨</div>
                    <div class="stat-content">
                        <h4>Urgentes</h4>
                        <span class="stat-number"><?php echo $user_stats['urgent']; ?></span>
                    </div>
                </div>
            </div>
            
            <!-- Progreso del usuario -->
            <?php if ($user_stats['total'] > 0): ?>
                <div class="user-progress">
                    <h4>Progreso Personal</h4>
                    <div class="progress-bar">
                        <div class="progress-fill" style="width: <?php echo ($user_stats['completed'] / $user_stats['total']) * 100; ?>%"></div>
                    </div>
                    <span class="progress-text"><?php echo round(($user_stats['completed'] / $user_stats['total']) * 100); ?>% completado</span>
                </div>
            <?php endif; ?>
        </div>
        <?php
    }

    /**
     * Página del tablero
     */
    public function bugboard_tablero_page() {
        ?>
        <div class="wrap">
            <h1>Tablero Kanban - BugBoard</h1>
            
            <div id="debug-info" style="background: #f0f0f0; padding: 10px; margin: 10px 0; border-radius: 4px; font-family: monospace; font-size: 12px;">
                <strong>Debug:</strong> Cargando tareas...
            </div>
            
            <div class="bugboard-columns">
                <div class="bugboard-column" data-status="por-hacer">
                    <div class="bugboard-column-header">
                        <h3>Por Hacer</h3>
                        <span class="task-count">0</span>
                    </div>
                    <div class="bugboard-tasks">
                        <!-- Las tareas se cargarán aquí -->
                        <div class="empty-column-message" style="display: none;">
                            <p>No hay tareas pendientes</p>
                            <small>Arrastra tareas aquí o crea una nueva</small>
                        </div>
                    </div>
                    <button class="add-task-btn" onclick="openTaskModal('por-hacer')">+ Añadir Tarea</button>
                </div>
                
                <div class="bugboard-column" data-status="en-progreso">
                    <div class="bugboard-column-header">
                        <h3>En Progreso</h3>
                        <span class="task-count">0</span>
                    </div>
                    <div class="bugboard-tasks">
                        <!-- Las tareas se cargarán aquí -->
                        <div class="empty-column-message" style="display: none;">
                            <p>No hay tareas en progreso</p>
                            <small>Arrastra tareas aquí o crea una nueva</small>
                        </div>
                    </div>
                    <button class="add-task-btn" onclick="openTaskModal('en-progreso')">+ Añadir Tarea</button>
                </div>
                
                <div class="bugboard-column" data-status="en-revision">
                    <div class="bugboard-column-header">
                        <h3>En Revisión</h3>
                        <span class="task-count">0</span>
                    </div>
                    <div class="bugboard-tasks">
                        <!-- Las tareas se cargarán aquí -->
                        <div class="empty-column-message" style="display: none;">
                            <p>No hay tareas en revisión</p>
                            <small>Arrastra tareas aquí o crea una nueva</small>
                        </div>
                    </div>
                    <button class="add-task-btn" onclick="openTaskModal('en-revision')">+ Añadir Tarea</button>
                </div>
                
                <div class="bugboard-column" data-status="completado">
                    <div class="bugboard-column-header">
                        <h3>Completado</h3>
                        <span class="task-count">0</span>
                    </div>
                    <div class="bugboard-tasks">
                        <!-- Las tareas se cargarán aquí -->
                        <div class="empty-column-message" style="display: none;">
                            <p>No hay tareas completadas</p>
                            <small>Arrastra tareas aquí o crea una nueva</small>
                        </div>
                    </div>
                    <button class="add-task-btn" onclick="openTaskModal('completado')">+ Añadir Tarea</button>
                </div>
            </div>
            
            <!-- Modal para crear/editar tareas -->
            <div id="task-modal" class="bugboard-modal" style="display: none;">
                <div class="bugboard-modal-content">
                    <span class="bugboard-modal-close" onclick="closeTaskModal()">&times;</span>
                    <h2 id="modal-title">Añadir Nueva Tarea</h2>
                    
                    <form id="task-form">
                        <input type="hidden" id="task-id" name="task_id" value="">
                        
                        <div class="form-group">
                            <label for="task-title">Título:</label>
                            <input type="text" id="task-title" name="title" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="task-description">Descripción:</label>
                            <textarea id="task-description" name="description" rows="4"></textarea>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="task-status">Estado:</label>
                                <select id="task-status" name="status" required>
                                    <option value="por-hacer">Por Hacer</option>
                                    <option value="en-progreso">En Progreso</option>
                                    <option value="en-revision">En Revisión</option>
                                    <option value="completado">Completado</option>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label for="task-priority">Prioridad:</label>
                                <select id="task-priority" name="priority" required>
                                    <option value="baja">Baja</option>
                                    <option value="media" selected>Media</option>
                                    <option value="alta">Alta</option>
                                    <option value="critica">Crítica</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="task-assignee">Asignado a:</label>
                                <select id="task-assignee" name="assignee">
                                    <option value="">Sin asignar</option>
                                    <?php
                                    $users = BugBoard_Tasks::get_available_users();
                                    foreach ($users as $user) {
                                        echo '<option value="' . $user['id'] . '">' . esc_html($user['name']) . '</option>';
                                    }
                                    ?>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label for="task-due-date">Fecha límite:</label>
                                <input type="date" id="task-due-date" name="due_date">
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="task-estimated-hours">Horas estimadas:</label>
                            <input type="number" id="task-estimated-hours" name="estimated_hours" min="0" step="0.5">
                        </div>
                        
                        <div class="form-actions">
                            <button type="submit" class="button button-primary">Guardar</button>
                            <button type="button" class="button" onclick="closeTaskModal()">Cancelar</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * Obtener estadísticas de tareas del usuario
     */
    private function get_user_task_stats($user_id) {
        global $wpdb;
        
        $table_tasks = $wpdb->prefix . 'bugboard_tasks';
        
        $query = $wpdb->prepare("
            SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN status IN ('por-hacer', 'en-progreso', 'en-revision') THEN 1 ELSE 0 END) as pending,
                SUM(CASE WHEN status = 'en-progreso' THEN 1 ELSE 0 END) as in_progress,
                SUM(CASE WHEN status = 'completado' THEN 1 ELSE 0 END) as completed,
                SUM(CASE WHEN priority = 'alta' THEN 1 ELSE 0 END) as urgent
            FROM $table_tasks
            WHERE assignee_id = %d
        ", $user_id);
        
        $result = $wpdb->get_row($query, ARRAY_A);
        
        return array(
            'total' => (int) $result['total'],
            'pending' => (int) $result['pending'],
            'in_progress' => (int) $result['in_progress'],
            'completed' => (int) $result['completed'],
            'urgent' => (int) $result['urgent']
        );
    }

    /**
     * Cargar scripts y estilos del admin
     */
    public function enqueue_admin_scripts($hook) {
        // Solo cargar en páginas de BugBoard
        if (strpos($hook, 'bugboard') !== false) {
            wp_enqueue_style(
                'bugboard-admin-style',
                BUGBOARD_PLUGIN_URL . 'assets/css/admin.css',
                array(),
                BUGBOARD_VERSION
            );
            
            // Cargar JavaScript solo en la página del tablero
            if (strpos($hook, 'bugboard-tablero') !== false || $hook === 'bugboard_page_bugboard-tablero') {
                wp_enqueue_script(
                    'bugboard-tablero-js',
                    BUGBOARD_PLUGIN_URL . 'assets/js/tablero.js',
                    array('jquery'),
                    BUGBOARD_VERSION,
                    true
                );
                
                // Localizar script con variables necesarias
                wp_localize_script('bugboard-tablero-js', 'bugboardAjax', array(
                    'ajaxurl' => admin_url('admin-ajax.php'),
                    'nonce' => wp_create_nonce('bugboard_nonce')
                ));
            }
        }
    }
} 