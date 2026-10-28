<?php

declare(strict_types=1);

namespace SuperKernel\Di\Tests\Fixture;

final readonly class NullableService
{
    public function __construct(public ?MissingContract $dependency)
    {
    }
}
