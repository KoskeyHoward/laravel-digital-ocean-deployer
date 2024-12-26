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
    protected const CONNECTION_TIMEOUT = 60; // 1 minute for connection test

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

    protected function testSSHConnection(): void
    {
        $this->log('Testing SSH connection...');
        
        $host = $this->serverConfig['host'];
        $user = $this->serverConfig['username'];
        $keyFile = getenv('HOME') . '/.ssh/deploy_key';
        
        // Setup SSH key first
        $this->setupSSH();
        
        $this->log("Attempting to connect to {$user}@{$host}...");
        
        // Test connection with a simple command
        try {
            $sshCommand = "ssh -i {$keyFile} {$user}@{$host} 'echo \"SSH connection successful\"'";
            $result = Process::timeout(self::CONNECTION_TIMEOUT)->run($sshCommand);
            
            if (!$result->successful()) {
                $error = $result->errorOutput();
                $this->log("SSH connection error output: {$error}", 'error');
                throw new \RuntimeException(
                    "Failed to connect to server: {$error}\n" .
                    "Please verify your SSH credentials and server availability."
                );
            }
            
            $this->log($result->output());
            $this->log('SSH connection test successful');
        } catch (\Exception $e) {
            $this->log("SSH connection attempt failed", 'error');
            throw new \RuntimeException(
                "SSH connection failed: {$e->getMessage()}\n" .
                "Please check:\n" .
                "1. Server is accessible (try: ping {$host})\n" .
                "2. SSH port is open (try: nc -zv {$host} 22)\n" .
                "3. SSH credentials are correct\n" .
                "4. Firewall settings allow SSH access\n" .
                "5. The SSH key is properly formatted and base64 encoded"
            );
        }
    }

    protected function setupSSH(): self
    {
        $this->log('Setting up SSH connection...');
        
        // Setup SSH directory and files
        $sshDir = getenv('HOME') . '/.ssh';
        $keyFile = "{$sshDir}/deploy_key";
        $configFile = "{$sshDir}/config";
        $knownHostsFile = "{$sshDir}/known_hosts";
        
        $this->runCommand("mkdir -p {$sshDir}");
        
        // Save SSH key
        $sshKey = $this->serverConfig['ssh_key'];
        if (empty($sshKey)) {
            throw new \RuntimeException("SSH key is empty. Please check your environment variables.");
        }
        
        $this->log("Writing SSH key to {$keyFile}...");
        if (file_put_contents($keyFile, base64_decode($sshKey)) === false) {
            throw new \RuntimeException("Failed to write SSH key file");
        }
        $this->runCommand("chmod 600 {$keyFile}");
        
        // Create SSH config
        $host = $this->serverConfig['host'];
        $user = $this->serverConfig['username'];
        $sshConfig = "Host {$host}\n" .
                    "    HostName {$host}\n" .
                    "    User {$user}\n" .
                    "    IdentityFile {$keyFile}\n" .
                    "    StrictHostKeyChecking no\n" .
                    "    UserKnownHostsFile {$knownHostsFile}\n" .
                    "    ConnectTimeout 60\n" .
                    "    ServerAliveInterval 30\n" .
                    "    ServerAliveCountMax 4\n" .
                    "    ControlMaster auto\n" .
                    "    ControlPath {$sshDir}/control-%h-%p-%r\n" .
                    "    ControlPersist 600\n";
        
        $this->log("Creating SSH config...");
        if (file_put_contents($configFile, $sshConfig) === false) {
            throw new \RuntimeException("Failed to write SSH config file");
        }
        $this->runCommand("chmod 600 {$configFile}");
        
        // Add server to known hosts
        $this->log("Adding {$host} to known hosts...");
        $this->runCommand("ssh-keyscan -H {$host} >> {$knownHostsFile} 2>/dev/null || true");
        
        $this->log('SSH setup completed');
        return $this;
    }

    protected function runSSHCommand(string $command): void
    {
        $host = $this->serverConfig['host'];
        $user = $this->serverConfig['username'];
        $keyFile = getenv('HOME') . '/.ssh/deploy_key';
        
        $sshCommand = "ssh -i {$keyFile} {$user}@{$host} '{$command}'";
        $this->runCommand($sshCommand);
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
