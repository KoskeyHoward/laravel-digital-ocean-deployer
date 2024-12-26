<?php

namespace Koskey\LaravelDigitalOceanDeployer;

use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Log;

class Deployer
{
    protected array $config;
    protected array $serverConfig;

    public function __construct()
    {
        $this->config = config('deployer');
        $this->serverConfig = [
            'host' => env('DO_HOST'),
            'username' => env('DO_USERNAME'),
            'ssh_key' => env('DO_SSH_KEY'),
            'path' => env('DO_PATH', '/var/www/html'),
        ];
    }

    public function deploy(): bool
    {
        try {
            // Validate required environment variables
            $this->validateEnvironment();

            $this->runBeforeHooks()
                 ->setupSSH()
                 ->pullCode()
                 ->runDeploymentSteps()
                 ->setPermissions()
                 ->runAfterHooks();

            return true;
        } catch (\Exception $e) {
            Log::error('Deployment failed: ' . $e->getMessage());
            return false;
        }
    }

    protected function validateEnvironment(): void
    {
        $required = ['DO_HOST', 'DO_USERNAME', 'DO_SSH_KEY', 'DO_PATH'];
        $missing = [];

        foreach ($required as $var) {
            if (empty(env($var))) {
                $missing[] = $var;
            }
        }

        if (!empty($missing)) {
            throw new \RuntimeException(
                'Missing required GitHub secrets: ' . implode(', ', $missing)
            );
        }
    }

    protected function setupSSH(): self
    {
        // Setup SSH key and known hosts
        Process::run('mkdir -p ~/.ssh/');
        Process::run("echo '{$this->serverConfig['ssh_key']}' | base64 -d > ~/.ssh/deploy_key");
        Process::run('chmod 600 ~/.ssh/deploy_key');
        Process::run('eval "$(ssh-agent -s)"');
        Process::run('ssh-add ~/.ssh/deploy_key');
        Process::run("ssh-keyscan -H {$this->serverConfig['host']} >> ~/.ssh/known_hosts");

        return $this;
    }

    protected function pullCode(): self
    {
        $host = $this->serverConfig['host'];
        $user = $this->serverConfig['username'];
        $path = $this->serverConfig['path'];
        $branch = $this->config['repository']['branch'];

        $command = "ssh -o StrictHostKeyChecking=no -i ~/.ssh/deploy_key {$user}@{$host} '
            cd {$path} &&
            git fetch --all &&
            git reset --hard origin/{$branch}
        '";

        Process::run($command);

        return $this;
    }

    protected function runDeploymentSteps(): self
    {
        $steps = $this->config['steps'];
        $host = $this->serverConfig['host'];
        $user = $this->serverConfig['username'];
        $path = $this->serverConfig['path'];

        $commands = [];

        if ($steps['composer_install']) {
            $commands[] = 'composer install --no-interaction --prefer-dist --optimize-autoloader';
        }

        if ($steps['npm_install']) {
            $commands[] = 'npm install';
        }

        if ($steps['npm_build']) {
            $commands[] = 'npm run build';
        }

        if ($steps['artisan_migrate']) {
            $commands[] = 'php artisan migrate --force';
        }

        if ($steps['artisan_storage_link']) {
            $commands[] = 'php artisan storage:link';
        }

        if ($steps['artisan_cache_clear']) {
            $commands[] = 'php artisan cache:clear';
        }

        if ($steps['artisan_config_cache']) {
            $commands[] = 'php artisan config:cache';
        }

        if ($steps['artisan_route_cache']) {
            $commands[] = 'php artisan route:cache';
        }

        if ($steps['artisan_view_cache']) {
            $commands[] = 'php artisan view:cache';
        }

        $commandString = implode(' && ', $commands);
        $sshCommand = "ssh -o StrictHostKeyChecking=no -i ~/.ssh/deploy_key {$user}@{$host} 'cd {$path} && {$commandString}'";
        
        Process::run($sshCommand);

        return $this;
    }

    protected function setPermissions(): self
    {
        $host = $this->serverConfig['host'];
        $user = $this->serverConfig['username'];
        $path = $this->serverConfig['path'];

        $command = "ssh -o StrictHostKeyChecking=no -i ~/.ssh/deploy_key {$user}@{$host} '
            cd {$path} &&
            chmod -R 775 storage bootstrap/cache
        '";

        Process::run($command);

        return $this;
    }

    protected function runBeforeHooks(): self
    {
        if (isset($this->config['hooks']['before'])) {
            foreach ($this->config['hooks']['before'] as $command) {
                Process::run($command);
            }
        }

        return $this;
    }

    protected function runAfterHooks(): self
    {
        if (isset($this->config['hooks']['after'])) {
            foreach ($this->config['hooks']['after'] as $command) {
                Process::run($command);
            }
        }

        return $this;
    }

    public function getOutput(): array
    {
        return [];
    }
}
