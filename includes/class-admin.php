<?php
/**
 * Admin functionality for the Elementor CSS Framework plugin
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

class ECF_Admin {
    
    /**
     * Constructor
     */
    public function __construct() {
        // Empty constructor
    }
    
    /**
     * Initialize admin functionality
     */
    public function init() {
        // Add admin menu for CSS variables
        add_action('admin_menu', array($this, 'add_admin_menu'));
        
        // Add admin scripts and styles
        add_action('admin_enqueue_scripts', array($this, 'admin_enqueue_scripts'));
        
        // Register AJAX handler for saving variables
        add_action('wp_ajax_ecf_save_variables', array($this, 'ajax_save_variables'));
    }
    
    /**
     * Add admin menu for CSS variables
     */
    public function add_admin_menu() {
        add_menu_page(
            __('CSS Framework', 'elementor-css-framework'),
            __('CSS Framework', 'elementor-css-framework'),
            'manage_options',
            'elementor-css-framework',
            array($this, 'admin_page_display'),
            'dashicons-editor-code',
            60
        );
        
        // Add a submenu with the same name to avoid confusion
        add_submenu_page(
            'elementor-css-framework',
            __('CSS Variables', 'elementor-css-framework'),
            __('CSS Variables', 'elementor-css-framework'),
            'manage_options',
            'elementor-css-framework',
            array($this, 'admin_page_display')
        );
        
        // Add a submenu for editing CSS files
        add_submenu_page(
            'elementor-css-framework',
            __('Edit CSS Files', 'elementor-css-framework'),
            __('Edit CSS Files', 'elementor-css-framework'),
            'manage_options',
            'elementor-css-framework-edit',
            array($this, 'edit_css_page_display')
        );
    }
    
    /**
     * Enqueue admin scripts and styles
     */
    public function admin_enqueue_scripts($hook) {
        // Only load on our admin pages
        if ('toplevel_page_elementor-css-framework' !== $hook && 'css-framework_page_elementor-css-framework-edit' !== $hook) {
            return;
        }
        
        // Enqueue CodeMirror for CSS editing
        wp_enqueue_code_editor(array('type' => 'text/css'));
        
        // Check if admin.js exists and enqueue it
        $admin_js_file = ECF_ASSETS_PATH . 'js/admin.js';
        $admin_js_url = ECF_PLUGIN_URL . 'assets/js/admin.js';
        
        if (file_exists($admin_js_file)) {
            wp_enqueue_script(
                'elementor-css-framework-admin',
                $admin_js_url,
                array('jquery', 'wp-util', 'code-editor'),
                filemtime($admin_js_file),
                true
            );
            
            // Add localization data
            wp_localize_script('elementor-css-framework-admin', 'ecfData', array(
                'ajaxurl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('ecf_save_variables')
            ));
        } else {
            // If admin.js doesn't exist, create a simple inline script for basic functionality
            $inline_js = "
                jQuery(document).ready(function($) {
                    $('#ecf-save-variables, #ecf-save-variables-bottom').on('click', function(e) {
                        e.preventDefault();
                        $('#ecf-variables-form').submit();
                    });
                    
                    $('.ecf-tab-button').on('click', function() {
                        var tab = $(this).data('tab');
                        $('.ecf-tab-button').removeClass('active');
                        $(this).addClass('active');
                        $('.ecf-tab-panel').removeClass('active');
                        $('#tab-' + tab).addClass('active');
                    });
                });
            ";
            wp_add_inline_script('jquery', $inline_js);
        }
        
        // Check if admin.css exists and enqueue it
        $admin_css_file = ECF_CSS_PATH . 'admin.css';
        $admin_css_url = ECF_PLUGIN_URL . 'assets/css/admin.css';
        
        if (file_exists($admin_css_file)) {
            wp_enqueue_style(
                'elementor-css-framework-admin',
                $admin_css_url,
                array(),
                filemtime($admin_css_file)
            );
        } else {
            // If admin.css doesn't exist, add basic inline styles
            $inline_css = "
                .ecf-variables-form {
                    background: #fff;
                    border: 1px solid #ccd0d4;
                    padding: 20px;
                    margin-top: 20px;
                }
                .ecf-variable-row {
                    margin-bottom: 15px;
                    padding-bottom: 15px;
                    border-bottom: 1px solid #f0f0f0;
                }
                .ecf-tab-buttons {
                    margin-bottom: 20px;
                }
                .ecf-tab-button {
                    padding: 5px 10px;
                    margin-right: 5px;
                }
                .ecf-tab-panel {
                    display: none;
                }
                .ecf-tab-panel.active {
                    display: block;
                }
            ";
            wp_add_inline_style('wp-admin', $inline_css);
        }
    }
    
    /**
     * Display admin page for CSS variables
     */
    public function admin_page_display() {
        // Load CSS variables
        $variables_file = ECF_CSS_PATH . 'variables.css';
        $variables = ecf()->css_manager->get_variables_from_file($variables_file);
        
        if (empty($variables)) {
            // If no variables found, use default ones
            $variables = ecf()->core->default_variables;
            
            // Create the variables file with defaults
            ecf()->css_manager->maybe_create_variables_file();
        }
        
        // Handle form submission for manual (non-AJAX) saving
        if (isset($_POST['variables']) && is_array($_POST['variables'])) {
            check_admin_referer('ecf_update_variables_nonce', 'ecf_variables_nonce');
            
            // Build the CSS content
            $content = "/* CSS Framework Variables */\n\n:root {\n";
            
            foreach ($_POST['variables'] as $name => $value) {
                $description = isset(ecf()->core->variable_descriptions[$name]) ? ecf()->core->variable_descriptions[$name] : '';
                $content .= "    " . sanitize_text_field($name) . ": " . sanitize_text_field($value) . "; /* " . $description . " */\n";
            }
            
            $content .= "}\n";
            
            // Save to file
            $result = file_put_contents($variables_file, $content);
            
            if ($result !== false) {
                echo '<div class="notice notice-success is-dismissible"><p>' . __('Variables saved successfully!', 'elementor-css-framework') . '</p></div>';
                // Refresh variables after save
                $variables = ecf()->css_manager->get_variables_from_file($variables_file);
            } else {
                echo '<div class="notice notice-error is-dismissible"><p>' . __('Failed to write to CSS file. Please check file permissions.', 'elementor-css-framework') . '</p></div>';
            }
        }
        
        // Display the admin form
        ?>
        <div class="wrap ecf-admin">
            <h1><?php echo esc_html__('CSS Framework Variables', 'elementor-css-framework'); ?></h1>
            
            <p class="description"><?php echo esc_html__('Edit the CSS variables used by your framework. Changes will be saved to the variables.css file.', 'elementor-css-framework'); ?></p>
            
            <form method="post" id="ecf-variables-form" class="ecf-variables-form">
                <?php wp_nonce_field('ecf_update_variables_nonce', 'ecf_variables_nonce'); ?>
                
                <div class="ecf-form-header">
                    <div class="ecf-search">
                        <input type="text" id="ecf-search-variables" placeholder="<?php esc_attr_e('Search variables...', 'elementor-css-framework'); ?>">
                    </div>
                    <div class="ecf-actions">
                        <button id="ecf-save-variables" class="button button-primary"><?php esc_html_e('Save Changes', 'elementor-css-framework'); ?></button>
                    </div>
                </div>
                
                <div class="ecf-variables-container">
                    <?php foreach ($variables as $name => $value) : 
                        $description = isset(ecf()->core->variable_descriptions[$name]) ? ecf()->core->variable_descriptions[$name] : '';
                    ?>
                        <div class="ecf-variable-row">
                            <div class="ecf-variable-name">
                                <label for="<?php echo esc_attr($name); ?>"><?php echo esc_html($name); ?></label>
                                <?php if ($description) : ?>
                                    <span class="ecf-variable-description"><?php echo esc_html($description); ?></span>
                                <?php endif; ?>
                            </div>
                            <div class="ecf-variable-value">
                                <input type="text" id="<?php echo esc_attr($name); ?>" name="variables[<?php echo esc_attr($name); ?>]" value="<?php echo esc_attr($value); ?>" class="regular-text">
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <div class="ecf-form-footer">
                    <div class="ecf-messages" id="ecf-messages"></div>
                    <div class="ecf-actions">
                        <button id="ecf-save-variables-bottom" class="button button-primary"><?php esc_html_e('Save Changes', 'elementor-css-framework'); ?></button>
                    </div>
                </div>
            </form>
        </div>
        <?php
    }
    
    /**
     * Display admin page for editing CSS files
     */
    public function edit_css_page_display() {
        $variables_file = ECF_CSS_PATH . 'variables.css';
        $main_css_file = ECF_CSS_PATH . 'elementor-section-classes.css';
        
        $variables_content = '';
        $main_css_content = '';
        
        if (file_exists($variables_file)) {
            $variables_content = file_get_contents($variables_file);
        }
        
        if (file_exists($main_css_file)) {
            $main_css_content = file_get_contents($main_css_file);
        }
        
        // Handle form submission for file editing
        if (isset($_POST['ecf_save_files']) && check_admin_referer('ecf_save_files_nonce', 'ecf_files_nonce')) {
            if (isset($_POST['variables_content'])) {
                file_put_contents($variables_file, wp_unslash($_POST['variables_content']));
            }
            
            if (isset($_POST['main_css_content'])) {
                file_put_contents($main_css_file, wp_unslash($_POST['main_css_content']));
            }
            
            echo '<div class="notice notice-success is-dismissible"><p>' . __('CSS files updated successfully!', 'elementor-css-framework') . '</p></div>';
            
            // Refresh content after save
            $variables_content = file_get_contents($variables_file);
            $main_css_content = file_get_contents($main_css_file);
        }
        
        ?>
        <div class="wrap ecf-admin">
            <h1><?php echo esc_html__('Edit CSS Files', 'elementor-css-framework'); ?></h1>
            
            <p class="description"><?php echo esc_html__('Edit the CSS files directly. Be careful when making changes as incorrect CSS syntax can break your site\'s appearance.', 'elementor-css-framework'); ?></p>
            
            <form method="post" action="">
                <?php wp_nonce_field('ecf_save_files_nonce', 'ecf_files_nonce'); ?>
                
                <div class="ecf-tabs">
                    <div class="ecf-tab-buttons">
                        <button type="button" class="ecf-tab-button active" data-tab="variables"><?php esc_html_e('Variables CSS', 'elementor-css-framework'); ?></button>
                        <button type="button" class="ecf-tab-button" data-tab="main-css"><?php esc_html_e('Main CSS', 'elementor-css-framework'); ?></button>
                    </div>
                    
                    <div class="ecf-tab-content">
                        <div class="ecf-tab-panel active" id="tab-variables">
                            <h2><?php esc_html_e('Variables CSS File', 'elementor-css-framework'); ?></h2>
                            <textarea name="variables_content" id="ecf-variables-editor" class="ecf-code-editor"><?php echo esc_textarea($variables_content); ?></textarea>
                        </div>
                        
                        <div class="ecf-tab-panel" id="tab-main-css">
                            <h2><?php esc_html_e('Main CSS File', 'elementor-css-framework'); ?></h2>
                            <textarea name="main_css_content" id="ecf-main-css-editor" class="ecf-code-editor"><?php echo esc_textarea($main_css_content); ?></textarea>
                        </div>
                    </div>
                </div>
                
                <p class="submit">
                    <input type="submit" name="ecf_save_files" class="button button-primary" value="<?php echo esc_attr__('Save All Files', 'elementor-css-framework'); ?>">
                </p>
            </form>
        </div>
        
        <script>
            jQuery(document).ready(function($) {
                // Initialize CodeMirror editors
                if (wp.codeEditor) {
                    var variablesEditor = wp.codeEditor.initialize($('#ecf-variables-editor'), {
                        mode: 'css'
                    });
                    
                    var mainCssEditor = wp.codeEditor.initialize($('#ecf-main-css-editor'), {
                        mode: 'css'
                    });
                }
                
                // Tab functionality
                $('.ecf-tab-button').on('click', function() {
                    var tab = $(this).data('tab');
                    
                    // Update active tab button
                    $('.ecf-tab-button').removeClass('active');
                    $(this).addClass('active');
                    
                    // Show corresponding tab panel
                    $('.ecf-tab-panel').removeClass('active');
                    $('#tab-' + tab).addClass('active');
                    
                    // Refresh CodeMirror instances
                    if (typeof variablesEditor !== 'undefined' && typeof mainCssEditor !== 'undefined') {
                        variablesEditor.codemirror.refresh();
                        mainCssEditor.codemirror.refresh();
                    }
                });
            });
        </script>
        <?php
    }
    
    /**
     * AJAX handler for saving variables
     */
    public function ajax_save_variables() {
        // Check nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'ecf_save_variables')) {
            wp_send_json_error(array('message' => __('Security check failed.', 'elementor-css-framework')));
        }
        
        // Check if variables data exists
        if (!isset($_POST['variables']) || !is_array($_POST['variables'])) {
            wp_send_json_error(array('message' => __('No variables data received.', 'elementor-css-framework')));
        }
        
        $variables_file = ECF_CSS_PATH . 'variables.css';
        $variables = $_POST['variables'];
        
        // Build the CSS content
        $content = "/* CSS Framework Variables */\n\n:root {\n";
        
        foreach ($variables as $name => $value) {
            $description = isset(ecf()->core->variable_descriptions[$name]) ? ecf()->core->variable_descriptions[$name] : '';
            $content .= "    " . sanitize_text_field($name) . ": " . sanitize_text_field($value) . "; /* " . $description . " */\n";
        }
        
        $content .= "}\n";
        
        // Save to file
        $result = file_put_contents($variables_file, $content);
        
        if ($result !== false) {
            wp_send_json_success(array('message' => __('Variables saved successfully!', 'elementor-css-framework')));
        } else {
            wp_send_json_error(array('message' => __('Failed to write to CSS file. Please check file permissions.', 'elementor-css-framework')));
        }
    }
    
    /**
     * Create admin assets
     */
    public function create_admin_assets() {
        // Create admin.js file if it doesn't exist
        $js_dir = ECF_ASSETS_PATH . 'js/';
        if (!file_exists($js_dir)) {
            wp_mkdir_p($js_dir);
        }
        
        $admin_js_file = $js_dir . 'admin.js';
        
        if (!file_exists($admin_js_file)) {
            $js_content = "/**\n * Elementor CSS Framework - Admin JavaScript\n */\n";
            $js_content .= "(function($) {\n    'use strict';\n\n";
            $js_content .= "    // DOM ready\n";
            $js_content .= "    $(document).ready(function() {\n";
            $js_content .= "        // Variables\n";
            $js_content .= "        const \$searchInput = $('#ecf-search-variables');\n";
            $js_content .= "        const \$variableRows = $('.ecf-variable-row');\n";
            $js_content .= "        const \$saveBtn = $('#ecf-save-variables, #ecf-save-variables-bottom');\n\n";
            $js_content .= "        // Search functionality\n";
            $js_content .= "        \$searchInput.on('keyup', function() {\n";
            $js_content .= "            const searchTerm = $(this).val().toLowerCase();\n";
            $js_content .= "            \n";
            $js_content .= "            \$variableRows.each(function() {\n";
            $js_content .= "                const \$row = $(this);\n";
            $js_content .= "                const variableName = \$row.find('label').text().toLowerCase();\n";
            $js_content .= "                const variableDesc = \$row.find('.ecf-variable-description').text().toLowerCase();\n";
            $js_content .= "                \n";
            $js_content .= "                if (variableName.includes(searchTerm) || variableDesc.includes(searchTerm)) {\n";
            $js_content .= "                    \$row.show();\n";
            $js_content .= "                } else {\n";
            $js_content .= "                    \$row.hide();\n";
            $js_content .= "                }\n";
            $js_content .= "            });\n";
            $js_content .= "        });\n";
            $js_content .= "    });\n";
            $js_content .= "})(jQuery);";
            
            file_put_contents($admin_js_file, $js_content);
        }
        
        // Create admin.css if it doesn't exist
        $admin_css_file = ECF_CSS_PATH . 'admin.css';
        if (!file_exists($admin_css_file)) {
            $css_content = "/**\n * Elementor CSS Framework - Admin Styles\n */\n\n";
            $css_content .= ".ecf-admin {\n    max-width: 1200px;\n}\n\n";
            $css_content .= "/* Variables Form */\n";
            $css_content .= ".ecf-variables-form {\n";
            $css_content .= "    background: #fff;\n";
            $css_content .= "    border: 1px solid #ccd0d4;\n";
            $css_content .= "    box-shadow: 0 1px 1px rgba(0, 0, 0, 0.04);\n";
            $css_content .= "    margin-top: 20px;\n";
            $css_content .= "}\n\n";
            $css_content .= ".ecf-variable-row {\n";
            $css_content .= "    margin-bottom: 15px;\n";
            $css_content .= "    padding-bottom: 15px;\n";
            $css_content .= "    border-bottom: 1px solid #f0f0f0;\n";
            $css_content .= "}\n\n";
            $css_content .= ".ecf-variable-description {\n";
            $css_content .= "    color: #666;\n";
            $css_content .= "    font-size: 12px;\n";
            $css_content .= "    display: block;\n";
            $css_content .= "    margin-top: 5px;\n";
            $css_content .= "}\n";
            
            file_put_contents($admin_css_file, $css_content);
        }
    }
}