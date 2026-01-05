<?php
declare(strict_types=1);

namespace SuperKernel\Di\Scan\Handler;

use SuperKernel\Di\Contract\ScanHandlerInterface;
use SuperKernel\Di\Contract\ScannedInterface;
use SuperKernel\Di\Scan\Scanned;

final class NullScanHandler implements ScanHandlerInterface
{
	public function scan(): ScannedInterface
	{
		return new Scanned(true);
	}
}