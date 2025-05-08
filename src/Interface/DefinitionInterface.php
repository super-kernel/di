<?php

declare(strict_types=1);

namespace SuperKernel\Di\Interface;

/**
 * @DefinitionInterface
 * @\SuperKernel\Di\Interface\DefinitionInterface
 */
interface DefinitionInterface
{
	public function getName(): string;

	public function isInstantiable(): bool;

	public function __toString(): string;
}