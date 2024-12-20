<?php

declare(strict_types=1);

namespace Juling\DevTools\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;
use Juling\DevTools\Facades\GenerateStub;
use Juling\DevTools\Support\SchemaTrait;

class GenModel extends Command
{
    use SchemaTrait;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'gen:model';

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
        $tables = $this->getTables();
        foreach ($tables as $table) {
            $this->modelTpl($table['name']);
        }
    }

    private function modelTpl(string $tableName): void
    {
        $groupName = $this->getTableGroupName($tableName);
        $className = Str::studly($this->getSingular($tableName));
        $columns = $this->getTableColumns($tableName);
        $primaryKey = $this->getTablePrimaryKey($tableName);
        $ignoreColumns = array_merge($this->ignoreColumns, [$primaryKey]);
        $dist = app_path('Models/'.$groupName);
        $this->ensureDirectoryExists($dist);

        $softDelete = false;

        $fieldStr = '';
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
                'groupName' => $groupName,
                'name' => $className,
                'tableName' => $tableName,
                'pk' => $primaryKey,
                'useSoftDelete' => $useSoftDelete,
                'fieldStr' => $fieldStr,
            ])
            ->generate();
    }
}
