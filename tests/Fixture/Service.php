<?php

declare(strict_types=1);

namespace SuperKernel\Di\Tests\Fixture;

final readonly class Service
{
    public function __construct(public Dependency $dependency)
    {
    }
}
