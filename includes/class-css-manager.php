<?php
/**
 * CSS management functionality for the Elementor CSS Framework plugin
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

class ECF_CSS_Manager {
    
    /**
     * Constructor
     */
    public function __construct() {
        // Empty constructor
    }
    
    /**
     * Initialize CSS manager functionality
     */
    public function init() {
        // Nothing to initialize for now
    }
    
    /**
     * Get CSS variables from file
     */
    public function get_variables_from_file($file_path) {
        $variables = array();
        
        if (!file_exists($file_path)) {
            return $variables;
        }
        
        $css_content = file_get_contents($file_path);
        
        // Match :root { ... } block
        if (preg_match('/:root\s*{(.*?)}/s', $css_content, $matches)) {
            $root_content = $matches[1];
            
            // Match all --variable: value; declarations
            preg_match_all('/--([a-zA-Z0-9-_]+)\s*:\s*([^;]*);/', $root_content, $var_matches, PREG_SET_ORDER);
            
            foreach ($var_matches as $match) {
                $name = '--' . $match[1];
                $value = trim($match[2]);
                $variables[$name] = $value;
            }
        }
        
        return $variables;
    }
    
    /**
     * Create the variables CSS file if it doesn't exist
     */
    public function maybe_create_variables_file() {
        $variables_file = ECF_CSS_PATH . 'variables.css';
        
        // Create directory if it doesn't exist
        if (!file_exists(ECF_CSS_PATH)) {
            wp_mkdir_p(ECF_CSS_PATH);
        }
        
        if (!file_exists($variables_file)) {
            $content = "/* CSS Framework Variables */\n\n:root {\n";
            
            foreach (ecf()->core->default_variables as $name => $value) {
                $description = isset(ecf()->core->variable_descriptions[$name]) ? ecf()->core->variable_descriptions[$name] : '';
                $content .= "    " . $name . ": " . $value . "; /* " . $description . " */\n";
            }
            
            $content .= "}\n";
            
            $result = file_put_contents($variables_file, $content);
            
            // If the file couldn't be created, add an admin notice
            if ($result === false) {
                add_action('admin_notices', function() use ($variables_file) {
                    echo '<div class="notice notice-error"><p>' . 
                         sprintf(__('Could not create CSS variables file at: %s. Please check directory permissions.', 'elementor-css-framework'), $variables_file) . 
                         '</p></div>';
                });
            }
        }
    }
    
    /**
     * Create the main CSS file if it doesn't exist
     */
    public function maybe_create_main_css_file() {
        $main_css_file = ECF_CSS_PATH . 'elementor-section-classes.css';
        
        // Create directory if it doesn't exist
        if (!file_exists(ECF_CSS_PATH)) {
            wp_mkdir_p(ECF_CSS_PATH);
        }
        
        if (!file_exists($main_css_file)) {
            // Copy from the file uploaded by the user or create a default one
            $uploaded_css = ECF_PLUGIN_PATH . 'elementor-section-classes.css';
            
            if (file_exists($uploaded_css)) {
                $result = copy($uploaded_css, $main_css_file);
                if (!$result) {
                    // If copy fails, create a new file
                    $this->create_default_css_file($main_css_file);
                }
            } else {
                $this->create_default_css_file($main_css_file);
            }
        }
    }
    
    /**
     * Create a default CSS file with basic section classes
     */
    public function create_default_css_file($file_path) {
        // Create a basic CSS file with the section classes
        $content = "/* CSS Framework Classes */\n\n";
        $content .= "/* Import variables */\n";
        $content .= "@import url('variables.css');\n\n";
        
        // Add classes for each section type
        $content .= "/* Section/Container Padding - Fluid Variants */\n";
        $content .= ".section-xxl {\n";
        $content .= "    padding-top: clamp(var(--section-xxl-padding-min), 1.087vw + 9.13rem, var(--section-xxl-padding-max));\n";
        $content .= "    padding-bottom: clamp(var(--section-xxl-padding-min), 1.087vw + 9.13rem, var(--section-xxl-padding-max));\n";
        $content .= "    padding-left: clamp(var(--fluid-side-padding-min), 6.522vw + -0.217rem, var(--fluid-side-padding-max))!important;\n";
        $content .= "    padding-right: clamp(var(--fluid-side-padding-min), 6.522vw + -0.217rem, var(--fluid-side-padding-max))!important;\n";
        $content .= "}\n\n";
        
        $content .= ".section-xl {\n";
        $content .= "    padding-top: clamp(var(--section-xl-padding-min), 1.087vw + 6.63rem, var(--section-xl-padding-max));\n";
        $content .= "    padding-bottom: clamp(var(--section-xl-padding-min), 1.087vw + 6.63rem, var(--section-xl-padding-max));\n";
        $content .= "    padding-left: clamp(var(--fluid-side-padding-min), 6.522vw + -0.217rem, var(--fluid-side-padding-max))!important;\n";
        $content .= "    padding-right: clamp(var(--fluid-side-padding-min), 6.522vw + -0.217rem, var(--fluid-side-padding-max))!important;\n";
        $content .= "}\n\n";
        
        $content .= ".section-l {\n";
        $content .= "    padding-top: clamp(var(--section-l-padding-min), 1.087vw + 5.38rem, var(--section-l-padding-max));\n";
        $content .= "    padding-bottom: clamp(var(--section-l-padding-min), 1.087vw + 5.38rem, var(--section-l-padding-max));\n";
        $content .= "    padding-left: clamp(var(--fluid-side-padding-min), 6.522vw + -0.217rem, var(--fluid-side-padding-max))!important;\n";
        $content .= "    padding-right: clamp(var(--fluid-side-padding-min), 6.522vw + -0.217rem, var(--fluid-side-padding-max))!important;\n";
        $content .= "}\n\n";
        
        $content .= ".section-m {\n";
        $content .= "    padding-top: clamp(var(--section-m-padding-min), 0vw + 5rem, var(--section-m-padding-max));\n";
        $content .= "    padding-bottom: clamp(var(--section-m-padding-min), 0vw + 5rem, var(--section-m-padding-max));\n";
        $content .= "    padding-left: clamp(var(--fluid-side-padding-min), 6.522vw + -0.217rem, var(--fluid-side-padding-max))!important;\n";
        $content .= "    padding-right: clamp(var(--fluid-side-padding-min), 6.522vw + -0.217rem, var(--fluid-side-padding-max))!important;\n";
        $content .= "}\n\n";
        
        $content .= ".section-s {\n";
        $content .= "    padding-top: clamp(var(--section-s-padding-min), 0vw + 3.75rem, var(--section-s-padding-max));\n";
        $content .= "    padding-bottom: clamp(var(--section-s-padding-min), 0vw + 3.75rem, var(--section-s-padding-max));\n";
        $content .= "    padding-left: clamp(var(--fluid-side-padding-min), 6.522vw + -0.217rem, var(--fluid-side-padding-max))!important;\n";
        $content .= "    padding-right: clamp(var(--fluid-side-padding-min), 6.522vw + -0.217rem, var(--fluid-side-padding-max))!important;\n";
        $content .= "}\n\n";
        
        $content .= ".section-xs {\n";
        $content .= "    padding-top: clamp(var(--section-xs-padding-min), 0vw + 2.5rem, var(--section-xs-padding-max));\n";
        $content .= "    padding-bottom: clamp(var(--section-xs-padding-min), 0vw + 2.5rem, var(--section-xs-padding-max));\n";
        $content .= "    padding-left: clamp(var(--fluid-side-padding-min), 6.522vw + -0.217rem, var(--fluid-side-padding-max))!important;\n";
        $content .= "    padding-right: clamp(var(--fluid-side-padding-min), 6.522vw + -0.217rem, var(--fluid-side-padding-max))!important;\n";
        $content .= "}\n\n";
        
        $content .= ".section-xxs {\n";
        $content .= "    padding-top: clamp(var(--section-xxs-padding-min), 0vw + 1.5rem, var(--section-xxs-padding-max));\n";
        $content .= "    padding-bottom: clamp(var(--section-xxs-padding-min), 0vw + 1.5rem, var(--section-xxs-padding-max));\n";
        $content .= "    padding-left: clamp(var(--fluid-side-padding-min), 6.522vw + -0.217rem, var(--fluid-side-padding-max))!important;\n";
        $content .= "    padding-right: clamp(var(--fluid-side-padding-min), 6.522vw + -0.217rem, var(--fluid-side-padding-max))!important;\n";
        $content .= "}\n\n";
        
        $content .= ".section-header {\n";
        $content .= "    padding-top: clamp(var(--section-header-padding-min), 0vw + 1.25rem, var(--section-header-padding-max));\n";
        $content .= "    padding-bottom: clamp(var(--section-header-padding-min), 0vw + 1.25rem, var(--section-header-padding-max));\n";
        $content .= "    padding-left: clamp(var(--fluid-side-padding-min), 6.522vw + -0.217rem, var(--fluid-side-padding-max))!important;\n";
        $content .= "    padding-right: clamp(var(--fluid-side-padding-min), 6.522vw + -0.217rem, var(--fluid-side-padding-max))!important;\n";
        $content .= "}\n\n";
        
        $content .= "/* Hero Container/Sections Height */\n";
        $content .= ".section-hero {\n";
        $content .= "    min-height: var(--section-hero-height)!important;\n";
        $content .= "}\n\n";
        
        $content .= ".section-hero .e-con-inner {\n";
        $content .= "    justify-content: center!important;\n";
        $content .= "}\n\n";
        
        $content .= "/* Full Width Sections - No Side Padding */\n";
        $content .= ".section-full div {\n";
        $content .= "    max-width: 100%!important;\n";
        $content .= "}\n\n";
        
        $content .= "/* Narrow Sections */\n";
        $content .= ".section-narrow .e-con-inner {\n";
        $content .= "    max-width: var(--section-narrow)!important;\n";
        $content .= "}\n\n";
        
        $content .= ".section-narrow-xs .e-con-inner {\n";
        $content .= "    max-width: var(--section-narrow-xs)!important;\n";
        $content .= "}\n\n";
        
        $content .= "/* Offset Padding for Overlay Headers */\n";
        $content .= ".section-offset {\n";
        $content .= "    padding-top: calc(var(--section-offset-header) + var(--section-xxl-padding-min));\n";
        $content .= "}\n";
        
        $result = file_put_contents($file_path, $content);
        
        // If the file couldn't be created, add an admin notice
        if ($result === false) {
            add_action('admin_notices', function() use ($file_path) {
                echo '<div class="notice notice-error"><p>' . 
                     sprintf(__('Could not create CSS file at: %s. Please check directory permissions.', 'elementor-css-framework'), $file_path) . 
                     '</p></div>';
            });
        }
    }
}