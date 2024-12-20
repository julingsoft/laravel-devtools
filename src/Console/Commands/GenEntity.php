<?php

declare(strict_types=1);

namespace Juling\DevTools\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;
use Juling\DevTools\Facades\GenerateStub;
use Juling\DevTools\Support\SchemaTrait;

class GenEntity extends Command
{
    use SchemaTrait;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'gen:entity';

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
        $tables = $this->getTables();
        foreach ($tables as $table) {
            $this->entityTpl($table['name']);
        }
    }

    private function entityTpl(string $tableName): void
    {
        $groupName = $this->getTableGroupName($tableName);
        $className = Str::studly($this->getSingular($tableName));
        $columns = $this->getTableColumns($tableName);
        $dist = app_path('Entities/'.$groupName);
        $this->ensureDirectoryExists($dist);

        $fields = "\n";
        foreach ($columns as $column) {
            $fields .= "    const string get{$column['studly_name']} = '{$column['name']}';";
            if (! empty($column['comment'])) {
                $fields .= " // {$column['comment']}";
            }
            $fields .= "\n\n";
        }

        foreach ($columns as $column) {
            if ($column['name'] === 'default') {
                $column['name'] = 'isDefault';
            }
            if ($column['name'] === 'id' && empty($column['comment'])) {
                $column['comment'] = 'ID';
            }
            if ($column['name'] === 'created_at' && empty($column['comment'])) {
                $column['comment'] = '创建时间';
            }
            if ($column['name'] === 'updated_at' && empty($column['comment'])) {
                $column['comment'] = '更新时间';
            }
            if ($column['name'] === 'deleted_at' && empty($column['comment'])) {
                $column['comment'] = '删除时间';
            }
            $fields .= "    #[OA\\Property(property: '{$column['camel_name']}', description: '{$column['comment']}', type: '{$column['swagger_type']}')]\n";
            $fields .= '    private '.$column['base_type'].' $'.$column['camel_name'].";\n\n";
        }

        foreach ($columns as $column) {
            $fields .= $this->getSet($column['camel_name'], $column['base_type'], $column['comment'])."\n\n";
        }

        $fields = rtrim($fields, "\n");

        GenerateStub::from(__DIR__.'/stubs/entity/entity.stub')
            ->to($dist)
            ->name($className.'Entity')
            ->ext('php')
            ->replaces([
                'groupName' => $groupName,
                'name' => $className,
                'fields' => $fields,
            ])
            ->generate();
    }
}
