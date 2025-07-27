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

        // Add custom tabs to the existing tabs
        $tabs = $this->add_custom_tabs_to_product_tabs($tabs);

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
        $toggle_style = get_option('wap_toggle_icon_style', 'plus_minus');
        ?>
        <div class="wap-accordion-container wap-template-<?php echo esc_attr($template); ?>" data-toggle-style="<?php echo esc_attr($toggle_style); ?>">
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
                                <?php echo $this->get_toggle_icon($toggle_style, $auto_expand); ?>
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

        // Get settings for CSS variables - UPDATED with new variables
        $header_bg = get_option('wap_header_bg_color', '#f8f9fa');
        $header_text = get_option('wap_header_text_color', '#495057');
        $active_bg = get_option('wap_active_header_bg_color', '#0073aa');
        $active_text = get_option('wap_active_header_text_color', '#ffffff'); // NEW
        $border_color = get_option('wap_border_color', '#dee2e6');

        echo '<style>
            .woocommerce-tabs {
                display: none !important;
            }
            :root {
                --wap-header-bg: ' . esc_attr($header_bg) . ';
                --wap-header-text: ' . esc_attr($header_text) . ';
                --wap-active-bg: ' . esc_attr($active_bg) . ';
                --wap-active-text: ' . esc_attr($active_text) . '; /* NEW */
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
    
    
  
    
    
    /**
     * Add custom tabs to product tabs
     */
    private function add_custom_tabs_to_product_tabs($tabs) {
        global $product;
        
        if (!$product || !class_exists('WAP_Custom_Tabs')) {
            return $tabs;
        }

        $custom_tabs_manager = WAP_Custom_Tabs::instance();
        $custom_tabs = $custom_tabs_manager->get_custom_tabs();
        
        foreach ($custom_tabs as $tab_id => $tab_data) {
            // Check if tab should be displayed for this product
            if ($this->should_display_custom_tab($tab_data, $product)) {
                $tabs['custom_' . $tab_id] = array(
                    'title' => $tab_data['title'],
                    'priority' => isset($tab_data['priority']) ? $tab_data['priority'] : 50,
                    'callback' => array($this, 'custom_tab_content'),
                    'tab_data' => $tab_data
                );
            }
        }

        return $tabs;
    }    
    
    
    /**
     * Check if custom tab should be displayed
     */
    private function should_display_custom_tab($tab_data, $product) {
        // Check if tab is enabled
        if (empty($tab_data['enabled'])) {
            return false;
        }

        // Check category conditions
        if (!empty($tab_data['conditions']['categories'])) {
            $product_categories = wp_get_post_terms($product->get_id(), 'product_cat', array('fields' => 'ids'));
            $allowed_categories = array_map('intval', $tab_data['conditions']['categories']);
            
            // If no category matches, hide tab
            if (empty(array_intersect($product_categories, $allowed_categories))) {
                return false;
            }
        }

        // Check user role conditions
        if (!empty($tab_data['conditions']['user_roles'])) {
            $current_user = wp_get_current_user();
            $user_roles = (array) $current_user->roles;
            $allowed_roles = $tab_data['conditions']['user_roles'];
            
            // If user has no allowed role, hide tab
            if (empty(array_intersect($user_roles, $allowed_roles))) {
                return false;
            }
        }

        // Check product type conditions
        if (!empty($tab_data['conditions']['product_types'])) {
            $product_type = $product->get_type();
            $allowed_types = $tab_data['conditions']['product_types'];
            
            if (!in_array($product_type, $allowed_types)) {
                return false;
            }
        }

        return true;
    }    
    
    /**
     * Output custom tab content
     */
    public function custom_tab_content($key, $tab) {
        if (!isset($tab['tab_data'])) {
            return;
        }

        $tab_data = $tab['tab_data'];
        
        // Process dynamic content
        $content = $this->process_dynamic_content($tab_data['content']);
        
        echo '<div class="wap-custom-tab-content">';
        echo wp_kses_post($content);
        echo '</div>';
    }
    
    
    /**
     * Process dynamic content (shortcodes, placeholders)
     */
    private function process_dynamic_content($content) {
        global $product;
        
        if (!$product) {
            return $content;
        }
        
        // Replace product placeholders
        $placeholders = array(
            '{product_name}' => $product->get_name(),
            '{product_price}' => $product->get_price_html(),
            '{product_sku}' => $product->get_sku(),
            '{product_weight}' => $product->get_weight(),
            '{product_dimensions}' => $this->get_product_dimensions($product),
        );

        $content = str_replace(array_keys($placeholders), array_values($placeholders), $content);
        
        // Process shortcodes
        $content = do_shortcode($content);
        
        return $content;
    }
    
    /**
     * Get formatted product dimensions
     */
    private function get_product_dimensions($product) {
        $dimensions = array(
            'length' => $product->get_length(),
            'width' => $product->get_width(),
            'height' => $product->get_height()
        );
        
        $dimensions = array_filter($dimensions);
        
        if (empty($dimensions)) {
            return '';
        }
        
        return implode(' × ', $dimensions) . ' ' . get_option('woocommerce_dimension_unit');
    }    
    
    
    
    /**
     * Get toggle icon based on style - NEW METHOD
     */
    private function get_toggle_icon($style, $is_active = false) {
        switch ($style) {
        case 'arrow_down':
            if ($is_active) {
                return '<i class="wap-icon wap-arrow-unicode wap-arrow-up" aria-label="Collapse"></i>';
            } else {
                return '<i class="wap-icon wap-arrow-unicode wap-arrow-down" aria-label="Expand"></i>';
            }
            
        case 'chevron':
            if ($is_active) {
                return '<i class="wap-icon wap-chevron-unicode wap-chevron-up" aria-label="Collapse"></i>';
            } else {
                return '<i class="wap-icon wap-chevron-unicode wap-chevron-down" aria-label="Expand"></i>';
            }

            case 'triangle':
                if ($is_active) {
                    return '<i class="wap-icon wap-triangle wap-triangle-down" aria-label="Collapse"></i>';
                } else {
                    return '<i class="wap-icon wap-triangle wap-triangle-right" aria-label="Expand"></i>';
                }

            case 'plus_minus':
            default:
                if ($is_active) {
                    return '<i class="wap-icon wap-minus" aria-label="Collapse"></i>';
                } else {
                    return '<i class="wap-icon wap-plus" aria-label="Expand"></i>';
                }
        }
    }
  
    
    
    
}