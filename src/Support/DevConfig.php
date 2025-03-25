<?php

declare(strict_types=1);

namespace Juling\DevTools\Support;

use Illuminate\Support\Str;

class DevConfig
{
    private array $config;

    public function __construct()
    {
        $this->config = config('devtools');
    }

    public function getDist(string $moduleName = ''): string
    {
        $dist = Str::rtrim($this->config['dist'], '/');
        if (! empty($moduleName)) {
            $dist .= '/'.$moduleName;
        }
        return $dist;
    }

    public function getIgnoreTables(): array
    {
        return $this->config['ignore_tables'];
    }

    public function getIgnoreControllers(): array
    {
        return $this->config['ignore_controllers'];
    }

    public function getIgnoreSingular(): bool
    {
        return $this->config['ignore_singular'];
    }

    public function getMultiModule(): bool
    {
        return $this->config['multi_module'];
    }
}
