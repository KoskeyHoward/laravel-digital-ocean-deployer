<?php

namespace Koskey\LaravelDigitalOceanDeployer;

use Illuminate\Support\ServiceProvider;
use Koskey\LaravelDigitalOceanDeployer\Commands\DeployCommand;

class DeployerServiceProvider extends ServiceProvider
{
    public function boot()
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/deployer.php' => config_path('deployer.php'),
            ], 'config');

            $this->publishes([
                __DIR__.'/../stubs/github/workflows/deploy.yml' => base_path('.github/workflows/deploy.yml'),
            ], 'github-workflow');

            $this->commands([
                DeployCommand::class,
            ]);
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
