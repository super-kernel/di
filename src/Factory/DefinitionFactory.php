<?php

declare(strict_types=1);

namespace SuperKernel\Di\Factory;

use InvalidArgumentException;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use ReflectionClass;
use SplPriorityQueue;
use SuperKernel\Attribute\Definer as DefinerAttribute;
use SuperKernel\Contract\AnnotationCollectorInterface;
use SuperKernel\Di\Contract\DefinerInterface;
use SuperKernel\Di\Contract\DefinitionFactoryInterface;
use SuperKernel\Di\Contract\DefinitionInterface;
use SuperKernel\Di\Exception\ResolutionException;
use Throwable;

use function array_key_exists;
use function sprintf;

/**
 * Selects definitions from an immutable, priority-ordered definer queue.
 */
final class DefinitionFactory implements DefinitionFactoryInterface
{
    /** @var SplPriorityQueue<DefinerInterface, array{int, int}> */
    private SplPriorityQueue $definers {
        /**
         * @throws ContainerExceptionInterface When the annotation collector is missing or cannot be resolved
         * @throws InvalidArgumentException    When an annotated definer cannot be reflected, validated, or constructed
         */
        get {
            if (!isset($this->definers)) {
                $collector = ReservedCollector::from($this->container);
                $definers = new SplPriorityQueue();
                $definers->setExtractFlags(SplPriorityQueue::EXTR_DATA);
                $position = 0;

                foreach ($collector->getClassesWithAttribute(DefinerAttribute::class) as $className => [$attribute]) {
                    try {
                        $reflection = new ReflectionClass($className);
                    } catch (Throwable $throwable) {
                        throw new InvalidArgumentException(sprintf(
                            'Unable to reflect annotated definer %s.',
                            $className,
                        ), 0, $throwable);
                    }

                    if (!$reflection->isInstantiable() || !$reflection->implementsInterface(DefinerInterface::class)) {
                        throw new InvalidArgumentException(sprintf(
                            'Annotated definer %s must be an instantiable implementation of %s.',
                            $className,
                            DefinerInterface::class,
                        ));
                    }

                    $constructor = $reflection->getConstructor();
                    $requiredParameters = $constructor?->getNumberOfRequiredParameters() ?? 0;

                    if (1 < $requiredParameters) {
                        throw new InvalidArgumentException(sprintf(
                            'Annotated definer %s must have no required constructor parameters or accept '
                            . '%s as its only required bootstrap dependency.',
                            $className,
                            AnnotationCollectorInterface::class,
                        ));
                    }

                    try {
                        $definer = 0 === $requiredParameters
                            ? $reflection->newInstance()
                            : $reflection->newInstance($collector);
                    } catch (Throwable $throwable) {
                        throw new InvalidArgumentException(sprintf(
                            'Unable to create annotated definer %s with the available bootstrap dependencies.',
                            $className,
                        ), 0, $throwable);
                    }

                    if (!$definer instanceof DefinerInterface) {
                        throw new InvalidArgumentException(sprintf(
                            'Annotated definer %s must implement %s.',
                            $className,
                            DefinerInterface::class,
                        ));
                    }

                    $definers->insert(
                        $definer,
                        [
                            $attribute->attribute()->priority,
                            -$position,
                        ],
                    );
                    ++$position;
                }

                $this->definers = $definers;
            }

            return $this->definers;
        }
    }

    /** @var array<string, DefinitionInterface|null> */
    private array $definitions = [];

    public function __construct(private readonly ContainerInterface $container)
    {
    }

    public function get(string $id): ?DefinitionInterface
    {
        if (array_key_exists($id, $this->definitions)) {
            return $this->definitions[$id];
        }

        try {
            foreach (clone $this->definers as $definer) {
                if ($definer->support($id)) {
                    return $this->definitions[$id] = $definer->get($id);
                }
            }
        } catch (Throwable $throwable) {
            if ($throwable instanceof ContainerExceptionInterface) {
                throw $throwable;
            }

            throw ResolutionException::definitionLookupFailed($id, $throwable);
        }

        return $this->definitions[$id] = null;
    }
}
