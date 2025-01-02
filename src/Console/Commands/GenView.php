<?php

declare(strict_types=1);

namespace Juling\DevTools\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;
use Juling\DevTools\Facades\GenerateStub;
use Juling\DevTools\Support\SchemaTrait;

class GenView extends Command
{
    use SchemaTrait;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'gen:view {--prefix=} {--table=}';

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
            $comment = Str::rtrim($table['comment'], '表').'模块';

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

        $dist = resource_path('admin/src/views/'.Str::camel($groupName).'/'.Str::camel($className));
        $this->ensureDirectoryExists($dist);

        GenerateStub::from(__DIR__.'/stubs/view/'.$view.'.stub')
            ->to($dist)
            ->name(Str::studly($view).'View')
            ->ext('vue')
            ->replaces([
                'groupName' => $groupName,
                'className' => $className,
                'comment' => $comment,
            ])
            ->generate();
    }
}
