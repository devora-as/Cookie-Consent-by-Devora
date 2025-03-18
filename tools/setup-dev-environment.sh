#!/bin/bash

# Color variables
GREEN='\033[0;32m'
BLUE='\033[0;34m'
RED='\033[0;31m'
YELLOW='\033[0;33m'
NC='\033[0m' # No Color

# Set PHP path to Local by Flywheel instance
PHP_CMD="/Users/christian/Library/Application Support/Local/lightning-services/php-8.0.30+0/bin/darwin-arm64/bin/php"

# Check if the specific PHP exists
if [ ! -f "$PHP_CMD" ]; then
    echo -e "${YELLOW}Specific PHP not found at: $PHP_CMD${NC}"
    echo -e "${YELLOW}Falling back to system PHP${NC}"
    PHP_CMD="php"
fi

echo -e "${GREEN}Using PHP: $PHP_CMD${NC}"

echo "${BLUE}Setting up development environment for Custom Cookie Consent plugin...${NC}"

# Install Composer dependencies
echo "${GREEN}Installing Composer dependencies...${NC}"
if command -v composer > /dev/null; then
    composer install
else
    echo "${RED}Composer not found. Please install Composer: https://getcomposer.org/download/${NC}"
    exit 1
fi

# Add PHP Mess Detector if not in composer.json
if ! grep -q "phpmd/phpmd" composer.json; then
    echo "${GREEN}Adding PHP Mess Detector...${NC}"
    composer require --dev phpmd/phpmd
fi

# Install WP-CLI if not already installed
if [ ! -f wp-cli.phar ]; then
    echo "${GREEN}Installing WP-CLI...${NC}"
    curl -O https://raw.githubusercontent.com/wp-cli/builds/gh-pages/phar/wp-cli.phar
    chmod +x wp-cli.phar
fi

# Install WordPress test library
echo "${GREEN}Setting up WordPress test environment...${NC}"
if [ ! -d /tmp/wordpress-tests-lib ]; then
    bash "./bin/install-wp-tests.sh" wordpress_test root '' localhost latest
else
    echo "${GREEN}WordPress test library already installed.${NC}"
fi

# Install Pa11y for accessibility testing if not installed
if ! command -v pa11y > /dev/null; then
    echo "${GREEN}Installing Pa11y for accessibility testing...${NC}"
    if command -v npm > /dev/null; then
        npm install -g pa11y
    else
        echo "${RED}npm not found. Please install Node.js and npm to enable accessibility testing.${NC}"
    fi
fi

# Make test script executable
chmod +x ./tools/run-tests.sh

echo "${BLUE}Development environment setup complete!${NC}"
echo "Run tests with: ./tools/run-tests.sh" 