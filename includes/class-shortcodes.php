<?php
/**
 * AzuraCast Song History Shortcodes
 * 
 * Handles shortcode functionality for displaying song history
 * 
 * @package AzuraCast_Song_History
 * @version 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class AzuraCast_Shortcodes {
    
    /**
     * Initialize shortcodes
     */
    public function __construct() {
        add_shortcode('azuracast_history', array($this, 'song_history_shortcode'));
        add_shortcode('azuracast_nowplaying', array($this, 'now_playing_shortcode'));
        add_shortcode('azuracast_player', array($this, 'player_shortcode'));
        add_shortcode('azuracast_live_moderator', array($this, 'live_moderator_shortcode'));
    }
    
    /**
     * Song history shortcode
     * 
     * Usage: [azuracast_history count="10" layout="grid" covers="true" time="true"]
     * 
     * @param array $atts Shortcode attributes
     * @return string HTML output
     */
    public function song_history_shortcode($atts) {
        $atts = shortcode_atts(array(
            'count' => 10,
            'layout' => 'list', // list, grid, compact, table
            'covers' => 'true',
            'time' => 'true',
            'artist' => 'true',
            'album' => 'true',
            'refresh' => 'false',
            'refresh_interval' => 30,
            'title' => '',
            'class' => '',
            'style' => '',
            'limit_width' => 'false'
        ), $atts, 'azuracast_history');
        
        // Sanitize and validate attributes
        $count = max(1, min(50, intval($atts['count'])));
        $layout = in_array($atts['layout'], array('list', 'grid', 'compact', 'table')) ? 
            $atts['layout'] : 'list';
        $show_covers = filter_var($atts['covers'], FILTER_VALIDATE_BOOLEAN);
        $show_time = filter_var($atts['time'], FILTER_VALIDATE_BOOLEAN);
        $show_artist = filter_var($atts['artist'], FILTER_VALIDATE_BOOLEAN);
        $show_album = filter_var($atts['album'], FILTER_VALIDATE_BOOLEAN);
        $auto_refresh = filter_var($atts['refresh'], FILTER_VALIDATE_BOOLEAN);
        $refresh_interval = max(10, min(300, intval($atts['refresh_interval'])));
        $title = sanitize_text_field($atts['title']);
        $custom_class = sanitize_html_class($atts['class']);
        $custom_style = sanitize_text_field($atts['style']);
        $limit_width = filter_var($atts['limit_width'], FILTER_VALIDATE_BOOLEAN);
        
        // Get song history
        $api = new AzuraCast_API();
        $songs = $api->get_song_history($count);
        
        if (is_wp_error($songs)) {
            // Try cached data as fallback
            $songs = $api->get_cached_history($count);
            
            if (empty($songs)) {
                return '<div class="azuracast-error">' . 
                       esc_html__('Unable to load song history at this time.', 'azuracast-song-history') . 
                       '</div>';
            }
        }
        
        if (empty($songs) || empty($songs['song_history'])) {
            return '<div class="azuracast-no-songs">' . 
                   esc_html__('No songs available.', 'azuracast-song-history') . 
                   '</div>';
        }
        
        // Extract the actual song list
        $song_list = $songs['song_history'];
        
        // Generate unique container ID for AJAX refresh
        $container_id = 'azuracast-shortcode-' . uniqid();
        
        // Build CSS classes
        $css_classes = array(
            'azuracast-song-history-shortcode',
            'azuracast-layout-' . $layout
        );
        
        if ($custom_class) {
            $css_classes[] = $custom_class;
        }
        
        if ($limit_width) {
            $css_classes[] = 'azuracast-limit-width';
        }
        
        // Start output buffering
        ob_start();
        
        echo '<div class="' . esc_attr(implode(' ', $css_classes)) . '" id="' . esc_attr($container_id) . '"';
        if ($custom_style) {
            echo ' style="' . esc_attr($custom_style) . '"';
        }
        echo '>';
        
        // Title
        if ($title) {
            echo '<h3 class="azuracast-history-title">' . esc_html($title) . '</h3>';
        }
        
        // Auto-refresh indicator
        if ($auto_refresh) {
            echo '<div class="azuracast-refresh-indicator" style="display: none;">
                    <span class="spinner"></span> ' . 
                    esc_html__('Updating...', 'azuracast-song-history') . '
                  </div>';
        }
        
        // Songs container
        echo '<div class="azuracast-songs-container">';
        
        switch ($layout) {
            case 'table':
                $this->render_table_layout($song_list, $show_covers, $show_time, $show_artist, $show_album);
                break;
            case 'grid':
                $this->render_grid_layout($song_list, $show_covers, $show_time, $show_artist, $show_album);
                break;
            case 'compact':
                $this->render_compact_layout($song_list, $show_covers, $show_time, $show_artist, $show_album);
                break;
            default: // list
                $this->render_list_layout($song_list, $show_covers, $show_time, $show_artist, $show_album);
                break;
        }
        
        echo '</div>'; // songs-container
        
        // Last updated timestamp for auto-refresh
        if ($auto_refresh) {
            echo '<div class="azuracast-last-updated">
                    <small>' . 
                    sprintf(
                        esc_html__('Last updated: %s', 'azuracast-song-history'),
                        '<span class="timestamp">' . current_time('H:i:s') . '</span>'
                    ) . 
                    '</small>
                  </div>';
        }
        
        echo '</div>'; // main container
        
        // Add auto-refresh script
        if ($auto_refresh) {
            $this->add_shortcode_refresh_script($container_id, $refresh_interval, $atts);
        }
        
        return ob_get_clean();
    }
    
    /**
     * Now playing shortcode
     * 
     * Usage: [azuracast_nowplaying layout="card" covers="true"]
     * 
     * @param array $atts Shortcode attributes
     * @return string HTML output
     */
    public function now_playing_shortcode($atts) {
        $atts = shortcode_atts(array(
            'layout' => 'card', // card, inline, minimal
            'covers' => 'true',
            'title' => 'Now Playing',
            'show_title' => 'true',
            'class' => '',
            'style' => '',
            'refresh' => 'true',
            'refresh_interval' => 10
        ), $atts, 'azuracast_nowplaying');
        
        // Sanitize attributes
        $layout = in_array($atts['layout'], array('card', 'inline', 'minimal')) ? 
            $atts['layout'] : 'card';
        $show_covers = filter_var($atts['covers'], FILTER_VALIDATE_BOOLEAN);
        $title = sanitize_text_field($atts['title']);
        $show_title = filter_var($atts['show_title'], FILTER_VALIDATE_BOOLEAN);
        $custom_class = sanitize_html_class($atts['class']);
        $custom_style = sanitize_text_field($atts['style']);
        $auto_refresh = filter_var($atts['refresh'], FILTER_VALIDATE_BOOLEAN);
        $refresh_interval = max(5, min(60, intval($atts['refresh_interval'])));
        
        // Get current song
        $api = new AzuraCast_API();
        $song = $api->get_now_playing();
        
        if (is_wp_error($song) || empty($song)) {
            return '<div class="azuracast-nowplaying-error">' . 
                   esc_html__('Currently not playing', 'azuracast-song-history') . 
                   '</div>';
        }
        
        $container_id = 'azuracast-nowplaying-' . uniqid();
        
        $css_classes = array(
            'azuracast-nowplaying-shortcode',
            'azuracast-layout-' . $layout
        );
        
        if ($custom_class) {
            $css_classes[] = $custom_class;
        }
        
        ob_start();
        
        echo '<div class="' . esc_attr(implode(' ', $css_classes)) . '" id="' . esc_attr($container_id) . '"';
        if ($custom_style) {
            echo ' style="' . esc_attr($custom_style) . '"';
        }
        echo '>';
        
        if ($show_title && $title) {
            echo '<h3 class="azuracast-nowplaying-title">' . esc_html($title) . '</h3>';
        }
        
        if ($auto_refresh) {
            echo '<div class="azuracast-refresh-indicator" style="display: none;">
                    <span class="spinner"></span>
                  </div>';
        }
        
        echo '<div class="azuracast-nowplaying-container">';
        $this->render_now_playing_item($song, $show_covers, $layout);
        echo '</div>';
        
        echo '</div>';
        
        if ($auto_refresh) {
            $this->add_nowplaying_refresh_script($container_id, $refresh_interval, $atts);
        }
        
        return ob_get_clean();
    }
    
    /**
     * Audio player shortcode (if stream URL is configured)
     * 
     * Usage: [azuracast_player autoplay="false" volume="0.8"]
     * 
     * @param array $atts Shortcode attributes
     * @return string HTML output
     */
    public function player_shortcode($atts) {
        $atts = shortcode_atts(array(
            'autoplay' => 'false',
            'volume' => '0.8',
            'controls' => 'true',
            'class' => '',
            'style' => ''
        ), $atts, 'azuracast_player');
        
        $options = get_option('azuracast_song_history_options', array());
        $stream_url = isset($options['stream_url']) ? trim($options['stream_url']) : '';
        
        if (empty($stream_url)) {
            return '<div class="azuracast-player-error">' . 
                   esc_html__('Stream URL not configured', 'azuracast-song-history') . 
                   '</div>';
        }
        
        $autoplay = filter_var($atts['autoplay'], FILTER_VALIDATE_BOOLEAN);
        $volume = max(0, min(1, floatval($atts['volume'])));
        $show_controls = filter_var($atts['controls'], FILTER_VALIDATE_BOOLEAN);
        $custom_class = sanitize_html_class($atts['class']);
        $custom_style = sanitize_text_field($atts['style']);
        
        $css_classes = array('azuracast-audio-player');
        if ($custom_class) {
            $css_classes[] = $custom_class;
        }
        
        ob_start();
        
        echo '<div class="' . esc_attr(implode(' ', $css_classes)) . '"';
        if ($custom_style) {
            echo ' style="' . esc_attr($custom_style) . '"';
        }
        echo '>';
        
        echo '<audio';
        if ($show_controls) echo ' controls';
        if ($autoplay) echo ' autoplay';
        echo ' preload="none">';
        echo '<source src="' . esc_url($stream_url) . '" type="audio/mpeg">';
        echo esc_html__('Your browser does not support the audio element.', 'azuracast-song-history');
        echo '</audio>';
        
        if ($volume !== 0.8) {
            echo '<script>
                document.addEventListener("DOMContentLoaded", function() {
                    var audio = document.querySelector(".azuracast-audio-player audio");
                    if (audio) {
                        audio.volume = ' . esc_js($volume) . ';
                    }
                });
            </script>';
        }
        
        echo '</div>';
        
        return ob_get_clean();
    }
    
    /**
     * Render list layout
     */
    private function render_list_layout($songs, $show_covers, $show_time, $show_artist, $show_album) {
        echo '<ul class="azuracast-song-list">';
        
        foreach ($songs as $song) {
            echo '<li class="azuracast-song-item">';
            
            if ($show_covers) {
                echo '<div class="azuracast-song-cover">';
                $this->render_cover_image($song);
                echo '</div>';
            }
            
            echo '<div class="azuracast-song-info">';
            echo '<div class="azuracast-song-title">' . esc_html($song['title']) . '</div>';
            
            if ($show_artist && !empty($song['artist'])) {
                echo '<div class="azuracast-song-artist">' . esc_html($song['artist']) . '</div>';
            }
            
            if ($show_album && !empty($song['album'])) {
                echo '<div class="azuracast-song-album">' . esc_html($song['album']) . '</div>';
            }
            
            if ($show_time) {
                echo '<div class="azuracast-song-time">' . 
                     esc_html($this->format_time_ago($song['played_at'])) . 
                     '</div>';
            }
            
            echo '</div>'; // song-info
            echo '</li>';
        }
        
        echo '</ul>';
    }
    
    /**
     * Render grid layout
     */
    private function render_grid_layout($songs, $show_covers, $show_time, $show_artist, $show_album) {
        echo '<div class="azuracast-song-grid">';
        
        foreach ($songs as $song) {
            echo '<div class="azuracast-song-card">';
            
            if ($show_covers) {
                echo '<div class="azuracast-song-cover">';
                $this->render_cover_image($song);
                echo '</div>';
            }
            
            echo '<div class="azuracast-song-info">';
            echo '<div class="azuracast-song-title">' . esc_html($song['title']) . '</div>';
            
            if ($show_artist && !empty($song['artist'])) {
                echo '<div class="azuracast-song-artist">' . esc_html($song['artist']) . '</div>';
            }
            
            if ($show_album && !empty($song['album'])) {
                echo '<div class="azuracast-song-album">' . esc_html($song['album']) . '</div>';
            }
            
            if ($show_time) {
                echo '<div class="azuracast-song-time">' . 
                     esc_html($this->format_time_ago($song['played_at'])) . 
                     '</div>';
            }
            
            echo '</div>'; // song-info
            echo '</div>'; // song-card
        }
        
        echo '</div>'; // song-grid
    }
    
    /**
     * Render compact layout
     */
    private function render_compact_layout($songs, $show_covers, $show_time, $show_artist, $show_album) {
        echo '<div class="azuracast-song-compact">';
        
        foreach ($songs as $song) {
            echo '<div class="azuracast-song-item">';
            
            if ($show_covers) {
                $this->render_cover_image($song, 'small');
            }
            
            echo '<span class="azuracast-song-text">';
            echo esc_html($song['title']);
            
            if ($show_artist && !empty($song['artist'])) {
                echo ' - ' . esc_html($song['artist']);
            }
            
            echo '</span>';
            
            if ($show_time) {
                echo '<span class="azuracast-song-time">' . 
                     esc_html($this->format_time_ago($song['played_at'])) . 
                     '</span>';
            }
            
            echo '</div>';
        }
        
        echo '</div>';
    }
    
    /**
     * Render table layout
     */
    private function render_table_layout($songs, $show_covers, $show_time, $show_artist, $show_album) {
        echo '<table class="azuracast-song-table">';
        echo '<thead><tr>';
        
        if ($show_covers) echo '<th>' . esc_html__('Cover', 'azuracast-song-history') . '</th>';
        echo '<th>' . esc_html__('Title', 'azuracast-song-history') . '</th>';
        if ($show_artist) echo '<th>' . esc_html__('Artist', 'azuracast-song-history') . '</th>';
        if ($show_album) echo '<th>' . esc_html__('Album', 'azuracast-song-history') . '</th>';
        if ($show_time) echo '<th>' . esc_html__('Played', 'azuracast-song-history') . '</th>';
        
        echo '</tr></thead><tbody>';
        
        foreach ($songs as $song) {
            echo '<tr>';
            
            if ($show_covers) {
                echo '<td class="azuracast-cover-cell">';
                $this->render_cover_image($song, 'small');
                echo '</td>';
            }
            
            echo '<td class="azuracast-title-cell">' . esc_html($song['title']) . '</td>';
            
            if ($show_artist) {
                echo '<td class="azuracast-artist-cell">' . esc_html($song['artist']) . '</td>';
            }
            
            if ($show_album) {
                echo '<td class="azuracast-album-cell">' . esc_html($song['album']) . '</td>';
            }
            
            if ($show_time) {
                echo '<td class="azuracast-time-cell">' . 
                     esc_html($this->format_time_ago($song['played_at'])) . 
                     '</td>';
            }
            
            echo '</tr>';
        }
        
        echo '</tbody></table>';
    }
    
    /**
     * Render now playing item
     */
    private function render_now_playing_item($song, $show_covers, $layout) {
        echo '<div class="azuracast-nowplaying-item ' . esc_attr($layout) . '">';
        
        if ($show_covers) {
            echo '<div class="azuracast-nowplaying-cover">';
            $this->render_cover_image($song, $layout === 'minimal' ? 'small' : 'medium');
            echo '</div>';
        }
        
        echo '<div class="azuracast-nowplaying-info">';
        echo '<div class="azuracast-nowplaying-title">' . esc_html($song['title']) . '</div>';
        echo '<div class="azuracast-nowplaying-artist">' . esc_html($song['artist']) . '</div>';
        
        if (!empty($song['album'])) {
            echo '<div class="azuracast-nowplaying-album">' . esc_html($song['album']) . '</div>';
        }
        
        echo '</div>';
        echo '</div>';
    }
    
    /**
     * Render cover image
     */
    private function render_cover_image($song, $size = 'medium') {
        $art_url = !empty($song['art']) ? $song['art'] : '';
        $title = !empty($song['title']) ? $song['title'] : __('Unknown Title', 'azuracast-song-history');
        $artist = !empty($song['artist']) ? $song['artist'] : __('Unknown Artist', 'azuracast-song-history');
        
        if ($art_url) {
            echo '<img src="' . esc_url($art_url) . '" alt="' . 
                 esc_attr(sprintf(__('Cover for %s by %s', 'azuracast-song-history'), $title, $artist)) . 
                 '" class="azuracast-cover-image ' . esc_attr($size) . '" loading="lazy">';
        } else {
            echo '<div class="azuracast-no-cover ' . esc_attr($size) . '">
                    <span class="azuracast-music-icon">♪</span>
                  </div>';
        }
    }
    
    /**
     * Add auto-refresh script for shortcode
     */
    private function add_shortcode_refresh_script($container_id, $interval, $atts) {
        ?>
        <script type="text/javascript">
        (function() {
            var containerId = '<?php echo esc_js($container_id); ?>';
            var interval = <?php echo intval($interval * 1000); ?>;
            var settings = <?php echo wp_json_encode($atts); ?>;
            
            function refreshShortcode() {
                var container = document.getElementById(containerId);
                if (!container) return;
                
                var indicator = container.querySelector('.azuracast-refresh-indicator');
                var songsContainer = container.querySelector('.azuracast-songs-container');
                var timestamp = container.querySelector('.timestamp');
                
                if (indicator) indicator.style.display = 'block';
                
                var data = {
                    action: 'azuracast_refresh_shortcode',
                    nonce: '<?php echo wp_create_nonce('azuracast_refresh_shortcode'); ?>',
                    shortcode: 'azuracast_history',
                    atts: settings
                };
                
                fetch('<?php echo admin_url('admin-ajax.php'); ?>', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: new URLSearchParams(data)
                })
                .then(response => response.json())
                .then(data => {
                    if (indicator) indicator.style.display = 'none';
                    
                    if (data.success && data.data.html) {
                        if (songsContainer) {
                            songsContainer.innerHTML = data.data.html;
                        }
                        if (timestamp) {
                            timestamp.textContent = new Date().toLocaleTimeString();
                        }
                    }
                })
                .catch(error => {
                    if (indicator) indicator.style.display = 'none';
                    console.error('AzuraCast Shortcode refresh error:', error);
                });
            }
            
            if (interval > 0) {
                setInterval(refreshShortcode, interval);
            }
        })();
        </script>
        <?php
    }
    
    /**
     * Add auto-refresh script for now playing
     */
    private function add_nowplaying_refresh_script($container_id, $interval, $atts) {
        ?>
        <script type="text/javascript">
        (function() {
            var containerId = '<?php echo esc_js($container_id); ?>';
            var interval = <?php echo intval($interval * 1000); ?>;
            var settings = <?php echo wp_json_encode($atts); ?>;
            
            function refreshNowPlaying() {
                var container = document.getElementById(containerId);
                if (!container) return;
                
                var indicator = container.querySelector('.azuracast-refresh-indicator');
                var nowplayingContainer = container.querySelector('.azuracast-nowplaying-container');
                
                if (indicator) indicator.style.display = 'block';
                
                var data = {
                    action: 'azuracast_refresh_shortcode',
                    nonce: '<?php echo wp_create_nonce('azuracast_refresh_shortcode'); ?>',
                    shortcode: 'azuracast_nowplaying',
                    atts: settings
                };
                
                fetch('<?php echo admin_url('admin-ajax.php'); ?>', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: new URLSearchParams(data)
                })
                .then(response => response.json())
                .then(data => {
                    if (indicator) indicator.style.display = 'none';
                    
                    if (data.success && data.data.html) {
                        if (nowplayingContainer) {
                            nowplayingContainer.innerHTML = data.data.html;
                        }
                    }
                })
                .catch(error => {
                    if (indicator) indicator.style.display = 'none';
                    console.error('AzuraCast Now Playing refresh error:', error);
                });
            }
            
            if (interval > 0) {
                setInterval(refreshNowPlaying, interval);
            }
        })();
        </script>
        <?php
    }
    
    /**
     * Format time ago string
     */
    private function format_time_ago($timestamp) {
        $time_diff = time() - $timestamp;
        
        if ($time_diff < 60) {
            return __('Just now', 'azuracast-song-history');
        } elseif ($time_diff < 3600) {
            $minutes = floor($time_diff / 60);
            return sprintf(
                _n('%d min ago', '%d mins ago', $minutes, 'azuracast-song-history'),
                $minutes
            );
        } elseif ($time_diff < 86400) {
            $hours = floor($time_diff / 3600);
            return sprintf(
                _n('%d hour ago', '%d hours ago', $hours, 'azuracast-song-history'),
                $hours
            );
        } else {
            return date_i18n(get_option('time_format'), $timestamp);
        }
    }
    
    /**
     * Live moderator shortcode
     * 
     * Usage: [azuracast_live_moderator class="my-class" style="color: red;"]
     * 
     * @param array $atts Shortcode attributes
     * @return string HTML output
     */
    public function live_moderator_shortcode($atts) {
        $atts = shortcode_atts(array(
            'class' => '',
            'style' => ''
        ), $atts, 'azuracast_live_moderator');
        
        // Sanitize attributes
        $custom_class = sanitize_html_class($atts['class']);
        $custom_style = sanitize_text_field($atts['style']);
        
        // Get live status
        $api = new AzuraCast_API();
        $response = $api->get_song_history(1); // We only need the live status
        
        if (is_wp_error($response) || empty($response)) {
            // Try cached data as fallback
            $response = $api->get_cached_history(1);
            
            if (empty($response)) {
                return ''; // No data available
            }
        }
        
        // Extract live information
        $live_info = isset($response['station']) && isset($response['station']['live']) ? 
            $response['station']['live'] : null;
        
        // Check if live
        $is_live = $live_info && isset($live_info['is_live']) && $live_info['is_live'];
        
        if (!$is_live) {
            return ''; // Don't show anything when offline
        }
        
        // Extract moderator info
        $moderator_name = isset($live_info['streamer_name']) ? trim($live_info['streamer_name']) : '';
        
        // If no moderator name, don't show anything
        if (empty($moderator_name)) {
            return '';
        }
        
        // Simple output - just the moderator name
        $css_classes = array('azuracast-live-moderator-name');
        if ($custom_class) {
            $css_classes[] = $custom_class;
        }
        
        $output = '<span class="' . esc_attr(implode(' ', $css_classes)) . '"';
        if ($custom_style) {
            $output .= ' style="' . esc_attr($custom_style) . '"';
        }
        $output .= '>' . esc_html($moderator_name) . '</span>';
        
        return $output;
    }
}