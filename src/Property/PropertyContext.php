<?php

declare(strict_types=1);

namespace SuperKernel\Di\Property;

use LogicException;
use ReflectionProperty;
use SuperKernel\Contract\PropertyContextInterface;

use function sprintf;

/**
 * One property assignment during container-owned resolution.
 *
 * @internal
 */
final class PropertyContext implements PropertyContextInterface
{
    private bool $hasAssignment = false;

    private mixed $assignment;

    /**
     * @param class-string $className
     */
    public function __construct(
        private readonly string             $className,
        private readonly ReflectionProperty $property,
    )
    {
    }

    public function className(): string
    {
        return $this->className;
    }

    public function property(): ReflectionProperty
    {
        return $this->property;
    }

    public function hasAssignment(): bool
    {
        return $this->hasAssignment;
    }

    public function assignment(): mixed
    {
        if (!$this->hasAssignment) {
            throw new LogicException(sprintf(
                'Property %s::$%s has no assignment.',
                $this->className,
                $this->property->getName(),
            ));
        }

        return $this->assignment;
    }

    public function assign(mixed $value): void
    {
        if ($this->hasAssignment) {
            throw new LogicException(sprintf(
                'Property %s::$%s already has an assignment.',
                $this->className,
                $this->property->getName(),
            ));
        }

        $this->assignment = $value;
        $this->hasAssignment = true;
    }
}
