<?php

declare(strict_types=1);

namespace SuperKernel\Di\Tests\Fixture;

use RuntimeException;

final class LazyThrowingService
{
    public static int $constructions = 0;

    /**
     * Remains uninitialized so reading it triggers the failing lazy constructor.
     *
     * @noinspection PhpPropertyOnlyWrittenInspection
     */
    private int $state;

    public function __construct()
    {
        ++self::$constructions;

        throw new RuntimeException('lazy constructor failure');
    }

    public function state(): int
    {
        return $this->state;
    }
}
