<?php
declare(strict_types=1);

namespace SuperKernel\Di\Definition;

use SuperKernel\Contract\ReflectorInterface;
use SuperKernel\Di\Contract\DefinitionInterface;
use function sprintf;

final readonly class PropertyDefinition implements DefinitionInterface
{
	public function __construct(private string $className)
	{
	}

	public function getName(): string
	{
		return $this->className;
	}

	public function getProperties(ReflectorInterface $reflector): array
	{
		return $reflector->reflectClass($this->className)->getProperties();
	}

	public function __toString(): string
	{
		return sprintf('Property[%s]', $this->getName());
	}
}