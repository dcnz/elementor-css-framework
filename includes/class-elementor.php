<?php
/**
 * Elementor integration for the Elementor CSS Framework plugin
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

class ECF_Elementor {
    
    /**
     * Constructor
     */
    public function __construct() {
        // Empty constructor
    }
    
    /**
     * Initialize Elementor integration
     */
    public function init() {
        // Check if Elementor is loaded
        if (did_action('elementor/loaded')) {
            // Add section controls
            add_action('elementor/init', array($this, 'init_elementor_hooks'));
            
            // Enqueue editor scripts
            add_action('elementor/editor/after_enqueue_scripts', array($this, 'enqueue_editor_scripts'));
        }
    }
    
    /**
     * Initialize Elementor hooks after Elementor is fully loaded
     */
    public function init_elementor_hooks() {
        // For Sections
        add_action('elementor/element/section/section_advanced/after_section_end', array($this, 'add_section_classes_tab'), 10, 2);
        
        // For Containers (Elementor 3.0+)
        add_action('elementor/element/container/section_layout/after_section_end', array($this, 'add_section_classes_tab'), 10, 2);
        
        // Render hooks for frontend
        add_action('elementor/frontend/section/before_render', array($this, 'before_render_element'), 10, 1);
        add_action('elementor/frontend/container/before_render', array($this, 'before_render_element'), 10, 1);
    }
    
    /**
     * Enqueue scripts for Elementor editor
     */
    public function enqueue_editor_scripts() {
        // Enqueue the framework styles in the editor
        ecf()->core->enqueue_styles();
        
        // Create simple CSS file for editor if it doesn't exist
        $this->ensure_editor_css_exists();
        
        // Editor CSS
        wp_enqueue_style(
            'elementor-css-framework-editor',
            ECF_PLUGIN_URL . 'assets/css/elementor-editor.css',
            [],
            filemtime(ECF_PLUGIN_PATH . 'assets/css/elementor-editor.css')
        );
        
        // Add custom script for editor integration
        wp_enqueue_script(
            'elementor-css-framework-editor',
            ECF_PLUGIN_URL . 'assets/js/elementor-editor.js',
            array('jquery'),
            filemtime(ECF_PLUGIN_PATH . 'assets/js/elementor-editor.js'),
            true
        );
        
        // Localize script with class options
        wp_localize_script('elementor-css-framework-editor', 'ecfConfig', array(
            'classOptions' => ecf()->core->section_classes
        ));
    }
    
    /**
     * Make sure editor CSS file exists
     */
    private function ensure_editor_css_exists() {
        $editor_css_file = ECF_PLUGIN_PATH . 'assets/css/elementor-editor.css';
        
        if (!file_exists($editor_css_file)) {
            $css_content = "/**\n * Elementor CSS Framework - Editor Styles\n */\n\n";
            $css_content .= "/* Force Elementor editor to recognize our framework classes */\n";
            $css_content .= ".elementor-editor-active [class*='section-'] {\n";
            $css_content .= "    position: relative !important;\n";
            $css_content .= "}\n";
            
            file_put_contents($editor_css_file, $css_content);
        }
    }
    
    /**
     * Add a new CSS Framework tab with section class selector
     */
    public function add_section_classes_tab($element, $section_id) {
        // Make sure Elementor is fully loaded
        if (!class_exists('\Elementor\Controls_Manager')) {
            return;
        }
        
        // Add new section
        $element->start_controls_section(
            'ecf_section',
            [
                'tab' => \Elementor\Controls_Manager::TAB_ADVANCED,
                'label' => __('CSS Framework', 'elementor-css-framework'),
            ]
        );
        
        // Add multi-select control
        $element->add_control(
            'ecf_section_classes',
            [
                'label' => __('Section Classes', 'elementor-css-framework'),
                'type' => \Elementor\Controls_Manager::SELECT2,
                'multiple' => true,
                'options' => ecf()->core->section_classes,
                'default' => [],
                'label_block' => true,
                'separator' => 'none',
            ]
        );
        
        $element->end_controls_section();
    }
    
    /**
     * Add classes to element before render (frontend only)
     * This is a safer approach that avoids array conversion issues
     */
    public function before_render_element($element) {
        $settings = $element->get_settings_for_display();
        
        if (!empty($settings['ecf_section_classes']) && is_array($settings['ecf_section_classes'])) {
            // Filter out any non-string values to prevent warnings
            $clean_classes = array_filter($settings['ecf_section_classes'], 'is_string');
            
            // Apply each class individually to avoid array issues
            foreach ($clean_classes as $class) {
                if (!empty($class)) {
                    $element->add_render_attribute('_wrapper', 'class', $class);
                }
            }
        }
    }
}