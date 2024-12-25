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
    protected $signature = 'gen:view';

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
        $tables = $this->getTables();
        foreach ($tables as $table) {
            $className = Str::studly($this->getSingular($table['name']));
            $comment = $table['comment'];
            if (Str::endsWith($comment, '表')) {
                $comment = Str::substr($comment, 0, -1);
            }
            $comment .= '模块';
            $columns = $this->getTableColumns($table['name']);

            $this->tpl($className, $comment, 'index');
            $this->tpl($className, $comment, 'create');
            $this->tpl($className, $comment, 'edit');
        }
    }

    private function tpl(string $className, string $comment, string $view): void
    {
        $groupName = $this->getTableGroupName(Str::snake($className));
        $dist = resource_path('admin/src/views/'.$groupName.'/'.Str::camel($className));
        $this->ensureDirectoryExists($dist);

        GenerateStub::from(__DIR__.'/stubs/view/'.$view.'.stub')
            ->to($dist)
            ->name($view.'View')
            ->ext('vue')
            ->replaces([
                'groupName' => $groupName,
                'name' => $className,
                'comment' => $comment,
            ])
            ->generate();
    }
}
