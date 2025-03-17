<?php

/**
 * GitHub Updater Verification Tool
 * 
 * This script helps verify that the GitHub updater is correctly configured.
 * Run this script from your WordPress admin area to check the GitHub connection.
 * 
 * Usage:
 * 1. Upload this file to your wp-content/plugins/custom-cookie-consent/tools/ directory
 * 2. Visit https://your-site.com/wp-content/plugins/custom-cookie-consent/tools/verify-github-updater.php
 * 
 * Note: This file should be deleted after verification for security.
 */

// Only run in WordPress environment
if (!defined('ABSPATH')) {
    // Try to load WordPress
    $wp_load_file = realpath(dirname(__FILE__) . '/../../../../wp-load.php');
    if (file_exists($wp_load_file)) {
        require_once $wp_load_file;
    } else {
        die('WordPress environment not found. Cannot run verification.');
    }
}

// Check if user is logged in and has sufficient permissions
if (!current_user_can('manage_options')) {
    wp_die('You do not have sufficient permissions to access this page.');
}

// Include necessary WordPress files
require_once ABSPATH . 'wp-admin/includes/plugin.php';

// Set headers to display output as HTML
header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>GitHub Updater Verification</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
        }

        h1 {
            color: #32373c;
            border-bottom: 1px solid #eee;
            padding-bottom: 10px;
        }

        .result {
            background-color: #f9f9f9;
            border-left: 4px solid #ccc;
            padding: 10px 15px;
            margin: 15px 0;
            overflow-x: auto;
        }

        .success {
            border-left-color: #46b450;
        }

        .warning {
            border-left-color: #ffb900;
        }

        .error {
            border-left-color: #dc3232;
        }

        code {
            background: #f0f0f0;
            padding: 2px 5px;
            border-radius: 3px;
        }

        table {
            border-collapse: collapse;
            width: 100%;
            margin: 15px 0;
        }

        th,
        td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }

        th {
            background-color: #f4f4f4;
        }
    </style>
</head>

<body>
    <h1>GitHub Updater Verification</h1>

    <?php
    // 1. Check if the plugin exists
    $plugin_file = trailingslashit(WP_PLUGIN_DIR) . 'custom-cookie-consent/cookie-consent.php';
    if (!file_exists($plugin_file)) {
        echo '<div class="result error"><strong>Error:</strong> Plugin file not found at expected location: ' . esc_html($plugin_file) . '</div>';
        exit;
    }
    echo '<div class="result success"><strong>Success:</strong> Plugin file found at: ' . esc_html($plugin_file) . '</div>';

    // 2. Check if the plugin is activated
    $plugin_slug = 'custom-cookie-consent/cookie-consent.php';
    if (!is_plugin_active($plugin_slug)) {
        echo '<div class="result warning"><strong>Warning:</strong> Plugin is not active. Activate it for updates to work properly.</div>';
    } else {
        echo '<div class="result success"><strong>Success:</strong> Plugin is active.</div>';
    }

    // 3. Check plugin header for GitHub information
    $plugin_data = get_plugin_data($plugin_file);
    echo '<h2>Plugin Information</h2>';
    echo '<table>';
    echo '<tr><th>Name</th><td>' . esc_html($plugin_data['Name']) . '</td></tr>';
    echo '<tr><th>Version</th><td>' . esc_html($plugin_data['Version']) . '</td></tr>';
    echo '<tr><th>Author</th><td>' . wp_kses_post($plugin_data['Author']) . '</td></tr>';
    echo '</table>';

    // Get GitHub specific headers
    $github_headers = array(
        'GitHub Plugin URI' => 'GitHub Plugin URI',
        'GitHub Plugin URI (HTTPS)' => 'GitHub Plugin URI',
        'Primary Branch' => 'Primary Branch',
        'Release Asset' => 'Release Asset'
    );

    $github_data = get_file_data($plugin_file, $github_headers);

    echo '<h2>GitHub Integration Headers</h2>';
    echo '<table>';
    foreach ($github_data as $key => $value) {
        $status_class = !empty($value) ? 'success' : 'error';
        echo '<tr class="' . $status_class . '">';
        echo '<th>' . esc_html($key) . '</th>';
        echo '<td>' . esc_html($value) . '</td>';
        echo '</tr>';
    }
    echo '</table>';

    // 4. Check if GitHub updater class exists
    $updater_file = trailingslashit(WP_PLUGIN_DIR) . 'custom-cookie-consent/includes/class-github-updater.php';
    if (!file_exists($updater_file)) {
        echo '<div class="result error"><strong>Error:</strong> GitHub Updater class file not found at: ' . esc_html($updater_file) . '</div>';
    } else {
        echo '<div class="result success"><strong>Success:</strong> GitHub Updater class file found.</div>';

        // Load the updater class if not already loaded
        if (!class_exists('\\CustomCookieConsent\\GitHubUpdater')) {
            require_once $updater_file;

            if (class_exists('\\CustomCookieConsent\\GitHubUpdater')) {
                echo '<div class="result success"><strong>Success:</strong> GitHub Updater class loaded successfully.</div>';
            } else {
                echo '<div class="result error"><strong>Error:</strong> Failed to load GitHub Updater class.</div>';
            }
        } else {
            echo '<div class="result success"><strong>Success:</strong> GitHub Updater class is already loaded.</div>';
        }
    }

    // 5. Check GitHub connectivity if we have a URI
    $github_uri = $github_data['GitHub Plugin URI'];
    if (!empty($github_uri)) {
        // Extract username and repository
        $repo_parts = array();
        if (preg_match('/https:\/\/github.com\/([^\/]+)\/([^\/]+)/i', $github_uri, $matches)) {
            $repo_parts['username'] = $matches[1];
            $repo_parts['repository'] = $matches[2];
        } else {
            $parts = explode('/', $github_uri);
            if (count($parts) === 2) {
                $repo_parts['username'] = $parts[0];
                $repo_parts['repository'] = $parts[1];
            }
        }

        if (!empty($repo_parts)) {
            echo '<h2>GitHub Repository Information</h2>';
            echo '<table>';
            echo '<tr><th>Username</th><td>' . esc_html($repo_parts['username']) . '</td></tr>';
            echo '<tr><th>Repository</th><td>' . esc_html($repo_parts['repository']) . '</td></tr>';
            echo '</table>';

            // Try to connect to GitHub API
            $api_url = 'https://api.github.com/repos/' . $repo_parts['username'] . '/' . $repo_parts['repository'];

            // If we have a GitHub token defined, use it
            $headers = array('Accept' => 'application/json');
            if (defined('GITHUB_ACCESS_TOKEN') && constant('GITHUB_ACCESS_TOKEN')) {
                $headers['Authorization'] = 'token ' . constant('GITHUB_ACCESS_TOKEN');
                echo '<div class="result success"><strong>Note:</strong> Using defined GITHUB_ACCESS_TOKEN for authentication.</div>';
            }

            $response = wp_remote_get($api_url, array(
                'timeout' => 10,
                'headers' => $headers,
                'user-agent' => 'WordPress/' . get_bloginfo('version') . '; ' . get_bloginfo('url')
            ));

            if (is_wp_error($response)) {
                echo '<div class="result error"><strong>Error:</strong> Failed to connect to GitHub: ' . esc_html($response->get_error_message()) . '</div>';
            } else {
                $status_code = wp_remote_retrieve_response_code($response);
                $body = wp_remote_retrieve_body($response);
                $repo_data = json_decode($body, true);

                if ($status_code === 200 && !empty($repo_data)) {
                    echo '<div class="result success"><strong>Success:</strong> Connected to GitHub repository successfully.</div>';

                    echo '<h2>Repository Details</h2>';
                    echo '<table>';
                    echo '<tr><th>Full Name</th><td>' . esc_html($repo_data['full_name']) . '</td></tr>';
                    echo '<tr><th>Description</th><td>' . esc_html($repo_data['description']) . '</td></tr>';
                    echo '<tr><th>Default Branch</th><td>' . esc_html($repo_data['default_branch']) . '</td></tr>';
                    if (isset($repo_data['private'])) {
                        echo '<tr><th>Private</th><td>' . ($repo_data['private'] ? 'Yes' : 'No') . '</td></tr>';
                    }
                    echo '</table>';

                    // Check releases
                    $releases_url = $api_url . '/releases';
                    $releases_response = wp_remote_get($releases_url, array(
                        'timeout' => 10,
                        'headers' => $headers,
                        'user-agent' => 'WordPress/' . get_bloginfo('version') . '; ' . get_bloginfo('url')
                    ));

                    if (!is_wp_error($releases_response) && wp_remote_retrieve_response_code($releases_response) === 200) {
                        $releases = json_decode(wp_remote_retrieve_body($releases_response), true);

                        if (!empty($releases)) {
                            echo '<h2>Available Releases</h2>';
                            echo '<table>';
                            echo '<tr><th>Tag</th><th>Name</th><th>Published</th><th>Assets</th></tr>';

                            foreach ($releases as $release) {
                                echo '<tr>';
                                echo '<td>' . esc_html($release['tag_name']) . '</td>';
                                echo '<td>' . esc_html($release['name']) . '</td>';
                                echo '<td>' . esc_html(date('Y-m-d H:i', strtotime($release['published_at']))) . '</td>';
                                echo '<td>' . count($release['assets']) . '</td>';
                                echo '</tr>';
                            }

                            echo '</table>';

                            // Check if any release matches the current plugin version
                            $current_version = $plugin_data['Version'];
                            $found_matching_version = false;

                            foreach ($releases as $release) {
                                $release_version = ltrim($release['tag_name'], 'v');
                                if ($release_version === $current_version) {
                                    $found_matching_version = true;
                                    break;
                                }
                            }

                            if ($found_matching_version) {
                                echo '<div class="result success"><strong>Success:</strong> Found a release matching the current plugin version (' . esc_html($current_version) . ').</div>';
                            } else {
                                echo '<div class="result warning"><strong>Warning:</strong> No release found with tag "' . esc_html($current_version) . '" or "v' . esc_html($current_version) . '". Updates might not work correctly.</div>';
                            }
                        } else {
                            echo '<div class="result warning"><strong>Warning:</strong> No releases found. Create a release on GitHub to enable updates.</div>';
                        }
                    } else {
                        echo '<div class="result error"><strong>Error:</strong> Failed to fetch releases information from GitHub.</div>';
                    }
                } else {
                    echo '<div class="result error"><strong>Error:</strong> Failed to connect to GitHub repository. Status code: ' . esc_html($status_code) . '</div>';
                    if (!empty($repo_data) && isset($repo_data['message'])) {
                        echo '<div class="result error"><strong>GitHub Error:</strong> ' . esc_html($repo_data['message']) . '</div>';
                    }
                }
            }
        } else {
            echo '<div class="result error"><strong>Error:</strong> Could not parse GitHub repository information from URI: ' . esc_html($github_uri) . '</div>';
        }
    } else {
        echo '<div class="result error"><strong>Error:</strong> GitHub Plugin URI not found in plugin header. Add it to enable GitHub updates.</div>';
    }

    // 6. Display debugging information
    echo '<h2>WordPress Environment</h2>';
    echo '<table>';
    echo '<tr><th>WordPress Version</th><td>' . esc_html(get_bloginfo('version')) . '</td></tr>';
    echo '<tr><th>PHP Version</th><td>' . esc_html(phpversion()) . '</td></tr>';
    echo '<tr><th>WP_DEBUG</th><td>' . (defined('WP_DEBUG') && WP_DEBUG ? 'Enabled' : 'Disabled') . '</td></tr>';
    echo '<tr><th>WP_DEBUG_LOG</th><td>' . (defined('WP_DEBUG_LOG') && WP_DEBUG_LOG ? 'Enabled' : 'Disabled') . '</td></tr>';
    echo '</table>';

    // Final recommendations
    echo '<h2>Recommendations</h2>';
    echo '<ol>';
    if (empty($github_data['GitHub Plugin URI'])) {
        echo '<li>Add <code>GitHub Plugin URI: YOUR_USERNAME/custom-cookie-consent</code> to your plugin header.</li>';
    }

    if (!empty($github_data['GitHub Plugin URI']) && strpos($github_data['GitHub Plugin URI'], 'GITHUB_USERNAME') !== false) {
        echo '<li>Replace <code>GITHUB_USERNAME</code> with your actual GitHub username in the plugin header.</li>';
    }

    if (empty($github_data['Primary Branch'])) {
        echo '<li>Add <code>Primary Branch: main</code> (or your default branch name) to your plugin header.</li>';
    }

    if (!is_plugin_active($plugin_slug)) {
        echo '<li>Activate the plugin to enable updates.</li>';
    }

    echo '<li>Ensure your GitHub repository has at least one release with a tag that matches your plugin version.</li>';
    echo '<li>Delete this verification script after completing the setup for security.</li>';
    echo '</ol>';
    ?>

    <p><strong>Security Notice:</strong> Delete this verification file after you've completed testing!</p>
</body>

</html>
<?php
// End of file 