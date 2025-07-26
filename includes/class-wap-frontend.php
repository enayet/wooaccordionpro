<?php
/**
 * WooAccordion Pro Frontend Class
 * 
 * Handles frontend accordion rendering and functionality
 * 
 * @class WAP_Frontend
 * @version 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class WAP_Frontend {

    /**
     * Single instance of the class
     * @var WAP_Frontend
     */
    protected static $_instance = null;

    /**
     * Main WAP_Frontend Instance
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
        $this->init_hooks();
    }

    /**
     * Initialize hooks
     */
    private function init_hooks() {
        // Only proceed if accordion is enabled
        if (get_option('wap_enable_accordion') !== 'yes') {
            return;
        }

        // Replace WooCommerce product tabs with accordions
        add_filter('woocommerce_product_tabs', array($this, 'replace_product_tabs'), 98);
        
        // Enqueue scripts and styles
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        
        // Add accordion HTML output
        add_action('woocommerce_output_product_data_tabs', array($this, 'output_accordion_html'));
        
        // Remove default tab styles
        add_action('wp_head', array($this, 'remove_default_tab_styles'));
        
        // Add custom accordion styles
        add_action('wp_head', array($this, 'add_custom_accordion_styles'));
    }

    /**
     * Replace WooCommerce product tabs with accordion data
     */
    public function replace_product_tabs($tabs) {
        if (!$this->should_show_accordions()) {
            return $tabs;
        }

        // Store original tabs for accordion conversion
        global $wap_accordion_tabs;
        $wap_accordion_tabs = $tabs;

        // Return empty array to prevent default tab output
        return array();
    }

    /**
     * Check if accordions should be shown based on conditions
     */
    private function should_show_accordions() {
        global $product;

        if (!$product || !is_product()) {
            return false;
        }

        // Check conditional logic
        $conditional_enabled = get_option('wap_enable_conditional_logic', 'no');
        
        if ($conditional_enabled === 'yes') {
            return $this->check_conditional_rules($product);
        }

        return true;
    }

    /**
     * Check conditional display rules
     */
    private function check_conditional_rules($product) {
        // Category-based rules
        $category_rules = get_option('wap_category_rules', array());
        if (!empty($category_rules)) {
            $product_categories = wp_get_post_terms($product->get_id(), 'product_cat', array('fields' => 'slugs'));
            foreach ($category_rules as $rule) {
                if (in_array($rule['category'], $product_categories)) {
                    return $rule['show_accordion'] === 'yes';
                }
            }
        }

        // User role-based rules
        $user_role_rules = get_option('wap_user_role_rules', array());
        if (!empty($user_role_rules)) {
            $current_user = wp_get_current_user();
            $user_roles = $current_user->roles;
            
            foreach ($user_role_rules as $rule) {
                if (in_array($rule['role'], $user_roles)) {
                    return $rule['show_accordion'] === 'yes';
                }
            }
        }

        return true;
    }

    /**
     * Enqueue frontend scripts and styles
     */
    public function enqueue_scripts() {
        if (!is_product() || !$this->should_show_accordions()) {
            return;
        }

        // Enqueue CSS
        wp_enqueue_style(
            'wap-frontend-css',
            WAP_PLUGIN_URL . 'assets/css/frontend.css',
            array(),
            WAP_VERSION
        );

        // Enqueue FontAwesome if enabled
        if (get_option('wap_icon_library') === 'fontawesome') {
            wp_enqueue_style(
                'wap-fontawesome',
                'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css',
                array(),
                '6.0.0'
            );
        }

        // Enqueue JavaScript
        wp_enqueue_script(
            'wap-frontend-js',
            WAP_PLUGIN_URL . 'assets/js/frontend.js',
            array(),
            WAP_VERSION,
            true
        );

        // Localize script with settings
        wp_localize_script('wap-frontend-js', 'wap_frontend', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('wap_frontend_nonce'),
            'settings' => array(
                'animation_type' => get_option('wap_animation_type', 'slide'),
                'animation_duration' => get_option('wap_animation_duration', '300'),
                'auto_expand_first' => get_option('wap_auto_expand_first', 'yes'),
                'allow_multiple_open' => get_option('wap_allow_multiple_open', 'no'),
                'enable_touch_gestures' => get_option('wap_enable_touch_gestures', 'yes'),
                'enable_analytics' => get_option('wap_enable_analytics', 'yes'),
                'product_id' => get_the_ID()
            )
        ));
    }

    /**
     * Output accordion HTML
     */
    public function output_accordion_html() {
        global $wap_accordion_tabs;

        if (empty($wap_accordion_tabs) || !$this->should_show_accordions()) {
            return;
        }

        $layout_template = get_option('wap_layout_template', 'modern-card');
        $show_icons = get_option('wap_show_icons', 'yes') === 'yes';

        echo '<div class="wap-accordion-container wap-template-' . esc_attr($layout_template) . '">';
        echo '<div class="wap-accordion" data-layout="' . esc_attr($layout_template) . '">';

        $index = 0;
        foreach ($wap_accordion_tabs as $key => $tab) {
            if (!isset($tab['callback'])) {
                continue;
            }

            $is_first = $index === 0;
            $auto_expand = get_option('wap_auto_expand_first', 'yes') === 'yes' && $is_first;

            echo '<div class="wap-accordion-item" data-section="' . esc_attr($key) . '">';
            
            // Accordion Header
            echo '<div class="wap-accordion-header' . ($auto_expand ? ' wap-active' : '') . '" data-target="' . esc_attr($key) . '">';
            
            if ($show_icons) {
                $icon = $this->get_accordion_icon($key);
                echo '<span class="wap-accordion-icon">' . $icon . '</span>';
            }
            
            echo '<span class="wap-accordion-title">' . esc_html($tab['title']) . '</span>';
            echo '<span class="wap-accordion-toggle">';
            echo $auto_expand ? $this->get_collapse_icon() : $this->get_expand_icon();
            echo '</span>';
            echo '</div>';

            // Accordion Content
            echo '<div class="wap-accordion-content' . ($auto_expand ? ' wap-active' : '') . '" id="wap-accordion-' . esc_attr($key) . '">';
            echo '<div class="wap-accordion-content-inner">';
            
            // Get content from tab callback
            ob_start();
            call_user_func($tab['callback'], $key, $tab);
            $content = ob_get_clean();
            
            echo $content;
            echo '</div>';
            echo '</div>';
            
            echo '</div>';
            $index++;
        }

        echo '</div>';
        echo '</div>';
    }

    /**
     * Get accordion icon based on section
     */
    private function get_accordion_icon($section) {
        $icons = array(
            'description' => '<i class="fas fa-align-left"></i>',
            'reviews' => '<i class="fas fa-star"></i>',
            'additional_information' => '<i class="fas fa-info-circle"></i>',
            'shipping' => '<i class="fas fa-truck"></i>',
            'size_guide' => '<i class="fas fa-ruler"></i>',
            'warranty' => '<i class="fas fa-shield-alt"></i>',
            'care_instructions' => '<i class="fas fa-heart"></i>'
        );

        return isset($icons[$section]) ? $icons[$section] : '<i class="fas fa-file-alt"></i>';
    }

    /**
     * Get expand icon
     */
    private function get_expand_icon() {
        return '<i class="fas fa-plus"></i>';
    }

    /**
     * Get collapse icon
     */
    private function get_collapse_icon() {
        return '<i class="fas fa-minus"></i>';
    }

    /**
     * Remove default WooCommerce tab styles
     */
    public function remove_default_tab_styles() {
        if (!is_product() || !$this->should_show_accordions()) {
            return;
        }

        echo '<style type="text/css">
            .woocommerce-tabs {
                display: none !important;
            }
            .wc-tabs-wrapper {
                display: none !important;
            }
        </style>';
    }

    /**
     * Add custom accordion styles based on settings
     */
    public function add_custom_accordion_styles() {
        if (!is_product() || !$this->should_show_accordions()) {
            return;
        }

        $header_bg = get_option('wap_header_bg_color', '#f8f9fa');
        $header_text = get_option('wap_header_text_color', '#495057');
        $active_header_bg = get_option('wap_active_header_bg_color', '#6366f1');
        $active_header_text = get_option('wap_active_header_text_color', '#ffffff');
        $content_bg = get_option('wap_content_bg_color', '#ffffff');
        $border_color = get_option('wap_border_color', '#dee2e6');
        $animation_duration = get_option('wap_animation_duration', '300');

        echo '<style type="text/css">
            :root {
                --wap-header-bg: ' . esc_attr($header_bg) . ';
                --wap-header-text: ' . esc_attr($header_text) . ';
                --wap-active-header-bg: ' . esc_attr($active_header_bg) . ';
                --wap-active-header-text: ' . esc_attr($active_header_text) . ';
                --wap-content-bg: ' . esc_attr($content_bg) . ';
                --wap-border-color: ' . esc_attr($border_color) . ';
                --wap-animation-duration: ' . esc_attr($animation_duration) . 'ms;
            }
        </style>';
    }

    /**
     * AJAX handler for accordion analytics
     */
    public function handle_accordion_analytics() {
        check_ajax_referer('wap_frontend_nonce', 'nonce');

        $product_id = intval($_POST['product_id']);
        $section = sanitize_text_field($_POST['section']);
        $action = sanitize_text_field($_POST['action']);

        if ($product_id && $section && $action) {
            WAP_Analytics::instance()->track_interaction($product_id, $section, $action);
        }

        wp_die();
    }
}

// Initialize AJAX handlers
add_action('wp_ajax_wap_track_interaction', array('WAP_Frontend', 'handle_accordion_analytics'));
add_action('wp_ajax_nopriv_wap_track_interaction', array('WAP_Frontend', 'handle_accordion_analytics'));