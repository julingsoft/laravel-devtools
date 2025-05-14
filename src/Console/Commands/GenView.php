<?php

declare(strict_types=1);

namespace Juling\DevTools\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;
use Juling\DevTools\Facades\GenerateStub;
use Juling\DevTools\Support\DevConfig;
use Juling\DevTools\Support\SchemaTrait;
use Juling\DevTools\Support\StrHelper;

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
            $tableName = Str::studly($this->getSingular($table['name']));
            $groupName = $this->getTableGroupName(Str::snake($tableName));
            $viewName = Str::substr($tableName, Str::length($groupName));

            $comment = StrHelper::rtrim($table['comment'], '表').'模块';
            $columns = $this->getTableColumns($table['name']);

            $this->tpl($groupName, $viewName, $viewName.'Index', $comment, $columns, 'index');
            $this->tpl($groupName, $viewName, $viewName.'Create', $comment, $columns, 'create');
            $this->tpl($groupName, $viewName, $viewName.'Edit', $comment, $columns, 'edit');
        }
    }

    private function tpl(string $groupName, string $viewName, string $name, string $comment, array $columns, string $view): void
    {
        $dist = resource_path('admin/src/modules/'.Str::camel($groupName));
        if (!empty($viewName)) {
            $dist .= '/'.Str::camel($viewName);
        }
        $this->ensureDirectoryExists($dist);

        $viewFile = $dist.'/'.$name.'View.vue';
        if (! file_exists($viewFile) || $this->option('force')) {
            GenerateStub::from(__DIR__.'/stubs/view/'.$view.'.stub')
                ->to($dist)
                ->name($name.'View')
                ->ext('vue')
                ->replaces([
                    'groupName' => $groupName,
                    'viewName' => $viewName,
                    'comment' => $comment,
                ])
                ->generate();
        }
    }
}
