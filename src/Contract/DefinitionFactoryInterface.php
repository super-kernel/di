<?php
declare(strict_types=1);

namespace SuperKernel\Di\Contract;

use SplPriorityQueue;

interface DefinitionFactoryInterface
{
	public function getDefiners(): SplPriorityQueue;

	public function getDefinition(string $id): ?DefinitionInterface;

	public function hasDefinition(string $id): bool;
}