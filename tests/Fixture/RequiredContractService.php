<?php

declare(strict_types=1);

namespace SuperKernel\Di\Tests\Fixture;

final readonly class RequiredContractService
{
    public function __construct(public MissingContract $dependency)
    {
    }
}
