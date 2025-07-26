<?php
/**
 * Plugin Name: WooAccordion Pro
 * Plugin URI: https://wooaccordionpro.com
 * Description: Transform your WooCommerce product tabs into beautiful, mobile-optimized accordions with animations, conditional logic, and analytics.
 * Version: 1.0.0
 * Author: WooAccordion Pro Team
 * Author URI: https://wooaccordionpro.com
 * Text Domain: wooaccordion-pro
 * Domain Path: /languages
 * Requires at least: 5.0
 * Tested up to: 6.4
 * Requires PHP: 7.4
 * WC requires at least: 4.0
 * WC tested up to: 8.5
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('WAP_VERSION', '1.0.0');
define('WAP_PLUGIN_URL', plugin_dir_url(__FILE__));
define('WAP_PLUGIN_PATH', plugin_dir_path(__FILE__));
define('WAP_PLUGIN_BASENAME', plugin_basename(__FILE__));

/**
 * Main WooAccordion Pro Class
 * 
 * @class WooAccordionPro
 * @version 1.0.0
 */
final class WooAccordionPro {

    /**
     * The single instance of the class.
     * @var WooAccordionPro
     */
    protected static $_instance = null;

    /**
     * Main WooAccordionPro Instance.
     * 
     * Ensures only one instance of WooAccordionPro is loaded or can be loaded.
     * 
     * @static
     * @return WooAccordionPro - Main instance.
     */
    public static function instance() {
        if (is_null(self::$_instance)) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    /**
     * WooAccordionPro Constructor.
     */
    public function __construct() {
        $this->init_hooks();
    }

    /**
     * Hook into actions and filters.
     */
    private function init_hooks() {
        add_action('init', array($this, 'init'), 0);
        add_action('plugins_loaded', array($this, 'plugins_loaded'));
        
        // Activation and deactivation hooks
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
    }

    /**
     * Init WooAccordionPro when WordPress Initialises.
     */
    public function init() {
        // Before init action
        do_action('wooaccordion_pro_before_init');

        // Set up localisation
        $this->load_plugin_textdomain();

        // Load classes
        $this->includes();

        // Init action
        do_action('wooaccordion_pro_init');
    }

    /**
     * When WP has loaded all plugins, trigger the `wooaccordion_pro_loaded` hook.
     */
    public function plugins_loaded() {
        // Check if WooCommerce is active
        if (!$this->is_woocommerce_active()) {
            add_action('admin_notices', array($this, 'woocommerce_missing_notice'));
            return;
        }

        do_action('wooaccordion_pro_loaded');
    }

    /**
     * Load Localisation files.
     */
    public function load_plugin_textdomain() {
        $locale = determine_locale();
        $locale = apply_filters('plugin_locale', $locale, 'wooaccordion-pro');

        unload_textdomain('wooaccordion-pro');
        load_textdomain('wooaccordion-pro', WP_LANG_DIR . '/wooaccordion-pro/wooaccordion-pro-' . $locale . '.mo');
        load_plugin_textdomain('wooaccordion-pro', false, plugin_basename(dirname(__FILE__)) . '/languages');
    }

    /**
     * Include required core files.
     */
    public function includes() {
        // Core includes
        include_once WAP_PLUGIN_PATH . 'includes/class-wap-settings.php';
        include_once WAP_PLUGIN_PATH . 'includes/class-wap-frontend.php';
        include_once WAP_PLUGIN_PATH . 'includes/class-wap-analytics.php';
        include_once WAP_PLUGIN_PATH . 'includes/class-wap-custom-fields.php';

        // Initialize classes
        if (is_admin()) {
            WAP_Settings::instance();
        }

        if (!is_admin() || wp_doing_ajax()) {
            WAP_Frontend::instance();
        }
        WAP_Custom_Fields::instance();
        WAP_Analytics::instance();
    }

    /**
     * Check if WooCommerce is active
     */
    private function is_woocommerce_active() {
        return class_exists('WooCommerce');
    }

    /**
     * WooCommerce missing notice.
     */
    public function woocommerce_missing_notice() {
        echo '<div class="error"><p><strong>' . sprintf(esc_html__('WooAccordion Pro requires WooCommerce to be installed and active. You can download %s here.', 'wooaccordion-pro'), '<a href="https://woocommerce.com/" target="_blank">WooCommerce</a>') . '</strong></p></div>';
    }

    /**
     * Plugin activation.
     */
    public function activate() {
        // Set default options
        $default_options = array(
            'enable_accordion' => 'yes',
            'animation_type' => 'slide',
            'animation_duration' => '300',
            'auto_expand_first' => 'yes',
            'allow_multiple_open' => 'no',
            'show_icons' => 'yes',
            'icon_library' => 'fontawesome',
            'header_bg_color' => '#f8f9fa',
            'header_text_color' => '#495057',
            'active_header_bg_color' => '#6366f1',
            'active_header_text_color' => '#ffffff',
            'content_bg_color' => '#ffffff',
            'border_color' => '#dee2e6',
            'layout_template' => 'modern-card',
            'enable_touch_gestures' => 'yes',
            'enable_analytics' => 'yes'
        );

        // Add default options if they don't exist
        foreach ($default_options as $key => $value) {
            if (get_option('wap_' . $key) === false) {
                add_option('wap_' . $key, $value);
            }
        }

        // Create analytics table
        $this->create_analytics_table();

        // Set activation flag
        add_option('wap_activation_time', current_time('mysql'));
        add_option('wap_version', WAP_VERSION);
    }

    /**
     * Plugin deactivation.
     */
    public function deactivate() {
        // Clean up scheduled events if any
        wp_clear_scheduled_hook('wap_cleanup_analytics');
    }

    /**
     * Create analytics table.
     */
    private function create_analytics_table() {
        global $wpdb;

        $table_name = $wpdb->prefix . 'wap_analytics';

        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            product_id bigint(20) NOT NULL,
            accordion_section varchar(100) NOT NULL,
            action varchar(50) NOT NULL,
            user_agent varchar(255) DEFAULT '',
            device_type varchar(50) DEFAULT '',
            timestamp datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY product_id (product_id),
            KEY accordion_section (accordion_section),
            KEY timestamp (timestamp)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    /**
     * Get the plugin url.
     * @return string
     */
    public function plugin_url() {
        return untrailingslashit(plugins_url('/', __FILE__));
    }

    /**
     * Get the plugin path.
     * @return string
     */
    public function plugin_path() {
        return untrailingslashit(plugin_dir_path(__FILE__));
    }

    /**
     * Get Ajax URL.
     * @return string
     */
    public function ajax_url() {
        return admin_url('admin-ajax.php', 'relative');
    }

    /**
     * Get plugin version.
     * @return string
     */
    public function get_version() {
        return WAP_VERSION;
    }
}

/**
 * Main instance of WooAccordionPro.
 * 
 * Returns the main instance of WAP to prevent the need to use globals.
 * 
 * @return WooAccordionPro
 */
function WAP() {
    return WooAccordionPro::instance();
}

// Global for backwards compatibility.
$GLOBALS['wooaccordion_pro'] = WAP();