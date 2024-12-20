<?php

declare(strict_types=1);

namespace Juling\DevTools\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;
use Juling\DevTools\Facades\GenerateStub;
use Juling\DevTools\Support\SchemaTrait;

class GenRepository extends Command
{
    use SchemaTrait;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'gen:dao';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate repository classes';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        $tables = $this->getTables();
        foreach ($tables as $table) {
            $this->repositoryTpl($table['name']);
        }
    }

    private function repositoryTpl(string $tableName): void
    {
        $groupName = $this->getTableGroupName($tableName);
        $className = Str::studly($this->getSingular($tableName));
        $dist = app_path('Repositories/'.$groupName);
        $this->ensureDirectoryExists($dist);

        GenerateStub::from(__DIR__.'/stubs/repository/repository.stub')
            ->to($dist)
            ->name($className.'Repository')
            ->ext('php')
            ->replaces([
                'groupName' => $groupName,
                'name' => $className,
                'tableName' => $tableName,
            ])
            ->generate();
    }
}
