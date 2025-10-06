<?php
declare(strict_types=1);

namespace SuperKernel\Di\Contract;

use SuperKernel\Di\Factory\DefinerFactory;

interface DefinerFactoryInterface
{
	public function getDefinition(string $id): ?DefinitionInterface;

	public function setDefiner(DefinerInterface $definer, int $priority = 0): void;

	public function __invoke(): DefinerFactory;
}