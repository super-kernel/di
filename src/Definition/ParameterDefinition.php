<?php
declare(strict_types=1);

namespace SuperKernel\Di\Definition;

use SuperKernel\Di\Contract\DefinitionInterface;
use function sprintf;

final readonly class ParameterDefinition implements DefinitionInterface
{
	/**
	 * @param string $className
	 * @param string $methodName
	 * @param array  $parameters Allows developers to build targets with any custom parameters.
	 */
	public function __construct(private string $className, private string $methodName, private array $parameters = [])
	{
	}

	/**
	 * @return string
	 */
	public function getName(): string
	{
		return $this->className;
	}

	public function getMethodName(): string
	{
		return $this->methodName;
	}

	public function getParameters(): array
	{
		return $this->parameters;
	}

	/**
	 * @return string
	 */
	public function __toString(): string
	{
		return sprintf('Parameter[%s]', $this->getName());
	}
}