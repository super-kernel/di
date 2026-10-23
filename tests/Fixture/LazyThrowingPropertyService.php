<?php

declare(strict_types=1);

namespace SuperKernel\Di\Tests\Fixture;

use SuperKernel\Attribute\Autowired;

final class LazyThrowingPropertyService
{
    /**
     * Assigned through reflection by the property-injection test.
     *
     * @noinspection PhpPropertyOnlyWrittenInspection
     */
    #[Autowired]
    private LazyThrowingService $dependency;

    public function dependency(): LazyThrowingService
    {
        return $this->dependency;
    }
}
