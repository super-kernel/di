<?php
declare(strict_types=1);

namespace SuperKernel\Di\Scan;

use Composer\Autoload\ClassLoader;
use Composer\InstalledVersions;
use SuperKernel\Di\Collector\PackageCollector;
use SuperKernel\Di\Contract\AttributeCollectorInterface;
use SuperKernel\Di\Contract\ScanHandlerInterface;

final readonly class Scanner
{
	private PackageCollector $packageCollector;

	public function __construct(private ScanHandlerInterface $scanHandler, ClassLoader $classLoader)
	{
		$this->packageCollector = new PackageCollector($classLoader);
	}

	public function scan(): AttributeCollectorInterface
	{
		foreach (InstalledVersions::getInstalledPackages() as $packageName) {
			$installPath = InstalledVersions::getInstallPath($packageName);

			if ($installPath) {
				$this->packageCollector->collect($packageName);
			}
		}

		$canned = $this->scanHandler->scan();

		if ($canned->isScanned()) {
			return ($this->packageCollector)();
		}

		$this->packageCollector->scan();

		exit;
	}
}