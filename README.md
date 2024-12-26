# Laravel DigitalOcean Deployer

A Laravel package for automated deployment to DigitalOcean servers using GitHub Actions.

## Installation

You can install the package via composer:

```bash
composer require koskey/laravel-digital-ocean-deployer
```

## Updating

To get the latest updates, run:

```bash
composer update koskey/laravel-digital-ocean-deployer
```

After updating, check the [CHANGELOG.md](CHANGELOG.md) for any breaking changes or new features.

If you've updated from version 1.0.0, you now have access to the new `deployer:generate-key` command for easier SSH key setup.

## Configuration

1. Publish the configuration file:

```bash
php artisan vendor:publish --provider="Koskey\LaravelDigitalOceanDeployer\DeployerServiceProvider" --tag="config"
```

2. Publish the GitHub workflow:

```bash
php artisan vendor:publish --provider="Koskey\LaravelDigitalOceanDeployer\DeployerServiceProvider" --tag="config"
```

Edit the `config/deployer.php` file to set your preferred branch name:

```php
'repository' => [
    'provider' => 'github',
    'branch' => 'master', // Change this to your desired branch
],
```

Then publish the GitHub workflow with your configured branch:

```bash
php artisan deployer:publish-workflow
```

3. Add the following environment variables to your GitHub repository secrets:

- `DO_HOST`: Your DigitalOcean server hostname
- `DO_USERNAME`: Your server username
- `DO_SSH_KEY`: Your SSH private key (base64 encoded)
- `DO_PATH`: Your application path on the server (default: /var/www/html)

### Getting Your Base64 Encoded SSH Key

The package provides an easy way to get your base64 encoded SSH key. After installation, run:

```bash
php artisan deployer:generate-key
```

This command will:
1. Find your SSH private key (usually in `~/.ssh/id_rsa`)
2. Convert it to base64 format
3. Display both the encoded private key (for GitHub) and the public key (for your server)
4. If no SSH key is found, it can generate a new key pair for you

You can also specify a custom path to your SSH key:
```bash
php artisan deployer:generate-key --path=/path/to/your/key
```

Add the base64 encoded output as the value for `DO_SSH_KEY` in your GitHub repository secrets.

Note: Make sure the corresponding public key is added to your DigitalOcean server's `~/.ssh/authorized_keys` file.

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
