<?php

declare(strict_types=1);

namespace SuperKernel\Di\Definition;

use SuperKernel\Di\Contract\DefinitionInterface;

/**
 * Describes the ordered parameters required by one class method.
 */
final readonly class MethodDefinition implements DefinitionInterface
{
    public string $methodName;

    /**
     * @param class-string $className
     */
    public function __construct(
        public string $className,
        ?string       $methodName = null,
        /** @var array<int|string, mixed> */
        public array  $parameters = [],
    )
    {
        $this->methodName = $methodName ?? '__construct';
    }

    public function identifier(): string
    {
        return self::class . ':' . $this->className . '::' . $this->methodName;
    }
}
