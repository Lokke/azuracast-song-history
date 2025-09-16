<?php
/**
 * AzuraCast Song History API Handler
 * 
 * Handles communication with AzuraCast API and data caching
 * 
 * @package AzuraCast_Song_History
 * @version 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class AzuraCast_API {
    
    /**
     * Plugin options
     */
    private $options;
    
    /**
     * Cache expiry time in seconds (5 minutes)
     */
    const CACHE_EXPIRY = 300;
    
    /**
     * Request timeout in seconds
     */
    const TIMEOUT = 10;
    
    /**
     * Default number of songs to fetch
     */
    const DEFAULT_SONG_COUNT = 10;
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->options = get_option('azuracast_song_history_options', array());
    }
    
    /**
     * Get song history from AzuraCast
     * 
     * @param int $count Number of songs to fetch
     * @return array|WP_Error Song history array or error
     */
    public function get_song_history($count = null) {
        if ($count === null) {
            $count = isset($this->options['song_count']) ? 
                intval($this->options['song_count']) : self::DEFAULT_SONG_COUNT;
        }
        
        // Validate count
        $count = max(1, min(50, $count)); // Between 1 and 50
        
        // Check cache first
        $cache_key = 'azuracast_song_history_' . md5($this->get_api_url() . $count);
        $cached_data = get_transient($cache_key);
        
        if ($cached_data !== false) {
            return $cached_data;
        }
        
        // Fetch from API
        $response = $this->fetch_from_api($count);
        
        if (is_wp_error($response)) {
            return $response;
        }
        
        // Cache the response
        set_transient($cache_key, $response, self::CACHE_EXPIRY);
        
        // Store in database for persistence
        $this->store_in_database($response);
        
        return $response;
    }
    
    /**
     * Fetch data directly from AzuraCast API
     * 
     * @param int $count Number of songs to fetch
     * @return array|WP_Error API response or error
     */
    private function fetch_from_api($count) {
        $api_url = $this->get_api_url();
        
        if (empty($api_url)) {
            return new WP_Error('missing_config', __('AzuraCast server URL not configured', 'azuracast-song-history'));
        }
        
        // Build API endpoint from server URL
        $server_url = trim($api_url);
        $server_url = preg_replace('#^https?://#', '', $server_url);
        $endpoint = 'https://' . $server_url . '/api/nowplaying';
        
        // Get station shortcode from settings
        $station_shortcode = isset($this->options['station_shortcode']) ? $this->options['station_shortcode'] : '';
        
        // Make API request
        $response = wp_remote_get($endpoint, array(
            'timeout' => self::TIMEOUT,
            'user-agent' => 'AzuraCast Song History Plugin'
        ));
        
        if (is_wp_error($response)) {
            // Try HTTP if HTTPS fails
            $endpoint = 'http://' . $server_url . '/api/nowplaying';
            $response = wp_remote_get($endpoint, array(
                'timeout' => self::TIMEOUT,
                'user-agent' => 'AzuraCast Song History Plugin'
            ));
        }
        
        if (is_wp_error($response)) {
            return $response;
        }
        
        $response_code = wp_remote_retrieve_response_code($response);
        if ($response_code !== 200) {
            return new WP_Error('api_error', sprintf(__('API request failed with status %d', 'azuracast-song-history'), $response_code));
        }
        
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            return new WP_Error('json_error', __('Invalid JSON response from API', 'azuracast-song-history'));
        }
        
        if (!is_array($data)) {
            return new WP_Error('invalid_response', __('Invalid response format from API', 'azuracast-song-history'));
        }
        
        // Find the correct station data
        $station_data = null;
        
        if (!empty($station_shortcode)) {
            // Look for specific station by shortcode
            foreach ($data as $item) {
                if (isset($item['station']['shortcode']) && $item['station']['shortcode'] === $station_shortcode) {
                    $station_data = $item;
                    break;
                }
            }
        }
        
        // If no specific station found or no shortcode set, use first station
        if (!$station_data && !empty($data)) {
            $station_data = $data[0];
        }
        
        if (!$station_data || !isset($station_data['song_history'])) {
            return new WP_Error('no_data', __('No song history data found', 'azuracast-song-history'));
        }
        
        // Extract and format song history
        $song_history = array_slice($station_data['song_history'], 0, $count);
        
        // Normalize song data format
        $normalized_songs = array();
        foreach ($song_history as $entry) {
            $normalized_songs[] = $this->normalize_song_data($entry);
        }
        
        return array(
            'station' => $station_data['station'],
            'now_playing' => isset($station_data['now_playing']) ? $this->normalize_song_data($station_data['now_playing']) : null,
            'song_history' => $normalized_songs,
            'timestamp' => current_time('timestamp'),
            'count' => count($normalized_songs)
        );
    }
    
    /**
     * Store song data in database for persistence
     * 
     * @param array $data Song data to store
     */
    private function store_in_database($data) {
        if (!is_array($data) || !isset($data['song_history'])) {
            return;
        }
        
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'azuracast_song_history';
        
        // Create table if it doesn't exist
        $charset_collate = $wpdb->get_charset_collate();
        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            song_data longtext NOT NULL,
            timestamp datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
        
        // Store current data
        $song_data = json_encode($data);
        $wpdb->replace(
            $table_name,
            array(
                'id' => 1, // Always use ID 1 to replace previous data
                'song_data' => $song_data,
                'timestamp' => current_time('mysql')
            ),
            array('%d', '%s', '%s')
        );
    }
    
    /**
     * Get cached song history from database
     * 
     * @param int $count Number of songs to retrieve
     * @return array Song history from database
     */
    public function get_cached_history($count = null) {
        global $wpdb;
        
        if ($count === null) {
            $count = isset($this->options['song_count']) ? 
                intval($this->options['song_count']) : self::DEFAULT_SONG_COUNT;
        }
        
        $table_name = $wpdb->prefix . 'azuracast_song_history';
        
        $result = $wpdb->get_var("SELECT song_data FROM $table_name WHERE id = 1");
        
        if (!$result) {
            return array();
        }
        
        $data = json_decode($result, true);
        
        if (!is_array($data) || !isset($data['song_history'])) {
            return array();
        }
        
        // Return requested number of songs - normalize cached data too
        $songs = array_slice($data['song_history'], 0, $count);
        
        // Normalize cached songs if they're not already normalized
        $normalized_songs = array();
        foreach ($songs as $song) {
            // Check if song is already normalized (has title/artist at top level)
            if (isset($song['title']) && isset($song['artist'])) {
                $normalized_songs[] = $song;
            } else {
                $normalized_songs[] = $this->normalize_song_data($song);
            }
        }
        
        return array(
            'station' => isset($data['station']) ? $data['station'] : null,
            'now_playing' => isset($data['now_playing']) ? $data['now_playing'] : null,
            'song_history' => $normalized_songs,
            'timestamp' => isset($data['timestamp']) ? $data['timestamp'] : 0,
            'count' => count($normalized_songs)
        );
    }
    
    /**
     * Normalize song data format from API response
     * 
     * @param array $song_entry Raw song entry from API
     * @return array Normalized song data
     */
    private function normalize_song_data($song_entry) {
        if (!is_array($song_entry)) {
            return array(
                'title' => __('Unknown Title', 'azuracast-song-history'),
                'artist' => __('Unknown Artist', 'azuracast-song-history'),
                'album' => '',
                'played_at' => '',
                'art' => ''
            );
        }
        
        // Extract song data from nested structure
        $song_data = isset($song_entry['song']) ? $song_entry['song'] : $song_entry;
        
        return array(
            'title' => !empty($song_data['title']) ? $song_data['title'] : __('Unknown Title', 'azuracast-song-history'),
            'artist' => !empty($song_data['artist']) ? $song_data['artist'] : __('Unknown Artist', 'azuracast-song-history'),
            'album' => !empty($song_data['album']) ? $song_data['album'] : '',
            'played_at' => isset($song_entry['played_at']) ? $song_entry['played_at'] : '',
            'art' => !empty($song_data['art']) ? $song_data['art'] : '',
            'genre' => !empty($song_data['genre']) ? $song_data['genre'] : '',
            'duration' => isset($song_entry['duration']) ? $song_entry['duration'] : 0,
            'playlist' => isset($song_entry['playlist']) ? $song_entry['playlist'] : ''
        );
    }
    
    /**
     * Clear all cached data
     */
    public function clear_cache() {
        global $wpdb;
        
        // Clear transients
        $wpdb->query("DELETE FROM $wpdb->options WHERE option_name LIKE '_transient_azuracast_song_history_%'");
        $wpdb->query("DELETE FROM $wpdb->options WHERE option_name LIKE '_transient_timeout_azuracast_song_history_%'");
        
        // Clear database cache
        $table_name = $wpdb->prefix . 'azuracast_song_history';
        $wpdb->query("TRUNCATE TABLE $table_name");
    }
    
    /**
     * Test API connection
     * 
     * @return bool|WP_Error True if connection successful, error otherwise
     */
    public function test_connection() {
        $api_url = $this->get_api_url();
        
        if (empty($api_url)) {
            return new WP_Error('missing_url', __('Server URL is required', 'azuracast-song-history'));
        }
        
        // Build endpoint
        $server_url = trim($api_url);
        $server_url = preg_replace('#^https?://#', '', $server_url);
        $endpoint = 'https://' . $server_url . '/api/nowplaying';
        
        $response = wp_remote_get($endpoint, array(
            'timeout' => 5,
            'user-agent' => 'AzuraCast Song History Plugin'
        ));
        
        if (is_wp_error($response)) {
            // Try HTTP if HTTPS fails
            $endpoint = 'http://' . $server_url . '/api/nowplaying';
            $response = wp_remote_get($endpoint, array(
                'timeout' => 5,
                'user-agent' => 'AzuraCast Song History Plugin'
            ));
        }
        
        if (is_wp_error($response)) {
            return $response;
        }
        
        $response_code = wp_remote_retrieve_response_code($response);
        
        if ($response_code !== 200) {
            return new WP_Error(
                'connection_failed',
                sprintf(__('Connection failed with status code: %d', 'azuracast-song-history'), $response_code)
            );
        }
        
        return true;
    }
    
    /**
     * Get API URL from options
     * 
     * @return string Server URL
     */
    private function get_api_url() {
        return isset($this->options['api_url']) ? 
            trim($this->options['api_url']) : '';
    }
    
    /**
     * Get current playing song
     * 
     * @return array|WP_Error Current song data or error
     */
    public function get_now_playing() {
        $history = $this->get_song_history(1);
        
        if (is_wp_error($history)) {
            return $history;
        }
        
        return isset($history['now_playing']) ? $history['now_playing'] : null;
    }
}