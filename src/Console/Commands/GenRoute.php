<?php

declare(strict_types=1);

namespace Juling\DevTools\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Juling\DevTools\Support\DevConfig;
use Juling\DevTools\Support\SchemaTrait;
use ReflectionClass;
use ReflectionException;
use ReflectionMethod;

class GenRoute extends Command
{
    use SchemaTrait;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'gen:route';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate project routes';

    protected array $ignoreList = ['Base'];

    /**
     * Execute the console command.
     *
     * @throws ReflectionException
     */
    public function handle(): void
    {
        $devConfig = new DevConfig();
        $dirs = glob(app_path('API/*'), GLOB_ONLYDIR);
        foreach ($dirs as $dir) {
            $module = basename($dir);

            $dist =$devConfig->getDist(basename(__CLASS__).'/'.$module.'/Routes');
            $this->ensureDirectoryExists($dist);

            $routes = $this->getRoutes(array_merge(
                glob($dir.'/Controllers/*Controller.php'),
                glob(app_path('Bundles/*/Controllers/'.$module.'/*Controller.php'))
            ));

            $this->genRoutes(Str::camel($module), $routes, $dist.'/api.php');
        }
    }

    /**
     * @throws ReflectionException
     */
    private function getRoutes(array $files): array
    {
        $routes = [];

        foreach ($files as $file) {
            $file = str_replace('/', '\\', $file);
            preg_match('/(app\\\\.+?\\\\(\w+)Controller)\.php/', $file, $matches);
            if (! in_array($matches[2], $this->ignoreList)) {
                $class = ucfirst($matches[1]);
                $classRoutes = $this->reflectionRoutes($class);
                $routes = array_merge($routes, $classRoutes);
            }
        }

        return $routes;
    }

    /**
     * @throws ReflectionException
     */
    private function reflectionRoutes(string $class): array
    {
        $reflectionClass = new ReflectionClass($class);
        $methods = $reflectionClass->getMethods(ReflectionMethod::IS_PUBLIC);
        $methods = array_filter($methods, function ($item) use ($class) {
            return $item->class === $class;
        });

        $routes = [];
        foreach ($methods as $method) {
            $methodAttributes = $reflectionClass->getMethod($method->name)->getAttributes();
            if (isset($methodAttributes[0])) {
                $methodAttribute = $methodAttributes[0];
                $routes[] = [
                    'httpMethod' => Str::lower(Arr::last(explode('\\', $methodAttribute->getName()))),
                    'path' => ltrim($methodAttribute->getArguments()['path'], '/'),
                    'class' => $class,
                    'action' => $method->name,
                    'summary' => $methodAttribute->getArguments()['summary'],
                ];
            }
        }

        return $routes;
    }

    private function genRoutes(string $module, array $routes, string $routeFile): void
    {
        $routeContent = '// Route start';
        $routeContent .= "\nRoute::prefix('{$module}')->group(function () {";
        foreach ($routes as $route) {
            $routeContent .= "\n    // ".$route['summary'];
            $routeContent .= "\n    Route::{$route['httpMethod']}('{$route['path']}', [\\{$route['class']}::class, '{$route['action']}'])";
            // if ($route['httpMethod'] === 'get') {
            //     $name = Str::replace('/', '.', $route['path']);
            //     $routeContent .= "->name('$name')";
            // }
            $routeContent .= ';';
        }
        $routeContent .= "\n});";
        $routeContent .= "\n// end";

        $content = $this->getTemplate($routeContent);
        file_put_contents($routeFile, $content);
    }

    private function getTemplate($content): string
    {
        return <<<EOF
<?php

// ==========================================================================
// Code generated by gen:route CLI tool. DO NOT EDIT.
// ==========================================================================

declare(strict_types=1);

use Illuminate\Support\Facades\Route;

$content

EOF;
    }
}
