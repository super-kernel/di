<?php

declare(strict_types=1);

namespace SuperKernel\Di\Tests\Fixture;

final class MethodArgumentService
{
    public function execute(
        Dependency       $dependency,
        string           $name = 'default',
        ?MissingContract $missing = null,
        Service       ...$services,
    ): void
    {
    }
}
