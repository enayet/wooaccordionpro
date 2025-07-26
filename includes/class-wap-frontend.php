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
        add_action('woocommerce_after_single_product_summary', array($this, 'output_accordion_html'), 20);
        
        // Remove default tab styles
        add_action('wp_head', array($this, 'remove_default_tab_styles'));
        
        // Add custom accordion styles
        add_action('wp_head', array($this, 'add_custom_accordion_styles'));
        
        // Add analytics AJAX handlers
        add_action('wp_ajax_wap_track_interaction', array($this, 'handle_accordion_analytics'));
        add_action('wp_ajax_nopriv_wap_track_interaction', array($this, 'handle_accordion_analytics'));
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

        // Debug: Log that we're enqueueing scripts
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('WAP Frontend: Enqueuing scripts for product page');
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
            array('jquery'),
            WAP_VERSION,
            true
        );

        // Pass ALL settings to frontend
        wp_localize_script('wap-frontend-js', 'wap_frontend', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('wap_frontend_nonce'),
            'settings' => $this->get_all_frontend_settings(),
            'debug' => defined('WP_DEBUG') && WP_DEBUG ? true : false
        ));

        // Debug: Log the settings being passed
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('WAP Frontend: Settings passed to JS: ' . print_r($this->get_all_frontend_settings(), true));
        }
    }
    
    /**
     * Get all frontend settings
     */
    private function get_all_frontend_settings() {
        return array(
            // General Settings
            'enable_accordion' => get_option('wap_enable_accordion', 'yes'),
            'auto_expand_first' => get_option('wap_auto_expand_first', 'yes'),
            'allow_multiple_open' => get_option('wap_allow_multiple_open', 'no'),
            'show_icons' => get_option('wap_show_icons', 'yes'),
            'icon_library' => get_option('wap_icon_library', 'fontawesome'),

            // Styling Settings
            'layout_template' => get_option('wap_layout_template', 'modern-card'),
            'header_bg_color' => get_option('wap_header_bg_color', '#f8f9fa'),
            'header_text_color' => get_option('wap_header_text_color', '#495057'),
            'active_header_bg_color' => get_option('wap_active_header_bg_color', '#6366f1'),
            'active_header_text_color' => get_option('wap_active_header_text_color', '#ffffff'),
            'content_bg_color' => get_option('wap_content_bg_color', '#ffffff'),
            'border_color' => get_option('wap_border_color', '#dee2e6'),
            'header_hover_effect' => get_option('wap_header_hover_effect', 'none'),

            // Animation Settings
            'animation_type' => get_option('wap_animation_type', 'slide'),
            'animation_duration' => get_option('wap_animation_duration', '300'),
            'animation_easing' => get_option('wap_animation_easing', 'ease'),
            'enable_stagger' => get_option('wap_enable_stagger', 'no'),
            'stagger_delay' => get_option('wap_stagger_delay', '50'),
            'toggle_icon_style' => get_option('wap_toggle_icon_style', 'plus-minus'),
            'custom_toggle_collapsed' => get_option('wap_custom_toggle_collapsed', 'fas fa-plus'),
            'custom_toggle_expanded' => get_option('wap_custom_toggle_expanded', 'fas fa-minus'),

            // Mobile Settings
            'enable_touch_gestures' => get_option('wap_enable_touch_gestures', 'yes'),
            'sticky_navigation' => get_option('wap_sticky_navigation', 'no'),
            'progressive_disclosure' => get_option('wap_progressive_disclosure', 'no'),
            'mobile_breakpoint' => get_option('wap_mobile_breakpoint', '768'),

            // Analytics
            'enable_analytics' => get_option('wap_enable_analytics', 'yes'),
            'track_scroll_depth' => get_option('wap_track_scroll_depth', 'no'),
            'track_time_spent' => get_option('wap_track_time_spent', 'yes'),

            // Performance
            'hardware_acceleration' => get_option('wap_hardware_acceleration', 'yes'),
            'lazy_load_content' => get_option('wap_lazy_load_content', 'no'),
            'preload_first' => get_option('wap_preload_first', 'yes'),

            // Accessibility
            'keyboard_navigation' => get_option('wap_keyboard_navigation', 'yes'),
            'focus_management' => get_option('wap_focus_management', 'yes'),
            'high_contrast' => get_option('wap_high_contrast', 'no'),
            'screen_reader_labels' => get_option('wap_screen_reader_labels', 'yes'),

            // Product ID for analytics
            'product_id' => get_the_ID()
        );
    }

    /**
     * Output accordion HTML
     */
    public function output_accordion_html() {
        global $wap_accordion_tabs;

        if (empty($wap_accordion_tabs) || !$this->should_show_accordions()) {
            return;
        }

        // Debug: Log that we're outputting accordion HTML
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('WAP Frontend: Outputting accordion HTML with ' . count($wap_accordion_tabs) . ' tabs');
        }

        $settings = $this->get_all_frontend_settings();

        // Build data attributes for JavaScript
        $data_attributes = array(
            'data-animation="' . esc_attr($settings['animation_type']) . '"',
            'data-duration="' . esc_attr($settings['animation_duration']) . '"',
            'data-easing="' . esc_attr($settings['animation_easing']) . '"',
            'data-multiple="' . esc_attr($settings['allow_multiple_open']) . '"',
            'data-auto-expand="' . esc_attr($settings['auto_expand_first']) . '"',
            'data-stagger="' . esc_attr($settings['enable_stagger']) . '"',
            'data-stagger-delay="' . esc_attr($settings['stagger_delay']) . '"'
        );

        echo '<div class="wap-accordion-container wap-template-' . esc_attr($settings['layout_template']) . '">';
        echo '<div class="wap-accordion" ' . implode(' ', $data_attributes) . '>';

        $index = 0;
        foreach ($wap_accordion_tabs as $key => $tab) {
            if (!isset($tab['callback'])) {
                continue;
            }

            $is_first = $index === 0;
            $auto_expand = $settings['auto_expand_first'] === 'yes' && $is_first;

            echo '<div class="wap-accordion-item" data-section="' . esc_attr($key) . '">';

            // Accordion Header with proper toggle icons
            echo '<div class="wap-accordion-header' . ($auto_expand ? ' wap-active' : '') . '" data-target="' . esc_attr($key) . '">';

            if ($settings['show_icons'] === 'yes') {
                $icon = $this->get_accordion_icon($key);
                echo '<span class="wap-accordion-icon">' . $icon . '</span>';
            }

            echo '<span class="wap-accordion-title">' . esc_html($tab['title']) . '</span>';
            echo '<span class="wap-accordion-toggle">';
            echo $auto_expand ? $this->get_toggle_icon(true, $settings) : $this->get_toggle_icon(false, $settings);
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

        // Add debug information
        if (defined('WP_DEBUG') && WP_DEBUG) {
            echo '<!-- WooAccordion Pro Debug: ' . count($wap_accordion_tabs) . ' accordion items rendered -->';
        }
    }

    /**
     * Get toggle icon based on settings
     */
    private function get_toggle_icon($is_expanded, $settings) {
        $icon_style = $settings['toggle_icon_style'];

        switch ($icon_style) {
            case 'chevron':
                return $is_expanded ? '<i class="fas fa-chevron-up"></i>' : '<i class="fas fa-chevron-down"></i>';
            case 'arrow':
                return $is_expanded ? '<i class="fas fa-arrow-up"></i>' : '<i class="fas fa-arrow-down"></i>';
            case 'caret':
                return $is_expanded ? '<i class="fas fa-caret-up"></i>' : '<i class="fas fa-caret-down"></i>';
            case 'custom':
                return $is_expanded ? 
                    '<i class="' . esc_attr($settings['custom_toggle_expanded']) . '"></i>' : 
                    '<i class="' . esc_attr($settings['custom_toggle_collapsed']) . '"></i>';
            default: // plus-minus
                return $is_expanded ? '<i class="fas fa-minus"></i>' : '<i class="fas fa-plus"></i>';
        }
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

        $settings = $this->get_all_frontend_settings();

        echo '<style type="text/css">
            :root {
                --wap-header-bg: ' . esc_attr($settings['header_bg_color']) . ';
                --wap-header-text: ' . esc_attr($settings['header_text_color']) . ';
                --wap-active-header-bg: ' . esc_attr($settings['active_header_bg_color']) . ';
                --wap-active-header-text: ' . esc_attr($settings['active_header_text_color']) . ';
                --wap-content-bg: ' . esc_attr($settings['content_bg_color']) . ';
                --wap-border-color: ' . esc_attr($settings['border_color']) . ';
                --wap-animation-duration: ' . esc_attr($settings['animation_duration']) . 'ms;
                --wap-stagger-delay: ' . esc_attr($settings['stagger_delay']) . 'ms;
            }

            /* Hardware acceleration */
            ' . ($settings['hardware_acceleration'] === 'yes' ? '
            .wap-accordion-content,
            .wap-accordion-header {
                will-change: transform, opacity;
                transform: translateZ(0);
            }' : '') . '

            /* High contrast mode */
            ' . ($settings['high_contrast'] === 'yes' ? '
            .wap-accordion {
                border: 2px solid #000;
            }
            .wap-accordion-header {
                border-bottom: 1px solid #000;
            }
            .wap-accordion-header.wap-active {
                background: #000 !important;
                color: #fff !important;
            }' : '') . '

            /* Mobile breakpoint adjustments */
            @media (max-width: ' . esc_attr($settings['mobile_breakpoint']) . 'px) {
                .wap-accordion-header {
                    min-height: 60px;
                    padding: 1.25rem 1rem;
                }
            }

            /* Sticky navigation */
            ' . ($settings['sticky_navigation'] === 'yes' ? '
            .wap-accordion-header.wap-active {
                position: sticky;
                top: 0;
                z-index: 100;
            }' : '') . '
        </style>';

        // Add custom CSS if provided
        $custom_css = get_option('wap_custom_css', '');
        if (!empty($custom_css)) {
            echo '<style type="text/css">' . wp_strip_all_tags($custom_css) . '</style>';
        }
    }

    /**
     * AJAX handler for accordion analytics
     */
    public function handle_accordion_analytics() {
        // Debug: Log the request
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('WAP Frontend: Analytics handler called with data: ' . print_r($_POST, true));
        }

        check_ajax_referer('wap_frontend_nonce', 'nonce');

        $product_id = intval($_POST['product_id']);
        $section = sanitize_text_field($_POST['section']);
        $action = sanitize_text_field($_POST['action_type']); // Changed from 'action'

        if ($product_id && $section && $action) {
            $analytics = WAP_Analytics::instance();
            $success = $analytics->track_interaction($product_id, $section, $action);
            
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log("WAP Frontend: Analytics tracking result: " . ($success ? 'success' : 'failed'));
            }
            
            wp_send_json_success(array('tracked' => $success));
        } else {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log("WAP Frontend: Analytics tracking failed - missing data");
            }
            wp_send_json_error(array('message' => 'Missing required data'));
        }

        wp_die();
    }
}