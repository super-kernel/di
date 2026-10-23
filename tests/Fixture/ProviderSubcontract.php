<?php

declare(strict_types=1);

namespace SuperKernel\Di\Tests\Fixture;

use SuperKernel\Attribute\Provider;

#[Provider(ProviderContract::class)]
interface ProviderSubcontract extends ProviderContract
{
}
