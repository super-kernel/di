<?php
declare(strict_types=1);

namespace SuperKernel\Di\Definition;

use SuperKernel\Di\Contract\DefinitionInterface;

final readonly class InterfaceDefinition implements DefinitionInterface
{
	private string $classname;

	public function __construct(string $classname)
	{
		$this->classname = $classname;
	}

	/**
	 * @return string
	 */
	public function getName(): string
	{
		return $this->classname;
	}

	/**
	 * @return string
	 */
	public function __toString(): string
	{
		return sprintf('Interface[%s]', $this->getName());
	}
}