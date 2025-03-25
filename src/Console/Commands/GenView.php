<?php

declare(strict_types=1);

namespace Juling\DevTools\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;
use Juling\DevTools\Facades\GenerateStub;
use Juling\DevTools\Support\DevConfig;
use Juling\DevTools\Support\SchemaTrait;
use Juling\Foundation\Support\StrHelper;

class GenView extends Command
{
    use SchemaTrait;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'gen:view {--prefix=} {--table=} {--force=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate view template';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        $tables = $this->getTables($this->option('prefix'), $this->option('table'));
        foreach ($tables as $table) {
            $tableName = $table['name'];
            $className = Str::studly($this->getSingular($tableName));
            $comment = StrHelper::rtrim($table['comment'], '表').'模块';

            $columns = $this->getTableColumns($table['name']);
            $this->tpl($className, $comment, 'index');
            $this->tpl($className, $comment, 'create');
            $this->tpl($className, $comment, 'edit');
        }
    }

    private function tpl(string $className, string $comment, string $view): void
    {
        $groupName = $this->getTableGroupName(Str::snake($className));
        $className = Str::ltrim($className, $groupName);

        $devConfig = new DevConfig();
        $dist = $devConfig->getDist(basename(__CLASS__).'/src/views/'.Str::camel($groupName).'/'.Str::camel($className));
        $this->ensureDirectoryExists($dist);

        $viewFile = $dist.'/'.$className.'View.vue';
        if (! file_exists($viewFile) || $this->option('force')) {
            GenerateStub::from(__DIR__.'/stubs/view/'.$view.'.stub')
                ->to($dist)
                ->name($className.'View')
                ->ext('vue')
                ->replaces([
                    'groupName' => $groupName,
                    'className' => $className,
                    'comment' => $comment,
                ])
                ->generate();
        }
    }
}
