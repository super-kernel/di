<?php
declare(strict_types=1);

namespace SuperKernel\Di\Aop\Scanner;

use Closure;
use Composer\Autoload\ClassLoader;
use Phar;

final class ConfigProvider
{
	private ?ClassLoader $classLoader = null {
		get => $this->classLoader ??= (function () {
			$loaders = ClassLoader::getRegisteredLoaders();

			return reset($loaders);
		})();
	}

	private ?bool $pharEnable = null {
		get => $this->pharEnable ?? !!strlen(Phar::running(false));
	}

	public function getClassLoader(): ClassLoader
	{
		return $this->classLoader;
	}

	public function getRootPath(): string
	{
		return dirname(Closure::bind(fn() => $this->vendorDir, $this->classLoader, ClassLoader::class)());
	}

	public function pharEnable(): bool
	{
		return $this->pharEnable;
	}
}