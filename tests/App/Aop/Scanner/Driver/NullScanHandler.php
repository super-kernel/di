<?php
declare(strict_types=1);

namespace Tests\App\Aop\Scanner\Driver;

use SuperKernel\Di\Abstract\ScanHandlerAbstract;

final class NullScanHandler extends ScanHandlerAbstract
{
	public function scan(): void
	{
	}
}