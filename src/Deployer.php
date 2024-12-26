<?php

namespace Koskey\LaravelDigitalOceanDeployer;

use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Log;

class Deployer
{
    protected array $config;
    protected array $output = [];

    public function __construct(array $config)
    {
        $this->config = $config;
    }

    public function deploy(): bool
    {
        try {
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

    protected function setupSSH(): self
    {
        // Setup SSH key and known hosts
        Process::run('mkdir -p ~/.ssh/');
        Process::run('echo "$DO_SSH_KEY" | base64 -d > ~/.ssh/deploy_key');
        Process::run('chmod 600 ~/.ssh/deploy_key');
        Process::run('eval "$(ssh-agent -s)"');
        Process::run('ssh-add ~/.ssh/deploy_key');
        Process::run("ssh-keyscan -H {$this->config['server']['host']} >> ~/.ssh/known_hosts");

        return $this;
    }

    protected function pullCode(): self
    {
        $host = $this->config['server']['host'];
        $user = $this->config['server']['username'];
        $path = $this->config['server']['path'];
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
        $host = $this->config['server']['host'];
        $user = $this->config['server']['username'];
        $path = $this->config['server']['path'];

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
        Process::run("ssh -o StrictHostKeyChecking=no -i ~/.ssh/deploy_key {$user}@{$host} 'cd {$path} && {$commandString}'");

        return $this;
    }

    protected function setPermissions(): self
    {
        $host = $this->config['server']['host'];
        $user = $this->config['server']['username'];
        $path = $this->config['server']['path'];
        $permissions = $this->config['permissions'];

        $commands = [
            "chmod -R {$permissions['files']} {$path}",
            "find {$path} -type d -exec chmod {$permissions['directories']} {} \;",
            "chmod -R {$permissions['storage']} {$path}/storage",
            "chmod -R {$permissions['bootstrap_cache']} {$path}/bootstrap/cache",
        ];

        $commandString = implode(' && ', $commands);
        Process::run("ssh -o StrictHostKeyChecking=no -i ~/.ssh/deploy_key {$user}@{$host} '{$commandString}'");

        return $this;
    }

    protected function runBeforeHooks(): self
    {
        foreach ($this->config['hooks']['before'] as $command) {
            Process::run($command);
        }
        return $this;
    }

    protected function runAfterHooks(): self
    {
        foreach ($this->config['hooks']['after'] as $command) {
            Process::run($command);
        }
        return $this;
    }

    public function getOutput(): array
    {
        return $this->output;
    }
}
