<?php
declare(strict_types=1);

namespace SuperKernel\Di\Collector;

use SuperKernel\Contract\AttributeMetadataInterface;

final readonly class PackageAttributeMetadata
{
	/**
	 * @var array<AttributeMetadataInterface>
	 */
	private array $attributes;

	public function __construct(
		private string             $name,
		private ?string            $reference,
		AttributeMetadataInterface ...$attributes
	)
	{
		$this->attributes = $attributes;
	}

	public function getName(): string
	{
		return $this->name;
	}

	public function getReference(): ?string
	{
		return $this->reference;
	}

	/**
	 * @return array<AttributeMetadataInterface>
	 */
	public function getAttributes(): array
	{
		return $this->attributes;
	}
}