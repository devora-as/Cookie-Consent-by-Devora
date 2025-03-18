#!/bin/bash

# Accessibility Testing Script
# This script runs Pa11y accessibility tests on the plugin

# Configuration
WP_VERSION=${1:-latest}
PHP_VERSION=${2:-"7.4"}
WP_MULTISITE=${3:-0}
PLUGIN_DIR=$(pwd)
WP_TEST_URL="http://devora.local"

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[0;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

echo -e "${BLUE}Running Accessibility Tests${NC}"
echo -e "${BLUE}===========================${NC}\n"

# Check if Pa11y is installed
if ! command -v pa11y &> /dev/null; then
    echo -e "${YELLOW}Pa11y not installed. Installing...${NC}"
    npm install -g pa11y pa11y-reporter-html
fi

# Check if WordPress test site is running
WP_SERVER_RUNNING=false

# Try to connect to test server
if curl -s $WP_TEST_URL > /dev/null; then
    WP_SERVER_RUNNING=true
    echo -e "${GREEN}WordPress test server found at $WP_TEST_URL${NC}"
else
    echo -e "${YELLOW}WordPress test server not running. Starting temporary server...${NC}"
    
    # Check if WP-CLI is available
    if command -v wp &> /dev/null; then
        # Check if we're in a WordPress installation
        if [ -f "wp-config.php" ] || [ -f "../wp-config.php" ] || [ -f "../../wp-config.php" ]; then
            # We're in a WordPress installation
            echo -e "${YELLOW}Starting WordPress server using wp-cli...${NC}"
            wp server --host=localhost --port=8000 &
            WP_SERVER_PID=$!
            sleep 3
            WP_SERVER_RUNNING=true
        else
            # Set up a temporary WordPress installation
            if [ ! -d "/tmp/wordpress" ]; then
                echo -e "${YELLOW}Setting up temporary WordPress installation...${NC}"
                mkdir -p /tmp/wordpress
                wp core download --version=$WP_VERSION --path=/tmp/wordpress
                wp config create --dbname=wordpress_test --dbuser=root --dbpass=root --path=/tmp/wordpress
                wp db create --path=/tmp/wordpress || true
                wp core install --url=http://devora.local --title="Testing Site" --admin_user=admin --admin_password=password --admin_email=admin@example.com --path=/tmp/wordpress
                
                # Link our plugin
                ln -s $(pwd) /tmp/wordpress/wp-content/plugins/
                wp plugin activate $PLUGIN_DIR --path=/tmp/wordpress
            fi
            
            echo -e "${YELLOW}Starting temporary WordPress server...${NC}"
            php -S 127.0.0.1:8000 -t /tmp/wordpress &
            WP_SERVER_PID=$!
            sleep 3
            WP_SERVER_RUNNING=true
        fi
    else
        echo -e "${RED}WP-CLI not found and no WordPress server running. Cannot run accessibility tests.${NC}"
        echo -e "${YELLOW}Please install WP-CLI or start a WordPress server manually.${NC}"
        exit 1
    fi
fi

# Create output directory for reports
mkdir -p ./tests/a11y-reports

# Run Pa11y tests
if [ "$WP_SERVER_RUNNING" = true ]; then
    echo -e "${YELLOW}Running Pa11y accessibility tests...${NC}"
    
    # Define pages to test
    PAGES=(
        "/"
        "/wp-admin/admin.php?page=cookie-consent-settings"
        "/wp-admin/admin.php?page=cookie-consent-settings&tab=design"
        "/wp-admin/admin.php?page=cookie-consent-settings&tab=analytics"
    )
    
    # Test each page
    FAILED=false
    for page in "${PAGES[@]}"; do
        echo -e "${YELLOW}Testing page: $WP_TEST_URL$page${NC}"
        
        # Run Pa11y and save HTML report
        REPORT_FILE="./tests/a11y-reports/$(echo "$page" | sed 's/[^a-zA-Z0-9]/_/g').html"
        
        if pa11y --reporter html "$WP_TEST_URL$page" > "$REPORT_FILE"; then
            echo -e "${GREEN}✓ Page passed accessibility tests${NC}"
        else
            echo -e "${RED}✗ Page failed accessibility tests${NC}"
            echo -e "${YELLOW}Report saved to $REPORT_FILE${NC}"
            FAILED=true
        fi
    done
    
    # Kill the server if we started it
    if [ ! -z "$WP_SERVER_PID" ]; then
        echo -e "${YELLOW}Stopping temporary WordPress server...${NC}"
        kill $WP_SERVER_PID
    fi
    
    # Final output
    if [ "$FAILED" = true ]; then
        echo -e "\n${RED}✗ Some pages failed accessibility tests. See reports for details.${NC}"
        echo -e "${YELLOW}Reports saved to ./tests/a11y-reports/${NC}"
        exit 1
    else
        echo -e "\n${GREEN}✓ All pages passed accessibility tests!${NC}"
        exit 0
    fi
else
    echo -e "${RED}Cannot run accessibility tests without a WordPress server.${NC}"
    exit 1
fi

# Start PHP server if not already running
if [ "$WP_SERVER_RUNNING" = false ]; then
    # First check if we need to set up WordPress
    if [ ! -d "/tmp/wordpress" ]; then
        echo -e "${YELLOW}Setting up temporary WordPress installation...${NC}"
        mkdir -p /tmp/wordpress
        wp core download --version=$WP_VERSION --path=/tmp/wordpress
        wp config create --dbname=wordpress_test --dbuser=root --dbpass=root --path=/tmp/wordpress
        wp db create --path=/tmp/wordpress || true
        wp core install --url=http://devora.local --title="Testing Site" --admin_user=admin --admin_password=password --admin_email=admin@example.com --path=/tmp/wordpress
        
        # Link our plugin
        ln -s $(pwd) /tmp/wordpress/wp-content/plugins/
        wp plugin activate $PLUGIN_DIR --path=/tmp/wordpress
    fi
    
    echo -e "${YELLOW}Starting temporary WordPress server...${NC}"
    php -S 127.0.0.1:8000 -t /tmp/wordpress &
    WP_SERVER_PID=$!
    
    # Configure hosts file
    echo "Setting up hosts file..."
    if ! grep -q "devora.local" /etc/hosts; then
        echo "127.0.0.1 devora.local" | sudo tee -a /etc/hosts > /dev/null
    fi
    
    sleep 3
else
    echo -e "${GREEN}WordPress test server already running.${NC}"
fi 