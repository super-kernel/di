<?php

declare(strict_types=1);

namespace SuperKernel\Di\Tests\Fixture;

final readonly class AmbiguousService
{
    public function __construct(public Dependency|Service $dependency)
    {
    }
}
