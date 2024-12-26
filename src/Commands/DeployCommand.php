<?php

namespace Koskey\LaravelDigitalOceanDeployer\Commands;

use Illuminate\Console\Command;
use Koskey\LaravelDigitalOceanDeployer\Deployer;

class DeployCommand extends Command
{
    protected $signature = 'deploy';
    protected $description = 'Deploy the application to DigitalOcean';

    public function handle(Deployer $deployer)
    {
        $this->info('Starting deployment...');

        try {
            $result = $deployer->deploy($this->output->isVerbose() ? function($message) {
                $this->line($message);
            } : null);

            if ($result === true) {
                $this->newLine();
                $this->info('✓ Deployment completed successfully!');
                return Command::SUCCESS;
            }

            $this->newLine();
            $this->error('✗ Deployment failed!');
            
            if (!$this->output->isVerbose()) {
                $this->warn('Run with -v option to see detailed output:');
                $this->line('php artisan deploy -v');
            }

            return Command::FAILURE;
        } catch (\Exception $e) {
            $this->newLine();
            $this->error('✗ Deployment failed: ' . $e->getMessage());
            
            if ($this->output->isVerbose()) {
                $this->newLine();
                $this->error('Stack trace:');
                $this->line($e->getTraceAsString());
            } else {
                $this->warn('Run with -v option to see detailed output:');
                $this->line('php artisan deploy -v');
            }

            return Command::FAILURE;
        }
    }
}
