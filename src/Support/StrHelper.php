<?php

declare(strict_types=1);

namespace Juling\DevTools\Support;

class StrHelper
{
    public static function ltrim($value, $charlist): string
    {
        $str = mb_substr($value, 0, 1);

        if ($str === $charlist) {
            $value = mb_substr($value, 1);
        }

        return $value;
    }

    public static function rtrim($value, $charlist): string
    {
        $str = mb_substr($value, -1);

        if ($str === $charlist) {
            $value = mb_substr($value, 0, -1, 'UTF-8');
        }

        return $value;
    }
}
