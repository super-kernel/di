<?php
declare(strict_types=1);

namespace SuperKernel\Di\Definition;

use SuperKernel\Di\Contract\DefinitionInterface;
use function sprintf;

final readonly class ObjectDefinition implements DefinitionInterface
{
	public function __construct(private string $className)
	{
	}

	public function getName(): string
	{
		return $this->className;
	}

	public function __toString(): string
	{
		return sprintf('Object[%s]', $this->getName());
	}
}