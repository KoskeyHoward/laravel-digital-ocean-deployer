<?php

namespace Koskey\LaravelDigitalOceanDeployer;

use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Log;

class Deployer
{
    protected array $config;
    protected array $serverConfig;
    protected $logger;

    public function __construct()
    {
        $this->config = config('deployer');
        $this->serverConfig = [
            'host' => $_SERVER['DO_HOST'] ?? null,
            'username' => $_SERVER['DO_USERNAME'] ?? null,
            'ssh_key' => $_SERVER['DO_SSH_KEY'] ?? null,
            'path' => $_SERVER['DO_PATH'] ?? '/var/www/html',
        ];
    }

    public function deploy(?callable $logger = null): bool
    {
        $this->logger = $logger;
        $this->log('Starting deployment process...');

        try {
            // Validate required environment variables
            $this->validateEnvironment();

            $this->runBeforeHooks()
                 ->setupSSH()
                 ->pullCode()
                 ->runDeploymentSteps()
                 ->setPermissions()
                 ->runAfterHooks();

            $this->log('Deployment completed successfully!');
            return true;
        } catch (\Exception $e) {
            $error = 'Deployment failed: ' . $e->getMessage();
            $this->log($error, 'error');
            Log::error($error);
            if ($this->logger) {
                $this->log('Stack trace:');
                $this->log($e->getTraceAsString());
            }
            return false;
        }
    }

    protected function validateEnvironment(): void
    {
        $this->log('Validating environment variables...');
        $required = ['DO_HOST', 'DO_USERNAME', 'DO_SSH_KEY', 'DO_PATH'];
        $missing = [];

        foreach ($required as $var) {
            if (empty($_SERVER[$var])) {
                $missing[] = $var;
            }
        }

        if (!empty($missing)) {
            throw new \RuntimeException(
                'Missing required GitHub secrets: ' . implode(', ', $missing)
            );
        }
        $this->log('Environment validation successful');
    }

    protected function setupSSH(): self
    {
        $this->log('Setting up SSH connection...');
        
        // Setup SSH key and known hosts
        $this->runCommand('mkdir -p ~/.ssh/');
        $this->runCommand("echo '{$this->serverConfig['ssh_key']}' | base64 -d > ~/.ssh/deploy_key");
        $this->runCommand('chmod 600 ~/.ssh/deploy_key');
        $this->runCommand('eval "$(ssh-agent -s)"');
        $this->runCommand('ssh-add ~/.ssh/deploy_key');
        $this->runCommand("ssh-keyscan -H {$this->serverConfig['host']} >> ~/.ssh/known_hosts");

        $this->log('SSH setup completed');
        return $this;
    }

    protected function pullCode(): self
    {
        $this->log('Pulling latest code from repository...');
        
        $host = $this->serverConfig['host'];
        $user = $this->serverConfig['username'];
        $path = $this->serverConfig['path'];
        $branch = $this->config['repository']['branch'];

        $command = "ssh -o StrictHostKeyChecking=no -i ~/.ssh/deploy_key {$user}@{$host} '
            cd {$path} &&
            git fetch --all &&
            git reset --hard origin/{$branch}
        '";

        $this->runCommand($command);
        $this->log('Code pull completed');
        return $this;
    }

    protected function runDeploymentSteps(): self
    {
        $this->log('Running deployment steps...');
        
        $steps = $this->config['steps'];
        $host = $this->serverConfig['host'];
        $user = $this->serverConfig['username'];
        $path = $this->serverConfig['path'];

        $commands = [];

        if ($steps['composer_install']) {
            $this->log('Running composer install...');
            $commands[] = 'composer install --no-interaction --prefer-dist --optimize-autoloader';
        }

        if ($steps['npm_install']) {
            $this->log('Running npm install...');
            $commands[] = 'npm install';
        }

        if ($steps['npm_build']) {
            $this->log('Running npm build...');
            $commands[] = 'npm run build';
        }

        if ($steps['artisan_migrate']) {
            $this->log('Running database migrations...');
            $commands[] = 'php artisan migrate --force';
        }

        if ($steps['artisan_storage_link']) {
            $this->log('Creating storage link...');
            $commands[] = 'php artisan storage:link';
        }

        if ($steps['artisan_cache_clear']) {
            $this->log('Clearing application cache...');
            $commands[] = 'php artisan cache:clear';
        }

        if ($steps['artisan_config_cache']) {
            $this->log('Caching configuration...');
            $commands[] = 'php artisan config:cache';
        }

        if ($steps['artisan_route_cache']) {
            $this->log('Caching routes...');
            $commands[] = 'php artisan route:cache';
        }

        if ($steps['artisan_view_cache']) {
            $this->log('Caching views...');
            $commands[] = 'php artisan view:cache';
        }

        $commandString = implode(' && ', $commands);
        $sshCommand = "ssh -o StrictHostKeyChecking=no -i ~/.ssh/deploy_key {$user}@{$host} 'cd {$path} && {$commandString}'";
        
        $this->runCommand($sshCommand);
        $this->log('Deployment steps completed');
        return $this;
    }

    protected function setPermissions(): self
    {
        $this->log('Setting directory permissions...');
        
        $host = $this->serverConfig['host'];
        $user = $this->serverConfig['username'];
        $path = $this->serverConfig['path'];

        $command = "ssh -o StrictHostKeyChecking=no -i ~/.ssh/deploy_key {$user}@{$host} '
            cd {$path} &&
            chmod -R 775 storage bootstrap/cache
        '";

        $this->runCommand($command);
        $this->log('Permissions set successfully');
        return $this;
    }

    protected function runBeforeHooks(): self
    {
        if (isset($this->config['hooks']['before'])) {
            $this->log('Running before hooks...');
            foreach ($this->config['hooks']['before'] as $command) {
                $this->runCommand($command);
            }
            $this->log('Before hooks completed');
        }

        return $this;
    }

    protected function runAfterHooks(): self
    {
        if (isset($this->config['hooks']['after'])) {
            $this->log('Running after hooks...');
            foreach ($this->config['hooks']['after'] as $command) {
                $this->runCommand($command);
            }
            $this->log('After hooks completed');
        }

        return $this;
    }

    protected function runCommand(string $command): void
    {
        $result = Process::run($command);
        
        if (!$result->successful()) {
            throw new \RuntimeException(
                "Command failed: {$result->errorOutput()}"
            );
        }

        if ($this->logger && $result->output()) {
            $this->log($result->output());
        }
    }

    protected function log(string $message, string $level = 'info'): void
    {
        if ($this->logger) {
            ($this->logger)($message);
        }
        Log::$level($message);
    }
}
