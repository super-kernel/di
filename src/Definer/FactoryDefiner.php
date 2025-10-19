<?php
declare(strict_types=1);

namespace SuperKernel\Di\Definer;

use Psr\Container\ContainerInterface;
use ReflectionAttribute;
use SuperKernel\Attribute\Factory;
use SuperKernel\Contract\AttributeCollectorInterface;
use SuperKernel\Contract\ReflectionCollectorInterface;
use SuperKernel\Di\Annotation\Definer;
use SuperKernel\Di\Contract\DefinerInterface;
use SuperKernel\Di\Contract\DefinitionInterface;
use SuperKernel\Di\Definition\FactoryDefinition;
use SuperKernel\Di\Exception\InvalidDefinitionException;

#[Definer(2)]
final class FactoryDefiner implements DefinerInterface
{
	private ?AttributeCollectorInterface $attributeCollector = null {
		get => $this->attributeCollector ??= $this->container->get(AttributeCollectorInterface::class);
	}

	private ?ReflectionCollectorInterface $reflectionCollector = null {
		get => $this->reflectionCollector ??= $this->container->get(ReflectionCollectorInterface::class);
	}

	private ?ObjectDefiner $objectDefiner = null {
		get => $this->objectDefiner ??= $this->container->get(ObjectDefiner::class);
	}

	public function __construct(private readonly ContainerInterface $container)
	{
	}

	public function support(string $id): bool
	{
		$classname = $this->attributeCollector->getRealEntry($id);

		if (class_exists($classname) || interface_exists($classname)) {
			return array_any($this->reflectionCollector->getAttributesByClass($classname),
				fn(ReflectionAttribute $attribute) => $attribute->getName() === Factory::class);
		}

		return false;
	}

	/**
	 * @param string $id
	 *
	 * @return DefinitionInterface
	 * @throws InvalidDefinitionException
	 */
	public function create(string $id): DefinitionInterface
	{
		$classname = $this->attributeCollector->getRealEntry($id);

		if (method_exists($classname, '__invoke')) {
			return new FactoryDefinition($classname);
		}

		throw new InvalidDefinitionException("The magic method $classname::__invoke() does not exist.");
	}
}