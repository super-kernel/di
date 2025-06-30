<?php
declare(strict_types=1);

namespace SuperKernel\Di\Aop\Scanner\Driver;

use SuperKernel\Di\Abstract\ScanHandlerAbstract;

final class NullScanHandler extends ScanHandlerAbstract
{
	public function scan(): void
	{
	}
}