<?php

declare(strict_types=1);

namespace Juling\DevTools\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Juling\DevTools\Support\DevConfig;

class InitCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'gen:init';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'DevTools init';

    public function handle(): void
    {
        $devConfig = new DevConfig();

        $fs = new Filesystem;
        $fs->ensureDirectoryExists(base_path('docs/api'));
        $fs->ensureDirectoryExists($devConfig->getDist());
    }
}
