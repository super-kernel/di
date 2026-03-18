<?php
declare(strict_types=1);

namespace SuperKernel\Di\Definer;

use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use SuperKernel\Di\Attribute\Definer;
use SuperKernel\Di\Contract\DefinerInterface;
use SuperKernel\Di\Contract\DefinitionFactoryInterface;
use SuperKernel\Di\Contract\DefinitionInterface;
use SuperKernel\Di\Definition\FactoryDefinition;
use SuperKernel\Di\Exception\Container\FactoryResolutionException;
use Throwable;
use function method_exists;

#[Definer(200)]
final class FactoryDefiner implements DefinerInterface
{
	private DefinitionFactoryInterface $definitionFactory {
		/**
		 * @throws ContainerExceptionInterface
		 * @throws NotFoundExceptionInterface
		 */
		get {
			if (!isset($this->definitionFactory)) {
				$this->definitionFactory = $this->container->get(DefinitionFactoryInterface::class);
			}
			return $this->definitionFactory;
		}
	}

	public function __construct(private readonly ContainerInterface $container)
	{
	}

	public function support(string $id): bool
	{
		try {
			$this->create($id);
		}
		catch (Throwable) {
			return false;
		}

		return true;
	}

	public function create(string $id): DefinitionInterface
	{
		/* @var DefinerInterface $definer */
		foreach ($this->definitionFactory->getDefiners() as $definer) {
			if ($definer instanceof $this) {
				continue;
			}
			if ($definer->support($id)) {
				/* @var DefinitionInterface $definition */
				$definition = $definer->create($id);
				if (method_exists($definition->getClassName(), '__invoke')) {
					return new FactoryDefinition($id, $definition->getClassName());
				}
			}
		}

		throw FactoryResolutionException::noInstantiationDefiner($id);
	}
}