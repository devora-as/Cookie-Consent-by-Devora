# Contributing to Cookie Consent by Devora

Thank you for considering contributing to Cookie Consent by Devora! This guide outlines how to contribute to the project and how to properly set up your development environment.

## Development Setup

1. **Clone the repository**:

   ```
   git clone https://github.com/devora-as/Cookie-Consent-by-Devora.git
   ```

2. **Set up a local WordPress environment**:

   - You can use Local by Flywheel, XAMPP, or any other local WordPress setup
   - Set up a WordPress development environment
   - Symlink or copy the plugin to your wp-content/plugins directory

3. **Activate the plugin** in your WordPress admin area

## Coding Standards

This project follows the WordPress Coding Standards. Please ensure your code adheres to these standards:

- [WordPress PHP Coding Standards](https://developer.wordpress.org/coding-standards/wordpress-coding-standards/php/)
- [WordPress JavaScript Coding Standards](https://developer.wordpress.org/coding-standards/wordpress-coding-standards/javascript/)
- [WordPress CSS Coding Standards](https://developer.wordpress.org/coding-standards/wordpress-coding-standards/css/)

## Pull Request Process

1. Create a new branch for your feature or bugfix
2. Write your code and test it thoroughly
3. Update the README.md and/or documentation as needed
4. Make sure all tests pass
5. Submit a pull request to the `develop` branch

Please include a clear description of the changes you've made in your pull request.

## Release Process

The plugin uses semantic versioning (MAJOR.MINOR.PATCH):

- MAJOR: Incompatible API changes
- MINOR: Backwards-compatible functionality additions
- PATCH: Backwards-compatible bug fixes

When a new release is created:

1. The version is updated in the main plugin file (`cookie-consent.php`)
2. A tag and GitHub release is created
3. The GitHub Action automatically creates the plugin zip file
4. The plugin update system notifies users of the new version

## Testing

Before submitting a pull request, please ensure:

1. Your code works with the latest version of WordPress
2. Your code is compatible with PHP 7.4+
3. All features work as expected
4. The plugin doesn't produce any errors or warnings
5. The code doesn't introduce any regressions

## Reporting Bugs

When reporting bugs, please include:

1. WordPress version
2. PHP version
3. Browser and OS details
4. Steps to reproduce the bug
5. Expected behavior
6. Actual behavior
7. Any error messages or logs

## License

By contributing to this project, you agree that your contributions will be licensed under the same [GPL v3 License](LICENSE) that covers the project.
