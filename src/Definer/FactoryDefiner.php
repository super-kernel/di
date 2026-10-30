<?php

declare(strict_types=1);

namespace SuperKernel\Di\Definer;

use InvalidArgumentException;
use ReflectionClass;
use SuperKernel\Attribute\Definer;
use SuperKernel\Attribute\FactoryProvider;
use SuperKernel\Contract\AnnotationCollectorInterface;
use SuperKernel\Di\Contract\DefinerInterface;
use SuperKernel\Di\Contract\DefinitionInterface;
use SuperKernel\Di\Definition\FactoryDefinition;
use SuperKernel\Di\Exception\NotFoundException;
use Throwable;

use function array_key_exists;
use function sprintf;

#[Definer(priority: 100)]
final readonly class FactoryDefiner implements DefinerInterface
{
    /** @var array<class-string, class-string> */
    private array $factoryProviders;

    public function __construct(AnnotationCollectorInterface $collector)
    {
        $factoryProviders = [];

        foreach ($collector->getClassesWithAttribute(FactoryProvider::class) as $factoryProvider => $attributes) {
            foreach ($attributes as $attribute) {
                $className = $attribute->attribute()->className;

                if (array_key_exists($className, $factoryProviders)) {
                    throw new InvalidArgumentException(sprintf(
                        'Factory provider for %s is declared by both %s and %s.',
                        $className,
                        $factoryProviders[$className],
                        $factoryProvider,
                    ));
                }

                $factoryProviders[$className] = $factoryProvider;
            }
        }

        $this->factoryProviders = $factoryProviders;
    }

    public function support(string $id): bool
    {
        return array_key_exists($id, $this->factoryProviders);
    }

    public function get(string $id): DefinitionInterface
    {
        if (!array_key_exists($id, $this->factoryProviders)) {
            throw NotFoundException::forIdentifier($id);
        }

        $factoryProvider = $this->factoryProviders[$id];

        try {
            $reflection = new ReflectionClass($factoryProvider);
        } catch (Throwable $throwable) {
            throw new InvalidArgumentException(sprintf(
                'Unable to reflect factory provider %s for %s.',
                $factoryProvider,
                $id,
            ), 0, $throwable);
        }

        $constructor = $reflection->getConstructor();

        if (
            !$reflection->isInstantiable()
            || (null !== $constructor && !$constructor->isPublic())
        ) {
            throw new InvalidArgumentException(sprintf(
                '%s must be instantiable with an implicit or public constructor.',
                $factoryProvider,
            ));
        }

        if (!$reflection->hasMethod('__invoke')) {
            throw new InvalidArgumentException(sprintf(
                '%s must declare a public non-static __invoke method.',
                $factoryProvider,
            ));
        }

        $invoke = $reflection->getMethod('__invoke');

        if (
            !$invoke->isPublic()
            || $invoke->isStatic()
            || $factoryProvider !== $invoke->getDeclaringClass()->getName()
        ) {
            throw new InvalidArgumentException(sprintf(
                '%s must declare a public non-static __invoke method.',
                $factoryProvider,
            ));
        }

        return new FactoryDefinition($id, $factoryProvider);
    }
}
