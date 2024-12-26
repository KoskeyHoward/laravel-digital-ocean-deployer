# Laravel DigitalOcean Deployer

A Laravel package for automated deployment to DigitalOcean servers using GitHub Actions.

## Installation

You can install the package via composer:

```bash
composer require koskey/laravel-digital-ocean-deployer
```

## Configuration

1. Publish the configuration file:

```bash
php artisan vendor:publish --provider="Koskey\LaravelDigitalOceanDeployer\DeployerServiceProvider" --tag="config"
```

2. Publish the GitHub workflow:

```bash
php artisan vendor:publish --provider="Koskey\LaravelDigitalOceanDeployer\DeployerServiceProvider" --tag="github-workflow"
```

3. Add the following environment variables to your GitHub repository secrets:

- `DO_HOST`: Your DigitalOcean server hostname
- `DO_USERNAME`: Your server username
- `DO_SSH_KEY`: Your SSH private key (base64 encoded)
- `DO_PATH`: Your application path on the server (default: /var/www/html)

## Usage

The package will automatically deploy your application when you push to the main branch. You can also manually deploy using the artisan command:

```bash
php artisan deploy
```

## Configuration Options

You can customize the deployment process by editing the `config/deployer.php` file:

- Server configuration
- Repository settings
- Deployment steps
- Custom hooks
- File permissions

## Contributing

Please see [CONTRIBUTING.md](CONTRIBUTING.md) for details.

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
