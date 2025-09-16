<?php
/**
 * Elementor Song History Widget
 * 
 * @package AzuraCast_Song_History
 */

use Elementor\Widget_Base;
use Elementor\Controls_Manager;
use Elementor\Group_Control_Typography;

class AzuraCast_Song_History_Widget extends Widget_Base {

    public function get_name() {
        return 'azuracast_song_history';
    }

    public function get_title() {
        return __('Song History', 'azuracast-song-history');
    }

    public function get_icon() {
        return 'eicon-menu';
    }

    public function get_categories() {
        return ['azuracast'];
    }

    protected function register_controls() {
        // Content Section
        $this->start_controls_section(
            'content_section',
            [
                'label' => __('Content', 'azuracast-song-history'),
                'tab' => Controls_Manager::TAB_CONTENT,
            ]
        );

        $this->add_control(
            'song_count',
            [
                'label' => __('Number of Songs', 'azuracast-song-history'),
                'type' => Controls_Manager::NUMBER,
                'min' => 1,
                'max' => 20,
                'step' => 1,
                'default' => 5,
            ]
        );

        $this->add_control(
            'layout_style',
            [
                'label' => __('Layout Style', 'azuracast-song-history'),
                'type' => Controls_Manager::SELECT,
                'default' => 'list',
                'options' => [
                    'list' => __('List', 'azuracast-song-history'),
                    'compact' => __('Compact', 'azuracast-song-history'),
                    'cards' => __('Cards', 'azuracast-song-history'),
                ],
            ]
        );

        $this->add_control(
            'show_covers',
            [
                'label' => __('Show Cover Art', 'azuracast-song-history'),
                'type' => Controls_Manager::SWITCHER,
                'label_on' => __('Show', 'azuracast-song-history'),
                'label_off' => __('Hide', 'azuracast-song-history'),
                'return_value' => 'yes',
                'default' => 'yes',
            ]
        );

        $this->add_control(
            'show_time',
            [
                'label' => __('Show Play Time', 'azuracast-song-history'),
                'type' => Controls_Manager::SWITCHER,
                'label_on' => __('Show', 'azuracast-song-history'),
                'label_off' => __('Hide', 'azuracast-song-history'),
                'return_value' => 'yes',
                'default' => 'yes',
            ]
        );

        $this->add_responsive_control(
            'content_align',
            [
                'label' => __('Alignment', 'azuracast-song-history'),
                'type' => Controls_Manager::CHOOSE,
                'options' => [
                    'left' => [
                        'title' => __('Left', 'azuracast-song-history'),
                        'icon' => 'eicon-text-align-left',
                    ],
                    'center' => [
                        'title' => __('Center', 'azuracast-song-history'),
                        'icon' => 'eicon-text-align-center',
                    ],
                    'right' => [
                        'title' => __('Right', 'azuracast-song-history'),
                        'icon' => 'eicon-text-align-right',
                    ],
                ],
                'default' => 'left',
                'selectors' => [
                    '{{WRAPPER}} .azuracast-song-history-elementor' => 'text-align: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'title_tag',
            [
                'label' => __('Title HTML Tag', 'azuracast-song-history'),
                'type' => Controls_Manager::SELECT,
                'default' => 'h4',
                'options' => [
                    'h1' => __('H1', 'azuracast-song-history'),
                    'h2' => __('H2', 'azuracast-song-history'),
                    'h3' => __('H3', 'azuracast-song-history'),
                    'h4' => __('H4', 'azuracast-song-history'),
                    'h5' => __('H5', 'azuracast-song-history'),
                    'h6' => __('H6', 'azuracast-song-history'),
                    'p' => __('Paragraph', 'azuracast-song-history'),
                    'span' => __('Span', 'azuracast-song-history'),
                    'div' => __('Div', 'azuracast-song-history'),
                ],
            ]
        );

        $this->add_control(
            'artist_tag',
            [
                'label' => __('Artist HTML Tag', 'azuracast-song-history'),
                'type' => Controls_Manager::SELECT,
                'default' => 'p',
                'options' => [
                    'h1' => __('H1', 'azuracast-song-history'),
                    'h2' => __('H2', 'azuracast-song-history'),
                    'h3' => __('H3', 'azuracast-song-history'),
                    'h4' => __('H4', 'azuracast-song-history'),
                    'h5' => __('H5', 'azuracast-song-history'),
                    'h6' => __('H6', 'azuracast-song-history'),
                    'p' => __('Paragraph', 'azuracast-song-history'),
                    'span' => __('Span', 'azuracast-song-history'),
                    'div' => __('Div', 'azuracast-song-history'),
                ],
            ]
        );

        $this->add_control(
            'cover_size',
            [
                'label' => __('Cover Size', 'azuracast-song-history'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['px'],
                'range' => [
                    'px' => [
                        'min' => 30,
                        'max' => 150,
                        'step' => 5,
                    ],
                ],
                'default' => [
                    'unit' => 'px',
                    'size' => 50,
                ],
                'selectors' => [
                    '{{WRAPPER}} .azuracast-cover-image' => 'width: {{SIZE}}{{UNIT}}; height: {{SIZE}}{{UNIT}};',
                    '{{WRAPPER}} .azuracast-no-cover' => 'width: {{SIZE}}{{UNIT}}; height: {{SIZE}}{{UNIT}};',
                ],
                'condition' => [
                    'show_covers' => 'yes',
                ],
            ]
        );

        $this->end_controls_section();

        // Style Section
        $this->start_controls_section(
            'style_section',
            [
                'label' => __('Style', 'azuracast-song-history'),
                'tab' => Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name' => 'title_typography',
                'label' => __('Title Typography', 'azuracast-song-history'),
                'selector' => '{{WRAPPER}} .azuracast-song-title',
            ]
        );

        $this->add_control(
            'title_color',
            [
                'label' => __('Title Color', 'azuracast-song-history'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .azuracast-song-title' => 'color: {{VALUE}}',
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name' => 'artist_typography',
                'label' => __('Artist Typography', 'azuracast-song-history'),
                'selector' => '{{WRAPPER}} .azuracast-song-artist',
            ]
        );

        $this->add_control(
            'artist_color',
            [
                'label' => __('Artist Color', 'azuracast-song-history'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .azuracast-song-artist' => 'color: {{VALUE}}',
                ],
            ]
        );

        $this->end_controls_section();
    }

    protected function render() {
        $settings = $this->get_settings_for_display();
        
        // Get song history
        $api = new AzuraCast_API();
        $response = $api->get_song_history($settings['song_count']);
        
        if (is_wp_error($response) || empty($response)) {
            $response = $api->get_cached_history($settings['song_count']);
            if (empty($response)) {
                echo '<div class="azuracast-song-history-elementor error">Unable to load song history</div>';
                return;
            }
        }
        
        $songs = isset($response['song_history']) ? $response['song_history'] : array();
        
        if (empty($songs)) {
            echo '<div class="azuracast-song-history-elementor empty">No songs available</div>';
            return;
        }
        
        $layout_class = 'layout-' . $settings['layout_style'];
        
        echo '<div class="azuracast-song-history-elementor ' . esc_attr($layout_class) . '">';
        
        foreach ($songs as $song) {
            echo '<div class="azuracast-song-item">';
            
            // Cover Art
            if ($settings['show_covers'] === 'yes') {
                echo '<div class="azuracast-song-cover">';
                if (!empty($song['art'])) {
                    echo '<img src="' . esc_url($song['art']) . '" alt="' . 
                         esc_attr($song['title'] . ' by ' . $song['artist']) . 
                         '" class="azuracast-cover-image">';
                } else {
                    echo '<div class="azuracast-no-cover">♪</div>';
                }
                echo '</div>';
            }
            
            // Song Info
            echo '<div class="azuracast-song-info">';
            
            $title_tag = $settings['title_tag'];
            $artist_tag = $settings['artist_tag'];
            
            echo '<' . $title_tag . ' class="azuracast-song-title">' . 
                 esc_html($song['title']) . '</' . $title_tag . '>';
            echo '<' . $artist_tag . ' class="azuracast-song-artist">' . 
                 esc_html($song['artist']) . '</' . $artist_tag . '>';
            
            if ($settings['show_time'] === 'yes' && !empty($song['played_at'])) {
                echo '<div class="azuracast-song-time">' . 
                     esc_html($this->format_time_ago($song['played_at'])) . 
                     '</div>';
            }
            
            echo '</div>';
            echo '</div>';
        }
        
        echo '</div>';
    }
        
        $songs = isset($response['song_history']) ? $response['song_history'] : array();
        
        if (empty($songs)) {
            echo '<div class="azuracast-song-history empty">No songs available</div>';
            return;
        }
        
        $layout_class = 'layout-' . $settings['layout_style'];
        
        echo '<div class="azuracast-song-history-elementor ' . esc_attr($layout_class) . '">';
        
        foreach ($songs as $song) {
            echo '<div class="azuracast-song-item">';
            
            // Cover Art
            if ($settings['show_covers'] === 'yes') {
                echo '<div class="azuracast-song-cover">';
                if (!empty($song['art'])) {
                    echo '<img src="' . esc_url($song['art']) . '" alt="' . 
                         esc_attr($song['title'] . ' by ' . $song['artist']) . 
                         '" class="azuracast-cover-image">';
                } else {
                    echo '<div class="azuracast-no-cover">♪</div>';
                }
                echo '</div>';
            }
            
            // Song Info
            echo '<div class="azuracast-song-info">';
            echo '<div class="azuracast-song-title">' . esc_html($song['title']) . '</div>';
            echo '<div class="azuracast-song-artist">' . esc_html($song['artist']) . '</div>';
            
            if ($settings['show_time'] === 'yes' && !empty($song['played_at'])) {
                echo '<div class="azuracast-song-time">' . 
                     esc_html($this->format_time_ago($song['played_at'])) . 
                     '</div>';
            }
            
            echo '</div>';
            echo '</div>';
        }
        
        echo '</div>';
    }
    
    private function format_time_ago($timestamp) {
        if (empty($timestamp)) {
            return '';
        }
        
        $time_diff = current_time('timestamp') - $timestamp;
        
        if ($time_diff < 60) {
            return __('Just now', 'azuracast-song-history');
        } elseif ($time_diff < 3600) {
            $minutes = floor($time_diff / 60);
            return sprintf(_n('%d min ago', '%d mins ago', $minutes, 'azuracast-song-history'), $minutes);
        } elseif ($time_diff < 86400) {
            $hours = floor($time_diff / 3600);
            return sprintf(_n('%d hour ago', '%d hours ago', $hours, 'azuracast-song-history'), $hours);
        } else {
            return date_i18n(get_option('time_format'), $timestamp);
        }
    }
}