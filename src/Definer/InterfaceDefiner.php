<?php
declare(strict_types=1);

namespace SuperKernel\Di\Definer;

use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use SuperKernel\Annotation\Provider;
use SuperKernel\Attribute\Contract\AttributeCollectorInterface;
use SuperKernel\Di\Attribute\Definer;
use SuperKernel\Di\Contract\DefinerInterface;
use SuperKernel\Di\Contract\DefinitionInterface;
use SuperKernel\Di\Definition\InterfaceDefinition;
use SuperKernel\Di\Exception\Container\InterfaceResolutionException;
use function interface_exists;

#[Definer]
final class InterfaceDefiner implements DefinerInterface
{
	private AttributeCollectorInterface $attributeCollector {
		/**
		 * @throws ContainerExceptionInterface
		 * @throws NotFoundExceptionInterface
		 */
		get {
			if (!isset($this->attributeCollector)) {
				$this->attributeCollector = $this->container->get(AttributeCollectorInterface::class);
			}
			return $this->attributeCollector;
		}
	}

	public function __construct(private readonly ContainerInterface $container)
	{
	}

	public function support(string $id): bool
	{
		if (!interface_exists($id)) {
			return false;
		}

		try {
			$this->create($id);
		}
		catch (InterfaceResolutionException) {
			return false;
		}

		return true;
	}

	/**
	 * @param string $id
	 *
	 * @return DefinitionInterface
	 */
	public function create(string $id): DefinitionInterface
	{
		$provider = null;

		foreach ($this->attributeCollector->getAttributes(Provider::class) as $attribute) {
			if (!($id === $attribute->getInstance()->class)) {
				continue;
			}

			if (null === $provider ||
			    $attribute->getInstance()->priority >= $provider->getInstance()->priority
			) {
				$provider = $attribute;
			}
		}

		if (null === $provider) {
			throw InterfaceResolutionException::noProvider($id);
		}

		return new InterfaceDefinition($id, $provider->getClass());
	}
}