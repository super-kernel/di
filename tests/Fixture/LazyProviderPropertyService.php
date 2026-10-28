<?php

declare(strict_types=1);

namespace SuperKernel\Di\Tests\Fixture;

use SuperKernel\Attribute\Autowired;

final class LazyProviderPropertyService
{
    /**
     * Assigned through reflection by the property-injection test.
     *
     * @noinspection PhpPropertyOnlyWrittenInspection
     */
    #[Autowired]
    private ProviderSubcontract $provider;

    public function provider(): ProviderSubcontract
    {
        return $this->provider;
    }
}
