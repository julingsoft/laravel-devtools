<?php

declare(strict_types=1);

namespace Juling\DevTools\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Blade;
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
            $tableName = $this->getSingular($table['name']);

            $distDir = $this->getTableGroupName($tableName);
            $subDir = Str::substr($tableName, Str::length($distDir)+1);
            if (!empty($subDir)) {
                $distDir = Str::camel($distDir).'/'.Str::camel($subDir);
            } else {
                $distDir = Str::camel($distDir);
            }

            $comment = StrHelper::rtrim($table['comment'], '表').'模块';
            $columns = $this->getTableColumns($table['name']);

            $this->tpl($distDir, $tableName, 'Index', $comment, $columns, 'index');
            $this->tpl($distDir, $tableName, 'Upsert', $comment, $columns, 'upsert');
        }
    }

    private function tpl(string $distDir, string $tableName, string $name, string $comment, array $columns, string $view): void
    {
        $dist = resource_path('admin/src/views/'.$distDir);
        $this->ensureDirectoryExists($dist);

        $viewFile = $dist.'/'.$name.'View.vue';
        if (! file_exists($viewFile) || $this->option('force')) {
            $content = file_get_contents(__DIR__ . '/stubs/view/'.$view.'.stub');
            $render = Blade::render($content, [
                'camelName' => Str::camel($tableName), // userAccount
                'snakeName' => Str::snake($tableName), // user_account
                'studlyName' => Str::studly($tableName), // UserAccount
                'primaryKey' => $this->getTablePrimaryKey($tableName),
                'comment' => $comment,
                'columns' => $columns,
            ]);
            file_put_contents($viewFile, $render);
        }
    }
}
