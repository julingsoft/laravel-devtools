<?php

declare(strict_types=1);

namespace Juling\DevTools;

use Illuminate\Support\ServiceProvider;
use Juling\DevTools\Console\Commands\GenBundleRoute;
use Juling\DevTools\Console\Commands\GenController;
use Juling\DevTools\Console\Commands\GenDict;
use Juling\DevTools\Console\Commands\GenEntity;
use Juling\DevTools\Console\Commands\GenEnums;
use Juling\DevTools\Console\Commands\GenModel;
use Juling\DevTools\Console\Commands\GenModuleRoute;
use Juling\DevTools\Console\Commands\GenPagesRoute;
use Juling\DevTools\Console\Commands\GenRepository;
use Juling\DevTools\Console\Commands\GenRoute;
use Juling\DevTools\Console\Commands\GenService;
use Juling\DevTools\Console\Commands\GenTypescript;
use Juling\DevTools\Console\Commands\GenView;
use Juling\DevTools\Console\Commands\InitCommand;

class DevToolsServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any package services.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(dirname(__DIR__).'/config/devtools.php', 'devtools');
    }

    /**
     * Bootstrap any package services.
     */
    public function boot(): void
    {
        $this->publishes([
            dirname(__DIR__).'/config/devtools.php' => config_path('devtools.php'),
        ]);

        if ($this->app->runningInConsole()) {
            $this->commands([
                GenBundleRoute::class,
                GenController::class,
                GenDict::class,
                GenEntity::class,
                GenEnums::class,
                GenModel::class,
                GenModuleRoute::class,
                GenPagesRoute::class,
                GenRepository::class,
                GenRoute::class,
                GenService::class,
                GenTypescript::class,
                GenView::class,
                InitCommand::class,
            ]);
        }
    }
}
