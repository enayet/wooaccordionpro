<?php
/**
 * WooAccordion Pro Custom Field Integration
 * 
 * Handles integration with ACF, Meta Box, and other custom field plugins
 * 
 * @class WAP_Custom_Fields
 * @version 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class WAP_Custom_Fields {

    /**
     * Single instance of the class
     * @var WAP_Custom_Fields
     */
    protected static $_instance = null;

    /**
     * Supported field types
     * @var array
     */
    private $supported_field_types = array();

    /**
     * Field type handlers
     * @var array
     */
    private $field_handlers = array();

    /**
     * Main WAP_Custom_Fields Instance
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
        $this->register_field_handlers();
    }

    /**
     * Initialize hooks
     */
    private function init_hooks() {
        // Only proceed if custom field integration is enabled
        if (get_option('wap_enable_custom_fields') !== 'yes') {
            return;
        }

        add_filter('wap_accordion_tabs', array($this, 'add_custom_field_tabs'), 10, 2);
        add_action('admin_init', array($this, 'detect_field_plugins'));
        add_action('admin_notices', array($this, 'show_field_plugin_notices'));
    }

    /**
     * Register field type handlers
     */
    private function register_field_handlers() {
        $this->field_handlers = array(
            'acf' => array($this, 'handle_acf_fields'),
            'meta_box' => array($this, 'handle_meta_box_fields'),
            'custom_meta' => array($this, 'handle_custom_meta_fields'),
            'woocommerce' => array($this, 'handle_woocommerce_fields')
        );

        $this->supported_field_types = array(
            'text', 'textarea', 'wysiwyg', 'select', 'checkbox', 'radio',
            'number', 'email', 'url', 'date', 'time', 'datetime',
            'image', 'gallery', 'file', 'repeater', 'group', 'flexible_content'
        );
    }

    /**
     * Add custom field tabs to accordion
     */
    public function add_custom_field_tabs($tabs, $product_id) {
        if (!$product_id) {
            return $tabs;
        }

        $custom_tabs = array();

        // Get ACF fields
        if ($this->is_acf_active()) {
            $acf_tabs = $this->get_acf_tabs($product_id);
            $custom_tabs = array_merge($custom_tabs, $acf_tabs);
        }

        // Get Meta Box fields
        if ($this->is_meta_box_active()) {
            $meta_box_tabs = $this->get_meta_box_tabs($product_id);
            $custom_tabs = array_merge($custom_tabs, $meta_box_tabs);
        }

        // Get custom meta fields
        $custom_meta_tabs = $this->get_custom_meta_tabs($product_id);
        $custom_tabs = array_merge($custom_tabs, $custom_meta_tabs);

        // Get WooCommerce custom fields
        $woo_custom_tabs = $this->get_woocommerce_custom_tabs($product_id);
        $custom_tabs = array_merge($custom_tabs, $woo_custom_tabs);

        // Merge with existing tabs
        $tabs = array_merge($tabs, $custom_tabs);

        return $tabs;
    }

    /**
     * Check if ACF is active
     */
    private function is_acf_active() {
        return class_exists('ACF') || function_exists('get_field');
    }

    /**
     * Check if Meta Box is active
     */
    private function is_meta_box_active() {
        return class_exists('RWMB_Loader') || function_exists('rwmb_meta');
    }

    /**
     * Get ACF tabs for product
     */
    private function get_acf_tabs($product_id) {
        if (!$this->is_acf_active()) {
            return array();
        }

        $tabs = array();
        $acf_settings = get_option('wap_acf_settings', array());

        // Get field groups for this product
        $field_groups = $this->get_acf_field_groups($product_id);

        foreach ($field_groups as $group) {
            if (empty($group['fields'])) {
                continue;
            }

            $tab_key = 'acf_' . sanitize_key($group['title']);
            $tab_content = $this->render_acf_field_group($group, $product_id);

            // Only add tab if there's content
            if (!empty($tab_content)) {
                $tabs[$tab_key] = array(
                    'title' => $group['title'],
                    'priority' => isset($acf_settings['priority']) ? $acf_settings['priority'] : 50,
                    'callback' => function() use ($tab_content) {
                        echo $tab_content;
                    },
                    'source' => 'acf'
                );
            }
        }

        return $tabs;
    }

    /**
     * Get ACF field groups for product
     */
    private function get_acf_field_groups($product_id) {
        if (!function_exists('acf_get_field_groups')) {
            return array();
        }

        $groups = acf_get_field_groups(array(
            'post_id' => $product_id,
            'post_type' => 'product'
        ));

        $field_groups = array();
        foreach ($groups as $group) {
            $fields = acf_get_fields($group);
            if (!empty($fields)) {
                $group['fields'] = $fields;
                $field_groups[] = $group;
            }
        }

        return $field_groups;
    }

    /**
     * Render ACF field group content
     */
    private function render_acf_field_group($group, $product_id) {
        $content = '';
        $show_empty = get_option('wap_show_empty_fields', 'no') === 'yes';

        foreach ($group['fields'] as $field) {
            $value = get_field($field['name'], $product_id);
            
            // Skip empty fields unless configured to show them
            if (empty($value) && !$show_empty) {
                continue;
            }

            $content .= $this->render_field_content($field, $value, 'acf');
        }

        return $content;
    }

    /**
     * Get Meta Box tabs for product
     */
    private function get_meta_box_tabs($product_id) {
        if (!$this->is_meta_box_active()) {
            return array();
        }

        $tabs = array();
        $meta_box_settings = get_option('wap_meta_box_settings', array());

        // Get meta boxes for this product
        $meta_boxes = $this->get_meta_box_fields($product_id);

        foreach ($meta_boxes as $meta_box) {
            $tab_key = 'mb_' . sanitize_key($meta_box['id']);
            $tab_content = $this->render_meta_box_fields($meta_box, $product_id);

            if (!empty($tab_content)) {
                $tabs[$tab_key] = array(
                    'title' => $meta_box['title'],
                    'priority' => isset($meta_box_settings['priority']) ? $meta_box_settings['priority'] : 60,
                    'callback' => function() use ($tab_content) {
                        echo $tab_content;
                    },
                    'source' => 'meta_box'
                );
            }
        }

        return $tabs;
    }

    /**
     * Get Meta Box fields for product
     */
    private function get_meta_box_fields($product_id) {
        if (!function_exists('rwmb_get_registry')) {
            return array();
        }

        $registry = rwmb_get_registry('meta_box');
        $meta_boxes = $registry->get_by(array(
            'object_type' => 'post',
            'post_types' => array('product')
        ));

        return $meta_boxes;
    }

    /**
     * Render Meta Box fields content
     */
    private function render_meta_box_fields($meta_box, $product_id) {
        $content = '';
        $show_empty = get_option('wap_show_empty_fields', 'no') === 'yes';

        if (isset($meta_box['fields'])) {
            foreach ($meta_box['fields'] as $field) {
                $value = rwmb_meta($field['id'], '', $product_id);
                
                if (empty($value) && !$show_empty) {
                    continue;
                }

                $content .= $this->render_field_content($field, $value, 'meta_box');
            }
        }

        return $content;
    }

    /**
     * Get custom meta tabs for product
     */
    private function get_custom_meta_tabs($product_id) {
        $tabs = array();
        $custom_meta_config = get_option('wap_custom_meta_config', array());

        if (empty($custom_meta_config)) {
            return $tabs;
        }

        foreach ($custom_meta_config as $config) {
            $meta_value = get_post_meta($product_id, $config['meta_key'], true);
            
            if (empty($meta_value) && get_option('wap_show_empty_fields', 'no') !== 'yes') {
                continue;
            }

            $tab_key = 'custom_' . sanitize_key($config['meta_key']);
            $content = $this->format_custom_meta_content($meta_value, $config);

            $tabs[$tab_key] = array(
                'title' => $config['title'],
                'priority' => isset($config['priority']) ? $config['priority'] : 70,
                'callback' => function() use ($content) {
                    echo $content;
                },
                'source' => 'custom_meta'
            );
        }

        return $tabs;
    }

    /**
     * Get WooCommerce custom tabs
     */
    private function get_woocommerce_custom_tabs($product_id) {
        $tabs = array();
        $product = wc_get_product($product_id);
        
        if (!$product) {
            return $tabs;
        }

        // Get product attributes
        $attributes = $product->get_attributes();
        
        foreach ($attributes as $attribute) {
            if (!$attribute->get_visible()) {
                continue;
            }

            $tab_key = 'attr_' . sanitize_key($attribute->get_name());
            $content = $this->render_attribute_content($attribute, $product);

            $tabs[$tab_key] = array(
                'title' => wc_attribute_label($attribute->get_name()),
                'priority' => 80,
                'callback' => function() use ($content) {
                    echo $content;
                },
                'source' => 'woocommerce'
            );
        }

        return $tabs;
    }

    /**
     * Render field content based on type
     */
    private function render_field_content($field, $value, $source) {
        $content = '';
        $field_type = isset($field['type']) ? $field['type'] : 'text';
        $field_label = isset($field['label']) ? $field['label'] : (isset($field['name']) ? $field['name'] : '');

        // Add field wrapper
        $content .= '<div class="wap-field-wrapper wap-field-' . esc_attr($field_type) . '">';
        
        // Add field label if configured
        if (get_option('wap_show_field_labels', 'yes') === 'yes' && !empty($field_label)) {
            $content .= '<h4 class="wap-field-label">' . esc_html($field_label) . '</h4>';
        }

        // Render content based on field type
        switch ($field_type) {
            case 'wysiwyg':
            case 'textarea':
                $content .= '<div class="wap-field-content">' . wpautop($value) . '</div>';
                break;

            case 'image':
                $content .= $this->render_image_field($value, $field);
                break;

            case 'gallery':
                $content .= $this->render_gallery_field($value, $field);
                break;

            case 'file':
                $content .= $this->render_file_field($value, $field);
                break;

            case 'select':
            case 'radio':
            case 'checkbox':
                $content .= $this->render_choice_field($value, $field);
                break;

            case 'repeater':
                $content .= $this->render_repeater_field($value, $field);
                break;

            case 'group':
                $content .= $this->render_group_field($value, $field);
                break;

            case 'url':
                $content .= '<div class="wap-field-content"><a href="' . esc_url($value) . '" target="_blank">' . esc_html($value) . '</a></div>';
                break;

            case 'email':
                $content .= '<div class="wap-field-content"><a href="mailto:' . esc_attr($value) . '">' . esc_html($value) . '</a></div>';
                break;

            case 'number':
                $content .= '<div class="wap-field-content">' . number_format_i18n($value) . '</div>';
                break;

            case 'date':
            case 'datetime':
                $content .= '<div class="wap-field-content">' . date_i18n(get_option('date_format'), strtotime($value)) . '</div>';
                break;

            default:
                $content .= '<div class="wap-field-content">' . wpautop(esc_html($value)) . '</div>';
                break;
        }

        $content .= '</div>';

        return $content;
    }

    /**
     * Render image field
     */
    private function render_image_field($value, $field) {
        if (empty($value)) {
            return '';
        }

        $content = '<div class="wap-image-field">';
        
        if (is_array($value)) {
            // ACF image array
            $image_url = isset($value['url']) ? $value['url'] : '';
            $alt_text = isset($value['alt']) ? $value['alt'] : '';
        } else {
            // Image ID or URL
            if (is_numeric($value)) {
                $image_url = wp_get_attachment_url($value);
                $alt_text = get_post_meta($value, '_wp_attachment_image_alt', true);
            } else {
                $image_url = $value;
                $alt_text = '';
            }
        }

        if ($image_url) {
            $content .= '<img src="' . esc_url($image_url) . '" alt="' . esc_attr($alt_text) . '" class="wap-field-image" />';
        }

        $content .= '</div>';
        return $content;
    }

    /**
     * Render gallery field
     */
    private function render_gallery_field($value, $field) {
        if (empty($value)) {
            return '';
        }

        $content = '<div class="wap-gallery-field">';
        
        if (is_array($value)) {
            foreach ($value as $image) {
                $content .= $this->render_image_field($image, $field);
            }
        }

        $content .= '</div>';
        return $content;
    }

    /**
     * Render file field
     */
    private function render_file_field($value, $field) {
        if (empty($value)) {
            return '';
        }

        $content = '<div class="wap-file-field">';
        
        if (is_array($value)) {
            $file_url = isset($value['url']) ? $value['url'] : '';
            $file_title = isset($value['title']) ? $value['title'] : basename($file_url);
        } else {
            if (is_numeric($value)) {
                $file_url = wp_get_attachment_url($value);
                $file_title = get_the_title($value);
            } else {
                $file_url = $value;
                $file_title = basename($file_url);
            }
        }

        if ($file_url) {
            $content .= '<a href="' . esc_url($file_url) . '" target="_blank" class="wap-file-link">';
            $content .= '<i class="fas fa-download"></i> ' . esc_html($file_title);
            $content .= '</a>';
        }

        $content .= '</div>';
        return $content;
    }

    /**
     * Render choice field (select, radio, checkbox)
     */
    private function render_choice_field($value, $field) {
        $content = '<div class="wap-choice-field">';
        
        if (is_array($value)) {
            $content .= '<ul class="wap-choice-list">';
            foreach ($value as $choice) {
                $content .= '<li>' . esc_html($choice) . '</li>';
            }
            $content .= '</ul>';
        } else {
            $content .= '<div class="wap-choice-single">' . esc_html($value) . '</div>';
        }

        $content .= '</div>';
        return $content;
    }

    /**
     * Render repeater field
     */
    private function render_repeater_field($value, $field) {
        if (empty($value) || !is_array($value)) {
            return '';
        }

        $content = '<div class="wap-repeater-field">';
        
        foreach ($value as $index => $row) {
            $content .= '<div class="wap-repeater-row">';
            $content .= '<h5 class="wap-repeater-title">' . sprintf(__('Item %d', 'wooaccordion-pro'), $index + 1) . '</h5>';
            
            if (is_array($row)) {
                foreach ($row as $sub_field_key => $sub_field_value) {
                    $sub_field = array(
                        'label' => $sub_field_key,
                        'type' => 'text'
                    );
                    $content .= $this->render_field_content($sub_field, $sub_field_value, 'acf');
                }
            } else {
                $content .= '<div class="wap-field-content">' . esc_html($row) . '</div>';
            }
            
            $content .= '</div>';
        }

        $content .= '</div>';
        return $content;
    }

    /**
     * Render group field
     */
    private function render_group_field($value, $field) {
        if (empty($value) || !is_array($value)) {
            return '';
        }

        $content = '<div class="wap-group-field">';
        
        foreach ($value as $sub_field_key => $sub_field_value) {
            $sub_field = array(
                'label' => $sub_field_key,
                'type' => 'text'
            );
            $content .= $this->render_field_content($sub_field, $sub_field_value, 'acf');
        }

        $content .= '</div>';
        return $content;
    }

    /**
     * Render attribute content
     */
    private function render_attribute_content($attribute, $product) {
        $content = '<div class="wap-attribute-content">';
        
        if ($attribute->is_taxonomy()) {
            $terms = wc_get_product_terms($product->get_id(), $attribute->get_name(), array('fields' => 'names'));
            if (!empty($terms)) {
                $content .= '<ul class="wap-attribute-list">';
                foreach ($terms as $term) {
                    $content .= '<li>' . esc_html($term) . '</li>';
                }
                $content .= '</ul>';
            }
        } else {
            $values = $attribute->get_options();
            if (!empty($values)) {
                $content .= '<div class="wap-attribute-values">' . esc_html(implode(', ', $values)) . '</div>';
            }
        }

        $content .= '</div>';
        return $content;
    }

    /**
     * Format custom meta content
     */
    private function format_custom_meta_content($value, $config) {
        $format = isset($config['format']) ? $config['format'] : 'text';
        
        switch ($format) {
            case 'html':
                return '<div class="wap-custom-meta">' . wp_kses_post($value) . '</div>';
            
            case 'url':
                return '<div class="wap-custom-meta"><a href="' . esc_url($value) . '" target="_blank">' . esc_html($value) . '</a></div>';
            
            case 'email':
                return '<div class="wap-custom-meta"><a href="mailto:' . esc_attr($value) . '">' . esc_html($value) . '</a></div>';
            
            case 'json':
                $decoded = json_decode($value, true);
                if (json_last_error() === JSON_ERROR_NONE) {
                    return '<div class="wap-custom-meta"><pre>' . esc_html(print_r($decoded, true)) . '</pre></div>';
                }
                return '<div class="wap-custom-meta">' . esc_html($value) . '</div>';
            
            default:
                return '<div class="wap-custom-meta">' . wpautop(esc_html($value)) . '</div>';
        }
    }

    /**
     * Detect available field plugins
     */
    public function detect_field_plugins() {
        $detected_plugins = array();
        
        if ($this->is_acf_active()) {
            $detected_plugins['acf'] = 'Advanced Custom Fields';
        }
        
        if ($this->is_meta_box_active()) {
            $detected_plugins['meta_box'] = 'Meta Box';
        }
        
        update_option('wap_detected_field_plugins', $detected_plugins);
    }

    /**
     * Show field plugin notices
     */
    public function show_field_plugin_notices() {
        if (get_option('wap_enable_custom_fields') !== 'yes') {
            return;
        }

        $detected_plugins = get_option('wap_detected_field_plugins', array());
        
        if (empty($detected_plugins)) {
            echo '<div class="notice notice-warning"><p>';
            echo '<strong>WooAccordion Pro:</strong> ';
            echo __('Custom Fields integration is enabled but no supported field plugins detected. Install ACF or Meta Box to use this feature.', 'wooaccordion-pro');
            echo '</p></div>';
        } else {
            echo '<div class="notice notice-success is-dismissible"><p>';
            echo '<strong>WooAccordion Pro:</strong> ';
            echo sprintf(__('Custom Fields integration active with: %s', 'wooaccordion-pro'), implode(', ', $detected_plugins));
            echo '</p></div>';
        }
    }
}