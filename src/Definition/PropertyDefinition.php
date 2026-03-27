<?php
declare(strict_types=1);

namespace SuperKernel\Di\Definition;

use SuperKernel\Di\Contract\DefinitionInterface;
use function sprintf;

final readonly class PropertyDefinition implements DefinitionInterface
{
	public function __construct(private string $propertyName, private string $className)
	{
	}

	public function getName(): string
	{
		return $this->propertyName;
	}

	public function getClassName(): string
	{
		return $this->className;
	}

	public function __toString(): string
	{
		return sprintf('Property[%s]', $this->getName());
	}
}