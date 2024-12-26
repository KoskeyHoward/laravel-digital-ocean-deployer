<?php

namespace Koskey\LaravelDigitalOceanDeployer;

use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Log;

class Deployer
{
    protected array $config;
    protected array $serverConfig;
    protected $logger;
    protected const TIMEOUT = 300; // 5 minutes timeout

    public function __construct()
    {
        $this->config = config('deployer');
        $this->serverConfig = $this->getServerConfig();
    }

    protected function getServerConfig(): array
    {
        // Check if we're running in GitHub Actions
        $isGitHubActions = isset($_SERVER['GITHUB_ACTIONS']) && $_SERVER['GITHUB_ACTIONS'] === 'true';

        if ($isGitHubActions) {
            $this->log('Running in GitHub Actions environment');
            return [
                'host' => $_SERVER['DO_HOST'] ?? null,
                'username' => $_SERVER['DO_USERNAME'] ?? null,
                'ssh_key' => $_SERVER['DO_SSH_KEY'] ?? null,
                'path' => $_SERVER['DO_PATH'] ?? '/var/www/html',
            ];
        }

        // Running locally, use Laravel environment variables
        $this->log('Running in local environment');
        return [
            'host' => env('DO_HOST'),
            'username' => env('DO_USERNAME'),
            'ssh_key' => env('DO_SSH_KEY'),
            'path' => env('DO_PATH', '/var/www/html'),
        ];
    }

    public function deploy(?callable $logger = null): bool
    {
        $this->logger = $logger;
        $this->log('Starting deployment process...');

        try {
            // Validate required environment variables
            $this->validateEnvironment();

            // Test SSH connection before proceeding
            $this->testSSHConnection();

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

    protected function testSSHConnection(): void
    {
        $this->log('Testing SSH connection...');
        
        $host = $this->serverConfig['host'];
        $user = $this->serverConfig['username'];
        
        // Setup SSH key first
        $this->setupSSH();
        
        // Test connection with a simple command
        $command = "ssh -o StrictHostKeyChecking=no -i ~/.ssh/deploy_key {$user}@{$host} 'echo \"SSH connection successful\"'";
        
        try {
            $result = Process::timeout(30)->run($command);
            
            if (!$result->successful()) {
                throw new \RuntimeException(
                    "Failed to connect to server: {$result->errorOutput()}\n" .
                    "Please verify your SSH credentials and server availability."
                );
            }
            
            $this->log('SSH connection test successful');
        } catch (\Exception $e) {
            throw new \RuntimeException(
                "SSH connection failed: {$e->getMessage()}\n" .
                "Please check:\n" .
                "1. Server is accessible\n" .
                "2. SSH credentials are correct\n" .
                "3. Firewall settings allow SSH access"
            );
        }
    }

    protected function validateEnvironment(): void
    {
        $this->log('Validating environment variables...');
        $missing = [];

        foreach (['host', 'username', 'ssh_key', 'path'] as $key) {
            if (empty($this->serverConfig[$key])) {
                $missing[] = 'DO_' . strtoupper($key);
            }
        }

        if (!empty($missing)) {
            throw new \RuntimeException(
                'Missing required environment variables: ' . implode(', ', $missing) . 
                '. Please set these in your .env file for local development or in GitHub secrets for deployment.'
            );
        }
        $this->log('Environment validation successful');
    }

    protected function runCommand(string $command): void
    {
        $result = Process::timeout(self::TIMEOUT)->run($command);
        
        if (!$result->successful()) {
            throw new \RuntimeException(
                "Command failed: {$result->errorOutput()}\n" .
                "Command: {$command}"
            );
        }

        if ($this->logger && $result->output()) {
            $this->log($result->output());
        }
    }

    protected function runSSHCommand(string $command): void
    {
        $host = $this->serverConfig['host'];
        $user = $this->serverConfig['username'];
        
        $sshCommand = "ssh -o StrictHostKeyChecking=no -i ~/.ssh/deploy_key {$user}@{$host} '{$command}'";
        $this->runCommand($sshCommand);
    }

    protected function pullCode(): self
    {
        $this->log('Pulling latest code from repository...');
        
        $path = $this->serverConfig['path'];
        $branch = $this->config['repository']['branch'];

        $command = "cd {$path} && git fetch --all && git reset --hard origin/{$branch}";
        $this->runSSHCommand($command);
        
        $this->log('Code pull completed');
        return $this;
    }

    protected function runDeploymentSteps(): self
    {
        $this->log('Running deployment steps...');
        
        $steps = $this->config['steps'];
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

        if (!empty($commands)) {
            $commandString = "cd {$path} && " . implode(' && ', $commands);
            $this->runSSHCommand($commandString);
        }
        
        $this->log('Deployment steps completed');
        return $this;
    }

    protected function setPermissions(): self
    {
        $this->log('Setting directory permissions...');
        
        $path = $this->serverConfig['path'];
        $command = "cd {$path} && chmod -R 775 storage bootstrap/cache";
        
        $this->runSSHCommand($command);
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

    protected function log(string $message, string $level = 'info'): void
    {
        if ($this->logger) {
            ($this->logger)($message);
        }
        Log::$level($message);
    }
}
