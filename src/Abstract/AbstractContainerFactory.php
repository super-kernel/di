<?php
declare(strict_types=1);

namespace SuperKernel\Di\Abstract;

use Psr\Container\ContainerInterface;
use SuperKernel\Contract\ComposerInterface;
use SuperKernel\Di\ConfigProvider;
use SuperKernel\Di\Definition\FactoryDefinition;
use SuperKernel\Di\Definition\ObjectDefinition;
use SuperKernel\Di\Definition\ParameterDefinition;
use SuperKernel\Di\Interface\ContainerFactoryInterface;
use SuperKernel\Di\Interface\DefinitionFactoryInterface;
use SuperKernel\Di\Interface\ResolverInterface;
use SuperKernel\Di\Resolver\FactoryResolver;
use SuperKernel\Di\Resolver\ObjectResolver;
use SuperKernel\Di\Resolver\ParameterResolver;

/**
 * @AbstractContainerFactory
 * @\SuperKernel\Di\Abstract\AbstractContainerFactory
 */
abstract class AbstractContainerFactory implements ContainerFactoryInterface
{
	private array $resolvers = [
		ObjectDefinition::class    => ObjectResolver::class,
		FactoryDefinition::class   => FactoryResolver::class,
		ParameterDefinition::class => ParameterResolver::class,
	];

	public function __construct(protected readonly ?ComposerInterface $composer = null)
	{
	}

	public function getDefinitionFactory(): DefinitionFactoryInterface
	{
		return new class ($this->getDependencies()) extends AbstractDefinitionFactory {
		};
	}

	public function getResolverDispatcher(ContainerInterface $container): ResolverInterface
	{
		return new class ($container, $this->resolvers) extends AbstractResolverDispatcher implements ResolverInterface {
		};
	}

	/**
	 * If developers take over container parsing, they need to provide a fallback dependency parsing.
	 *
	 * @return array
	 */
	abstract protected function getDependencies(): array;
}