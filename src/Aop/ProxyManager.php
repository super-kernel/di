<?php
declare(strict_types=1);

namespace SuperKernel\Di\Aop;

use Closure;

/**
 * @ProxyManager
 * @\SuperKernel\Parser\ProxyManager
 */
final class ProxyManager
{
    static private array $storage = [];

    static public function exist(string $classname): bool
    {
        return isset(self::$storage [$classname]);
    }

    static public function insert(string $classname, Closure $closure): void
    {
        self::$storage [$classname] = $closure;
    }

    static public function remove(string $classname): void
    {
        unset(self::$storage [$classname]);
    }
}