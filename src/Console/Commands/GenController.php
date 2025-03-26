<?php

declare(strict_types=1);

namespace Juling\DevTools\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;
use Juling\DevTools\Facades\GenerateStub;
use Juling\DevTools\Support\DevConfig;
use Juling\DevTools\Support\SchemaTrait;
use Juling\DevTools\Support\StrHelper;

class GenController extends Command
{
    use SchemaTrait;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'gen:controller {outDir=v1} {--prefix=} {--table=} {--force=}';

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
        $outDir = Str::studly($this->argument('outDir'));

        $tables = $this->getTables($this->option('prefix'), $this->option('table'));
        foreach ($tables as $table) {
            $tableName = $table['name'];
            $className = Str::studly($this->getSingular($tableName));
            $comment = StrHelper::rtrim($table['comment'], '表');
            $columns = $this->getTableColumns($tableName);

            $this->controllerTpl($className, $comment, $outDir);
            $this->requestTpl($className, $columns, $outDir);
            $this->responseTpl($className, $columns, $outDir);
        }
    }

    private function controllerTpl(string $className, string $comment, string $outDir): void
    {
        $groupName = $this->getTableGroupName(Str::snake($className));

        $devConfig = new DevConfig();
        if ($devConfig->getMultiModule()) {
            $dist = $devConfig->getDist('app/Modules/'.$groupName.'/Http/Controllers');
            $baseNamespace = "App\\Modules\\$groupName";
            $namespace = $baseNamespace.'\\Http';
        } else {
            $dist = $devConfig->getDist('app/Api/'.$outDir.'/Controllers');
            $baseNamespace = 'App';
            $namespace = $baseNamespace.'\\Api\\'.$outDir;
        }

        $controllerFile = $dist.'/'.$className.'Controller.php';
        if (! file_exists($controllerFile) || $this->option('force')) {
            $this->ensureDirectoryExists($dist);
            GenerateStub::from(__DIR__.'/stubs/controller/controller.stub')
                ->to($dist)
                ->name($className.'Controller')
                ->ext('php')
                ->replaces([
                    'namespace' => $namespace,
                    'baseNamespace' => $baseNamespace,
                    'className' => $className,
                    'groupName' => $groupName,
                    'classCamelName' => Str::camel($className),
                    'comment' => $comment,
                ])
                ->generate();
        }
    }

    private function requestTpl(string $className, array $columns, string $outDir): void
    {
        $ignoreFields = ['id', 'created_at', 'updated_at', 'deleted_at'];
        $dataSets = ['required' => '', 'properties' => '', 'constants' => '', 'rules' => '', 'messages' => ''];

        foreach ($columns as $column) {
            if (in_array($column['name'], $ignoreFields)) {
                continue;
            }
            $classCamelName = Str::studly($column['name']);
            $dataSets['required'] .= "        self::get{$classCamelName},\n";
            $dataSets['properties'] .= "        new OA\Property(property: self::get{$classCamelName}, description: '{$column['comment']}', type: '{$column['swagger_type']}'),\n";
            $dataSets['constants'] .= "    const string get{$classCamelName} = '{$column['name']}';\n\n";
            $dataSets['rules'] .= "            self::get{$classCamelName} => 'required',\n";

            $column['comment'] = Str::replace([':', '：'], ':', $column['comment']);
            $endPosition = Str::position($column['comment'], ':');
            if ($endPosition !== false) {
                $column['comment'] = Str::substr($column['comment'], 0, $endPosition);
            }
            $dataSets['messages'] .= "            self::get{$classCamelName}.'.required' => '请设置{$column['comment']}',\n";
        }

        $dataSets = array_map(function ($item) {
            return rtrim($item, "\n");
        }, $dataSets);

        $this->writeRequest($className, 'QueryRequest', '', '', '', '', '', $outDir);
        $this->writeRequest($className, 'UpsertRequest', $dataSets['required'], $dataSets['properties'], $dataSets['constants'], $dataSets['rules'], $dataSets['messages'], $outDir);
    }

    private function writeRequest($className, $suffix, $required, $properties, $constants, $rules, $messages, $outDir): void
    {
        $devConfig = new DevConfig();
        if ($devConfig->getMultiModule()) {
            $groupName = $this->getTableGroupName(Str::snake($className));
            $dist = $devConfig->getDist('app/Modules/'.$groupName.'/Http/Requests/'.$className);
            $namespace = "App\\Modules\\$groupName\\Http";
        } else {
            $dist = $devConfig->getDist('app/Api/'.$outDir.'/Requests/'.$className);
            $namespace = 'App\\Api\\'.$outDir;
        }

        $requestFile = $dist.'/'.$className.$suffix.'.php';
        if (! file_exists($requestFile) || $this->option('force')) {
            $this->ensureDirectoryExists($dist);
            GenerateStub::from(__DIR__.'/stubs/request/request.stub')
                ->to($dist)
                ->name($className.$suffix)
                ->ext('php')
                ->replaces([
                    'namespace' => $namespace,
                    'className' => $className,
                    'schema' => $className.$suffix,
                    'dataSets[required]' => $required,
                    'dataSets[properties]' => $properties,
                    'dataSets[constants]' => $constants,
                    'dataSets[rules]' => $rules,
                    'dataSets[messages]' => $messages,
                ])
                ->generate();
        }
    }

    private function responseTpl(string $className, array $columns, string $outDir): void
    {
        $devConfig = new DevConfig();
        if ($devConfig->getMultiModule()) {
            $groupName = $this->getTableGroupName(Str::snake($className));
            $dist = $devConfig->getDist('app/Modules/'.$groupName.'/Http/Responses/'.$className);
            $namespace = "App\\Modules\\$groupName\\Http";
        } else {
            $dist = $devConfig->getDist('app/Api/'.$outDir.'/Responses/'.$className);
            $namespace = 'App\\Api\\'.$outDir;
        }
        $this->ensureDirectoryExists($dist);

        $responseFile = $dist.'/'.$className.'QueryResponse.php';
        if (! file_exists($responseFile) || $this->option('force')) {
            GenerateStub::from(__DIR__.'/stubs/response/query.stub')
                ->to($dist)
                ->name($className.'QueryResponse')
                ->ext('php')
                ->replaces([
                    'namespace' => $namespace,
                    'className' => $className,
                ])
                ->generate();
        }

        $responseFile = $dist.'/'.$className.'DestroyResponse.php';
        if (! file_exists($responseFile) || $this->option('force')) {
            GenerateStub::from(__DIR__.'/stubs/response/destroy.stub')
                ->to($dist)
                ->name($className.'DestroyResponse')
                ->ext('php')
                ->replaces([
                    'namespace' => $namespace,
                    'className' => $className,
                ])
                ->generate();
        }

        $ignoreFields = ['deleted_time', 'password', 'password_salt'];

        $fields = "\n";
        $methods = "\n";
        foreach ($columns as $column) {
            if (in_array($column['name'], $ignoreFields)) {
                continue;
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

            $fields .= "    #[OA\Property(property: '{$column['camel_name']}', description: '{$column['comment']}', type: '{$column['swagger_type']}')]\n";
            $fields .= '    private '.$column['base_type'].' $'.$column['camel_name'].";\n\n";
            $methods .= $this->getSet($column['camel_name'], $column['base_type'], $column['comment'])."\n\n";
        }

        $fields = rtrim($fields, "\n");
        $methods = rtrim($methods, "\n");

        $responseFile = $dist.'/'.$className.'Response.php';
        if (! file_exists($responseFile) || $this->option('force')) {
            GenerateStub::from(__DIR__.'/stubs/response/response.stub')
                ->to($dist)
                ->name($className.'Response')
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
}
