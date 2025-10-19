<?php
declare(strict_types=1);

namespace SuperKernel\Di\Definition;

use SuperKernel\Di\Contract\DefinitionInterface;

final readonly class ParameterDefinition implements DefinitionInterface
{
	public function __construct(private string $classname, private string $methodName)
	{
	}

	/**
	 * @return string
	 */
	public function getName(): string
	{
		return $this->classname;
	}

	public function getMethodName(): string
	{
		return $this->methodName;
	}

	/**
	 * @return string
	 */
	public function __toString(): string
	{
		return sprintf('Parameter[%s]', $this->getName());
	}
}