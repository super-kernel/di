<?php

declare(strict_types=1);

namespace SuperKernel\Di\Tests\Fixture;

final readonly class UnresolvableService
{
    public function __construct(public int $port)
    {
    }
}
