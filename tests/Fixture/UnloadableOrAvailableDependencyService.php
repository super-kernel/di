<?php

declare(strict_types=1);

namespace SuperKernel\Di\Tests\Fixture;

final readonly class UnloadableOrAvailableDependencyService
{
    public function __construct(public UnloadableDependency|Dependency $dependency)
    {
    }
}
