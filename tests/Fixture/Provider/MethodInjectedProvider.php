<?php

declare(strict_types=1);

namespace SuperKernel\Di\Tests\Fixture\Provider;

use SuperKernel\Di\Tests\Fixture\Dependency;
use SuperKernel\Di\Tests\Fixture\MethodInjectedProduct;
use SuperKernel\Di\Tests\Fixture\Service;

final readonly class MethodInjectedProvider
{
    public function __construct(public Dependency $constructorDependency)
    {
    }

    public function __invoke(Service $methodDependency): MethodInjectedProduct
    {
        return new MethodInjectedProduct($this->constructorDependency, $methodDependency);
    }
}
