<?php
declare(strict_types=1);

namespace SuperKernel\Di\Interface\Visitor;

use SuperKernel\Di\Interface\DefinitionInterface;

/**
 * @DefinitionVisitorInterface
 * @\SuperKernel\Di\Interface\Visitor\DefinitionVisitorInterface
 */
interface DefinitionVisitorInterface
{
	public function getDefinitionName(): string;

	public function __invoke(string $id): ?DefinitionInterface;
}