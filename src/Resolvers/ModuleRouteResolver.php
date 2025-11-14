<?php

declare(strict_types=1);

namespace Juling\DevTools\Resolvers;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Juling\DevTools\Support\DevConfig;
use Juling\DevTools\Support\SchemaTrait;
use ReflectionClass;
use ReflectionException;
use ReflectionMethod;

class ModuleRouteResolver extends Foundation
{
    use SchemaTrait;

    /**
     * @throws ReflectionException
     */
    public function build(DevConfig $devConfig, array $data): void
    {
        $modules = glob(app_path('Modules/*'), GLOB_ONLYDIR);
        foreach ($modules as $modulePath) {
            $dist = $modulePath.'/Routes';
            $this->ensureDirectoryExists($dist);

            $moduleName = basename($modulePath);
            $controllerRoutes = $this->getControllerRoutes($modulePath);
            $viewRoutes = $this->getViewRoutes($moduleName, $modulePath);
            $routes = $this->getRouteContent($moduleName, $controllerRoutes, $viewRoutes);

            file_put_contents($dist.'/web.php', $this->getTemplate($routes));
        }
    }

    /**
     * @throws ReflectionException
     */
    private function getControllerRoutes(string $modulePath): array
    {
        $files = glob($modulePath.'/Controllers/*Controller.php');

        $routes = [];
        $ignoreControllers = config('devtools.ignore_controllers');
        foreach ($files as $file) {
            $file = str_replace('/', '\\', $file);
            preg_match('/(app\\\\.+?\\\\(\w+)Controller)\.php/', $file, $matches);
            if (! in_array(Str::studly($matches[2]), $ignoreControllers)) {
                $class = ucfirst($matches[1]);
                $classRoutes = $this->reflectionRoutes($class);
                $routes = array_merge($routes, $classRoutes);
            }
        }

        return $routes;
    }

    private function getViewRoutes(string $module, string $modulePath): string
    {
        $module = Str::lower($module);

        $exclude = ['login']; // config('devtools.exclude_views');
        $routeContent = "\n\n    // view route start";
        $routeContent .= "\n    Route::name('{$module}.')->group(function () {";

        $files = File::allFiles($modulePath.'/Views');
        foreach ($files as $file) {
            $view = Str::replace('\\', '/', $file->getPathname());
            preg_match('/Views\/(.+?)\.blade\.php/', $view, $matches);
            if (isset($matches[1])) {
                $routePath = $matches[1];
                $routeName = Str::replace('/', '.', $routePath);
                $routeView = $module.'::'.$routeName;
                if (in_array($routePath, $exclude) || str_contains($routePath, 'layouts')) {
                    continue;
                }
                if (Str::substr($routePath, -6) === '/index') {
                    $routePath = Str::substr($routePath, 0, -6);
                } elseif ($routePath === 'index') {
                    $routePath = '/';
                }
                $routeContent .= "\n        Route::view('{$routePath}', '{$routeView}')->name('{$routeName}');";
            }
        }
        $routeContent .= "\n    });";
        $routeContent .= "\n    // view route end";

        return $routeContent;
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

                $httpMethod = Str::lower(Arr::last(explode('\\', $methodAttribute->getName())));
                $path = $methodAttribute->getArguments()['path'];
                $summary = $methodAttribute->getArguments()['summary'];
                $routes[] = [
                    'httpMethod' => $httpMethod,
                    'path' => $path === '/' ? $path : ltrim($path, '/'),
                    'class' => $class,
                    'action' => $method->name,
                    'summary' => $summary,
                ];
            }
        }

        return $routes;
    }

    private function getRouteContent(string $module, array $routes, string $extras = ''): string
    {
        $module = Str::camel($module);
        $routeContent = '// '.$module.' route start';
        $routeContent .= "\nRoute::prefix('{$module}')->middleware('web')->group(function () {";
        foreach ($routes as $route) {
            $routeContent .= "\n    // ".$route['summary'];
            $routeContent .= "\n    Route::{$route['httpMethod']}('{$route['path']}', [\\{$route['class']}::class, '{$route['action']}'])";
            if ($route['httpMethod'] === 'get') {
                $name = 'index';
                if ($route['path'] !== '/') {
                    $name = Str::replace('/', '.', $route['path']);
                    $routeContent .= "->name('{$name}')";
                }
            }
            $routeContent .= ';';
        }
        $routeContent .= $extras;
        $routeContent .= "\n});";
        $routeContent .= "\n// end";

        return $routeContent;
    }

    private function getTemplate($content): string
    {
        return <<<EOF
<?php

// ==========================================================================
// Code generated by gen:module:route CLI tool. DO NOT EDIT.
// ==========================================================================

declare(strict_types=1);

use Illuminate\Support\Facades\Route;

$content

EOF;
    }
}
