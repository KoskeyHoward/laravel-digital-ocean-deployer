# Changelog

All notable changes to `laravel-digital-ocean-deployer` will be documented in this file.

## [1.2.2] - 2024-12-26

### Changed
- Use `$_SERVER` instead of `env()` for accessing GitHub Actions secrets
- Improved environment variable handling in GitHub Actions
- Updated config file structure - requires republishing config (`php artisan vendor:publish --provider="Koskey\LaravelDigitalOceanDeployer\DeployerServiceProvider" --tag="config" --force`)

## [1.2.1] - 2024-12-26

### Added
- SSH key configuration in `config/deployer.php`

### Changed
- Improved SSH key handling in deployment process
- Better error handling for SSH operations
- Simplified permissions management

## [1.2.0] - 2024-12-26

### Added
- New `deployer:publish-workflow` command to generate GitHub workflow with configured branch
- Configurable deployment branch through `config/deployer.php`

### Changed
- GitHub workflow now uses branch from configuration
- Updated documentation for workflow publishing

## [1.1.0] - 2024-12-26

### Added
- New `deployer:generate-key` command to easily generate base64 encoded SSH keys
- Support for Laravel 9 alongside Laravel 10
- Automatic SSH key generation if none exists
- Display of both encoded private key and public key

### Changed
- Simplified SSH key setup process
- Updated documentation with clearer instructions
- Improved error handling and user feedback

## [1.0.0] - 2024-12-26

### Initial Release
- Basic deployment functionality
- GitHub Actions workflow integration
- Configuration file publishing
- Deploy command
- Basic documentation
