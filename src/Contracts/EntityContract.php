<?php

declare(strict_types=1);

namespace Juling\DevTools\Contracts;

use Juling\DevTools\Support\DevConfig;

interface EntityContract
{
    public function build(DevConfig $devConfig, array $langOptions, string $tableName, string $comment);
}
