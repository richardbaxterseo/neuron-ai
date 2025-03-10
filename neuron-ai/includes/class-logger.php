<?php
/**
 * The logger class.
 *
 * @since      1.0.0
 * @package    NeuronAI
 */

namespace NeuronAI;

/**
 * Logger class.
 *
 * Handles logging for API interactions.
 */
class Logger {

    /**
     * The log table name.
     *
     * @since    1.0.0
     * @access   private
     * @var      string    $table_name    The log table name.
     */
    private $table_name;

    /**
     * Whether debug mode is active.
     *
     * @since    1.0.0
     * @access   private
     * @var      bool    $debug_mode    Whether debug mode is active.
     */
    private $debug_mode;

    /**
     * Initialize the class.
     *
     * @since    1.0.0
     */
    public function __construct() {
        global $wpdb;
        $this->table_name = $wpdb->prefix . 'neuron_ai_logs';
        $this->debug_mode = get_option('neuron_ai_debug_mode', false);

        // Set up AJAX handlers for logs
        add_action('wp_ajax_neuron_ai_get_logs', [$this, 'ajax_get_logs']);
        add_action('wp_ajax_neuron_ai_clear_logs', [$this, 'ajax_clear_logs']);

        // Set up log cleanup
        add_action('neuron_ai_cleanup_logs', [$this, 'cleanup_logs']);

        // Schedule log cleanup if not already scheduled
        if (!wp_next_scheduled('neuron_ai_cleanup_logs')) {
            wp_schedule_event(time(), 'daily', 'neuron_ai_cleanup_logs');
        }
    }

    /**
     * Create the logs table.
     *
     * @since    1.0.0
     */
    public function create_logs_table() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE $this->table_name (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            timestamp datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
            request_type varchar(50) NOT NULL,
            provider varchar(50),
            status_code smallint(6),
            details longtext,
            PRIMARY KEY  (id),
            KEY request_type (request_type),
            KEY provider (provider),
            KEY timestamp (timestamp)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    /**
     * Log a request.
     *
     * @since    1.0.0
     * @param    string    $request_type    The request type.
     * @param    array     $details         The request details.
     * @return   int|false                  The log ID or false on failure.
     */
    public function log_request($request_type, $details = []) {
        // Skip logging if debug mode is disabled and this is not an important request
        if (!$this->debug_mode && !in_array($request_type, ['error', 'enhance', 'search', 'chat'])) {
            return false;
        }
        
        global $wpdb;
        
        $data = [
            'request_type' => $request_type,
            'provider' => isset($details['provider']) ? $details['provider'] : null,
            'details' => maybe_serialize($details),
        ];
        
        $result = $wpdb->insert($this->table_name, $data);
        
        if ($result) {
            return $wpdb->insert_id;
        }
        
        return false;
    }

    /**
     * Log a response.
     *
     * @since    1.0.0
     * @param    int       $status_code    The HTTP status code.
     * @param    array     $details        The response details.
     * @return   int|false                 The log ID or false on failure.
     */
    public function log_response($status_code, $details = []) {
        // Skip logging if debug mode is disabled and this is a successful response
        if (!$this->debug_mode && $status_code >= 200 && $status_code < 300) {
            return false;
        }
        
        global $wpdb;
        
        $data = [
            'request_type' => 'response',
            'status_code' => $status_code,
            'details' => maybe_serialize($details),
        ];
        
        $result = $wpdb->insert($this->table_name, $data);
        
        if ($result) {
            return $wpdb->insert_id;
        }
        
        return false;
    }

    /**
     * Log an error.
     *
     * @since    1.0.0
     * @param    int       $code       The error code.
     * @param    string    $message    The error message.
     * @param    array     $details    Additional error details.
     * @return   int|false             The log ID or false on failure.
     */
    public function log_error($code, $message, $details = []) {
        global $wpdb;
        
        $error_data = [
            'code' => $code,
            'error_message' => $message,
            'details' => $details,
        ];
        
        $data = [
            'request_type' => 'error',
            'status_code' => $code,
            'details' => maybe_serialize($error_data),
        ];
        
        $result = $wpdb->insert($this->table_name, $data);
        
        if ($result) {
            return $wpdb->insert_id;
        }
        
        return false;
    }

    /**
     * Get logs with filtering.
     *
     * @since    1.0.0
     * @param    array     $filters    The filters to apply.
     * @return   array                 The logs.
     */
    public function get_logs($filters = []) {
        global $wpdb;
        
        $defaults = [
            'provider' => '',
            'request_type' => '',
            'status' => '',
            'date_range' => '24h',
            'page' => 1,
            'limit' => 50,
            'order_by' => 'timestamp',
            'order' => 'DESC',
        ];
        
        $filters = wp_parse_args($filters, $defaults);
        
        // Build WHERE clause
        $where = [];
        $where_args = [];
        
        if (!empty($filters['provider'])) {
            $where[] = 'provider = %s';
            $where_args[] = $filters['provider'];
        }
        
        if (!empty($filters['request_type'])) {
            $where[] = 'request_type = %s';
            $where_args[] = $filters['request_type'];
        }
        
        if (!empty($filters['status'])) {
            if ($filters['status'] === 'success') {
                $where[] = 'status_code >= 200 AND status_code < 300';
            } elseif ($filters['status'] === 'error') {
                $where[] = '(status_code < 200 OR status_code >= 300 OR request_type = "error")';
            }
        }
        
        // Add date range filter
        if (!empty($filters['date_range']) && $filters['date_range'] !== 'all') {
            $interval = '';
            
            switch ($filters['date_range']) {
                case '24h':
                    $interval = '1 DAY';
                    break;
                case '7d':
                    $interval = '7 DAY';
                    break;
                case '30d':
                    $interval = '30 DAY';
                    break;
            }
            
            if ($interval) {
                $where[] = 'timestamp >= DATE_SUB(NOW(), INTERVAL %s)';
                $where_args[] = $interval;
            }
        }
        
        // Support search by keyword
        if (!empty($filters['search'])) {
            $search_term = '%' . $wpdb->esc_like($filters['search']) . '%';
            $where[] = '(details LIKE %s)';
            $where_args[] = $search_term;
        }
        
        // Build the WHERE clause
        $where_sql = '';
        if (!empty($where)) {
            $where_sql = 'WHERE ' . implode(' AND ', $where);
        }
        
        // Sanitize order parameters
        $allowed_order_by = ['id', 'timestamp', 'request_type', 'provider', 'status_code'];
        if (!in_array($filters['order_by'], $allowed_order_by)) {
            $filters['order_by'] = 'timestamp';
        }
        
        $filters['order'] = strtoupper($filters['order']) === 'ASC' ? 'ASC' : 'DESC';
        
        // Calculate pagination
        $offset = ($filters['page'] - 1) * $filters['limit'];
        
        // Prepare the query
        $query = $wpdb->prepare(
            "SELECT SQL_CALC_FOUND_ROWS * FROM $this->table_name $where_sql ORDER BY {$filters['order_by']} {$filters['order']} LIMIT %d, %d",
            array_merge($where_args, [$offset, $filters['limit']])
        );
        
        // Get the logs
        $logs = $wpdb->get_results($query);
        
        // Get the total count
        $total = $wpdb->get_var('SELECT FOUND_ROWS()');
        
        // Format the logs
        $formatted_logs = [];
        foreach ($logs as $log) {
            $formatted_logs[] = [
                'id' => $log->id,
                'timestamp' => $log->timestamp,
                'request_type' => $log->request_type,
                'provider' => $log->provider,
                'status_code' => $log->status_code,
                'details' => maybe_unserialize($log->details),
            ];
        }
        
        return [
            'logs' => $formatted_logs,
            'pagination' => [
                'total' => $total,
                'page' => $filters['page'],
                'limit' => $filters['limit'],
                'total_pages' => ceil($total / $filters['limit']),
            ],
        ];
    }

    /**
     * Get log by ID.
     *
     * @since    1.0.0
     * @param    int       $log_id    The log ID.
     * @return   array|null           The log or null if not found.
     */
    public function get_log($log_id) {
        global $wpdb;
        
        $log = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM $this->table_name WHERE id = %d",
                $log_id
            )
        );
        
        if (!$log) {
            return null;
        }
        
        return [
            'id' => $log->id,
            'timestamp' => $log->timestamp,
            'request_type' => $log->request_type,
            'provider' => $log->provider,
            'status_code' => $log->status_code,
            'details' => maybe_unserialize($log->details),
        ];
    }

    /**
     * Clear all logs.
     *
     * @since    1.0.0
     * @return   int|false    The number of rows deleted or false on failure.
     */
    public function clear_logs() {
        global $wpdb;
        
        return $wpdb->query("TRUNCATE TABLE $this->table_name");
    }

    /**
     * Delete specific logs.
     *
     * @since    1.0.0
     * @param    array     $log_ids    Array of log IDs to delete.
     * @return   int|false             The number of rows deleted or false on failure.
     */
    public function delete_logs($log_ids) {
        if (empty($log_ids)) {
            return 0;
        }
        
        global $wpdb;
        
        // Prepare placeholders for the IDs
        $placeholders = implode(',', array_fill(0, count($log_ids), '%d'));
        
        return $wpdb->query(
            $wpdb->prepare(
                "DELETE FROM $this->table_name WHERE id IN ($placeholders)",
                $log_ids
            )
        );
    }

    /**
     * Cleanup old logs.
     *
     * @since    1.0.0
     * @return   int|false    The number of rows deleted or false on failure.
     */
    public function cleanup_logs() {
        global $wpdb;
        
        $retention_days = get_option('neuron_ai_log_retention', 30);
        
        // Skip if retention is set to 0 (keep forever)
        if ($retention_days <= 0) {
            return 0;
        }
        
        return $wpdb->query(
            $wpdb->prepare(
                "DELETE FROM $this->table_name WHERE timestamp < DATE_SUB(NOW(), INTERVAL %d DAY)",
                $retention_days
            )
        );
    }

    /**
     * Get log statistics.
     *
     * @since    1.0.0
     * @param    string    $period    The period to get statistics for (today, week, month, all).
     * @return   array                The log statistics.
     */
    public function get_log_stats($period = 'all') {
        global $wpdb;
        
        $where = '';
        
        // Add period filter
        switch ($period) {
            case 'today':
                $where = 'WHERE DATE(timestamp) = CURDATE()';
                break;
            case 'week':
                $where = 'WHERE timestamp >= DATE_SUB(NOW(), INTERVAL 7 DAY)';
                break;
            case 'month':
                $where = 'WHERE timestamp >= DATE_SUB(NOW(), INTERVAL 30 DAY)';
                break;
        }
        
        // Get total counts
        $total = $wpdb->get_var("SELECT COUNT(*) FROM $this->table_name $where");
        
        // Get counts by request type
        $request_types = $wpdb->get_results(
            "SELECT request_type, COUNT(*) as count FROM $this->table_name $where GROUP BY request_type",
            ARRAY_A
        );
        
        // Get counts by provider
        $providers = $wpdb->get_results(
            "SELECT provider, COUNT(*) as count FROM $this->table_name $where GROUP BY provider",
            ARRAY_A
        );
        
        // Get success vs error counts
        $success_count = $wpdb->get_var(
            "SELECT COUNT(*) FROM $this->table_name $where AND status_code >= 200 AND status_code < 300"
        );
        
        $error_count = $wpdb->get_var(
            "SELECT COUNT(*) FROM $this->table_name $where AND (status_code < 200 OR status_code >= 300 OR request_type = 'error')"
        );
        
        return [
            'total' => (int) $total,
            'request_types' => $request_types,
            'providers' => $providers,
            'success_count' => (int) $success_count,
            'error_count' => (int) $error_count,
        ];
    }

    /**
     * AJAX handler for getting logs.
     *
     * @since    1.0.0
     */
    public function ajax_get_logs() {
        // Check nonce
        if (!check_ajax_referer('neuron_ai_logs', '_wpnonce', false)) {
            wp_send_json_error(['message' => __('Invalid nonce.', 'neuron-ai')]);
        }
        
        // Check permissions
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('You do not have permission to access logs.', 'neuron-ai')]);
        }
        
        // Get filters
        $filters = [
            'provider' => isset($_POST['provider']) ? sanitize_text_field($_POST['provider']) : '',
            'request_type' => isset($_POST['request_type']) ? sanitize_text_field($_POST['request_type']) : '',
            'status' => isset($_POST['status']) ? sanitize_text_field($_POST['status']) : '',
            'date_range' => isset($_POST['date_range']) ? sanitize_text_field($_POST['date_range']) : '24h',
            'page' => isset($_POST['page']) ? intval($_POST['page']) : 1,
            'limit' => isset($_POST['limit']) ? intval($_POST['limit']) : 50,
            'order_by' => isset($_POST['order_by']) ? sanitize_text_field($_POST['order_by']) : 'timestamp',
            'order' => isset($_POST['order']) ? sanitize_text_field($_POST['order']) : 'DESC',
            'search' => isset($_POST['search']) ? sanitize_text_field($_POST['search']) : '',
        ];
        
        // Get logs
        $logs = $this->get_logs($filters);
        
        wp_send_json_success($logs);
    }

    /**
     * AJAX handler for clearing logs.
     *
     * @since    1.0.0
     */
    public function ajax_clear_logs() {
        // Check nonce
        if (!check_ajax_referer('neuron_ai_logs', '_wpnonce', false)) {
            wp_send_json_error(['message' => __('Invalid nonce.', 'neuron-ai')]);
        }
        
        // Check permissions
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('You do not have permission to clear logs.', 'neuron-ai')]);
        }
        
        // Clear logs
        $result = $this->clear_logs();
        
        if ($result !== false) {
            wp_send_json_success(['message' => __('Logs cleared successfully.', 'neuron-ai')]);
        } else {
            wp_send_json_error(['message' => __('Failed to clear logs.', 'neuron-ai')]);
        }
    }

    /**
     * AJAX handler for deleting specific logs.
     *
     * @since    1.0.0
     */
    public function ajax_delete_logs() {
        // Check nonce
        if (!check_ajax_referer('neuron_ai_logs', '_wpnonce', false)) {
            wp_send_json_error(['message' => __('Invalid nonce.', 'neuron-ai')]);
        }
        
        // Check permissions
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('You do not have permission to delete logs.', 'neuron-ai')]);
        }
        
        // Get log IDs
        $log_ids = isset($_POST['log_ids']) ? array_map('intval', (array) $_POST['log_ids']) : [];
        
        if (empty($log_ids)) {
            wp_send_json_error(['message' => __('No logs selected.', 'neuron-ai')]);
        }
        
        // Delete logs
        $result = $this->delete_logs($log_ids);
        
        if ($result !== false) {
            wp_send_json_success([
                'message' => sprintf(
                    _n(
                        '%d log deleted successfully.',
                        '%d logs deleted successfully.',
                        $result,
                        'neuron-ai'
                    ),
                    $result
                )
            ]);
        } else {
            wp_send_json_error(['message' => __('Failed to delete logs.', 'neuron-ai')]);
        }
    }

    /**
     * Get debug mode status.
     *
     * @since    1.0.0
     * @return   bool    Whether debug mode is active.
     */
    public function is_debug_mode() {
        return $this->debug_mode;
    }

    /**
     * Set debug mode status.
     *
     * @since    1.0.0
     * @param    bool    $debug_mode    Whether debug mode should be active.
     */
    public function set_debug_mode($debug_mode) {
        $this->debug_mode = (bool) $debug_mode;
        update_option('neuron_ai_debug_mode', $this->debug_mode);
    }

    /**
     * Register AJAX actions on plugin initialization.
     *
     * @since    1.0.0
     */
    public static function register_ajax_actions() {
        // Add AJAX actions if not already added
        if (!has_action('wp_ajax_neuron_ai_get_logs')) {
            add_action('wp_ajax_neuron_ai_get_logs', [self::class, 'ajax_get_logs_static']);
        }
        
        if (!has_action('wp_ajax_neuron_ai_clear_logs')) {
            add_action('wp_ajax_neuron_ai_clear_logs', [self::class, 'ajax_clear_logs_static']);
        }
        
        if (!has_action('wp_ajax_neuron_ai_delete_logs')) {
            add_action('wp_ajax_neuron_ai_delete_logs', [self::class, 'ajax_delete_logs_static']);
        }
    }

    /**
     * Static AJAX handler for getting logs.
     *
     * @since    1.0.0
     */
    public static function ajax_get_logs_static() {
        $logger = new self();
        $logger->ajax_get_logs();
    }

    /**
     * Static AJAX handler for clearing logs.
     *
     * @since    1.0.0
     */
    public static function ajax_clear_logs_static() {
        $logger = new self();
        $logger->ajax_clear_logs();
    }

    /**
     * Static AJAX handler for deleting specific logs.
     *
     * @since    1.0.0
     */
    public static function ajax_delete_logs_static() {
        $logger = new self();
        $logger->ajax_delete_logs();
    }
}