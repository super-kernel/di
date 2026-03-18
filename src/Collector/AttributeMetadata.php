<?php
declare(strict_types=1);

namespace SuperKernel\Di\Collector;

use LogicException;
use ReflectionAttribute;
use ReflectionClass;
use ReflectionClassConstant;
use ReflectionFunction;
use ReflectionMethod;
use ReflectionParameter;
use ReflectionProperty;
use Reflector;
use SuperKernel\Contract\AttributeMetadataInterface;
use SuperKernel\Di\Provider\ReflectionCollectorProvider;
use function property_exists;

final readonly class AttributeMetadata implements AttributeMetadataInterface
{
	private string $attribute;

	private ?string $class;

	private ?string $method;

	private ?string $function;

	private ?string $property;

	private ?string $classConstant;

	private ?int $parameterIndex;

	private int $target;

	private array $arguments;

	private object $instance;

	public function __construct(Reflector $reflector, ReflectionAttribute $reflectionAttribute)
	{
		$this->class = match (true) {
			$reflector instanceof ReflectionClass             => $reflector->getName(),
			$reflector instanceof ReflectionMethod,
				$reflector instanceof ReflectionProperty,
				$reflector instanceof ReflectionClassConstant => $reflector->getDeclaringClass()->getName(),
			default                                           => null,
		};
		$this->function = match (true) {
			$reflector instanceof ReflectionFunction  => $reflector->getName(),
			$reflector instanceof ReflectionParameter => $reflector->getDeclaringFunction()->getName(),
			default                                   => null,
		};
		$this->method = $reflector instanceof ReflectionMethod ? $reflector->getName() : null;
		$this->property = $reflector instanceof ReflectionProperty ? $reflector->getName() : null;
		$this->classConstant = $reflector instanceof ReflectionClassConstant ? $reflector->getName() : null;
		$this->parameterIndex = $reflector instanceof ReflectionParameter ? $reflector->getPosition() : null;
		$this->attribute = $reflectionAttribute->getName();
		$this->arguments = $reflectionAttribute->getArguments();
		$this->target = $reflectionAttribute->getTarget();
		$this->instance = $this->createGhostInstance();
	}

	private function createGhostInstance(): object
	{
		$attribute = $this->attribute;

		return new $attribute(...$this->arguments);
	}

	public function getAttribute(): string
	{
		return $this->attribute;
	}

	public function getInstance(): object
	{
		return $this->instance;
	}

	public function getTarget(): int
	{
		return $this->target;
	}

	public function compatible(int $type): bool
	{
		return ($this->target & $type) !== 0;
	}

	public function getReflector(): Reflector
	{
		return match (true) {
			null !== $this->class
			&& null === $this->method
			&& null === $this->property
			&& null === $this->classConstant
			                               => new ReflectionCollectorProvider()()->reflectClass($this->class),
			null !== $this->method         => new ReflectionCollectorProvider()()->reflectMethod($this->class, $this->method),
			null !== $this->property       => new ReflectionCollectorProvider()()->reflectProperty($this->class, $this->property),
			null !== $this->classConstant  => new ReflectionCollectorProvider()()->reflectClassConstant($this->class, $this->classConstant),
			null !== $this->parameterIndex => new ReflectionCollectorProvider()()->reflectFunction($this->function)->getParameters()[$this->parameterIndex],
			null !== $this->function       => new ReflectionCollectorProvider()()->reflectFunction($this->function),
			default                        => throw new LogicException('Unable to resolve reflector.'),
		};
	}

	public function getClass(): string
	{
		return $this->class ?? throw new LogicException('Class cannot be found.');
	}

	public function getMethod(): string
	{
		return $this->method ?? throw new LogicException('Method cannot be found.');
	}

	public function getFunction(): string
	{
		return $this->function ?? throw new LogicException('Function cannot be found.');
	}

	public function getProperty(): string
	{
		return $this->property ?? throw new LogicException('Property cannot be found.');
	}

	public function getClassConstant(): string
	{
		return $this->classConstant ?? throw new LogicException('Class constant cannot be found.');
	}

	public function getParameterIndex(): int
	{
		return $this->parameterIndex ?? throw new LogicException('Parameter index cannot be found.');
	}

	public function __serialize(): array
	{
		return [
			'attribute'      => $this->attribute,
			'class'          => $this->class,
			'method'         => $this->method,
			'function'       => $this->function,
			'property'       => $this->property,
			'classConstant'  => $this->classConstant,
			'parameterIndex' => $this->parameterIndex,
			'target'         => $this->target,
			'arguments'      => $this->arguments,
		];
	}

	public function __unserialize(array $data): void
	{
		foreach ($data as $key => $value) {
			if (property_exists($this, $key)) {
				$this->{$key} = $value;
			}
		}
		$this->instance = $this->createGhostInstance();
	}
}