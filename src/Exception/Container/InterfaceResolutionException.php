<?php
declare(strict_types=1);

namespace SuperKernel\Di\Exception\Container;

use SuperKernel\Di\Exception\ContainerException;
use Throwable;

final class InterfaceResolutionException extends ContainerException
{
	private function __construct(string $message, ?Throwable $previous = null)
	{
		parent::__construct($message, 0, $previous);
	}

	public static function noProvider(string $interface): self
	{
		return new self("No provider found for interface \"$interface\"");
	}
}