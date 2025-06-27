<?php
declare(strict_types=1);

namespace SuperKernel\Di\Aop\Scanner\Driver;

use SuperKernel\Di\Aop\Scanner\AbstractScanHandler;
use SuperKernel\Di\Aop\Scanner\Scanned;
use SuperKernel\Di\Contract\ScannerInterface;

final class NullScanHandler extends AbstractScanHandler implements ScannerInterface
{
	/**
	 * @return Scanned
	 */
	public function scan(): Scanned
	{
		// TODO: Implement scan() method.
	}
}