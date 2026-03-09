<?php
declare(strict_types=1);

namespace SuperKernel\Di\Exception\Container;

use SuperKernel\Di\Exception\ContainerException;

final class FactoryResolutionException extends ContainerException
{
	private function __construct(string $message)
	{
		parent::__construct($message);
	}

	public static function noInstantiationDefiner(string $entry): self
	{
		return new self("No instantiation definer found for entry \"$entry\"");
	}
}