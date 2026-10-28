<?php

declare(strict_types=1);

namespace SuperKernel\Di\Tests\Fixture;

use SuperKernel\Attribute\Autowired;

final class MissingProviderPropertyService
{
    #[Autowired]
    public ?MissingContract $dependency = null;
}
