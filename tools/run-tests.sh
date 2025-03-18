#!/bin/bash

# Run Tests Script - Runs all tests and performs automatic fixing

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[0;33m'
BLUE='\033[0;34m'
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

# Store failures
FAILURES=()
AUTO_FIXES=()

echo -e "${BLUE}Running WordPress Plugin Tests${NC}"
echo -e "${BLUE}==============================${NC}\n"

# Run PHPCS (PHP CodeSniffer) first
echo -e "${YELLOW}Running PHP CodeSniffer...${NC}"
"$PHP_CMD" vendor/bin/phpcs --standard=WordPress --report=summary --ignore=vendor/,node_modules/,tests/ .
PHPCS_EXIT_CODE=$?

if [ $PHPCS_EXIT_CODE -ne 0 ]; then
    echo -e "${RED}PHPCS found issues. Attempting to auto-fix...${NC}"
    "$PHP_CMD" vendor/bin/phpcbf --standard=WordPress --ignore=vendor/,node_modules/,tests/ .
    AUTO_FIXES+=("Applied PHPCBF fixes for coding standards")
    
    # Run PHPCS again to see if there are remaining issues
    "$PHP_CMD" vendor/bin/phpcs --standard=WordPress --report=summary --ignore=vendor/,node_modules/,tests/ .
    PHPCS_EXIT_CODE=$?
    
    if [ $PHPCS_EXIT_CODE -ne 0 ]; then
        FAILURES+=("PHPCS found issues that could not be automatically fixed")
    else
        echo -e "${GREEN}All PHPCS issues fixed automatically!${NC}"
    fi
else
    echo -e "${GREEN}No PHPCS issues found!${NC}"
fi

# Run PHPMD (PHP Mess Detector) if installed
if [ -f ./vendor/bin/phpmd ]; then
    echo -e "\n${YELLOW}Running PHP Mess Detector...${NC}"
    "$PHP_CMD" vendor/bin/phpmd . text ./tools/phpmd-ruleset.xml --exclude vendor/,node_modules/,tests/
    PHPMD_EXIT_CODE=$?
    
    if [ $PHPMD_EXIT_CODE -ne 0 ]; then
        FAILURES+=("PHPMD found issues in the code")
    else
        echo -e "${GREEN}No PHPMD issues found!${NC}"
    fi
fi

# Run custom auto-fix script
echo -e "\n${YELLOW}Running advanced auto-fix script...${NC}"
"$PHP_CMD" ./tools/auto-fix.php
AUTOFIX_EXIT_CODE=$?

if [ $AUTOFIX_EXIT_CODE -ne 0 ]; then
    AUTO_FIXES+=("Applied some automated fixes, but manual attention is needed")
else
    AUTO_FIXES+=("Applied advanced auto-fixes")
    echo -e "${GREEN}Auto-fix completed successfully!${NC}"
fi

# Run PHPUnit tests
echo -e "\n${YELLOW}Running PHPUnit tests...${NC}"
"$PHP_CMD" vendor/bin/phpunit --testdox
PHPUNIT_EXIT_CODE=$?

if [ $PHPUNIT_EXIT_CODE -ne 0 ]; then
    FAILURES+=("PHPUnit tests failed")
else
    echo -e "${GREEN}All PHPUnit tests passed!${NC}"
fi

# Run accessibility tests if the script exists
if [ -f ./tools/run-a11y-tests.sh ]; then
    echo -e "\n${YELLOW}Running accessibility tests...${NC}"
    bash ./tools/run-a11y-tests.sh
    A11Y_EXIT_CODE=$?
    
    if [ $A11Y_EXIT_CODE -ne 0 ]; then
        FAILURES+=("Accessibility tests failed")
    else
        echo -e "${GREEN}All accessibility tests passed!${NC}"
    fi
fi

# Check if Cypress is set up for E2E tests
if [ -d "./cypress" ]; then
    echo -e "\n${YELLOW}E2E tests are set up but not run automatically here.${NC}"
    echo -e "${YELLOW}Run 'composer run test:e2e' to execute E2E tests separately.${NC}"
else
    echo -e "\n${YELLOW}E2E tests are not set up. Run 'composer run setup-e2e' to set up Cypress.${NC}"
fi

# Show summary
echo -e "\n${BLUE}Test Summary${NC}"
echo -e "${BLUE}============${NC}"

if [ ${#FAILURES[@]} -eq 0 ]; then
    echo -e "${GREEN}All tests passed successfully!${NC}"
else
    echo -e "${RED}The following tests failed:${NC}"
    for i in "${FAILURES[@]}"; do
        echo -e "  ${RED}- $i${NC}"
    done
fi

if [ ${#AUTO_FIXES[@]} -gt 0 ]; then
    echo -e "\n${YELLOW}Applied fixes:${NC}"
    for i in "${AUTO_FIXES[@]}"; do
        echo -e "  ${YELLOW}- $i${NC}"
    done
fi

# Determine exit code
if [ ${#FAILURES[@]} -eq 0 ]; then
    echo -e "\n${GREEN}✓ All tests completed successfully!${NC}"
    exit 0
else
    echo -e "\n${RED}✗ Some tests failed. Please fix the issues and try again.${NC}"
    exit 1
fi 