<?php
/**
 * Plugin Name: BugBoard
 * Plugin URI: https://github.com/tu-usuario/bugboard
 * Description: Un tablero Kanban para gestión de tareas y bugs en WordPress
 * Version: 1.0.0
 * Author: Tu Nombre
 * Author URI: https://tu-sitio.com
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: bugboard
 * Domain Path: /languages
 * Requires at least: 5.0
 * Tested up to: 6.4
 * Requires PHP: 7.4
 */

// Prevenir acceso directo
if (!defined('ABSPATH')) {
    exit;
}

// Definir constantes del plugin
define('BUGBOARD_VERSION', '1.0.0');
define('BUGBOARD_PLUGIN_URL', plugin_dir_url(__FILE__));
define('BUGBOARD_PLUGIN_PATH', plugin_dir_path(__FILE__));

/**
 * Clase principal del plugin BugBoard
 */
class BugBoard {

    /**
     * Constructor
     */
    public function __construct() {
        $this->init_hooks();
        $this->load_dependencies();
    }

    /**
     * Inicializar hooks
     */
    private function init_hooks() {
        // Hook de activación
        register_activation_hook(__FILE__, array('BugBoard_Activator', 'activate'));
        
        // Hook de desactivación
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
        
        // Hook de desinstalación
        register_uninstall_hook(__FILE__, array('BugBoard_Activator', 'uninstall'));
        
        // Inicializar plugin
        add_action('plugins_loaded', array($this, 'init'));
    }

    /**
     * Cargar dependencias
     */
    private function load_dependencies() {
        // Incluir archivos necesarios
        require_once BUGBOARD_PLUGIN_PATH . 'includes/class-bugboard-activator.php';
        require_once BUGBOARD_PLUGIN_PATH . 'includes/class-bugboard-tasks.php';
        require_once BUGBOARD_PLUGIN_PATH . 'includes/class-bugboard-ajax.php';
        require_once BUGBOARD_PLUGIN_PATH . 'includes/class-bugboard-admin.php';
        require_once BUGBOARD_PLUGIN_PATH . 'includes/class-bugboard-tablero.php';
        require_once BUGBOARD_PLUGIN_PATH . 'includes/class-bugboard-notifications.php';
    }

    /**
     * Inicializar el plugin
     */
    public function init() {
        // Inicializar clases
        new BugBoard_Admin();
        new BugBoard_Ajax();
        new BugBoard_Tablero();
        new BugBoard_Notifications();
        
        // Cargar traducciones
        load_plugin_textdomain('bugboard', false, dirname(plugin_basename(__FILE__)) . '/languages');
    }

    /**
     * Desactivar el plugin
     */
    public function deactivate() {
        // Limpiar opciones temporales si es necesario
        // delete_option('bugboard_temp_option');
    }
}

// Inicializar el plugin
new BugBoard(); 