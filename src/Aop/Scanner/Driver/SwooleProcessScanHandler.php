<?php
declare(strict_types=1);

namespace SuperKernel\Di\Aop\Scanner\Driver;

use SuperKernel\Di\Aop\Scanner\AbstractScanHandler;
use SuperKernel\Di\Aop\Scanner\ConfigProvider;
use SuperKernel\Di\Aop\Scanner\Scanned;
use SuperKernel\Di\Collector\ReflectionManager;
use SuperKernel\Di\Contract\ScannerInterface;
use Swoole\Process;

final class SwooleProcessScanHandler extends AbstractScanHandler implements ScannerInterface
{
	public function __construct(private ConfigProvider $configProvider)
	{
	}

	/**
	 * @return Scanned
	 */
	public function scan(): Scanned
	{
		$classmap = $this->configProvider->getClassLoader()->getClassmap();

		$process = new Process(function () use ($classmap) {
			foreach ($classmap as $classname => $path) {
				if (class_exists($classname)) {

					$classImplements = class_implements($classname) ?: [];

					var_dump($classImplements);
					foreach ($classImplements as $classImplement) {
						if (!interface_exists($classImplement)) {
							break 2;
						}
					}

					new ReflectionManager()->reflectClass($classname);
				}
			}
		});

		$process->start();

		$status = $process->wait();

		var_dump(
			$this->configProvider->getRootPackage(),
		);

		return new Scanned(true);
	}

	private function runtimePath(): string
	{
		return $this->configProvider->getRootPath() . '/runtime/container/scan.cache';
	}
}