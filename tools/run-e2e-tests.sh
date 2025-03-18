#!/bin/bash

# E2E Testing Runner Script
# This script sets up a WordPress test environment and runs Cypress E2E tests

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[0;33m'
NC='\033[0m' # No Color

# Set up test WordPress environment using WP-CLI
echo -e "${GREEN}Setting up test WordPress environment...${NC}"

# Configuration
WP_VERSION=${1:-latest}
PHP_VERSION=${2:-"7.4"}
WP_MULTISITE=${3:-0}
PLUGIN_DIR="$(pwd)"
PLUGIN_NAME="$(basename "${PLUGIN_DIR}")"
WP_TEST_URL="http://devora.local"

# Check if WordPress test environment is available
if ! wp core is-installed --path=/tmp/wordpress &> /dev/null; then
    echo -e "${YELLOW}WordPress test environment not found. Setting up...${NC}"
    
    # Create WordPress test site
    mkdir -p /tmp/wordpress
    wp core download --version="$WP_VERSION" --path=/tmp/wordpress

    # Create wp-config.php
    wp config create --dbname=wordpress_test --dbuser=root --dbpass=root --path=/tmp/wordpress

    # Create database
    wp db create --path=/tmp/wordpress || true

    # Install WordPress
    wp core install --url=http://devora.local --title="Testing Site" --admin_user=admin --admin_password=password --admin_email=admin@example.com --path=/tmp/wordpress
    
    # Activate our plugin
    if [ ! -d "/tmp/wordpress/wp-content/plugins/$PLUGIN_NAME" ]; then
        ln -s "$PLUGIN_DIR" /tmp/wordpress/wp-content/plugins/
    fi
    wp plugin activate "$PLUGIN_NAME" --path=/tmp/wordpress
fi

# Start PHP server
echo -e "${YELLOW}Starting PHP server...${NC}"
php -S 127.0.0.1:8000 -t /tmp/wordpress &
SERVER_PID=$!

# Configure hosts file
echo -e "${YELLOW}Setting up hosts file...${NC}"
if ! grep -q "devora.local" /etc/hosts; then
    echo "127.0.0.1 devora.local" | sudo tee -a /etc/hosts > /dev/null
fi

sleep 2

# Run Cypress tests
echo -e "${GREEN}Running E2E tests with Cypress...${NC}"
CYPRESS_baseUrl=http://devora.local npm run cypress:run

# Capture the exit code of Cypress
CYPRESS_EXIT_CODE=$?

# Kill the PHP server
kill $SERVER_PID

# Exit with Cypress exit code
exit $CYPRESS_EXIT_CODE 