<?php

declare(strict_types=1);

namespace Juling\DevTools\Resolvers\Laravel;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Str;
use Juling\DevTools\Contracts\EntityContract;
use Juling\DevTools\Support\DevConfig;
use Juling\DevTools\Support\SchemaTrait;

class EntityBuilder implements EntityContract
{
    use SchemaTrait;

    public function build(DevConfig $devConfig, array $langOptions, string $tableName, string $comment): void
    {
        if ($devConfig->getMultiModule()) {
            $groupName = $this->getTableGroupName($tableName);
            $dist = $devConfig->getDist('app/Modules/' . $groupName . '/Entities');
            $namespace = "App\\Modules\\$groupName";
        } else {
            $dist = $devConfig->getDist('app/Entities');
            $namespace = 'App';
        }
        $this->ensureDirectoryExists($dist);

        $fields = "\n";
        $methods = "\n";

        $className = Str::studly($this->getSingular($tableName));
        $columns = $this->getTableColumns($tableName);
        foreach ($columns as $column) {
            $fields .= "    const string get{$column['studly_name']} = '{$column['name']}';";
            if (!empty($column['comment'])) {
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
            $fields .= '    private ' . $column['base_type'] . ' $' . $column['camel_name'] . ";\n\n";
            $methods .= $this->getSet($column['camel_name'], $column['base_type'], $column['comment']) . "\n\n";
        }

        $fields = rtrim($fields, "\n");
        $methods = rtrim($methods, "\n");

        $content = Blade::render(file_get_contents(__DIR__ . '/stubs/entity/php_entity.stub'), [
            'namespace' => $namespace,
            'entity' => $className,
            'fields' => $fields,
            'methods' => $methods,
        ]);

        file_put_contents($dist . '/' . $className . 'Entity.php', "<?php\n\n".$content);
    }
}
