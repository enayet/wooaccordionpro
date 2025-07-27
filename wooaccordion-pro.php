<?php
/**
 * Plugin Name: WooAccordion Pro
 * Plugin URI: https://wooaccordionpro.com
 * Description: Transform your WooCommerce product tabs into beautiful mobile-optimized accordions.
 * Version: 1.0.0
 * Author: WooAccordion Pro Team
 * Text Domain: wooaccordion-pro
 * Requires at least: 5.0
 * Tested up to: 6.4
 * Requires PHP: 7.4
 * WC requires at least: 4.0
 * WC tested up to: 8.5
 * License: GPL v2 or later
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('WAP_VERSION', '1.0.1');
define('WAP_PLUGIN_URL', plugin_dir_url(__FILE__));
define('WAP_PLUGIN_PATH', plugin_dir_path(__FILE__));

/**
 * Main WooAccordion Pro Class
 */
final class WooAccordionPro {

    /**
     * Single instance
     */
    protected static $_instance = null;

    /**
     * Main WooAccordionPro Instance
     */
    public static function instance() {
        if (is_null(self::$_instance)) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    /**
     * Constructor
     */
    public function __construct() {
        add_action('init', array($this, 'init'));
        add_action('plugins_loaded', array($this, 'plugins_loaded'));
        
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
    }

    /**
     * Initialize plugin
     */
    public function init() {
        $this->load_plugin_textdomain();
        $this->includes();
    }

    /**
     * Check dependencies
     */
    public function plugins_loaded() {
        if (!class_exists('WooCommerce')) {
            add_action('admin_notices', array($this, 'woocommerce_missing_notice'));
            return;
        }
    }

    /**
     * Load text domain
     */
    public function load_plugin_textdomain() {
        load_plugin_textdomain('wooaccordion-pro', false, plugin_basename(dirname(__FILE__)) . '/languages');
    }

    /**
     * Include required files
     */
    public function includes() {
        include_once WAP_PLUGIN_PATH . 'includes/class-wap-settings.php';
        include_once WAP_PLUGIN_PATH . 'includes/class-wap-frontend.php';
        include_once WAP_PLUGIN_PATH . 'includes/class-wap-custom-tabs.php';

        // Initialize classes
        if (is_admin()) {
            WAP_Settings::instance();
        }
        WAP_Frontend::instance();
        WAP_Custom_Tabs::instance();
    }

    /**
     * Plugin activation
     */
    public function activate() {
        // Set default options
        $defaults = array(
            'wap_enable_accordion' => 'yes',
            'wap_auto_expand_first' => 'yes',
            'wap_allow_multiple_open' => 'no',
            'wap_template' => 'modern',
            'wap_header_bg_color' => '#f8f9fa',
            'wap_header_text_color' => '#495057',
            'wap_active_header_bg_color' => '#0073aa',
            'wap_border_color' => '#dee2e6'
        );

        foreach ($defaults as $key => $value) {
            if (get_option($key) === false) {
                add_option($key, $value);
            }
        }
    }

    /**
     * Plugin deactivation
     */
    public function deactivate() {
        // Clean up if needed
    }

    /**
     * WooCommerce missing notice
     */
    public function woocommerce_missing_notice() {
        echo '<div class="error"><p><strong>' . 
             sprintf(__('WooAccordion Pro requires WooCommerce to be installed and active. You can download %s here.', 'wooaccordion-pro'), 
             '<a href="https://woocommerce.com/" target="_blank">WooCommerce</a>') . 
             '</strong></p></div>';
    }
}

/**
 * Initialize plugin
 */
function WAP() {
    return WooAccordionPro::instance();
}

// Start the plugin
WAP();