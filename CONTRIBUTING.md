# Contributing to AzuraCast Song History WordPress Plugin

Thank you for considering contributing to this project! Here are some guidelines to help you get started.

## How to Contribute

### Reporting Bugs

1. Check the [Issues](https://github.com/Lokke/azuracast-song-history/issues) to see if the bug has already been reported
2. If not, create a new issue using the bug report template
3. Include as much detail as possible about the bug and how to reproduce it

### Suggesting Features

1. Check existing [Issues](https://github.com/Lokke/azuracast-song-history/issues) for similar feature requests
2. Create a new issue using the feature request template
3. Describe the feature and explain why it would be useful

### Submitting Pull Requests

1. Fork the repository
2. Create a new branch for your feature or bug fix
3. Make your changes following the coding standards below
4. Test your changes thoroughly
5. Submit a pull request with a clear description of your changes

## Coding Standards

### WordPress Standards

- Follow [WordPress PHP Coding Standards](https://developer.wordpress.org/coding-standards/wordpress-coding-standards/php/)
- Use WordPress functions and hooks where appropriate
- Ensure all user input is properly sanitized and validated
- Include proper escaping for output

### PHP Standards

- Use PHP 7.4+ features appropriately
- Follow PSR-12 coding standards where they don't conflict with WordPress standards
- Document all functions and classes with proper PHPDoc comments
- Use meaningful variable and function names

### Security

- Always sanitize user input
- Escape output appropriately
- Use WordPress nonces for form submissions
- Validate and sanitize all data before database operations

### Testing

- Test with multiple WordPress versions (5.0+)
- Test with multiple PHP versions (7.4+)
- Test all admin functionality
- Test all frontend displays (widget, shortcodes)
- Test with different AzuraCast configurations

## Development Setup

1. Set up a local WordPress development environment
2. Clone this repository to your WordPress plugins directory
3. Install and configure an AzuraCast instance for testing
4. Enable WordPress debugging in wp-config.php

## License

By contributing to this project, you agree that your contributions will be licensed under the GPL v3 or later license.