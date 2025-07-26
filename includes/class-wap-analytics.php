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
        // Add analytics menu
        add_action('admin_menu', array($this, 'add_analytics_menu'));
        
        // Initialize additional hooks
        $this->init_additional_hooks();
        
        // Enqueue admin scripts for analytics page
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        
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
    }

    /**
     * Add analytics submenu under WooCommerce
     */
    public function add_analytics_menu() {
        add_submenu_page(
            'woocommerce',
            __('Accordion Analytics', 'wooaccordion-pro'),
            __('Accordion Analytics', 'wooaccordion-pro'),
            'manage_woocommerce',
            'wap-analytics',
            array($this, 'analytics_page')
        );
    }

    /**
     * Enqueue admin scripts for analytics page
     */
    public function enqueue_admin_scripts($hook) {
        if (strpos($hook, 'wap-analytics') !== false) {
            wp_enqueue_style(
                'wap-analytics-css',
                WAP_PLUGIN_URL . 'assets/css/admin.css',
                array(),
                WAP_VERSION
            );

            wp_enqueue_script(
                'wap-analytics-js',
                WAP_PLUGIN_URL . 'assets/js/admin.js',
                array('jquery'),
                WAP_VERSION,
                true
            );
        }
    }

    /**
     * Analytics page content
     */
    public function analytics_page() {
        // Check if analytics is enabled
        if (get_option('wap_enable_analytics') !== 'yes') {
            echo '<div class="wrap">';
            echo '<h1>' . __('Accordion Analytics', 'wooaccordion-pro') . '</h1>';
            echo '<div class="notice notice-warning"><p>';
            echo __('Analytics is currently disabled. Please enable it in the ', 'wooaccordion-pro');
            echo '<a href="' . admin_url('admin.php?page=wap-settings') . '">' . __('settings page', 'wooaccordion-pro') . '</a>.';
            echo '</p></div>';
            echo '</div>';
            return;
        }

        // Debug: Check if table exists and show some debug info
        global $wpdb;
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '{$this->table_name}'") == $this->table_name;
        $total_rows = $table_exists ? $wpdb->get_var("SELECT COUNT(*) FROM {$this->table_name}") : 0;

        // Get date range from URL parameters
        $date_from = isset($_GET['date_from']) ? sanitize_text_field($_GET['date_from']) : date('Y-m-d', strtotime('-30 days'));
        $date_to = isset($_GET['date_to']) ? sanitize_text_field($_GET['date_to']) : date('Y-m-d');

        // Get analytics data
        $daily_stats = $this->get_daily_stats(30);
        $device_breakdown = $this->get_device_breakdown($date_from, $date_to);
        $conversion_impact = $this->get_conversion_impact($date_from, $date_to);
        $top_sections = $this->get_top_sections(10, $date_from, $date_to);
        $total_interactions = $this->get_total_interactions($date_from, $date_to);

        ?>
        <div class="wrap wap-analytics-wrap">
            <h1><?php _e('Accordion Analytics Dashboard', 'wooaccordion-pro'); ?></h1>
            
            <?php if (defined('WP_DEBUG') && WP_DEBUG): ?>
            <!-- Debug Information -->
            <div class="notice notice-info">
                <p><strong>Debug Info:</strong></p>
                <ul>
                    <li>Analytics Table Exists: <?php echo $table_exists ? 'Yes' : 'No'; ?></li>
                    <li>Total Records in Database: <?php echo number_format($total_rows); ?></li>
                    <li>Analytics Setting: <?php echo get_option('wap_enable_analytics', 'not set'); ?></li>
                    <li>Accordion Setting: <?php echo get_option('wap_enable_accordion', 'not set'); ?></li>
                    <li>Table Name: <?php echo $this->table_name; ?></li>
                </ul>
                <?php if (!$table_exists): ?>
                <p><strong style="color: red;">Analytics table does not exist! Please deactivate and reactivate the plugin.</strong></p>
                <?php endif; ?>
            </div>
            <?php endif; ?>
            
            <!-- Date Range Filter -->
            <div class="wap-analytics-filters">
                <form method="get" action="">
                    <input type="hidden" name="page" value="wap-analytics">
                    <label for="date_from"><?php _e('From:', 'wooaccordion-pro'); ?></label>
                    <input type="date" id="date_from" name="date_from" value="<?php echo esc_attr($date_from); ?>">
                    
                    <label for="date_to"><?php _e('To:', 'wooaccordion-pro'); ?></label>
                    <input type="date" id="date_to" name="date_to" value="<?php echo esc_attr($date_to); ?>">
                    
                    <input type="submit" class="button" value="<?php _e('Filter', 'wooaccordion-pro'); ?>">
                    
                    <a href="<?php echo admin_url('admin.php?page=wap-analytics&export=csv&date_from=' . $date_from . '&date_to=' . $date_to); ?>" 
                       class="button button-secondary"><?php _e('Export CSV', 'wooaccordion-pro'); ?></a>
                    
                    <button type="button" class="button" onclick="wapTestTracking()"><?php _e('Test Tracking', 'wooaccordion-pro'); ?></button>
                </form>
            </div>

            <!-- Summary Cards -->
            <div class="wap-analytics-overview">
                <div class="wap-analytics-cards">
                    <div class="wap-analytics-card">
                        <h3><?php _e('Total Interactions', 'wooaccordion-pro'); ?></h3>
                        <div class="wap-analytics-number">
                            <?php echo number_format($total_interactions); ?>
                        </div>
                        <p class="wap-analytics-description">
                            <?php _e('All accordion clicks in selected period', 'wooaccordion-pro'); ?>
                        </p>
                    </div>
                    
                    <div class="wap-analytics-card">
                        <h3><?php _e('Products with Views', 'wooaccordion-pro'); ?></h3>
                        <div class="wap-analytics-number">
                            <?php echo $conversion_impact ? number_format($conversion_impact->products_with_interactions) : 0; ?>
                        </div>
                        <p class="wap-analytics-description">
                            <?php _e('Unique products with accordion interactions', 'wooaccordion-pro'); ?>
                        </p>
                    </div>
                    
                    <div class="wap-analytics-card">
                        <h3><?php _e('Engagement Rate', 'wooaccordion-pro'); ?></h3>
                        <div class="wap-analytics-number">
                            <?php echo $conversion_impact ? $conversion_impact->info_engagement_rate . '%' : '0%'; ?>
                        </div>
                        <p class="wap-analytics-description">
                            <?php _e('Users engaging with product information', 'wooaccordion-pro'); ?>
                        </p>
                    </div>

                    <div class="wap-analytics-card">
                        <h3><?php _e('Daily Average', 'wooaccordion-pro'); ?></h3>
                        <div class="wap-analytics-number">
                            <?php 
                            $days_count = max(1, count($daily_stats));
                            $daily_avg = $total_interactions / $days_count;
                            echo number_format($daily_avg, 1); 
                            ?>
                        </div>
                        <p class="wap-analytics-description">
                            <?php _e('Average interactions per day', 'wooaccordion-pro'); ?>
                        </p>
                    </div>
                </div>
            </div>

            <!-- Charts Section -->
            <div class="wap-analytics-charts">
                <div class="wap-chart-section">
                    <h3><?php _e('Daily Interactions Trend', 'wooaccordion-pro'); ?></h3>
                    <div class="wap-chart-container">
                        <?php if (!empty($daily_stats)): ?>
                            <canvas id="wap-daily-chart" width="800" height="300"></canvas>
                            <script>
                                // Simple chart data for daily stats
                                const dailyData = <?php echo json_encode($daily_stats); ?>;
                            </script>
                        <?php else: ?>
                            <p><?php _e('No data available for the selected period.', 'wooaccordion-pro'); ?></p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Device Breakdown -->
            <div class="wap-analytics-section">
                <h3><?php _e('Device Usage Breakdown', 'wooaccordion-pro'); ?></h3>
                <div class="wap-table-container">
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
                            <?php if (!empty($device_breakdown)): ?>
                                <?php foreach ($device_breakdown as $device): ?>
                                    <tr>
                                        <td>
                                            <span class="wap-device-icon">
                                                <?php
                                                $icon = 'desktop';
                                                if ($device->device_type === 'mobile') $icon = 'smartphone';
                                                if ($device->device_type === 'tablet') $icon = 'tablet';
                                                echo '<span class="dashicons dashicons-' . $icon . '"></span>';
                                                ?>
                                            </span>
                                            <?php echo esc_html(ucfirst($device->device_type)); ?>
                                        </td>
                                        <td><?php echo number_format($device->interactions); ?></td>
                                        <td><?php echo number_format($device->products); ?></td>
                                        <td>
                                            <div class="wap-percentage-bar">
                                                <div class="wap-percentage-fill" style="width: <?php echo esc_attr($device->percentage); ?>%"></div>
                                                <span class="wap-percentage-text"><?php echo esc_html($device->percentage); ?>%</span>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="4" class="wap-no-data">
                                        <?php _e('No device data available for the selected period.', 'wooaccordion-pro'); ?>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Top Performing Sections -->
            <div class="wap-analytics-section">
                <h3><?php _e('Top Performing Accordion Sections', 'wooaccordion-pro'); ?></h3>
                <div class="wap-table-container">
                    <table class="wp-list-table widefat fixed striped">
                        <thead>
                            <tr>
                                <th><?php _e('Section', 'wooaccordion-pro'); ?></th>
                                <th><?php _e('Total Clicks', 'wooaccordion-pro'); ?></th>
                                <th><?php _e('Products', 'wooaccordion-pro'); ?></th>
                                <th><?php _e('Avg per Product', 'wooaccordion-pro'); ?></th>
                                <th><?php _e('Performance', 'wooaccordion-pro'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($top_sections)): ?>
                                <?php 
                                $max_clicks = !empty($top_sections) ? $top_sections[0]->clicks : 1;
                                foreach ($top_sections as $section): 
                                    $avg_per_product = $section->products > 0 ? round($section->clicks / $section->products, 2) : 0;
                                    $performance_percent = ($section->clicks / $max_clicks) * 100;
                                ?>
                                    <tr>
                                        <td>
                                            <strong><?php echo esc_html(ucwords(str_replace('_', ' ', $section->accordion_section))); ?></strong>
                                        </td>
                                        <td><?php echo number_format($section->clicks); ?></td>
                                        <td><?php echo number_format($section->products); ?></td>
                                        <td><?php echo number_format($avg_per_product, 2); ?></td>
                                        <td>
                                            <div class="wap-performance-bar">
                                                <div class="wap-performance-fill" style="width: <?php echo esc_attr($performance_percent); ?>%"></div>
                                                <span class="wap-performance-text"><?php echo round($performance_percent); ?>%</span>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="5" class="wap-no-data">
                                        <?php _e('No section data available. Make sure analytics is enabled and users are interacting with accordions.', 'wooaccordion-pro'); ?>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="wap-analytics-actions">
                <h3><?php _e('Quick Actions', 'wooaccordion-pro'); ?></h3>
                <div class="wap-action-buttons">
                    <a href="<?php echo admin_url('admin.php?page=wap-settings'); ?>" class="button button-primary">
                        <?php _e('Configure Settings', 'wooaccordion-pro'); ?>
                    </a>
                    <button type="button" class="button" onclick="wapClearAnalytics()">
                        <?php _e('Clear Analytics Data', 'wooaccordion-pro'); ?>
                    </button>
                    <a href="<?php echo admin_url('admin.php?page=wap-analytics&export=csv&date_from=' . $date_from . '&date_to=' . $date_to); ?>" 
                       class="button button-secondary">
                        <?php _e('Export All Data', 'wooaccordion-pro'); ?>
                    </a>
                </div>
            </div>

            <?php if (empty($daily_stats) && empty($device_breakdown) && empty($top_sections)): ?>
                <div class="wap-analytics-empty">
                    <div class="wap-empty-state">
                        <h3><?php _e('No Analytics Data Yet', 'wooaccordion-pro'); ?></h3>
                        <p><?php _e('Start collecting data by visiting product pages with accordions enabled.', 'wooaccordion-pro'); ?></p>
                        <div class="wap-empty-checklist">
                            <h4><?php _e('Make sure:', 'wooaccordion-pro'); ?></h4>
                            <ul>
                                <li><?php _e('Analytics is enabled in settings', 'wooaccordion-pro'); ?></li>
                                <li><?php _e('Accordions are enabled and displaying', 'wooaccordion-pro'); ?></li>
                                <li><?php _e('Users are visiting product pages', 'wooaccordion-pro'); ?></li>
                                <li><?php _e('JavaScript is working properly', 'wooaccordion-pro'); ?></li>
                            </ul>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <script>
            function wapClearAnalytics() {
                if (confirm('<?php _e('Are you sure you want to clear all analytics data? This cannot be undone.', 'wooaccordion-pro'); ?>')) {
                    // Implementation for clearing analytics data
                    fetch(ajaxurl, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: 'action=wap_clear_analytics&nonce=<?php echo wp_create_nonce('wap_clear_analytics'); ?>'
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            location.reload();
                        } else {
                            alert('Error clearing data: ' + data.data.message);
                        }
                    });
                }
            }

            function wapTestTracking() {
                // Test the tracking functionality
                const testData = {
                    action: 'wap_track_interaction',
                    nonce: '<?php echo wp_create_nonce('wap_frontend_nonce'); ?>',
                    product_id: 1, // Test product ID
                    section: 'test_section',
                    action_type: 'open',
                    user_agent: navigator.userAgent,
                    device_type: 'desktop'
                };

                console.log('Testing tracking with data:', testData);

                fetch(ajaxurl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: new URLSearchParams(testData)
                })
                .then(response => response.json())
                .then(data => {
                    console.log('Tracking test response:', data);
                    if (data.success) {
                        alert('✅ Tracking test successful! Check the console for details.');
                        setTimeout(() => location.reload(), 1000);
                    } else {
                        alert('❌ Tracking test failed: ' + (data.data ? data.data.message : 'Unknown error'));
                    }
                })
                .catch(error => {
                    console.error('Tracking test error:', error);
                    alert('❌ Tracking test failed: ' + error.message);
                });
            }
        </script>

        <style>
            .wap-analytics-wrap {
                margin: 20px 20px 0 0;
            }

            .wap-analytics-filters {
                background: #fff;
                padding: 15px;
                border: 1px solid #ccd0d4;
                border-radius: 4px;
                margin-bottom: 20px;
            }

            .wap-analytics-filters label {
                margin-right: 10px;
                font-weight: 600;
            }

            .wap-analytics-filters input[type="date"] {
                margin-right: 15px;
            }

            .wap-analytics-cards {
                display: grid;
                grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
                gap: 20px;
                margin-bottom: 30px;
            }

            .wap-analytics-card {
                background: #fff;
                padding: 20px;
                border: 1px solid #ccd0d4;
                border-radius: 4px;
                text-align: center;
            }

            .wap-analytics-card h3 {
                margin: 0 0 10px 0;
                font-size: 14px;
                color: #666;
                text-transform: uppercase;
                letter-spacing: 0.5px;
            }

            .wap-analytics-number {
                font-size: 32px;
                font-weight: bold;
                color: #135e96;
                margin-bottom: 5px;
            }

            .wap-analytics-description {
                margin: 0;
                font-size: 12px;
                color: #666;
            }

            .wap-analytics-section {
                background: #fff;
                padding: 20px;
                border: 1px solid #ccd0d4;
                border-radius: 4px;
                margin-bottom: 20px;
            }

            .wap-analytics-section h3 {
                margin-top: 0;
                margin-bottom: 15px;
                padding-bottom: 10px;
                border-bottom: 1px solid #eee;
            }

            .wap-percentage-bar,
            .wap-performance-bar {
                position: relative;
                background: #f3f4f6;
                height: 20px;
                border-radius: 3px;
                overflow: hidden;
            }

            .wap-percentage-fill,
            .wap-performance-fill {
                background: linear-gradient(90deg, #10b981, #059669);
                height: 100%;
                transition: width 0.3s ease;
            }

            .wap-percentage-text,
            .wap-performance-text {
                position: absolute;
                top: 50%;
                left: 50%;
                transform: translate(-50%, -50%);
                font-size: 11px;
                font-weight: 600;
                color: #fff;
                text-shadow: 1px 1px 1px rgba(0,0,0,0.3);
            }

            .wap-device-icon .dashicons {
                margin-right: 5px;
                color: #666;
            }

            .wap-no-data {
                text-align: center;
                color: #666;
                font-style: italic;
                padding: 20px;
            }

            .wap-analytics-actions {
                background: #fff;
                padding: 20px;
                border: 1px solid #ccd0d4;
                border-radius: 4px;
            }

            .wap-action-buttons {
                display: flex;
                gap: 10px;
                flex-wrap: wrap;
            }

            .wap-analytics-empty {
                background: #fff;
                padding: 40px;
                border: 1px solid #ccd0d4;
                border-radius: 4px;
                text-align: center;
            }

            .wap-empty-state h3 {
                color: #666;
                margin-bottom: 15px;
            }

            .wap-empty-checklist {
                max-width: 400px;
                margin: 20px auto;
                text-align: left;
            }

            .wap-empty-checklist ul {
                list-style: none;
                padding: 0;
            }

            .wap-empty-checklist li {
                padding: 5px 0;
                position: relative;
                padding-left: 20px;
            }

            .wap-empty-checklist li:before {
                content: '✓';
                position: absolute;
                left: 0;
                color: #10b981;
                font-weight: bold;
            }

            @media (max-width: 768px) {
                .wap-analytics-cards {
                    grid-template-columns: 1fr;
                }
                
                .wap-action-buttons {
                    flex-direction: column;
                }
            }
        </style>
        <?php
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
        // Debug: Log the request
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('WAP Analytics: Track interaction called with data: ' . print_r($_POST, true));
        }

        // Check nonce
        if (!check_ajax_referer('wap_frontend_nonce', 'nonce', false)) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('WAP Analytics: Nonce verification failed');
            }
            wp_send_json_error(array('message' => 'Nonce verification failed'));
            return;
        }

        $product_id = isset($_POST['product_id']) ? absint($_POST['product_id']) : 0;
        $section = isset($_POST['section']) ? sanitize_text_field($_POST['section']) : '';
        $action = isset($_POST['action_type']) ? sanitize_text_field($_POST['action_type']) : ''; // Changed from 'action' to avoid conflict
        $user_agent = isset($_POST['user_agent']) ? sanitize_text_field($_POST['user_agent']) : '';
        $device_type = isset($_POST['device_type']) ? sanitize_text_field($_POST['device_type']) : '';

        // Debug: Log extracted data
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log("WAP Analytics: Extracted data - Product: $product_id, Section: $section, Action: $action");
        }

        if ($product_id && $section && $action) {
            $success = $this->track_interaction($product_id, $section, $action, $user_agent, $device_type);
            
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log("WAP Analytics: Track interaction result: " . ($success ? 'success' : 'failed'));
            }
            
            wp_send_json_success(array('tracked' => $success, 'message' => 'Interaction tracked'));
        } else {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log("WAP Analytics: Invalid data - missing required fields");
            }
            wp_send_json_error(array('message' => 'Invalid data - missing required fields'));
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

        // First get the total count
        $total_query = "SELECT COUNT(*) as total FROM {$this->table_name} {$where_clause}";
        $total_count = $wpdb->get_var(empty($params) ? $total_query : $wpdb->prepare($total_query, $params));
        
        if ($total_count == 0) {
            return array();
        }

        // Then get device breakdown with percentage calculation
        $query = "SELECT 
                    device_type,
                    COUNT(*) as interactions,
                    COUNT(DISTINCT product_id) as products,
                    ROUND((COUNT(*) * 100.0 / %d), 2) as percentage
                  FROM {$this->table_name} 
                  {$where_clause}
                  GROUP BY device_type
                  ORDER BY interactions DESC";

        // Add total count to params
        $final_params = array($total_count);
        if (!empty($params)) {
            $final_params = array_merge($final_params, $params);
        }

        return $wpdb->get_results($wpdb->prepare($query, $final_params));
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

    /**
     * Handle CSV export request
     */
    public function handle_export_request() {
        if (isset($_GET['page']) && $_GET['page'] === 'wap-analytics' && isset($_GET['export']) && $_GET['export'] === 'csv') {
            $date_from = isset($_GET['date_from']) ? sanitize_text_field($_GET['date_from']) : '';
            $date_to = isset($_GET['date_to']) ? sanitize_text_field($_GET['date_to']) : '';
            
            $this->export_to_csv($date_from, $date_to);
        }
    }

    /**
     * AJAX handler for clearing analytics data
     */
    public function ajax_clear_analytics() {
        check_ajax_referer('wap_clear_analytics', 'nonce');

        if (!current_user_can('manage_woocommerce')) {
            wp_send_json_error(array('message' => 'Insufficient permissions'));
        }

        global $wpdb;
        $result = $wpdb->query("TRUNCATE TABLE {$this->table_name}");

        if ($result !== false) {
            wp_send_json_success(array('message' => 'Analytics data cleared successfully'));
        } else {
            wp_send_json_error(array('message' => 'Failed to clear analytics data'));
        }
    }

    /**
     * Initialize additional hooks for export and clear functions
     */
    public function init_additional_hooks() {
        add_action('admin_init', array($this, 'handle_export_request'));
        add_action('wp_ajax_wap_clear_analytics', array($this, 'ajax_clear_analytics'));
    }
}