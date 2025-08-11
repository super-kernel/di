<?php
declare(strict_types=1);

namespace Tests\App\Aop\Scanner\Driver;

use SuperKernel\Di\Exception\Exception;
use Tests\App\Aop\Scanner\Scanned;
use Tests\App\ScanHandlerAbstract;
use function pcntl_fork;
use function pcntl_wait;

final class PcntlScanHandler extends ScanHandlerAbstract
{
	/**
	 * @return void
	 * @throws Exception
	 */
	public function scan(): void
	{
		$scanned = $this->process();

		if ($scanned->isScanned()) {
			return;
		}

		$this->scanner->process();

		exit(0);
	}

	/**
	 * @return Scanned|void
	 * @throws Exception
	 */
	private function process()
	{
		$pid = pcntl_fork();

		if ($pid == -1) {
			throw new Exception('The process fork failed');
		}
		if ($pid) {
			pcntl_wait($status);
			if ($status !== 0) {
				exit(-1);
			}

			return new Scanned(true);
		}

		return new Scanned(false);
	}
}