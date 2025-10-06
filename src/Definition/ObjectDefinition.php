<?php
declare(strict_types=1);

namespace SuperKernel\Di\Definition;

use SuperKernel\Di\Collector\ReflectionManager;
use SuperKernel\Di\Contract\DefinitionInterface;

final class ObjectDefinition implements DefinitionInterface
{
	private bool $classExists;

	private ?bool $instantiable = null;

	public function __construct(private string $name, private ?string $classname = null)
	{
		$this->setClassname($classname ?? $name);
	}

	public function __toString(): string
	{
		return sprintf('Object[%s]', $this->getClassName());
	}

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
		return $this->classname ?? $this->name;
	}

	public function setClassname(?string $classname = null): void
	{
		$this->classname = $classname;
	}

	public function isClassExists(): bool
	{
		return $this->classExists;
	}

	public function isInstantiable(): bool
	{
		if (is_null($this->instantiable)) {
			$classname = $this->getClassname();

			$this->classExists = class_exists($classname) || interface_exists($classname);

			if (!$this->classExists) {
				$this->instantiable = false;
			}

			$this->instantiable = ReflectionManager::reflectClass($classname)->isInstantiable();
		}

		return $this->instantiable;
	}
}