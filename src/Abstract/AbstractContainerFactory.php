<?php
declare(strict_types=1);

namespace SuperKernel\Di\Abstract;

use Psr\Container\ContainerInterface;
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

	public function __construct(private readonly array $dependencies = [])
	{
	}

	public function getDefinitionFactory(): DefinitionFactoryInterface
	{
		return new class ($this->dependencies) extends AbstractDefinitionFactory {
		};
	}

	public function getResolverDispatcher(ContainerInterface $container): ResolverInterface
	{
		return new class ($container, $this->resolvers) extends AbstractResolverDispatcher implements ResolverInterface {
		};
	}
}