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
    protected $signature = 'gen:dao {--prefix=} {--table=}';

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
        $tables = $this->getTables($this->option('prefix'), $this->option('table'));
        foreach ($tables as $table) {
            $tableName = $table['name'];
            $className = Str::studly($this->getSingular($tableName));

            $this->repositoryTpl($className, $tableName);
        }
    }

    private function repositoryTpl(string $className, string $tableName): void
    {
        $config = config('devtools');
        if ($config['multi_module']) {
            $groupName = $this->getTableGroupName($tableName);
            $dist = app_path('Modules/'.$groupName.'/Repositories');
            $namespace = "App\\Modules\\$groupName";
        } else {
            $dist = app_path('Repositories');
            $namespace = 'App';
        }
        $this->ensureDirectoryExists($dist);

        GenerateStub::from(__DIR__.'/stubs/repository/repository.stub')
            ->to($dist)
            ->name($className.'Repository')
            ->ext('php')
            ->replaces([
                'namespace' => $namespace,
                'className' => $className,
                'tableName' => $tableName,
            ])
            ->generate();
    }
}
