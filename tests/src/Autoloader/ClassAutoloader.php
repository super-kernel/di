<?php

declare(strict_types=1);

namespace SuperKernelTest\Di\Autoloader;

use RuntimeException;
use SuperKernel\Contract\ClassAutoloaderInterface;
use function array_merge;
use function dirname;
use function spl_autoload_register;
use function spl_autoload_unregister;

/**
 * High-performance Static Class Autoloader for SuperKernel.
 *
 * This loader provides a mandatory, high-speed lookup mechanism using a pre-defined class map.
 * It is designed for production environments to bypass expensive PSR-4 filesystem checks
 * by providing O(1) resolution for core framework components.
 *
 * @api
 */
final class ClassAutoloader implements ClassAutoloaderInterface
{
	/**
	 * @var array<string, string> $classMap Associative array where key is FQCN and value is absolute path.
	 */
	private array $classMap;

	/**
	 * Initializes the autoloader with the core SuperKernel class map.
	 */
	public function __construct()
	{
		$vendorDir = dirname(__DIR__, 3);
		$this->classMap = [
			'SuperKernel\\Contract\\ApplicationInterface'                        => $vendorDir . '/super-kernel/contract/src/ApplicationInterface.php',
			'SuperKernel\\Contract\\AttributeMetadataCollectorInterface'         => $vendorDir . '/super-kernel/contract/src/AttributeMetadataCollectorInterface.php',
			'SuperKernel\\Contract\\AttributeMetadataInterface'                  => $vendorDir . '/super-kernel/contract/src/AttributeMetadataInterface.php',
			'SuperKernel\\Contract\\ContainerInterface'                          => $vendorDir . '/super-kernel/contract/src/ContainerInterface.php',
			'SuperKernel\\Contract\\PackageMetadataCollectorInterface'           => $vendorDir . '/super-kernel/contract/src/PackageMetadataCollectorInterface.php',
			'SuperKernel\\Contract\\PackageMetadataInterface'                    => $vendorDir . '/super-kernel/contract/src/PackageMetadataInterface.php',
			'SuperKernel\\Contract\\PathResolverInterface'                       => $vendorDir . '/super-kernel/contract/src/PathResolverInterface.php',
			'SuperKernel\\Contract\\ProcessHandlerInterface'                     => $vendorDir . '/super-kernel/contract/src/ProcessHandlerInterface.php',
			'SuperKernel\\Contract\\ReflectionCollectorInterface'                => $vendorDir . '/super-kernel/contract/src/ReflectionCollectorInterface.php',
			'SuperKernel\\Contract\\StdoutLoggerInterface'                       => $vendorDir . '/super-kernel/contract/src/StdoutLoggerInterface.php',
			'SuperKernel\\Di\\Attribute\\Definer'                                => $vendorDir . '/super-kernel/di/src/Attribute/Definer.php',
			'SuperKernel\\Di\\Attribute\\Resolver'                               => $vendorDir . '/super-kernel/di/src/Attribute/Resolver.php',
			'SuperKernel\\Di\\Autoloader\\ClassAutoloader'                       => $vendorDir . '/super-kernel/di/src/Autoloader/ClassAutoloader.php',
			'SuperKernel\\Di\\Autoloader\\ClassMapper'                           => $vendorDir . '/super-kernel/di/src/Autoloader/ClassMapper.php',
			'SuperKernel\\Di\\Collector\\AttributeMetadataCollector'             => $vendorDir . '/super-kernel/di/src/Collector/AttributeMetadataCollector.php',
			'SuperKernel\\Di\\Collector\\ProviderCollector'                      => $vendorDir . '/super-kernel/di/src/Collector/ProviderCollector.php',
			'SuperKernel\\Di\\Collector\\ReflectionCollector'                    => $vendorDir . '/super-kernel/di/src/Collector/ReflectionCollector.php',
			'SuperKernel\\Di\\Container'                                         => $vendorDir . '/super-kernel/di/src/Container.php',
			'SuperKernel\\Di\\Contract\\DefinerInterface'                        => $vendorDir . '/super-kernel/di/src/Contract/DefinerInterface.php',
			'SuperKernel\\Di\\Contract\\DefinitionFactoryInterface'              => $vendorDir . '/super-kernel/di/src/Contract/DefinitionFactoryInterface.php',
			'SuperKernel\\Di\\Contract\\DefinitionInterface'                     => $vendorDir . '/super-kernel/di/src/Contract/DefinitionInterface.php',
			'SuperKernel\\Di\\Contract\\PathResolveAdapterInterface'             => $vendorDir . '/super-kernel/di/src/Contract/PathResolveAdapterInterface.php',
			'SuperKernel\\Di\\Contract\\ResolverFactoryInterface'                => $vendorDir . '/super-kernel/di/src/Contract/ResolverFactoryInterface.php',
			'SuperKernel\\Di\\Contract\\ResolverInterface'                       => $vendorDir . '/super-kernel/di/src/Contract/ResolverInterface.php',
			'SuperKernel\\Di\\Definer\\FactoryDefiner'                           => $vendorDir . '/super-kernel/di/src/Definer/FactoryDefiner.php',
			'SuperKernel\\Di\\Definer\\ObjectDefiner'                            => $vendorDir . '/super-kernel/di/src/Definer/ObjectDefiner.php',
			'SuperKernel\\Di\\Definer\\ProviderDefiner'                          => $vendorDir . '/super-kernel/di/src/Definer/ProviderDefiner.php',
			'SuperKernel\\Di\\Definition\\FactoryDefinition'                     => $vendorDir . '/super-kernel/di/src/Definition/FactoryDefinition.php',
			'SuperKernel\\Di\\Definition\\MethodDefinition'                      => $vendorDir . '/super-kernel/di/src/Definition/MethodDefinition.php',
			'SuperKernel\\Di\\Definition\\ObjectDefinition'                      => $vendorDir . '/super-kernel/di/src/Definition/ObjectDefinition.php',
			'SuperKernel\\Di\\Definition\\PropertyDefinition'                    => $vendorDir . '/super-kernel/di/src/Definition/PropertyDefinition.php',
			'SuperKernel\\Di\\Definition\\ProviderDefinition'                    => $vendorDir . '/super-kernel/di/src/Definition/ProviderDefinition.php',
			'SuperKernel\\Di\\Exception\\ContainerException'                     => $vendorDir . '/super-kernel/di/src/Exception/ContainerException.php',
			'SuperKernel\\Di\\Exception\\Container\\FactoryResolutionException'  => $vendorDir . '/super-kernel/di/src/Exception/Container/FactoryResolutionException.php',
			'SuperKernel\\Di\\Exception\\Container\\ProviderResolutionException' => $vendorDir . '/super-kernel/di/src/Exception/Container/ProviderResolutionException.php',
			'SuperKernel\\Di\\Exception\\Container\\ResolverException'           => $vendorDir . '/super-kernel/di/src/Exception/Container/ResolverException.php',
			'SuperKernel\\Di\\Exception\\NotFoundException'                      => $vendorDir . '/super-kernel/di/src/Exception/NotFoundException.php',
			'SuperKernel\\Di\\Factory\\DefinitionFactory'                        => $vendorDir . '/super-kernel/di/src/Factory/DefinitionFactory.php',
			'SuperKernel\\Di\\Factory\\ResolverFactory'                          => $vendorDir . '/super-kernel/di/src/Factory/ResolverFactory.php',
			'SuperKernel\\Di\\PathResolver\\Adapter\\ComposerAdapter'            => $vendorDir . '/super-kernel/di/src/PathResolver/Adapter/ComposerAdapter.php',
			'SuperKernel\\Di\\PathResolver\\Adapter\\PharAdapter'                => $vendorDir . '/super-kernel/di/src/PathResolver/Adapter/PharAdapter.php',
			'SuperKernel\\Di\\PathResolver\\Adapter\\StandardAdapter'            => $vendorDir . '/super-kernel/di/src/PathResolver/Adapter/StandardAdapter.php',
			'SuperKernel\\Di\\PathResolver\\PathResolver'                        => $vendorDir . '/super-kernel/di/src/PathResolver/PathResolver.php',
			'SuperKernel\\Di\\Provider\\AttributeMetadataCollectorProvider'      => $vendorDir . '/super-kernel/di/src/Provider/AttributeMetadataCollectorProvider.php',
			'SuperKernel\\Di\\Provider\\ContainerProvider'                       => $vendorDir . '/super-kernel/di/src/Provider/ContainerProvider.php',
			'SuperKernel\\Di\\Provider\\PathResolverProvider'                    => $vendorDir . '/super-kernel/di/src/Provider/PathResolverProvider.php',
			'SuperKernel\\Di\\Provider\\ReflectionCollectorProvider' => $vendorDir . '/super-kernel/di/src/Provider/ReflectionCollectorProvider.php',
			'SuperKernel\\Di\\Resolver\\FactoryResolver'                         => $vendorDir . '/super-kernel/di/src/Resolver/FactoryResolver.php',
			'SuperKernel\\Di\\Resolver\\MethodResolver'                          => $vendorDir . '/super-kernel/di/src/Resolver/MethodResolver.php',
			'SuperKernel\\Di\\Resolver\\ObjectResolver'                          => $vendorDir . '/super-kernel/di/src/Resolver/ObjectResolver.php',
			'SuperKernel\\Di\\Resolver\\PropertyResolver'                        => $vendorDir . '/super-kernel/di/src/Resolver/PropertyResolver.php',
			'SuperKernel\\Di\\Resolver\\ProviderResolver'                        => $vendorDir . '/super-kernel/di/src/Resolver/ProviderResolver.php',
		];
	}

	public function addClassMap(array $classMap): void
	{
		$this->classMap = array_merge($this->classMap, $classMap);
	}

	public function register(): void
	{
		if (!spl_autoload_register([$this, '__autoload'], true, true)) {
			throw new RuntimeException('Failed to register ClassAutoloader to the top of the SPL stack.');
		}
	}

	public function unregister(): void
	{
		spl_autoload_unregister([$this, '__autoload']);
	}

	/**
	 * Resolves the class name to its corresponding file path using the internal map.
	 *
	 * This method provides the primary resolution logic for the SPL autoloader mechanism.
	 * It ensures an O(1) lookup and avoids redundant filesystem I/O.
	 *
	 * @param string $class The fully qualified class name.
	 *
	 * @return void
	 * @internal This method is for SPL callback use only.
	 */
	public function __autoload(string $class): void
	{
		if (isset($this->classMap[$class])) {
			include $this->classMap[$class];
		}
	}
}