<?php
/**
 * GitHub Updater for AzuraCast Song History Plugin
 * 
 * @package AzuraCast_Song_History
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class AzuraCast_Song_History_Updater {
    
    private $plugin_slug;
    private $version;
    private $plugin_path;
    private $plugin_file;
    private $github_username;
    private $github_repo;
    private $github_api_result;
    private $plugin_activated;
    
    public function __construct($plugin_file, $github_username, $github_repo) {
        add_filter('pre_set_site_transient_update_plugins', array($this, 'modify_transient'), 10, 1);
        add_filter('plugins_api', array($this, 'plugin_popup'), 10, 3);
        add_filter('upgrader_post_install', array($this, 'after_install'), 10, 3);
        
        $this->plugin_file = $plugin_file;
        $this->plugin_slug = plugin_basename($plugin_file);
        $this->version = AZURACAST_SONG_HISTORY_VERSION;
        $this->plugin_path = plugin_dir_path($plugin_file);
        $this->github_username = $github_username;
        $this->github_repo = $github_repo;
        $this->plugin_activated = is_plugin_active($this->plugin_slug);
    }
    
    /**
     * Get information about remote GitHub repository
     */
    private function get_repository_info() {
        if (is_null($this->github_api_result)) {
            $request_uri = sprintf('https://api.github.com/repos/%s/%s/releases/latest', 
                $this->github_username, $this->github_repo);
            
            $response = wp_remote_get($request_uri, array(
                'timeout' => 15,
                'headers' => array(
                    'Accept' => 'application/vnd.github.v3+json'
                )
            ));
            
            if (!is_wp_error($response) && wp_remote_retrieve_response_code($response) === 200) {
                $this->github_api_result = json_decode(wp_remote_retrieve_body($response), true);
            }
        }
        
        return $this->github_api_result;
    }
    
    /**
     * Modify the plugin update transient
     */
    public function modify_transient($transient) {
        if (property_exists($transient, 'checked') && $checked = $transient->checked) {
            if (isset($checked[$this->plugin_slug])) {
                $remote_version = $this->get_new_version();
                
                if (version_compare($this->version, $remote_version, '<')) {
                    $response = array(
                        'slug' => $this->plugin_slug,
                        'plugin' => $this->plugin_slug,
                        'new_version' => $remote_version,
                        'tested' => '6.8',
                        'package' => $this->get_zip_url(),
                        'url' => sprintf('https://github.com/%s/%s', $this->github_username, $this->github_repo)
                    );
                    
                    $transient->response[$this->plugin_slug] = (object) $response;
                }
            }
        }
        
        return $transient;
    }
    
    /**
     * Push in plugin version information for plugin popup
     */
    public function plugin_popup($result, $action, $args) {
        if ($action !== 'plugin_information') {
            return false;
        }
        
        if (!empty($args->slug)) {
            if ($args->slug == $this->plugin_slug) {
                $this->get_repository_info();
                
                $plugin = get_plugin_data($this->plugin_file);
                
                $popup = array(
                    'name' => $plugin['Name'],
                    'slug' => $this->plugin_slug,
                    'version' => $this->github_api_result['tag_name'],
                    'author' => $plugin['AuthorName'],
                    'author_profile' => $plugin['AuthorURI'],
                    'last_updated' => $this->github_api_result['published_at'],
                    'homepage' => $plugin['PluginURI'],
                    'short_description' => $plugin['Description'],
                    'sections' => array(
                        'Description' => $plugin['Description'],
                        'Updates' => $this->github_api_result['body'],
                    ),
                    'download_link' => $this->get_zip_url()
                );
                
                return (object) $popup;
            }
        }
        
        return false;
    }
    
    /**
     * Perform additional actions after installation
     */
    public function after_install($response, $hook_extra, $result) {
        global $wp_filesystem;
        
        $install_directory = plugin_dir_path($this->plugin_file);
        $plugin_slug = dirname($this->plugin_slug);
        
        // Expected target directory
        $target_directory = WP_PLUGIN_DIR . '/' . $plugin_slug;
        
        // GitHub ZIP files come with a folder like "Lokke-azuracast-song-history-296e7ea"
        // We need to move the contents to our plugin directory
        if (isset($result['destination'])) {
            $source_dir = $result['destination'];
            
            // Remove the old plugin directory first
            if ($wp_filesystem->exists($target_directory)) {
                $wp_filesystem->rmdir($target_directory, true);
            }
            
            // Find the actual plugin folder inside the ZIP (GitHub adds repo name + commit)
            $files = $wp_filesystem->dirlist($source_dir);
            if (!empty($files)) {
                $source_plugin_dir = $source_dir . '/' . key($files);
                
                // Move the entire source directory to target
                if ($wp_filesystem->is_dir($source_plugin_dir)) {
                    $wp_filesystem->move($source_plugin_dir, $target_directory);
                    $result['destination'] = $target_directory;
                    
                    // Clean up the temporary directory
                    $wp_filesystem->rmdir($source_dir, true);
                }
            }
        }
        
        if ($this->plugin_activated) {
            activate_plugin($this->plugin_slug);
        }
        
        return $result;
    }
    
    /**
     * Get latest version number from GitHub
     */
    private function get_new_version() {
        $this->get_repository_info();
        
        if (!empty($this->github_api_result['tag_name'])) {
            return ltrim($this->github_api_result['tag_name'], 'v');
        }
        
        return $this->version;
    }
    
    /**
     * Get ZIP download URL from GitHub
     */
    private function get_zip_url() {
        $this->get_repository_info();
        
        if (!empty($this->github_api_result['zipball_url'])) {
            return $this->github_api_result['zipball_url'];
        }
        
        return false;
    }
    
    /**
     * Check if plugin has update available
     */
    public function has_update() {
        $remote_version = $this->get_new_version();
        return version_compare($this->version, $remote_version, '<');
    }
    
    /**
     * Get update notification HTML for admin
     */
    public function get_update_notice() {
        if (!$this->has_update()) {
            return '';
        }
        
        $remote_version = $this->get_new_version();
        $update_url = wp_nonce_url(self_admin_url('update.php?action=upgrade-plugin&plugin=' . urlencode($this->plugin_slug)), 'upgrade-plugin_' . $this->plugin_slug);
        
        return sprintf(
            '<div class="notice notice-warning"><p><strong>%s</strong> %s <a href="%s" class="button-primary">%s</a></p></div>',
            __('AzuraCast Song History Update Available', 'azuracast-song-history'),
            sprintf(__('Version %s is available.', 'azuracast-song-history'), $remote_version),
            $update_url,
            __('Update Now', 'azuracast-song-history')
        );
    }
}