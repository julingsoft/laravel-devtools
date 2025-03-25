<?php

declare(strict_types=1);

namespace Juling\DevTools\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;
use Juling\DevTools\Facades\GenerateStub;
use Juling\DevTools\Support\DevConfig;
use Juling\DevTools\Support\SchemaTrait;

class GenEnums extends Command
{
    use SchemaTrait;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'gen:enums {--prefix=} {--table=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate enum classes';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        $tables = $this->getTables($this->option('prefix'), $this->option('table'));
        foreach ($tables as $table) {
            $tableName = $table['name'];
            $className = Str::studly($this->getSingular($tableName));

            $this->enumsTpl($className, $tableName);
        }
    }

    public function enumsTpl(string $className, string $tableName): void
    {
        $devConfig = new DevConfig();
        if ($devConfig->getMultiModule()) {
            $groupName = $this->getTableGroupName($tableName);
            $dist = $devConfig->getDist(basename(__CLASS__).'/Modules/'.$groupName.'/Enums');
            $namespace = "App\\Modules\\$groupName";
        } else {
            $dist = $devConfig->getDist(basename(__CLASS__).'/Enums');
            $namespace = 'App';
        }
        $this->ensureDirectoryExists($dist);

        $columns = $this->getTableColumns($tableName);
        foreach ($columns as $column) {
            if ($column['type'] === 'enum' || $column['type_name'] === 'tinyint') {
                $enumsClass = Str::studly($this->getSingular($column['name']));
                $comment = Str::replace('：', ':', $column['comment']);
                $comment = Str::replace('，', ',', $comment);

                $split = explode(':', $comment);
                if (count($split) < 2) {
                    continue;
                }

                [$enumsName, $enumsOptions] = $split;
                $enumsOptions = explode(',', $enumsOptions);
                $enumsOptions = array_map(function ($enumsOption) {
                    if (Str::contains($enumsOption, '-')) {
                        return explode('-', $enumsOption);
                    } else {
                        preg_match('/^(\d+)(.*)$/', $enumsOption, $matches);

                        return [$matches[1], $matches[2]];
                    }
                }, $enumsOptions);

                $enums = '';
                $enumsType = 'int';
                foreach ($enumsOptions as $enumOption) {
                    $caseName = $enumsClass.$enumOption[0];
                    $caseVal = $enumOption[0];
                    $enums .= <<<EOF


    /**
     * $enumOption[1]
     */
    case $caseName = $caseVal;
EOF;
                    if (! is_numeric($caseVal)) {
                        $enumsType = 'string';
                    }
                }

                GenerateStub::from(__DIR__.'/stubs/enums/enums.stub')
                    ->to($dist)
                    ->name($className.$enumsClass.'Enum')
                    ->ext('php')
                    ->replaces([
                        'namespace' => $namespace,
                        'className' => $className.$enumsClass,
                        'comment' => $enumsName,
                        'enums' => $enums,
                        'enumsType' => $enumsType,
                    ])
                    ->generate();
            }
        }
    }
}
