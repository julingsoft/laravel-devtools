<?php

declare(strict_types=1);

namespace Juling\DevTools\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;
use Juling\DevTools\Facades\GenerateStub;
use Juling\DevTools\Support\SchemaTrait;

class GenEnums extends Command
{
    use SchemaTrait;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'gen:enums';

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
        $tables = $this->getTables();
        foreach ($tables as $key => $table) {
            $groupName = $this->getTableGroupName($table['name']);
            $comment = $table['comment'];
            if (Str::endsWith($comment, '表')) {
                $comment = Str::substr($comment, 0, -1);
            }
            $comment .= '模块';

            $this->enumsTpl($groupName, $table['name'], $comment);
        }
    }

    public function enumsTpl(string $groupName, string $tableName, string $comment): void
    {
        $dist = app_path('Bundles/'.$groupName.'/Enums');
        $this->ensureDirectoryExists($dist);

        $className = Str::studly($this->getSingular($tableName));
        $columns = $this->getTableColumns($tableName);
        foreach ($columns as $column) {
            if ($column['type'] === 'enum' || $column['type_name'] === 'tinyint') {
                $enumsClass = Str::studly($this->getSingular($column['name']));
                $comment = Str::replace('：', ':', $column['comment']);
                $comment = Str::replace('，', ',', $comment);
                [$enumsName, $enumsOptions] = explode(':', $comment);

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

                $className = $className.$enumsClass;
                GenerateStub::from(__DIR__.'/stubs/enums/enums.stub')
                    ->to($dist)
                    ->name($className.'Enum')
                    ->ext('php')
                    ->replaces([
                        'groupName' => $groupName,
                        'name' => $className,
                        'comment' => $enumsName,
                        'enums' => $enums,
                        'enumsType' => $enumsType,
                    ])
                    ->generate();
            }
        }
    }
}
