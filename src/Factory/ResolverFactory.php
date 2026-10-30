<?php

declare(strict_types=1);

namespace SuperKernel\Di\Factory;

use InvalidArgumentException;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use SplPriorityQueue;
use SuperKernel\Attribute\Resolver as ResolverAttribute;
use SuperKernel\Di\Contract\DefinitionInterface;
use SuperKernel\Di\Contract\ResolverFactoryInterface;
use SuperKernel\Di\Contract\ResolverInterface;
use SuperKernel\Di\Exception\ResolutionException;
use Throwable;

use function is_subclass_of;
use function sprintf;

/**
 * Resolves definitions through priority-discovered, container-bound resolvers.
 */
final class ResolverFactory implements ResolverFactoryInterface
{
    /** @var SplPriorityQueue<ResolverInterface, array{int, int}> */
    private SplPriorityQueue $resolvers {
        /**
         * @throws ContainerExceptionInterface When the annotation collector is missing or cannot be resolved
         * @throws InvalidArgumentException    When an annotated resolver is not a ResolverInterface or cannot be
         *                                     constructed with the root container
         */
        get {
            if (!isset($this->resolvers)) {
                $collector = ReservedCollector::from($this->container);

                $resolvers = new SplPriorityQueue();
                $resolvers->setExtractFlags(SplPriorityQueue::EXTR_DATA);
                $position = 0;

                foreach ($collector->getClassesWithAttribute(ResolverAttribute::class) as $className => [$attribute]) {
                    if (!is_subclass_of($className, ResolverInterface::class)) {
                        throw new InvalidArgumentException(sprintf(
                            'Annotated resolver %s must implement %s.',
                            $className,
                            ResolverInterface::class,
                        ));
                    }

                    try {
                        $resolver = new $className($this->container);
                    } catch (Throwable $throwable) {
                        throw new InvalidArgumentException(sprintf(
                            'Unable to create annotated resolver %s with the root container.',
                            $className,
                        ), 0, $throwable);
                    }

                    $resolvers->insert(
                        $resolver,
                        [
                            $attribute->attribute()->priority,
                            -$position,
                        ],
                    );
                    ++$position;
                }

                $this->resolvers = $resolvers;
            }

            return $this->resolvers;
        }
    }

    public function __construct(private readonly ContainerInterface $container)
    {
    }

    public function resolve(DefinitionInterface $definition): mixed
    {
        try {
            foreach (clone $this->resolvers as $resolver) {
                if ($resolver->support($definition)) {
                    return $resolver->resolve($definition);
                }
            }
        } catch (Throwable $throwable) {
            if ($throwable instanceof ContainerExceptionInterface) {
                throw $throwable;
            }

            throw ResolutionException::resolverDispatchFailed($definition, $throwable);
        }

        throw ResolutionException::unsupportedDefinition($definition);
    }

    public function support(DefinitionInterface $definition): bool
    {
        foreach (clone $this->resolvers as $resolver) {
            if ($resolver->support($definition)) {
                return true;
            }
        }

        return false;
    }
}
