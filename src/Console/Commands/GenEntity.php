<?php

declare(strict_types=1);

namespace Juling\DevTools\Console\Commands;

use Illuminate\Console\Command;
use Juling\DevTools\Support\DevConfig;
use Juling\DevTools\Support\SchemaTrait;
use Juling\DevTools\Support\StrHelper;

class GenEntity extends Command
{
    use SchemaTrait;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'gen:entity {--prefix=} {--table=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate entity classes';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        $tables = $this->getTables($this->option('prefix'), $this->option('table'));
        foreach ($tables as $table) {
            $tableName = $table['name'];
            $comment = StrHelper::rtrim($table['comment'], '表');
            $this->build($tableName, $comment);
        }
    }

    private function build(string $tableName, string $comment): void
    {
        $devConfig = new DevConfig();
        $languages = $devConfig->getMultiLanguage();
        foreach ($languages as $language => $langOptions) {
            $resolver = '\\Juling\\DevTools\\Resolvers\\' . $language . '\\EntityBuilder';
            if (method_exists($resolver, 'build')) {
                $resolver->build($devConfig, $langOptions, $tableName, $comment);
            }
        }
    }
}
