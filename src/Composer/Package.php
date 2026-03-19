<?php
declare(strict_types=1);

namespace SuperKernel\Di\Composer;

use SuperKernel\Di\Contract\PackageInterface;
use SuperKernel\Contract\PathResolverInterface;

final readonly class Package implements PackageInterface
{
	private array $data;

	public function __construct(private PathResolverInterface $pathResolver, mixed ...$data)
	{
		$this->data = $data;
	}

	public function getPathResolver(): PathResolverInterface
	{
		return $this->pathResolver;
	}

	public function getName(): string
	{
		return $this->data['name'];
	}

	public function getType(): string
	{
		return $this->data['type'] ?? 'project';
	}

	public function getReference(): ?string
	{
		return $this->data['dist']['reference'] ?? null;
	}

	public function getAutoload(): array
	{
		return $this->data['autoload'] ?? [];
	}

	public function getDevAutoload(): array
	{
		return $this->data['autoload-dev'] ?? [];
	}
}