<?php

declare(strict_types=1);

namespace Juling\DevTools\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;
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
        $distDir = config('devtools.dist');
        if (is_dir($distDir)) {
            $this->deleteDirectories($distDir.'/Views');
        }

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

    private function tpl(string $name, string $comment, string $view): void
    {
        $distDir = config('devtools.dist').'/Views/'.Str::camel($name);
        if (! is_dir($distDir)) {
            $this->ensureDirectoryExists($distDir);
        }

        $content = file_get_contents(__DIR__.'/stubs/view/'.$view.'.stub');
        $content = str_replace([
            '{$name}',
            '{$camelName}',
            '{$comment}',
            '{$namespace}',
        ], [
            $name,
            Str::camel($name),
            $comment,
            config('devtools.namespace'),
        ], $content);
        file_put_contents(config('devtools.dist').'/Views/'.$name.'/'.$view.'.blade.php', $content);
    }
}
