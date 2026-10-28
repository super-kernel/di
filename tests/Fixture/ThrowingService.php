<?php

declare(strict_types=1);

namespace SuperKernel\Di\Tests\Fixture;

use RuntimeException;

final class ThrowingService
{
    public function __construct()
    {
        throw new RuntimeException('constructor failure');
    }
}
