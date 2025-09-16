<?php
/**
 * AzuraCast API Integration Class
 * 
 * Handles communication with AzuraCast API to fetch song history
 * and provides caching functionality
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
     * Cache expiration time in seconds (default: 5 minutes)
     */
    const CACHE_EXPIRY = 300;
    
    /**
     * Default number of songs to fetch
     */
    const DEFAULT_SONG_COUNT = 10;
    
    /**
     * Plugin options
     */
    private $options;
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->options = get_option('azuracast_song_history_options', array());
    }
    
    /**
     * Fetch song history from AzuraCast API
     * 
     * @param int $count Number of songs to fetch
     * @return array|WP_Error Song history data or error
     */
    public function get_song_history($count = null) {
        // Get count from options or use default
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
        
        return array(
            'station' => $station_data['station'],
            'now_playing' => isset($station_data['now_playing']) ? $station_data['now_playing'] : null,
            'song_history' => $song_history,
            'timestamp' => current_time('timestamp'),
            'count' => count($song_history)
        );
    }
        }
        
        // Make API request
        $response = wp_remote_get($endpoint, array(
            'timeout' => 10,
            'headers' => array(
                'User-Agent' => 'WordPress AzuraCast Plugin/1.0.0'
            )
        ));
        
        if (is_wp_error($response)) {
            return $response;
        }
        
        $response_code = wp_remote_retrieve_response_code($response);
        
        if ($response_code !== 200) {
            return new WP_Error(
                'api_error', 
                sprintf(__('AzuraCast API returned error code: %d', 'azuracast-song-history'), $response_code)
            );
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
        
        return array(
            'station' => $station_data['station'],
            'now_playing' => isset($station_data['now_playing']) ? $station_data['now_playing'] : null,
            'song_history' => $song_history,
            'timestamp' => current_time('timestamp'),
            'count' => count($song_history)
        );
    }
    
    /**
     * Store song data in database for persistence
     * 
     * @param array $songs Song data to store
     */
    
    /**
     * Format individual song data
     * 
     * @param array $item Raw song item
     * @return array|null Formatted song data
     */
    private function format_song_data($item) {
        // Extract song information
        $song = isset($item['song']) ? $item['song'] : $item;
        
        if (!is_array($song)) {
            return null;
        }
        
        $formatted = array(
            'id' => isset($song['id']) ? $song['id'] : uniqid(),
            'title' => isset($song['title']) ? sanitize_text_field($song['title']) : '',
            'artist' => isset($song['artist']) ? sanitize_text_field($song['artist']) : '',
            'album' => isset($song['album']) ? sanitize_text_field($song['album']) : '',
            'lyrics' => isset($song['lyrics']) ? sanitize_textarea_field($song['lyrics']) : '',
            'art' => isset($song['art']) ? esc_url_raw($song['art']) : '',
            'played_at' => isset($item['played_at']) ? $item['played_at'] : time(),
            'duration' => isset($item['duration']) ? intval($item['duration']) : 0
        );
        
        // Ensure we have at least title or artist
        if (empty($formatted['title']) && empty($formatted['artist'])) {
            return null;
        }
        
        return $formatted;
    }
    
    /**
     * Store song history in database for persistence
     * 
     * @param array $songs Song history data
     */
    private function store_in_database($songs) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'azuracast_song_history';
        
        // Clear old entries (keep last 100)
        $wpdb->query("DELETE FROM $table_name WHERE id NOT IN (
            SELECT id FROM (
                SELECT id FROM $table_name ORDER BY played_at DESC LIMIT 100
            ) AS temp
        )");
        
        // Insert new songs
        foreach ($songs as $song) {
            $wpdb->replace(
                $table_name,
                array(
                    'song_id' => $song['id'],
                    'title' => $song['title'],
                    'artist' => $song['artist'],
                    'album' => $song['album'],
                    'lyrics' => $song['lyrics'],
                    'art_url' => $song['art'],
                    'played_at' => date('Y-m-d H:i:s', $song['played_at']),
                    'duration' => $song['duration'],
                    'cached_at' => current_time('mysql')
                ),
                array('%s', '%s', '%s', '%s', '%s', '%s', '%s', '%d', '%s')
            );
        }
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
        
        $results = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $table_name ORDER BY played_at DESC LIMIT %d",
            $count
        ), ARRAY_A);
        
        if (!$results) {
            return array();
        }
        
        // Format results
        $songs = array();
        foreach ($results as $row) {
            $songs[] = array(
                'id' => $row['song_id'],
                'title' => $row['title'],
                'artist' => $row['artist'],
                'album' => $row['album'],
                'lyrics' => $row['lyrics'],
                'art' => $row['art_url'],
                'played_at' => strtotime($row['played_at']),
                'duration' => intval($row['duration'])
            );
        }
        
        return $songs;
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
            return new WP_Error('missing_url', __('API URL is required', 'azuracast-song-history'));
        }
        
        $endpoint = rtrim($api_url, '/') . '/api/status';
        
        $response = wp_remote_get($endpoint, array(
            'timeout' => 5,
            'headers' => array(
                'User-Agent' => 'WordPress AzuraCast Plugin/1.0.0'
            )
        ));
        
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
     * @return string API URL
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
        $api_url = $this->get_api_url();
        
        if (empty($api_url)) {
            return new WP_Error('missing_config', __('AzuraCast API URL not configured', 'azuracast-song-history'));
        }
        
        $cache_key = 'azuracast_now_playing_' . md5($api_url);
        $cached_data = get_transient($cache_key);
        
        if ($cached_data !== false) {
            return $cached_data;
        }
        
        $endpoint = rtrim($api_url, '/') . '/api/nowplaying';
        
        $station_id = isset($this->options['station_id']) ? $this->options['station_id'] : '';
        if (!empty($station_id)) {
            $endpoint .= '/' . intval($station_id);
        }
        
        $response = wp_remote_get($endpoint, array(
            'timeout' => 10,
            'headers' => array(
                'User-Agent' => 'WordPress AzuraCast Plugin/1.0.0'
            )
        ));
        
        if (is_wp_error($response)) {
            return $response;
        }
        
        $response_code = wp_remote_retrieve_response_code($response);
        
        if ($response_code !== 200) {
            return new WP_Error(
                'api_error',
                sprintf(__('AzuraCast API returned error code: %d', 'azuracast-song-history'), $response_code)
            );
        }
        
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            return new WP_Error('invalid_json', __('Invalid JSON response from AzuraCast API', 'azuracast-song-history'));
        }
        
        $now_playing = isset($data['now_playing']) ? $data['now_playing'] : $data;
        $formatted = $this->format_song_data($now_playing);
        
        // Cache for 30 seconds
        set_transient($cache_key, $formatted, 30);
        
        return $formatted;
    }
}