<?php
declare(strict_types=1);

namespace SuperKernel\Di\Aop\ScannerHandler;

use SuperKernel\Di\Interface\ScanHandlerInterface;

/**
 * @SwooleProcessScanner
 * @\SuperKernel\Di\Composer\Scanner\SwooleProcessScanner
 */
final class SwooleProcessScanHandler implements ScanHandlerInterface
{
	public function __construct()
	{
	}

	/**
	 * @return Scanned
	 */
	public function scan(): Scanned
	{
		return new Scanned(true);
	}
}