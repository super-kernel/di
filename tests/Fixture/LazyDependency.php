<?php

declare(strict_types=1);

namespace SuperKernel\Di\Tests\Fixture;

final class LazyDependency
{
    public static int $constructions = 0;

    private int $state;

    public function __construct()
    {
        ++self::$constructions;
        $this->state = 1;
    }

    public function ping(): string
    {
        return 'pong';
    }

    public function state(): int
    {
        return $this->state;
    }
}
