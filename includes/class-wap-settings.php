<?php
/**
 * WooAccordion Pro Settings Class
 * 
 * Premium admin settings with clean UI/UX
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
    }
    /**
     * Save settings (traditional form submission)
     */
    private function save_settings() {
        $settings = array(
            'wap_enable_accordion' => isset($_POST['wap_enable_accordion']) ? 'yes' : 'no',
            'wap_auto_expand_first' => isset($_POST['wap_auto_expand_first']) ? 'yes' : 'no',
            'wap_allow_multiple_open' => isset($_POST['wap_allow_multiple_open']) ? 'yes' : 'no',
            'wap_show_icons' => isset($_POST['wap_show_icons']) ? 'yes' : 'no',
            'wap_enable_mobile_gestures' => isset($_POST['wap_enable_mobile_gestures']) ? 'yes' : 'no',
            'wap_template' => sanitize_text_field($_POST['wap_template'] ?? 'modern'),
            'wap_icon_library' => sanitize_text_field($_POST['wap_icon_library'] ?? 'css'),
            'wap_animation_duration' => sanitize_text_field($_POST['wap_animation_duration'] ?? '300'),
            'wap_header_bg_color' => sanitize_hex_color($_POST['wap_header_bg_color'] ?? '#f8f9fa'),
            'wap_header_text_color' => sanitize_hex_color($_POST['wap_header_text_color'] ?? '#495057'),
            'wap_active_header_bg_color' => sanitize_hex_color($_POST['wap_active_header_bg_color'] ?? '#0073aa'),
            'wap_border_color' => sanitize_hex_color($_POST['wap_border_color'] ?? '#dee2e6')
        );

        foreach ($settings as $key => $value) {
            update_option($key, $value);
        }
    
        return self::$_instance;
    }

    public function __construct() {
        add_action('admin_menu', array($this, 'admin_menu'));
        add_action('admin_init', array($this, 'admin_init'));
        add_action('admin_enqueue_scripts', array($this, 'admin_scripts'));
        add_action('wp_ajax_wap_save_settings', array($this, 'ajax_save_settings'));
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
        register_setting('wap_settings', 'wap_enable_accordion');
        register_setting('wap_settings', 'wap_auto_expand_first');
        register_setting('wap_settings', 'wap_allow_multiple_open');
        register_setting('wap_settings', 'wap_template');
        register_setting('wap_settings', 'wap_show_icons');
        register_setting('wap_settings', 'wap_icon_library');
        register_setting('wap_settings', 'wap_header_bg_color');
        register_setting('wap_settings', 'wap_header_text_color');
        register_setting('wap_settings', 'wap_active_header_bg_color');
        register_setting('wap_settings', 'wap_border_color');
        register_setting('wap_settings', 'wap_animation_duration');
        register_setting('wap_settings', 'wap_enable_mobile_gestures');
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
     * Premium admin page with tabbed interface
     */
    public function admin_page() {
        // Handle traditional form submission as fallback
        if (isset($_POST['submit']) && wp_verify_nonce($_POST['wap_nonce'], 'wap_settings_save')) {
            $this->save_settings();
            echo '<div class="notice notice-success"><p>' . __('Settings saved successfully!', 'wooaccordion-pro') . '</p></div>';
        }
        ?>
        <div class="wrap wap-admin-wrap">
            <div class="wap-admin-header">
                <div class="wap-admin-header-content">
                    <h2><?php _e('Transform Your Product Tabs Into Beautiful Accordions', 'wooaccordion-pro'); ?></h2>
                    <p><?php _e('Configure your accordion settings below to create a better mobile shopping experience.', 'wooaccordion-pro'); ?></p>
                </div>
                <div class="wap-admin-header-stats">
                    <div class="wap-stat">
                        <span class="wap-stat-number">40%</span>
                        <span class="wap-stat-label"><?php _e('Higher Mobile Conversions', 'wooaccordion-pro'); ?></span>
                    </div>
                    <div class="wap-stat">
                        <span class="wap-stat-number">75%</span>
                        <span class="wap-stat-label"><?php _e('Of Traffic is Mobile', 'wooaccordion-pro'); ?></span>
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
                                    <?php _e('General', 'wooaccordion-pro'); ?>
                                </button>
                                <button type="button" class="wap-tab-button" data-tab="styling">
                                    <?php _e('Styling', 'wooaccordion-pro'); ?>
                                </button>
                                <button type="button" class="wap-tab-button" data-tab="mobile">
                                    <?php _e('Mobile', 'wooaccordion-pro'); ?>
                                </button>
                            </nav>

                            <div class="wap-tab-content">
                                <!-- General Tab -->
                                <div class="wap-tab-panel active" data-panel="general">
                                    <h3><?php _e('General Settings', 'wooaccordion-pro'); ?></h3>
                                    <p><?php _e('Configure basic accordion functionality.', 'wooaccordion-pro'); ?></p>

                                    <div class="wap-form-field">
                                        <label for="wap_enable_accordion"><?php _e('Enable Accordions', 'wooaccordion-pro'); ?></label>
                                        <input type="checkbox" id="wap_enable_accordion" name="wap_enable_accordion" value="yes" 
                                               <?php checked(get_option('wap_enable_accordion'), 'yes'); ?> />
                                        <p class="description"><?php _e('Replace WooCommerce product tabs with accordions', 'wooaccordion-pro'); ?></p>
                                    </div>

                                    <div class="wap-form-field">
                                        <label for="wap_auto_expand_first"><?php _e('Auto Expand First', 'wooaccordion-pro'); ?></label>
                                        <input type="checkbox" id="wap_auto_expand_first" name="wap_auto_expand_first" value="yes" 
                                               <?php checked(get_option('wap_auto_expand_first'), 'yes'); ?> />
                                        <p class="description"><?php _e('Automatically expand the first accordion item when page loads', 'wooaccordion-pro'); ?></p>
                                    </div>

                                    <div class="wap-form-field">
                                        <label for="wap_allow_multiple_open"><?php _e('Allow Multiple Open', 'wooaccordion-pro'); ?></label>
                                        <input type="checkbox" id="wap_allow_multiple_open" name="wap_allow_multiple_open" value="yes" 
                                               <?php checked(get_option('wap_allow_multiple_open'), 'yes'); ?> />
                                        <p class="description"><?php _e('Allow multiple accordion items to be open simultaneously', 'wooaccordion-pro'); ?></p>
                                    </div>

                                    <div class="wap-form-field">
                                        <label for="wap_show_icons"><?php _e('Show Icons', 'wooaccordion-pro'); ?></label>
                                        <input type="checkbox" id="wap_show_icons" name="wap_show_icons" value="yes" 
                                               <?php checked(get_option('wap_show_icons', 'yes'), 'yes'); ?> />
                                        <p class="description"><?php _e('Display icons next to accordion headers', 'wooaccordion-pro'); ?></p>
                                    </div>

                                    <div class="wap-form-field">
                                        <label for="wap_icon_library"><?php _e('Icon Library', 'wooaccordion-pro'); ?></label>
                                        <select id="wap_icon_library" name="wap_icon_library">
                                            <option value="css" <?php selected(get_option('wap_icon_library', 'css'), 'css'); ?>>
                                                <?php _e('CSS Icons (Lightweight)', 'wooaccordion-pro'); ?>
                                            </option>
                                            <option value="fontawesome" <?php selected(get_option('wap_icon_library'), 'fontawesome'); ?>>
                                                <?php _e('FontAwesome', 'wooaccordion-pro'); ?>
                                            </option>
                                        </select>
                                        <p class="description"><?php _e('Choose which icon library to use', 'wooaccordion-pro'); ?></p>
                                    </div>

                                    <div class="wap-form-field">
                                        <label for="wap_animation_duration"><?php _e('Animation Speed', 'wooaccordion-pro'); ?></label>
                                        <select id="wap_animation_duration" name="wap_animation_duration">
                                            <option value="200" <?php selected(get_option('wap_animation_duration', '300'), '200'); ?>>
                                                <?php _e('Fast (200ms)', 'wooaccordion-pro'); ?>
                                            </option>
                                            <option value="300" <?php selected(get_option('wap_animation_duration', '300'), '300'); ?>>
                                                <?php _e('Normal (300ms)', 'wooaccordion-pro'); ?>
                                            </option>
                                            <option value="500" <?php selected(get_option('wap_animation_duration', '300'), '500'); ?>>
                                                <?php _e('Slow (500ms)', 'wooaccordion-pro'); ?>
                                            </option>
                                        </select>
                                        <p class="description"><?php _e('How fast accordions open and close', 'wooaccordion-pro'); ?></p>
                                    </div>
                                </div>

                                <!-- Styling Tab -->
                                <div class="wap-tab-panel" data-panel="styling">
                                    <h3><?php _e('Styling Options', 'wooaccordion-pro'); ?></h3>
                                    <p><?php _e('Customize the appearance of your accordions.', 'wooaccordion-pro'); ?></p>

                                    <div class="wap-form-field">
                                        <label for="wap_template"><?php _e('Template Style', 'wooaccordion-pro'); ?></label>
                                        <select id="wap_template" name="wap_template">
                                            <option value="modern" <?php selected(get_option('wap_template'), 'modern'); ?>>
                                                <?php _e('Modern Card', 'wooaccordion-pro'); ?>
                                            </option>
                                            <option value="minimal" <?php selected(get_option('wap_template'), 'minimal'); ?>>
                                                <?php _e('Minimal Flat', 'wooaccordion-pro'); ?>
                                            </option>
                                            <option value="classic" <?php selected(get_option('wap_template'), 'classic'); ?>>
                                                <?php _e('Classic', 'wooaccordion-pro'); ?>
                                            </option>
                                        </select>
                                        <p class="description"><?php _e('Choose a pre-designed layout template', 'wooaccordion-pro'); ?></p>
                                    </div>

                                    <div class="wap-form-field">
                                        <label for="wap_header_bg_color"><?php _e('Header Background', 'wooaccordion-pro'); ?></label>
                                        <input type="color" id="wap_header_bg_color" name="wap_header_bg_color" 
                                               value="<?php echo esc_attr(get_option('wap_header_bg_color', '#f8f9fa')); ?>" />
                                        <p class="description"><?php _e('Background color for accordion headers', 'wooaccordion-pro'); ?></p>
                                    </div>

                                    <div class="wap-form-field">
                                        <label for="wap_header_text_color"><?php _e('Header Text Color', 'wooaccordion-pro'); ?></label>
                                        <input type="color" id="wap_header_text_color" name="wap_header_text_color" 
                                               value="<?php echo esc_attr(get_option('wap_header_text_color', '#495057')); ?>" />
                                        <p class="description"><?php _e('Text color for accordion headers', 'wooaccordion-pro'); ?></p>
                                    </div>

                                    <div class="wap-form-field">
                                        <label for="wap_active_header_bg_color"><?php _e('Active Header Background', 'wooaccordion-pro'); ?></label>
                                        <input type="color" id="wap_active_header_bg_color" name="wap_active_header_bg_color" 
                                               value="<?php echo esc_attr(get_option('wap_active_header_bg_color', '#0073aa')); ?>" />
                                        <p class="description"><?php _e('Background color for active/expanded accordion headers', 'wooaccordion-pro'); ?></p>
                                    </div>

                                    <div class="wap-form-field">
                                        <label for="wap_border_color"><?php _e('Border Color', 'wooaccordion-pro'); ?></label>
                                        <input type="color" id="wap_border_color" name="wap_border_color" 
                                               value="<?php echo esc_attr(get_option('wap_border_color', '#dee2e6')); ?>" />
                                        <p class="description"><?php _e('Border color for accordion items', 'wooaccordion-pro'); ?></p>
                                    </div>
                                </div>

                                <!-- Mobile Tab -->
                                <div class="wap-tab-panel" data-panel="mobile">
                                    <h3><?php _e('Mobile Settings', 'wooaccordion-pro'); ?></h3>
                                    <p><?php _e('Optimize accordion behavior for mobile devices.', 'wooaccordion-pro'); ?></p>

                                    <div class="wap-form-field">
                                        <label for="wap_enable_mobile_gestures"><?php _e('Enable Touch Gestures', 'wooaccordion-pro'); ?></label>
                                        <input type="checkbox" id="wap_enable_mobile_gestures" name="wap_enable_mobile_gestures" value="yes" 
                                               <?php checked(get_option('wap_enable_mobile_gestures', 'yes'), 'yes'); ?> />
                                        <p class="description"><?php _e('Allow swipe gestures to expand/collapse accordions on mobile', 'wooaccordion-pro'); ?></p>
                                    </div>

                                    <div class="wap-notice wap-notice-info">
                                        <h4><?php _e('Mobile Optimization Features', 'wooaccordion-pro'); ?></h4>
                                        <ul>
                                            <li><?php _e('Automatic touch-friendly spacing', 'wooaccordion-pro'); ?></li>
                                            <li><?php _e('Responsive design for all screen sizes', 'wooaccordion-pro'); ?></li>
                                            <li><?php _e('Optimized animations for mobile performance', 'wooaccordion-pro'); ?></li>
                                            <li><?php _e('Large touch targets for better usability', 'wooaccordion-pro'); ?></li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="wap-form-actions">
                            <input type="submit" name="submit" id="submit" class="button-primary" value="<?php _e('Save Settings', 'wooaccordion-pro'); ?>" />
                            <button type="button" class="button button-secondary" id="wap-reset-settings">
                                <?php _e('Reset to Defaults', 'wooaccordion-pro'); ?>
                            </button>
                        </div>
                    </form>
                </div>

                <div class="wap-admin-sidebar">
                    <div class="wap-admin-box">
                        <h3><?php _e('Current Status', 'wooaccordion-pro'); ?></h3>
                        <div class="wap-status-item">
                            <span class="wap-status-label"><?php _e('Accordions:', 'wooaccordion-pro'); ?></span>
                            <span class="wap-status-value <?php echo get_option('wap_enable_accordion') === 'yes' ? 'enabled' : 'disabled'; ?>">
                                <?php echo get_option('wap_enable_accordion') === 'yes' ? __('Enabled', 'wooaccordion-pro') : __('Disabled', 'wooaccordion-pro'); ?>
                            </span>
                        </div>
                        <div class="wap-status-item">
                            <span class="wap-status-label"><?php _e('Template:', 'wooaccordion-pro'); ?></span>
                            <span class="wap-status-value"><?php echo esc_html(ucfirst(get_option('wap_template', 'modern'))); ?></span>
                        </div>
                        <div class="wap-status-item">
                            <span class="wap-status-label"><?php _e('Mobile Gestures:', 'wooaccordion-pro'); ?></span>
                            <span class="wap-status-value <?php echo get_option('wap_enable_mobile_gestures', 'yes') === 'yes' ? 'enabled' : 'disabled'; ?>">
                                <?php echo get_option('wap_enable_mobile_gestures', 'yes') === 'yes' ? __('Enabled', 'wooaccordion-pro') : __('Disabled', 'wooaccordion-pro'); ?>
                            </span>
                        </div>
                    </div>

                    <div class="wap-admin-box">
                        <h3><?php _e('Quick Links', 'wooaccordion-pro'); ?></h3>
                        <ul>
                            <li><a href="<?php echo admin_url('edit.php?post_type=product'); ?>"><?php _e('View Products', 'wooaccordion-pro'); ?></a></li>
                            <li><a href="<?php echo esc_url('https://wooaccordionpro.com/docs/'); ?>" target="_blank"><?php _e('Documentation', 'wooaccordion-pro'); ?></a></li>
                            <li><a href="<?php echo esc_url('https://wooaccordionpro.com/support/'); ?>" target="_blank"><?php _e('Get Support', 'wooaccordion-pro'); ?></a></li>
                        </ul>
                    </div>

                    <div class="wap-admin-box wap-upgrade-box">
                        <h3><?php _e('Need Help?', 'wooaccordion-pro'); ?></h3>
                        <p><?php _e('Check out our comprehensive documentation or contact our support team.', 'wooaccordion-pro'); ?></p>
                        <a href="<?php echo esc_url('https://wooaccordionpro.com/support/'); ?>" target="_blank" class="button button-primary">
                            <?php _e('Get Support', 'wooaccordion-pro'); ?>
                        </a>
                    </div>
                </div>
            </div>
        </div>
        <?php
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
            'wap_template' => sanitize_text_field($_POST['wap_template']),
            'wap_icon_library' => sanitize_text_field($_POST['wap_icon_library']),
            'wap_animation_duration' => sanitize_text_field($_POST['wap_animation_duration']),
            'wap_header_bg_color' => sanitize_hex_color($_POST['wap_header_bg_color']),
            'wap_header_text_color' => sanitize_hex_color($_POST['wap_header_text_color']),
            'wap_active_header_bg_color' => sanitize_hex_color($_POST['wap_active_header_bg_color']),
            'wap_border_color' => sanitize_hex_color($_POST['wap_border_color'])
        );

        foreach ($settings as $key => $value) {
            update_option($key, $value);
        }

        wp_send_json_success(array('message' => __('Settings saved successfully!', 'wooaccordion-pro')));
    }
}