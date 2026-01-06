<?php
declare(strict_types=1);

namespace SuperKernel\Di\Definer;

use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use SuperKernel\Attribute\Contract\AttributeCollectorInterface;
use SuperKernel\Di\Attribute\Definer;
use SuperKernel\Di\Attribute\Provider;
use SuperKernel\Di\Contract\DefinerInterface;
use SuperKernel\Di\Contract\DefinitionInterface;
use SuperKernel\Di\Definition\InterfaceDefinition;

//  TODO: This class needs to consider collector state transitions.
#[Definer]
final readonly class InterfaceDefiner implements DefinerInterface
{
	private AttributeCollectorInterface $attributeCollector;

	/**
	 * @param ContainerInterface $container
	 *
	 * @throws ContainerExceptionInterface
	 * @throws NotFoundExceptionInterface
	 */
	public function __construct(private ContainerInterface $container)
	{
		$this->attributeCollector = $this->container->get(AttributeCollectorInterface::class);
	}

	public function support(string $id): bool
	{
		if (!interface_exists($id)) {
			return false;
		}

		return !empty($this->attributeCollector->getAttributes(Provider::class));
	}

	/**
	 * @param string $id
	 *
	 * @return DefinitionInterface
	 */
	public function create(string $id): DefinitionInterface
	{
		$providerAttribute = null;

		foreach ($this->attributeCollector->getAttributes(Provider::class) as $attribute) {
			if (!$providerAttribute) {

				if ($providerAttribute->attributeInstance->class < $attribute->attributeInstance->priority) {
					$providerAttribute = $attribute;
					continue;
				}
			}

			if ($attribute->attributeInstance->class === $id) {
				$providerAttribute = $attribute;
			}
		}

		return new InterfaceDefinition($providerAttribute->class);
	}
}