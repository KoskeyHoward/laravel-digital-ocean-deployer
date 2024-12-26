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

### Getting Your Base64 Encoded SSH Key

To get your base64 encoded SSH private key, follow these steps:

1. Locate your SSH private key (usually in `~/.ssh/id_rsa`):
```bash
cat ~/.ssh/id_rsa
```

2. Convert it to base64:
```bash
cat ~/.ssh/id_rsa | base64
```

3. Copy the entire output (it should be one long string without line breaks)

4. Add this base64 encoded string as the value for `DO_SSH_KEY` in your GitHub repository secrets

Note: Make sure you're using the private key that has access to your DigitalOcean server. If you haven't set up SSH keys yet, you can create them using:
```bash
ssh-keygen -t rsa -b 4096
```

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
