<?php
declare(strict_types=1);

namespace SuperKernel\Di\Exception\Container;

use SuperKernel\Di\Exception\ContainerException;

final class ProviderResolutionException extends ContainerException
{
	private function __construct(string $message)
	{
		parent::__construct($message);
	}

	public static function noProvider(string $interface): self
	{
		return new self("No provider found for interface \"$interface\"");
	}
}