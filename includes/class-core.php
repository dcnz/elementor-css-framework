<?php
/**
 * Core functionality for the Elementor CSS Framework plugin
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

class ECF_Core {
    
    // CSS class options for the multi-select
    public $section_classes = array(
        'section-xxl' => 'XXL Padding',
        'section-xl' => 'XL Padding',
        'section-l' => 'Large Padding',
        'section-m' => 'Medium Padding',
        'section-s' => 'Small Padding',
        'section-xs' => 'XS Padding',
        'section-xxs' => 'XXS Padding',
        'section-header' => 'Header Padding',
        'section-hero' => 'Hero Height',
        'section-full' => 'Full Width',
        'section-narrow' => 'Narrow Width',
        'section-narrow-xs' => 'Extra Narrow Width',
        'section-offset' => 'Offset Header'
    );
    
    // Default CSS variables
    public $default_variables = array(
        '--fluid-side-padding-min' => '1.25rem',
        '--fluid-side-padding-max' => '5rem',
        '--section-xxl-padding-min' => '9.375rem',
        '--section-xxl-padding-max' => '10rem',
        '--section-xl-padding-min' => '6.875rem',
        '--section-xl-padding-max' => '7.5rem',
        '--section-l-padding-min' => '5.625rem',
        '--section-l-padding-max' => '6.25rem',
        '--section-m-padding-min' => '5rem',
        '--section-m-padding-max' => '5rem',
        '--section-s-padding-min' => '3.75rem',
        '--section-s-padding-max' => '3.75rem',
        '--section-xs-padding-min' => '2.5rem',
        '--section-xs-padding-max' => '2.5rem',
        '--section-xxs-padding-min' => '1.5rem',
        '--section-xxs-padding-max' => '1.5rem',
        '--section-header-padding-min' => '1.25rem',
        '--section-header-padding-max' => '1.25rem',
        '--section-hero-height' => '100vh',
        '--section-offset-header' => '80px',
        '--section-narrow' => '62.5rem',
        '--section-narrow-xs' => '45rem'
    );
    
    // Variable descriptions/comments
    public $variable_descriptions = array(
        '--fluid-side-padding-min' => 'Left/Right Min Padding (20px)',
        '--fluid-side-padding-max' => 'Left/Right Max Padding (80px)',
        '--section-xxl-padding-min' => 'XXL Top/Bottom Min Padding (150px)',
        '--section-xxl-padding-max' => 'XXL Top/Bottom Max Padding (160px)',
        '--section-xl-padding-min' => 'XL Top/Bottom Min Padding (110px)',
        '--section-xl-padding-max' => 'XL Top/Bottom Max Padding (120px)',
        '--section-l-padding-min' => 'Large Top/Bottom Min Padding (90px)',
        '--section-l-padding-max' => 'Large Top/Bottom Max Padding (100px)',
        '--section-m-padding-min' => 'Medium Top/Bottom Min Padding (80px)',
        '--section-m-padding-max' => 'Medium Top/Bottom Max Padding (80px)',
        '--section-s-padding-min' => 'Small Top/Bottom Min Padding (60px)',
        '--section-s-padding-max' => 'Small Top/Bottom Max Padding (60px)',
        '--section-xs-padding-min' => 'XS Top/Bottom Min Padding (40px)',
        '--section-xs-padding-max' => 'XS Top/Bottom Max Padding (40px)',
        '--section-xxs-padding-min' => 'XXS Top/Bottom Min Padding (24px)',
        '--section-xxs-padding-max' => 'XXS Top/Bottom Max Padding (24px)',
        '--section-header-padding-min' => 'Header Top/Bottom Min Padding (20px)',
        '--section-header-padding-max' => 'Header Top/Bottom Max Padding (20px)',
        '--section-hero-height' => 'Hero Section Height (100% viewport height)',
        '--section-offset-header' => 'Offset Padding for Overlay Headers (80px)',
        '--section-narrow' => 'Narrow Section Width (1000px)',
        '--section-narrow-xs' => 'Extra Narrow Section Width (720px)'
    );
    
    /**
     * Constructor
     */
    public function __construct() {
        // Check for Elementor on admin_init
        add_action('admin_init', array($this, 'check_elementor'));
    }
    
    /**
     * Initialize core functionality
     */
    public function init() {
        // Load the CSS files
        add_action('wp_enqueue_scripts', array($this, 'enqueue_styles'));
        
        // Add AJAX handler for getting section classes
        add_action('wp_ajax_ecf_get_section_classes', array($this, 'ajax_get_section_classes'));
    }
    
    /**
     * AJAX handler for getting section classes
     */
    public function ajax_get_section_classes() {
        wp_send_json_success($this->section_classes);
    }
    
    /**
     * Check if Elementor is active and show notice if needed
     */
    public function check_elementor() {
        // Only check once per session
        if (!get_transient('ecf_elementor_check')) {
            // Set transient to prevent repeated checks
            set_transient('ecf_elementor_check', true, DAY_IN_SECONDS);
            
            // If Elementor is active, don't show notice
            if (did_action('elementor/loaded')) {
                // Remove any existing notice option
                delete_option('ecf_show_elementor_notice');
            } else {
                // Set option to show notice
                update_option('ecf_show_elementor_notice', true);
                add_action('admin_notices', array($this, 'admin_notice_missing_elementor'));
            }
        } else if (get_option('ecf_show_elementor_notice')) {
            // If we previously determined Elementor is missing, show notice
            add_action('admin_notices', array($this, 'admin_notice_missing_elementor'));
        }
    }
    
    /**
     * Load CSS files
     */
    public function enqueue_styles() {
        // Enqueue variables CSS first
        $variables_file = ECF_CSS_PATH . 'variables.css';
        if (file_exists($variables_file)) {
            wp_enqueue_style(
                'elementor-css-framework-variables',
                ECF_PLUGIN_URL . 'assets/css/variables.css',
                array(),
                filemtime($variables_file)
            );
        }
        
        // Then enqueue the main CSS
        $main_css_file = ECF_CSS_PATH . 'elementor-section-classes.css';
        if (file_exists($main_css_file)) {
            wp_enqueue_style(
                'elementor-css-framework',
                ECF_PLUGIN_URL . 'assets/css/elementor-section-classes.css',
                array('elementor-css-framework-variables'),
                filemtime($main_css_file)
            );
        }
    }
    
    /**
     * Add admin notice if Elementor is not active
     */
    public function admin_notice_missing_elementor() {
        if (isset($_GET['activate'])) {
            unset($_GET['activate']);
        }
        
        // Add a dismiss button that will prevent the notice from showing again
        $dismiss_url = add_query_arg(array('ecf-dismiss-elementor-notice' => 'true'), admin_url());
        
        $message = sprintf(
            esc_html__('"%1$s" requires "%2$s" to be installed and activated for some features. The basic functionality will still work without Elementor.', 'elementor-css-framework'),
            '<strong>Elementor CSS Framework</strong>',
            '<strong>Elementor</strong>'
        );
        
        $dismiss_button = sprintf(
            ' <a href="%1$s" class="button button-secondary">%2$s</a>',
            esc_url($dismiss_url),
            esc_html__('Dismiss', 'elementor-css-framework')
        );
        
        printf('<div class="notice notice-warning"><p>%1$s%2$s</p></div>', $message, $dismiss_button);
        
        // Handle notice dismissal
        if (isset($_GET['ecf-dismiss-elementor-notice']) && $_GET['ecf-dismiss-elementor-notice'] === 'true') {
            delete_option('ecf_show_elementor_notice');
            wp_redirect(remove_query_arg('ecf-dismiss-elementor-notice'));
            exit;
        }
    }
}