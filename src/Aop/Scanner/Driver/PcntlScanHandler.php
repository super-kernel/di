<?php
declare(strict_types=1);

namespace SuperKernel\Di\Aop\Scanner\Driver;

use SuperKernel\Di\Aop\Scanner\AbstractScanHandler;
use SuperKernel\Di\Aop\Scanner\Scanned;
use SuperKernel\Di\Contract\ScannerInterface;

final class PcntlScanHandler extends AbstractScanHandler implements ScannerInterface
{
	public function scan(): Scanned
	{
	}
}