<?php
/**
 * Elementor Current Song Widget
 * 
 * @package AzuraCast_Song_History
 */

use Elementor\Widget_Base;
use Elementor\Controls_Manager;
use Elementor\Group_Control_Typography;
use Elementor\Group_Control_Border;
use Elementor\Group_Control_Box_Shadow;

class AzuraCast_Current_Song_Widget extends Widget_Base {

    public function get_name() {
        return 'azuracast_current_song';
    }

    public function get_title() {
        return __('Current Song', 'azuracast-song-history');
    }

    public function get_icon() {
        return 'eicon-play';
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
            'show_cover',
            [
                'label' => __('Show Cover Art', 'azuracast-song-history'),
                'type' => Controls_Manager::SWITCHER,
                'label_on' => __('Show', 'azuracast-song-history'),
                'label_off' => __('Hide', 'azuracast-song-history'),
                'return_value' => 'yes',
                'default' => 'yes',
                'condition' => [
                    'layout_style!' => 'text-only',
                ],
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
                    '{{WRAPPER}} .azuracast-current-song-elementor' => 'text-align: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'title_tag',
            [
                'label' => __('Title HTML Tag', 'azuracast-song-history'),
                'type' => Controls_Manager::SELECT,
                'default' => 'h3',
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
                        'min' => 50,
                        'max' => 300,
                        'step' => 10,
                    ],
                ],
                'default' => [
                    'unit' => 'px',
                    'size' => 120,
                ],
                'condition' => [
                    'show_cover' => 'yes',
                ],
                'selectors' => [
                    '{{WRAPPER}} .azuracast-cover-image' => 'width: {{SIZE}}{{UNIT}}; height: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->add_control(
            'layout',
            [
                'label' => __('Layout', 'azuracast-song-history'),
                'type' => Controls_Manager::SELECT,
                'default' => 'horizontal',
                'options' => [
                    'horizontal' => __('Horizontal', 'azuracast-song-history'),
                    'vertical' => __('Vertical', 'azuracast-song-history'),
                    'cover_only' => __('Cover Only', 'azuracast-song-history'),
                    'text_only' => __('Text Only', 'azuracast-song-history'),
                ],
            ]
        );

        $this->add_control(
            'show_artist',
            [
                'label' => __('Show Artist', 'azuracast-song-history'),
                'type' => Controls_Manager::SWITCHER,
                'label_on' => __('Show', 'azuracast-song-history'),
                'label_off' => __('Hide', 'azuracast-song-history'),
                'return_value' => 'yes',
                'default' => 'yes',
                'condition' => [
                    'layout!' => 'cover_only',
                ],
            ]
        );

        $this->add_control(
            'show_title',
            [
                'label' => __('Show Title', 'azuracast-song-history'),
                'type' => Controls_Manager::SWITCHER,
                'label_on' => __('Show', 'azuracast-song-history'),
                'label_off' => __('Hide', 'azuracast-song-history'),
                'return_value' => 'yes',
                'default' => 'yes',
                'condition' => [
                    'layout!' => 'cover_only',
                ],
            ]
        );

        $this->add_control(
            'show_album',
            [
                'label' => __('Show Album', 'azuracast-song-history'),
                'type' => Controls_Manager::SWITCHER,
                'label_on' => __('Show', 'azuracast-song-history'),
                'label_off' => __('Hide', 'azuracast-song-history'),
                'return_value' => 'yes',
                'default' => 'no',
                'condition' => [
                    'layout!' => 'cover_only',
                ],
            ]
        );

        $this->end_controls_section();

        // Style Section - Container
        $this->start_controls_section(
            'container_style',
            [
                'label' => __('Container', 'azuracast-song-history'),
                'tab' => Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_control(
            'container_background',
            [
                'label' => __('Background Color', 'azuracast-song-history'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .azuracast-current-song' => 'background-color: {{VALUE}}',
                ],
            ]
        );

        $this->add_responsive_control(
            'container_padding',
            [
                'label' => __('Padding', 'azuracast-song-history'),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', '%', 'em'],
                'selectors' => [
                    '{{WRAPPER}} .azuracast-current-song' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Border::get_type(),
            [
                'name' => 'container_border',
                'selector' => '{{WRAPPER}} .azuracast-current-song',
            ]
        );

        $this->add_responsive_control(
            'container_border_radius',
            [
                'label' => __('Border Radius', 'azuracast-song-history'),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', '%'],
                'selectors' => [
                    '{{WRAPPER}} .azuracast-current-song' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Box_Shadow::get_type(),
            [
                'name' => 'container_box_shadow',
                'selector' => '{{WRAPPER}} .azuracast-current-song',
            ]
        );

        $this->end_controls_section();

        // Style Section - Typography
        $this->start_controls_section(
            'typography_style',
            [
                'label' => __('Typography', 'azuracast-song-history'),
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
        
        // Get current song
        $api = new AzuraCast_API();
        $response = $api->get_song_history(1);
        
        if (is_wp_error($response) || empty($response)) {
            $response = $api->get_cached_history(1);
            if (empty($response)) {
                echo '<div class="azuracast-current-song-elementor error">Unable to load current song</div>';
                return;
            }
        }
        
        $current_song = isset($response['now_playing']) ? $response['now_playing'] : null;
        
        if (!$current_song) {
            echo '<div class="azuracast-current-song-elementor error">No song playing</div>';
            return;
        }
        
        $layout_class = 'layout-' . $settings['layout_style'];
        
        echo '<div class="azuracast-current-song-elementor ' . esc_attr($layout_class) . '">';
        
        // Cover Art
        if ($settings['show_cover'] === 'yes' && $settings['layout_style'] !== 'text-only') {
            echo '<div class="azuracast-song-cover">';
            if (!empty($current_song['art'])) {
                echo '<img src="' . esc_url($current_song['art']) . '" alt="' . 
                     esc_attr($current_song['title'] . ' by ' . $current_song['artist']) . 
                     '" class="azuracast-cover-image">';
            } else {
                echo '<div class="azuracast-no-cover">♪</div>';
            }
            echo '</div>';
        }
        
        // Song Info (unless cover-only layout)
        if ($settings['layout_style'] !== 'cover-only') {
            echo '<div class="azuracast-song-info">';
            
            $title_tag = $settings['title_tag'];
            $artist_tag = $settings['artist_tag'];
            
            echo '<' . $title_tag . ' class="azuracast-song-title">' . 
                 esc_html($current_song['title']) . '</' . $title_tag . '>';
            echo '<' . $artist_tag . ' class="azuracast-song-artist">' . 
                 esc_html($current_song['artist']) . '</' . $artist_tag . '>';
            
            echo '</div>';
        }
        
        echo '</div>';
    }
        
        // Cover Art
        if ($settings['show_cover'] === 'yes' && $settings['layout'] !== 'text_only') {
            echo '<div class="azuracast-song-cover">';
            if (!empty($current_song['art'])) {
                echo '<img src="' . esc_url($current_song['art']) . '" alt="' . 
                     esc_attr($current_song['title'] . ' by ' . $current_song['artist']) . 
                     '" class="azuracast-cover-image">';
            } else {
                echo '<div class="azuracast-no-cover">♪</div>';
            }
            echo '</div>';
        }
        
        // Song Info
        if ($settings['layout'] !== 'cover_only') {
            echo '<div class="azuracast-song-info">';
            
            if ($settings['show_title'] === 'yes') {
                echo '<div class="azuracast-song-title">' . esc_html($current_song['title']) . '</div>';
            }
            
            if ($settings['show_artist'] === 'yes') {
                echo '<div class="azuracast-song-artist">' . esc_html($current_song['artist']) . '</div>';
            }
            
            if ($settings['show_album'] === 'yes' && !empty($current_song['album'])) {
                echo '<div class="azuracast-song-album">' . esc_html($current_song['album']) . '</div>';
            }
            
            echo '</div>';
        }
        
        echo '</div>';
    }
}