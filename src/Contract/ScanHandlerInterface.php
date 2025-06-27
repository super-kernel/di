<?php
declare(strict_types=1);

namespace SuperKernel\Di\Contract;

use SuperKernel\Di\Aop\Scanner\Scanned;

interface ScanHandlerInterface
{
	public function scan(): Scanned;
}