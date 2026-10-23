<?php

declare(strict_types=1);

namespace SuperKernel\Di\Tests\Fixture;

final readonly class MethodInjectedProduct
{
    public function __construct(public Dependency $constructorDependency, public Service    $methodDependency)
    {
    }
}
