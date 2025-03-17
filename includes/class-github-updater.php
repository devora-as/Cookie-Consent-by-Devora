<?php

/**
 * GitHub Updater Class
 *
 * Enables plugin updates from a GitHub repository.
 *
 * @package CustomCookieConsent
 * @since 1.2.0
 */

namespace CustomCookieConsent;

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

/**
 * GitHub Updater Class
 *
 * This class handles plugin updates from a GitHub repository.
 */
class GitHubUpdater
{
    /**
     * @var string
     */
    private $file;

    /**
     * @var array
     */
    private $plugin;

    /**
     * @var string
     */
    private $basename;

    /**
     * @var string
     */
    private $active;

    /**
     * @var string
     */
    private $username;

    /**
     * @var string
     */
    private $repository;

    /**
     * @var string
     */
    private $github_api_url;

    /**
     * @var object|null
     */
    private $github_response;

    /**
     * @var string|null
     */
    private $authorization_header;

    /**
     * @var string
     */
    private $plugin_slug;

    /**
     * @var string
     */
    private $primary_branch;

    /**
     * Class constructor.
     *
     * @param string $file The plugin file
     */
    public function __construct($file)
    {
        // Set class properties
        $this->file = $file;
        $this->plugin = get_plugin_data($this->file);
        $this->basename = plugin_basename($this->file);
        $this->active = is_plugin_active($this->basename);
        $this->plugin_slug = dirname($this->basename);
        $this->primary_branch = $this->get_plugin_header('Primary Branch') ?: 'main';

        // Get GitHub repository info from plugin headers
        $github_uri = $this->get_plugin_header('GitHub Plugin URI');

        if (!empty($github_uri)) {
            // Extract username and repo from GitHub URI
            if (preg_match('/https:\/\/github.com\/([^\/]+)\/([^\/]+)/i', $github_uri, $matches)) {
                $this->username = $matches[1];
                $this->repository = $matches[2];
            } else {
                // Alternative format: username/repository
                $parts = explode('/', $github_uri);
                if (count($parts) === 2) {
                    $this->username = $parts[0];
                    $this->repository = $parts[1];
                }
            }

            // Set GitHub API URL
            $this->github_api_url = 'https://api.github.com/repos/' . $this->username . '/' . $this->repository . '/releases/latest';

            // Set optional authorization header (for higher rate limits)
            $github_token = defined('GITHUB_ACCESS_TOKEN') ? constant('GITHUB_ACCESS_TOKEN') : null;
            $this->authorization_header = $github_token ? "Authorization: token {$github_token}" : null;

            // Initialize updater
            add_filter('pre_set_site_transient_update_plugins', [$this, 'check_update']);
            add_filter('plugins_api', [$this, 'plugin_info'], 20, 3);
            add_filter('upgrader_post_install', [$this, 'post_install'], 10, 3);
        }
    }

    /**
     * Get a specific plugin header.
     *
     * @param string $header The header to get
     * @return string The header value
     */
    private function get_plugin_header($header)
    {
        if (!function_exists('get_file_data')) {
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
        }

        $plugin_data = get_file_data(
            $this->file,
            array(
                $header => $header,
            )
        );

        return isset($plugin_data[$header]) ? $plugin_data[$header] : '';
    }

    /**
     * Check for plugin updates.
     *
     * @param object $transient The WordPress update transient
     * @return object Updated transient
     */
    public function check_update($transient)
    {
        if (empty($transient->checked)) {
            return $transient;
        }

        // Clear our transient when WP updates its transient
        delete_transient('github_' . $this->plugin_slug . '_update_data');

        // Get update information from GitHub
        $this->github_response = $this->get_update_data();

        // If there is an update, modify the transient
        if ($this->is_update_available() && is_object($this->github_response)) {
            $current_version = $this->plugin['Version'];
            $new_version = $this->github_response->tag_name;

            // Format version number (remove 'v' prefix if present)
            $new_version = ltrim($new_version, 'v');

            // Only proceed if the GitHub version is newer
            if (version_compare($new_version, $current_version, '>')) {
                // Create package URL
                $package_url = $this->get_package_url();

                // Ensure we have a valid package URL
                if (!empty($package_url)) {
                    $plugin_data = array(
                        'id'            => $this->basename,
                        'slug'          => $this->plugin_slug,
                        'plugin'        => $this->basename,
                        'new_version'   => $new_version,
                        'url'           => $this->plugin['PluginURI'],
                        'package'       => $package_url,
                        'icons'         => array(),
                        'banners'       => array(),
                        'banners_rtl'   => array(),
                        'tested'        => '',
                        'requires_php'  => '',
                        'compatibility' => new \stdClass(),
                    );

                    $transient->response[$this->basename] = (object) $plugin_data;
                }
            }
        }

        return $transient;
    }

    /**
     * Get update data from GitHub or transient.
     *
     * @return object|null The update data
     */
    private function get_update_data()
    {
        // Check for cached data
        $cached_data = get_transient('github_' . $this->plugin_slug . '_update_data');
        if ($cached_data !== false) {
            return $cached_data;
        }

        // Fetch update data from GitHub
        $response = wp_remote_get($this->github_api_url, array(
            'timeout'     => 10,
            'headers'     => array(
                'Accept'        => 'application/json',
                'Authorization' => $this->authorization_header,
                'User-Agent'    => 'WordPress/' . get_bloginfo('version') . '; ' . get_bloginfo('url'),
            ),
        ));

        // Handle errors
        if (is_wp_error($response) || 200 !== wp_remote_retrieve_response_code($response)) {
            return null;
        }

        // Decode response
        $response_body = wp_remote_retrieve_body($response);
        $data = json_decode($response_body);

        // Cache response for 6 hours
        if (is_object($data)) {
            set_transient('github_' . $this->plugin_slug . '_update_data', $data, 6 * HOUR_IN_SECONDS);
        }

        return $data;
    }

    /**
     * Check if an update is available.
     *
     * @return bool Whether an update is available
     */
    private function is_update_available()
    {
        if (!is_object($this->github_response)) {
            return false;
        }

        $current_version = $this->plugin['Version'];
        $new_version = ltrim($this->github_response->tag_name, 'v');

        return version_compare($new_version, $current_version, '>');
    }

    /**
     * Get the package URL for the update.
     *
     * @return string The package URL
     */
    private function get_package_url()
    {
        // Check if we should use a specific asset
        $use_asset = $this->get_plugin_header('Release Asset') === 'true';

        if ($use_asset && !empty($this->github_response->assets)) {
            // Look for a zip asset
            foreach ($this->github_response->assets as $asset) {
                if (strpos($asset->name, '.zip') !== false) {
                    return $asset->browser_download_url;
                }
            }
        }

        // Fallback to the source code zip
        if (isset($this->github_response->zipball_url)) {
            return $this->github_response->zipball_url;
        }

        return '';
    }

    /**
     * Provide plugin information for update API.
     *
     * @param object|false $result The result object or false
     * @param string       $action The API action being performed
     * @param object       $args   Plugin arguments
     * @return object|false The plugin info or false
     */
    public function plugin_info($result, $action, $args)
    {
        // Only proceed if this is our plugin
        if (isset($args->slug) && $args->slug === $this->plugin_slug && $action === 'plugin_information') {
            // Get update information from GitHub
            $this->github_response = $this->get_update_data();

            if (is_object($this->github_response)) {
                $plugin_info = array(
                    'name'              => $this->plugin['Name'],
                    'slug'              => $this->plugin_slug,
                    'version'           => ltrim($this->github_response->tag_name, 'v'),
                    'author'            => $this->plugin['Author'],
                    'author_profile'    => 'https://github.com/' . $this->username,
                    'last_updated'      => $this->github_response->published_at,
                    'homepage'          => $this->plugin['PluginURI'],
                    'short_description' => $this->plugin['Description'],
                    'sections'          => array(
                        'description'   => $this->plugin['Description'],
                        'changelog'     => nl2br($this->github_response->body),
                    ),
                    'download_link'     => $this->get_package_url(),
                    'requires'          => '5.0',
                    'tested'            => get_bloginfo('version'),
                    'requires_php'      => '7.0',
                    'compatibility'     => [],
                );

                return (object) $plugin_info;
            }
        }

        return $result;
    }

    /**
     * Handle post-installation tasks.
     *
     * @param bool  $response   Installation response
     * @param array $hook_extra Extra information about the installation
     * @param array $result     Installation result data
     * @return array The result
     */
    public function post_install($response, $hook_extra, $result)
    {
        // Only proceed if this is our plugin
        if (isset($hook_extra['plugin']) && $hook_extra['plugin'] === $this->basename) {
            global $wp_filesystem;

            // Ensure required functions are loaded
            if (!function_exists('activate_plugin')) {
                require_once ABSPATH . 'wp-admin/includes/plugin.php';
            }

            // Re-activate plugin if it was active before the update
            if ($this->active) {
                activate_plugin($this->basename);
            }
        }

        return $result;
    }

    /**
     * Initialize the GitHub Updater.
     *
     * @param string $file The plugin file
     * @return void
     */
    public static function init($file)
    {
        new self($file);
    }
}

// Initialize the updater
add_action('admin_init', function () {
    // Ensure the plugin file path is correct
    $plugin_file = trailingslashit(WP_PLUGIN_DIR) . 'custom-cookie-consent/cookie-consent.php';

    if (file_exists($plugin_file)) {
        GitHubUpdater::init($plugin_file);
    }
});
