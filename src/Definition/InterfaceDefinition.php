<?php
declare(strict_types=1);

namespace SuperKernel\Di\Definition;

use SuperKernel\Di\Contract\DefinitionInterface;
use function sprintf;

final readonly class InterfaceDefinition implements DefinitionInterface
{
	public function __construct(private string $interfaceName, private string $className)
	{
	}

	/**
	 * @return string
	 */
	public function getName(): string
	{
		return $this->className;
	}

	public function getInterfaceName(): string
	{
		return $this->interfaceName;
	}

	/**
	 * @return string
	 */
	public function __toString(): string
	{
		return sprintf('Interface[%s]', $this->getName());
	}
}