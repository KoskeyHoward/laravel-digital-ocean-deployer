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

        $bar = $this->output->createProgressBar(6);
        $bar->start();

        try {
            if ($deployer->deploy()) {
                $bar->finish();
                $this->newLine();
                $this->info('Deployment completed successfully!');
                return Command::SUCCESS;
            }

            $this->error('Deployment failed!');
            return Command::FAILURE;
        } catch (\Exception $e) {
            $this->error('Deployment failed: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
}
