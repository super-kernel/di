<?php

/** @noinspection PhpUndefinedClassInspection The absent fixture type validates optional injection fallback. */

declare(strict_types=1);

namespace SuperKernel\Di\Tests\Fixture;

use SuperKernel\Attribute\Autowired;

final class PropertyInjectedService
{
    /**
     * Assigned through reflection by the property-injection test.
     *
     * @noinspection PhpPropertyOnlyWrittenInspection
     */
    #[Autowired]
    private readonly Dependency $dependency;

    #[Autowired(AnnotatedProvider::class)]
    protected ProviderContract $provider;

    #[Autowired]
    public string $name = 'default';

    /**
     * The absent type exercises fallback to the declared null value.
     */
    #[Autowired]
    public ?UndefinedPropertyDependency $fallback = null;

    public function dependency(): Dependency
    {
        return $this->dependency;
    }

    public function provider(): ProviderContract
    {
        return $this->provider;
    }
}
