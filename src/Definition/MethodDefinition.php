<?php
declare(strict_types=1);

namespace SuperKernel\Di\Definition;

use SuperKernel\Di\Contract\DefinitionInterface;
use function sprintf;

final readonly class MethodDefinition implements DefinitionInterface
{
	public function __construct(private string $className, private string $methodName, private array $parameters = [])
	{
	}

	public function getName(): string
	{
		return $this->methodName;
	}

	public function getClassName(): string
	{
		return $this->className;
	}

	public function getParameters(): array
	{
		return $this->parameters;
	}

	public function __toString(): string
	{
		return sprintf('Parameter[%s]', $this->getName());
	}
}