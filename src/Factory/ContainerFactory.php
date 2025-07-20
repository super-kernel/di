<?php
declare(strict_types=1);

namespace SuperKernel\Di\Factory;

use Psr\Container\ContainerInterface as PsrContainerInterface;
use SuperKernel\Di\Annotation\Annotation;
use SuperKernel\Di\Annotation\Factory;
use SuperKernel\Di\Container;
use SuperKernel\Di\Contract\ContainerInterface;
use SuperKernel\Di\Contract\DefinerFactoryInterface;
use SuperKernel\Di\Contract\DefinitionFactoryInterface;
use SuperKernel\Di\Contract\ResolverFactoryInterface;
use SuperKernel\Di\Definer\FactoryDefiner;
use SuperKernel\Di\Definer\ObjectDefiner;
use SuperKernel\Di\Resolver\FactoryResolver;
use SuperKernel\Di\Resolver\ObjectResolver;
use SuperKernel\Di\Resolver\ParameterResolver;

#[
	Factory,
	Annotation(
		[
			Container::class,
			ContainerInterface::class,
			PsrContainerInterface::class,
		],
	),
]
abstract class ContainerFactory
{
	private ?DefinerFactoryInterface $definerFactory = null {
		get => $this->definerFactory ??= new DefinerFactory();
	}

	private ?ResolverFactoryInterface $resolverFactory = null {
		get => $this->resolverFactory ??= new ResolverFactory();
	}

	private ?DefinitionFactoryInterface $definitionFactory = null {
		get => $this->definitionFactory ??= new DefinitionFactory($this->definerFactory);
	}

	public function __construct()
	{
		$this->definerFactory->setDefiner(new FactoryDefiner());
		$this->definerFactory->setDefiner(new ObjectDefiner());

		$this->resolverFactory->setResolver(FactoryResolver::class);
		$this->resolverFactory->setResolver(ObjectResolver::class);
		$this->resolverFactory->setResolver(ParameterResolver::class);
	}

	/**
	 * @return ContainerInterface
	 */
	public function __invoke(): ContainerInterface
	{
		return new Container($this->definitionFactory, $this->resolverFactory);
	}
}