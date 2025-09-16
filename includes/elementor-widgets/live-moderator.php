<?php
/**
 * Elementor Live Moderator Widget
 * 
 * @package AzuraCast_Song_History
 */

use Elementor\Widget_Base;
use Elementor\Controls_Manager;
use Elementor\Group_Control_Typography;
use Elementor\Group_Control_Text_Shadow;

class AzuraCast_Live_Moderator_Widget extends Widget_Base {

    public function get_name() {
        return 'azuracast_live_moderator';
    }

    public function get_title() {
        return __('Live Moderator', 'azuracast-song-history');
    }

    public function get_icon() {
        return 'eicon-microphone';
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
            'prefix_text',
            [
                'label' => __('Prefix Text', 'azuracast-song-history'),
                'type' => Controls_Manager::TEXT,
                'default' => __('🔴 LIVE:', 'azuracast-song-history'),
                'placeholder' => __('Text before moderator name', 'azuracast-song-history'),
            ]
        );

        $this->add_control(
            'suffix_text',
            [
                'label' => __('Suffix Text', 'azuracast-song-history'),
                'type' => Controls_Manager::TEXT,
                'default' => __('ist on Air!', 'azuracast-song-history'),
                'placeholder' => __('Text after moderator name', 'azuracast-song-history'),
            ]
        );

        $this->add_control(
            'offline_behavior',
            [
                'label' => __('When Offline', 'azuracast-song-history'),
                'type' => Controls_Manager::SELECT,
                'default' => 'hide',
                'options' => [
                    'hide' => __('Hide completely', 'azuracast-song-history'),
                    'show_message' => __('Show custom message', 'azuracast-song-history'),
                ],
            ]
        );

        $this->add_control(
            'offline_message',
            [
                'label' => __('Offline Message', 'azuracast-song-history'),
                'type' => Controls_Manager::TEXT,
                'default' => __('Automated playlist', 'azuracast-song-history'),
                'condition' => [
                    'offline_behavior' => 'show_message',
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
                'name' => 'typography',
                'selector' => '{{WRAPPER}} .azuracast-live-display',
            ]
        );

        $this->add_control(
            'text_color',
            [
                'label' => __('Text Color', 'azuracast-song-history'),
                'type' => Controls_Manager::COLOR,
                'default' => '#dc3545',
                'selectors' => [
                    '{{WRAPPER}} .azuracast-live-display' => 'color: {{VALUE}}',
                ],
            ]
        );

        $this->add_control(
            'moderator_color',
            [
                'label' => __('Moderator Name Color', 'azuracast-song-history'),
                'type' => Controls_Manager::COLOR,
                'default' => '#ffffff',
                'selectors' => [
                    '{{WRAPPER}} .azuracast-moderator-name' => 'color: {{VALUE}}',
                ],
            ]
        );

        $this->add_control(
            'background_color',
            [
                'label' => __('Background Color', 'azuracast-song-history'),
                'type' => Controls_Manager::COLOR,
                'default' => '#dc3545',
                'selectors' => [
                    '{{WRAPPER}} .azuracast-live-display' => 'background-color: {{VALUE}}',
                ],
            ]
        );

        $this->add_responsive_control(
            'padding',
            [
                'label' => __('Padding', 'azuracast-song-history'),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', '%', 'em'],
                'default' => [
                    'top' => 10,
                    'right' => 15,
                    'bottom' => 10,
                    'left' => 15,
                    'unit' => 'px',
                ],
                'selectors' => [
                    '{{WRAPPER}} .azuracast-live-display' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'border_radius',
            [
                'label' => __('Border Radius', 'azuracast-song-history'),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', '%'],
                'default' => [
                    'top' => 25,
                    'right' => 25,
                    'bottom' => 25,
                    'left' => 25,
                    'unit' => 'px',
                ],
                'selectors' => [
                    '{{WRAPPER}} .azuracast-live-display' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Text_Shadow::get_type(),
            [
                'name' => 'text_shadow',
                'selector' => '{{WRAPPER}} .azuracast-live-display',
            ]
        );

        $this->end_controls_section();
    }

    protected function render() {
        $settings = $this->get_settings_for_display();
        
        // Get live status
        $api = new AzuraCast_API();
        $response = $api->get_song_history(1);
        
        if (is_wp_error($response) || empty($response)) {
            $response = $api->get_cached_history(1);
            if (empty($response)) {
                if ($settings['offline_behavior'] === 'show_message') {
                    echo '<div class="azuracast-live-display offline">' . esc_html($settings['offline_message']) . '</div>';
                }
                return;
            }
        }
        
        // Extract live information
        $live_info = isset($response['station']) && isset($response['station']['live']) ? 
            $response['station']['live'] : null;
        
        $is_live = $live_info && isset($live_info['is_live']) && $live_info['is_live'];
        $moderator_name = $is_live && isset($live_info['streamer_name']) ? 
            trim($live_info['streamer_name']) : '';
        
        if (!$is_live || empty($moderator_name)) {
            if ($settings['offline_behavior'] === 'show_message') {
                echo '<div class="azuracast-live-display offline">' . esc_html($settings['offline_message']) . '</div>';
            }
            return;
        }
        
        // Render live moderator
        echo '<div class="azuracast-live-display online">';
        
        if (!empty($settings['prefix_text'])) {
            echo '<span class="azuracast-prefix">' . esc_html($settings['prefix_text']) . ' </span>';
        }
        
        echo '<span class="azuracast-moderator-name">' . esc_html($moderator_name) . '</span>';
        
        if (!empty($settings['suffix_text'])) {
            echo ' <span class="azuracast-suffix">' . esc_html($settings['suffix_text']) . '</span>';
        }
        
        echo '</div>';
    }
}