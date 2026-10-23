<?php

declare(strict_types=1);

namespace SuperKernel\Di\Tests\Fixture\Provider;

use SuperKernel\Attribute\FactoryProvider;
use SuperKernel\Di\Tests\Fixture\Dependency;
use SuperKernel\Di\Tests\Fixture\FactoryProduct;

#[FactoryProvider(FactoryProduct::class)]
final class MismatchedProvider
{
    public function __invoke(): object
    {
        return new Dependency();
    }
}
