<?php
/**
 * AzuraCast Song History Admin Interface
 * 
 * Handles the WordPress admin interface for plugin configuration
 * 
 * @package AzuraCast_Song_History
 * @version 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class AzuraCast_Admin {
    
    /**
     * Plugin options
     */
    private $options;
    
    /**
     * Constructor
     */
    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'admin_init'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        add_action('wp_ajax_azuracast_test_connection', array($this, 'ajax_test_connection'));
        add_action('wp_ajax_azuracast_clear_cache', array($this, 'ajax_clear_cache'));
    }
    
    /**
     * Add admin menu
     */
    public function add_admin_menu() {
        add_options_page(
            __('AzuraCast Song History', 'azuracast-song-history'),
            __('AzuraCast', 'azuracast-song-history'),
            'manage_options',
            'azuracast-song-history',
            array($this, 'admin_page')
        );
    }
    
    /**
     * Initialize admin settings
     */
    public function admin_init() {
        register_setting(
            'azuracast_song_history_group',
            'azuracast_song_history_options',
            array($this, 'sanitize_options')
        );
        
        // Connection Settings Section
        add_settings_section(
            'azuracast_connection_section',
            __('AzuraCast Connection Settings', 'azuracast-song-history'),
            array($this, 'connection_section_callback'),
            'azuracast-song-history'
        );
        
        add_settings_field(
            'api_url',
            __('AzuraCast Server URL', 'azuracast-song-history'),
            array($this, 'api_url_callback'),
            'azuracast-song-history',
            'azuracast_connection_section'
        );
        
        add_settings_field(
            'station_selection',
            __('Radio Station', 'azuracast-song-history'),
            array($this, 'station_selection_callback'),
            'azuracast-song-history',
            'azuracast_connection_section'
        );
        
        // Display Settings Section
        add_settings_section(
            'azuracast_display_section',
            __('Default Display Settings', 'azuracast-song-history'),
            array($this, 'display_section_callback'),
            'azuracast-song-history'
        );
        
        add_settings_field(
            'song_count',
            __('Default Song Count', 'azuracast-song-history'),
            array($this, 'song_count_callback'),
            'azuracast-song-history',
            'azuracast_display_section'
        );
        
        add_settings_field(
            'default_layout',
            __('Default Layout', 'azuracast-song-history'),
            array($this, 'default_layout_callback'),
            'azuracast-song-history',
            'azuracast_display_section'
        );
        
        add_settings_field(
            'show_covers',
            __('Show Cover Images', 'azuracast-song-history'),
            array($this, 'show_covers_callback'),
            'azuracast-song-history',
            'azuracast_display_section'
        );
        
        add_settings_field(
            'show_timestamps',
            __('Show Timestamps', 'azuracast-song-history'),
            array($this, 'show_timestamps_callback'),
            'azuracast-song-history',
            'azuracast_display_section'
        );
        
        // Cache Settings Section
        add_settings_section(
            'azuracast_cache_section',
            __('Cache Settings', 'azuracast-song-history'),
            array($this, 'cache_section_callback'),
            'azuracast-song-history'
        );
        
        add_settings_field(
            'cache_duration',
            __('Cache Duration (minutes)', 'azuracast-song-history'),
            array($this, 'cache_duration_callback'),
            'azuracast-song-history',
            'azuracast_cache_section'
        );
        
        add_settings_field(
            'enable_database_cache',
            __('Enable Database Cache', 'azuracast-song-history'),
            array($this, 'enable_database_cache_callback'),
            'azuracast-song-history',
            'azuracast_cache_section'
        );
        
        $this->options = get_option('azuracast_song_history_options');
    }
    
    /**
     * Enqueue admin scripts and styles
     */
    public function enqueue_admin_scripts($hook) {
        if ($hook !== 'settings_page_azuracast-song-history') {
            return;
        }
        
        wp_enqueue_style(
            'azuracast-admin-style',
            plugin_dir_url(dirname(__FILE__)) . 'assets/css/admin.css',
            array(),
            '1.0.0'
        );
        
        wp_enqueue_script(
            'azuracast-admin-script',
            plugin_dir_url(dirname(__FILE__)) . 'assets/js/admin.js',
            array('jquery'),
            '1.0.0',
            true
        );
        
        wp_localize_script('azuracast-admin-script', 'azuracast_admin', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('azuracast_admin_nonce'),
            'strings' => array(
                'testing' => __('Testing connection...', 'azuracast-song-history'),
                'success' => __('Connection successful!', 'azuracast-song-history'),
                'error' => __('Connection failed', 'azuracast-song-history'),
                'clearing' => __('Clearing cache...', 'azuracast-song-history'),
                'cleared' => __('Cache cleared successfully!', 'azuracast-song-history'),
                'clear_error' => __('Failed to clear cache', 'azuracast-song-history')
            )
        ));
    }
    
    /**
     * Sanitize options
     */
    public function sanitize_options($input) {
        $sanitized = array();
        
        if (isset($input['api_url'])) {
            $sanitized['api_url'] = esc_url_raw(trim($input['api_url']));
        }
        
        if (isset($input['station_id'])) {
            $sanitized['station_id'] = sanitize_text_field($input['station_id']);
        }
        
        if (isset($input['stream_url'])) {
            $sanitized['stream_url'] = esc_url_raw(trim($input['stream_url']));
        }
        
        if (isset($input['song_count'])) {
            $sanitized['song_count'] = max(1, min(50, intval($input['song_count'])));
        }
        
        if (isset($input['default_layout'])) {
            $valid_layouts = array('list', 'grid', 'compact', 'table');
            $sanitized['default_layout'] = in_array($input['default_layout'], $valid_layouts) ? 
                $input['default_layout'] : 'list';
        }
        
        $sanitized['show_covers'] = isset($input['show_covers']);
        $sanitized['show_timestamps'] = isset($input['show_timestamps']);
        $sanitized['enable_database_cache'] = isset($input['enable_database_cache']);
        
        if (isset($input['cache_duration'])) {
            $sanitized['cache_duration'] = max(1, min(60, intval($input['cache_duration'])));
        }
        
        return $sanitized;
    }
    
    /**
     * Admin page content
     */
    public function admin_page() {
        ?>
        <div class="wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
            
            <div class="azuracast-admin-header">
                <p><?php esc_html_e('Configure your AzuraCast integration settings below.', 'azuracast-song-history'); ?></p>
            </div>
            
            <?php settings_errors(); ?>
            
            <div class="azuracast-admin-tabs">
                <nav class="nav-tab-wrapper">
                    <a href="#connection" class="nav-tab nav-tab-active" data-tab="connection">
                        <?php esc_html_e('Connection', 'azuracast-song-history'); ?>
                    </a>
                    <a href="#display" class="nav-tab" data-tab="display">
                        <?php esc_html_e('Display', 'azuracast-song-history'); ?>
                    </a>
                    <a href="#cache" class="nav-tab" data-tab="cache">
                        <?php esc_html_e('Cache', 'azuracast-song-history'); ?>
                    </a>
                    <a href="#usage" class="nav-tab" data-tab="usage">
                        <?php esc_html_e('Usage', 'azuracast-song-history'); ?>
                    </a>
                    <a href="#tools" class="nav-tab" data-tab="tools">
                        <?php esc_html_e('Tools', 'azuracast-song-history'); ?>
                    </a>
                </nav>
                
                <form method="post" action="options.php">
                    <?php settings_fields('azuracast_song_history_group'); ?>
                    
                    <div id="tab-connection" class="tab-content active">
                        <?php do_settings_sections('azuracast-song-history'); ?>
                        
                        <div class="azuracast-test-connection">
                            <button type="button" class="button" id="test-connection">
                                <?php esc_html_e('Test Connection', 'azuracast-song-history'); ?>
                            </button>
                            <span class="test-result"></span>
                        </div>
                    </div>
                    
                    <div id="tab-display" class="tab-content">
                        <!-- Display settings will be rendered by do_settings_sections above -->
                    </div>
                    
                    <div id="tab-cache" class="tab-content">
                        <!-- Cache settings will be rendered by do_settings_sections above -->
                        
                        <div class="azuracast-cache-tools">
                            <h3><?php esc_html_e('Cache Management', 'azuracast-song-history'); ?></h3>
                            <p><?php esc_html_e('Clear all cached song data to force fresh retrieval from AzuraCast.', 'azuracast-song-history'); ?></p>
                            <button type="button" class="button" id="clear-cache">
                                <?php esc_html_e('Clear Cache', 'azuracast-song-history'); ?>
                            </button>
                            <span class="clear-result"></span>
                        </div>
                    </div>
                    
                    <div id="tab-usage" class="tab-content">
                        <?php $this->render_usage_tab(); ?>
                    </div>
                    
                    <div id="tab-tools" class="tab-content">
                        <?php $this->render_tools_tab(); ?>
                    </div>
                    
                    <?php submit_button(); ?>
                </form>
            </div>
        </div>
        <?php
    }
    
    /**
     * Connection section callback
     */
    public function connection_section_callback() {
        echo '<p>' . esc_html__('Enter your AzuraCast server details to connect and fetch song history.', 'azuracast-song-history') . '</p>';
    }
    
    /**
     * Display section callback
     */
    public function display_section_callback() {
        echo '<p>' . esc_html__('Configure default display settings for widgets and shortcodes.', 'azuracast-song-history') . '</p>';
    }
    
    /**
     * Cache section callback
     */
    public function cache_section_callback() {
        echo '<p>' . esc_html__('Configure caching to improve performance and reduce API calls.', 'azuracast-song-history') . '</p>';
    }
    
    /**
     * API URL field callback
     */
    public function api_url_callback() {
        $value = isset($this->options['api_url']) ? $this->options['api_url'] : '';
        echo '<input type="text" id="api_url" name="azuracast_song_history_options[api_url]" 
                     value="' . esc_attr($value) . '" class="regular-text" 
                     placeholder="https://funkturm.radio-endstation.de">';
        echo '<p class="description">' . 
             esc_html__('Enter your AzuraCast server URL (with or without https://). Example: https://funkturm.radio-endstation.de', 'azuracast-song-history') . 
             '</p>';
    }
    
    /**
     * Station selection field callback
     */
    public function station_selection_callback() {
        $api_url = isset($this->options['api_url']) ? $this->options['api_url'] : '';
        $selected_station = isset($this->options['station_shortcode']) ? $this->options['station_shortcode'] : '';
        
        echo '<div id="station-selection-container">';
        
        if (empty($api_url)) {
            echo '<p class="description" style="color: #d63638;">' . 
                 esc_html__('Please enter your AzuraCast server URL first, then save settings to load available stations.', 'azuracast-song-history') . 
                 '</p>';
        } else {
            // Try to fetch stations
            $stations = $this->fetch_available_stations($api_url);
            
            if (is_array($stations) && !empty($stations)) {
                echo '<select id="station_shortcode" name="azuracast_song_history_options[station_shortcode]">';
                echo '<option value="">' . esc_html__('Select a station...', 'azuracast-song-history') . '</option>';
                
                foreach ($stations as $station) {
                    $shortcode = isset($station['shortcode']) ? $station['shortcode'] : '';
                    $name = isset($station['name']) ? $station['name'] : '';
                    $description = isset($station['description']) ? $station['description'] : '';
                    
                    if (!empty($shortcode)) {
                        echo '<option value="' . esc_attr($shortcode) . '" ' . selected($selected_station, $shortcode, false) . '>';
                        echo esc_html($name);
                        if (!empty($description)) {
                            echo ' - ' . esc_html($description);
                        }
                        echo '</option>';
                    }
                }
                echo '</select>';
                echo '<p class="description">' . 
                     esc_html__('Select which radio station to display song history from.', 'azuracast-song-history') . 
                     '</p>';
            } else {
                echo '<p class="description" style="color: #d63638;">' . 
                     esc_html__('Could not load stations. Please check your server URL and make sure it is reachable.', 'azuracast-song-history') . 
                     '</p>';
                echo '<input type="text" id="station_shortcode" name="azuracast_song_history_options[station_shortcode]" 
                             value="' . esc_attr($selected_station) . '" class="regular-text" 
                             placeholder="radio-endstation">';
                echo '<p class="description">' . 
                     esc_html__('Enter the station shortcode manually if automatic detection failed.', 'azuracast-song-history') . 
                     '</p>';
            }
        }
        
        echo '</div>';
    }
    
    /**
     * Fetch available stations from AzuraCast API
     */
    private function fetch_available_stations($server_url) {
        // Clean and prepare URL
        $server_url = trim($server_url);
        $server_url = preg_replace('#^https?://#', '', $server_url);
        $api_url = 'https://' . $server_url . '/api/nowplaying';
        
        // Use WordPress HTTP API
        $response = wp_remote_get($api_url, array(
            'timeout' => 10,
            'user-agent' => 'AzuraCast Song History Plugin'
        ));
        
        if (is_wp_error($response)) {
            // Try HTTP if HTTPS fails
            $api_url = 'http://' . $server_url . '/api/nowplaying';
            $response = wp_remote_get($api_url, array(
                'timeout' => 10,
                'user-agent' => 'AzuraCast Song History Plugin'
            ));
        }
        
        if (is_wp_error($response)) {
            return false;
        }
        
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        
        if (!is_array($data)) {
            return false;
        }
        
        $stations = array();
        foreach ($data as $station_data) {
            if (isset($station_data['station'])) {
                $stations[] = $station_data['station'];
            }
        }
        
        return $stations;
    }
    
    /**
     * Stream URL field callback
     */
    public function stream_url_callback() {
        $value = isset($this->options['stream_url']) ? $this->options['stream_url'] : '';
        echo '<input type="url" id="stream_url" name="azuracast_song_history_options[stream_url]" 
                     value="' . esc_attr($value) . '" class="regular-text" 
                     placeholder="https://your-stream-url.com/stream">';
        echo '<p class="description">' . 
             esc_html__('Direct stream URL for the audio player shortcode (optional).', 'azuracast-song-history') . 
             '</p>';
    }
    
    /**
     * Song count field callback
     */
    public function song_count_callback() {
        $value = isset($this->options['song_count']) ? $this->options['song_count'] : 10;
        echo '<input type="number" id="song_count" name="azuracast_song_history_options[song_count]" 
                     value="' . esc_attr($value) . '" class="small-text" 
                     min="1" max="50">';
        echo '<p class="description">' . 
             esc_html__('Default number of songs to display (1-50).', 'azuracast-song-history') . 
             '</p>';
    }
    
    /**
     * Default layout field callback
     */
    public function default_layout_callback() {
        $value = isset($this->options['default_layout']) ? $this->options['default_layout'] : 'list';
        $layouts = array(
            'list' => __('List', 'azuracast-song-history'),
            'grid' => __('Grid', 'azuracast-song-history'),
            'compact' => __('Compact', 'azuracast-song-history'),
            'table' => __('Table', 'azuracast-song-history')
        );
        
        echo '<select id="default_layout" name="azuracast_song_history_options[default_layout]">';
        foreach ($layouts as $key => $label) {
            echo '<option value="' . esc_attr($key) . '" ' . selected($value, $key, false) . '>' . 
                 esc_html($label) . '</option>';
        }
        echo '</select>';
        echo '<p class="description">' . 
             esc_html__('Default layout style for widgets and shortcodes.', 'azuracast-song-history') . 
             '</p>';
    }
    
    /**
     * Show covers field callback
     */
    public function show_covers_callback() {
        $value = isset($this->options['show_covers']) ? $this->options['show_covers'] : true;
        echo '<input type="checkbox" id="show_covers" name="azuracast_song_history_options[show_covers]" 
                     value="1" ' . checked($value, true, false) . '>';
        echo '<label for="show_covers">' . 
             esc_html__('Show album cover images by default', 'azuracast-song-history') . 
             '</label>';
    }
    
    /**
     * Show timestamps field callback
     */
    public function show_timestamps_callback() {
        $value = isset($this->options['show_timestamps']) ? $this->options['show_timestamps'] : true;
        echo '<input type="checkbox" id="show_timestamps" name="azuracast_song_history_options[show_timestamps]" 
                     value="1" ' . checked($value, true, false) . '>';
        echo '<label for="show_timestamps">' . 
             esc_html__('Show when songs were played by default', 'azuracast-song-history') . 
             '</label>';
    }
    
    /**
     * Cache duration field callback
     */
    public function cache_duration_callback() {
        $value = isset($this->options['cache_duration']) ? $this->options['cache_duration'] : 5;
        echo '<input type="number" id="cache_duration" name="azuracast_song_history_options[cache_duration]" 
                     value="' . esc_attr($value) . '" class="small-text" 
                     min="1" max="60">';
        echo '<p class="description">' . 
             esc_html__('How long to cache API responses in minutes (1-60).', 'azuracast-song-history') . 
             '</p>';
    }
    
    /**
     * Enable database cache field callback
     */
    public function enable_database_cache_callback() {
        $value = isset($this->options['enable_database_cache']) ? $this->options['enable_database_cache'] : true;
        echo '<input type="checkbox" id="enable_database_cache" name="azuracast_song_history_options[enable_database_cache]" 
                     value="1" ' . checked($value, true, false) . '>';
        echo '<label for="enable_database_cache">' . 
             esc_html__('Store songs in database for persistent caching', 'azuracast-song-history') . 
             '</label>';
        echo '<p class="description">' . 
             esc_html__('Keeps a backup of song data in case the API is temporarily unavailable.', 'azuracast-song-history') . 
             '</p>';
    }
    
    /**
     * Render usage tab
     */
    private function render_usage_tab() {
        ?>
        <div class="azuracast-usage-guide">
            <h3><?php esc_html_e('Widget Usage', 'azuracast-song-history'); ?></h3>
            <p><?php esc_html_e('Add the AzuraCast Song History widget to your sidebar:', 'azuracast-song-history'); ?></p>
            <ol>
                <li><?php esc_html_e('Go to Appearance → Widgets', 'azuracast-song-history'); ?></li>
                <li><?php esc_html_e('Find "AzuraCast Song History" widget', 'azuracast-song-history'); ?></li>
                <li><?php esc_html_e('Drag it to your desired sidebar', 'azuracast-song-history'); ?></li>
                <li><?php esc_html_e('Configure the widget settings', 'azuracast-song-history'); ?></li>
            </ol>
            
            <h3><?php esc_html_e('Shortcode Usage', 'azuracast-song-history'); ?></h3>
            <p><?php esc_html_e('Use these shortcodes in your posts, pages, or custom templates:', 'azuracast-song-history'); ?></p>
            
            <h4><?php esc_html_e('Song History', 'azuracast-song-history'); ?></h4>
            <div class="code-example">
                <code>[azuracast_history]</code>
                <p><?php esc_html_e('Basic song history with default settings', 'azuracast-song-history'); ?></p>
            </div>
            
            <div class="code-example">
                <code>[azuracast_history count="15" layout="grid" covers="true" time="true"]</code>
                <p><?php esc_html_e('Custom song history with 15 songs in grid layout', 'azuracast-song-history'); ?></p>
            </div>
            
            <h4><?php esc_html_e('Now Playing', 'azuracast-song-history'); ?></h4>
            <div class="code-example">
                <code>[azuracast_nowplaying]</code>
                <p><?php esc_html_e('Current playing song with default settings', 'azuracast-song-history'); ?></p>
            </div>
            
            <div class="code-example">
                <code>[azuracast_nowplaying layout="inline" covers="false"]</code>
                <p><?php esc_html_e('Now playing in inline layout without cover image', 'azuracast-song-history'); ?></p>
            </div>
            
            <h4><?php esc_html_e('Audio Player', 'azuracast-song-history'); ?></h4>
            <div class="code-example">
                <code>[azuracast_player]</code>
                <p><?php esc_html_e('Audio player (requires stream URL in settings)', 'azuracast-song-history'); ?></p>
            </div>
            
            <h3><?php esc_html_e('Shortcode Parameters', 'azuracast-song-history'); ?></h3>
            <table class="widefat">
                <thead>
                    <tr>
                        <th><?php esc_html_e('Parameter', 'azuracast-song-history'); ?></th>
                        <th><?php esc_html_e('Default', 'azuracast-song-history'); ?></th>
                        <th><?php esc_html_e('Options', 'azuracast-song-history'); ?></th>
                        <th><?php esc_html_e('Description', 'azuracast-song-history'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><code>count</code></td>
                        <td>10</td>
                        <td>1-50</td>
                        <td><?php esc_html_e('Number of songs to display', 'azuracast-song-history'); ?></td>
                    </tr>
                    <tr>
                        <td><code>layout</code></td>
                        <td>list</td>
                        <td>list, grid, compact, table</td>
                        <td><?php esc_html_e('Display layout style', 'azuracast-song-history'); ?></td>
                    </tr>
                    <tr>
                        <td><code>covers</code></td>
                        <td>true</td>
                        <td>true, false</td>
                        <td><?php esc_html_e('Show album cover images', 'azuracast-song-history'); ?></td>
                    </tr>
                    <tr>
                        <td><code>time</code></td>
                        <td>true</td>
                        <td>true, false</td>
                        <td><?php esc_html_e('Show when songs were played', 'azuracast-song-history'); ?></td>
                    </tr>
                    <tr>
                        <td><code>refresh</code></td>
                        <td>false</td>
                        <td>true, false</td>
                        <td><?php esc_html_e('Enable auto-refresh', 'azuracast-song-history'); ?></td>
                    </tr>
                    <tr>
                        <td><code>refresh_interval</code></td>
                        <td>30</td>
                        <td>10-300</td>
                        <td><?php esc_html_e('Auto-refresh interval in seconds', 'azuracast-song-history'); ?></td>
                    </tr>
                </tbody>
            </table>
        </div>
        <?php
    }
    
    /**
     * Render tools tab
     */
    private function render_tools_tab() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'azuracast_song_history';
        $song_count = $wpdb->get_var("SELECT COUNT(*) FROM $table_name");
        $last_update = $wpdb->get_var("SELECT MAX(cached_at) FROM $table_name");
        
        ?>
        <div class="azuracast-tools">
            <h3><?php esc_html_e('System Information', 'azuracast-song-history'); ?></h3>
            <table class="widefat">
                <tbody>
                    <tr>
                        <td><strong><?php esc_html_e('Plugin Version', 'azuracast-song-history'); ?></strong></td>
                        <td><?php echo esc_html(AZURACAST_SONG_HISTORY_VERSION); ?></td>
                    </tr>
                    <tr>
                        <td><strong><?php esc_html_e('Cached Songs', 'azuracast-song-history'); ?></strong></td>
                        <td><?php echo intval($song_count); ?></td>
                    </tr>
                    <tr>
                        <td><strong><?php esc_html_e('Last Cache Update', 'azuracast-song-history'); ?></strong></td>
                        <td><?php echo $last_update ? esc_html($last_update) : esc_html__('Never', 'azuracast-song-history'); ?></td>
                    </tr>
                    <tr>
                        <td><strong><?php esc_html_e('WordPress Version', 'azuracast-song-history'); ?></strong></td>
                        <td><?php echo esc_html(get_bloginfo('version')); ?></td>
                    </tr>
                    <tr>
                        <td><strong><?php esc_html_e('PHP Version', 'azuracast-song-history'); ?></strong></td>
                        <td><?php echo esc_html(PHP_VERSION); ?></td>
                    </tr>
                </tbody>
            </table>
            
            <h3><?php esc_html_e('Debug Information', 'azuracast-song-history'); ?></h3>
            <p><?php esc_html_e('If you\'re experiencing issues, include this information when asking for support:', 'azuracast-song-history'); ?></p>
            <textarea readonly class="large-text" rows="10"><?php
                echo "Plugin: AzuraCast Song History v" . AZURACAST_SONG_HISTORY_VERSION . "\n";
                echo "WordPress: " . get_bloginfo('version') . "\n";
                echo "PHP: " . PHP_VERSION . "\n";
                echo "Server: " . $_SERVER['SERVER_SOFTWARE'] . "\n";
                echo "Cached Songs: " . intval($song_count) . "\n";
                echo "Last Update: " . ($last_update ?: 'Never') . "\n";
                echo "Options: " . wp_json_encode(get_option('azuracast_song_history_options'), JSON_PRETTY_PRINT);
            ?></textarea>
        </div>
        <?php
    }
    
    /**
     * AJAX test connection
     */
    public function ajax_test_connection() {
        check_ajax_referer('azuracast_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(__('Insufficient permissions', 'azuracast-song-history'));
        }
        
        $api = new AzuraCast_API();
        $result = $api->test_connection();
        
        if (is_wp_error($result)) {
            wp_send_json_error($result->get_error_message());
        } else {
            wp_send_json_success(__('Connection successful!', 'azuracast-song-history'));
        }
    }
    
    /**
     * AJAX clear cache
     */
    public function ajax_clear_cache() {
        check_ajax_referer('azuracast_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(__('Insufficient permissions', 'azuracast-song-history'));
        }
        
        $api = new AzuraCast_API();
        $api->clear_cache();
        
        wp_send_json_success(__('Cache cleared successfully!', 'azuracast-song-history'));
    }
}