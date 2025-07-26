<?php
/**
 * WooAccordion Pro Frontend Class
 * 
 * Handles frontend accordion rendering
 */

if (!defined('ABSPATH')) {
    exit;
}

class WAP_Frontend {

    protected static $_instance = null;

    public static function instance() {
        if (is_null(self::$_instance)) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    public function __construct() {
        $this->init_hooks();
    }

    /**
     * Initialize hooks
     */
    private function init_hooks() {
        // Only run if accordion is enabled
        if (get_option('wap_enable_accordion') !== 'yes') {
            return;
        }

        // Replace WooCommerce product tabs
        add_filter('woocommerce_product_tabs', array($this, 'replace_product_tabs'), 98);
        
        // Enqueue scripts and styles
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        
        // Output accordion HTML
        add_action('woocommerce_after_single_product_summary', array($this, 'output_accordion'), 20);
        
        // Hide default tabs
        add_action('wp_head', array($this, 'hide_default_tabs'));
    }

    /**
     * Replace product tabs with accordion data
     */
    public function replace_product_tabs($tabs) {
        if (!is_product()) {
            return $tabs;
        }

        // Store tabs for accordion conversion
        global $wap_accordion_tabs;
        $wap_accordion_tabs = $tabs;

        // Return empty to hide default tabs
        return array();
    }

    /**
     * Enqueue frontend assets
     */
    public function enqueue_scripts() {
        if (!is_product()) {
            return;
        }

        // Enqueue CSS
        wp_enqueue_style(
            'wap-frontend-css',
            WAP_PLUGIN_URL . 'assets/css/frontend.css',
            array(),
            WAP_VERSION
        );

        // Enqueue FontAwesome if selected
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

        // Pass settings to JavaScript
        wp_localize_script('wap-frontend-js', 'wap_settings', array(
            'auto_expand_first' => get_option('wap_auto_expand_first', 'yes'),
            'allow_multiple_open' => get_option('wap_allow_multiple_open', 'no'),
            'show_icons' => get_option('wap_show_icons', 'yes'),
            'icon_library' => get_option('wap_icon_library', 'css'),
            'animation_duration' => get_option('wap_animation_duration', '300'),
            'enable_mobile_gestures' => get_option('wap_enable_mobile_gestures', 'yes')
        ));
    }

    /**
     * Output accordion HTML
     */
    public function output_accordion() {
        global $wap_accordion_tabs;

        if (empty($wap_accordion_tabs)) {
            return;
        }

        $template = get_option('wap_template', 'modern');
        ?>
        <div class="wap-accordion-container wap-template-<?php echo esc_attr($template); ?>">
            <div class="wap-accordion">
                <?php 
                $index = 0;
                foreach ($wap_accordion_tabs as $key => $tab) :
                    $is_first = $index === 0;
                    $auto_expand = get_option('wap_auto_expand_first') === 'yes' && $is_first;
                ?>
                    <div class="wap-accordion-item">
                        <div class="wap-accordion-header <?php echo $auto_expand ? 'wap-active' : ''; ?>" 
                             data-target="<?php echo esc_attr($key); ?>">
                            <?php if (get_option('wap_show_icons', 'yes') === 'yes') : ?>
                                <span class="wap-accordion-icon">
                                    <?php echo $this->get_accordion_icon($key); ?>
                                </span>
                            <?php endif; ?>
                            <span class="wap-accordion-title"><?php echo esc_html($tab['title']); ?></span>
                            <span class="wap-accordion-toggle">
                                <i class="wap-icon <?php echo $auto_expand ? 'wap-minus' : 'wap-plus'; ?>"></i>
                            </span>
                        </div>
                        <div class="wap-accordion-content <?php echo $auto_expand ? 'wap-active' : ''; ?>" 
                             id="wap-accordion-<?php echo esc_attr($key); ?>">
                            <div class="wap-accordion-content-inner">
                                <?php
                                if (isset($tab['callback'])) {
                                    call_user_func($tab['callback'], $key, $tab);
                                }
                                ?>
                            </div>
                        </div>
                    </div>
                <?php 
                    $index++;
                endforeach; 
                ?>
            </div>
        </div>
        <?php
    }

    /**
     * Hide default WooCommerce tabs
     */
    public function hide_default_tabs() {
        if (!is_product()) {
            return;
        }

        // Get settings for CSS variables
        $header_bg = get_option('wap_header_bg_color', '#f8f9fa');
        $header_text = get_option('wap_header_text_color', '#495057');
        $active_bg = get_option('wap_active_header_bg_color', '#0073aa');
        $border_color = get_option('wap_border_color', '#dee2e6');

        echo '<style>
            .woocommerce-tabs {
                display: none !important;
            }
            :root {
                --wap-header-bg: ' . esc_attr($header_bg) . ';
                --wap-header-text: ' . esc_attr($header_text) . ';
                --wap-active-bg: ' . esc_attr($active_bg) . ';
                --wap-border-color: ' . esc_attr($border_color) . ';
                --wap-animation-duration: ' . esc_attr(get_option('wap_animation_duration', '300')) . 'ms;
            }
        </style>';
    }

    /**
     * Get accordion icon based on section
     */
    private function get_accordion_icon($section) {
        $icon_library = get_option('wap_icon_library', 'css');
        
        if ($icon_library === 'fontawesome') {
            $icons = array(
                'description' => '<i class="fas fa-align-left"></i>',
                'reviews' => '<i class="fas fa-star"></i>',
                'additional_information' => '<i class="fas fa-info-circle"></i>',
                'shipping' => '<i class="fas fa-truck"></i>',
            );
            return isset($icons[$section]) ? $icons[$section] : '<i class="fas fa-file-alt"></i>';
        } else {
            // CSS icons (lightweight)
            $icons = array(
                'description' => '<span class="wap-css-icon wap-icon-text"></span>',
                'reviews' => '<span class="wap-css-icon wap-icon-star"></span>',
                'additional_information' => '<span class="wap-css-icon wap-icon-info"></span>',
                'shipping' => '<span class="wap-css-icon wap-icon-truck"></span>',
            );
            return isset($icons[$section]) ? $icons[$section] : '<span class="wap-css-icon wap-icon-file"></span>';
        }
    }
}