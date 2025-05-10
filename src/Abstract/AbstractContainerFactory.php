<?php
declare(strict_types=1);

namespace SuperKernel\Di\Abstract;

use Psr\Container\ContainerInterface;
use SuperKernel\Contract\ComposerInterface;
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
abstract readonly class AbstractContainerFactory implements ContainerFactoryInterface
{
	private array $resolvers;

	public function __construct(protected ?ComposerInterface $composer = null, array $resolvers = [])
	{
		$this->resolvers = array_merge(
			[
				ObjectDefinition::class => ObjectResolver::class,
				                        FactoryDefinition::class => FactoryResolver::class,
				                                    ParameterDefinition::class => ParameterResolver::class,
			], $resolvers);
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