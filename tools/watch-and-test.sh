#!/bin/bash

# Set PHP path to Local by Flywheel instance
PHP_CMD="/Users/christian/Library/Application Support/Local/lightning-services/php-8.0.30+0/bin/darwin-arm64/bin/php"

# Check if the specific PHP exists
if [ ! -f "$PHP_CMD" ]; then
    echo "Specific PHP not found at: $PHP_CMD"
    echo "Falling back to system PHP"
    PHP_CMD="php"
fi

echo "Using PHP: $PHP_CMD"

# Check if fswatch is installed
if ! command -v fswatch &> /dev/null; then
    echo "fswatch not found. Installing with Homebrew..."
    brew install fswatch || { echo "Error: Failed to install fswatch"; exit 1; }
fi

echo "Watching files for changes..."
echo "Press Ctrl+C to stop."

# Watch PHP files and run tests when they change
fswatch -o --exclude="vendor|node_modules|\.git" -e ".*" -i "\.php$" . | while read -r changed_file; do
    echo "File changed: $changed_file"
    echo "Running tests..."
    
    # Get just the filename for display
    filename=$(basename "$changed_file")
    
    # Run PHPCS on the changed file
    echo "Running PHP CodeSniffer on $filename..."
    "$PHP_CMD" ./vendor/bin/phpcs --standard=WordPress "$changed_file"
    
    # If it's a test file or has a corresponding test file, run PHPUnit
    if [[ "$changed_file" == *"test-"* ]] || [[ -f "tests/test-$(basename "$changed_file")" ]]; then
        echo "Running PHPUnit tests..."
        "$PHP_CMD" ./vendor/bin/phpunit
    fi
    
    echo "-----------------------------------------------------------"
done 