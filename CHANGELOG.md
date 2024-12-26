# Changelog

All notable changes to `laravel-digital-ocean-deployer` will be documented in this file.

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
