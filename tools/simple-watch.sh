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
WP_CLI="$PROJECT_DIR/wp-cli.phar"
ERROR_LOG="$TEMP_DIR/php_error_log.txt"
ERROR_HISTORY="$TEMP_DIR/php_error_history.txt"

# Clear error logs initially
> "$ERROR_LOG"
> "$ERROR_HISTORY"

# Find PHP in various locations
if command -v php &> /dev/null; then
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
        PHP_PATH=$(mdfind -name 'php' | grep '/bin/php$' | head -1)
        if [ ! -z "$PHP_PATH" ]; then
            PHP_CMD="$PHP_PATH"
        fi
    fi
    
    # If PHP is still not found, use the path from phpinfo
    if [ -z "$PHP_CMD" ]; then
        echo -e "${YELLOW}Trying to find PHP using alternative methods...${NC}"
        PHPINFO=$(php -i 2>/dev/null | grep 'PHP Binary' | awk '{print $4}')
        if [ ! -z "$PHPINFO" ]; then
            PHP_CMD="$PHPINFO"
        else
            # Last resort - use env to run with the current shell's environment
            PHP_CMD="env php"
        fi
    fi
fi

echo -e "${GREEN}Using PHP: $PHP_CMD${NC}"

# Check if wp-cli.phar exists
if [ ! -f "$WP_CLI" ]; then
    echo -e "${YELLOW}WP-CLI not found at $WP_CLI. Trying to use globally installed wp command.${NC}"
    if command -v wp &> /dev/null; then
        WP_CLI="wp"
    else
        echo -e "${YELLOW}Neither wp-cli.phar nor global wp command found. Some functionality might be limited.${NC}"
    fi
fi

# Function to check for git commits
function check_for_commits() {
    local current_commit=$(cd "$PROJECT_DIR" && git rev-parse HEAD 2>/dev/null || echo "none")
    local last_checked_commit_file="$TEMP_DIR/last_checked_commit.txt"
    
    # If commit file doesn't exist, create it
    if [ ! -f "$last_checked_commit_file" ]; then
        echo "$current_commit" > "$last_checked_commit_file"
        return 0
    fi
    
    local last_checked_commit=$(cat "$last_checked_commit_file")
    
    if [ "$current_commit" != "$last_checked_commit" ]; then
        echo "$current_commit" > "$last_checked_commit_file"
        return 1  # Commit has changed
    fi
    
    return 0  # No change
}

# Function to run PHPCS safely
function run_phpcs_safely() {
    local file="$1"
    local output_file="$2"
    
    # Use a simple report format to avoid vsprintf errors
    $PHP_CMD "$PROJECT_DIR/vendor/bin/phpcs" --standard=WordPress --report=summary "$file" > "$output_file" 2>&1
    return $?
}

# Function to run PHPUnit safely
function run_phpunit_safely() {
    local file="$1"
    local output_file="$2"
    
    # When running WP tests, make sure the WP test lib is installed
    if [ ! -d "/tmp/wordpress-tests-lib" ] && [ -f "$PROJECT_DIR/bin/install-wp-tests.sh" ]; then
        echo -e "${YELLOW}WordPress test library not found. Installing...${NC}"
        bash "$PROJECT_DIR/bin/install-wp-tests.sh" wordpress_test root root localhost latest
    fi
    
    # Run the tests
    $PHP_CMD "$PROJECT_DIR/vendor/bin/phpunit" > "$output_file" 2>&1
    return $?
}

# Function to run tests and track errors
function run_tests() {
    local file="$1"
    local filename=$(basename "$file")
    local temp_error_log="$TEMP_DIR/temp_error_log.txt"
    local current_errors=false
    
    echo -e "\n${BLUE}File changed: ${YELLOW}$filename${NC}"
    echo -e "${BLUE}Running tests...${NC}"
    
    # Clear temp error log
    > "$temp_error_log"
    
    # Run PHP syntax check first
    echo -e "${YELLOW}Checking PHP syntax...${NC}"
    if $PHP_CMD -l "$file" > "$temp_error_log" 2>&1; then
        echo -e "${GREEN}✓ PHP syntax is valid${NC}"
    else
        echo -e "${RED}✗ PHP syntax error:${NC}"
        cat "$temp_error_log"
        echo -e "$file: PHP syntax error on $(date)" >> "$ERROR_LOG"
        current_errors=true
        
        # Don't continue with other tests if syntax is invalid
        echo -e "${BLUE}Skipping additional tests due to syntax error${NC}"
        return
    fi
    
    # Run PHPCS on the changed file
    echo -e "${YELLOW}Running PHP CodeSniffer on $filename...${NC}"
    if run_phpcs_safely "$file" "$temp_error_log"; then
        echo -e "${GREEN}✓ PHPCS passed with no errors${NC}"
    else
        echo -e "${RED}✗ PHPCS found issues:${NC}"
        cat "$temp_error_log"
        echo -e "$file: PHPCS errors on $(date)" >> "$ERROR_LOG"
        current_errors=true
    fi
    
    # If it's a test file or has a corresponding test file, run PHPUnit
    if [[ "$file" == *"test-"* ]] || [[ -f "$PROJECT_DIR/tests/test-$(basename "$file")" ]]; then
        echo -e "\n${YELLOW}Running PHPUnit tests...${NC}"
        > "$temp_error_log"
        if run_phpunit_safely "$file" "$temp_error_log"; then
            echo -e "${GREEN}✓ PHPUnit tests passed${NC}"
        else
            echo -e "${RED}✗ PHPUnit tests failed:${NC}"
            cat "$temp_error_log"
            echo -e "$file: PHPUnit errors on $(date)" >> "$ERROR_LOG"
            current_errors=true
        fi
    fi
    
    # Try auto-fix if errors were found
    if [ "$current_errors" = true ]; then
        echo -e "\n${YELLOW}Attempting to auto-fix issues...${NC}"
        $PHP_CMD "$PROJECT_DIR/tools/auto-fix.php" "$file" > /dev/null 2>&1 || true
        echo -e "${BLUE}Re-running tests after auto-fix...${NC}"
        
        # Re-run PHPCS to see if errors were fixed
        > "$temp_error_log"
        if run_phpcs_safely "$file" "$temp_error_log"; then
            echo -e "${GREEN}✓ Auto-fix resolved PHPCS errors!${NC}"
            # Remove file from error log if errors were fixed
            grep -v "$file: PHPCS errors" "$ERROR_LOG" > "$TEMP_DIR/error_log_temp.txt"
            mv "$TEMP_DIR/error_log_temp.txt" "$ERROR_LOG"
        else
            echo -e "${RED}✗ Some PHPCS issues remain after auto-fix${NC}"
        fi
    else
        # If no errors, remove file from error log
        grep -v "$file" "$ERROR_LOG" > "$TEMP_DIR/error_log_temp.txt"
        mv "$TEMP_DIR/error_log_temp.txt" "$ERROR_LOG"
    fi
    
    # Run phpcbf on the file to attempt to fix coding standards
    echo -e "\n${YELLOW}Running PHPCBF to auto-fix coding standards...${NC}"
    $PHP_CMD "$PROJECT_DIR/vendor/bin/phpcbf" --standard=WordPress "$file" > /dev/null 2>&1 || true
    
    # Separator for readability
    echo -e "${BLUE}-----------------------------------------------------------${NC}"
}

# Get initial state of PHP files
find "$PROJECT_DIR" -name "*.php" -not -path "*/vendor/*" -not -path "*/node_modules/*" -type f -exec stat -f "%m %N" {} \; | sort > "$TEMP_DIR/phpfiles_state.txt"

# Initialize git commit tracking
check_for_commits

while true; do
    # Wait a bit to avoid excessive CPU usage
    sleep 2
    
    # Check if there was a new commit
    if ! check_for_commits; then
        echo -e "\n${GREEN}New git commit detected! Clearing error logs.${NC}"
        > "$ERROR_LOG"
        > "$ERROR_HISTORY"
        echo -e "${GREEN}Error logs cleared. Starting fresh monitoring.${NC}"
    fi
    
    # Get current state
    find "$PROJECT_DIR" -name "*.php" -not -path "*/vendor/*" -not -path "*/node_modules/*" -type f -exec stat -f "%m %N" {} \; | sort > "$TEMP_DIR/phpfiles_state_new.txt"
    
    # Compare with previous state
    changed_files=$(diff "$TEMP_DIR/phpfiles_state.txt" "$TEMP_DIR/phpfiles_state_new.txt" | grep ">" | cut -d' ' -f3-)
    
    if [ ! -z "$changed_files" ]; then
        for file in $changed_files; do
            run_tests "$file"
            
            # Append error history for reference
            cat "$ERROR_LOG" >> "$ERROR_HISTORY"
        done
        
        # Show summary of remaining errors
        error_count=$(wc -l < "$ERROR_LOG")
        if [ "$error_count" -gt 0 ]; then
            echo -e "\n${YELLOW}Remaining errors to fix:${NC}"
            cat "$ERROR_LOG"
        else
            echo -e "\n${GREEN}All detected errors have been fixed!${NC}"
        fi
    fi
    
    # Update state
    cp "$TEMP_DIR/phpfiles_state_new.txt" "$TEMP_DIR/phpfiles_state.txt"
done 