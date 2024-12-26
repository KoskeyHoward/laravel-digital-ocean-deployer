<?php

namespace Koskey\LaravelDigitalOceanDeployer\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class PublishWorkflowCommand extends Command
{
    protected $signature = 'deployer:publish-workflow';
    protected $description = 'Publish GitHub workflow with configured branch';

    public function handle()
    {
        $branch = config('deployer.repository.branch', 'main');
        
        // Read the workflow template
        $workflowContent = File::get(__DIR__ . '/../../stubs/github/workflows/deploy.yml');
        
        // Replace the branch
        $workflowContent = str_replace(
            "branches: [ main ]",
            "branches: [ $branch ]",
            $workflowContent
        );
        
        // Ensure the workflows directory exists
        $workflowsPath = base_path('.github/workflows');
        if (!File::isDirectory($workflowsPath)) {
            File::makeDirectory($workflowsPath, 0755, true);
        }
        
        // Write the modified workflow
        File::put("$workflowsPath/deploy.yml", $workflowContent);
        
        $this->info("GitHub workflow published with branch: $branch");
    }
}
