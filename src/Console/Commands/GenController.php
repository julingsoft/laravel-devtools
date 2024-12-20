<?php

declare(strict_types=1);

namespace Juling\DevTools\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;
use Juling\DevTools\Facades\GenerateStub;
use Juling\DevTools\Support\SchemaTrait;

class GenController extends Command
{
    use SchemaTrait;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'gen:controller';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate controller classes';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        $tables = $this->getTables();
        foreach ($tables as $table) {
            $className = Str::studly($this->getSingular($table['name']));
            $comment = $table['comment'];
            if (Str::endsWith($comment, '表')) {
                $comment = Str::substr($comment, 0, -1);
            }
            $comment .= '模块';
            $columns = $this->getTableColumns($table['name']);

            $this->controllerTpl($className, $comment);
            $this->requestTpl($className, $columns);
            $this->responseTpl($className, $columns);
        }
    }

    private function controllerTpl(string $className, string $comment): void
    {
        $groupName = $this->getTableGroupName(Str::snake($className));
        $dist = app_path('Modules/'.$groupName.'/Controllers');
        $this->ensureDirectoryExists($dist);

        GenerateStub::from(__DIR__.'/stubs/controller/controller.stub')
            ->to($dist)
            ->name($className.'Controller')
            ->ext('php')
            ->replaces([
                'groupName' => $groupName,
                'name' => $className,
                'camelName' => Str::camel($className),
                'comment' => $comment,
            ])
            ->generate();
    }

    private function requestTpl(string $className, array $columns): void
    {
        $groupName = $this->getTableGroupName(Str::snake($className));
        $dist = app_path('Modules/'.$groupName.'/Requests/'.$className);
        $this->ensureDirectoryExists($dist);

        $ignoreFields = ['created_at', 'updated_at', 'deleted_at'];

        $dataSets = ['required' => '', 'properties' => '', 'consts' => '', 'rules' => '', 'messages' => ''];
        foreach ($columns as $column) {
            if (in_array($column['name'], $ignoreFields)) {
                continue;
            }
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
            $camelName = Str::studly($column['name']);
            $dataSets['required'] .= "        self::get{$camelName},\n";
            $dataSets['properties'] .= "        new OA\Property(property: self::get{$camelName}, description: '{$column['comment']}', type: '{$column['swagger_type']}'),\n";
            $dataSets['consts'] .= "    const string get{$camelName} = '{$column['name']}';\n\n";
            $dataSets['rules'] .= "            self::get{$camelName} => 'require',\n";

            $column['comment'] = Str::replace([':', '：'], ':', $column['comment']);
            $endPosition = Str::position($column['comment'], ':');
            if ($endPosition !== false) {
                $column['comment'] = Str::substr($column['comment'], 0, $endPosition);
            }
            $dataSets['messages'] .= "            self::get{$camelName}.'.require' => '请设置{$column['comment']}',\n";
        }

        $dataSets = array_map(function ($item) {
            return rtrim($item, "\n");
        }, $dataSets);

        $this->writeRequest($className, 'QueryRequest', '', '', '', '', '');
        $this->writeRequest($className, 'UpsertRequest', $dataSets['required'], $dataSets['properties'], $dataSets['consts'], $dataSets['rules'], $dataSets['messages']);
    }

    private function writeRequest($className, $suffix, $required, $properties, $consts, $rules, $messages): void
    {
        $groupName = $this->getTableGroupName(Str::snake($className));
        $dist = app_path('Modules/'.$groupName.'/Requests/'.$className);
        $this->ensureDirectoryExists($dist);

        GenerateStub::from(__DIR__.'/stubs/request/request.stub')
            ->to($dist)
            ->name($className.$suffix)
            ->ext('php')
            ->replaces([
                'name' => $className,
                'schema' => $className.$suffix,
                'dataSets[required]' => $required,
                'dataSets[properties]' => $properties,
                'dataSets[consts]' => $consts,
                'dataSets[rules]' => $rules,
                'dataSets[messages]' => $messages,
            ])
            ->generate();
    }

    private function responseTpl(string $className, array $columns): void
    {
        $groupName = $this->getTableGroupName(Str::snake($className));
        $dist = app_path('Modules/'.$groupName.'/Responses/'.$className);
        $this->ensureDirectoryExists($dist);

        GenerateStub::from(__DIR__.'/stubs/response/query.stub')
            ->to($dist)
            ->name($className.'QueryResponse')
            ->ext('php')
            ->replaces([
                'name' => $className,
            ])
            ->generate();

        GenerateStub::from(__DIR__.'/stubs/response/destroy.stub')
            ->to($dist)
            ->name($className.'DestroyResponse')
            ->ext('php')
            ->replaces([
                'name' => $className,
            ])
            ->generate();

        $ignoreFields = ['deleted_time', 'password', 'password_salt'];

        $fields = "\n";
        foreach ($columns as $column) {
            if (in_array($column['name'], $ignoreFields)) {
                continue;
            }

            if ($column['name'] === 'default') {
                $column['name'] = 'isDefault';
            }
            if ($column['name'] === 'id' && empty($column['comment'])) {
                $column['comment'] = 'ID';
            }
            $column['name'] = Str::camel($column['name']);
            $fields .= "    #[OA\Property(property: '{$column['name']}', description: '{$column['comment']}', type: '{$column['swagger_type']}')]\n";
            $fields .= '    private '.$column['base_type'].' $'.$column['name'].";\n\n";
        }

        foreach ($columns as $column) {
            if (in_array($column['name'], $ignoreFields)) {
                continue;
            }

            $column['name'] = Str::camel($column['name']);
            $fields .= $this->getSet($column['name'], $column['base_type'], $column['comment'])."\n\n";
        }

        $fields = rtrim($fields, "\n");

        GenerateStub::from(__DIR__.'/stubs/response/response.stub')
            ->to($dist)
            ->name($className.'Response')
            ->ext('php')
            ->replaces([
                'name' => $className,
                'fields' => $fields,
            ])
            ->generate();
    }
}
