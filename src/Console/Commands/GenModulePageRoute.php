<?php

declare(strict_types=1);

namespace Juling\DevTools\Console\Commands;

use Illuminate\Console\Command;
use Juling\DevTools\Support\SchemaTrait;

class GenModulePageRoute extends Command
{
    use SchemaTrait;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'gen:module:page:route';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate project module page routes';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        $this->resolve('modulePageRoute');
    }
}
