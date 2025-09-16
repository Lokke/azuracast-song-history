<?php
/**
 * AzuraCast Song History Widget
 * 
 * Provides sidebar widget for displaying song history
 * 
 * @package AzuraCast_Song_History
 * @version 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class AzuraCast_Widget extends WP_Widget {
    
    /**
     * Widget constructor
     */
    public function __construct() {
        parent::__construct(
            'azuracast_song_history_widget',
            __('AzuraCast Song History', 'azuracast-song-history'),
            array(
                'description' => __('Display recent songs from your AzuraCast radio station', 'azuracast-song-history'),
                'classname' => 'azuracast-song-history-widget'
            )
        );
    }
    
    /**
     * Widget front-end display
     * 
     * @param array $args Widget arguments
     * @param array $instance Widget instance
     */
    public function widget($args, $instance) {
        // Get widget settings
        $title = !empty($instance['title']) ? $instance['title'] : __('Recent Songs', 'azuracast-song-history');
        $count = !empty($instance['count']) ? intval($instance['count']) : 5;
        $show_covers = isset($instance['show_covers']) ? (bool) $instance['show_covers'] : true;
        $show_time = isset($instance['show_time']) ? (bool) $instance['show_time'] : true;
        $layout = !empty($instance['layout']) ? $instance['layout'] : 'vertical';
        $auto_refresh = isset($instance['auto_refresh']) ? (bool) $instance['auto_refresh'] : false;
        $refresh_interval = !empty($instance['refresh_interval']) ? intval($instance['refresh_interval']) : 30;
        
        // Validate settings
        $count = max(1, min(20, $count));
        $refresh_interval = max(10, min(300, $refresh_interval));
        
        echo $args['before_widget'];
        
        if ($title) {
            echo $args['before_title'] . esc_html(apply_filters('widget_title', $title)) . $args['after_title'];
        }
        
        // Get song history
        $api = new AzuraCast_API();
        $songs = $api->get_song_history($count);
        
        if (is_wp_error($songs)) {
            // Try to get cached data as fallback
            $songs = $api->get_cached_history($count);
            
            if (empty($songs)) {
                echo '<p class="azuracast-error">' . 
                     esc_html__('Unable to load song history at this time.', 'azuracast-song-history') . 
                     '</p>';
                echo $args['after_widget'];
                return;
            }
        }
        
        if (empty($songs)) {
            echo '<p class="azuracast-no-songs">' . 
                 esc_html__('No songs available.', 'azuracast-song-history') . 
                 '</p>';
            echo $args['after_widget'];
            return;
        }
        
        // Generate unique widget ID for AJAX refresh
        $widget_id = 'azuracast-widget-' . uniqid();
        
        // Output widget content
        echo '<div class="azuracast-song-history-widget ' . esc_attr($layout) . '" id="' . esc_attr($widget_id) . '">';
        
        if ($auto_refresh) {
            echo '<div class="azuracast-refresh-indicator" style="display: none;">
                    <span class="spinner"></span> ' . 
                    esc_html__('Updating...', 'azuracast-song-history') . '
                  </div>';
        }
        
        echo '<div class="azuracast-songs-container">';
        
        foreach ($songs as $song) {
            $this->render_song_item($song, $show_covers, $show_time, $layout);
        }
        
        echo '</div>'; // songs-container
        
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
        
        echo '</div>'; // widget container
        
        // Add auto-refresh script if enabled
        if ($auto_refresh) {
            $this->add_refresh_script($widget_id, $refresh_interval, $instance);
        }
        
        echo $args['after_widget'];
    }
    
    /**
     * Render individual song item
     * 
     * @param array $song Song data
     * @param bool $show_covers Whether to show cover images
     * @param bool $show_time Whether to show play time
     * @param string $layout Layout style
     */
    private function render_song_item($song, $show_covers, $show_time, $layout) {
        $title = !empty($song['title']) ? $song['title'] : __('Unknown Title', 'azuracast-song-history');
        $artist = !empty($song['artist']) ? $song['artist'] : __('Unknown Artist', 'azuracast-song-history');
        $album = !empty($song['album']) ? $song['album'] : '';
        $art_url = !empty($song['art']) ? $song['art'] : '';
        $played_at = isset($song['played_at']) ? $song['played_at'] : time();
        
        echo '<div class="azuracast-song-item ' . esc_attr($layout) . '">';
        
        // Cover image
        if ($show_covers) {
            echo '<div class="azuracast-song-cover">';
            if ($art_url) {
                echo '<img src="' . esc_url($art_url) . '" alt="' . 
                     esc_attr(sprintf(__('Cover for %s by %s', 'azuracast-song-history'), $title, $artist)) . 
                     '" class="azuracast-cover-image" loading="lazy">';
            } else {
                echo '<div class="azuracast-no-cover">
                        <span class="azuracast-music-icon">♪</span>
                      </div>';
            }
            echo '</div>';
        }
        
        // Song information
        echo '<div class="azuracast-song-info">';
        
        echo '<div class="azuracast-song-title">' . esc_html($title) . '</div>';
        echo '<div class="azuracast-song-artist">' . esc_html($artist) . '</div>';
        
        if ($album) {
            echo '<div class="azuracast-song-album">' . esc_html($album) . '</div>';
        }
        
        if ($show_time) {
            echo '<div class="azuracast-song-time">' . 
                 esc_html($this->format_time_ago($played_at)) . 
                 '</div>';
        }
        
        echo '</div>'; // song-info
        echo '</div>'; // song-item
    }
    
    /**
     * Add auto-refresh JavaScript
     * 
     * @param string $widget_id Widget DOM ID
     * @param int $interval Refresh interval in seconds
     * @param array $instance Widget instance settings
     */
    private function add_refresh_script($widget_id, $interval, $instance) {
        ?>
        <script type="text/javascript">
        (function() {
            var widgetId = '<?php echo esc_js($widget_id); ?>';
            var interval = <?php echo intval($interval * 1000); ?>; // Convert to milliseconds
            var settings = <?php echo wp_json_encode($instance); ?>;
            
            function refreshWidget() {
                var widget = document.getElementById(widgetId);
                if (!widget) return;
                
                var indicator = widget.querySelector('.azuracast-refresh-indicator');
                var container = widget.querySelector('.azuracast-songs-container');
                var timestamp = widget.querySelector('.timestamp');
                
                if (indicator) indicator.style.display = 'block';
                
                var data = {
                    action: 'azuracast_refresh_widget',
                    nonce: '<?php echo wp_create_nonce('azuracast_refresh_widget'); ?>',
                    settings: settings
                };
                
                var xhr = new XMLHttpRequest();
                xhr.open('POST', '<?php echo admin_url('admin-ajax.php'); ?>', true);
                xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
                
                xhr.onreadystatechange = function() {
                    if (xhr.readyState === 4) {
                        if (indicator) indicator.style.display = 'none';
                        
                        if (xhr.status === 200) {
                            try {
                                var response = JSON.parse(xhr.responseText);
                                if (response.success && response.data.html) {
                                    if (container) {
                                        container.innerHTML = response.data.html;
                                    }
                                    if (timestamp) {
                                        var now = new Date();
                                        timestamp.textContent = now.toLocaleTimeString();
                                    }
                                }
                            } catch (e) {
                                console.error('AzuraCast Widget: Invalid JSON response');
                            }
                        }
                    }
                };
                
                // Convert data object to URL-encoded string
                var params = Object.keys(data).map(function(key) {
                    return encodeURIComponent(key) + '=' + encodeURIComponent(
                        typeof data[key] === 'object' ? JSON.stringify(data[key]) : data[key]
                    );
                }).join('&');
                
                xhr.send(params);
            }
            
            // Set up auto-refresh
            if (interval > 0) {
                setInterval(refreshWidget, interval);
            }
        })();
        </script>
        <?php
    }
    
    /**
     * Widget settings form
     * 
     * @param array $instance Widget instance
     */
    public function form($instance) {
        $title = isset($instance['title']) ? $instance['title'] : __('Recent Songs', 'azuracast-song-history');
        $count = isset($instance['count']) ? intval($instance['count']) : 5;
        $show_covers = isset($instance['show_covers']) ? (bool) $instance['show_covers'] : true;
        $show_time = isset($instance['show_time']) ? (bool) $instance['show_time'] : true;
        $layout = isset($instance['layout']) ? $instance['layout'] : 'vertical';
        $auto_refresh = isset($instance['auto_refresh']) ? (bool) $instance['auto_refresh'] : false;
        $refresh_interval = isset($instance['refresh_interval']) ? intval($instance['refresh_interval']) : 30;
        ?>
        
        <p>
            <label for="<?php echo esc_attr($this->get_field_id('title')); ?>">
                <?php esc_html_e('Title:', 'azuracast-song-history'); ?>
            </label>
            <input class="widefat" id="<?php echo esc_attr($this->get_field_id('title')); ?>" 
                   name="<?php echo esc_attr($this->get_field_name('title')); ?>" 
                   type="text" value="<?php echo esc_attr($title); ?>">
        </p>
        
        <p>
            <label for="<?php echo esc_attr($this->get_field_id('count')); ?>">
                <?php esc_html_e('Number of songs:', 'azuracast-song-history'); ?>
            </label>
            <input class="small-text" id="<?php echo esc_attr($this->get_field_id('count')); ?>" 
                   name="<?php echo esc_attr($this->get_field_name('count')); ?>" 
                   type="number" min="1" max="20" value="<?php echo esc_attr($count); ?>">
        </p>
        
        <p>
            <label for="<?php echo esc_attr($this->get_field_id('layout')); ?>">
                <?php esc_html_e('Layout:', 'azuracast-song-history'); ?>
            </label>
            <select class="widefat" id="<?php echo esc_attr($this->get_field_id('layout')); ?>" 
                    name="<?php echo esc_attr($this->get_field_name('layout')); ?>">
                <option value="vertical" <?php selected($layout, 'vertical'); ?>>
                    <?php esc_html_e('Vertical', 'azuracast-song-history'); ?>
                </option>
                <option value="horizontal" <?php selected($layout, 'horizontal'); ?>>
                    <?php esc_html_e('Horizontal', 'azuracast-song-history'); ?>
                </option>
                <option value="compact" <?php selected($layout, 'compact'); ?>>
                    <?php esc_html_e('Compact', 'azuracast-song-history'); ?>
                </option>
            </select>
        </p>
        
        <p>
            <input class="checkbox" type="checkbox" <?php checked($show_covers); ?> 
                   id="<?php echo esc_attr($this->get_field_id('show_covers')); ?>" 
                   name="<?php echo esc_attr($this->get_field_name('show_covers')); ?>">
            <label for="<?php echo esc_attr($this->get_field_id('show_covers')); ?>">
                <?php esc_html_e('Show cover images', 'azuracast-song-history'); ?>
            </label>
        </p>
        
        <p>
            <input class="checkbox" type="checkbox" <?php checked($show_time); ?> 
                   id="<?php echo esc_attr($this->get_field_id('show_time')); ?>" 
                   name="<?php echo esc_attr($this->get_field_name('show_time')); ?>">
            <label for="<?php echo esc_attr($this->get_field_id('show_time')); ?>">
                <?php esc_html_e('Show play time', 'azuracast-song-history'); ?>
            </label>
        </p>
        
        <p>
            <input class="checkbox" type="checkbox" <?php checked($auto_refresh); ?> 
                   id="<?php echo esc_attr($this->get_field_id('auto_refresh')); ?>" 
                   name="<?php echo esc_attr($this->get_field_name('auto_refresh')); ?>">
            <label for="<?php echo esc_attr($this->get_field_id('auto_refresh')); ?>">
                <?php esc_html_e('Auto-refresh', 'azuracast-song-history'); ?>
            </label>
        </p>
        
        <p class="azuracast-refresh-settings" <?php if (!$auto_refresh) echo 'style="display:none;"'; ?>>
            <label for="<?php echo esc_attr($this->get_field_id('refresh_interval')); ?>">
                <?php esc_html_e('Refresh interval (seconds):', 'azuracast-song-history'); ?>
            </label>
            <input class="small-text" id="<?php echo esc_attr($this->get_field_id('refresh_interval')); ?>" 
                   name="<?php echo esc_attr($this->get_field_name('refresh_interval')); ?>" 
                   type="number" min="10" max="300" value="<?php echo esc_attr($refresh_interval); ?>">
        </p>
        
        <script type="text/javascript">
        (function() {
            var autoRefreshCheckbox = document.getElementById('<?php echo esc_js($this->get_field_id('auto_refresh')); ?>');
            var refreshSettings = autoRefreshCheckbox.closest('form').querySelector('.azuracast-refresh-settings');
            
            if (autoRefreshCheckbox && refreshSettings) {
                autoRefreshCheckbox.addEventListener('change', function() {
                    refreshSettings.style.display = this.checked ? 'block' : 'none';
                });
            }
        })();
        </script>
        
        <?php
    }
    
    /**
     * Update widget settings
     * 
     * @param array $new_instance New settings
     * @param array $old_instance Previous settings
     * @return array Updated settings
     */
    public function update($new_instance, $old_instance) {
        $instance = array();
        
        $instance['title'] = !empty($new_instance['title']) ? 
            sanitize_text_field($new_instance['title']) : '';
        
        $instance['count'] = !empty($new_instance['count']) ? 
            max(1, min(20, intval($new_instance['count']))) : 5;
        
        $instance['show_covers'] = isset($new_instance['show_covers']);
        $instance['show_time'] = isset($new_instance['show_time']);
        
        $instance['layout'] = !empty($new_instance['layout']) && 
            in_array($new_instance['layout'], array('vertical', 'horizontal', 'compact')) ? 
            $new_instance['layout'] : 'vertical';
        
        $instance['auto_refresh'] = isset($new_instance['auto_refresh']);
        
        $instance['refresh_interval'] = !empty($new_instance['refresh_interval']) ? 
            max(10, min(300, intval($new_instance['refresh_interval']))) : 30;
        
        return $instance;
    }
    
    /**
     * Format time ago string
     * 
     * @param int $timestamp Unix timestamp
     * @return string Formatted time ago
     */
    private function format_time_ago($timestamp) {
        $time_diff = time() - $timestamp;
        
        if ($time_diff < 60) {
            return __('Just now', 'azuracast-song-history');
        } elseif ($time_diff < 3600) {
            $minutes = floor($time_diff / 60);
            return sprintf(
                _n('%d minute ago', '%d minutes ago', $minutes, 'azuracast-song-history'),
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
}