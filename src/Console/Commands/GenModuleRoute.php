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

class GenModuleRoute extends Command
{
    use SchemaTrait;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'gen:module:route';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate project module routes';

    protected array $ignoreList = ['Base'];

    /**
     * Execute the console command.
     *
     * @throws ReflectionException
     */
    public function handle(): void
    {
        $devConfig = new DevConfig();
        $modules = glob(app_path('Modules/*'), GLOB_ONLYDIR);
        foreach ($modules as $modulePath) {
            $module = basename($modulePath);

            $dist = $devConfig->getDist(__CLASS__.'/'.$module.'/Routes');
            $this->ensureDirectoryExists($dist);

            $controllers = glob($modulePath.'/Http/Controllers/*Controller.php');
            $routes = $this->getRouteContent(Str::camel($module), $this->getRoutes($controllers));

            file_put_contents($dist.'/route.php', $this->getTemplate($routes));
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
                if (! isset($methodAttribute->getArguments()['path'])) {
                    exit('Route path not found:'.$class.'@'.$method->name);
                }

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

    private function getRouteContent(string $module, array $routes): string
    {
        $routeContent = '// '.$module.' route start';
        $routeContent .= "\nRoute::prefix('{$module}')->group(function () {";
        foreach ($routes as $route) {
            $routeContent .= "\n    // ".$route['summary'];
            $routeContent .= "\n    Route::{$route['httpMethod']}('{$route['path']}', [\\{$route['class']}::class, '{$route['action']}'])";
            if ($route['httpMethod'] === 'get') {
                $name = Str::replace('/', '.', $route['path']);
                // $routeContent .= "->name('$name')";
            }
            $routeContent .= ';';
        }
        $routeContent .= "\n});";
        $routeContent .= "\n// end";

        return $routeContent;
    }

    private function getTemplate($content): string
    {
        return <<<EOF
<?php

// ==========================================================================
// Code generated by module:gen:route CLI tool. DO NOT EDIT.
// ==========================================================================

declare(strict_types=1);

use Illuminate\Support\Facades\Route;

$content

EOF;
    }
}
