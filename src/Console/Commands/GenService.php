<?php

declare(strict_types=1);

namespace Juling\DevTools\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;
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
            $this->serviceTpl($className);
            $this->bundleTpl($table['name'], $className);
        }
    }

    private function serviceTpl(string $className): void
    {
        $dist = app_path('Services');
        if (! is_dir($dist)) {
            $this->ensureDirectoryExists($dist);
        }

        $serviceFile = $dist.'/'.$className.'Service.php';
        if (! file_exists($serviceFile)) {
            $content = file_get_contents(__DIR__.'/stubs/service/service.stub');
            $content = str_replace([
                '{$name}',
            ], [
                $className,
            ], $content);
            file_put_contents($serviceFile, $content);
        }
    }

    private function bundleTpl(string $tableName, string $className): void
    {
        $groupName = $this->getTableGroupName($tableName);
        $dist = app_path('Bundles/'.$groupName.'/Services');
        if (! is_dir($dist)) {
            $this->ensureDirectoryExists($dist);
        }

        $bundleFile = $dist.'/'.$className.'BundleService.php';
        if (! file_exists($bundleFile)) {
            $content = file_get_contents(__DIR__.'/stubs/service/bundle.stub');
            $content = str_replace([
                '{$group}',
                '{$name}',
            ], [
                $groupName,
                $className,
            ], $content);
            file_put_contents($bundleFile, $content);
        }
    }
}
