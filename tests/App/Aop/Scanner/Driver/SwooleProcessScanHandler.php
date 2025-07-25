<?php
declare(strict_types=1);

namespace Tests\App\Aop\Scanner\Driver;

use SuperKernel\Di\Abstract\ScanHandlerAbstract;
use Swoole\Process;

final class SwooleProcessScanHandler extends ScanHandlerAbstract
{

	public function scan(): void
	{
		$process = new Process(function () {
			$this->scanner->process();

			exit(0);
		});

		$process->start();

		$status = $process->wait();

		var_dump($status);
	}
}