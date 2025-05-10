<?php
declare(strict_types=1);

namespace SuperKernel\Di\Aop;

use Closure;
use Composer\Autoload\ClassLoader;
use Composer\InstalledVersions;
use Phar;
use SuperKernel\Contract\ComposerInterface;

/**
 * @ComposerFactory
 * @\SuperKernel\Di\Composer\ComposerFactory
 */
final class ComposerFactory
{
	private static ?ComposerFactory $instance = null;

	private ?ComposerInterface $composer = null {
		get => $this->composer ??= new class implements ComposerInterface {
			private ?ClassLoader $classLoader = null {
				get => $this->classLoader ??= (function () {
					$classLoaders = ClassLoader::getRegisteredLoaders();
					/* @var ClassLoader $classLoader */
					return reset($classLoaders);
				})();
			}

			private ?string $rootPath = null {
				get => $this->rootPath ??= dirname(
					Phar::running(false)
						?: Closure::bind(fn() => $this->vendorDir, $this->classLoader, $this->classLoader));
			}

			public function __construct()
			{
				$this->scan();
			}

			public function getRootPath(): string
			{
				return $this->rootPath;
			}

			private function scan(): void
			{
			}
		};
	}

	public function __invoke(): ComposerInterface
	{
		return (self::$instance ??= $this)->composer;
	}
}