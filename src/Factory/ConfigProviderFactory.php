<?php
declare(strict_types=1);

namespace SuperKernel\Di\Factory;

use Closure;
use Composer\Autoload\ClassLoader;
use Composer\InstalledVersions;
use Exception;
use RuntimeException;
use SuperKernel\Di\Contract\ConfigProviderInterface;

/**
 * @ConfigProviderFactory
 * @\SuperKernel\Di\Factory\ConfigProviderFactory
 */
final class ConfigProviderFactory
{
	private static ?self $instance = null;

	private ?ConfigProviderInterface $configProvider = null {
		get => $this->configProvider ??= new class implements ConfigProviderInterface {
			private ?string $rootPath = null {
				get => $this->rootPath ??= dirname(Closure::bind(fn() => $this->vendorDir, $this->classLoader, ClassLoader::class)());
			}

			private ?array $rootPackage = null {
				get => $this->rootPackage ??= InstalledVersions::getRootPackage();
			}

			private ?array $allPackages = null {
				get => $this->allPackages ??= (function () {
					$versions = InstalledVersions::getAllRawData()[0]['versions'] ?? null;

					if (!$versions) {
						throw new RuntimeException('All raw data are not installed');
					}

					return $this->allPackages = $versions;
				})();
			}

			private ?array $providerConfigs = null;

			private readonly ClassLoader $classLoader;

			public function __construct()
			{
				$loaders = ClassLoader::getRegisteredLoaders();

				$this->classLoader = reset($loaders);
			}

			public function getClassLoader(): ClassLoader
			{
				return $this->classLoader;
			}

			public function getRootPath(): string
			{
				return $this->rootPath;
			}

			public function getRootPackage(): array
			{
				return $this->rootPackage;
			}

			public function getAllPackages(): array
			{
				return $this->allPackages;
			}

			public function getAllProviders(): array
			{
				if (null !== $this->providerConfigs) {
					return $this->providerConfigs;
				}

				$providerConfigs = [];

				$packages = array_merge($this->allPackages, $this->rootPackage);

				foreach ($packages as $packageName => $package) {
					$configProvider = $package['extra']['super-kernel']['config'] ?? null;

					if (null === $configProvider) {
						continue;
					}

					if (!is_string($configProvider) ||
					    !class_exists($configProvider) ||
					    !is_a($configProvider, ConfigProviderInterface::class, true)
					) {
						throw new RuntimeException(
							sprintf(
								'The configProvider for package [%s] is invalid, `extra.config` must be an ' .
								'existing classname string that inherits from `ConfigProviderInterface`.',
								$packageName,
							),
						);
					}

					$providerConfigs[] = new $configProvider()();
				}

				return $this->providerConfigs = array_merge_recursive(...$providerConfigs);
			}
		};
	}

	/**
	 * @throws Exception
	 */
	public function __construct()
	{
		if (!InstalledVersions::isInstalled('super-kernel/composer-plugin')) {
			throw new Exception(<<<ERROR
				[ERROR] Missing required dependency: super-kernel/composer-plugin

				Please install it using Composer:
                    composer require super-kernel/composer-plugin --dev

				This plugin is essential for the framework to work properly.
ERROR,
			);
		}
	}

	public function __invoke(): ConfigProviderInterface
	{
		return (self::$instance ??= $this)->configProvider;
	}
}