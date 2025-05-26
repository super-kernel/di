<?php
declare(strict_types=1);

namespace SuperKernel\Di\Contract;

use SuperKernel\Di\Aop\Scanned;

/**
 * @ScannerInterface
 * @\SuperKernel\Di\Contract\ScannerInterface
 */
interface ScannerInterface
{
	public function scan(): Scanned;
}