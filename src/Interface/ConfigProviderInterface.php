<?php
declare(strict_types=1);

namespace SuperKernel\Di\Interface;

/**
 * The configuration provider's class needs to implement this interface.
 *
 * @ConfigProviderInterface
 * @\SuperKernel\Di\Interface\ConfigProviderInterface
 */
interface ConfigProviderInterface
{
	public function __construct();

	public function __invoke(): array;
}