<?php

namespace Koskey\LaravelDigitalOceanDeployer\Commands;

use Illuminate\Console\Command;
use Koskey\LaravelDigitalOceanDeployer\Deployer;

class DeployCommand extends Command
{
    protected $signature = 'deploy {--verbose : Display detailed output during deployment}';
    protected $description = 'Deploy the application to DigitalOcean';

    public function handle(Deployer $deployer)
    {
        $this->info('Starting deployment...');
        $verbose = $this->option('verbose');

        try {
            $result = $deployer->deploy($verbose ? function($message) {
                $this->line($message);
            } : null);

            if ($result === true) {
                $this->newLine();
                $this->info('✓ Deployment completed successfully!');
                return Command::SUCCESS;
            }

            $this->newLine();
            $this->error('✗ Deployment failed!');
            
            if (!$verbose) {
                $this->warn('Run with --verbose option to see detailed output:');
                $this->line('php artisan deploy --verbose');
            }

            return Command::FAILURE;
        } catch (\Exception $e) {
            $this->newLine();
            $this->error('✗ Deployment failed: ' . $e->getMessage());
            
            if ($verbose) {
                $this->newLine();
                $this->error('Stack trace:');
                $this->line($e->getTraceAsString());
            } else {
                $this->warn('Run with --verbose option to see detailed output:');
                $this->line('php artisan deploy --verbose');
            }

            return Command::FAILURE;
        }
    }
}
