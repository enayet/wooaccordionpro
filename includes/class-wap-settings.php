<?php
/**
 * WooAccordion Pro Settings Class - Complete with Custom Tabs
 * 
 * Premium admin settings with clean UI/UX and custom tabs functionality
 */

if (!defined('ABSPATH')) {
    exit;
}

class WAP_Settings {

    protected static $_instance = null;

    public static function instance() {
        if (is_null(self::$_instance)) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    public function __construct() {
        add_action('admin_menu', array($this, 'admin_menu'));
        add_action('admin_init', array($this, 'admin_init'));
        add_action('admin_enqueue_scripts', array($this, 'admin_scripts'));
        add_action('wp_ajax_wap_save_settings', array($this, 'ajax_save_settings'));
        
        // Ensure custom tabs manager is loaded
        add_action('init', array($this, 'ensure_custom_tabs_loaded'));
    }

    /**
     * Ensure custom tabs manager is loaded
     */
    public function ensure_custom_tabs_loaded() {
        if (!class_exists('WAP_Custom_Tabs')) {
            include_once WAP_PLUGIN_PATH . 'includes/class-wap-custom-tabs.php';
            WAP_Custom_Tabs::instance();
        }
    }

    /**
     * Add admin menu
     */
    public function admin_menu() {
        add_submenu_page(
            'woocommerce',
            __('WooAccordion Pro', 'wooaccordion-pro'),
            __('Accordions', 'wooaccordion-pro'),
            'manage_woocommerce',
            'wap-settings',
            array($this, 'admin_page')
        );
    }

    /**
     * Initialize admin settings
     */
    public function admin_init() {
        // Register settings with proper sanitization callbacks
        register_setting('wap_settings', 'wap_enable_accordion', array(
            'sanitize_callback' => array($this, 'sanitize_checkbox')
        ));

        register_setting('wap_settings', 'wap_auto_expand_first', array(
            'sanitize_callback' => array($this, 'sanitize_checkbox')
        ));

        register_setting('wap_settings', 'wap_allow_multiple_open', array(
            'sanitize_callback' => array($this, 'sanitize_checkbox')
        ));

        register_setting('wap_settings', 'wap_template', array(
            'sanitize_callback' => array($this, 'sanitize_template')
        ));

        register_setting('wap_settings', 'wap_show_icons', array(
            'sanitize_callback' => array($this, 'sanitize_checkbox')
        ));

        register_setting('wap_settings', 'wap_icon_library', array(
            'sanitize_callback' => array($this, 'sanitize_icon_library')
        ));

        register_setting('wap_settings', 'wap_header_bg_color', array(
            'sanitize_callback' => 'sanitize_hex_color'
        ));

        register_setting('wap_settings', 'wap_header_text_color', array(
            'sanitize_callback' => 'sanitize_hex_color'
        ));

        register_setting('wap_settings', 'wap_active_header_bg_color', array(
            'sanitize_callback' => 'sanitize_hex_color'
        ));

        register_setting('wap_settings', 'wap_active_header_text_color', array(
            'sanitize_callback' => 'sanitize_hex_color'
        ));

        register_setting('wap_settings', 'wap_border_color', array(
            'sanitize_callback' => 'sanitize_hex_color'
        ));

        register_setting('wap_settings', 'wap_toggle_icon_style', array(
            'sanitize_callback' => array($this, 'sanitize_toggle_icon_style')
        ));

        register_setting('wap_settings', 'wap_animation_duration', array(
            'sanitize_callback' => array($this, 'sanitize_animation_duration')
        ));

        register_setting('wap_settings', 'wap_enable_mobile_gestures', array(
            'sanitize_callback' => array($this, 'sanitize_checkbox')
        ));
    }

    /**
     * Sanitize checkbox values
     */
    public function sanitize_checkbox($value) {
        return $value === 'yes' ? 'yes' : 'no';
    }

    /**
     * Sanitize template selection
     */
    public function sanitize_template($value) {
        $allowed_templates = array('modern', 'minimal', 'classic');
        return in_array($value, $allowed_templates) ? $value : 'modern';
    }

    /**
     * Sanitize icon library selection
     */
    public function sanitize_icon_library($value) {
        $allowed_libraries = array('css', 'fontawesome');
        return in_array($value, $allowed_libraries) ? $value : 'css';
    }

    /**
     * Sanitize toggle icon style
     */
    public function sanitize_toggle_icon_style($value) {
        $allowed_styles = array('plus_minus', 'arrow_down', 'chevron', 'triangle');
        return in_array($value, $allowed_styles) ? $value : 'plus_minus';
    }

    /**
     * Sanitize animation duration
     */
    public function sanitize_animation_duration($value) {
        $allowed_durations = array('200', '300', '500');
        return in_array($value, $allowed_durations) ? $value : '300';
    }

    /**
     * Enqueue admin scripts
     */
    public function admin_scripts($hook) {
        if (strpos($hook, 'wap-settings') !== false) {
            wp_enqueue_style(
                'wap-admin-css',
                WAP_PLUGIN_URL . 'assets/css/admin.css',
                array(),
                WAP_VERSION
            );

            wp_enqueue_script(
                'wap-admin-js',
                WAP_PLUGIN_URL . 'assets/js/admin.js',
                array('jquery'),
                WAP_VERSION,
                true
            );
            
            // Enqueue WordPress editor assets
            wp_enqueue_editor(); 

            wp_localize_script('wap-admin-js', 'wap_admin', array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('wap_admin_nonce'),
                'strings' => array(
                    'save_success' => __('Settings saved successfully!', 'wooaccordion-pro'),
                    'save_error' => __('Error saving settings. Please try again.', 'wooaccordion-pro')
                )
            ));
        }
    }

    /**
     * Save settings (traditional form submission)
     */
    private function save_settings() {
        // Verify nonce - this method is only called after nonce verification in admin_page()        
        if (isset($_POST['submit']) && isset($_POST['wap_nonce']) && wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['wap_nonce'])), 'wap_settings_save')) {
            $this->save_settings();
            echo '<div class="notice notice-success"><p>' . esc_html__('Settings saved successfully!', 'wooaccordion-pro') . '</p></div>';
        }        
        
        $current_template = get_option('wap_template', 'modern');
        $new_template = isset($_POST['wap_template']) ? sanitize_text_field(wp_unslash($_POST['wap_template'])) : 'modern';

        $settings = array(
            'wap_enable_accordion' => isset($_POST['wap_enable_accordion']) ? 'yes' : 'no',
            'wap_auto_expand_first' => isset($_POST['wap_auto_expand_first']) ? 'yes' : 'no',
            'wap_allow_multiple_open' => isset($_POST['wap_allow_multiple_open']) ? 'yes' : 'no',
            'wap_show_icons' => isset($_POST['wap_show_icons']) ? 'yes' : 'no',
            'wap_enable_mobile_gestures' => isset($_POST['wap_enable_mobile_gestures']) ? 'yes' : 'no',
            'wap_template' => $new_template,
            'wap_icon_library' => isset($_POST['wap_icon_library']) ? sanitize_text_field(wp_unslash($_POST['wap_icon_library'])) : 'css',
            'wap_toggle_icon_style' => isset($_POST['wap_toggle_icon_style']) ? sanitize_text_field(wp_unslash($_POST['wap_toggle_icon_style'])) : 'plus_minus',
            'wap_animation_duration' => isset($_POST['wap_animation_duration']) ? sanitize_text_field(wp_unslash($_POST['wap_animation_duration'])) : '300',
        );

        // If template changed, reset colors to template defaults
        if ($current_template !== $new_template) {
            $template_defaults = $this->get_template_defaults($new_template);
            $settings = array_merge($settings, $template_defaults);
        } else {
            // Use submitted colors - FIXED: Make sure to include the new field
            $settings = array_merge($settings, array(
                'wap_header_bg_color' => isset($_POST['wap_header_bg_color']) ? sanitize_hex_color(wp_unslash($_POST['wap_header_bg_color'])) : '#f8f9fa',
                'wap_header_text_color' => isset($_POST['wap_header_text_color']) ? sanitize_hex_color(wp_unslash($_POST['wap_header_text_color'])) : '#495057',
                'wap_active_header_bg_color' => isset($_POST['wap_active_header_bg_color']) ? sanitize_hex_color(wp_unslash($_POST['wap_active_header_bg_color'])) : '#0073aa',
                'wap_active_header_text_color' => isset($_POST['wap_active_header_text_color']) ? sanitize_hex_color(wp_unslash($_POST['wap_active_header_text_color'])) : '#ffffff', // FIXED
                'wap_border_color' => isset($_POST['wap_border_color']) ? sanitize_hex_color(wp_unslash($_POST['wap_border_color'])) : '#dee2e6'
            ));
        }

        foreach ($settings as $key => $value) {
            update_option($key, $value);
        }

        return true;
    }

    /**
     * Premium admin page with tabbed interface
     */
    public function admin_page() {
        // Handle traditional form submission as fallback       
        if (isset($_POST['submit']) && isset($_POST['wap_nonce']) && wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['wap_nonce'])), 'wap_settings_save')) {
            $this->save_settings();
            echo '<div class="notice notice-success"><p>' . esc_html__('Settings saved successfully!', 'wooaccordion-pro') . '</p></div>';
        }
        ?>
        <div class="wrap wap-admin-wrap">
            <div class="wap-admin-header">
                <div class="wap-admin-header-content">
                    <h2><?php esc_html_e('Transform Your Product Tabs Into Beautiful Accordions', 'wooaccordion-pro'); ?></h2>
                    <p><?php esc_html_e('Configure your accordion settings below to create a better mobile shopping experience.', 'wooaccordion-pro'); ?></p>
                </div>
                <div class="wap-admin-header-stats">
                    <div class="wap-stat">
                        <span class="wap-stat-number">40%</span>
                        <span class="wap-stat-label"><?php esc_html_e('Higher Mobile Conversions', 'wooaccordion-pro'); ?></span>
                    </div>
                    <div class="wap-stat">
                        <span class="wap-stat-number">75%</span>
                        <span class="wap-stat-label"><?php esc_html_e('Of Traffic is Mobile', 'wooaccordion-pro'); ?></span>
                    </div>
                </div>
            </div>

            <div class="wap-admin-content">
                <div class="wap-admin-main">
                    <form method="post" action="" id="wap-settings-form">
                        <?php wp_nonce_field('wap_settings_save', 'wap_nonce'); ?>
                        
                        <div class="wap-settings-tabs">
                            <nav class="wap-tab-nav">
                                <button type="button" class="wap-tab-button active" data-tab="general">
                                    <?php esc_html_e('General', 'wooaccordion-pro'); ?>
                                </button>
                                <button type="button" class="wap-tab-button" data-tab="styling">
                                    <?php esc_html_e('Styling', 'wooaccordion-pro'); ?>
                                </button>
                                <button type="button" class="wap-tab-button" data-tab="mobile">
                                    <?php esc_html_e('Mobile', 'wooaccordion-pro'); ?>
                                </button>
                                <button type="button" class="wap-tab-button" data-tab="custom-tabs">
                                    <?php esc_html_e('Custom Tabs', 'wooaccordion-pro'); ?>
                                </button>
                            </nav>

                            <div class="wap-tab-content">
                                <!-- General Tab -->
                                <div class="wap-tab-panel active" data-panel="general">
                                    <h3><?php esc_html_e('General Settings', 'wooaccordion-pro'); ?></h3>
                                    <p><?php esc_html_e('Configure basic accordion functionality.', 'wooaccordion-pro'); ?></p>

                                    <div class="wap-form-field">
                                        <label for="wap_enable_accordion"><?php esc_html_e('Enable Accordions', 'wooaccordion-pro'); ?></label>
                                        <input type="checkbox" id="wap_enable_accordion" name="wap_enable_accordion" value="yes" 
                                               <?php checked(get_option('wap_enable_accordion'), 'yes'); ?> />
                                        <p class="description"><?php esc_html_e('Replace WooCommerce product tabs with accordions', 'wooaccordion-pro'); ?></p>
                                    </div>

                                    <div class="wap-form-field">
                                        <label for="wap_auto_expand_first"><?php esc_html_e('Auto Expand First', 'wooaccordion-pro'); ?></label>
                                        <input type="checkbox" id="wap_auto_expand_first" name="wap_auto_expand_first" value="yes" 
                                               <?php checked(get_option('wap_auto_expand_first'), 'yes'); ?> />
                                        <p class="description"><?php esc_html_e('Automatically expand the first accordion item when page loads', 'wooaccordion-pro'); ?></p>
                                    </div>

                                    <div class="wap-form-field">
                                        <label for="wap_allow_multiple_open"><?php esc_html_e('Allow Multiple Open', 'wooaccordion-pro'); ?></label>
                                        <input type="checkbox" id="wap_allow_multiple_open" name="wap_allow_multiple_open" value="yes" 
                                               <?php checked(get_option('wap_allow_multiple_open'), 'yes'); ?> />
                                        <p class="description"><?php esc_html_e('Allow multiple accordion items to be open simultaneously', 'wooaccordion-pro'); ?></p>
                                    </div>

                                    <div class="wap-form-field">
                                        <label for="wap_show_icons"><?php esc_html_e('Show Icons', 'wooaccordion-pro'); ?></label>
                                        <input type="checkbox" id="wap_show_icons" name="wap_show_icons" value="yes" 
                                               <?php checked(get_option('wap_show_icons', 'yes'), 'yes'); ?> />
                                        <p class="description"><?php esc_html_e('Display icons next to accordion headers', 'wooaccordion-pro'); ?></p>
                                    </div>

                                    <div class="wap-form-field">
                                        <label for="wap_icon_library"><?php esc_html_e('Icon Library', 'wooaccordion-pro'); ?></label>
                                        <select id="wap_icon_library" name="wap_icon_library">
                                            <option value="css" <?php selected(get_option('wap_icon_library', 'css'), 'css'); ?>>
                                                <?php esc_html_e('CSS Icons (Lightweight)', 'wooaccordion-pro'); ?>
                                            </option>
                                            <option value="fontawesome" <?php selected(get_option('wap_icon_library'), 'fontawesome'); ?>>
                                                <?php esc_html_e('FontAwesome', 'wooaccordion-pro'); ?>
                                            </option>
                                        </select>
                                        <p class="description"><?php esc_html_e('Choose which icon library to use', 'wooaccordion-pro'); ?></p>
                                    </div>

                                    <div class="wap-form-field">
                                        <label for="wap_animation_duration"><?php esc_html_e('Animation Speed', 'wooaccordion-pro'); ?></label>
                                        <select id="wap_animation_duration" name="wap_animation_duration">
                                            <option value="200" <?php selected(get_option('wap_animation_duration', '300'), '200'); ?>>
                                                <?php esc_html_e('Fast (200ms)', 'wooaccordion-pro'); ?>
                                            </option>
                                            <option value="300" <?php selected(get_option('wap_animation_duration', '300'), '300'); ?>>
                                                <?php esc_html_e('Normal (300ms)', 'wooaccordion-pro'); ?>
                                            </option>
                                            <option value="500" <?php selected(get_option('wap_animation_duration', '300'), '500'); ?>>
                                                <?php esc_html_e('Slow (500ms)', 'wooaccordion-pro'); ?>
                                            </option>
                                        </select>
                                        <p class="description"><?php esc_html_e('How fast accordions open and close', 'wooaccordion-pro'); ?></p>
                                    </div>
                                </div>

                                <!-- Styling Tab -->
                                <div class="wap-tab-panel" data-panel="styling">
                                    <h3><?php esc_html_e('Styling Options', 'wooaccordion-pro'); ?></h3>
                                    <p><?php esc_html_e('Customize the appearance of your accordions.', 'wooaccordion-pro'); ?></p>

                                    <div class="wap-form-field">
                                        <label for="wap_template"><?php esc_html_e('Template Style', 'wooaccordion-pro'); ?></label>
                                        <select id="wap_template" name="wap_template">
                                            <option value="modern" <?php selected(get_option('wap_template'), 'modern'); ?>>
                                                <?php esc_html_e('Modern Card', 'wooaccordion-pro'); ?>
                                            </option>
                                            <option value="minimal" <?php selected(get_option('wap_template'), 'minimal'); ?>>
                                                <?php esc_html_e('Minimal Flat', 'wooaccordion-pro'); ?>
                                            </option>
                                            <option value="classic" <?php selected(get_option('wap_template'), 'classic'); ?>>
                                                <?php esc_html_e('Classic', 'wooaccordion-pro'); ?>
                                            </option>
                                        </select>
                                        <p class="description"><?php esc_html_e('Choose a pre-designed layout template. Colors will reset to template defaults when changed.', 'wooaccordion-pro'); ?></p>
                                    </div>
                                    
                                    
                                    <!-- NEW: Toggle Icon Style -->
                                    <div class="wap-form-field">
                                        <label for="wap_toggle_icon_style"><?php esc_html_e('Toggle Icon Style', 'wooaccordion-pro'); ?></label>
                                        <select id="wap_toggle_icon_style" name="wap_toggle_icon_style">
                                            <option value="plus_minus" <?php selected(get_option('wap_toggle_icon_style', 'plus_minus'), 'plus_minus'); ?>>
                                                <?php esc_html_e('Plus/Minus (+/−)', 'wooaccordion-pro'); ?>
                                            </option>
                                            <option value="arrow_down" <?php selected(get_option('wap_toggle_icon_style'), 'arrow_down'); ?>>
                                                <?php esc_html_e('Arrow Down (↓)', 'wooaccordion-pro'); ?>
                                            </option>
                                            <option value="chevron" <?php selected(get_option('wap_toggle_icon_style'), 'chevron'); ?>>
                                                <?php esc_html_e('Chevron (⌄)', 'wooaccordion-pro'); ?>
                                            </option>
                                            <option value="triangle" <?php selected(get_option('wap_toggle_icon_style'), 'triangle'); ?>>
                                                <?php esc_html_e('Triangle (▶)', 'wooaccordion-pro'); ?>
                                            </option>
                                        </select>
                                        <p class="description"><?php esc_html_e('Choose the icon style for expand/collapse indicators', 'wooaccordion-pro'); ?></p>
                                    </div>                                    
                                    
                                    

                                    <div class="wap-form-field">
                                        <label for="wap_header_bg_color"><?php esc_html_e('Header Background', 'wooaccordion-pro'); ?></label>
                                        <div style="display: flex; align-items: center; gap: 10px;">
                                            <input type="color" id="wap_header_bg_color" name="wap_header_bg_color" 
                                                   value="<?php echo esc_attr(get_option('wap_header_bg_color', '#f8f9fa')); ?>" />
                                            <button type="button" class="button button-small wap-reset-color" data-field="wap_header_bg_color" data-default="#f8f9fa">
                                                <?php esc_html_e('Reset', 'wooaccordion-pro'); ?>
                                            </button>
                                        </div>
                                        <p class="description"><?php esc_html_e('Background color for accordion headers', 'wooaccordion-pro'); ?></p>
                                    </div>

                                    <div class="wap-form-field">
                                        <label for="wap_header_text_color"><?php esc_html_e('Header Text Color', 'wooaccordion-pro'); ?></label>
                                        <div style="display: flex; align-items: center; gap: 10px;">
                                            <input type="color" id="wap_header_text_color" name="wap_header_text_color" 
                                                   value="<?php echo esc_attr(get_option('wap_header_text_color', '#495057')); ?>" />
                                            <button type="button" class="button button-small wap-reset-color" data-field="wap_header_text_color" data-default="#495057">
                                                <?php esc_html_e('Reset', 'wooaccordion-pro'); ?>
                                            </button>
                                        </div>
                                        <p class="description"><?php esc_html_e('Text color for accordion headers', 'wooaccordion-pro'); ?></p>
                                    </div>

                                    <div class="wap-form-field">
                                        <label for="wap_active_header_bg_color"><?php esc_html_e('Active Header Background', 'wooaccordion-pro'); ?></label>
                                        <div style="display: flex; align-items: center; gap: 10px;">
                                            <input type="color" id="wap_active_header_bg_color" name="wap_active_header_bg_color" 
                                                   value="<?php echo esc_attr(get_option('wap_active_header_bg_color', '#0073aa')); ?>" />
                                            <button type="button" class="button button-small wap-reset-color" data-field="wap_active_header_bg_color" data-default="#0073aa">
                                                <?php esc_html_e('Reset', 'wooaccordion-pro'); ?>
                                            </button>
                                        </div>
                                        <p class="description"><?php esc_html_e('Background color for active/expanded accordion headers', 'wooaccordion-pro'); ?></p>
                                    </div>
                                    
                                    <!-- NEW: Active Header Text Color -->
                                    <div class="wap-form-field">
                                        <label for="wap_active_header_text_color"><?php esc_html_e('Active Header Text Color', 'wooaccordion-pro'); ?></label>
                                        <div style="display: flex; align-items: center; gap: 10px;">
                                            <input type="color" id="wap_active_header_text_color" name="wap_active_header_text_color" 
                                                   value="<?php echo esc_attr(get_option('wap_active_header_text_color', '#ffffff')); ?>" />
                                            <button type="button" class="button button-small wap-reset-color" data-field="wap_active_header_text_color" data-default="#ffffff">
                                                <?php esc_html_e('Reset', 'wooaccordion-pro'); ?>
                                            </button>
                                        </div>
                                        <p class="description"><?php esc_html_e('Text color for active/expanded accordion headers', 'wooaccordion-pro'); ?></p>
                                    </div>                                    
                                    
                                    

                                    <div class="wap-form-field">
                                        <label for="wap_border_color"><?php esc_html_e('Border Color', 'wooaccordion-pro'); ?></label>
                                        <div style="display: flex; align-items: center; gap: 10px;">
                                            <input type="color" id="wap_border_color" name="wap_border_color" 
                                                   value="<?php echo esc_attr(get_option('wap_border_color', '#dee2e6')); ?>" />
                                            <button type="button" class="button button-small wap-reset-color" data-field="wap_border_color" data-default="#dee2e6">
                                                <?php esc_html_e('Reset', 'wooaccordion-pro'); ?>
                                            </button>
                                        </div>
                                        <p class="description"><?php esc_html_e('Border color for accordion items', 'wooaccordion-pro'); ?></p>
                                    </div>
                                </div>

                                <!-- Mobile Tab -->
                                <div class="wap-tab-panel" data-panel="mobile">
                                    <h3><?php esc_html_e('Mobile Settings', 'wooaccordion-pro'); ?></h3>
                                    <p><?php esc_html_e('Optimize accordion behavior for mobile devices.', 'wooaccordion-pro'); ?></p>

                                    <div class="wap-form-field">
                                        <label for="wap_enable_mobile_gestures"><?php esc_html_e('Enable Touch Gestures', 'wooaccordion-pro'); ?></label>
                                        <input type="checkbox" id="wap_enable_mobile_gestures" name="wap_enable_mobile_gestures" value="yes" 
                                               <?php checked(get_option('wap_enable_mobile_gestures', 'yes'), 'yes'); ?> />
                                        <p class="description"><?php esc_html_e('Allow swipe gestures to expand/collapse accordions on mobile', 'wooaccordion-pro'); ?></p>
                                    </div>

                                    <div class="wap-notice wap-notice-info">
                                        <h4><?php esc_html_e('Mobile Optimization Features', 'wooaccordion-pro'); ?></h4>
                                        <ul>
                                            <li><?php esc_html_e('Automatic touch-friendly spacing', 'wooaccordion-pro'); ?></li>
                                            <li><?php esc_html_e('Responsive design for all screen sizes', 'wooaccordion-pro'); ?></li>
                                            <li><?php esc_html_e('Optimized animations for mobile performance', 'wooaccordion-pro'); ?></li>
                                            <li><?php esc_html_e('Large touch targets for better usability', 'wooaccordion-pro'); ?></li>
                                        </ul>
                                    </div>
                                </div>

                                <!-- Custom Tabs Tab -->
                                <?php $this->render_custom_tabs_panel(); ?>
                            </div>
                        </div>
                        
                        <div class="wap-form-actions">
                            <input type="submit" name="submit" id="submit" class="button-primary" value="<?php esc_html_e('Save Settings', 'wooaccordion-pro'); ?>" />
                            <button type="button" class="button button-secondary" id="wap-reset-settings">
                                <?php esc_html_e('Reset to Defaults', 'wooaccordion-pro'); ?>
                            </button>
                        </div>
                    </form>
                </div>

                <div class="wap-admin-sidebar">
                    <div class="wap-admin-box">
                        <h3><?php esc_html_e('Current Status', 'wooaccordion-pro'); ?></h3>
                        <div class="wap-status-item">
                            <span class="wap-status-label"><?php esc_html_e('Accordions:', 'wooaccordion-pro'); ?></span>
                            <span class="wap-status-value <?php echo get_option('wap_enable_accordion') === 'yes' ? 'enabled' : 'disabled'; ?>">
                                <?php echo get_option('wap_enable_accordion') === 'yes' ? esc_html__('Enabled', 'wooaccordion-pro') : esc_html__('Disabled', 'wooaccordion-pro'); ?>
                            </span>
                        </div>
                        <div class="wap-status-item">
                            <span class="wap-status-label"><?php esc_html_e('Template:', 'wooaccordion-pro'); ?></span>
                            <span class="wap-status-value"><?php echo esc_html(ucfirst(get_option('wap_template', 'modern'))); ?></span>
                        </div>
                        <div class="wap-status-item">
                            <span class="wap-status-label"><?php esc_html_e('Custom Tabs:', 'wooaccordion-pro'); ?></span>
                            <span class="wap-status-value">
                                <?php 
                                $custom_tabs = get_option('wap_custom_tabs', array());
                                echo count($custom_tabs) . ' ' . esc_html__('tabs', 'wooaccordion-pro');
                                ?>
                            </span>
                        </div>
                    </div>

                    <div class="wap-admin-box">
                        <h3><?php esc_html_e('Quick Links', 'wooaccordion-pro'); ?></h3>
                        <ul>
                            <li><a href="<?php echo esc_url(admin_url('edit.php?post_type=product')); ?>"><?php esc_html_e('View Products', 'wooaccordion-pro'); ?></a></li>
                            <li><a href="<?php echo esc_url('https://wooaccordionpro.com/docs/'); ?>" target="_blank"><?php esc_html_e('Documentation', 'wooaccordion-pro'); ?></a></li>
                            <li><a href="<?php echo esc_url('https://wooaccordionpro.com/support/'); ?>" target="_blank"><?php esc_html_e('Get Support', 'wooaccordion-pro'); ?></a></li>
                        </ul>
                    </div>

                    <div class="wap-admin-box wap-upgrade-box">
                        <h3><?php esc_html_e('Need Help?', 'wooaccordion-pro'); ?></h3>
                        <p><?php esc_html_e('Check out our comprehensive documentation or contact our support team.', 'wooaccordion-pro'); ?></p>
                        <a href="<?php echo esc_url('https://wooaccordionpro.com/support/'); ?>" target="_blank" class="button button-primary">
                            <?php esc_html_e('Get Support', 'wooaccordion-pro'); ?>
                        </a>
                    </div>
                </div>
            </div>

            <!-- Custom Tab Editor Modal -->
            <?php $this->render_custom_tab_modal(); ?>
        </div>
        <?php
    }

    /**
     * Render custom tabs panel
     */
    private function render_custom_tabs_panel() {
        // Ensure custom tabs manager is available
        if (!class_exists('WAP_Custom_Tabs')) {
            $this->ensure_custom_tabs_loaded();
        }
        
        $custom_tabs_manager = WAP_Custom_Tabs::instance();
        $custom_tabs = $custom_tabs_manager->get_custom_tabs();
        ?>
        <div class="wap-tab-panel" data-panel="custom-tabs">
            <h3><?php esc_html_e('Custom Tabs Manager', 'wooaccordion-pro'); ?></h3>
            <p><?php esc_html_e('Create custom accordion tabs with conditional display logic.', 'wooaccordion-pro'); ?></p>

            <div class="wap-custom-tabs-header">
                <button type="button" class="button button-primary" id="wap-add-custom-tab">
                    <?php esc_html_e('Add New Tab', 'wooaccordion-pro'); ?>
                </button>
            </div>

            <div class="wap-custom-tabs-list">
                <?php if (empty($custom_tabs)) : ?>
                    <div class="wap-no-tabs-message">
                        <p><?php esc_html_e('No custom tabs created yet. Click "Add New Tab" to get started.', 'wooaccordion-pro'); ?></p>
                    </div>
                <?php else : ?>
                    <div class="wap-tabs-table">
                        <div class="wap-tabs-header">
                            <div class="wap-col-title"><?php esc_html_e('Tab Title', 'wooaccordion-pro'); ?></div>
                            <div class="wap-col-conditions"><?php esc_html_e('Conditions', 'wooaccordion-pro'); ?></div>
                            <div class="wap-col-priority"><?php esc_html_e('Priority', 'wooaccordion-pro'); ?></div>
                            <div class="wap-col-status"><?php esc_html_e('Status', 'wooaccordion-pro'); ?></div>
                            <div class="wap-col-actions"><?php esc_html_e('Actions', 'wooaccordion-pro'); ?></div>
                        </div>
                        
                        <?php foreach ($custom_tabs as $tab_id => $tab_data) : ?>
                            <div class="wap-tab-row" data-tab-id="<?php echo esc_attr($tab_id); ?>">
                                <div class="wap-col-title">
                                    <strong><?php echo esc_html($tab_data['title']); ?></strong>
                                </div>
                                <div class="wap-col-conditions">
                                    <?php echo esc_html($this->format_tab_conditions($tab_data['conditions'] ?? array())); ?>
                                </div>
                                <div class="wap-col-priority">
                                    <?php echo esc_html($tab_data['priority'] ?? 50); ?>
                                </div>
                                <div class="wap-col-status">
                                    <span class="wap-status-badge <?php echo !empty($tab_data['enabled']) ? 'enabled' : 'disabled'; ?>">
                                        <?php echo !empty($tab_data['enabled']) ? esc_html__('Enabled', 'wooaccordion-pro') : esc_html__('Disabled', 'wooaccordion-pro'); ?>
                                    </span>
                                </div>
                                <div class="wap-col-actions">
                                    <button type="button" class="button button-small wap-edit-tab" data-tab-id="<?php echo esc_attr($tab_id); ?>">
                                        <?php esc_html_e('Edit', 'wooaccordion-pro'); ?>
                                    </button>
                                    <button type="button" class="button button-small button-link-delete wap-delete-tab" data-tab-id="<?php echo esc_attr($tab_id); ?>">
                                        <?php esc_html_e('Delete', 'wooaccordion-pro'); ?>
                                    </button>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        <?php
    }

    /**
     * Render custom tab editor modal
     */
    private function render_custom_tab_modal() {
        // Ensure custom tabs manager is available
        if (!class_exists('WAP_Custom_Tabs')) {
            $this->ensure_custom_tabs_loaded();
        }
        
        $custom_tabs_manager = WAP_Custom_Tabs::instance();
        ?>
        <div id="wap-tab-editor-modal" class="wap-modal" style="display: none;">
            <div class="wap-modal-content">
                <div class="wap-modal-header">
                    <h3 id="wap-modal-title"><?php esc_html_e('Add New Tab', 'wooaccordion-pro'); ?></h3>
                    <button type="button" class="wap-modal-close">&times;</button>
                </div>
                
                <div class="wap-modal-body">
                    <div class="wap-modal-message" style="display: none;"></div>
                    
                    <form id="wap-custom-tab-form">
                        <input type="hidden" id="wap-tab-id" name="tab_id" value="">
                        
                        <div class="wap-form-section">
                            <h4><?php esc_html_e('Basic Information', 'wooaccordion-pro'); ?></h4>
                            
                            <div class="wap-form-field">
                                <label for="wap-tab-title"><?php esc_html_e('Tab Title', 'wooaccordion-pro'); ?></label>
                                <input type="text" id="wap-tab-title" name="tab_data[title]" required />
                                <p class="description"><?php esc_html_e('The title that appears in the accordion header', 'wooaccordion-pro'); ?></p>
                            </div>

                            <div class="wap-form-field">
                                <label for="wap-tab-priority"><?php esc_html_e('Priority', 'wooaccordion-pro'); ?></label>
                                <input type="number" id="wap-tab-priority" name="tab_data[priority]" value="50" min="1" max="100" />
                                <p class="description"><?php esc_html_e('Lower numbers appear first (1-100)', 'wooaccordion-pro'); ?></p>
                            </div>

                            <div class="wap-form-field">
                                <label for="wap-tab-enabled"><?php esc_html_e('Status', 'wooaccordion-pro'); ?></label>
                                <input type="checkbox" id="wap-tab-enabled" name="tab_data[enabled]" value="1" checked />
                                <p class="description"><?php esc_html_e('Enable this tab', 'wooaccordion-pro'); ?></p>
                            </div>
                        </div>

                        <div class="wap-form-section">
                            <h4><?php esc_html_e('Tab Content', 'wooaccordion-pro'); ?></h4>
                            
                            <?php $this->render_wp_editor_in_modal(); ?>
                        </div>

                        <div class="wap-form-section">
                            <h4><?php esc_html_e('Display Conditions', 'wooaccordion-pro'); ?></h4>
                            <p class="description"><?php esc_html_e('Leave empty to show on all products. Add conditions to show only on specific products.', 'wooaccordion-pro'); ?></p>
                            
                            <div class="wap-form-field">
                                <label for="wap-tab-categories"><?php esc_html_e('Product Categories', 'wooaccordion-pro'); ?></label>
                                <select id="wap-tab-categories" name="tab_data[conditions][categories][]" multiple>
                                    <?php
                                    $categories = $custom_tabs_manager->get_product_categories();
                                    foreach ($categories as $id => $name) :
                                    ?>
                                        <option value="<?php echo esc_attr($id); ?>"><?php echo esc_html($name); ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <p class="description"><?php esc_html_e('Show tab only for products in these categories', 'wooaccordion-pro'); ?></p>
                            </div>

                            <div class="wap-form-field">
                                <label for="wap-tab-user-roles"><?php esc_html_e('User Roles', 'wooaccordion-pro'); ?></label>
                                <select id="wap-tab-user-roles" name="tab_data[conditions][user_roles][]" multiple>
                                    <?php
                                    $user_roles = $custom_tabs_manager->get_user_roles();
                                    foreach ($user_roles as $role_key => $role_name) :
                                    ?>
                                        <option value="<?php echo esc_attr($role_key); ?>"><?php echo esc_html($role_name); ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <p class="description"><?php esc_html_e('Show tab only for users with these roles', 'wooaccordion-pro'); ?></p>
                            </div>

                            <div class="wap-form-field">
                                <label for="wap-tab-product-types"><?php esc_html_e('Product Types', 'wooaccordion-pro'); ?></label>
                                <select id="wap-tab-product-types" name="tab_data[conditions][product_types][]" multiple>
                                    <?php
                                    $product_types = $custom_tabs_manager->get_product_types();
                                    foreach ($product_types as $type_key => $type_name) :
                                    ?>
                                        <option value="<?php echo esc_attr($type_key); ?>"><?php echo esc_html($type_name); ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <p class="description"><?php esc_html_e('Show tab only for these product types', 'wooaccordion-pro'); ?></p>
                            </div>
                        </div>
                    </form>
                </div>
                
                <div class="wap-modal-footer">
                    <button type="button" class="button button-secondary wap-modal-close">
                        <?php esc_html_e('Cancel', 'wooaccordion-pro'); ?>
                    </button>
                    <button type="button" class="button button-primary" id="wap-save-custom-tab">
                        <?php esc_html_e('Save Tab', 'wooaccordion-pro'); ?>
                    </button>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * Format tab conditions for display
     */
    private function format_tab_conditions($conditions) {
        $formatted = array();
        
        if (!empty($conditions['categories'])) {
            $category_names = array();
            foreach ($conditions['categories'] as $cat_id) {
                $term = get_term($cat_id, 'product_cat');
                if ($term && !is_wp_error($term)) {
                    $category_names[] = $term->name;
                }
            }
            if (!empty($category_names)) {
                $formatted[] = __('Categories: ', 'wooaccordion-pro') . implode(', ', $category_names);
            }
        }
        
        if (!empty($conditions['user_roles'])) {
            global $wp_roles;
            $role_names = array();
            foreach ($conditions['user_roles'] as $role_key) {
                if (isset($wp_roles->roles[$role_key])) {
                    $role_names[] = $wp_roles->roles[$role_key]['name'];
                }
            }
            if (!empty($role_names)) {
                $formatted[] = __('Roles: ', 'wooaccordion-pro') . implode(', ', $role_names);
            }
        }
        
        if (!empty($conditions['product_types'])) {
            $type_labels = array(
                'simple' => __('Simple', 'wooaccordion-pro'),
                'grouped' => __('Grouped', 'wooaccordion-pro'),
                'external' => __('External', 'wooaccordion-pro'),
                'variable' => __('Variable', 'wooaccordion-pro'),
            );
            $type_names = array();
            foreach ($conditions['product_types'] as $type) {
                if (isset($type_labels[$type])) {
                    $type_names[] = $type_labels[$type];
                }
            }
            if (!empty($type_names)) {
                $formatted[] = __('Types: ', 'wooaccordion-pro') . implode(', ', $type_names);
            }
        }
        
        return !empty($formatted) ? implode('<br>', $formatted) : __('All products', 'wooaccordion-pro');
    }

    /**
     * AJAX handler for saving settings
     */
    public function ajax_save_settings() {
        check_ajax_referer('wap_admin_nonce', 'nonce');

        if (!current_user_can('manage_woocommerce')) {
            wp_send_json_error(array('message' => __('Insufficient permissions', 'wooaccordion-pro')));
        }

        $settings = array(
            'wap_enable_accordion' => isset($_POST['wap_enable_accordion']) ? 'yes' : 'no',
            'wap_auto_expand_first' => isset($_POST['wap_auto_expand_first']) ? 'yes' : 'no',
            'wap_allow_multiple_open' => isset($_POST['wap_allow_multiple_open']) ? 'yes' : 'no',
            'wap_show_icons' => isset($_POST['wap_show_icons']) ? 'yes' : 'no',
            'wap_enable_mobile_gestures' => isset($_POST['wap_enable_mobile_gestures']) ? 'yes' : 'no',
            'wap_template' => sanitize_text_field(wp_unslash($_POST['wap_template'] ?? 'modern')),
            'wap_icon_library' => sanitize_text_field(wp_unslash($_POST['wap_icon_library'] ?? 'css')),
            'wap_toggle_icon_style' => sanitize_text_field(wp_unslash($_POST['wap_toggle_icon_style'] ?? 'plus_minus')),
            'wap_animation_duration' => sanitize_text_field(wp_unslash($_POST['wap_animation_duration'] ?? '300')),
            'wap_header_bg_color' => sanitize_hex_color(wp_unslash($_POST['wap_header_bg_color'] ?? '#f8f9fa')),
            'wap_header_text_color' => sanitize_hex_color(wp_unslash($_POST['wap_header_text_color'] ?? '#495057')),
            'wap_active_header_bg_color' => sanitize_hex_color(wp_unslash($_POST['wap_active_header_bg_color'] ?? '#0073aa')),
            'wap_active_header_text_color' => sanitize_hex_color(wp_unslash($_POST['wap_active_header_text_color'] ?? '#ffffff')),
            'wap_border_color' => sanitize_hex_color(wp_unslash($_POST['wap_border_color'] ?? '#dee2e6'))
        );

        foreach ($settings as $key => $value) {
            update_option($key, $value);
        }

        wp_send_json_success(array('message' => __('Settings saved successfully!', 'wooaccordion-pro')));
    }
    
    
    private function render_wp_editor_in_modal() {
        ?>
        <div class="wap-form-field">
            <label for="wap-tab-content"><?php esc_html_e('Content', 'wooaccordion-pro'); ?></label>

            <div class="wap-simple-editor-wrapper">
                <!-- Simple formatting toolbar -->
                <div class="wap-simple-toolbar">
                    <button type="button" class="wap-format-btn" data-tag="strong" title="Bold">
                        <strong>B</strong>
                    </button>
                    <button type="button" class="wap-format-btn" data-tag="em" title="Italic">
                        <em>I</em>
                    </button>
                    <button type="button" class="wap-format-btn" data-tag="h2" title="Heading 2">
                        H2
                    </button>
                    <button type="button" class="wap-format-btn" data-tag="h3" title="Heading 3">
                        H3
                    </button>
                    <button type="button" class="wap-format-btn" data-tag="ul" title="Bullet List">
                        • List
                    </button>
                    <button type="button" class="wap-format-btn" data-tag="code" title="Code">
                        &lt;/&gt;
                    </button>
                    <button type="button" class="wap-link-btn" title="Insert Link">
                        🔗 Link
                    </button>
                </div>

                <textarea id="wap-tab-content" name="tab_data[content]" rows="10" class="large-text" 
                          placeholder="Enter your tab content here. You can use HTML tags for formatting."></textarea>
            </div>

            <p class="description">
                <?php esc_html_e('Tab content supports HTML. Use the buttons above for quick formatting, or type HTML directly.', 'wooaccordion-pro'); ?>
                <br>
                <?php esc_html_e('Placeholders:', 'wooaccordion-pro'); ?>
                <code>{product_name}</code>, <code>{product_price}</code>, <code>{product_sku}</code>, 
                <code>{product_weight}</code>, <code>{product_dimensions}</code>
            </p>
        </div>
        <?php
    }   
    
    /**
     * Get default colors for templates
     */
    private function get_template_defaults($template = 'modern') {
        $defaults = array(
            'modern' => array(
                'wap_header_bg_color' => '#f8f9fa',
                'wap_header_text_color' => '#495057',
                'wap_active_header_bg_color' => '#0073aa',
                'wap_active_header_text_color' => '#ffffff', // NEW
                'wap_border_color' => '#dee2e6'
            ),
            'minimal' => array(
                'wap_header_bg_color' => '#ffffff',
                'wap_header_text_color' => '#333333',
                'wap_active_header_bg_color' => '#6366f1',
                'wap_active_header_text_color' => '#ffffff', // NEW
                'wap_border_color' => '#e5e7eb'
            ),
            'classic' => array(
                'wap_header_bg_color' => '#f1f1f1',
                'wap_header_text_color' => '#333333',
                'wap_active_header_bg_color' => '#333333',
                'wap_active_header_text_color' => '#ffffff', // NEW
                'wap_border_color' => '#cccccc'
            )
        );

        return $defaults[$template] ?? $defaults['modern'];
    }   
    
    
    
}