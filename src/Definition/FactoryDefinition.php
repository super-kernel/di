<?php
declare(strict_types=1);

namespace SuperKernel\Di\Definition;

use SuperKernel\Di\Contract\DefinitionInterface;

final class FactoryDefinition implements DefinitionInterface
{
	public function __construct(private string $name, private readonly mixed $factory = null)
	{
	}

	/**
	 * @return string
	 */
	public function getName(): string
	{
		return $this->name;
	}

	public function setName(string $name): self
	{
		$this->name = $name;
		return $this;
	}

	public function getClassname(): string
	{
		return $this->factory ?? $this->name;
	}

	/**
	 * @return bool
	 */
	public function isInstantiable(): bool
	{
		return true;
	}

	/**
	 * @return string
	 */
	public function __toString(): string
	{
		return $this->name;
	}
}