<?php

declare(strict_types=1);

namespace SuperKernel\Di\Tests\Fixture\Provider;

use SuperKernel\Attribute\FactoryProvider;
use SuperKernel\Di\Tests\Fixture\FactoryProduct;

#[FactoryProvider(className: FactoryProduct::class)]
final class AnnotatedProvider
{
    /**
     * The mixed declaration exercises factory resolution independently of the returned value.
     *
     * @noinspection PhpMixedReturnTypeCanBeReducedInspection
     */
    public function __invoke(): mixed
    {
        return new FactoryProduct();
    }
}
