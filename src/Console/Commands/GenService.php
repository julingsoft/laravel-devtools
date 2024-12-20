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
    protected $signature = 'gen:service';

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
        $tables = $this->getTables();
        foreach ($tables as $table) {
            $className = Str::studly($this->getSingular($table['name']));
            $this->serviceTpl($table['name'], $className);
            $this->bundleTpl($table['name'], $className);
        }
    }

    private function serviceTpl(string $tableName, string $className): void
    {
        $groupName = $this->getTableGroupName($tableName);
        $dist = app_path('Services/'.$groupName);
        $this->ensureDirectoryExists($dist);

        GenerateStub::from(__DIR__.'/stubs/service/service.stub')
            ->to($dist)
            ->name($className.'Service')
            ->ext('php')
            ->replaces([
                'groupName' => $groupName,
                'name' => $className,
            ])
            ->generate();
    }

    private function bundleTpl(string $tableName, string $className): void
    {
        $groupName = $this->getTableGroupName($tableName);
        $dist = app_path('Bundles/'.$groupName.'/Services');
        $this->ensureDirectoryExists($dist);

        $bundleFile = $dist.'/'.$className.'BundleService.php';
        if (! file_exists($bundleFile)) {
            GenerateStub::from(__DIR__.'/stubs/service/bundle.stub')
                ->to($dist)
                ->name($className.'BundleService')
                ->ext('php')
                ->replaces([
                    'groupName' => $groupName,
                    'name' => $className,
                ])
                ->generate();
        }
    }
}
