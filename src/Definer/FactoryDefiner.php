<?php
declare(strict_types=1);

namespace SuperKernel\Di\Definer;

use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use SuperKernel\Annotation\Factory;
use SuperKernel\Annotation\Provider;
use SuperKernel\Attribute\Attribute;
use SuperKernel\Attribute\Contract\AttributeCollectorInterface;
use SuperKernel\Contract\ReflectorInterface;
use SuperKernel\Di\Attribute\Definer;
use SuperKernel\Di\Collector\ProviderCollector;
use SuperKernel\Di\Contract\DefinerInterface;
use SuperKernel\Di\Contract\DefinitionInterface;
use SuperKernel\Di\Definition\FactoryDefinition;
use Throwable;
use function method_exists;

#[Definer(2)]
final class FactoryDefiner implements DefinerInterface
{
	private ReflectorInterface $reflector;

	private ProviderCollector $providerCollector;

	private AttributeCollectorInterface $attributeCollector;

	/**
	 * @param ContainerInterface $container
	 *
	 * @throws ContainerExceptionInterface
	 * @throws NotFoundExceptionInterface
	 */
	public function __construct(ContainerInterface $container)
	{
		$this->reflector = $container->get(ReflectorInterface::class);
		$this->providerCollector = $container->get(ProviderCollector::class);
		$this->attributeCollector = $container->get(AttributeCollectorInterface::class);
	}

	public function support(string $id): bool
	{
		if (!$this->providerCollector->has($id)) {
			return false;
		}

		$class = $this->providerCollector->get($id);

		return array_any(
			$this->attributeCollector->getClassAttributes($class),
			fn(Attribute $attribute) => $attribute->getInstance() instanceof Factory::class,
		);
	}

	/**
	 * @param string $id
	 *
	 * @return DefinitionInterface
	 */
	public function create(string $id): DefinitionInterface
	{
		$class = $this->providerCollector->get($id);

		if (!method_exists($class, '__invoke')) {

		}

		return new FactoryDefinition($id);
	}
}