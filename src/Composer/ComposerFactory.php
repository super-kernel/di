<?php
declare(strict_types=1);

namespace SuperKernel\Di\Composer;

use SuperKernel\Contract\ConfigProviderInterface;
use SuperKernel\Di\Collector\ReflectionManager;
use SuperKernel\Di\Dispatcher\Definition\DefinitionFactory;
use SuperKernel\Di\Interface\ComposerInterface;
use SuperKernel\Di\Interface\DefinitionFactoryInterface;

/**
 * @ComposerFactory
 * @\SuperKernel\Di\Composer\ComposerFactory
 *
 * @method static string getRootPath()
 * @method static string getVendorDir()
 * @method static array getMergedExtra(?string $key = null)
 */
final class ComposerFactory
{
	private static ?ComposerInterface $instance = null;

	private static ?array $providerConfigs = null;

	private function __construct()
	{
	}

	public function __invoke(): ComposerInterface
	{
		return self::$instance ??= new class extends Composer {
		};
	}

	public static function __callStatic(string $name, array $arguments): mixed
	{
		return new self()()->{$name}(...$arguments);
	}

	public static function loadExtraConfig(): array
	{
		return self::$providerConfigs ??= self::loadProviders(
			self::$instance->getMergedExtra('super-kernel')['config'] ?? [],
		);
	}

	private static function loadProviders(array $extraConfigs): array
	{
		$providerConfigs = [];

		foreach ($extraConfigs as $configProvider) {
			if (
				is_string($configProvider)
				&& class_exists($configProvider)
				&& ReflectionManager::reflectClass($configProvider)->implementsInterface(ConfigProviderInterface::class)
			) {
				$providerConfigs[] = new $configProvider()();
			}
		}

		return array_merge_recursive(...$providerConfigs);
	}

	public static function getDefinitionFactory(): DefinitionFactoryInterface
	{
		$definitionFactory = new DefinitionFactory(self::loadExtraConfig()['dependencies'] ?? []);

		return new Scanner($definitionFactory)();
	}
}