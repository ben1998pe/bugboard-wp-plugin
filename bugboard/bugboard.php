<?php
/**
 * Plugin Name: BugBoard
 * Plugin URI: 
 * Description: Plugin para gestión de tareas y bugs
 * Version: 1.0.0
 * Author: 
 * License: GPL v2 or later
 * Text Domain: bugboard
 */

// Prevenir acceso directo
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Clase principal del plugin BugBoard
 */
class BugBoard {
    
    /**
     * Constructor de la clase
     */
    public function __construct() {
        // Hooks para inicializar el plugin
        add_action('init', array($this, 'init'));
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        
        // Hooks AJAX
        add_action('wp_ajax_bugboard_save_task', array($this, 'save_task_ajax'));
        
        // Registrar hooks AJAX del tablero
        add_action('init', array($this, 'register_tablero_ajax_hooks'));
    }
    
    /**
     * Inicialización del plugin
     */
    public function init() {
        // Registrar el Custom Post Type
        $this->register_bug_post_type();
    }
    
    /**
     * Registrar hooks AJAX del tablero
     */
    public function register_tablero_ajax_hooks() {
        // Cargar la clase del tablero
        require_once plugin_dir_path(__FILE__) . 'includes/class-bugboard-tablero.php';
        
        // Crear instancia y registrar hooks
        $tablero = new BugBoard_Tablero();
        $tablero->register_ajax_hooks();
        
        error_log('BugBoard: Hooks AJAX del tablero registrados');
    }
    
    /**
     * Registrar el Custom Post Type 'bug'
     */
    public function register_bug_post_type() {
        $labels = array(
            'name'                  => 'Tareas',
            'singular_name'         => 'Tarea',
            'menu_name'             => 'Tareas',
            'name_admin_bar'        => 'Tarea',
            'add_new'               => 'Añadir Nueva',
            'add_new_item'          => 'Añadir Nueva Tarea',
            'new_item'              => 'Nueva Tarea',
            'edit_item'             => 'Editar Tarea',
            'view_item'             => 'Ver Tarea',
            'all_items'             => 'Todas las Tareas',
            'search_items'          => 'Buscar Tareas',
            'parent_item_colon'     => 'Tareas Padre:',
            'not_found'             => 'No se encontraron tareas.',
            'not_found_in_trash'    => 'No se encontraron tareas en la papelera.',
            'featured_image'        => 'Imagen Destacada de la Tarea',
            'set_featured_image'    => 'Establecer imagen destacada',
            'remove_featured_image' => 'Eliminar imagen destacada',
            'use_featured_image'    => 'Usar como imagen destacada',
            'archives'              => 'Archivo de Tareas',
            'insert_into_item'      => 'Insertar en la tarea',
            'uploaded_to_this_item' => 'Subido a esta tarea',
            'filter_items_list'     => 'Filtrar lista de tareas',
            'items_list_navigation' => 'Navegación de lista de tareas',
            'items_list'            => 'Lista de tareas',
        );
        
        $args = array(
            'labels'              => $labels,
            'public'              => false, // Solo visible en admin
            'publicly_queryable'  => false,
            'show_ui'             => true,
            'show_in_menu'        => true,
            'query_var'           => true,
            'rewrite'             => array('slug' => 'bug'),
            'capability_type'     => 'post',
            'has_archive'         => false,
            'hierarchical'        => false,
            'menu_position'       => null,
            'menu_icon'           => 'dashicons-bug',
            'supports'            => array('title', 'editor', 'author'),
            'show_in_rest'        => false, // Sin opción de exportación
        );
        
        register_post_type('bug', $args);
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
        </div>
        <?php
    }
    
    /**
     * Página del tablero
     */
    public function bugboard_tablero_page() {
        // Cargar la clase del tablero
        require_once plugin_dir_path(__FILE__) . 'includes/class-bugboard-tablero.php';
        
        // Inicializar el tablero
        $tablero = new BugBoard_Tablero();
        
        // Renderizar el tablero
        $tablero->render_tablero();
    }
    
    /**
     * Cargar scripts y estilos del admin
     */
    public function enqueue_admin_scripts($hook) {
        // Debug: mostrar el hook actual
        error_log('BugBoard Hook: ' . $hook);
        
        // Solo cargar en páginas de BugBoard
        if (strpos($hook, 'bugboard') !== false) {
            wp_enqueue_style(
                'bugboard-admin-style',
                plugin_dir_url(__FILE__) . 'assets/css/admin.css',
                array(),
                '1.0.0'
            );
            
            // Cargar JavaScript solo en la página del tablero
            if (strpos($hook, 'bugboard-tablero') !== false || $hook === 'bugboard_page_bugboard-tablero') {
                wp_enqueue_script(
                    'bugboard-tablero-js',
                    plugin_dir_url(__FILE__) . 'assets/js/tablero.js',
                    array('jquery'),
                    '1.0.0',
                    true
                );
                
                // Debug: confirmar que se está cargando el script
                error_log('BugBoard: Cargando script del tablero');
            }
        }
    }
    
    /**
     * Guardar tarea via AJAX
     */
    public function save_task_ajax() {
        check_ajax_referer('bugboard_nonce', 'nonce');
        
        $task_data = array(
            'task_id' => isset($_POST['task_id']) ? intval($_POST['task_id']) : 0,
            'task_title' => sanitize_text_field($_POST['task_title']),
            'task_description' => wp_kses_post($_POST['task_description']),
            'task_status' => sanitize_text_field($_POST['task_status']),
            'task_priority' => sanitize_text_field($_POST['task_priority']),
            'task_assignee' => sanitize_text_field($_POST['task_assignee'])
        );
        
        // Cargar la clase del tablero
        require_once plugin_dir_path(__FILE__) . 'includes/class-bugboard-tablero.php';
        $tablero = new BugBoard_Tablero();
        
        $result = $tablero->save_task($task_data);
        
        if ($result) {
            wp_send_json_success(array(
                'message' => 'Tarea guardada correctamente',
                'task_id' => $result
            ));
        } else {
            wp_send_json_error(array('message' => 'Error al guardar la tarea'));
        }
    }
}

/**
 * Inicializar el plugin
 */
function bugboard_init() {
    new BugBoard();
}

// Hook para inicializar el plugin
add_action('plugins_loaded', 'bugboard_init');

/**
 * Activación del plugin
 */
function bugboard_activate() {
    // Registrar el CPT al activar
    $bugboard = new BugBoard();
    $bugboard->register_bug_post_type();
    
    // Flush rewrite rules
    flush_rewrite_rules();
}

/**
 * Desactivación del plugin
 */
function bugboard_deactivate() {
    // Flush rewrite rules
    flush_rewrite_rules();
}

// Hooks de activación y desactivación
register_activation_hook(__FILE__, 'bugboard_activate');
register_deactivation_hook(__FILE__, 'bugboard_deactivate'); 