<?php

/**
 * Auto-fix script for WordPress plugins
 *
 * This script automatically fixes common issues found in WordPress plugins
 * based on PHPCS, PHPMD, and WP-CLI analysis.
 *
 * @package Custom_Cookie_Consent
 */

if (PHP_SAPI !== 'cli') {
    die('This script can only be run from the command line.');
}

// Set error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Make sure we're in the right directory
$script_dir = dirname(__FILE__);
$project_dir = dirname($script_dir);
chdir($project_dir);

echo "WordPress Plugin Auto-Fix Tool\n";
echo "=============================\n\n";

// Check if necessary tools are installed
$phpcbf_locations = [
    './vendor/bin/phpcbf',
    './vendor/bin/phpcbf.bat',
    dirname(__DIR__) . '/vendor/bin/phpcbf',
    dirname(__DIR__) . '/vendor/bin/phpcbf.bat',
];

$phpcbf_found = false;
foreach ($phpcbf_locations as $location) {
    if (file_exists($location)) {
        $phpcbf_found = true;
        $phpcbf_path = $location;
        break;
    }
}

if (!$phpcbf_found) {
    echo "Warning: PHP Code Beautifier not found. Some fixes may not be applied.\n";
    $phpcbf_path = 'phpcbf';
}

// Determine if we're running on a specific file or on the entire project
$target_file = null;
if (isset($argv[1]) && file_exists($argv[1])) {
    $target_file = $argv[1];
    echo "Running auto-fix on specific file: $target_file\n\n";
    $files_to_check = [$target_file];
} else {
    // Get list of files to process
    $files_to_check = get_php_files();
    if (empty($files_to_check)) {
        die("No PHP files found in the current directory.\n");
    }

    echo "Found " . count($files_to_check) . " PHP files to process.\n\n";
}

// Capture any errors for reporting
$errors = [];
$fixes = [];

// Run PHP Code Beautifier and Fixer
echo "Running PHP Code Beautifier and Fixer...\n";
try {
    if ($target_file) {
        $command = escapeshellcmd("php $phpcbf_path --standard=WordPress $target_file");
    } else {
        $command = escapeshellcmd("php $phpcbf_path --standard=WordPress --ignore=vendor/,node_modules/,tests/ .");
    }
    $output = shell_exec($command . ' 2>&1');
    echo $output . "\n";
    $fixes[] = "Applied code style fixes with PHPCBF";
} catch (Exception $e) {
    echo "Warning: PHPCBF execution failed: " . $e->getMessage() . "\n";
}

// Run PHPCS to check for remaining issues
echo "Checking for remaining issues with PHPCS...\n";
try {
    $phpcs_locations = [
        './vendor/bin/phpcs',
        './vendor/bin/phpcs.bat',
        dirname(__DIR__) . '/vendor/bin/phpcs',
        dirname(__DIR__) . '/vendor/bin/phpcs.bat',
    ];

    $phpcs_found = false;
    foreach ($phpcs_locations as $location) {
        if (file_exists($location)) {
            $phpcs_found = true;
            $phpcs_path = $location;
            break;
        }
    }

    if (!$phpcs_found) {
        echo "Warning: PHPCS not found. Skipping code standards check.\n";
    } else {
        if ($target_file) {
            $command = escapeshellcmd("php $phpcs_path --standard=WordPress --report=summary $target_file");
        } else {
            $command = escapeshellcmd("php $phpcs_path --standard=WordPress --report=summary --ignore=vendor/,node_modules/,tests/ .");
        }
        $output = shell_exec($command . ' 2>&1');
        if (strpos($output, 'ERROR') !== false) {
            echo "Found PHPCS errors that could not be auto-fixed:\n";
            echo $output . "\n";
            $errors[] = "PHPCS found issues that need manual attention";
        } else {
            echo "No PHPCS errors found or all were fixed.\n";
        }
    }
} catch (Exception $e) {
    echo "Warning: PHPCS execution failed: " . $e->getMessage() . "\n";
}

// Fix deprecated WordPress functions
echo "Checking for deprecated WordPress functions...\n";
$deprecated_functions = [
    'get_page(' => 'get_post(',
    'is_page_template(' => 'has_block_template(',
    'wp_redirect(' => 'wp_safe_redirect(',
    'create_function(' => 'Use anonymous functions instead',
    '_deprecated_function' => 'This function is marked as deprecated',
    '_deprecated_hook' => 'This hook is marked as deprecated',
    '_deprecated_argument' => 'This argument is marked as deprecated',
    '_deprecated_file' => 'This file is marked as deprecated',
];

$files_with_deprecated = 0;
foreach ($files_to_check as $file) {
    if (!file_exists($file)) {
        continue;
    }

    $content = file_get_contents($file);
    $original_content = $content;

    foreach ($deprecated_functions as $old => $new) {
        if (strpos($content, $old) !== false) {
            echo "Found deprecated function $old in $file. Suggesting replacement: $new\n";
            $errors[] = "Deprecated function $old in $file needs manual replacement";
            $files_with_deprecated++;
        }
    }
}

if ($files_with_deprecated === 0) {
    echo "No deprecated WordPress functions found.\n";
}

// Check for accessibility issues
echo "\nChecking for accessibility issues in HTML output...\n";
$accessibility_issues = [
    '<img(?![^>]*alt=)' => 'Make sure all <img> tags have alt attributes',
    '<button(?![^>]*aria-label)(?![^>]*>[\s\S]+?<\/button>)' => 'Ensure buttons have accessible labels',
    'style="color:' => 'Check for proper color contrast ratios',
    'tabindex=["\'](?!0)(?!-1)' => 'Avoid tabindex values other than 0 and -1',
    'onclick=' => 'Consider using addEventListener instead of inline events',
    '<div(?![^>]*role=)[\s\S]*?class="[^"]*?nav' => 'Navigation divs should have role="navigation"',
    '<a(?![^>]*href=)' => 'Links should have href attributes',
    '<a[^>]*href="#"(?![^>]*role=)' => 'Empty links should have a role attribute',
];

$files_with_a11y_issues = 0;
foreach ($files_to_check as $file) {
    if (!file_exists($file)) {
        continue;
    }

    $content = file_get_contents($file);

    foreach ($accessibility_issues as $pattern => $suggestion) {
        try {
            if (preg_match('/' . $pattern . '/i', $content)) {
                echo "Possible accessibility issue in $file: $suggestion\n";
                $errors[] = "Accessibility issue in $file: $suggestion";
                $files_with_a11y_issues++;
            }
        } catch (Exception $e) {
            echo "Warning: Error checking accessibility pattern '$pattern': " . $e->getMessage() . "\n";
        }
    }
}

if ($files_with_a11y_issues === 0) {
    echo "No obvious accessibility issues found in HTML output.\n";
}

// Check for database operations without error handling
echo "\nChecking for database operations without error handling...\n";
$db_functions = [
    'wpdb->query' => 'Missing error handling for database query',
    'wpdb->get_results' => 'Missing error handling for database results',
    'wpdb->get_row' => 'Missing error handling for database row',
    'wpdb->get_var' => 'Missing error handling for database variable',
    'wpdb->insert' => 'Missing error handling for database insert',
    'wpdb->update' => 'Missing error handling for database update',
    'wpdb->delete' => 'Missing error handling for database delete',
];

$files_with_db_issues = 0;
foreach ($files_to_check as $file) {
    if (!file_exists($file)) {
        continue;
    }

    $content = file_get_contents($file);

    foreach ($db_functions as $func => $issue) {
        try {
            if (
                strpos($content, $func) !== false &&
                !preg_match('/if\s*\(\s*(?:null\s*!==|!)\s*' . preg_quote($func, '/') . '/i', $content) &&
                !preg_match('/try\s*{[^}]*' . preg_quote($func, '/') . '/i', $content)
            ) {
                echo "Possible database issue in $file: $issue\n";
                $errors[] = "Database operation in $file may need error handling";
                $files_with_db_issues++;
            }
        } catch (Exception $e) {
            echo "Warning: Error checking database pattern '$func': " . $e->getMessage() . "\n";
        }
    }
}

if ($files_with_db_issues === 0) {
    echo "No obvious database operations without error handling found.\n";
}

// Check for security issues
echo "\nChecking for security issues...\n";
$security_issues = [
    '\$_GET\[[\'"][^\'"]+[\'"]\](?![^;]*(?:sanitize_|wp_kses|esc_))' => 'Unsanitized $_GET data',
    '\$_POST\[[\'"][^\'"]+[\'"]\](?![^;]*(?:sanitize_|wp_kses|esc_))' => 'Unsanitized $_POST data',
    '\$_REQUEST\[[\'"][^\'"]+[\'"]\](?![^;]*(?:sanitize_|wp_kses|esc_))' => 'Unsanitized $_REQUEST data',
    '\$_SERVER\[[\'"][^\'"]+[\'"]\](?![^;]*(?:sanitize_|wp_kses|esc_))' => 'Unsanitized $_SERVER data',
    'wp_signon\((?![^)]*nonce)' => 'wp_signon call without nonce verification',
    'add_menu_page\([^,)]*,[^,)]*,[^,)]*,[^,)]*,[^,)]*,(?![^,)]*capability)' => 'Menu page without capability check',
];

$files_with_security_issues = 0;
foreach ($files_to_check as $file) {
    if (!file_exists($file)) {
        continue;
    }

    $content = file_get_contents($file);

    foreach ($security_issues as $pattern => $issue) {
        try {
            if (preg_match('/' . $pattern . '/i', $content)) {
                echo "Possible security issue in $file: $issue\n";
                $errors[] = "Security issue in $file: $issue";
                $files_with_security_issues++;
            }
        } catch (Exception $e) {
            echo "Warning: Error checking security pattern '$pattern': " . $e->getMessage() . "\n";
        }
    }
}

if ($files_with_security_issues === 0) {
    echo "No obvious security issues found.\n";
}

// Summary
echo "\n=== Auto-fix Summary ===\n";
if (! empty($fixes)) {
    echo "\nApplied fixes:\n";
    foreach ($fixes as $fix) {
        echo "- $fix\n";
    }
}

if (! empty($errors)) {
    echo "\nIssues requiring attention:\n";
    foreach ($errors as $error) {
        echo "- $error\n";
    }
    echo "\nPlease fix these issues manually.\n";
} else {
    echo "\nNo issues requiring manual attention were found.\n";
}

echo "\nAuto-fix process completed! Please review the changes before committing.\n";

// Exit with appropriate code for automation
exit(empty($errors) ? 0 : 1);

/**
 * Get all PHP files in the current directory and subdirectories
 *
 * @return array Array of PHP file paths
 */
function get_php_files()
{
    try {
        $directory_iterator = new RecursiveDirectoryIterator('.');
        $iterator = new RecursiveIteratorIterator($directory_iterator);
        $files = [];

        foreach ($iterator as $file) {
            if (
                $file->getExtension() === 'php' &&
                ! preg_match('/(vendor|node_modules|tests)/', $file->getPathname())
            ) {
                $files[] = $file->getPathname();
            }
        }

        return $files;
    } catch (Exception $e) {
        echo "Error scanning files: " . $e->getMessage() . "\n";
        return [];
    }
}
