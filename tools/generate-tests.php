<?php

/**
 * Test Generator for WordPress Plugins
 *
 * This script automatically generates PHPUnit test files for PHP files in the plugin.
 * It analyzes the file structure, class methods, and hooks to create appropriate tests.
 *
 * @package Custom_Cookie_Consent
 */

if (PHP_SAPI !== 'cli') {
    die('This script can only be run from the command line.');
}

// Set error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Check if a specific file was provided
if (empty($argv[1])) {
    echo "Usage: php generate-tests.php [file_path]\n";
    echo "Example: php generate-tests.php includes/class-cookie-consent.php\n";
    die();
}

$file_path = $argv[1];

if (!file_exists($file_path)) {
    die("Error: File '$file_path' not found.\n");
}

echo "WordPress Test Generator\n";
echo "=======================\n\n";
echo "Generating tests for: $file_path\n";

// Parse the file to extract class information
$file_content = file_get_contents($file_path);
$namespace = extract_namespace($file_content);
$class_name = extract_class_name($file_content);
$methods = extract_methods($file_content);
$hooks = extract_hooks($file_content);

if (empty($class_name)) {
    echo "No class found in the file. Generating functional tests instead.\n";
    generate_functional_test($file_path, $file_content);
    exit(0);
}

echo "Found class: $class_name\n";
if (!empty($namespace)) {
    echo "Namespace: $namespace\n";
}
echo "Methods found: " . count($methods) . "\n";
echo "Hooks found: " . count($hooks) . "\n\n";

// Generate the test file content
$test_content = generate_test_content($namespace, $class_name, $methods, $hooks, $file_path);

// Determine the test file path
$file_name = basename($file_path);
$test_file_name = 'test-' . $file_name;
$test_file_path = 'tests/' . $test_file_name;

// Check if the test file already exists
if (file_exists($test_file_path)) {
    echo "Test file already exists at: $test_file_path\n";
    echo "Do you want to overwrite it? (y/n): ";
    $handle = fopen("php://stdin", "r");
    $line = fgets($handle);
    if (strtolower(trim($line)) !== 'y') {
        echo "Test generation cancelled.\n";
        exit(0);
    }
    fclose($handle);
}

// Write the test file
if (file_put_contents($test_file_path, $test_content)) {
    echo "Test file generated successfully at: $test_file_path\n";
} else {
    echo "Error: Could not write to file: $test_file_path\n";
}

/**
 * Extract the namespace from the file content
 *
 * @param string $content The file content.
 * @return string The namespace or empty string.
 */
function extract_namespace($content)
{
    if (preg_match('/namespace\s+([^;]+);/', $content, $matches)) {
        return trim($matches[1]);
    }
    return '';
}

/**
 * Extract the class name from the file content
 *
 * @param string $content The file content.
 * @return string The class name or empty string.
 */
function extract_class_name($content)
{
    if (preg_match('/class\s+(\w+)(?:\s+extends\s+\w+)?(?:\s+implements\s+[\w,\s]+)?/', $content, $matches)) {
        return trim($matches[1]);
    }
    return '';
}

/**
 * Extract methods from the file content
 *
 * @param string $content The file content.
 * @return array Array of method names and visibilities.
 */
function extract_methods($content)
{
    $methods = [];
    preg_match_all('/(?:public|protected|private)\s+function\s+(\w+)\s*\(/', $content, $matches, PREG_SET_ORDER);

    foreach ($matches as $match) {
        if (strpos($match[0], 'public') !== false) {
            $visibility = 'public';
        } elseif (strpos($match[0], 'protected') !== false) {
            $visibility = 'protected';
        } else {
            $visibility = 'private';
        }

        $methods[] = [
            'name' => $match[1],
            'visibility' => $visibility
        ];
    }

    return $methods;
}

/**
 * Extract hooks (actions and filters) from the file content
 *
 * @param string $content The file content.
 * @return array Array of hooks (type, name, callback).
 */
function extract_hooks($content)
{
    $hooks = [];

    // Extract add_action calls
    preg_match_all('/add_action\s*\(\s*[\'"]([\w\-\/]+)[\'"],\s*(?:\[\s*\$this,\s*[\'"](\w+)[\'"]\s*\]|[\'"](\w+)[\'"])/', $content, $action_matches, PREG_SET_ORDER);

    foreach ($action_matches as $match) {
        $hook_name = $match[1];
        $callback = !empty($match[2]) ? $match[2] : (!empty($match[3]) ? $match[3] : '');

        if (!empty($callback)) {
            $hooks[] = [
                'type' => 'action',
                'name' => $hook_name,
                'callback' => $callback
            ];
        }
    }

    // Extract add_filter calls
    preg_match_all('/add_filter\s*\(\s*[\'"]([\w\-\/]+)[\'"],\s*(?:\[\s*\$this,\s*[\'"](\w+)[\'"]\s*\]|[\'"](\w+)[\'"])/', $content, $filter_matches, PREG_SET_ORDER);

    foreach ($filter_matches as $match) {
        $hook_name = $match[1];
        $callback = !empty($match[2]) ? $match[2] : (!empty($match[3]) ? $match[3] : '');

        if (!empty($callback)) {
            $hooks[] = [
                'type' => 'filter',
                'name' => $hook_name,
                'callback' => $callback
            ];
        }
    }

    return $hooks;
}

/**
 * Generate test content for a class
 *
 * @param string $namespace The namespace of the class.
 * @param string $class_name The class name.
 * @param array  $methods Array of methods.
 * @param array  $hooks Array of hooks.
 * @param string $file_path The file path.
 * @return string The generated test content.
 */
function generate_test_content($namespace, $class_name, $methods, $hooks, $file_path)
{
    $full_class_name = !empty($namespace) ? "$namespace\\$class_name" : $class_name;
    $test_class_name = "Test" . preg_replace('/^class-/', '', $class_name);
    $test_class_name = str_replace('-', '_', $test_class_name);

    $content = "<?php\n\n";
    $content .= "/**\n";
    $content .= " * Class $test_class_name\n";
    $content .= " *\n";
    $content .= " * @package Custom_Cookie_Consent\n";
    $content .= " */\n\n";

    if (!empty($namespace)) {
        $content .= "use $namespace\\$class_name;\n\n";
    }

    $content .= "class $test_class_name extends WP_UnitTestCase\n{\n";

    // Setup method if needed
    $content .= "    /**\n";
    $content .= "     * Set up test environment\n";
    $content .= "     */\n";
    $content .= "    public function set_up() {\n";
    $content .= "        parent::set_up();\n";

    // Add any necessary setup code
    if (strpos($file_path, 'class-consent-logger.php') !== false) {
        $content .= "        // Ensure the consent logs table exists\n";
        $content .= "        \$logger = new \\CustomCookieConsent\\Consent_Logger();\n";
        $content .= "        \$logger->create_database_table();\n";
    }

    $content .= "    }\n\n";

    // Teardown method if needed
    $content .= "    /**\n";
    $content .= "     * Tear down test environment\n";
    $content .= "     */\n";
    $content .= "    public function tear_down() {\n";
    $content .= "        parent::tear_down();\n";
    $content .= "        // Add any cleanup needed\n";
    $content .= "    }\n\n";

    // Add tests for each public method
    foreach ($methods as $method) {
        if ($method['visibility'] === 'public' && !in_array($method['name'], ['__construct', 'set_up', 'tear_down'])) {
            $content .= generate_method_test($method['name'], $full_class_name, $file_path);
        }
    }

    // Add tests for hooks
    if (!empty($hooks)) {
        $content .= "    /**\n";
        $content .= "     * Test hooks are registered\n";
        $content .= "     */\n";
        $content .= "    public function test_hooks_are_registered() {\n";

        foreach ($hooks as $hook) {
            $content .= "        \$this->assertNotFalse(has_" . $hook['type'] . "('{$hook['name']}', ['{$full_class_name}', '{$hook['callback']}']));\n";
        }

        $content .= "    }\n\n";
    }

    $content .= "}\n";

    return $content;
}

/**
 * Generate a test for a specific method
 *
 * @param string $method_name The method name.
 * @param string $full_class_name The full class name.
 * @param string $file_path The file path.
 * @return string The generated test method.
 */
function generate_method_test($method_name, $full_class_name, $file_path)
{
    $test = "    /**\n";
    $test .= "     * Test {$method_name}\n";
    $test .= "     */\n";
    $test .= "    public function test_{$method_name}() {\n";

    // Special handling for common method names
    if ($method_name === 'get_instance' || $method_name === 'instance') {
        $test .= "        \$instance = {$full_class_name}::{$method_name}();\n";
        $test .= "        \$this->assertInstanceOf({$full_class_name}::class, \$instance);\n\n";
        $test .= "        // Test singleton pattern\n";
        $test .= "        \$second_instance = {$full_class_name}::{$method_name}();\n";
        $test .= "        \$this->assertSame(\$instance, \$second_instance);\n";
    } elseif (strpos($method_name, 'get_') === 0) {
        $test .= "        \$instance = new {$full_class_name}();\n";
        $test .= "        \$result = \$instance->{$method_name}();\n";
        $test .= "        \$this->assertNotNull(\$result);\n";
        $test .= "        // Add more specific assertions here\n";
    } elseif (strpos($method_name, 'set_') === 0) {
        $test .= "        \$instance = new {$full_class_name}();\n";
        $test .= "        \$test_value = 'test_value';\n";
        $test .= "        \$instance->{$method_name}(\$test_value);\n";
        $test .= "        // Add assertions to verify the value was set correctly\n";
    } elseif (strpos($method_name, 'create_') === 0) {
        $test .= "        \$instance = new {$full_class_name}();\n";
        $test .= "        \$result = \$instance->{$method_name}();\n";
        $test .= "        \$this->assertTrue(\$result);\n";
        $test .= "        // Add assertions to verify creation was successful\n";
    } elseif (strpos($method_name, 'delete_') === 0) {
        $test .= "        \$instance = new {$full_class_name}();\n";
        $test .= "        // Create something to delete first\n";
        $test .= "        \$result = \$instance->{$method_name}();\n";
        $test .= "        \$this->assertTrue(\$result);\n";
        $test .= "        // Add assertions to verify deletion was successful\n";
    } elseif (strpos($method_name, 'is_') === 0 || strpos($method_name, 'has_') === 0) {
        $test .= "        \$instance = new {$full_class_name}();\n";
        $test .= "        \$result = \$instance->{$method_name}();\n";
        $test .= "        \$this->assertIsBool(\$result);\n";
    } elseif ($method_name === 'export_logs_csv') {
        $test .= "        \$instance = new {$full_class_name}();\n";
        $test .= "        \$this->expectOutputRegex('/Date,IP,User ID,Categories/');\n";
        $test .= "        \$instance->{$method_name}();\n";
    } elseif ($method_name === 'table_exists') {
        $test .= "        \$instance = new {$full_class_name}();\n";
        $test .= "        \$result = \$instance->{$method_name}();\n";
        $test .= "        \$this->assertTrue(\$result);\n";
    } else {
        $test .= "        \$instance = new {$full_class_name}();\n";
        $test .= "        // Add test setup and assertions for {$method_name}\n";
        $test .= "        \$this->markTestIncomplete('This test needs to be implemented');\n";
    }

    $test .= "    }\n\n";
    return $test;
}

/**
 * Generate functional tests for files without classes
 *
 * @param string $file_path The file path.
 * @param string $file_content The file content.
 */
function generate_functional_test($file_path, $file_content)
{
    // Extract functions
    preg_match_all('/function\s+(\w+)\s*\(/', $file_content, $matches);
    $functions = $matches[1];

    echo "Functions found: " . count($functions) . "\n";

    // Generate the test file content
    $test_content = "<?php\n\n";
    $test_content .= "/**\n";
    $test_content .= " * Functional tests for " . basename($file_path) . "\n";
    $test_content .= " *\n";
    $test_content .= " * @package Custom_Cookie_Consent\n";
    $test_content .= " */\n\n";

    $test_content .= "class Test_" . str_replace(['-', '.php'], ['_', ''], basename($file_path)) . " extends WP_UnitTestCase\n{\n";

    // Add tests for each function
    foreach ($functions as $function) {
        $test_content .= "    /**\n";
        $test_content .= "     * Test {$function} function\n";
        $test_content .= "     */\n";
        $test_content .= "    public function test_{$function}() {\n";
        $test_content .= "        // Add test setup and assertions for {$function}\n";
        $test_content .= "        \$this->markTestIncomplete('This test needs to be implemented');\n";
        $test_content .= "    }\n\n";
    }

    $test_content .= "}\n";

    // Determine the test file path
    $file_name = basename($file_path);
    $test_file_name = 'test-' . $file_name;
    $test_file_path = 'tests/' . $test_file_name;

    // Check if the test file already exists
    if (file_exists($test_file_path)) {
        echo "Test file already exists at: $test_file_path\n";
        echo "Do you want to overwrite it? (y/n): ";
        $handle = fopen("php://stdin", "r");
        $line = fgets($handle);
        if (strtolower(trim($line)) !== 'y') {
            echo "Test generation cancelled.\n";
            exit(0);
        }
        fclose($handle);
    }

    // Write the test file
    if (file_put_contents($test_file_path, $test_content)) {
        echo "Test file generated successfully at: $test_file_path\n";
    } else {
        echo "Error: Could not write to file: $test_file_path\n";
    }
}
