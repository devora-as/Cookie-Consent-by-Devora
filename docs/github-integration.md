# GitHub Integration for Automatic WordPress Plugin Updates

This document explains how to set up your GitHub repository to enable automatic updates for the Custom Cookie Consent plugin.

## Prerequisites

- A GitHub account
- Git installed on your local machine
- The Custom Cookie Consent plugin files

## Initial Setup

### 1. Update the Plugin Header

The plugin header in `cookie-consent.php` contains GitHub-specific fields that control the update process. Update the following lines with your GitHub username:

```php
 * GitHub Plugin URI: YOUR_USERNAME/custom-cookie-consent
 * GitHub Plugin URI: https://github.com/YOUR_USERNAME/custom-cookie-consent
```

### 2. Create a GitHub Repository

1. Log in to your GitHub account
2. Click the "+" icon in the top-right corner and select "New repository"
3. Name it `custom-cookie-consent`
4. Choose "Public" or "Private" (private repositories require an access token)
5. Click "Create repository"

### 3. Push Your Plugin Code

Follow these steps to push your code to GitHub:

```bash
# Navigate to your plugin directory
cd /path/to/wp-content/plugins/custom-cookie-consent

# Initialize git repository
git init

# Add all files
git add .

# Commit the files
git commit -m "Initial commit"

# Add the remote repository
git remote add origin https://github.com/YOUR_USERNAME/custom-cookie-consent.git

# Push to GitHub
git push -u origin main
```

## Creating Releases

WordPress will check for new versions by looking at GitHub releases. To create a release:

1. On GitHub, navigate to your repository
2. Click on "Releases" in the right sidebar
3. Click "Create a new release"
4. Set the tag version to match your plugin version (e.g., "1.2.0")
5. Add a title (e.g., "Version 1.2.0")
6. Add a description listing changes in this version
7. Optionally attach a ZIP file of your plugin
   - If `Release Asset: true` is in your plugin header, the updater will use this ZIP file
   - If not, it will use the source code
8. Click "Publish release"

## Release Process Workflow

When releasing a new version of the plugin:

1. Update the version number in:
   - `cookie-consent.php` plugin header
   - `CUSTOM_COOKIE_VERSION` constant
2. Update the changelog in the README.md file
3. Commit and push changes to GitHub
4. Create a new release on GitHub with the matching version number

## Testing Updates

To test if the update system works correctly:

1. Install the plugin on a test WordPress site
2. Create a new release on GitHub with a higher version number
3. Go to WordPress > Dashboard > Updates
4. WordPress should detect the new version
5. Update and verify the new version is installed correctly

## Private Repositories

If your repository is private, you need to add an access token:

1. Go to GitHub > Settings > Developer settings > Personal access tokens
2. Generate a new token with the `repo` scope
3. Add this to your WordPress site's wp-config.php:

```php
define('GITHUB_ACCESS_TOKEN', 'your_github_token_here');
```

## Troubleshooting

If updates are not working:

1. **Check version numbers**: Ensure the plugin version and GitHub release tag match exactly
2. **Verify headers**: Make sure the GitHub plugin URI headers are correct
3. **Clean transients**: Try clearing the WordPress transients database
4. **Enable debugging**: Add this to wp-config.php:
   ```php
   define('WP_DEBUG', true);
   define('WP_DEBUG_LOG', true);
   ```
5. **Check access**: For private repositories, verify your access token has the correct permissions

## Security Considerations

- Be cautious with access tokens—they grant access to your repositories
- Consider using branch protection rules on GitHub for your main branch
- Review code changes carefully before creating releases
- Use a staging environment to test updates before applying to production sites
