<?php

declare(strict_types=1);

namespace SuperKernel\Di\Tests\Fixture;

use Fiber;
use SuperKernel\Contract\ContainerInterface;
use Throwable;

final readonly class InterleavingRootService
{
    public InterleavingDependency $dependency;

    /** @throws Throwable When fixture setup or a supplied test callback fails */
    public function __construct(ContainerInterface $container)
    {
        Fiber::suspend('root');

        $this->dependency = $container->get(InterleavingDependency::class);
    }
}
