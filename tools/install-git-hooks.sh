#!/bin/bash

# Colors
GREEN='\033[0;32m'
BLUE='\033[0;34m'
RED='\033[0;31m'
NC='\033[0m' # No Color

echo -e "${BLUE}Installing Custom Cookie Consent git hooks...${NC}"

# Create hooks directory if it doesn't exist
mkdir -p .git/hooks

# Copy pre-commit hook
echo -e "${BLUE}Installing pre-commit hook...${NC}"
cp "tools/git-hooks/pre-commit" ".git/hooks/pre-commit"
chmod +x ".git/hooks/pre-commit"

# Make all hooks in tools/git-hooks executable
chmod +x tools/git-hooks/*

echo -e "${GREEN}Git hooks installed successfully!${NC}"
echo "The following hooks have been installed:"
echo "- pre-commit: Runs PHP CodeSniffer and PHPUnit tests before each commit" 