#!/bin/bash

# E2E Testing Setup Script
# This script sets up Cypress for end-to-end testing of the WordPress plugin

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[0;33m'
NC='\033[0m' # No Color

echo -e "${GREEN}Custom Cookie Consent - E2E Testing Setup${NC}"
echo -e "${GREEN}==========================================${NC}\n"

# Check if Node.js is installed
if ! command -v node &> /dev/null; then
    echo -e "${RED}Node.js is not installed. Please install Node.js first.${NC}"
    exit 1
fi

# Check if npm is installed
if ! command -v npm &> /dev/null; then
    echo -e "${RED}npm is not installed. Please install npm first.${NC}"
    exit 1
fi

# Create cypress directory if it doesn't exist
if [ ! -d "cypress" ]; then
    echo -e "${YELLOW}Setting up Cypress for E2E testing...${NC}"
    mkdir -p cypress
    mkdir -p cypress/e2e
    mkdir -p cypress/fixtures
    mkdir -p cypress/support
else
    echo -e "${YELLOW}Cypress directory already exists. Updating configuration...${NC}"
fi

# Install Cypress and dependencies if not already installed
if [ ! -d "node_modules/cypress" ]; then
    echo -e "${YELLOW}Installing Cypress and dependencies...${NC}"
    npm init -y
    npm install --save-dev cypress @wordpress/e2e-test-utils-playwright
else
    echo -e "${YELLOW}Cypress already installed. Checking for updates...${NC}"
    npm update cypress @wordpress/e2e-test-utils-playwright
fi

# Create package.json scripts if they don't exist
if ! grep -q "cypress:open" package.json; then
    echo -e "${YELLOW}Adding Cypress scripts to package.json...${NC}"
    # Use temp file for sed compatibility across platforms
    sed -i.bak '/"scripts": {/a \
    "cypress:open": "cypress open",\
    "cypress:run": "cypress run",\
    "test:e2e": "cypress run",' package.json
    rm package.json.bak
fi

# Create cypress.config.js if it doesn't exist
if [ ! -f "cypress.config.js" ]; then
    echo -e "${YELLOW}Creating Cypress configuration...${NC}"
    cat > cypress.config.js << 'EOL'
const { defineConfig } = require('cypress')

module.exports = defineConfig({
  e2e: {
    baseUrl: 'http://devora.local/wp-admin',
    specPattern: 'cypress/e2e/**/*.cy.{js,jsx,ts,tsx}',
    supportFile: 'cypress/support/e2e.js',
    setupNodeEvents(on, config) {
      // implement node event listeners here
    },
  },
})
EOL
fi

# Create support/e2e.js if it doesn't exist
if [ ! -f "cypress/support/e2e.js" ]; then
    echo -e "${YELLOW}Creating Cypress support files...${NC}"
    mkdir -p cypress/support
    cat > cypress/support/e2e.js << 'EOL'
// ***********************************************************
// This example support/e2e.js is processed and
// loaded automatically before your test files.
//
// This is a great place to put global configuration and
// behavior that modifies Cypress.
// ***********************************************************

// Import commands.js using ES2015 syntax:
import './commands'

// Hide fetch/XHR requests from command log
const app = window.top;
if (app && !app.document.head.querySelector('[data-hide-command-log-request]')) {
  const style = app.document.createElement('style');
  style.innerHTML =
    '.command-name-request, .command-name-xhr { display: none }';
  style.setAttribute('data-hide-command-log-request', '');
  app.document.head.appendChild(style);
}
EOL
fi

# Create support/commands.js if it doesn't exist
if [ ! -f "cypress/support/commands.js" ]; then
    mkdir -p cypress/support
    cat > cypress/support/commands.js << 'EOL'
// ***********************************************
// This example commands.js shows you how to
// create various custom commands and overwrite
// existing commands.
//
// For more comprehensive examples of custom
// commands please read more here:
// https://on.cypress.io/custom-commands
// ***********************************************

// WordPress login command
Cypress.Commands.add('login', (username = 'admin', password = 'password') => {
  cy.visit('/wp-login.php')
  cy.get('#user_login').type(username)
  cy.get('#user_pass').type(password)
  cy.get('#wp-submit').click()
  cy.get('body').should('have.class', 'wp-admin')
})

// Navigate to plugin settings
Cypress.Commands.add('goToPluginSettings', () => {
  cy.visit('/wp-admin/admin.php?page=cookie-consent-settings')
  cy.get('h1').should('contain', 'Cookie Consent Settings')
})

// Enable plugin features
Cypress.Commands.add('enableFeature', (featureSelector) => {
  cy.get(featureSelector).check()
  cy.get('input[name="submit"]').click()
  cy.get('.notice-success').should('be.visible')
})

// Open consent banner on frontend
Cypress.Commands.add('openFrontendConsentBanner', () => {
  cy.visit('/')
  cy.get('.cc-banner').should('be.visible')
})
EOL
fi

# Create a sample E2E test if it doesn't exist
if [ ! -f "cypress/e2e/admin-pages.cy.js" ]; then
    echo -e "${YELLOW}Creating sample E2E tests...${NC}"
    mkdir -p cypress/e2e
    cat > cypress/e2e/admin-pages.cy.js << 'EOL'
/**
 * Test admin pages in the plugin
 */
describe('Admin Pages', () => {
  beforeEach(() => {
    cy.login()
  })

  it('should load the settings page', () => {
    cy.goToPluginSettings()
    cy.get('.nav-tab-wrapper').should('be.visible')
    cy.get('form#cookie-consent-settings').should('be.visible')
  })

  it('should navigate to all tabs', () => {
    cy.goToPluginSettings()
    
    // Click on each tab and verify content loads
    cy.get('.nav-tab').each(($tab) => {
      cy.wrap($tab).click()
      cy.get('form#cookie-consent-settings').should('be.visible')
    })
  })

  it('should save settings properly', () => {
    cy.goToPluginSettings()
    
    // Make a change to a setting
    cy.get('input[name="cookie_consent_settings[consent_expiration]"]').clear().type('365')
    
    // Save the form
    cy.get('input[name="submit"]').click()
    
    // Verify success message
    cy.get('.notice-success').should('be.visible')
    
    // Verify the setting was saved
    cy.get('input[name="cookie_consent_settings[consent_expiration]"]').should('have.value', '365')
  })
})
EOL

    cat > cypress/e2e/frontend-consent.cy.js << 'EOL'
/**
 * Test frontend consent banner functionality
 */
describe('Frontend Consent Banner', () => {
  beforeEach(() => {
    // Log in and enable the consent banner
    cy.login()
    cy.goToPluginSettings()
    cy.get('input[name="cookie_consent_settings[enable_cookie_consent]"]').check()
    cy.get('input[name="submit"]').click()
    
    // Clear cookies and localStorage to ensure banner shows
    cy.clearCookies()
    cy.clearLocalStorage()
  })

  it('should display the consent banner on frontend', () => {
    cy.visit('/')
    cy.get('.cc-banner').should('be.visible')
    cy.get('.cc-banner .cc-message').should('be.visible')
    cy.get('.cc-banner .cc-btn').should('be.visible')
  })

  it('should accept cookies when clicking accept button', () => {
    cy.visit('/')
    cy.get('.cc-banner .cc-btn[data-cc-accept="accept-all"]').click()
    
    // Banner should be hidden
    cy.get('.cc-banner').should('not.be.visible')
    
    // Reload page and verify banner doesn't show again
    cy.reload()
    cy.get('.cc-banner').should('not.exist')
  })

  it('should open cookie settings when clicking settings button', () => {
    cy.visit('/')
    cy.get('.cc-banner .cc-btn[data-cc-settings-btn]').click()
    
    // Settings panel should be visible
    cy.get('.cc-settings-panel').should('be.visible')
    
    // Should have category options
    cy.get('.cc-category-group').should('have.length.at.least', 1)
  })
})
EOL

    cat > cypress/e2e/analytics.cy.js << 'EOL'
/**
 * Test analytics functionality
 */
describe('Consent Analytics', () => {
  beforeEach(() => {
    cy.login()
  })

  it('should display the analytics tab', () => {
    cy.visit('/wp-admin/admin.php?page=cookie-consent-settings&tab=analytics')
    cy.get('h2').should('contain', 'Analytics & Statistics')
  })

  it('should display consent logs table', () => {
    cy.visit('/wp-admin/admin.php?page=cookie-consent-settings&tab=analytics')
    cy.get('.consent-logs-table').should('be.visible')
    cy.get('.consent-logs-table th').should('contain', 'Date')
  })

  it('should have working export button', () => {
    cy.visit('/wp-admin/admin.php?page=cookie-consent-settings&tab=analytics')
    cy.get('a.export-csv-button').should('be.visible')
    cy.get('a.export-csv-button').should('have.attr', 'href').and('include', 'action=export_logs_csv')
  })
})
EOL
fi

# Configuration
WP_VERSION=${1:-latest}
PHP_VERSION=${2:-"7.4"}
WP_MULTISITE=${3:-0}
PLUGIN_DIR="$(basename "$(pwd)")"
WP_TEST_URL="http://devora.local"

# Add integration with WP-CLI for testing
if [ ! -f "tools/run-e2e-tests.sh" ]; then
    echo -e "${YELLOW}Creating E2E test runner script...${NC}"
    cat > tools/run-e2e-tests.sh << 'EOL'
#!/bin/bash

# E2E Testing Runner Script
# This script sets up a WordPress test environment and runs Cypress E2E tests

# Set up test WordPress environment using WP-CLI
echo "Setting up test WordPress environment..."

# Check if WordPress test environment is available
if ! wp core is-installed --path=/tmp/wordpress &> /dev/null; then
    echo "WordPress test environment not found. Setting up..."
    
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
    PLUGIN_DIR="$(basename "$(pwd)")"
    if [ ! -d "/tmp/wordpress/wp-content/plugins/$PLUGIN_DIR" ]; then
        ln -s "$(pwd)" /tmp/wordpress/wp-content/plugins/
    fi
    wp plugin activate "$PLUGIN_DIR" --path=/tmp/wordpress
fi

# Start PHP server
echo "Starting PHP server..."
php -S 127.0.0.1:8000 -t /tmp/wordpress &
SERVER_PID=$!

# Configure hosts file
echo "Setting up hosts file..."
if ! grep -q "devora.local" /etc/hosts; then
    echo "127.0.0.1 devora.local" | sudo tee -a /etc/hosts > /dev/null
fi

sleep 2

# Run Cypress tests
echo "Running E2E tests with Cypress..."
CYPRESS_baseUrl=http://devora.local npm run cypress:run

# Capture the exit code of Cypress
CYPRESS_EXIT_CODE=$?

# Kill the PHP server
kill $SERVER_PID

# Exit with Cypress exit code
exit $CYPRESS_EXIT_CODE
EOL
    chmod +x tools/run-e2e-tests.sh
fi

# Update composer.json to include E2E testing commands
if ! grep -q "\"test:e2e\"" composer.json; then
    echo -e "${YELLOW}Adding E2E testing commands to composer.json...${NC}"
    # Add test:e2e script
    sed -i.bak '/"scripts": {/a \
    "test:e2e": "tools\/run-e2e-tests.sh",' composer.json
    rm composer.json.bak
fi

# Create a basic cypress.json file if it doesn't exist
if [ ! -f "./cypress.json" ]; then
    echo "Creating cypress.json file..."
    cat > ./cypress.json << 'EOL'
{
    "baseUrl": "http://devora.local/wp-admin",
    "viewportWidth": 1280,
    "viewportHeight": 800,
    "chromeWebSecurity": false,
    "video": false
}
EOL
fi

echo -e "\n${GREEN}E2E Testing environment setup complete!${NC}"
echo -e "${YELLOW}Commands to run E2E tests:${NC}"
echo -e "  ${GREEN}npm run cypress:open${NC} - Open Cypress interactive mode"
echo -e "  ${GREEN}npm run cypress:run${NC} - Run Cypress tests in headless mode"
echo -e "  ${GREEN}composer run test:e2e${NC} - Run E2E tests with WordPress test environment"

echo -e "\n${GREEN}Happy testing!${NC}"
exit 0 