<?php

declare(strict_types=1);

namespace SuperKernel\Di\Tests\Fixture\Provider;

final class NonPublicConstructorProvider
{
    private function __construct()
    {
    }

    /**
     * The mixed declaration exercises factory resolution independently of the returned value.
     *
     * @noinspection PhpMixedReturnTypeCanBeReducedInspection
     */
    public function __invoke(): mixed
    {
        return null;
    }
}
