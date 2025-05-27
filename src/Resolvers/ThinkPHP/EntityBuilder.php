<?php

declare(strict_types=1);

namespace Juling\DevTools\Resolvers\ThinkPHP;

use Juling\DevTools\Contracts\EntityContract;
use Juling\DevTools\Support\DevConfig;
use Juling\DevTools\Support\SchemaTrait;

class EntityBuilder implements EntityContract
{
    use SchemaTrait;

    public function build(DevConfig $devConfig, array $langOptions, string $tableName, string $comment): void
    {

    }
}