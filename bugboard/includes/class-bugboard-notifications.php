<?php
/**
 * Clase para manejar notificaciones y widgets del BugBoard
 */
class BugBoard_Notifications {

    /**
     * Constructor
     */
    public function __construct() {
        add_action('admin_notices', array($this, 'show_pending_tasks_notice'));
        add_action('wp_dashboard_setup', array($this, 'add_dashboard_widget'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_notification_scripts'));
        add_action('admin_menu', array($this, 'add_menu_notifications'));
    }

    /**
     * Mostrar notificación de tareas pendientes
     */
    public function show_pending_tasks_notice() {
        // Solo mostrar a usuarios que pueden gestionar tareas
        if (!current_user_can('manage_options')) {
            return;
        }

        $user_id = get_current_user_id();
        $pending_tasks = $this->get_user_pending_tasks($user_id);
        
        if (empty($pending_tasks)) {
            return;
        }

        // Verificar si ya se mostró la notificación hoy
        $today = date('Y-m-d');
        $last_notice = get_user_meta($user_id, 'bugboard_last_notice_date', true);
        
        if ($last_notice === $today) {
            return;
        }

        // Guardar que se mostró la notificación hoy
        update_user_meta($user_id, 'bugboard_last_notice_date', $today);

        $task_count = count($pending_tasks);
        
        // Crear mensaje más detallado
        $urgent_tasks = array_filter($pending_tasks, function($task) {
            return $task['priority'] === 'alta';
        });
        
        $message = sprintf(
            'Tienes <strong>%d tarea%s pendiente%s</strong> en BugBoard',
            $task_count,
            $task_count === 1 ? '' : 's',
            $task_count === 1 ? '' : 's'
        );
        
        if (!empty($urgent_tasks)) {
            $message .= sprintf(
                ' (<strong>%d urgente%s</strong>)',
                count($urgent_tasks),
                count($urgent_tasks) === 1 ? '' : 's'
            );
        }
        
        $message .= sprintf(' <a href="%s">Ver tablero</a>', admin_url('admin.php?page=bugboard-tablero'));

        echo '<div class="notice notice-info is-dismissible bugboard-notice">';
        echo '<p>' . $message . '</p>';
        echo '</div>';
    }

    /**
     * Agregar widget al dashboard
     */
    public function add_dashboard_widget() {
        // Solo mostrar a usuarios que pueden gestionar tareas
        if (!current_user_can('manage_options')) {
            return;
        }

        wp_add_dashboard_widget(
            'bugboard_dashboard_widget',
            'BugBoard - Mis Tareas',
            array($this, 'render_dashboard_widget')
        );
    }

    /**
     * Renderizar widget del dashboard
     */
    public function render_dashboard_widget() {
        $user_id = get_current_user_id();
        $user_tasks = $this->get_user_tasks($user_id);
        $stats = $this->get_user_task_stats($user_id);

        ?>
        <div class="bugboard-dashboard-widget">
            <!-- Estadísticas rápidas -->
            <div class="bugboard-stats-overview">
                <div class="stat-item">
                    <span class="stat-number"><?php echo $stats['total']; ?></span>
                    <span class="stat-label">Total</span>
                </div>
                <div class="stat-item">
                    <span class="stat-number"><?php echo $stats['pending']; ?></span>
                    <span class="stat-label">Pendientes</span>
                </div>
                <div class="stat-item">
                    <span class="stat-number"><?php echo $stats['in_progress']; ?></span>
                    <span class="stat-label">En Progreso</span>
                </div>
                <div class="stat-item">
                    <span class="stat-number"><?php echo $stats['completed']; ?></span>
                    <span class="stat-label">Completadas</span>
                </div>
            </div>

            <!-- Tareas recientes -->
            <?php if (!empty($user_tasks)): ?>
                <div class="bugboard-recent-tasks">
                    <h4>Tareas Recientes</h4>
                    <ul class="bugboard-task-list">
                        <?php foreach (array_slice($user_tasks, 0, 5) as $task): ?>
                            <li class="bugboard-task-item priority-<?php echo $task['priority']; ?>">
                                <div class="task-info">
                                    <strong><?php echo esc_html($task['title']); ?></strong>
                                    <span class="task-status"><?php echo $this->get_status_label($task['status']); ?></span>
                                </div>
                                <div class="task-meta">
                                    <span class="task-priority"><?php echo strtoupper($task['priority']); ?></span>
                                    <span class="task-date"><?php echo date('d/m/Y', strtotime($task['created_at'])); ?></span>
                                </div>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php else: ?>
                <p>No tienes tareas asignadas.</p>
            <?php endif; ?>

            <!-- Enlaces rápidos -->
            <div class="bugboard-quick-links">
                <a href="<?php echo admin_url('admin.php?page=bugboard-tablero'); ?>" class="button button-primary">
                    Ver Tablero
                </a>
                <a href="<?php echo admin_url('admin.php?page=bugboard-tablero'); ?>" class="button" onclick="openTaskModal('por-hacer'); return false;">
                    Nueva Tarea
                </a>
            </div>
        </div>
        <?php
    }

    /**
     * Obtener tareas pendientes del usuario
     */
    private function get_user_pending_tasks($user_id) {
        global $wpdb;
        
        $table_tasks = $wpdb->prefix . 'bugboard_tasks';
        
        $query = $wpdb->prepare("
            SELECT id, title, status, priority, created_at
            FROM $table_tasks
            WHERE assignee_id = %d 
            AND status IN ('por-hacer', 'en-progreso', 'en-revision')
            ORDER BY created_at DESC
            LIMIT 10
        ", $user_id);
        
        return $wpdb->get_results($query, ARRAY_A);
    }

    /**
     * Obtener todas las tareas del usuario
     */
    private function get_user_tasks($user_id) {
        global $wpdb;
        
        $table_tasks = $wpdb->prefix . 'bugboard_tasks';
        
        $query = $wpdb->prepare("
            SELECT id, title, status, priority, created_at
            FROM $table_tasks
            WHERE assignee_id = %d 
            ORDER BY created_at DESC
            LIMIT 10
        ", $user_id);
        
        return $wpdb->get_results($query, ARRAY_A);
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
                SUM(CASE WHEN status = 'completado' THEN 1 ELSE 0 END) as completed
            FROM $table_tasks
            WHERE assignee_id = %d
        ", $user_id);
        
        $result = $wpdb->get_row($query, ARRAY_A);
        
        return array(
            'total' => (int) $result['total'],
            'pending' => (int) $result['pending'],
            'in_progress' => (int) $result['in_progress'],
            'completed' => (int) $result['completed']
        );
    }

    /**
     * Obtener etiqueta de estado
     */
    private function get_status_label($status) {
        $labels = array(
            'por-hacer' => 'Por Hacer',
            'en-progreso' => 'En Progreso',
            'en-revision' => 'En Revisión',
            'completado' => 'Completado'
        );
        
        return isset($labels[$status]) ? $labels[$status] : $status;
    }

    /**
     * Agregar notificaciones al menú
     */
    public function add_menu_notifications() {
        global $menu;
        
        // Solo mostrar a usuarios que pueden gestionar tareas
        if (!current_user_can('manage_options')) {
            return;
        }
        
        $user_id = get_current_user_id();
        $pending_tasks = $this->get_user_pending_tasks($user_id);
        $task_count = count($pending_tasks);
        
        if ($task_count > 0) {
            // Buscar el menú BugBoard y agregar el contador
            foreach ($menu as $key => $item) {
                if (isset($item[2]) && $item[2] === 'bugboard') {
                    $menu[$key][0] .= " <span class='update-plugins count-{$task_count}'><span class='plugin-count'>{$task_count}</span></span>";
                    break;
                }
            }
        }
    }

    /**
     * Cargar scripts para notificaciones
     */
    public function enqueue_notification_scripts($hook) {
        // Solo cargar en dashboard y páginas de BugBoard
        if ($hook === 'index.php' || strpos($hook, 'bugboard') !== false) {
            wp_enqueue_style(
                'bugboard-notifications',
                BUGBOARD_PLUGIN_URL . 'assets/css/notifications.css',
                array(),
                BUGBOARD_VERSION
            );
        }
    }
} 