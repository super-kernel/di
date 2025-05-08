<?php
declare(strict_types=1);

namespace SuperKernel\Di\Interface;

use SuperKernel\Di\Aop\ScannerHandler\Scanned;

/**
 * @ScanHandlerInterface
 * @\SuperKernel\Di\Interface\ScanHandlerInterface
 */
interface ScanHandlerInterface
{
	public function scan(): Scanned;
}