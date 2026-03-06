<?php
declare(strict_types=1);

namespace SuperKernel\Di\Exception\Container;

use SuperKernel\Di\Contract\DefinitionInterface;
use SuperKernel\Di\Exception\ContainerException;
use Throwable;
use function sprintf;

final class ResolverException extends ContainerException
{
	private function __construct(string $message, ?Throwable $previous = null)
	{
		parent::__construct($message, 0, $previous);
	}

	public static function unsupportedDefinition(DefinitionInterface $definition): self
	{
		return new self(
			sprintf(
				'Entry "%s" cannot be resolved by this resolver: expected %s, got %s.',
				$definition->getName(),
				$definition,
				$definition::class,
			),
		);
	}

	public static function propertyNotResolvable(string $class, string $property): self
	{
		return new self(
			sprintf(
				'Failed to inject property %s::$%s: none of the type can be resolved from the container and no default value is provided.',
				$class,
				$property,
			),
		);
	}

	public static function parameterNotResolvable(DefinitionInterface $definition, string $parameter): self
	{
		return new self(
			sprintf(
				'Failed to resolve parameter %s::$%s: none of the type can be resolved from the container and no default value is provided.',
				$definition->getName(),
				$parameter,
			),
		);
	}

	public static function lazyInitializationNotSupported(string $className, Throwable $throwable): self
	{
		return new self("Failed to lazy initialize class \"$className\"", $throwable);
	}

	public static function parameterResolutionFailed($class, string $method, Throwable $throwable): self
	{
		return new self(
			sprintf(
				'Parsing the parameters of the method %s:%s failed due to the following reason: %s',
				$class,
				$method,
				$throwable->getMessage(),
			),
		);
	}
}