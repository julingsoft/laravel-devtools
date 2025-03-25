<?php

declare(strict_types=1);

namespace Juling\DevTools\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;
use Juling\DevTools\Facades\GenerateStub;
use Juling\DevTools\Support\DevConfig;
use Juling\DevTools\Support\SchemaTrait;
use Juling\Foundation\Support\StrHelper;

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
            $className = Str::studly($this->getSingular($tableName));
            $comment = StrHelper::rtrim($table['comment'], '表');

            $this->entityTpl($tableName);
        }
    }

    private function entityTpl(string $tableName): void
    {
        $devConfig = new DevConfig();
        if ($devConfig->getMultiModule()) {
            $groupName = $this->getTableGroupName($tableName);
            $dist = $devConfig->getDist(basename(__CLASS__).'/Modules/'.$groupName.'/Entities');
            $namespace = "App\\Modules\\$groupName";
        } else {
            $dist = $devConfig->getDist(basename(__CLASS__).'/Entities');
            $namespace = 'App';
        }
        $this->ensureDirectoryExists($dist);

        $fields = "\n";
        $methods = "\n";

        $className = Str::studly($this->getSingular($tableName));
        $columns = $this->getTableColumns($tableName);
        foreach ($columns as $column) {
            $fields .= "    const string get{$column['studly_name']} = '{$column['name']}';";
            if (! empty($column['comment'])) {
                $fields .= " // {$column['comment']}";
            }
            $fields .= "\n\n";
        }

        foreach ($columns as $column) {
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
            $methods .= $this->getSet($column['camel_name'], $column['base_type'], $column['comment'])."\n\n";
        }

        $fields = rtrim($fields, "\n");
        $methods = rtrim($methods, "\n");

        GenerateStub::from(__DIR__.'/stubs/entity/entity.stub')
            ->to($dist)
            ->name($className.'Entity')
            ->ext('php')
            ->replaces([
                'namespace' => $namespace,
                'className' => $className,
                'fields' => $fields,
                'methods' => $methods,
            ])
            ->generate();
    }
}
