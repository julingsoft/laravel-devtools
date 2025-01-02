<?php

declare(strict_types=1);

namespace Juling\DevTools\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;
use Juling\DevTools\Facades\GenerateStub;
use Juling\DevTools\Support\SchemaTrait;

class GenService extends Command
{
    use SchemaTrait;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'gen:service {--prefix=} {--table=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate service classes';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        $tables = $this->getTables($this->option('prefix'), $this->option('table'));
        foreach ($tables as $table) {
            $tableName = $table['name'];
            $className = Str::studly($this->getSingular($tableName));

            $this->serviceTpl($className, $tableName);
            $this->bundleTpl($className, $tableName);
        }
    }

    private function serviceTpl(string $className, string $tableName): void
    {
        $config = config('devtools');
        if ($config['multi_module']) {
            $groupName = $this->getTableGroupName($tableName);
            $dist = app_path('Modules/'.$groupName.'/Services');
            $namespace = "App\\Modules\\$groupName";
        } else {
            $dist = app_path('Services');
            $namespace = 'App';
        }
        $this->ensureDirectoryExists($dist);

        GenerateStub::from(__DIR__.'/stubs/service/service.stub')
            ->to($dist)
            ->name($className.'Service')
            ->ext('php')
            ->replaces([
                'namespace' => $namespace,
                'className' => $className,
            ])
            ->generate();
    }

    private function bundleTpl(string $className, string $tableName): void
    {
        $groupName = $this->getTableGroupName($tableName);
        $namespace = "App\\Bundles\\$groupName";
        $dist = app_path('Bundles/'.$groupName.'/Services');
        $this->ensureDirectoryExists($dist);

        $config = config('devtools');
        if ($config['multi_module']) {
            $groupName = $this->getTableGroupName($tableName);
            $useNamespace = "App\\Modules\\$groupName";
        } else {
            $useNamespace = 'App';
        }

        $bundleFile = $dist.'/'.$className.'BundleService.php';
        if (! file_exists($bundleFile)) {
            GenerateStub::from(__DIR__.'/stubs/service/bundle.stub')
                ->to($dist)
                ->name($className.'BundleService')
                ->ext('php')
                ->replaces([
                    'namespace' => $namespace,
                    'useNamespace' => $useNamespace,
                    'groupName' => $groupName,
                    'className' => $className,
                ])
                ->generate();
        }
    }
}
