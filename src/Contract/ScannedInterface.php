<?php
declare(strict_types=1);

namespace SuperKernel\Di\Contract;

interface ScannedInterface
{
	public function isScanned(): bool;
}