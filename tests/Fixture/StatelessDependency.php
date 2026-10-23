<?php

declare(strict_types=1);

namespace SuperKernel\Di\Tests\Fixture;

final class StatelessDependency
{
    public static int $constructions = 0;

    public int $count {
        get => self::$constructions;
    }

    public function __construct()
    {
        ++self::$constructions;
    }
}
