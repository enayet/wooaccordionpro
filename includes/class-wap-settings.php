<?php
/**
 * WooAccordion Pro Settings Class
 * 
 * Handles admin settings and configuration
 * 
 * @class WAP_Settings
 * @version 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class WAP_Settings {

    /**
     * Single instance of the class
     * @var WAP_Settings
     */
    protected static $_instance = null;

    /**
     * Settings sections
     * @var array
     */
    private $settings_sections = array();

    /**
     * Main WAP_Settings Instance
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
        add_action('admin_init', array($this, 'admin_init'));
        add_action('admin_menu', array($this, 'admin_menu'));
        add_filter('woocommerce_settings_tabs_array', array($this, 'add_settings_tab'), 50);
        add_action('woocommerce_settings_tabs_accordions', array($this, 'settings_tab'));
        add_action('woocommerce_update_options_accordions', array($this, 'update_settings'));
        add_action('admin_enqueue_scripts', array($this, 'admin_scripts'));
        
        
        // ADD THESE MISSING AJAX HANDLERS:
        add_action('wp_ajax_wap_save_settings', array($this, 'ajax_save_settings'));
        add_action('wp_ajax_nopriv_wap_save_settings', array($this, 'ajax_save_settings')); // Remove if admin-only
        
        
    }

    /**
     * Admin init
     */
    public function admin_init() {
        $this->init_settings_sections();
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
     * Add settings tab to WooCommerce
     */
    public function add_settings_tab($settings_tabs) {
        $settings_tabs['accordions'] = __('Accordions', 'wooaccordion-pro');
        return $settings_tabs;
    }

    /**
     * Settings tab content
     */
    public function settings_tab() {
        woocommerce_admin_fields($this->get_settings());
    }

    /**
     * Update settings
     */
    public function update_settings() {
        woocommerce_update_options($this->get_settings());
    }

    /**
     * Enqueue admin scripts
     */
    public function admin_scripts($hook) {
        if (strpos($hook, 'wap-settings') !== false || strpos($hook, 'woocommerce_page_wc-settings') !== false) {
            wp_enqueue_script(
                'wap-admin-js',
                WAP_PLUGIN_URL . 'assets/js/admin.js',
                array('jquery', 'jquery-ui-sortable'),
                WAP_VERSION,
                true
            );

            wp_enqueue_style(
                'wap-admin-css',
                WAP_PLUGIN_URL . 'assets/css/admin.css',
                array(),
                WAP_VERSION
            );

            wp_localize_script('wap-admin-js', 'wap_admin', array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('wap_admin_nonce'),
                'strings' => array(
                    'save_success' => __('Settings saved successfully!', 'wooaccordion-pro'),
                    'save_error' => __('Error saving settings. Please try again.', 'wooaccordion-pro'),
                    'confirm_reset' => __('Are you sure you want to reset all settings to default?', 'wooaccordion-pro'),
                    'preview_loading' => __('Loading preview...', 'wooaccordion-pro')
                )
            ));
        }
    }

    /**
     * Initialize settings sections
     */
    private function init_settings_sections() {
        $this->settings_sections = array(
            'general' => __('General', 'wooaccordion-pro'),
            'styling' => __('Styling', 'wooaccordion-pro'),
            'animations' => __('Animations', 'wooaccordion-pro'),
            'custom_fields' => __('Custom Fields', 'wooaccordion-pro'),
            'conditional' => __('Conditional Logic', 'wooaccordion-pro'),
            'mobile' => __('Mobile', 'wooaccordion-pro'),
            'analytics' => __('Analytics', 'wooaccordion-pro'),
            'advanced' => __('Advanced', 'wooaccordion-pro')
        );
    }

    /**
     * Get all settings
     */
    public function get_settings() {
        $settings = array();

        // General Settings Section
        $settings[] = array(
            'name' => __('General Settings', 'wooaccordion-pro'),
            'type' => 'title',
            'desc' => __('Configure basic accordion functionality.', 'wooaccordion-pro'),
            'id' => 'wap_general_options'
        );

        $settings[] = array(
            'name' => __('Enable Accordions', 'wooaccordion-pro'),
            'type' => 'checkbox',
            'desc' => __('Replace WooCommerce product tabs with accordions', 'wooaccordion-pro'),
            'id' => 'wap_enable_accordion',
            'default' => 'yes'
        );

        $settings[] = array(
            'name' => __('Auto Expand First', 'wooaccordion-pro'),
            'type' => 'checkbox',
            'desc' => __('Automatically expand the first accordion item when page loads', 'wooaccordion-pro'),
            'id' => 'wap_auto_expand_first',
            'default' => 'yes'
        );

        $settings[] = array(
            'name' => __('Allow Multiple Open', 'wooaccordion-pro'),
            'type' => 'checkbox',
            'desc' => __('Allow multiple accordion items to be open simultaneously', 'wooaccordion-pro'),
            'id' => 'wap_allow_multiple_open',
            'default' => 'no'
        );

        $settings[] = array(
            'name' => __('Show Icons', 'wooaccordion-pro'),
            'type' => 'checkbox',
            'desc' => __('Display icons next to accordion headers', 'wooaccordion-pro'),
            'id' => 'wap_show_icons',
            'default' => 'yes'
        );

        $settings[] = array(
            'name' => __('Icon Library', 'wooaccordion-pro'),
            'type' => 'select',
            'desc' => __('Choose which icon library to use', 'wooaccordion-pro'),
            'id' => 'wap_icon_library',
            'options' => array(
                'fontawesome' => __('FontAwesome', 'wooaccordion-pro'),
                'custom' => __('Custom Icons', 'wooaccordion-pro')
            ),
            'default' => 'fontawesome'
        );

        $settings[] = array(
            'type' => 'sectionend',
            'id' => 'wap_general_options'
        );

        // Styling Options Section
        $settings[] = array(
            'name' => __('Styling Options', 'wooaccordion-pro'),
            'type' => 'title',
            'desc' => __('Customize the appearance of your accordions.', 'wooaccordion-pro'),
            'id' => 'wap_styling_options'
        );

        $settings[] = array(
            'name' => __('Layout Template', 'wooaccordion-pro'),
            'type' => 'select',
            'desc' => __('Choose a pre-designed layout template', 'wooaccordion-pro'),
            'id' => 'wap_layout_template',
            'options' => array(
                'modern-card' => __('Modern Card', 'wooaccordion-pro'),
                'minimal-flat' => __('Minimal Flat', 'wooaccordion-pro'),
                'material-design' => __('Material Design', 'wooaccordion-pro'),
                'glassmorphism' => __('Glassmorphism', 'wooaccordion-pro'),
                'neumorphism' => __('Neumorphism', 'wooaccordion-pro'),
                'dark-mode' => __('Dark Mode', 'wooaccordion-pro'),
                'corporate' => __('Corporate', 'wooaccordion-pro'),
                'gradient' => __('Gradient', 'wooaccordion-pro'),
                'minimal-lines' => __('Minimal Lines', 'wooaccordion-pro'),
                'card-stack' => __('Card Stack', 'wooaccordion-pro')
            ),
            'default' => 'modern-card'
        );

        $settings[] = array(
            'name' => __('Header Background Color', 'wooaccordion-pro'),
            'type' => 'color',
            'desc' => __('Background color for accordion headers', 'wooaccordion-pro'),
            'id' => 'wap_header_bg_color',
            'default' => '#f8f9fa',
            'css' => 'width:6em;'
        );

        $settings[] = array(
            'name' => __('Header Text Color', 'wooaccordion-pro'),
            'type' => 'color',
            'desc' => __('Text color for accordion headers', 'wooaccordion-pro'),
            'id' => 'wap_header_text_color',
            'default' => '#495057',
            'css' => 'width:6em;'
        );

        $settings[] = array(
            'name' => __('Active Header Background', 'wooaccordion-pro'),
            'type' => 'color',
            'desc' => __('Background color for active/expanded accordion headers', 'wooaccordion-pro'),
            'id' => 'wap_active_header_bg_color',
            'default' => '#6366f1',
            'css' => 'width:6em;'
        );

        $settings[] = array(
            'name' => __('Active Header Text Color', 'wooaccordion-pro'),
            'type' => 'color',
            'desc' => __('Text color for active/expanded accordion headers', 'wooaccordion-pro'),
            'id' => 'wap_active_header_text_color',
            'default' => '#ffffff',
            'css' => 'width:6em;'
        );

        $settings[] = array(
            'name' => __('Content Background Color', 'wooaccordion-pro'),
            'type' => 'color',
            'desc' => __('Background color for accordion content areas', 'wooaccordion-pro'),
            'id' => 'wap_content_bg_color',
            'default' => '#ffffff',
            'css' => 'width:6em;'
        );

        $settings[] = array(
            'name' => __('Border Color', 'wooaccordion-pro'),
            'type' => 'color',
            'desc' => __('Border color for accordion items', 'wooaccordion-pro'),
            'id' => 'wap_border_color',
            'default' => '#dee2e6',
            'css' => 'width:6em;'
        );

        $settings[] = array(
            'name' => __('Header Hover Effect', 'wooaccordion-pro'),
            'type' => 'select',
            'desc' => __('Choose hover effect for accordion headers', 'wooaccordion-pro'),
            'id' => 'wap_header_hover_effect',
            'options' => array(
                'none' => __('None', 'wooaccordion-pro'),
                'shine' => __('Shine Effect', 'wooaccordion-pro'),
                'lift' => __('Lift Effect', 'wooaccordion-pro'),
                'glow' => __('Glow Effect', 'wooaccordion-pro'),
                'scale' => __('Scale Effect', 'wooaccordion-pro')
            ),
            'default' => 'none'
        );

        $settings[] = array(
            'type' => 'sectionend',
            'id' => 'wap_styling_options'
        );

        // Enhanced Animation Settings Section
        $settings[] = array(
            'name' => __('Animation Settings', 'wooaccordion-pro'),
            'type' => 'title',
            'desc' => __('Configure advanced accordion animations and transitions.', 'wooaccordion-pro'),
            'id' => 'wap_animation_options'
        );

        $settings[] = array(
            'name' => __('Animation Type', 'wooaccordion-pro'),
            'type' => 'select',
            'desc' => __('Choose the animation effect for accordion expand/collapse', 'wooaccordion-pro'),
            'id' => 'wap_animation_type',
            'options' => array(
                'slide' => __('Slide Down/Up', 'wooaccordion-pro'),
                'slide-spring' => __('Slide with Spring Easing', 'wooaccordion-pro'),
                'fade' => __('Fade In/Out', 'wooaccordion-pro'),
                'fade-scale' => __('Fade with Scale', 'wooaccordion-pro'),
                'bounce' => __('Bounce In', 'wooaccordion-pro'),
                'elastic' => __('Elastic Bounce', 'wooaccordion-pro'),
                'slide-left' => __('Slide from Left', 'wooaccordion-pro'),
                'flip' => __('Flip Effect', 'wooaccordion-pro')
            ),
            'default' => 'slide'
        );

        $settings[] = array(
            'name' => __('Animation Duration', 'wooaccordion-pro'),
            'type' => 'number',
            'desc' => __('Animation duration in milliseconds (200-800ms recommended)', 'wooaccordion-pro'),
            'id' => 'wap_animation_duration',
            'default' => '300',
            'custom_attributes' => array(
                'min' => '100',
                'max' => '1000',
                'step' => '50'
            )
        );

        $settings[] = array(
            'name' => __('Animation Easing', 'wooaccordion-pro'),
            'type' => 'select',
            'desc' => __('Choose the easing function for animations', 'wooaccordion-pro'),
            'id' => 'wap_animation_easing',
            'options' => array(
                'ease' => __('Default Ease', 'wooaccordion-pro'),
                'ease-in-out-back' => __('Back Easing', 'wooaccordion-pro'),
                'ease-in-out-circ' => __('Circular Easing', 'wooaccordion-pro'),
                'ease-in-out-expo' => __('Exponential Easing', 'wooaccordion-pro'),
                'ease-in-out-sine' => __('Sine Easing', 'wooaccordion-pro')
            ),
            'default' => 'ease'
        );

        $settings[] = array(
            'name' => __('Enable Stagger Animation', 'wooaccordion-pro'),
            'type' => 'checkbox',
            'desc' => __('Animate accordion items one after another on page load', 'wooaccordion-pro'),
            'id' => 'wap_enable_stagger',
            'default' => 'no'
        );

        $settings[] = array(
            'name' => __('Stagger Delay', 'wooaccordion-pro'),
            'type' => 'number',
            'desc' => __('Delay between each item animation in milliseconds', 'wooaccordion-pro'),
            'id' => 'wap_stagger_delay',
            'default' => '50',
            'custom_attributes' => array(
                'min' => '25',
                'max' => '200',
                'step' => '25'
            )
        );

        $settings[] = array(
            'name' => __('Toggle Icon Style', 'wooaccordion-pro'),
            'type' => 'select',
            'desc' => __('Choose the style for toggle icons', 'wooaccordion-pro'),
            'id' => 'wap_toggle_icon_style',
            'options' => array(
                'plus-minus' => __('Plus/Minus', 'wooaccordion-pro'),
                'chevron' => __('Chevron', 'wooaccordion-pro'),
                'arrow' => __('Arrow', 'wooaccordion-pro'),
                'caret' => __('Caret', 'wooaccordion-pro'),
                'custom' => __('Custom Icon', 'wooaccordion-pro')
            ),
            'default' => 'plus-minus'
        );

        $settings[] = array(
            'name' => __('Custom Toggle Icon (Collapsed)', 'wooaccordion-pro'),
            'type' => 'text',
            'desc' => __('FontAwesome class for collapsed state (e.g., fas fa-plus)', 'wooaccordion-pro'),
            'id' => 'wap_custom_toggle_collapsed',
            'default' => 'fas fa-plus'
        );

        $settings[] = array(
            'name' => __('Custom Toggle Icon (Expanded)', 'wooaccordion-pro'),
            'type' => 'text',
            'desc' => __('FontAwesome class for expanded state (e.g., fas fa-minus)', 'wooaccordion-pro'),
            'id' => 'wap_custom_toggle_expanded',
            'default' => 'fas fa-minus'
        );

        $settings[] = array(
            'type' => 'sectionend',
            'id' => 'wap_animation_options'
        );
        
        
    // Custom Fields Integration Section
    $settings[] = array(
        'name' => __('Custom Fields Integration', 'wooaccordion-pro'),
        'type' => 'title',
        'desc' => __('Automatically create accordion tabs from custom fields.', 'wooaccordion-pro'),
        'id' => 'wap_custom_fields_options'
    );

    $settings[] = array(
        'name' => __('Enable Custom Fields', 'wooaccordion-pro'),
        'type' => 'checkbox',
        'desc' => __('Automatically generate accordion tabs from custom fields', 'wooaccordion-pro'),
        'id' => 'wap_enable_custom_fields',
        'default' => 'no'
    );

    $settings[] = array(
        'name' => __('Show Field Labels', 'wooaccordion-pro'),
        'type' => 'checkbox',
        'desc' => __('Display field labels within accordion content', 'wooaccordion-pro'),
        'id' => 'wap_show_field_labels',
        'default' => 'yes'
    );

    $settings[] = array(
        'name' => __('Show Empty Fields', 'wooaccordion-pro'),
        'type' => 'checkbox',
        'desc' => __('Show accordion tabs even when fields are empty', 'wooaccordion-pro'),
        'id' => 'wap_show_empty_fields',
        'default' => 'no'
    );

    // ACF Integration Settings
    $settings[] = array(
        'name' => __('ACF Integration', 'wooaccordion-pro'),
        'type' => 'title',
        'desc' => __('Advanced Custom Fields integration settings.', 'wooaccordion-pro'),
        'id' => 'wap_acf_integration'
    );

    $settings[] = array(
        'name' => __('ACF Field Groups', 'wooaccordion-pro'),
        'type' => 'multiselect',
        'desc' => __('Select which ACF field groups to include as accordion tabs', 'wooaccordion-pro'),
        'id' => 'wap_acf_field_groups',
        'options' => $this->get_acf_field_groups_options(),
        'class' => 'wc-enhanced-select',
        'css' => 'min-width:300px;',
        'default' => array()
    );

    $settings[] = array(
        'name' => __('ACF Tab Priority', 'wooaccordion-pro'),
        'type' => 'number',
        'desc' => __('Priority order for ACF tabs (higher numbers appear later)', 'wooaccordion-pro'),
        'id' => 'wap_acf_priority',
        'default' => '50',
        'custom_attributes' => array(
            'min' => '1',
            'max' => '100',
            'step' => '1'
        )
    );

    $settings[] = array(
        'name' => __('ACF Tab Title Format', 'wooaccordion-pro'),
        'type' => 'select',
        'desc' => __('How to display ACF tab titles', 'wooaccordion-pro'),
        'id' => 'wap_acf_title_format',
        'options' => array(
            'group_name' => __('Use Field Group Name', 'wooaccordion-pro'),
            'custom_prefix' => __('Custom Prefix + Group Name', 'wooaccordion-pro'),
            'custom_title' => __('Custom Title Only', 'wooaccordion-pro')
        ),
        'default' => 'group_name'
    );

    $settings[] = array(
        'name' => __('ACF Custom Title Prefix', 'wooaccordion-pro'),
        'type' => 'text',
        'desc' => __('Prefix to add before ACF group names (e.g., "Details: ")', 'wooaccordion-pro'),
        'id' => 'wap_acf_title_prefix',
        'default' => ''
    );

    $settings[] = array(
        'type' => 'sectionend',
        'id' => 'wap_acf_integration'
    );

    // Meta Box Integration Settings
    $settings[] = array(
        'name' => __('Meta Box Integration', 'wooaccordion-pro'),
        'type' => 'title',
        'desc' => __('Meta Box plugin integration settings.', 'wooaccordion-pro'),
        'id' => 'wap_meta_box_integration'
    );

    $settings[] = array(
        'name' => __('Meta Box Groups', 'wooaccordion-pro'),
        'type' => 'multiselect',
        'desc' => __('Select which Meta Box groups to include as accordion tabs', 'wooaccordion-pro'),
        'id' => 'wap_meta_box_groups',
        'options' => $this->get_meta_box_groups_options(),
        'class' => 'wc-enhanced-select',
        'css' => 'min-width:300px;',
        'default' => array()
    );

    $settings[] = array(
        'name' => __('Meta Box Tab Priority', 'wooaccordion-pro'),
        'type' => 'number',
        'desc' => __('Priority order for Meta Box tabs', 'wooaccordion-pro'),
        'id' => 'wap_meta_box_priority',
        'default' => '60',
        'custom_attributes' => array(
            'min' => '1',
            'max' => '100',
            'step' => '1'
        )
    );

    $settings[] = array(
        'type' => 'sectionend',
        'id' => 'wap_meta_box_integration'
    );

    // Custom Meta Fields Settings
    $settings[] = array(
        'name' => __('Custom Meta Fields', 'wooaccordion-pro'),
        'type' => 'title',
        'desc' => __('Configure custom post meta fields to display as accordion tabs.', 'wooaccordion-pro'),
        'id' => 'wap_custom_meta_integration'
    );

    $settings[] = array(
        'name' => __('Custom Meta Configuration', 'wooaccordion-pro'),
        'type' => 'textarea',
        'desc' => __('JSON configuration for custom meta fields. Format: [{"meta_key":"field_name","title":"Tab Title","format":"text","priority":70}]', 'wooaccordion-pro'),
        'id' => 'wap_custom_meta_config_json',
        'css' => 'min-height:120px; font-family:monospace;',
        'default' => '[
      {
        "meta_key": "_product_manual",
        "title": "Product Manual",
        "format": "html",
        "priority": 70
      },
      {
        "meta_key": "_warranty_info",
        "title": "Warranty Information", 
        "format": "text",
        "priority": 75
      }
    ]'
    );

    $settings[] = array(
        'name' => __('Available Format Types', 'wooaccordion-pro'),
        'type' => 'text',
        'desc' => __('Supported formats: text, html, url, email, json, image, file', 'wooaccordion-pro'),
        'id' => 'wap_meta_format_help',
        'default' => 'text, html, url, email, json, image, file',
        'custom_attributes' => array('readonly' => 'readonly')
    );

    $settings[] = array(
        'type' => 'sectionend',
        'id' => 'wap_custom_meta_integration'
    );

    // WooCommerce Integration Settings
    $settings[] = array(
        'name' => __('WooCommerce Fields', 'wooaccordion-pro'),
        'type' => 'title',
        'desc' => __('Include WooCommerce-specific fields as accordion tabs.', 'wooaccordion-pro'),
        'id' => 'wap_woocommerce_integration'
    );

    $settings[] = array(
        'name' => __('Include Product Attributes', 'wooaccordion-pro'),
        'type' => 'checkbox',
        'desc' => __('Show product attributes as separate accordion tabs', 'wooaccordion-pro'),
        'id' => 'wap_include_attributes',
        'default' => 'yes'
    );

    $settings[] = array(
        'name' => __('Attributes Display Format', 'wooaccordion-pro'),
        'type' => 'select',
        'desc' => __('How to display product attributes', 'wooaccordion-pro'),
        'id' => 'wap_attributes_format',
        'options' => array(
            'list' => __('Bulleted List', 'wooaccordion-pro'),
            'table' => __('Table Format', 'wooaccordion-pro'),
            'inline' => __('Comma Separated', 'wooaccordion-pro'),
            'badges' => __('Badge Style', 'wooaccordion-pro')
        ),
        'default' => 'list'
    );

    $settings[] = array(
        'name' => __('Include Variation Data', 'wooaccordion-pro'),
        'type' => 'checkbox',
        'desc' => __('Show variation-specific data in accordion tabs', 'wooaccordion-pro'),
        'id' => 'wap_include_variations',
        'default' => 'no'
    );

    $settings[] = array(
        'name' => __('WooCommerce Tab Priority', 'wooaccordion-pro'),
        'type' => 'number',
        'desc' => __('Priority order for WooCommerce custom tabs', 'wooaccordion-pro'),
        'id' => 'wap_woocommerce_priority',
        'default' => '80',
        'custom_attributes' => array(
            'min' => '1',
            'max' => '100',
            'step' => '1'
        )
    );

    $settings[] = array(
        'type' => 'sectionend',
        'id' => 'wap_woocommerce_integration'
    );

    // Field Styling Settings
    $settings[] = array(
        'name' => __('Field Styling', 'wooaccordion-pro'),
        'type' => 'title',
        'desc' => __('Customize the appearance of custom field content.', 'wooaccordion-pro'),
        'id' => 'wap_field_styling'
    );

    $settings[] = array(
        'name' => __('Field Label Style', 'wooaccordion-pro'),
        'type' => 'select',
        'desc' => __('How to style field labels within accordion content', 'wooaccordion-pro'),
        'id' => 'wap_field_label_style',
        'options' => array(
            'heading' => __('Heading Style (H4)', 'wooaccordion-pro'),
            'bold' => __('Bold Text', 'wooaccordion-pro'),
            'italic' => __('Italic Text', 'wooaccordion-pro'),
            'underline' => __('Underlined Text', 'wooaccordion-pro'),
            'badge' => __('Badge Style', 'wooaccordion-pro'),
            'none' => __('No Special Styling', 'wooaccordion-pro')
        ),
        'default' => 'heading'
    );

    $settings[] = array(
        'name' => __('Image Field Size', 'wooaccordion-pro'),
        'type' => 'select',
        'desc' => __('Default size for image fields in accordions', 'wooaccordion-pro'),
        'id' => 'wap_image_field_size',
        'options' => array(
            'thumbnail' => __('Thumbnail (150px)', 'wooaccordion-pro'),
            'medium' => __('Medium (300px)', 'wooaccordion-pro'),
            'large' => __('Large (1024px)', 'wooaccordion-pro'),
            'full' => __('Full Size', 'wooaccordion-pro')
        ),
        'default' => 'medium'
    );

    $settings[] = array(
        'name' => __('Gallery Layout', 'wooaccordion-pro'),
        'type' => 'select',
        'desc' => __('Layout for gallery fields', 'wooaccordion-pro'),
        'id' => 'wap_gallery_layout',
        'options' => array(
            'grid' => __('Grid Layout', 'wooaccordion-pro'),
            'masonry' => __('Masonry Layout', 'wooaccordion-pro'),
            'carousel' => __('Carousel/Slider', 'wooaccordion-pro'),
            'list' => __('Vertical List', 'wooaccordion-pro')
        ),
        'default' => 'grid'
    );

    $settings[] = array(
        'name' => __('Enable Field Icons', 'wooaccordion-pro'),
        'type' => 'checkbox',
        'desc' => __('Show icons next to different field types', 'wooaccordion-pro'),
        'id' => 'wap_enable_field_icons',
        'default' => 'yes'
    );

    $settings[] = array(
        'name' => __('Field Content Animation', 'wooaccordion-pro'),
        'type' => 'select',
        'desc' => __('Animation for field content within accordions', 'wooaccordion-pro'),
        'id' => 'wap_field_content_animation',
        'options' => array(
            'none' => __('No Animation', 'wooaccordion-pro'),
            'fade-in' => __('Fade In', 'wooaccordion-pro'),
            'slide-up' => __('Slide Up', 'wooaccordion-pro'),
            'zoom-in' => __('Zoom In', 'wooaccordion-pro'),
            'stagger' => __('Stagger Fields', 'wooaccordion-pro')
        ),
        'default' => 'fade-in'
    );

    $settings[] = array(
        'type' => 'sectionend',
        'id' => 'wap_field_styling'
    );

    $settings[] = array(
        'type' => 'sectionend',
        'id' => 'wap_custom_fields_options'
    );        
        
        

        // Conditional Logic Section
        $settings[] = array(
            'name' => __('Conditional Logic', 'wooaccordion-pro'),
            'type' => 'title',
            'desc' => __('Control when and where accordions appear.', 'wooaccordion-pro'),
            'id' => 'wap_conditional_options'
        );

        $settings[] = array(
            'name' => __('Enable Conditional Logic', 'wooaccordion-pro'),
            'type' => 'checkbox',
            'desc' => __('Show/hide accordions based on conditions', 'wooaccordion-pro'),
            'id' => 'wap_enable_conditional_logic',
            'default' => 'no'
        );

        $settings[] = array(
            'name' => __('Category Rules', 'wooaccordion-pro'),
            'type' => 'textarea',
            'desc' => __('JSON rules for category-based display (Advanced users only)', 'wooaccordion-pro'),
            'id' => 'wap_category_rules_json',
            'css' => 'min-height:80px; font-family:monospace;',
            'default' => '[]'
        );

        $settings[] = array(
            'name' => __('User Role Rules', 'wooaccordion-pro'),
            'type' => 'textarea',
            'desc' => __('JSON rules for user role-based display (Advanced users only)', 'wooaccordion-pro'),
            'id' => 'wap_user_role_rules_json',
            'css' => 'min-height:80px; font-family:monospace;',
            'default' => '[]'
        );

        $settings[] = array(
            'type' => 'sectionend',
            'id' => 'wap_conditional_options'
        );

        // Mobile Settings Section
        $settings[] = array(
            'name' => __('Mobile Settings', 'wooaccordion-pro'),
            'type' => 'title',
            'desc' => __('Optimize accordion behavior for mobile devices.', 'wooaccordion-pro'),
            'id' => 'wap_mobile_options'
        );

        $settings[] = array(
            'name' => __('Enable Touch Gestures', 'wooaccordion-pro'),
            'type' => 'checkbox',
            'desc' => __('Allow swipe gestures to expand/collapse accordions on mobile', 'wooaccordion-pro'),
            'id' => 'wap_enable_touch_gestures',
            'default' => 'yes'
        );

        $settings[] = array(
            'name' => __('Sticky Navigation', 'wooaccordion-pro'),
            'type' => 'checkbox',
            'desc' => __('Keep accordion headers visible while scrolling long content', 'wooaccordion-pro'),
            'id' => 'wap_sticky_navigation',
            'default' => 'no'
        );

        $settings[] = array(
            'name' => __('Progressive Disclosure', 'wooaccordion-pro'),
            'type' => 'checkbox',
            'desc' => __('Show content summaries first, expand for full details', 'wooaccordion-pro'),
            'id' => 'wap_progressive_disclosure',
            'default' => 'no'
        );

        $settings[] = array(
            'name' => __('Mobile Breakpoint', 'wooaccordion-pro'),
            'type' => 'number',
            'desc' => __('Screen width (in pixels) below which mobile optimizations apply', 'wooaccordion-pro'),
            'id' => 'wap_mobile_breakpoint',
            'default' => '768',
            'custom_attributes' => array(
                'min' => '320',
                'max' => '1024',
                'step' => '1'
            )
        );

        $settings[] = array(
            'type' => 'sectionend',
            'id' => 'wap_mobile_options'
        );

        // Analytics Settings Section
        $settings[] = array(
            'name' => __('Analytics Settings', 'wooaccordion-pro'),
            'type' => 'title',
            'desc' => __('Track accordion interactions and user behavior.', 'wooaccordion-pro'),
            'id' => 'wap_analytics_options'
        );

        $settings[] = array(
            'name' => __('Enable Analytics', 'wooaccordion-pro'),
            'type' => 'checkbox',
            'desc' => __('Track accordion clicks and user interactions', 'wooaccordion-pro'),
            'id' => 'wap_enable_analytics',
            'default' => 'yes'
        );

        $settings[] = array(
            'name' => __('Data Retention Period', 'wooaccordion-pro'),
            'type' => 'select',
            'desc' => __('How long to keep analytics data', 'wooaccordion-pro'),
            'id' => 'wap_analytics_retention',
            'options' => array(
                '30' => __('30 Days', 'wooaccordion-pro'),
                '90' => __('90 Days', 'wooaccordion-pro'),
                '365' => __('1 Year', 'wooaccordion-pro'),
                '0' => __('Keep Forever', 'wooaccordion-pro')
            ),
            'default' => '90'
        );

        $settings[] = array(
            'name' => __('Track Scroll Depth', 'wooaccordion-pro'),
            'type' => 'checkbox',
            'desc' => __('Track how far users scroll within accordion content', 'wooaccordion-pro'),
            'id' => 'wap_track_scroll_depth',
            'default' => 'no'
        );

        $settings[] = array(
            'name' => __('Track Time Spent', 'wooaccordion-pro'),
            'type' => 'checkbox',
            'desc' => __('Track how long users spend reading accordion content', 'wooaccordion-pro'),
            'id' => 'wap_track_time_spent',
            'default' => 'yes'
        );

        $settings[] = array(
            'type' => 'sectionend',
            'id' => 'wap_analytics_options'
        );

        // Performance Settings Section
        $settings[] = array(
            'name' => __('Performance Settings', 'wooaccordion-pro'),
            'type' => 'title',
            'desc' => __('Optimize accordion performance and loading.', 'wooaccordion-pro'),
            'id' => 'wap_performance_options'
        );

        $settings[] = array(
            'name' => __('Enable Hardware Acceleration', 'wooaccordion-pro'),
            'type' => 'checkbox',
            'desc' => __('Use GPU acceleration for smoother animations', 'wooaccordion-pro'),
            'id' => 'wap_hardware_acceleration',
            'default' => 'yes'
        );

        $settings[] = array(
            'name' => __('Lazy Load Content', 'wooaccordion-pro'),
            'type' => 'checkbox',
            'desc' => __('Load accordion content only when expanded', 'wooaccordion-pro'),
            'id' => 'wap_lazy_load_content',
            'default' => 'no'
        );

        $settings[] = array(
            'name' => __('Preload First Accordion', 'wooaccordion-pro'),
            'type' => 'checkbox',
            'desc' => __('Always preload the first accordion content for faster display', 'wooaccordion-pro'),
            'id' => 'wap_preload_first',
            'default' => 'yes'
        );

        $settings[] = array(
            'name' => __('CSS Optimization', 'wooaccordion-pro'),
            'type' => 'select',
            'desc' => __('Choose CSS loading optimization', 'wooaccordion-pro'),
            'id' => 'wap_css_optimization',
            'options' => array(
                'none' => __('No Optimization', 'wooaccordion-pro'),
                'minify' => __('Minify CSS', 'wooaccordion-pro'),
                'inline' => __('Inline Critical CSS', 'wooaccordion-pro'),
                'defer' => __('Defer Non-Critical CSS', 'wooaccordion-pro')
            ),
            'default' => 'minify'
        );

        $settings[] = array(
            'type' => 'sectionend',
            'id' => 'wap_performance_options'
        );

        // Accessibility Settings Section
        $settings[] = array(
            'name' => __('Accessibility Settings', 'wooaccordion-pro'),
            'type' => 'title',
            'desc' => __('Improve accessibility for all users.', 'wooaccordion-pro'),
            'id' => 'wap_accessibility_options'
        );

        $settings[] = array(
            'name' => __('Enable Keyboard Navigation', 'wooaccordion-pro'),
            'type' => 'checkbox',
            'desc' => __('Allow keyboard navigation with arrow keys', 'wooaccordion-pro'),
            'id' => 'wap_keyboard_navigation',
            'default' => 'yes'
        );

        $settings[] = array(
            'name' => __('Focus Management', 'wooaccordion-pro'),
            'type' => 'checkbox',
            'desc' => __('Automatically manage focus for better accessibility', 'wooaccordion-pro'),
            'id' => 'wap_focus_management',
            'default' => 'yes'
        );

        $settings[] = array(
            'name' => __('High Contrast Mode', 'wooaccordion-pro'),
            'type' => 'checkbox',
            'desc' => __('Enhance colors for high contrast accessibility', 'wooaccordion-pro'),
            'id' => 'wap_high_contrast',
            'default' => 'no'
        );

        $settings[] = array(
            'name' => __('Screen Reader Labels', 'wooaccordion-pro'),
            'type' => 'checkbox',
            'desc' => __('Add ARIA labels for screen readers', 'wooaccordion-pro'),
            'id' => 'wap_screen_reader_labels',
            'default' => 'yes'
        );

        $settings[] = array(
            'name' => __('Skip Links', 'wooaccordion-pro'),
            'type' => 'checkbox',
            'desc' => __('Add skip links for easier keyboard navigation', 'wooaccordion-pro'),
            'id' => 'wap_skip_links',
            'default' => 'no'
        );

        $settings[] = array(
            'type' => 'sectionend',
            'id' => 'wap_accessibility_options'
        );

        // Advanced Settings Section
        $settings[] = array(
            'name' => __('Advanced Settings', 'wooaccordion-pro'),
            'type' => 'title',
            'desc' => __('Advanced configuration options for developers.', 'wooaccordion-pro'),
            'id' => 'wap_advanced_options'
        );

        $settings[] = array(
            'name' => __('Custom CSS', 'wooaccordion-pro'),
            'type' => 'textarea',
            'desc' => __('Add custom CSS to override default accordion styles. Changes will be previewed in real-time.', 'wooaccordion-pro'),
            'id' => 'wap_custom_css',
            'css' => 'min-height:200px; font-family:monospace; width:100%;',
            'default' => '/* Add your custom CSS here */
    .wap-accordion-header {
        /* Custom header styles */
    }

    .wap-accordion-content {
        /* Custom content styles */
    }'
        );

        $settings[] = array(
            'name' => __('Custom JavaScript', 'wooaccordion-pro'),
            'type' => 'textarea',
            'desc' => __('Add custom JavaScript for advanced functionality', 'wooaccordion-pro'),
            'id' => 'wap_custom_js',
            'css' => 'min-height:150px; font-family:monospace; width:100%;',
            'default' => '/* Add your custom JavaScript here */
    // Example: Custom accordion event listener
    // document.addEventListener("wap:accordion:opened", function(e) {
    //     console.log("Accordion opened:", e.detail);
    // });'
        );

        $settings[] = array(
            'name' => __('CSS Minification', 'wooaccordion-pro'),
            'type' => 'checkbox',
            'desc' => __('Minify custom CSS for better performance', 'wooaccordion-pro'),
            'id' => 'wap_minify_css',
            'default' => 'yes'
        );

        $settings[] = array(
            'name' => __('Load CSS in Footer', 'wooaccordion-pro'),
            'type' => 'checkbox',
            'desc' => __('Load accordion CSS in footer for better page speed', 'wooaccordion-pro'),
            'id' => 'wap_css_in_footer',
            'default' => 'no'
        );

        $settings[] = array(
            'name' => __('Debug Mode', 'wooaccordion-pro'),
            'type' => 'checkbox',
            'desc' => __('Enable debug mode for troubleshooting (shows console logs)', 'wooaccordion-pro'),
            'id' => 'wap_debug_mode',
            'default' => 'no'
        );

        $settings[] = array(
            'name' => __('Developer Mode', 'wooaccordion-pro'),
            'type' => 'checkbox',
            'desc' => __('Enable developer features and advanced options', 'wooaccordion-pro'),
            'id' => 'wap_developer_mode',
            'default' => 'no'
        );

        $settings[] = array(
            'type' => 'sectionend',
            'id' => 'wap_advanced_options'
        );

        return apply_filters('wap_settings', $settings);
    }

    /**
     * Admin page content
     */
    public function admin_page() {
        ?>
        <div class="wrap wap-admin-wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
            
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
                    <form method="post" action="options.php" id="wap-settings-form">
                        <?php wp_nonce_field('wap_admin_nonce', 'wap_nonce'); ?>
                        <?php
                        settings_fields('wap_settings');
                        do_settings_sections('wap_settings');
                        $this->output_settings_form();
                        ?>
                        
                        <div class="wap-form-actions">
                            <?php submit_button(__('Save Settings', 'wooaccordion-pro'), 'primary', 'submit', false); ?>
                            <button type="button" class="button button-secondary" id="wap-preview-accordion">
                                <?php _e('Preview Accordion', 'wooaccordion-pro'); ?>
                            </button>
                            <button type="button" class="button button-link-delete" id="wap-reset-settings">
                                <?php _e('Reset to Defaults', 'wooaccordion-pro'); ?>
                            </button>
                        </div>
                    </form>
                </div>

                <div class="wap-admin-sidebar">
                    <div class="wap-admin-box">
                        <h3><?php _e('Quick Links', 'wooaccordion-pro'); ?></h3>
                        <ul>
                            <li><a href="<?php echo admin_url('admin.php?page=wc-reports&tab=wap_analytics'); ?>"><?php _e('View Analytics', 'wooaccordion-pro'); ?></a></li>
                            <li><a href="<?php echo esc_url('https://wooaccordionpro.com/docs/'); ?>" target="_blank"><?php _e('Documentation', 'wooaccordion-pro'); ?></a></li>
                            <li><a href="<?php echo esc_url('https://wooaccordionpro.com/support/'); ?>" target="_blank"><?php _e('Get Support', 'wooaccordion-pro'); ?></a></li>
                        </ul>
                    </div>

                    <div class="wap-admin-box">
                        <h3><?php _e('Current Status', 'wooaccordion-pro'); ?></h3>
                        <div class="wap-status-item">
                            <span class="wap-status-label"><?php _e('Accordions:', 'wooaccordion-pro'); ?></span>
                            <span class="wap-status-value <?php echo get_option('wap_enable_accordion') === 'yes' ? 'enabled' : 'disabled'; ?>">
                                <?php echo get_option('wap_enable_accordion') === 'yes' ? __('Enabled', 'wooaccordion-pro') : __('Disabled', 'wooaccordion-pro'); ?>
                            </span>
                        </div>
                        <div class="wap-status-item">
                            <span class="wap-status-label"><?php _e('Analytics:', 'wooaccordion-pro'); ?></span>
                            <span class="wap-status-value <?php echo get_option('wap_enable_analytics') === 'yes' ? 'enabled' : 'disabled'; ?>">
                                <?php echo get_option('wap_enable_analytics') === 'yes' ? __('Enabled', 'wooaccordion-pro') : __('Disabled', 'wooaccordion-pro'); ?>
                            </span>
                        </div>
                        <div class="wap-status-item">
                            <span class="wap-status-label"><?php _e('Template:', 'wooaccordion-pro'); ?></span>
                            <span class="wap-status-value"><?php echo esc_html(get_option('wap_layout_template', 'modern-card')); ?></span>
                        </div>
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

        <div id="wap-preview-modal" class="wap-modal" style="display: none;">
            <div class="wap-modal-content">
                <div class="wap-modal-header">
                    <h3><?php _e('Accordion Preview', 'wooaccordion-pro'); ?></h3>
                    <button type="button" class="wap-modal-close">&times;</button>
                </div>
                <div class="wap-modal-body">
                    <div id="wap-preview-content">
                        <?php _e('Loading preview...', 'wooaccordion-pro'); ?>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * Output settings form with tabs
     */
    private function output_settings_form() {
        ?>
        <div class="wap-settings-tabs">
            <nav class="wap-tab-nav">
                <?php foreach ($this->settings_sections as $section_id => $section_name) : ?>
                    <button type="button" class="wap-tab-button <?php echo $section_id === 'general' ? 'active' : ''; ?>" data-tab="<?php echo esc_attr($section_id); ?>">
                        <?php echo esc_html($section_name); ?>
                    </button>
                <?php endforeach; ?>
            </nav>

            <div class="wap-tab-content">
                <?php
                $settings = $this->get_settings();
                $current_section = '';
                $is_first = true;

                foreach ($settings as $setting) {
                    if ($setting['type'] === 'title') {
                        if (!$is_first) {
                            echo '</div>'; // Close previous tab
                        }
                        $section_id = $this->get_section_id_from_setting($setting);
                        echo '<div class="wap-tab-panel ' . ($is_first ? 'active' : '') . '" data-panel="' . esc_attr($section_id) . '">';
                        $is_first = false;
                    }

                    if ($setting['type'] !== 'sectionend') {
                        $this->output_setting_field($setting);
                    }
                }
                echo '</div>'; // Close last tab
                ?>
            </div>
        </div>
        <?php
    }

    /**
     * Get section ID from setting
     */
    private function get_section_id_from_setting($setting) {
        $section_map = array(
            'wap_general_options' => 'general',
            'wap_styling_options' => 'styling',
            'wap_animation_options' => 'animations',
            'wap_custom_fields_options' => 'custom_fields',
            'wap_conditional_options' => 'conditional',
            'wap_mobile_options' => 'mobile',
            'wap_analytics_options' => 'analytics',
            'wap_advanced_options' => 'advanced'
        );

        return isset($section_map[$setting['id']]) ? $section_map[$setting['id']] : 'general';
    }

    /**
     * Output individual setting field
     */
    private function output_setting_field($setting) {
        if ($setting['type'] === 'title') {
            echo '<h3>' . esc_html($setting['name']) . '</h3>';
            if (!empty($setting['desc'])) {
                echo '<p>' . esc_html($setting['desc']) . '</p>';
            }
            return;
        }

        $value = get_option($setting['id'], isset($setting['default']) ? $setting['default'] : '');
        ?>
        <div class="wap-form-field">
            <label for="<?php echo esc_attr($setting['id']); ?>">
                <?php echo esc_html($setting['name']); ?>
            </label>
            
            <?php
            switch ($setting['type']) {
                case 'checkbox':
                    ?>
                    <input type="checkbox" 
                           id="<?php echo esc_attr($setting['id']); ?>" 
                           name="<?php echo esc_attr($setting['id']); ?>" 
                           value="yes" 
                           <?php checked($value, 'yes'); ?> />
                    <?php
                    break;

                case 'select':
                    ?>
                    <select id="<?php echo esc_attr($setting['id']); ?>" 
                            name="<?php echo esc_attr($setting['id']); ?>">
                        <?php foreach ($setting['options'] as $option_value => $option_label) : ?>
                            <option value="<?php echo esc_attr($option_value); ?>" 
                                    <?php selected($value, $option_value); ?>>
                                <?php echo esc_html($option_label); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <?php
                    break;

                case 'color':
                    ?>
                    <input type="color" 
                           id="<?php echo esc_attr($setting['id']); ?>" 
                           name="<?php echo esc_attr($setting['id']); ?>" 
                           value="<?php echo esc_attr($value); ?>"
                           <?php echo isset($setting['css']) ? 'style="' . esc_attr($setting['css']) . '"' : ''; ?> />
                    <?php
                    break;

                case 'number':
                    ?>
                    <input type="number" 
                           id="<?php echo esc_attr($setting['id']); ?>" 
                           name="<?php echo esc_attr($setting['id']); ?>" 
                           value="<?php echo esc_attr($value); ?>"
                           <?php
                           if (isset($setting['custom_attributes'])) {
                               foreach ($setting['custom_attributes'] as $attr => $attr_value) {
                                   echo esc_attr($attr) . '="' . esc_attr($attr_value) . '" ';
                               }
                           }
                           ?> />
                    <?php
                    break;

                case 'textarea':
                    ?>
                    <textarea id="<?php echo esc_attr($setting['id']); ?>" 
                              name="<?php echo esc_attr($setting['id']); ?>"
                              <?php echo isset($setting['css']) ? 'style="' . esc_attr($setting['css']) . '"' : ''; ?>><?php echo esc_textarea($value); ?></textarea>
                    <?php
                    break;

                default:
                    ?>
                    <input type="text" 
                           id="<?php echo esc_attr($setting['id']); ?>" 
                           name="<?php echo esc_attr($setting['id']); ?>" 
                           value="<?php echo esc_attr($value); ?>" />
                    <?php
                    break;
            }
            ?>

            <?php if (!empty($setting['desc'])) : ?>
                <p class="description"><?php echo esc_html($setting['desc']); ?></p>
            <?php endif; ?>
        </div>
        <?php
    }
    
    
    
    /**
     * Get ACF field groups options for settings
     */
    private function get_acf_field_groups_options() {
        $options = array();

        if (!function_exists('acf_get_field_groups')) {
            return $options;
        }

        $field_groups = acf_get_field_groups();

        foreach ($field_groups as $group) {
            // Check if group applies to products
            $location_rules = isset($group['location']) ? $group['location'] : array();
            $applies_to_products = false;

            foreach ($location_rules as $rule_group) {
                foreach ($rule_group as $rule) {
                    if (isset($rule['param']) && $rule['param'] === 'post_type' && 
                        isset($rule['value']) && $rule['value'] === 'product') {
                        $applies_to_products = true;
                        break 2;
                    }
                }
            }

            if ($applies_to_products) {
                $options[$group['key']] = $group['title'];
            }
        }

        return $options;
    }

    /**
     * Get Meta Box groups options for settings
     */
    private function get_meta_box_groups_options() {
        $options = array();

        if (!function_exists('rwmb_get_registry')) {
            return $options;
        }

        try {
            $registry = rwmb_get_registry('meta_box');
            $meta_boxes = $registry->get_by(array(
                'object_type' => 'post'
            ));

            foreach ($meta_boxes as $meta_box) {
                // Check if applies to products
                $post_types = isset($meta_box['post_types']) ? $meta_box['post_types'] : array();

                if (in_array('product', $post_types)) {
                    $options[$meta_box['id']] = $meta_box['title'];
                }
            }
        } catch (Exception $e) {
            // Meta Box not properly initialized
        }

        return $options;
    }

    /**
     * Save custom fields configuration
     */
    public function save_custom_fields_config() {
        // Parse and validate custom meta configuration JSON
        $custom_meta_json = get_option('wap_custom_meta_config_json', '[]');
        $custom_meta_config = json_decode($custom_meta_json, true);

        if (json_last_error() === JSON_ERROR_NONE && is_array($custom_meta_config)) {
            update_option('wap_custom_meta_config', $custom_meta_config);
        } else {
            // Invalid JSON, reset to empty array
            update_option('wap_custom_meta_config', array());
            add_settings_error(
                'wap_settings',
                'invalid_json',
                __('Invalid JSON in Custom Meta Configuration. Please check your syntax.', 'wooaccordion-pro')
            );
        }

        // Parse ACF settings
        $acf_settings = array(
            'field_groups' => get_option('wap_acf_field_groups', array()),
            'priority' => get_option('wap_acf_priority', 50),
            'title_format' => get_option('wap_acf_title_format', 'group_name'),
            'title_prefix' => get_option('wap_acf_title_prefix', '')
        );
        update_option('wap_acf_settings', $acf_settings);

        // Parse Meta Box settings
        $meta_box_settings = array(
            'groups' => get_option('wap_meta_box_groups', array()),
            'priority' => get_option('wap_meta_box_priority', 60)
        );
        update_option('wap_meta_box_settings', $meta_box_settings);
    }

    /**
     * Add custom fields preview to admin
     */
    public function add_custom_fields_preview() {
        if (get_option('wap_enable_custom_fields') !== 'yes') {
            return;
        }

        ?>
        <div class="wap-custom-fields-preview">
            <h3><?php _e('Custom Fields Preview', 'wooaccordion-pro'); ?></h3>
            <div class="wap-preview-info">
                <p><?php _e('The following custom fields will be automatically converted to accordion tabs:', 'wooaccordion-pro'); ?></p>

                <?php if (function_exists('acf_get_field_groups')): ?>
                    <h4><?php _e('ACF Field Groups', 'wooaccordion-pro'); ?></h4>
                    <?php
                    $acf_groups = get_option('wap_acf_field_groups', array());
                    if (!empty($acf_groups)):
                    ?>
                        <ul class="wap-field-list">
                            <?php foreach ($acf_groups as $group_key): ?>
                                <?php
                                $group = acf_get_field_group($group_key);
                                if ($group):
                                ?>
                                    <li>
                                        <strong><?php echo esc_html($group['title']); ?></strong>
                                        <span class="wap-priority">(Priority: <?php echo get_option('wap_acf_priority', 50); ?>)</span>
                                    </li>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </ul>
                    <?php else: ?>
                        <p><em><?php _e('No ACF field groups selected.', 'wooaccordion-pro'); ?></em></p>
                    <?php endif; ?>
                <?php endif; ?>

                <?php
                $custom_meta_config = get_option('wap_custom_meta_config', array());
                if (!empty($custom_meta_config)):
                ?>
                    <h4><?php _e('Custom Meta Fields', 'wooaccordion-pro'); ?></h4>
                    <ul class="wap-field-list">
                        <?php foreach ($custom_meta_config as $field): ?>
                            <li>
                                <strong><?php echo esc_html($field['title']); ?></strong>
                                <code><?php echo esc_html($field['meta_key']); ?></code>
                                <span class="wap-format">(<?php echo esc_html($field['format']); ?>)</span>
                                <span class="wap-priority">(Priority: <?php echo esc_html($field['priority']); ?>)</span>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
        </div>

        <style>
        .wap-custom-fields-preview {
            background: #f9f9f9;
            border: 1px solid #ddd;
            border-radius: 4px;
            padding: 15px;
            margin: 15px 0;
        }

        .wap-field-list {
            margin: 10px 0;
        }

        .wap-field-list li {
            padding: 5px 0;
            border-bottom: 1px solid #eee;
        }

        .wap-priority,
        .wap-format {
            color: #666;
            font-size: 0.9em;
            margin-left: 10px;
        }

        .wap-field-list code {
            background: #fff;
            padding: 2px 6px;
            border-radius: 3px;
            margin-left: 10px;
        }
        </style>
        <?php
    }

    /**
     * Validate custom fields settings
     */
    public function validate_custom_fields_settings($input) {
        $validated = array();

        // Validate JSON configuration
        if (isset($input['wap_custom_meta_config_json'])) {
            $json = stripslashes($input['wap_custom_meta_config_json']);
            $decoded = json_decode($json, true);

            if (json_last_error() === JSON_ERROR_NONE) {
                $validated['wap_custom_meta_config_json'] = $json;

                // Validate each field configuration
                $valid_config = array();
                foreach ($decoded as $field_config) {
                    if (isset($field_config['meta_key']) && isset($field_config['title'])) {
                        $valid_field = array(
                            'meta_key' => sanitize_key($field_config['meta_key']),
                            'title' => sanitize_text_field($field_config['title']),
                            'format' => isset($field_config['format']) ? sanitize_key($field_config['format']) : 'text',
                            'priority' => isset($field_config['priority']) ? absint($field_config['priority']) : 70
                        );
                        $valid_config[] = $valid_field;
                    }
                }

                update_option('wap_custom_meta_config', $valid_config);
            } else {
                add_settings_error(
                    'wap_settings',
                    'invalid_custom_meta_json',
                    __('Invalid JSON format in Custom Meta Configuration.', 'wooaccordion-pro')
                );
            }
        }

        return $validated;
    }    
    
    
/**
 * AJAX handler for saving settings
 */
public function ajax_save_settings() {
    // Verify nonce
    if (!check_ajax_referer('wap_admin_nonce', 'nonce', false)) {
        wp_send_json_error(array('message' => __('Security check failed', 'wooaccordion-pro')));
        return;
    }

    // Check user permissions
    if (!current_user_can('manage_woocommerce')) {
        wp_send_json_error(array('message' => __('Insufficient permissions', 'wooaccordion-pro')));
        return;
    }

    try {
        // Get all settings from the form
        $settings = $this->get_settings();
        $updated_count = 0;

        foreach ($settings as $setting) {
            if (isset($setting['id']) && $setting['type'] !== 'title' && $setting['type'] !== 'sectionend') {
                $option_name = $setting['id'];
                
                if (isset($_POST[$option_name])) {
                    $value = $this->sanitize_setting_value($_POST[$option_name], $setting);
                    update_option($option_name, $value);
                    $updated_count++;
                } else if ($setting['type'] === 'checkbox') {
                    // Handle unchecked checkboxes
                    update_option($option_name, 'no');
                    $updated_count++;
                }
            }
        }

        // Handle multiselect fields separately
        $multiselect_fields = array('wap_acf_field_groups', 'wap_meta_box_groups');
        foreach ($multiselect_fields as $field) {
            if (isset($_POST[$field])) {
                $value = is_array($_POST[$field]) ? array_map('sanitize_text_field', $_POST[$field]) : array();
                update_option($field, $value);
            } else {
                update_option($field, array());
            }
        }

        // Custom fields configuration
        $this->save_custom_fields_config();

        wp_send_json_success(array(
            'message' => sprintf(__('Settings saved successfully! Updated %d options.', 'wooaccordion-pro'), $updated_count)
        ));

    } catch (Exception $e) {
        wp_send_json_error(array(
            'message' => __('Error saving settings: ', 'wooaccordion-pro') . $e->getMessage()
        ));
    }
}

/**
 * Sanitize setting value based on type
 */
private function sanitize_setting_value($value, $setting) {
    switch ($setting['type']) {
        case 'textarea':
            return sanitize_textarea_field($value);
        case 'email':
            return sanitize_email($value);
        case 'url':
            return esc_url_raw($value);
        case 'number':
            return absint($value);
        case 'color':
            return sanitize_hex_color($value);
        case 'checkbox':
            return $value === 'yes' ? 'yes' : 'no';
        default:
            return sanitize_text_field($value);
    }
}    
    
/**
 * Log save attempts for debugging
 */
private function log_save_attempt($data) {
    if (defined('WP_DEBUG') && WP_DEBUG) {
        error_log('WAP Settings Save Attempt: ' . print_r($data, true));
    }
}    
    
    
}