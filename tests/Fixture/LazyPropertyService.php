<?php

declare(strict_types=1);

namespace SuperKernel\Di\Tests\Fixture;

use SuperKernel\Attribute\Autowired;

final class LazyPropertyService
{
    /**
     * Assigned through reflection by the property-injection test.
     *
     * @noinspection PhpPropertyOnlyWrittenInspection
     */
    #[Autowired]
    private LazyDependency $dependency;

    public function dependency(): LazyDependency
    {
        return $this->dependency;
    }
}
