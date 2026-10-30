<?php

declare(strict_types=1);

namespace SuperKernel\Di\Definer;

use InvalidArgumentException;
use ReflectionClass;
use SuperKernel\Attribute\Definer;
use SuperKernel\Attribute\Provider;
use SuperKernel\Contract\AnnotationCollectorInterface;
use SuperKernel\Di\Contract\DefinerInterface;
use SuperKernel\Di\Contract\DefinitionInterface;
use SuperKernel\Di\Definition\ClassDefinition;
use SuperKernel\Di\Definition\ProviderDefinition;
use SuperKernel\Di\Exception\NotFoundException;
use Throwable;

use function array_key_exists;
use function is_a;
use function sprintf;

#[Definer(priority: 200)]
final readonly class ProviderDefiner implements DefinerInterface
{
    /** @var array<class-string, class-string> */
    private array $providers;

    public function __construct(AnnotationCollectorInterface $collector)
    {
        $providers = [];

        foreach ($collector->getClassesWithAttribute(Provider::class) as $provider => [$attribute]) {
            $className = $attribute->attribute()->className;

            if (array_key_exists($className, $providers)) {
                throw new InvalidArgumentException(sprintf(
                    'Provider %s is declared by both %s and %s.',
                    $className,
                    $providers[$className],
                    $provider,
                ));
            }

            $providers[$className] = $provider;
        }

        $this->providers = $providers;
    }

    public function support(string $id): bool
    {
        return array_key_exists($id, $this->providers);
    }

    public function get(string $id): DefinitionInterface
    {
        if (!array_key_exists($id, $this->providers)) {
            throw NotFoundException::forIdentifier($id);
        }

        $provider = $this->providers[$id];

        try {
            $reflection = new ReflectionClass($provider);
        } catch (Throwable $throwable) {
            throw new InvalidArgumentException(sprintf(
                'Unable to reflect provider %s for %s.',
                $provider,
                $id,
            ), 0, $throwable);
        }

        if ($provider === $id) {
            if (!$reflection->isInstantiable()) {
                throw new InvalidArgumentException(sprintf(
                    'Self-provided class %s must be instantiable.',
                    $provider,
                ));
            }

            return new ClassDefinition($provider);
        }

        if (!is_a($provider, $id, true)) {
            throw new InvalidArgumentException(sprintf(
                '%s must be a distinct provider of %s.',
                $provider,
                $id,
            ));
        }

        return new ProviderDefinition($id, $provider);
    }
}
