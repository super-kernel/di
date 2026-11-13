<?php

declare(strict_types=1);

namespace SuperKernel\Di\Tests\Fixture;

final readonly class NullableUnloadableDependencyService
{
    public function __construct(public ?UnloadableDependency $dependency)
    {
    }
}
