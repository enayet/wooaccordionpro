<?php
/**
 * WooAccordion Pro Analytics Class
 * 
 * Handles analytics tracking and reporting
 * 
 * @class WAP_Analytics
 * @version 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class WAP_Analytics {

    /**
     * Single instance of the class
     * @var WAP_Analytics
     */
    protected static $_instance = null;

    /**
     * Database table name
     * @var string
     */
    private $table_name;

    /**
     * Main WAP_Analytics Instance
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
        global $wpdb;
        $this->table_name = $wpdb->prefix . 'wap_analytics';
        $this->init_hooks();
    }

    /**
     * Initialize hooks
     */
    private function init_hooks() {
        // Only proceed if analytics is enabled
        if (get_option('wap_enable_analytics') !== 'yes') {
            return;
        }

        add_action('wp_ajax_wap_track_interaction', array($this, 'ajax_track_interaction'));
        add_action('wp_ajax_nopriv_wap_track_interaction', array($this, 'ajax_track_interaction'));
        
        // Cleanup old data
        add_action('wap_cleanup_analytics', array($this, 'cleanup_old_data'));
        
        // Schedule cleanup if not already scheduled
        if (!wp_next_scheduled('wap_cleanup_analytics')) {
            wp_schedule_event(time(), 'daily', 'wap_cleanup_analytics');
        }

        // Add analytics reports
        add_filter('woocommerce_admin_reports', array($this, 'add_analytics_reports'));
    }

    /**
     * Track accordion interaction
     */
    public function track_interaction($product_id, $section, $action, $user_agent = '', $device_type = '') {
        global $wpdb;

        if (get_option('wap_enable_analytics') !== 'yes') {
            return false;
        }

        // Get user agent and device type if not provided
        if (empty($user_agent)) {
            $user_agent = isset($_SERVER['HTTP_USER_AGENT']) ? sanitize_text_field($_SERVER['HTTP_USER_AGENT']) : '';
        }

        if (empty($device_type)) {
            $device_type = $this->detect_device_type($user_agent);
        }

        $data = array(
            'product_id' => absint($product_id),
            'accordion_section' => sanitize_text_field($section),
            'action' => sanitize_text_field($action),
            'user_agent' => substr($user_agent, 0, 255), // Limit to 255 chars
            'device_type' => sanitize_text_field($device_type),
            'timestamp' => current_time('mysql')
        );

        $result = $wpdb->insert($this->table_name, $data);

        return $result !== false;
    }

    /**
     * AJAX handler for tracking interactions
     */
    public function ajax_track_interaction() {
        check_ajax_referer('wap_frontend_nonce', 'nonce');

        $product_id = isset($_POST['product_id']) ? absint($_POST['product_id']) : 0;
        $section = isset($_POST['section']) ? sanitize_text_field($_POST['section']) : '';
        $action = isset($_POST['action']) ? sanitize_text_field($_POST['action']) : '';
        $user_agent = isset($_POST['user_agent']) ? sanitize_text_field($_POST['user_agent']) : '';
        $device_type = isset($_POST['device_type']) ? sanitize_text_field($_POST['device_type']) : '';

        if ($product_id && $section && $action) {
            $success = $this->track_interaction($product_id, $section, $action, $user_agent, $device_type);
            wp_send_json_success(array('tracked' => $success));
        } else {
            wp_send_json_error(array('message' => 'Invalid data'));
        }
    }

    /**
     * Detect device type from user agent
     */
    private function detect_device_type($user_agent) {
        $mobile_agents = array(
            'Mobile', 'Android', 'iPhone', 'iPad', 'iPod', 'BlackBerry', 
            'Windows Phone', 'Opera Mini', 'IEMobile'
        );

        foreach ($mobile_agents as $agent) {
            if (stripos($user_agent, $agent) !== false) {
                if (stripos($user_agent, 'iPad') !== false || 
                    (stripos($user_agent, 'Android') !== false && stripos($user_agent, 'Mobile') === false)) {
                    return 'tablet';
                }
                return 'mobile';
            }
        }

        return 'desktop';
    }

    /**
     * Get analytics data for a specific product
     */
    public function get_product_analytics($product_id, $date_from = '', $date_to = '') {
        global $wpdb;

        $where_clause = "WHERE product_id = %d";
        $params = array($product_id);

        if (!empty($date_from)) {
            $where_clause .= " AND timestamp >= %s";
            $params[] = $date_from . ' 00:00:00';
        }

        if (!empty($date_to)) {
            $where_clause .= " AND timestamp <= %s";
            $params[] = $date_to . ' 23:59:59';
        }

        $query = "SELECT 
                    accordion_section,
                    action,
                    device_type,
                    COUNT(*) as count,
                    DATE(timestamp) as date
                  FROM {$this->table_name} 
                  {$where_clause}
                  GROUP BY accordion_section, action, device_type, DATE(timestamp)
                  ORDER BY timestamp DESC";

        return $wpdb->get_results($wpdb->prepare($query, $params));
    }

    /**
     * Get top performing accordion sections
     */
    public function get_top_sections($limit = 10, $date_from = '', $date_to = '') {
        global $wpdb;

        $where_clause = "WHERE action = 'open'";
        $params = array();

        if (!empty($date_from)) {
            $where_clause .= " AND timestamp >= %s";
            $params[] = $date_from . ' 00:00:00';
        }

        if (!empty($date_to)) {
            $where_clause .= " AND timestamp <= %s";
            $params[] = $date_to . ' 23:59:59';
        }

        $query = "SELECT 
                    accordion_section,
                    COUNT(*) as clicks,
                    COUNT(DISTINCT product_id) as products
                  FROM {$this->table_name} 
                  {$where_clause}
                  GROUP BY accordion_section
                  ORDER BY clicks DESC
                  LIMIT %d";

        $params[] = $limit;

        return $wpdb->get_results($wpdb->prepare($query, $params));
    }

    /**
     * Get device breakdown
     */
    public function get_device_breakdown($date_from = '', $date_to = '') {
        global $wpdb;

        $where_clause = "WHERE 1=1";
        $params = array();

        if (!empty($date_from)) {
            $where_clause .= " AND timestamp >= %s";
            $params[] = $date_from . ' 00:00:00';
        }

        if (!empty($date_to)) {
            $where_clause .= " AND timestamp <= %s";
            $params[] = $date_to . ' 23:59:59';
        }

        $query = "SELECT 
                    device_type,
                    COUNT(*) as interactions,
                    COUNT(DISTINCT product_id) as products,
                    ROUND(COUNT(*) * 100.0 / SUM(COUNT(*)) OVER(), 2) as percentage
                  FROM {$this->table_name} 
                  {$where_clause}
                  GROUP BY device_type
                  ORDER BY interactions DESC";

        return $wpdb->get_results($wpdb->prepare($query, $params));
    }

    /**
     * Get daily interaction stats
     */
    public function get_daily_stats($days = 30) {
        global $wpdb;

        $date_from = date('Y-m-d', strtotime("-{$days} days"));

        $query = "SELECT 
                    DATE(timestamp) as date,
                    COUNT(*) as total_interactions,
                    COUNT(CASE WHEN action = 'open' THEN 1 END) as opens,
                    COUNT(CASE WHEN action = 'close' THEN 1 END) as closes,
                    COUNT(DISTINCT product_id) as products_viewed
                  FROM {$this->table_name} 
                  WHERE timestamp >= %s
                  GROUP BY DATE(timestamp)
                  ORDER BY date ASC";

        return $wpdb->get_results($wpdb->prepare($query, $date_from . ' 00:00:00'));
    }

    /**
     * Get conversion impact data
     */
    public function get_conversion_impact($date_from = '', $date_to = '') {
        global $wpdb;

        // This is a simplified version - in a real scenario, you'd join with order data
        $where_clause = "WHERE action = 'open'";
        $params = array();

        if (!empty($date_from)) {
            $where_clause .= " AND timestamp >= %s";
            $params[] = $date_from . ' 00:00:00';
        }

        if (!empty($date_to)) {
            $where_clause .= " AND timestamp <= %s";
            $params[] = $date_to . ' 23:59:59';
        }

        $query = "SELECT 
                    COUNT(DISTINCT product_id) as products_with_interactions,
                    COUNT(*) as total_opens,
                    ROUND(AVG(
                        CASE WHEN accordion_section IN ('description', 'additional_information') 
                        THEN 1 ELSE 0 END
                    ) * 100, 2) as info_engagement_rate
                  FROM {$this->table_name} 
                  {$where_clause}";

        return $wpdb->get_row($wpdb->prepare($query, $params));
    }

    /**
     * Cleanup old analytics data
     */
    public function cleanup_old_data() {
        global $wpdb;

        $retention_days = get_option('wap_analytics_retention', '90');
        
        if ($retention_days == '0') {
            return; // Keep forever
        }

        $cutoff_date = date('Y-m-d H:i:s', strtotime("-{$retention_days} days"));

        $wpdb->query($wpdb->prepare(
            "DELETE FROM {$this->table_name} WHERE timestamp < %s",
            $cutoff_date
        ));
    }

    /**
     * Add analytics reports to WooCommerce
     */
    public function add_analytics_reports($reports) {
        $reports['accordions'] = array(
            'title' => __('Accordions', 'wooaccordion-pro'),
            'reports' => array(
                'accordion_overview' => array(
                    'title' => __('Accordion Overview', 'wooaccordion-pro'),
                    'description' => '',
                    'hide_title' => true,
                    'callback' => array($this, 'output_overview_report')
                ),
                'accordion_sections' => array(
                    'title' => __('Section Performance', 'wooaccordion-pro'),
                    'description' => '',
                    'hide_title' => true,
                    'callback' => array($this, 'output_sections_report')
                )
            )
        );

        return $reports;
    }

    /**
     * Output overview report
     */
    public function output_overview_report() {
        $date_from = isset($_GET['start_date']) ? sanitize_text_field($_GET['start_date']) : date('Y-m-d', strtotime('-30 days'));
        $date_to = isset($_GET['end_date']) ? sanitize_text_field($_GET['end_date']) : date('Y-m-d');

        $daily_stats = $this->get_daily_stats(30);
        $device_breakdown = $this->get_device_breakdown($date_from, $date_to);
        $conversion_impact = $this->get_conversion_impact($date_from, $date_to);

        ?>
        <div class="wap-analytics-overview">
            <h2><?php _e('Accordion Analytics Overview', 'wooaccordion-pro'); ?></h2>
            
            <div class="wap-analytics-cards">
                <div class="wap-analytics-card">
                    <h3><?php _e('Total Interactions', 'wooaccordion-pro'); ?></h3>
                    <div class="wap-analytics-number">
                        <?php echo array_sum(array_column($daily_stats, 'total_interactions')); ?>
                    </div>
                </div>
                
                <div class="wap-analytics-card">
                    <h3><?php _e('Products with Accordion Views', 'wooaccordion-pro'); ?></h3>
                    <div class="wap-analytics-number">
                        <?php echo $conversion_impact ? $conversion_impact->products_with_interactions : 0; ?>
                    </div>
                </div>
                
                <div class="wap-analytics-card">
                    <h3><?php _e('Info Engagement Rate', 'wooaccordion-pro'); ?></h3>
                    <div class="wap-analytics-number">
                        <?php echo $conversion_impact ? $conversion_impact->info_engagement_rate . '%' : '0%'; ?>
                    </div>
                </div>
            </div>

            <div class="wap-analytics-charts">
                <div class="wap-chart-container">
                    <h3><?php _e('Daily Interactions', 'wooaccordion-pro'); ?></h3>
                    <canvas id="wap-daily-chart" width="400" height="200"></canvas>
                </div>
                
                <div class="wap-chart-container">
                    <h3><?php _e('Device Breakdown', 'wooaccordion-pro'); ?></h3>
                    <canvas id="wap-device-chart" width="400" height="200"></canvas>
                </div>
            </div>

            <script>
                // Simple chart implementation would go here
                // For MVP, we'll show the data in tables instead
            </script>

            <div class="wap-analytics-tables">
                <div class="wap-table-container">
                    <h3><?php _e('Device Usage', 'wooaccordion-pro'); ?></h3>
                    <table class="wp-list-table widefat fixed striped">
                        <thead>
                            <tr>
                                <th><?php _e('Device Type', 'wooaccordion-pro'); ?></th>
                                <th><?php _e('Interactions', 'wooaccordion-pro'); ?></th>
                                <th><?php _e('Products', 'wooaccordion-pro'); ?></th>
                                <th><?php _e('Percentage', 'wooaccordion-pro'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($device_breakdown as $device) : ?>
                                <tr>
                                    <td><?php echo esc_html(ucfirst($device->device_type)); ?></td>
                                    <td><?php echo esc_html($device->interactions); ?></td>
                                    <td><?php echo esc_html($device->products); ?></td>
                                    <td><?php echo esc_html($device->percentage); ?>%</td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * Output sections report
     */
    public function output_sections_report() {
        $date_from = isset($_GET['start_date']) ? sanitize_text_field($_GET['start_date']) : date('Y-m-d', strtotime('-30 days'));
        $date_to = isset($_GET['end_date']) ? sanitize_text_field($_GET['end_date']) : date('Y-m-d');

        $top_sections = $this->get_top_sections(20, $date_from, $date_to);

        ?>
        <div class="wap-analytics-sections">
            <h2><?php _e('Accordion Section Performance', 'wooaccordion-pro'); ?></h2>
            
            <div class="wap-table-container">
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th><?php _e('Section', 'wooaccordion-pro'); ?></th>
                            <th><?php _e('Total Clicks', 'wooaccordion-pro'); ?></th>
                            <th><?php _e('Products', 'wooaccordion-pro'); ?></th>
                            <th><?php _e('Avg Clicks per Product', 'wooaccordion-pro'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($top_sections as $section) : ?>
                            <tr>
                                <td>
                                    <strong><?php echo esc_html(ucwords(str_replace('_', ' ', $section->accordion_section))); ?></strong>
                                </td>
                                <td><?php echo esc_html($section->clicks); ?></td>
                                <td><?php echo esc_html($section->products); ?></td>
                                <td><?php echo esc_html(round($section->clicks / max($section->products, 1), 2)); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <?php if (empty($top_sections)) : ?>
                <div class="wap-no-data">
                    <p><?php _e('No accordion interaction data available for the selected date range.', 'wooaccordion-pro'); ?></p>
                    <p><?php _e('Make sure analytics is enabled and visit some product pages to start collecting data.', 'wooaccordion-pro'); ?></p>
                </div>
            <?php endif; ?>
        </div>
        <?php
    }

    /**
     * Get total interaction count
     */
    public function get_total_interactions($date_from = '', $date_to = '') {
        global $wpdb;

        $where_clause = "WHERE 1=1";
        $params = array();

        if (!empty($date_from)) {
            $where_clause .= " AND timestamp >= %s";
            $params[] = $date_from . ' 00:00:00';
        }

        if (!empty($date_to)) {
            $where_clause .= " AND timestamp <= %s";
            $params[] = $date_to . ' 23:59:59';
        }

        $query = "SELECT COUNT(*) FROM {$this->table_name} {$where_clause}";

        if (empty($params)) {
            return $wpdb->get_var($query);
        }

        return $wpdb->get_var($wpdb->prepare($query, $params));
    }

    /**
     * Export analytics data to CSV
     */
    public function export_to_csv($date_from = '', $date_to = '') {
        global $wpdb;

        $where_clause = "WHERE 1=1";
        $params = array();

        if (!empty($date_from)) {
            $where_clause .= " AND timestamp >= %s";
            $params[] = $date_from . ' 00:00:00';
        }

        if (!empty($date_to)) {
            $where_clause .= " AND timestamp <= %s";
            $params[] = $date_to . ' 23:59:59';
        }

        $query = "SELECT * FROM {$this->table_name} {$where_clause} ORDER BY timestamp DESC";

        $results = empty($params) ? $wpdb->get_results($query, ARRAY_A) : $wpdb->get_results($wpdb->prepare($query, $params), ARRAY_A);

        if (empty($results)) {
            return false;
        }

        $filename = 'wooaccordion-analytics-' . date('Y-m-d') . '.csv';

        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="' . $filename . '"');

        $output = fopen('php://output', 'w');

        // Add headers
        fputcsv($output, array_keys($results[0]));

        // Add data
        foreach ($results as $row) {
            fputcsv($output, $row);
        }

        fclose($output);
        exit;
    }
}