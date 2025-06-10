<?php
declare(strict_types=1);

namespace SuperKernel\Di\Contract;

interface DefinerInterface
{
	public function __invoke(string $name);
}