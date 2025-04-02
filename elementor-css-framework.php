<?php
/**
 * Plugin Name: Elementor CSS Framework
 * Description: Add custom CSS framework classes to Elementor sections and edit CSS variables
 * Version: 1.0.0
 * Author: Your Name
 * Text Domain: elementor-css-framework
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('ECF_PLUGIN_PATH', plugin_dir_path(__FILE__));
define('ECF_PLUGIN_URL', plugin_dir_url(__FILE__));
define('ECF_INCLUDES_PATH', ECF_PLUGIN_PATH . 'includes/');
define('ECF_ASSETS_PATH', ECF_PLUGIN_PATH . 'assets/');
define('ECF_CSS_PATH', ECF_ASSETS_PATH . 'css/');

// Load required files
require_once ECF_INCLUDES_PATH . 'class-core.php';
require_once ECF_INCLUDES_PATH . 'class-admin.php';
require_once ECF_INCLUDES_PATH . 'class-css-manager.php';
require_once ECF_INCLUDES_PATH . 'class-elementor.php';

/**
 * Main plugin class
 */
class Elementor_CSS_Framework {
    
    // Singleton instance
    private static $instance = null;
    
    // Class instances
    public $core;
    public $admin;
    public $css_manager;
    public $elementor;
    
    /**
     * Get singleton instance
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Constructor
     */
    private function __construct() {
        // Create instances of component classes
        $this->core = new ECF_Core();
        $this->admin = new ECF_Admin();
        $this->css_manager = new ECF_CSS_Manager();
        $this->elementor = new ECF_Elementor();
        
        // Register activation hook
        register_activation_hook(__FILE__, array($this, 'activate'));
        
        // Initialize the plugin
        add_action('plugins_loaded', array($this, 'init'));
    }
    
    /**
     * Plugin activation hook
     */
    public function activate() {
        // Create necessary directories
        if (!file_exists(ECF_INCLUDES_PATH)) {
            wp_mkdir_p(ECF_INCLUDES_PATH);
        }
        
        if (!file_exists(ECF_ASSETS_PATH)) {
            wp_mkdir_p(ECF_ASSETS_PATH);
        }
        
        if (!file_exists(ECF_CSS_PATH)) {
            wp_mkdir_p(ECF_CSS_PATH);
        }
        
        // Create JS directory
        $js_dir = ECF_ASSETS_PATH . 'js/';
        if (!file_exists($js_dir)) {
            wp_mkdir_p($js_dir);
        }
        
        // Initialize CSS files
        $this->css_manager->maybe_create_variables_file();
        $this->css_manager->maybe_create_main_css_file();
        
        // Create basic admin js/css
        $this->admin->create_admin_assets();
    }
    
    /**
     * Initialize the plugin
     */
    public function init() {
        // Initialize all components
        $this->core->init();
        $this->admin->init();
        $this->css_manager->init();
        $this->elementor->init();
    }
}

// Initialize the plugin
function ecf() {
    return Elementor_CSS_Framework::get_instance();
}

// Start the plugin
ecf();