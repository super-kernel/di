<?php

declare(strict_types=1);

namespace SuperKernel\Di\Tests\Fixture;

final readonly class DefaultedService
{
    public function __construct(public string $name = 'default')
    {
    }
}
