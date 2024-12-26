<?php

namespace Koskey\LaravelDigitalOceanDeployer;

use Illuminate\Support\ServiceProvider;
use Koskey\LaravelDigitalOceanDeployer\Commands\DeployCommand;
use Koskey\LaravelDigitalOceanDeployer\Commands\GenerateKeyCommand;
use Koskey\LaravelDigitalOceanDeployer\Commands\PublishWorkflowCommand;

class DeployerServiceProvider extends ServiceProvider
{
    public function boot()
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                DeployCommand::class,
                GenerateKeyCommand::class,
                PublishWorkflowCommand::class,
            ]);

            $this->publishes([
                __DIR__.'/../config/deployer.php' => config_path('deployer.php'),
            ], 'config');

            $this->publishes([
                __DIR__.'/../stubs/github/workflows/deploy.yml' => base_path('.github/workflows/deploy.yml'),
            ], 'github-workflow');
        }
    }

    public function register()
    {
        $this->mergeConfigFrom(
            __DIR__.'/../config/deployer.php', 'deployer'
        );

        $this->app->singleton(Deployer::class, function ($app) {
            return new Deployer($app['config']['deployer']);
        });
    }
}
