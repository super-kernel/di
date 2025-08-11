<?php
declare(strict_types=1);

namespace Tests\App\Aop\Scanner\Driver;

use Tests\App\ScanHandlerAbstract;

final class NullScanHandler extends ScanHandlerAbstract
{
	public function scan(): void
	{
	}
}