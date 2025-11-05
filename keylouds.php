<?php
/**
 * Plugin Name: Keylouds
 * Plugin URI: https://github.com/markfenske84/keylouds
 * Description: Create keyword clouds from any URL and display them with shortcodes or Gutenberg blocks
 * Version: 1.1.7
 * Author: Webfor Agency
 * Author URI: https://webfor.com
 * License: GPL v2 or later
 * Text Domain: keylouds
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Initialize Plugin Update Checker (only for non-WordPress.org installations)
if (!defined('KEYLOUDS_DISABLE_UPDATES') && file_exists(__DIR__ . '/plugin-update-checker/plugin-update-checker.php')) {
    require_once __DIR__ . '/plugin-update-checker/plugin-update-checker.php';
    
    $keyloudsUpdateChecker = YahnisElsts\PluginUpdateChecker\v5p4\PucFactory::buildUpdateChecker(
        'https://github.com/markfenske84/keylouds',
        __FILE__,
        'keylouds'
    );
    
    // Set the branch to check for updates
    $keyloudsUpdateChecker->setBranch('main');
}

// Define plugin constants
define('KEYLOUDS_VERSION', '1.1.7');
define('KEYLOUDS_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('KEYLOUDS_PLUGIN_URL', plugin_dir_url(__FILE__));

class Keylouds {
    
    private static $instance = null;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        // Activation/Deactivation hooks
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
        
        // Initialize plugin
        add_action('plugins_loaded', array($this, 'init'));
        
        // Check for database updates
        add_action('plugins_loaded', array($this, 'check_database_updates'));
        
        // Add settings link on plugins page
        add_filter('plugin_action_links_' . plugin_basename(__FILE__), array($this, 'add_plugin_action_links'));
    }
    
    public function activate() {
        $this->create_database_table();
        flush_rewrite_rules();
        
        // Set database version
        update_option('keylouds_db_version', '1.1.0');
    }
    
    public function add_plugin_action_links($links) {
        $settings_link = '<a href="' . admin_url('admin.php?page=keylouds-settings') . '">' . __('Settings', 'keylouds') . '</a>';
        array_unshift($links, $settings_link);
        return $links;
    }
    
    public function check_database_updates() {
        $current_version = get_option('keylouds_db_version', '1.0.0');
        
        if (version_compare($current_version, '1.1.0', '<')) {
            $this->migrate_to_1_1_0();
            update_option('keylouds_db_version', '1.1.0');
        }
    }
    
    private function migrate_to_1_1_0() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'keylouds';
        
        // Check if layout_seed column exists
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
        $column_exists = $wpdb->get_results($wpdb->prepare(
            "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS 
            WHERE TABLE_SCHEMA = %s AND TABLE_NAME = %s AND COLUMN_NAME = 'layout_seed'",
            DB_NAME,
            $table_name
        ));
        
        // Add layout_seed column if it doesn't exist
        if (empty($column_exists)) {
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
            $wpdb->query(
                "ALTER TABLE {$table_name} ADD COLUMN layout_seed int(11) DEFAULT NULL AFTER keywords" // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.SchemaChange
            );
            
            // Generate random seeds for existing clouds
            $clouds = $wpdb->get_results( "SELECT id FROM {$table_name}" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
            foreach ($clouds as $cloud) {
                $seed = wp_rand(1000, 9999999);
                // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
                $wpdb->update(
                    $table_name,
                    array('layout_seed' => $seed),
                    array('id' => $cloud->id),
                    array('%d'),
                    array('%d')
                );
            }
        }
    }
    
    public function deactivate() {
        flush_rewrite_rules();
    }
    
    private function create_database_table() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'keylouds';
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            title varchar(255) NOT NULL,
            url varchar(500) NOT NULL,
            keywords longtext NOT NULL,
            layout_seed int(11) DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
            PRIMARY KEY (id)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
    
    public function init() {
        // Admin menu
        add_action('admin_menu', array($this, 'add_admin_menu'));
        
        // Register settings
        add_action('admin_init', array($this, 'register_settings'));
        
        // Enqueue scripts
        add_action('admin_enqueue_scripts', array($this, 'admin_enqueue_scripts'));
        add_action('wp_enqueue_scripts', array($this, 'frontend_enqueue_scripts'));
        
        // AJAX actions
        add_action('wp_ajax_keylouds_scrape', array($this, 'ajax_scrape_url'));
        add_action('wp_ajax_keylouds_delete', array($this, 'ajax_delete_cloud'));
        add_action('wp_ajax_keylouds_shuffle', array($this, 'ajax_shuffle_layout'));
        
        // Register shortcode
        add_shortcode('keycloud', array($this, 'shortcode_handler'));
        
        // Register Gutenberg block
        add_action('init', array($this, 'register_block'));
    }
    
    public function add_admin_menu() {
        add_menu_page(
            __('Keylouds', 'keylouds'),
            __('Keylouds', 'keylouds'),
            'manage_options',
            'keylouds',
            array($this, 'admin_page'),
            'dashicons-tag',
            30
        );
        
        add_submenu_page(
            'keylouds',
            __('Settings', 'keylouds'),
            __('Settings', 'keylouds'),
            'manage_options',
            'keylouds-settings',
            array($this, 'settings_page')
        );
    }
    
    public function admin_enqueue_scripts($hook) {
        // Enqueue on main admin page
        if ('toplevel_page_keylouds' === $hook) {
            wp_enqueue_style('keylouds-admin', KEYLOUDS_PLUGIN_URL . 'assets/css/admin.css', array(), KEYLOUDS_VERSION);
            wp_enqueue_style('keylouds-frontend', KEYLOUDS_PLUGIN_URL . 'assets/css/frontend.css', array(), KEYLOUDS_VERSION);
            
            // Enqueue wordcloud2 library for preview
            wp_enqueue_script('wordcloud2', KEYLOUDS_PLUGIN_URL . 'assets/js/wordcloud2.js', array(), '1.2.2', true);
            wp_enqueue_script('keylouds-frontend', KEYLOUDS_PLUGIN_URL . 'assets/js/frontend.js', array('wordcloud2'), KEYLOUDS_VERSION, true);
            
            wp_enqueue_script('keylouds-admin', KEYLOUDS_PLUGIN_URL . 'assets/js/admin.js', array('keylouds-frontend'), KEYLOUDS_VERSION, true);
            
            wp_localize_script('keylouds-admin', 'keyloudsAdmin', array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('keylouds_nonce')
            ));
        }
        
        // Enqueue color picker on settings page
        if ('keylouds_page_keylouds-settings' === $hook) {
            wp_enqueue_style('wp-color-picker');
            wp_enqueue_style('keylouds-admin', KEYLOUDS_PLUGIN_URL . 'assets/css/admin.css', array(), KEYLOUDS_VERSION);
            wp_enqueue_script('keylouds-settings', KEYLOUDS_PLUGIN_URL . 'assets/js/settings.js', array('wp-color-picker'), KEYLOUDS_VERSION, true);
        }
    }
    
    public function frontend_enqueue_scripts() {
        wp_enqueue_style('keylouds-frontend', KEYLOUDS_PLUGIN_URL . 'assets/css/frontend.css', array(), KEYLOUDS_VERSION);
        
        // Enqueue wordcloud2.js library
        wp_enqueue_script('wordcloud2', KEYLOUDS_PLUGIN_URL . 'assets/js/wordcloud2.js', array(), '1.2.2', true);
        
        // Enqueue frontend initialization script
        wp_enqueue_script('keylouds-frontend', KEYLOUDS_PLUGIN_URL . 'assets/js/frontend.js', array('wordcloud2'), KEYLOUDS_VERSION, true);
    }
    
    public function admin_page() {
        require_once KEYLOUDS_PLUGIN_DIR . 'includes/admin-page.php';
    }
    
    public function settings_page() {
        require_once KEYLOUDS_PLUGIN_DIR . 'includes/settings-page.php';
    }
    
    public function register_settings() {
        register_setting('keylouds_settings', 'keylouds_color_small', array(
            'type' => 'string',
            'default' => '#a3d0e0',
            'sanitize_callback' => 'sanitize_hex_color'
        ));
        
        register_setting('keylouds_settings', 'keylouds_color_medium', array(
            'type' => 'string',
            'default' => '#3498db',
            'sanitize_callback' => 'sanitize_hex_color'
        ));
        
        register_setting('keylouds_settings', 'keylouds_color_large', array(
            'type' => 'string',
            'default' => '#2271b1',
            'sanitize_callback' => 'sanitize_hex_color'
        ));
    }
    
    private function get_color_for_size($font_size, $custom_colors = array()) {
        // Use custom colors if provided, otherwise use defaults
        if (!empty($custom_colors) && isset($custom_colors['small']) && isset($custom_colors['medium']) && isset($custom_colors['large'])) {
            $color_small = $custom_colors['small'];
            $color_medium = $custom_colors['medium'];
            $color_large = $custom_colors['large'];
        } else {
            $color_small = get_option('keylouds_color_small', '#a3d0e0');
            $color_medium = get_option('keylouds_color_medium', '#3498db');
            $color_large = get_option('keylouds_color_large', '#2271b1');
        }
        
        if ($font_size < 1.0) {
            return $color_small;
        } elseif ($font_size < 1.5) {
            return $color_medium;
        } else {
            return $color_large;
        }
    }
    
    public function ajax_scrape_url() {
        check_ajax_referer('keylouds_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Unauthorized');
        }
        
        $url = isset($_POST['url']) ? esc_url_raw(wp_unslash($_POST['url'])) : '';
        $title = isset($_POST['title']) ? sanitize_text_field(wp_unslash($_POST['title'])) : '';
        
        if (empty($url)) {
            wp_send_json_error('URL is required');
        }
        
        // Scrape the URL
        $keywords = $this->scrape_and_analyze($url);
        
        if (empty($keywords)) {
            wp_send_json_error('Failed to scrape URL or no keywords found');
        }
        
        // Save to database with random seed for consistent layout
        global $wpdb;
        $table_name = $wpdb->prefix . 'keylouds';
        
        $layout_seed = wp_rand(1000, 9999999);
        
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
        $wpdb->insert(
            $table_name,
            array(
                'title' => $title,
                'url' => $url,
                'keywords' => json_encode($keywords),
                'layout_seed' => $layout_seed
            ),
            array('%s', '%s', '%s', '%d')
        );
        
        $cloud_id = $wpdb->insert_id;
        
        wp_send_json_success(array(
            'id' => $cloud_id,
            'title' => $title,
            'url' => $url,
            'keywords' => $keywords
        ));
    }
    
    public function ajax_delete_cloud() {
        check_ajax_referer('keylouds_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Unauthorized');
        }
        
        $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
        
        if (empty($id)) {
            wp_send_json_error('Invalid ID');
        }
        
        global $wpdb;
        $table_name = $wpdb->prefix . 'keylouds';
        
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
        $wpdb->delete($table_name, array('id' => $id), array('%d'));
        
        wp_send_json_success();
    }
    
    public function ajax_shuffle_layout() {
        check_ajax_referer('keylouds_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Unauthorized');
        }
        
        $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
        
        if (empty($id)) {
            wp_send_json_error('Invalid ID');
        }
        
        global $wpdb;
        $table_name = $wpdb->prefix . 'keylouds';
        
        // Generate new random seed
        $new_seed = wp_rand(1000, 9999999);
        
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
        $wpdb->update(
            $table_name,
            array('layout_seed' => $new_seed),
            array('id' => $id),
            array('%d'),
            array('%d')
        );
        
        wp_send_json_success(array(
            'seed' => $new_seed
        ));
    }
    
    private function scrape_and_analyze($url) {
        // Fetch the URL content
        $response = wp_remote_get($url, array(
            'timeout' => 30,
            'user-agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36'
        ));
        
        if (is_wp_error($response)) {
            return false;
        }
        
        $html = wp_remote_retrieve_body($response);
        
        if (empty($html)) {
            return false;
        }
        
        // Parse HTML and extract text content
        $text = $this->extract_text_from_html($html);
        
        // Analyze and get keyword frequencies
        $keywords = $this->analyze_keywords($text);
        
        return $keywords;
    }
    
    private function extract_text_from_html($html) {
        // Remove scripts, styles, and comments
        $html = preg_replace('/<script\b[^>]*>(.*?)<\/script>/is', '', $html);
        $html = preg_replace('/<style\b[^>]*>(.*?)<\/style>/is', '', $html);
        $html = preg_replace('/<!--.*?-->/s', '', $html);
        
        // Strip all HTML tags
        $text = wp_strip_all_tags($html);
        
        // Decode HTML entities
        $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        
        // Normalize whitespace
        $text = preg_replace('/\s+/', ' ', $text);
        
        return trim($text);
    }
    
    private function analyze_keywords($text) {
        // Convert to lowercase
        $text = strtolower($text);
        
        // Remove punctuation and special characters
        $text = preg_replace('/[^a-z0-9\s]/', ' ', $text);
        
        // Split into words
        $words = preg_split('/\s+/', $text, -1, PREG_SPLIT_NO_EMPTY);
        
        // Common stop words to exclude
        $stop_words = array(
            'a', 'about', 'above', 'after', 'again', 'against', 'all', 'am', 'an', 'and', 'any', 'are', 'as', 'at',
            'be', 'because', 'been', 'before', 'being', 'below', 'between', 'both', 'but', 'by',
            'can', 'cannot', 'could', 'did', 'do', 'does', 'doing', 'down', 'during',
            'each', 'few', 'for', 'from', 'further',
            'had', 'has', 'have', 'having', 'he', 'her', 'here', 'hers', 'herself', 'him', 'himself', 'his', 'how',
            'i', 'if', 'in', 'into', 'is', 'it', 'its', 'itself',
            'just', 'me', 'might', 'more', 'most', 'must', 'my', 'myself',
            'no', 'nor', 'not', 'now', 'of', 'off', 'on', 'once', 'only', 'or', 'other', 'our', 'ours', 'ourselves', 'out', 'over', 'own',
            'same', 'she', 'should', 'so', 'some', 'such',
            'than', 'that', 'the', 'their', 'theirs', 'them', 'themselves', 'then', 'there', 'these', 'they', 'this', 'those', 'through', 'to', 'too',
            'under', 'until', 'up', 'very',
            'was', 'we', 'were', 'what', 'when', 'where', 'which', 'while', 'who', 'whom', 'why', 'will', 'with', 'would',
            'you', 'your', 'yours', 'yourself', 'yourselves'
        );
        
        // Filter out stop words and short words
        $filtered_words = array();
        foreach ($words as $word) {
            if (strlen($word) > 2 && !in_array($word, $stop_words) && !is_numeric($word)) {
                $filtered_words[] = $word;
            }
        }
        
        // Count word frequencies
        $word_counts = array_count_values($filtered_words);
        
        // Sort by frequency (descending)
        arsort($word_counts);
        
        // Take top 50 keywords
        $top_keywords = array_slice($word_counts, 0, 50, true);
        
        // Normalize frequencies to a scale of 1-10 for font sizing
        if (!empty($top_keywords)) {
            $max_count = max($top_keywords);
            $min_count = min($top_keywords);
            $range = $max_count - $min_count;
            
            $normalized = array();
            foreach ($top_keywords as $word => $count) {
                if ($range > 0) {
                    $weight = round(1 + (($count - $min_count) / $range) * 9);
                } else {
                    $weight = 5;
                }
                // Ensure weight is between 1 and 10
                $weight = max(1, min(10, $weight));
                // Store both weight and original count
                $normalized[$word] = array('weight' => $weight, 'count' => $count);
            }
            
            // Sort by weight (descending) to arrange by frequency
            uasort($normalized, function($a, $b) {
                return $b['weight'] - $a['weight'];
            });
            
            // Rearrange so biggest words are in the middle
            $arranged = array();
            $temp_array = array();
            $index = 0;
            
            foreach ($normalized as $word => $data) {
                $temp_array[] = array('word' => $word, 'weight' => $data['weight'], 'count' => $data['count']);
            }
            
            // Alternate between adding to beginning and end to create a pattern
            // where largest words end up in the middle
            $result = array();
            $left = array();
            $right = array();
            
            for ($i = 0; $i < count($temp_array); $i++) {
                if ($i % 2 == 0) {
                    $right[] = $temp_array[$i];
                } else {
                    $left[] = $temp_array[$i];
                }
            }
            
            // Reverse left array so it builds outward from center
            $left = array_reverse($left);
            
            // Combine: left (reversed) + right creates center-heavy layout
            $result = array_merge($left, $right);
            
            // Convert back to associative array with both weight and count
            foreach ($result as $item) {
                $arranged[$item['word']] = array('weight' => $item['weight'], 'count' => $item['count']);
            }
            
            return $arranged;
        }
        
        return array();
    }
    
    public function shortcode_handler($atts) {
        $atts = shortcode_atts(array(
            'id' => 0,
            'color_small' => '',
            'color_medium' => '',
            'color_large' => ''
        ), $atts, 'keycloud');
        
        $cloud_id = intval($atts['id']);
        
        if (empty($cloud_id)) {
            return '<p>Invalid keyword cloud ID</p>';
        }
        
        global $wpdb;
        $table_name = $wpdb->prefix . 'keylouds';
        
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
        $cloud = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}keylouds WHERE id = %d",
            $cloud_id
        ));
        
        if (!$cloud) {
            return '<p>Keyword cloud not found</p>';
        }
        
        $keywords = json_decode($cloud->keywords, true);
        
        if (empty($keywords)) {
            return '<p>No keywords available</p>';
        }
        
        // Build custom colors array if provided
        $custom_colors = array();
        if (!empty($atts['color_small']) && !empty($atts['color_medium']) && !empty($atts['color_large'])) {
            $custom_colors = array(
                'small' => sanitize_hex_color($atts['color_small']),
                'medium' => sanitize_hex_color($atts['color_medium']),
                'large' => sanitize_hex_color($atts['color_large'])
            );
        }
        
        return $this->render_keyword_cloud($keywords, $custom_colors, $cloud_id);
    }
    
    private function render_keyword_cloud($keywords, $custom_colors = array(), $cloud_id = 0) {
        // Get color settings
        if (!empty($custom_colors) && isset($custom_colors['small']) && isset($custom_colors['medium']) && isset($custom_colors['large'])) {
            $color_small = $custom_colors['small'];
            $color_medium = $custom_colors['medium'];
            $color_large = $custom_colors['large'];
        } else {
            $color_small = get_option('keylouds_color_small', '#a3d0e0');
            $color_medium = get_option('keylouds_color_medium', '#3498db');
            $color_large = get_option('keylouds_color_large', '#2271b1');
        }
        
        // Get layout seed from database if cloud_id provided
        $layout_seed = 0;
        if ($cloud_id > 0) {
            global $wpdb;
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
            $cloud = $wpdb->get_row($wpdb->prepare(
                "SELECT layout_seed FROM {$wpdb->prefix}keylouds WHERE id = %d",
                $cloud_id
            ));
            if ($cloud && $cloud->layout_seed) {
                $layout_seed = $cloud->layout_seed;
            } else if ($cloud) {
                // Generate seed if one doesn't exist (backwards compatibility)
                $layout_seed = wp_rand(1000, 9999999);
                // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
                $wpdb->update(
                    $wpdb->prefix . 'keylouds',
                    array('layout_seed' => $layout_seed),
                    array('id' => $cloud_id),
                    array('%d'),
                    array('%d')
                );
            }
        }
        
        // Create container with data attributes for wordcloud2
        $output = sprintf(
            '<div class="keylouds-cloud-container" data-cloud="%s" data-color-small="%s" data-color-medium="%s" data-color-large="%s" data-seed="%d">',
            esc_attr(wp_json_encode($keywords)),
            esc_attr($color_small),
            esc_attr($color_medium),
            esc_attr($color_large),
            $layout_seed
        );
        
        // Add fallback content for non-JS users (SEO and accessibility)
        $output .= '<noscript><div class="keylouds-cloud-fallback">';
        foreach ($keywords as $word => $data) {
            // Handle both old format (weight only) and new format (array with weight and count)
            if (is_array($data)) {
                $weight = isset($data['weight']) ? intval($data['weight']) : 1;
                $count = isset($data['count']) ? intval($data['count']) : 0;
            } else {
                $weight = intval($data);
                $count = 0;
            }
            
            // Ensure weight is at least 1
            $weight = max(1, $weight);
            
            // Calculate font size: 0.8em (min) to 3.0em (max)
            $font_size = 0.8 + (($weight - 1) / 9) * 2.2;
            
            // Get color based on font size
            $color = $this->get_color_for_size($font_size, $custom_colors);
            
            $output .= sprintf(
                '<span class="keylouds-word" data-weight="%d" data-count="%d" style="font-size: %.2fem; color: %s;" title="Found %d times">%s</span> ',
                esc_attr($weight),
                esc_attr($count),
                $font_size,
                esc_attr($color),
                $count,
                esc_html($word)
            );
        }
        $output .= '</div></noscript>';
        
        $output .= '</div>';
        
        return $output;
    }
    
    public function register_block() {
        // Register wordcloud2 library for editor
        wp_register_script(
            'wordcloud2-editor',
            KEYLOUDS_PLUGIN_URL . 'assets/js/wordcloud2.js',
            array(),
            '1.2.2',
            true
        );
        
        // Register frontend script for editor preview
        wp_register_script(
            'keylouds-frontend-editor',
            KEYLOUDS_PLUGIN_URL . 'assets/js/frontend.js',
            array('wordcloud2-editor'),
            KEYLOUDS_VERSION,
            true
        );
        
        // Register block script
        wp_register_script(
            'keylouds-block-editor',
            KEYLOUDS_PLUGIN_URL . 'assets/js/block.js',
            array('wp-blocks', 'wp-element', 'wp-block-editor', 'wp-components', 'wp-server-side-render', 'wordcloud2-editor', 'keylouds-frontend-editor'),
            KEYLOUDS_VERSION,
            true
        );
        
        // Register frontend styles for editor preview
        wp_register_style(
            'keylouds-block-editor-style',
            KEYLOUDS_PLUGIN_URL . 'assets/css/frontend.css',
            array(),
            KEYLOUDS_VERSION
        );
        
        // Get all keyword clouds for the block selector
        global $wpdb;
        $table_name = $wpdb->prefix . 'keylouds';
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
        $clouds = $wpdb->get_results(
            "SELECT id, title FROM {$wpdb->prefix}keylouds ORDER BY created_at DESC"
        );
        
        wp_localize_script('keylouds-block-editor', 'keyloudsBlock', array(
            'clouds' => $clouds ? $clouds : array()
        ));
        
        // Register block
        register_block_type('keylouds/cloud', array(
            'editor_script' => 'keylouds-block-editor',
            'editor_style' => 'keylouds-block-editor-style',
            'style' => 'keylouds-block-editor-style',
            'attributes' => array(
                'cloudId' => array(
                    'type' => 'number',
                    'default' => 0
                ),
                'colorSmall' => array(
                    'type' => 'string',
                    'default' => ''
                ),
                'colorMedium' => array(
                    'type' => 'string',
                    'default' => ''
                ),
                'colorLarge' => array(
                    'type' => 'string',
                    'default' => ''
                )
            ),
            'render_callback' => array($this, 'render_block')
        ));
    }
    
    public function render_block($attributes) {
        if (empty($attributes['cloudId'])) {
            return '<p>Please select a keyword cloud</p>';
        }
        
        // Build shortcode attributes with custom colors if provided
        $shortcode_atts = array('id' => $attributes['cloudId']);
        
        if (!empty($attributes['colorSmall'])) {
            $shortcode_atts['color_small'] = $attributes['colorSmall'];
        }
        if (!empty($attributes['colorMedium'])) {
            $shortcode_atts['color_medium'] = $attributes['colorMedium'];
        }
        if (!empty($attributes['colorLarge'])) {
            $shortcode_atts['color_large'] = $attributes['colorLarge'];
        }
        
        return $this->shortcode_handler($shortcode_atts);
    }
}

// Initialize the plugin
Keylouds::get_instance();

