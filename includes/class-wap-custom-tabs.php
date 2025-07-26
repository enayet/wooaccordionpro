<?php
/**
 * WooAccordion Pro Custom Tabs Manager
 * 
 * Handles custom tab creation and conditional logic
 */

if (!defined('ABSPATH')) {
    exit;
}

class WAP_Custom_Tabs {

    protected static $_instance = null;

    public static function instance() {
        if (is_null(self::$_instance)) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    public function __construct() {
        add_action('init', array($this, 'init'));
        add_action('wp_ajax_wap_save_custom_tab', array($this, 'ajax_save_custom_tab'));
        add_action('wp_ajax_wap_delete_custom_tab', array($this, 'ajax_delete_custom_tab'));
        add_action('wp_ajax_wap_get_custom_tab', array($this, 'ajax_get_custom_tab'));
    }

    public function init() {
        // Hook into AJAX handlers only (frontend integration is handled by WAP_Frontend)
        // We don't need to hook into tab filtering here as it's handled in the frontend class
    }

    /**
     * Get all custom tabs
     */
    public function get_custom_tabs() {
        return get_option('wap_custom_tabs', array());
    }

    /**
     * Save custom tab
     */
    public function save_custom_tab($tab_id, $tab_data) {
        $custom_tabs = $this->get_custom_tabs();
        
        // Sanitize tab data
        $tab_data = $this->sanitize_tab_data($tab_data);
        
        if (empty($tab_id)) {
            // Generate new tab ID
            $tab_id = 'tab_' . time() . '_' . wp_rand(1000, 9999);
        }
        
        $custom_tabs[$tab_id] = $tab_data;
        
        return update_option('wap_custom_tabs', $custom_tabs);
    }

    /**
     * Delete custom tab
     */
    public function delete_custom_tab($tab_id) {
        $custom_tabs = $this->get_custom_tabs();
        
        if (isset($custom_tabs[$tab_id])) {
            unset($custom_tabs[$tab_id]);
            return update_option('wap_custom_tabs', $custom_tabs);
        }
        
        return false;
    }

    /**
     * Sanitize tab data
     */
    private function sanitize_tab_data($data) {
        return array(
            'title' => sanitize_text_field($data['title'] ?? ''),
            'content' => wp_kses_post($data['content'] ?? ''),
            'priority' => intval($data['priority'] ?? 50),
            'enabled' => !empty($data['enabled']),
            'conditions' => array(
                'categories' => array_map('intval', $data['conditions']['categories'] ?? array()),
                'user_roles' => array_map('sanitize_text_field', $data['conditions']['user_roles'] ?? array()),
                'product_types' => array_map('sanitize_text_field', $data['conditions']['product_types'] ?? array())
            )
        );
    }

    /**
     * AJAX: Save custom tab
     */
    public function ajax_save_custom_tab() {
        check_ajax_referer('wap_admin_nonce', 'nonce');

        if (!current_user_can('manage_woocommerce')) {
            wp_send_json_error(array('message' => __('Insufficient permissions', 'wooaccordion-pro')));
        }

        $tab_id = sanitize_text_field($_POST['tab_id'] ?? '');
        $tab_data = $_POST['tab_data'] ?? array();

        if ($this->save_custom_tab($tab_id, $tab_data)) {
            wp_send_json_success(array(
                'message' => __('Custom tab saved successfully!', 'wooaccordion-pro'),
                'tab_id' => $tab_id ?: 'tab_' . time()
            ));
        } else {
            wp_send_json_error(array('message' => __('Failed to save custom tab', 'wooaccordion-pro')));
        }
    }

    /**
     * AJAX: Delete custom tab
     */
    public function ajax_delete_custom_tab() {
        check_ajax_referer('wap_admin_nonce', 'nonce');

        if (!current_user_can('manage_woocommerce')) {
            wp_send_json_error(array('message' => __('Insufficient permissions', 'wooaccordion-pro')));
        }

        $tab_id = sanitize_text_field($_POST['tab_id'] ?? '');

        if ($this->delete_custom_tab($tab_id)) {
            wp_send_json_success(array('message' => __('Custom tab deleted successfully!', 'wooaccordion-pro')));
        } else {
            wp_send_json_error(array('message' => __('Failed to delete custom tab', 'wooaccordion-pro')));
        }
    }

    /**
     * AJAX: Get custom tab data
     */
    public function ajax_get_custom_tab() {
        check_ajax_referer('wap_admin_nonce', 'nonce');

        if (!current_user_can('manage_woocommerce')) {
            wp_send_json_error(array('message' => __('Insufficient permissions', 'wooaccordion-pro')));
        }

        $tab_id = sanitize_text_field($_POST['tab_id'] ?? '');
        $custom_tabs = $this->get_custom_tabs();

        if (isset($custom_tabs[$tab_id])) {
            wp_send_json_success(array('tab_data' => $custom_tabs[$tab_id]));
        } else {
            wp_send_json_error(array('message' => __('Tab not found', 'wooaccordion-pro')));
        }
    }

    /**
     * Get available product categories for conditions
     */
    public function get_product_categories() {
        $categories = get_terms(array(
            'taxonomy' => 'product_cat',
            'hide_empty' => false,
        ));

        $options = array();
        foreach ($categories as $category) {
            $options[$category->term_id] = $category->name;
        }

        return $options;
    }

    /**
     * Get available user roles for conditions
     */
    public function get_user_roles() {
        global $wp_roles;
        
        $roles = array();
        foreach ($wp_roles->roles as $role_key => $role) {
            $roles[$role_key] = $role['name'];
        }
        
        return $roles;
    }

    /**
     * Get available product types for conditions
     */
    public function get_product_types() {
        return array(
            'simple' => __('Simple Product', 'wooaccordion-pro'),
            'grouped' => __('Grouped Product', 'wooaccordion-pro'),
            'external' => __('External Product', 'wooaccordion-pro'),
            'variable' => __('Variable Product', 'wooaccordion-pro'),
        );
    }
}