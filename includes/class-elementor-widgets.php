<?php
/**
 * AzuraCast Song History Elementor Widgets
 * 
 * Custom Elementor widgets for better design integration
 * 
 * @package AzuraCast_Song_History
 * @version 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

use Elementor\Plugin;
use Elementor\Widgets_Manager;

/**
 * Check if Elementor is active
 */
if (!did_action('elementor/loaded')) {
    return;
}

/**
 * Elementor Widgets Manager
 */
class AzuraCast_Elementor_Widgets {
    
    /**
     * Initialize the widgets
     */
    public function __construct() {
        add_action('elementor/widgets/register', array($this, 'register_widgets'));
        add_action('elementor/elements/categories', array($this, 'register_category'));
    }
    
    /**
     * Register widget category
     */
    public function register_category($elements_manager) {
        $elements_manager->add_category(
            'azuracast',
            array(
                'title' => __('AzuraCast', 'azuracast-song-history'),
                'icon' => 'fa fa-music',
            )
        );
    }
    
    /**
     * Register widgets
     */
    public function register_widgets($widgets_manager) {
        // Check if required classes exist
        if (!class_exists('AzuraCast_API')) {
            return; // Exit early if API class not available
        }
        
        require_once plugin_dir_path(__FILE__) . 'elementor-widgets/live-moderator.php';
        require_once plugin_dir_path(__FILE__) . 'elementor-widgets/current-song.php';
        require_once plugin_dir_path(__FILE__) . 'elementor-widgets/song-history.php';
        
        $widgets_manager->register(new AzuraCast_Live_Moderator_Widget());
        $widgets_manager->register(new AzuraCast_Current_Song_Widget());
        $widgets_manager->register(new AzuraCast_Song_History_Widget());
    }
}

// Initialize if Elementor is loaded
if (class_exists('Elementor\Widget_Base')) {
    new AzuraCast_Elementor_Widgets();
}