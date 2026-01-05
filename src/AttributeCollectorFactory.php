<?php
declare(strict_types=1);

namespace SuperKernel\Di;

use Composer\Autoload\ClassLoader;
use RuntimeException;
use SuperKernel\Di\Contract\AttributeCollectorInterface;
use SuperKernel\Di\Scan\ScanHandlerFactory;
use SuperKernel\Di\Scan\Scanner;
use function spl_autoload_functions;

final class AttributeCollectorFactory
{
	public function __invoke(): AttributeCollectorInterface
	{
		$classLoader = null;
		foreach (spl_autoload_functions() as $loader) {
			if ($loader[0] instanceof ClassLoader) {
				$classLoader = $loader[0];
				break;
			}
		}

		if (null === $classLoader) {
			throw new RuntimeException('Composer loader not found.');
		}

		return new Scanner(new ScanHandlerFactory()(), $classLoader)->scan();
	}
}