<?php

declare(strict_types=1);

namespace Juling\DevTools\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;
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
        $distDir = config('devtools.dist');
        if (is_dir($distDir)) {
            $this->deleteDirectories($distDir.'/Controllers');
            $this->deleteDirectories($distDir.'/Requests');
            $this->deleteDirectories($distDir.'/Responses');
        }

        $tables = $this->getTables();
        foreach ($tables as $table) {
            $className = Str::studly(Str::singular($table['name']));
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

    private function controllerTpl(string $name, string $comment): void
    {
        $distDir = config('devtools.dist').'/Controllers';
        if (! is_dir($distDir)) {
            $this->ensureDirectoryExists($distDir);
        }

        $content = file_get_contents(__DIR__.'/stubs/controller/controller.stub');
        $content = str_replace([
            '{$name}',
            '{$camelName}',
            '{$comment}',
            '{$namespace}',
            '{$viewNamespace}',
        ], [
            $name,
            Str::camel($name),
            $comment,
            config('devtools.namespace'),
            Str::camel(basename(config('devtools.dist'))),
        ], $content);
        file_put_contents(config('devtools.dist').'/Controllers/'.$name.'Controller.php', $content);
    }

    private function requestTpl(string $name, array $columns): void
    {
        $distDir = config('devtools.dist').'/Requests/'.$name;
        if (! is_dir($distDir)) {
            $this->ensureDirectoryExists($distDir);
        }

        $ignoreFields = ['id', 'created_at', 'updated_at', 'deleted_at'];

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
            $dataSets['consts'] .= "    const get{$camelName} = '{$column['name']}';\n\n";
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

        $this->writeRequest($name, 'CreateRequest', $dataSets['required'], $dataSets['properties'], $dataSets['consts'], $dataSets['rules'], $dataSets['messages']);
        $this->writeRequest($name, 'QueryRequest', '', '', '', '', '');
        $this->writeRequest($name, 'UpdateRequest', $dataSets['required'], $dataSets['properties'], $dataSets['consts'], $dataSets['rules'], $dataSets['messages']);
    }

    private function writeRequest($name, $suffix, $required, $properties, $consts, $rules, $messages): void
    {
        if ($suffix === 'UpdateRequest') {
            $required = "        self::getId,\n".$required;
            $properties = "        new OA\Property(property: self::getId, description: 'ID', type: 'integer'),\n".$properties;
            $rules = "            self::getId => 'require',\n".$rules;
            $messages = "            self::getId.'.require' => '请设置ID',\n".$messages;
        }

        $content = file_get_contents(__DIR__.'/stubs/request/request.stub');
        $content = str_replace([
            '{$name}',
            '{$schema}',
            '{$dataSets[required]}',
            '{$dataSets[properties]}',
            '{$dataSets[consts]}',
            '{$dataSets[rules]}',
            '{$dataSets[messages]}',
            '{$namespace}',
        ], [
            $name,
            $name.$suffix,
            $required,
            $properties,
            $consts,
            $rules,
            $messages,
            config('devtools.namespace'),
        ], $content);
        file_put_contents(config('devtools.dist').'/Requests/'.$name.'/'.$name.$suffix.'.php', $content);
    }

    private function responseTpl(string $name, array $columns): void
    {
        $distDir = config('devtools.dist').'/Responses/'.$name;
        if (! is_dir($distDir)) {
            $this->ensureDirectoryExists($distDir);
        }

        $content = file_get_contents(__DIR__.'/stubs/response/query.stub');
        $content = str_replace([
            '{$name}',
            '{$namespace}',
        ], [
            $name,
            config('devtools.namespace'),
        ], $content);
        file_put_contents(config('devtools.dist').'/Responses/'.$name.'/'.$name.'QueryResponse.php', $content);

        $content = file_get_contents(__DIR__.'/stubs/response/destroy.stub');
        $content = str_replace([
            '{$name}',
            '{$namespace}',
        ], [
            $name,
            config('devtools.namespace'),
        ], $content);
        file_put_contents(config('devtools.dist').'/Responses/'.$name.'/'.$name.'DestroyResponse.php', $content);

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

        $content = file_get_contents(__DIR__.'/stubs/response/response.stub');
        $content = str_replace([
            '{$name}',
            '{$fields}',
            '{$namespace}',
        ], [
            $name,
            $fields,
            config('devtools.namespace'),
        ], $content);
        file_put_contents(config('devtools.dist').'/Responses/'.$name.'/'.$name.'Response.php', $content);
    }
}
