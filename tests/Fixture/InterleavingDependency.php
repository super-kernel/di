<?php

declare(strict_types=1);

namespace SuperKernel\Di\Tests\Fixture;

use Fiber;
use Throwable;

final class InterleavingDependency
{
    public static int $constructions = 0;

    /** @throws Throwable When fixture setup or a supplied test callback fails */
    public function __construct()
    {
        $construction = ++self::$constructions;

        if (1 === $construction) {
            Fiber::suspend('dependency');
        }
    }
}
