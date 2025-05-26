<?php
declare(strict_types=1);

namespace SuperKernel\Di\Aop\Scanner;

use SuperKernel\Contract\ProviderConfigInterface;
use SuperKernel\Di\Aop\Scanned;
use SuperKernel\Di\Collector\ReflectionManager;
use SuperKernel\Di\Contract\ScannerInterface;
use Swoole\Process;

/**
 * @SwooleProcessScanner
 * @\SuperKernel\Di\Composer\Scanner\SwooleProcessScanner
 */
final readonly class SwooleProcessScanner implements ScannerInterface
{
	public function __construct(private ProviderConfigInterface $providerConfig)
	{
	}

	/**
	 * @return Scanned
	 */
	public function scan(): Scanned
	{
		$classmap = $this->providerConfig->getClassLoader()->getClassmap();

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
			$this->providerConfig->getRootPackage(),
		);

		return new Scanned(true);
	}

	private function runtimePath(): string
	{
		return $this->providerConfig->getRootPath() . '/runtime/container/scan.cache';
	}
}