#!/bin/bash

# Enhanced file watcher script with error tracking and cleanup
# It uses a basic polling approach instead of fswatch
# Features:
# - Cleans up errors once they're fixed
# - Resets error log on git commits
# - Maintains a clean output interface

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[0;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

echo -e "${BLUE}Starting enhanced file watcher...${NC}"
echo -e "${BLUE}===============================${NC}"
echo -e "Press ${YELLOW}Ctrl+C${NC} to stop."

# Define paths
TEMP_DIR="/tmp"
PROJECT_DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )/.." && pwd )"
WP_CLI="${PROJECT_DIR}/wp-cli.phar"
ERROR_LOG="${TEMP_DIR}/php_error_log.txt"
ERROR_HISTORY="${TEMP_DIR}/php_error_history.txt"

# Clear error logs initially
> "${ERROR_LOG}"
> "${ERROR_HISTORY}"

# Initialize global test results
GLOBAL_TEST_RESULTS="${TEMP_DIR}/global_test_results.txt"
> "${GLOBAL_TEST_RESULTS}"

# Set PHP path directly to Local by Flywheel instance
LOCAL_PHP_PATH="/Users/christian/Library/Application Support/Local/lightning-services/php-8.0.30+0/bin/darwin-arm64/bin/php"

# Check if the specific PHP exists, otherwise fall back to alternatives
if [ -f "${LOCAL_PHP_PATH}" ]; then
    PHP_CMD="${LOCAL_PHP_PATH}"
    echo -e "${GREEN}Using Local by Flywheel PHP: ${PHP_CMD}${NC}"
elif [ -f "/Applications/Local/resources/lightning-services/php-*/bin/php" ]; then
    # Use wildcard to match any PHP version installed by Local
    PHP_CMD=$(ls -1 /Applications/Local/resources/lightning-services/php-*/bin/php | sort -r | head -1)
    echo -e "${GREEN}Found Local by Flywheel PHP: ${PHP_CMD}${NC}"
elif [ -f "/Users/Shared/Local Sites/bin/php" ]; then
    # Alternative location for Local by Flywheel
    PHP_CMD="/Users/Shared/Local Sites/bin/php"
    echo -e "${GREEN}Found Local by Flywheel PHP: ${PHP_CMD}${NC}"
elif command -v php &> /dev/null; then
    PHP_CMD="php"
elif [ -f "/usr/bin/php" ]; then
    PHP_CMD="/usr/bin/php"
elif [ -f "/usr/local/bin/php" ]; then
    PHP_CMD="/usr/local/bin/php"
elif [ -f "/opt/homebrew/bin/php" ]; then
    PHP_CMD="/opt/homebrew/bin/php"
else
    # Try to find PHP with mdfind (on macOS)
    if command -v mdfind &> /dev/null; then
        PHP_PATH=$(mdfind -name 'php' | grep -v 'Dropbox' | grep '/bin/php$' | head -1)
        if [ ! -z "${PHP_PATH}" ]; then
            PHP_CMD="${PHP_PATH}"
        fi
    fi
    
    # If PHP is still not found, use the path from phpinfo
    if [ -z "${PHP_CMD}" ]; then
        echo -e "${YELLOW}Trying to find PHP using alternative methods...${NC}"
        PHPINFO=$(php -i 2>/dev/null | grep 'PHP Binary' | awk '{print $4}')
        if [ ! -z "${PHPINFO}" ] && [[ "${PHPINFO}" != *"Dropbox"* ]]; then
            PHP_CMD="${PHPINFO}"
        else
            # Last resort - use env to run with the current shell's environment
            PHP_CMD="env php"
        fi
    fi
fi

echo -e "${GREEN}Using PHP: ${PHP_CMD}${NC}"

# Check if wp-cli.phar exists
if [ ! -f "${WP_CLI}" ]; then
    echo -e "${YELLOW}WP-CLI not found at ${WP_CLI}. Trying to use globally installed wp command.${NC}"
    if command -v wp &> /dev/null; then
        WP_CLI="wp"
    else
        echo -e "${YELLOW}Neither wp-cli.phar nor global wp command found. Some functionality might be limited.${NC}"
    fi
fi

# Function to ensure path is absolute
function ensure_absolute_path() {
    local path="$1"
    if [[ "$path" != /* ]]; then
        # If not absolute, prepend PROJECT_DIR
        path="${PROJECT_DIR}/${path}"
    fi
    
    # Clean up any double slashes
    path=$(echo "$path" | sed 's|//|/|g')
    echo "$path"
}

# Function to check for git commits
function check_for_commits() {
    local current_commit=$(cd "${PROJECT_DIR}" && git rev-parse HEAD 2>/dev/null || echo "none")
    local last_checked_commit_file="${TEMP_DIR}/last_checked_commit.txt"
    
    # If commit file doesn't exist, create it
    if [ ! -f "${last_checked_commit_file}" ]; then
        echo "${current_commit}" > "${last_checked_commit_file}"
        return 0
    fi
    
    local last_checked_commit=$(cat "${last_checked_commit_file}")
    
    if [ "${current_commit}" != "${last_checked_commit}" ]; then
        echo "${current_commit}" > "${last_checked_commit_file}"
        return 1  # Commit has changed
    fi
    
    return 0  # No change
}

# Function to run PHPCS safely
function run_phpcs_safely() {
    local file="$1"
    local output_file="$2"
    
    # Ensure file path is absolute
    file=$(ensure_absolute_path "$file")
    
    # Create a temporary shell script to run the command
    local script_file="${TEMP_DIR}/run_phpcs_$$.sh"
    echo "#!/bin/bash" > "$script_file"
    echo "\"${PHP_CMD}\" \"${PROJECT_DIR}/vendor/bin/phpcs\" --standard=WordPress --report=summary \"${file}\"" >> "$script_file"
    chmod +x "$script_file"
    
    # Execute the script
    "$script_file" > "${output_file}" 2>&1
    local result=$?
    rm -f "$script_file"
    return $result
}

# Function to run PHPUnit safely
function run_phpunit_safely() {
    local file="$1"
    local output_file="$2"
    
    # Ensure file path is absolute
    file=$(ensure_absolute_path "$file")
    
    # When running WP tests, make sure the WP test lib is installed
    if [ ! -d "/tmp/wordpress-tests-lib" ] && [ -f "${PROJECT_DIR}/bin/install-wp-tests.sh" ]; then
        echo -e "${YELLOW}WordPress test library not found. Installing...${NC}"
        bash "${PROJECT_DIR}/bin/install-wp-tests.sh" wordpress_test root root localhost latest
    fi
    
    # Create a temporary shell script to run the command
    local script_file="${TEMP_DIR}/run_phpunit_$$.sh"
    echo "#!/bin/bash" > "$script_file"
    echo "\"${PHP_CMD}\" \"${PROJECT_DIR}/vendor/bin/phpunit\" --filter=\"$(basename "${file}" .php)\"" >> "$script_file"
    chmod +x "$script_file"
    
    # Execute the script
    "$script_file" > "${output_file}" 2>&1
    local result=$?
    rm -f "$script_file"
    return $result
}

# Function to run tests and track errors
function run_tests() {
    local file="$1"
    
    # Ensure file path is absolute
    file=$(ensure_absolute_path "$file")
    
    local filename=$(basename "${file}")
    local temp_error_log="${TEMP_DIR}/temp_error_log.txt"
    local current_errors=false
    local test_results="${TEMP_DIR}/test_results_$$.txt"
    
    # Start with empty test results
    > "${test_results}"
    
    echo -e "\n${BLUE}File changed: ${YELLOW}${filename}${NC}"
    echo -e "${BLUE}Running tests on: ${YELLOW}${file}${NC}"
    echo -e "${BLUE}Running tests...${NC}"
    
    # Verify file exists before running tests
    if [ ! -f "${file}" ]; then
        echo -e "${RED}File not found: ${file}${NC}"
        echo "PHP Syntax: SKIPPED (File not found)" >> "${test_results}"
        echo "${filename}: FAILED (File not found)" >> "${GLOBAL_TEST_RESULTS}"
        return 1
    fi
    
    # Clear temp error log
    > "${temp_error_log}"
    
    # Run PHP syntax check first - create a temporary shell script
    echo -e "${YELLOW}Checking PHP syntax...${NC}"
    local syntax_script="${TEMP_DIR}/check_syntax_$$.sh"
    echo "#!/bin/bash" > "$syntax_script"
    echo "\"${PHP_CMD}\" -l \"${file}\"" >> "$syntax_script"
    chmod +x "$syntax_script"

    if "$syntax_script" > "${temp_error_log}" 2>&1; then
        echo -e "${GREEN}✓ PHP syntax is valid${NC}"
        echo "PHP Syntax: OK" >> "${test_results}"
        rm -f "$syntax_script"
    else
        echo -e "${RED}✗ PHP syntax error:${NC}"
        cat "${temp_error_log}"
        echo -e "${file}: PHP syntax error on $(date)" >> "${ERROR_LOG}"
        echo "PHP Syntax: FAILED" >> "${test_results}"
        current_errors=true
        rm -f "$syntax_script"
        
        # Don't continue with other tests if syntax is invalid
        echo -e "${BLUE}Skipping additional tests due to syntax error${NC}"
        return 1
    fi
    
    # Run PHPCS on the changed file
    echo -e "${YELLOW}Running PHP CodeSniffer on ${filename}...${NC}"
    if run_phpcs_safely "${file}" "${temp_error_log}"; then
        echo -e "${GREEN}✓ PHPCS passed with no errors${NC}"
        echo "PHPCS: OK" >> "${test_results}"
    else
        echo -e "${RED}✗ PHPCS found issues:${NC}"
        cat "${temp_error_log}"
        echo -e "${file}: PHPCS errors on $(date)" >> "${ERROR_LOG}"
        echo "PHPCS: FAILED" >> "${test_results}"
        current_errors=true
    fi
    
    # If it's a test file or has a corresponding test file, run PHPUnit
    if [[ "${file}" == *"test-"* ]] || [[ -f "${PROJECT_DIR}/tests/test-$(basename "${file}")" ]]; then
        echo -e "\n${YELLOW}Running PHPUnit tests...${NC}"
        > "${temp_error_log}"
        if run_phpunit_safely "${file}" "${temp_error_log}"; then
            echo -e "${GREEN}✓ PHPUnit tests passed${NC}"
            echo "PHPUnit: OK" >> "${test_results}"
        else
            echo -e "${RED}✗ PHPUnit tests failed:${NC}"
            cat "${temp_error_log}"
            echo -e "${file}: PHPUnit errors on $(date)" >> "${ERROR_LOG}"
            echo "PHPUnit: FAILED" >> "${test_results}"
            current_errors=true
        fi
    else
        echo "PHPUnit: SKIPPED (Not a test file)" >> "${test_results}"
    fi
    
    # Run Pa11y accessibility tests if it's a template file
    if [[ "${file}" == *"/templates/"* ]] || [[ "${file}" == *".html"* ]] || [[ "${file}" == *".php"* && $(grep -c "get_header\|wp_head" "${file}") -gt 0 ]]; then
        echo -e "\n${YELLOW}Running Pa11y accessibility tests...${NC}"
        if [ -f "${PROJECT_DIR}/tools/run-a11y-tests.sh" ]; then
            # Create a temporary script to run Pa11y
            local a11y_script="${TEMP_DIR}/run_a11y_$$.sh"
            echo "#!/bin/bash" > "$a11y_script"
            echo "cd \"${PROJECT_DIR}\" && bash tools/run-a11y-tests.sh \"${file}\"" >> "$a11y_script"
            chmod +x "$a11y_script"
            
            # Run the script
            if "$a11y_script" > "${temp_error_log}" 2>&1; then
                echo -e "${GREEN}✓ Pa11y accessibility tests passed${NC}"
                echo "Pa11y Accessibility: OK" >> "${test_results}"
            else
                echo -e "${RED}✗ Pa11y accessibility tests failed:${NC}"
                cat "${temp_error_log}"
                echo -e "${file}: Pa11y accessibility errors on $(date)" >> "${ERROR_LOG}"
                echo "Pa11y Accessibility: FAILED" >> "${test_results}"
                current_errors=true
            fi
            rm -f "$a11y_script"
        else
            echo -e "${YELLOW}Pa11y tests skipped (tools/run-a11y-tests.sh not found)${NC}"
            echo "Pa11y Accessibility: SKIPPED (Test script not found)" >> "${test_results}"
        fi
    else
        echo "Pa11y Accessibility: SKIPPED (Not a template file)" >> "${test_results}"
    fi
    
    # Run E2E tests if we're modifying frontend files
    if [[ "${file}" == *".js"* ]] || [[ "${file}" == *".css"* ]] || [[ "${file}" == *"/templates/"* ]] || [[ "${file}" == *".html"* ]]; then
        echo -e "\n${YELLOW}Running E2E tests...${NC}"
        if [ -f "${PROJECT_DIR}/tools/run-e2e-tests.sh" ]; then
            # Create a temporary script to run E2E tests
            local e2e_script="${TEMP_DIR}/run_e2e_$$.sh"
            echo "#!/bin/bash" > "$e2e_script"
            echo "cd \"${PROJECT_DIR}\" && bash tools/run-e2e-tests.sh" >> "$e2e_script"
            chmod +x "$e2e_script"
            
            # Run the script
            if "$e2e_script" > "${temp_error_log}" 2>&1; then
                echo -e "${GREEN}✓ E2E tests passed${NC}"
                echo "E2E Test: OK" >> "${test_results}"
            else
                echo -e "${RED}✗ E2E tests failed:${NC}"
                cat "${temp_error_log}"
                echo -e "${file}: E2E test errors on $(date)" >> "${ERROR_LOG}"
                echo "E2E Test: FAILED" >> "${test_results}"
                current_errors=true
            fi
            rm -f "$e2e_script"
        else
            echo -e "${YELLOW}E2E tests skipped (tools/run-e2e-tests.sh not found)${NC}"
            echo "E2E Test: SKIPPED (Test script not found)" >> "${test_results}"
        fi
    else
        echo "E2E Test: SKIPPED (Not a frontend file)" >> "${test_results}"
    fi
    
    # Try auto-fix if errors were found
    if [ "${current_errors}" = true ]; then
        echo -e "\n${YELLOW}Attempting to auto-fix issues...${NC}"
        
        # Create and run auto-fix script
        local autofix_script="${TEMP_DIR}/auto_fix_$$.sh"
        echo "#!/bin/bash" > "$autofix_script"
        echo "\"${PHP_CMD}\" \"${PROJECT_DIR}/tools/auto-fix.php\" \"${file}\"" >> "$autofix_script"
        chmod +x "$autofix_script"
        "$autofix_script" > /dev/null 2>&1 || true
        rm -f "$autofix_script"
        
        echo -e "${BLUE}Re-running tests after auto-fix...${NC}"
        
        # Run phpcbf on the file to attempt to fix coding standards
        echo -e "${YELLOW}Running PHPCBF to auto-fix coding standards...${NC}"
        local phpcbf_script="${TEMP_DIR}/phpcbf_$$.sh"
        echo "#!/bin/bash" > "$phpcbf_script"
        echo "\"${PHP_CMD}\" \"${PROJECT_DIR}/vendor/bin/phpcbf\" --standard=WordPress \"${file}\"" >> "$phpcbf_script"
        chmod +x "$phpcbf_script"
        "$phpcbf_script" > /dev/null 2>&1 || true
        rm -f "$phpcbf_script"
        
        # Re-run PHPCS to see if errors were fixed
        > "${temp_error_log}"
        if run_phpcs_safely "${file}" "${temp_error_log}"; then
            echo -e "${GREEN}✓ Auto-fix resolved PHPCS errors!${NC}"
            # Remove file from error log if errors were fixed
            grep -v "${file}: PHPCS errors" "${ERROR_LOG}" > "${TEMP_DIR}/error_log_temp.txt"
            mv "${TEMP_DIR}/error_log_temp.txt" "${ERROR_LOG}"
            # Update test results
            sed -i '' 's/PHPCS: FAILED/PHPCS: OK (Auto-fixed)/g' "${test_results}"
        else
            echo -e "${RED}✗ Some PHPCS issues remain after auto-fix${NC}"
        fi
        
        # Re-run Pa11y and E2E tests if they failed
        # (This would require more implementation - skipping for brevity but would follow similar pattern)
    else
        # If no errors, remove file from error log
        grep -v "${file}" "${ERROR_LOG}" > "${TEMP_DIR}/error_log_temp.txt"
        mv "${TEMP_DIR}/error_log_temp.txt" "${ERROR_LOG}"
    fi
    
    # Display test results summary
    echo -e "\n${BLUE}Test Results Summary for ${YELLOW}${filename}${BLUE}:${NC}"
    cat "${test_results}"
    
    # Check if all tests passed
    if grep -q "FAILED" "${test_results}"; then
        echo -e "\n${RED}❌ Some tests failed. Please fix the issues before committing.${NC}"
        # Save to a global results file
        echo "${filename}: FAILED" >> "${GLOBAL_TEST_RESULTS}"
        return 1
    else
        echo -e "\n${GREEN}✅ All tests passed! This file is ready to commit.${NC}"
        # Save to a global results file
        echo "${filename}: PASSED" >> "${GLOBAL_TEST_RESULTS}"
        return 0
    fi
    
    # Cleanup
    rm -f "${test_results}"
    
    # Separator for readability
    echo -e "${BLUE}-----------------------------------------------------------${NC}"
}

# Get initial state of PHP files - use xargs to handle spaces in filenames
find "${PROJECT_DIR}" -name "*.php" -not -path "*/vendor/*" -not -path "*/node_modules/*" -type f -print0 | xargs -0 stat -f "%m %N" | sort > "${TEMP_DIR}/phpfiles_state.txt"

# Initialize git commit tracking
check_for_commits

while true; do
    # Wait a bit to avoid excessive CPU usage
    sleep 2
    
    # Check if there was a new commit
    if ! check_for_commits; then
        echo -e "\n${GREEN}New git commit detected! Clearing error logs.${NC}"
        > "${ERROR_LOG}"
        > "${ERROR_HISTORY}"
        > "${GLOBAL_TEST_RESULTS}"
        echo -e "${GREEN}Error logs cleared. Starting fresh monitoring.${NC}"
    fi
    
    # Create temporary files for processing
    diff_output="${TEMP_DIR}/diff_output.txt"
    changed_files_list="${TEMP_DIR}/changed_files.txt"
    
    # Get current state - use xargs to handle spaces in filenames
    find "${PROJECT_DIR}" -name "*.php" -not -path "*/vendor/*" -not -path "*/node_modules/*" -type f -print0 | xargs -0 stat -f "%m %N" | sort > "${TEMP_DIR}/phpfiles_state_new.txt"
    
    # Process diff output more carefully to handle filenames with spaces
    # Generate diff output
    diff "${TEMP_DIR}/phpfiles_state.txt" "${TEMP_DIR}/phpfiles_state_new.txt" > "${diff_output}"
    
    # Reset changed files list
    > "${changed_files_list}"
    
    # Extract filenames more carefully - only process lines starting with "> "
    grep "^> " "${diff_output}" | while IFS= read -r line; do
        # Remove the "> " prefix and the timestamp at the beginning
        echo "${line}" | sed 's/^> [0-9]* //' >> "${changed_files_list}"
    done
    
    # Process each changed file
    if [ -s "${changed_files_list}" ]; then
        while IFS= read -r file; do
            # Skip if file is empty or just whitespace
            if [ -z "$(echo "${file}" | tr -d '[:space:]')" ]; then
                continue
            fi
            
            # Skip .git directory files
            if [[ "${file}" == *"/.git/"* ]]; then
                echo -e "${YELLOW}Skipping Git file: ${file}${NC}"
                continue
            fi
            
            run_tests "${file}"
            
            # Append error history for reference
            cat "${ERROR_LOG}" >> "${ERROR_HISTORY}"
        done < "${changed_files_list}"
        
        # Update previous state for next comparison
        mv "${TEMP_DIR}/phpfiles_state_new.txt" "${TEMP_DIR}/phpfiles_state.txt"
    fi
    
    # Clean up temporary files
    rm -f "${diff_output}" "${changed_files_list}"
    
    # Display remaining errors summary, if any
    if [ -s "${ERROR_LOG}" ]; then
        echo -e "\n${YELLOW}Remaining errors to fix:${NC}"
        cat "${ERROR_LOG}"
    fi
    
    # Display global test results summary if any files were tested
    if [ -s "${GLOBAL_TEST_RESULTS}" ]; then
        echo -e "\n${BLUE}===== OVERALL TEST STATUS =====${NC}"
        cat "${GLOBAL_TEST_RESULTS}"
        
        if grep -q "FAILED" "${GLOBAL_TEST_RESULTS}"; then
            echo -e "\n${RED}❌ Some tests failed. Please fix all issues before committing.${NC}"
            echo -e "${YELLOW}Use 'git status' to see which files were changed.${NC}"
            echo -e "${YELLOW}When all tests pass, you can commit your changes.${NC}"
        else
            echo -e "\n${GREEN}✅ All tests passed! Your changes are ready to commit.${NC}"
            echo -e "${GREEN}Recommended actions:${NC}"
            echo -e "  git add ."
            echo -e "  git commit -m \"Your commit message\""
        fi
        
        # Clear the global test results after displaying
        > "${GLOBAL_TEST_RESULTS}"
    fi
done 