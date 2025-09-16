<?php
/**
 * Plugin Name: AzuraCast Song History
 * Plugin URI: https://github.com/Lokke/azuracast-song-history
 * Description: Display recent song history from your AzuraCast radio station with customizable widgets and shortcodes.
 * Version:           0.0.3
 * Author: Lokke
 * Author URI: https://github.com/Lokke
 * Text Domain: azuracast-song-history
 * Domain Path: /languages
 * Requires at least: 5.0
 * Tested up to: 6.8
 * Requires PHP: 7.4
 * License: GPL v3 or later
 * License URI: https://www.gnu.org/licenses/gpl-3.0.html
 * Network: false
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Plugin constants
define('AZURACAST_SONG_HISTORY_VERSION', '0.0.1');
define('AZURACAST_SONG_HISTORY_PLUGIN_FILE', __FILE__);
define('AZURACAST_SONG_HISTORY_PLUGIN_PATH', plugin_dir_path(__FILE__));
define('AZURACAST_SONG_HISTORY_PLUGIN_URL', plugin_dir_url(__FILE__));
define('AZURACAST_SONG_HISTORY_PLUGIN_BASENAME', plugin_basename(__FILE__));

/**
 * Main plugin class
 */
class AzuraCast_Song_History {
    
    /**
     * Plugin instance
     */
    private static $_instance = null;
    
    /**
     * Get plugin instance
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
        // Activation and deactivation hooks
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
        
        // Initialize plugin
        add_action('plugins_loaded', array($this, 'init'));
    }
    
    /**
     * Initialize plugin
     */
    public function init() {
        // Load text domain for translations
        load_plugin_textdomain('azuracast-song-history', false, dirname(plugin_basename(__FILE__)) . '/languages');
        
        // Include required files
        $this->includes();
        
        // Initialize components
        $this->init_admin();
        $this->init_widgets();
        $this->init_shortcodes();
        
        // Enqueue scripts and styles
        add_action('wp_enqueue_scripts', array($this, 'enqueue_public_assets'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));
        
        // AJAX handlers
        add_action('wp_ajax_azuracast_refresh_songs', array($this, 'ajax_refresh_songs'));
        add_action('wp_ajax_nopriv_azuracast_refresh_songs', array($this, 'ajax_refresh_songs'));
    }
    
    /**
     * Include required files
     */
    private function includes() {
        require_once AZURACAST_SONG_HISTORY_PLUGIN_PATH . 'includes/class-api.php';
        require_once AZURACAST_SONG_HISTORY_PLUGIN_PATH . 'includes/class-widget.php';
        require_once AZURACAST_SONG_HISTORY_PLUGIN_PATH . 'includes/class-shortcodes.php';
        require_once AZURACAST_SONG_HISTORY_PLUGIN_PATH . 'admin/class-admin.php';
    }
    
    /**
     * Initialize admin interface
     */
    private function init_admin() {
        if (is_admin()) {
            new AzuraCast_Song_History_Admin();
        }
    }
    
    /**
     * Initialize widgets
     */
    private function init_widgets() {
        add_action('widgets_init', function() {
            register_widget('AzuraCast_Song_History_Widget');
        });
    }
    
    /**
     * Initialize shortcodes
     */
    private function init_shortcodes() {
        new AzuraCast_Shortcodes();
    }
    
    /**
     * Enqueue public assets
     */
    public function enqueue_public_assets() {
        wp_enqueue_style(
            'azuracast-song-history',
            AZURACAST_SONG_HISTORY_PLUGIN_URL . 'assets/css/public.css',
            array(),
            AZURACAST_SONG_HISTORY_VERSION
        );
        
        wp_enqueue_script(
            'azuracast-song-history',
            AZURACAST_SONG_HISTORY_PLUGIN_URL . 'assets/js/public.js',
            array('jquery'),
            AZURACAST_SONG_HISTORY_VERSION,
            true
        );
        
        // Localize script for AJAX
        wp_localize_script('azuracast-song-history', 'azuracast_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('azuracast_nonce'),
            'auto_refresh' => get_option('azuracast_auto_refresh', 30)
        ));
    }
    
    /**
     * Enqueue admin assets
     */
    public function enqueue_admin_assets($hook) {
        if (strpos($hook, 'azuracast') !== false) {
            wp_enqueue_style(
                'azuracast-admin',
                AZURACAST_SONG_HISTORY_PLUGIN_URL . 'assets/css/admin.css',
                array(),
                AZURACAST_SONG_HISTORY_VERSION
            );
            
            wp_enqueue_script(
                'azuracast-admin',
                AZURACAST_SONG_HISTORY_PLUGIN_URL . 'assets/js/admin.js',
                array('jquery'),
                AZURACAST_SONG_HISTORY_VERSION,
                true
            );
        }
    }
    
    /**
     * AJAX handler for refreshing songs
     */
    public function ajax_refresh_songs() {
        check_ajax_referer('azuracast_nonce', 'nonce');
        
        $api = new AzuraCast_Song_History_API();
        $songs = $api->get_song_history();
        
        if (is_wp_error($songs)) {
            wp_send_json_error($songs->get_error_message());
        }
        
        wp_send_json_success($songs);
    }
    
    /**
     * Plugin activation
     */
    public function activate() {
        // Set default options
        $defaults = array(
            'azuracast_url' => '',
            'azuracast_station_id' => '',
            'azuracast_api_key' => '',
            'azuracast_show_covers' => true,
            'azuracast_song_count' => 5,
            'azuracast_layout_style' => 'compact',
            'azuracast_auto_refresh' => 30,
            'azuracast_theme' => 'light',
            'azuracast_cache_duration' => 300
        );
        
        foreach ($defaults as $option => $value) {
            if (get_option($option) === false) {
                add_option($option, $value);
            }
        }
        
        // Create database tables if needed
        $this->create_tables();
    }
    
    /**
     * Plugin deactivation
     */
    public function deactivate() {
        // Clear any cached data
        wp_cache_flush();
        
        // Remove scheduled events
        wp_clear_scheduled_hook('azuracast_cleanup_cache');
    }
    
    /**
     * Create database tables
     */
    private function create_tables() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'azuracast_song_cache';
        
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE $table_name (
            id int(11) NOT NULL AUTO_INCREMENT,
            station_id varchar(50) NOT NULL,
            song_data longtext NOT NULL,
            cached_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY station_id (station_id),
            KEY cached_at (cached_at)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
}

// Initialize the plugin
AzuraCast_Song_History::instance();