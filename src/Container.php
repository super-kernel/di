<?php

declare(strict_types=1);

namespace SuperKernel\Di;

use Psr\Container\ContainerInterface as PsrContainerInterface;
use SuperKernel\Contract\AnnotationCollectorInterface;
use SuperKernel\Contract\ContainerInterface;
use SuperKernel\Di\Contract\DefinitionFactoryInterface;
use SuperKernel\Di\Contract\DefinitionInterface;
use SuperKernel\Di\Contract\ResolverFactoryInterface;
use SuperKernel\Di\Contract\RetainedEntriesInterface;
use SuperKernel\Di\Exception\NotFoundException;
use SuperKernel\Di\Exception\ResolutionException;
use SuperKernel\Di\Factory\DefinitionFactory;
use SuperKernel\Di\Factory\ResolverFactory;
use SuperKernel\Di\Storage\RetainedEntries;
use Throwable;

use function class_exists;
use function get_debug_type;
use function interface_exists;

/**
 * Resolves and retains shared application services through PSR-11.
 */
final readonly class Container implements ContainerInterface
{
    private RetainedEntriesInterface $retainedEntries;

    private DefinitionFactoryInterface $definitionFactory;

    private ResolverFactoryInterface $resolverFactory;

    /**
     * @param array<string, mixed> $entries Shared services already created in this process;
     *                                      reserved IDs remain canonical
     *
     * @throws ResolutionException When a supplied class or interface entry has an incompatible value
     */
    public function __construct(AnnotationCollectorInterface $annotationCollector, array $entries = [])
    {
        $this->definitionFactory = new DefinitionFactory($this);
        $this->resolverFactory = new ResolverFactory($this);
        $this->retainedEntries = new RetainedEntries();
        $this->retainedEntries->retain(self::class, $this);
        $this->retainedEntries->retain(ContainerInterface::class, $this);
        $this->retainedEntries->retain(PsrContainerInterface::class, $this);
        $this->retainedEntries->retain(AnnotationCollectorInterface::class, $annotationCollector);
        $this->retainedEntries->retain(DefinitionFactoryInterface::class, $this->definitionFactory);
        $this->retainedEntries->retain(DefinitionFactory::class, $this->definitionFactory);
        $this->retainedEntries->retain(ResolverFactoryInterface::class, $this->resolverFactory);
        $this->retainedEntries->retain(ResolverFactory::class, $this->resolverFactory);

        foreach ($entries as $id => $instance) {
            if ($this->retainedEntries->has($id)) {
                continue;
            }

            if ((class_exists($id) || interface_exists($id)) && !$instance instanceof $id) {
                throw ResolutionException::entryTypeMismatch($id, get_debug_type($instance));
            }

            $this->retainedEntries->retain($id, $instance);
        }
    }

    public function has(string $id): bool
    {
        if ($this->retainedEntries->has($id)) {
            return true;
        }

        try {
            return $this->definitionFactory->get($id) instanceof DefinitionInterface;
        } catch (Throwable) {
            return false;
        }
    }

    public function get(string $id): mixed
    {
        if ($this->retainedEntries->has($id)) {
            return $this->retainedEntries->get($id);
        }

        $definition = $this->definitionFactory->get($id);

        if (!$definition instanceof DefinitionInterface) {
            throw NotFoundException::forIdentifier($id);
        }

        $instance = $this->resolverFactory->resolve($definition);

        if (
            (class_exists($id) || interface_exists($id))
            && !$instance instanceof $id
        ) {
            throw ResolutionException::entryTypeMismatch($id, get_debug_type($instance));
        }

        return $this->retainedEntries->retain($id, $instance);
    }

}
