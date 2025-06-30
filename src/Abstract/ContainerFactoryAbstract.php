<?php
declare(strict_types=1);

namespace SuperKernel\Di\Abstract;

use Psr\Container\{
	ContainerExceptionInterface, ContainerInterface, NotFoundExceptionInterface
};
use SuperKernel\Di\{
	Container,
	Contract\ConfigProviderInterface,
	Contract\ScannerInterface,
	Definition\FactoryDefinition,
	Definition\ObjectDefinition,
	Definition\ParameterDefinition,
	Contract\ContainerFactoryInterface,
	Contract\DefinitionFactoryInterface,
	Contract\ResolverInterface,
	Exception\NotFoundException,
	Resolver\FactoryResolver,
	Resolver\ObjectResolver,
	Resolver\ParameterResolver,
};

/**
 * @ContainerFactoryAbstract
 * @\SuperKernel\Di\Abstract\ContainerFactoryAbstract
 */
abstract class ContainerFactoryAbstract implements ContainerFactoryInterface
{
	private array $resolvers = [
		ObjectDefinition::class    => ObjectResolver::class,
		FactoryDefinition::class   => FactoryResolver::class,
		ParameterDefinition::class => ParameterResolver::class,
	];

	/**
	 * @param ConfigProviderInterface|null $configProvider
	 * @param array<string,string>         $resolvers
	 */
	public function __construct(protected ?ConfigProviderInterface $configProvider = null, array $resolvers = [])
	{
		$this->resolvers = $this->resolvers + $resolvers;
	}

	public function getDefinitionFactory(): DefinitionFactoryInterface
	{
		return new class ($this->getDependencies()) extends DefinitionFactoryAbstract {
		};
	}

	public function getResolverDispatcher(ContainerInterface $container): ResolverInterface
	{
		return new class ($container, $this->resolvers) extends ResolverDispatcherAbstract implements ResolverInterface {
		};
	}

	/**
	 * @return ContainerInterface
	 * @throws ContainerExceptionInterface
	 * @throws NotFoundExceptionInterface
	 * @throws NotFoundException
	 */
	final public function __invoke(): ContainerInterface
	{
		$container = new Container($this);

		if (is_null($this->configProvider)) {
			$container = new static($container->get(ConfigProviderInterface::class))();
			//TODO: Waiting for the scanner to intervene and complete the preliminary operation.
			$container->get(ScannerInterface::class)->scan();
		}

		return $container;
	}

	/**
	 * If developers take over container parsing, they need to provide a fallback dependency parsing.
	 *
	 * @return array
	 */
	abstract protected function getDependencies(): array;
}