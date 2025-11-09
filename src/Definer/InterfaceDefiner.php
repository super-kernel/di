<?php
declare(strict_types=1);

namespace SuperKernel\Di\Definer;

use Psr\Container\ContainerInterface;
use SuperKernel\Di\Attribute\Definer;
use SuperKernel\Di\Attribute\Provider;
use SuperKernel\Di\Collector\Attribute;
use SuperKernel\Di\Contract\AttributeCollectorInterface;
use SuperKernel\Di\Contract\DefinerInterface;
use SuperKernel\Di\Contract\DefinitionInterface;
use SuperKernel\Di\Definition\InterfaceDefinition;

#[Definer]
final class InterfaceDefiner implements DefinerInterface
{
	private ?AttributeCollectorInterface $attributeCollector = null {
		get => $this->attributeCollector ??= $this->container->get(AttributeCollectorInterface::class);
	}

	/**
	 * @var array<Attribute>
	 */
	private array $containers = [];

	public function __construct(private readonly ContainerInterface $container)
	{
		foreach ($this->attributeCollector->getAttributes(Provider::class) as $attribute) {
			/* @var Provider $provider */
			$provider = $attribute->attribute;

			if (!isset($this->containers[$provider->class])) {
				$this->containers[$provider->class] = $attribute;
				continue;
			}

			$priority = $this->containers[$provider->class]->attribute->priority;

			if ($priority < $provider->priority) {
				continue;
			}

			$this->containers[$provider->class] = $attribute;

		}
	}

	public function support(string $id): bool
	{
		if (!interface_exists($id)) {
			return false;
		}

		return isset($this->containers[$id]);
	}

	/**
	 * @param string $id
	 *
	 * @return DefinitionInterface
	 */
	public function create(string $id): DefinitionInterface
	{
		$class = $this->containers[$id]->attribute->class;

		return new InterfaceDefinition($class);
	}
}