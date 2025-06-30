<?php
declare(strict_types=1);

namespace SuperKernel\Di\Definition;

use ReflectionMethod;
use SuperKernel\Di\Collector\ReflectionManager;
use SuperKernel\Di\Contract\DefinitionInterface;

final class ParameterDefinition implements DefinitionInterface
{
	public function __construct(private string $name, private string $method, private array $parameters = [])
	{
	}

	/**
	 * @return string
	 */
	public function getName(): string
	{
		return $this->name;
	}

	/**
	 * @return bool
	 */
	public function isInstantiable(): bool
	{
		// TODO: Implement isInstantiable() method.
	}

	/**
	 * @return string
	 */
	public function __toString(): string
	{
		// TODO: Implement __toString() method.
	}

	public function getReflectionMethod(): ?ReflectionMethod
	{
		if (class_exists($this->name) && method_exists($this->name, $this->method)) {
			return ReflectionManager::reflectMethod($this->name, $this->method);
		}

		return null;
	}
}