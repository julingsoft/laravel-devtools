<?php

declare(strict_types=1);

namespace Juling\DevTools\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;

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
        $fs = new Filesystem;
        $fs->ensureDirectoryExists(public_path('openapi'));
        $fs->ensureDirectoryExists(resource_path('ts/services'));
        $fs->ensureDirectoryExists(resource_path('ts/types'));
    }
}
