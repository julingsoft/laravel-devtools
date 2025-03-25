<?php

declare(strict_types=1);

namespace Juling\DevTools\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;
use Juling\DevTools\Facades\GenerateStub;
use Juling\DevTools\Support\DevConfig;
use Juling\DevTools\Support\SchemaTrait;

class GenModel extends Command
{
    use SchemaTrait;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'gen:model {--prefix=} {--table=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate model classes';

    private array $ignoreColumns = [
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        $tables = $this->getTables($this->option('prefix'), $this->option('table'));
        foreach ($tables as $table) {
            $tableName = $table['name'];
            $className = Str::studly($this->getSingular($tableName));

            $this->modelTpl($className, $tableName);
        }
    }

    private function modelTpl(string $className, string $tableName): void
    {
        $devConfig = new DevConfig();
        if ($devConfig->getMultiModule()) {
            $groupName = $this->getTableGroupName($tableName);
            $dist = $devConfig->getDist(__CLASS__.'/Modules/'.$groupName.'/Models');
            $namespace = "App\\Modules\\$groupName";
        } else {
            $dist = $devConfig->getDist(__CLASS__.'/Models');
            $namespace = 'App';
        }
        $this->ensureDirectoryExists($dist);

        $primaryKey = $this->getTablePrimaryKey($tableName);
        $ignoreColumns = array_merge($this->ignoreColumns, [$primaryKey]);

        $softDelete = false;

        $fieldStr = '';
        $columns = $this->getTableColumns($tableName);
        foreach ($columns as $column) {
            if (! in_array($column['name'], $ignoreColumns)) {
                $fieldStr .= str_pad(' ', 8)."'{$column['name']}',\n";
            }
            if ($column['name'] === 'deleted_at') {
                $softDelete = true;
            }
        }
        $fieldStr = rtrim($fieldStr, "\n");

        $useSoftDelete = '';
        if ($softDelete) {
            $useSoftDelete = "    use SoftDeletes;\n";
        }

        GenerateStub::from(__DIR__.'/stubs/model/model.stub')
            ->to($dist)
            ->name($className)
            ->ext('php')
            ->replaces([
                'namespace' => $namespace,
                'className' => $className,
                'tableName' => $tableName,
                'pk' => $primaryKey,
                'useSoftDelete' => $useSoftDelete,
                'fieldStr' => $fieldStr,
            ])
            ->generate();
    }
}
