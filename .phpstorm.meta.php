<?php
declare(strict_types=1);

namespace PHPSTORM_META {
	// Reflect
	override(\SuperKernel\Di\Contract\Container::get(0), map(['' => '@']));
	override(\Psr\Container\ContainerInterface::get(0), map(['' => '@']));
	override(\SuperKernel\Di\Contract\ContainerInterface::get(0), map(['' => '@']));
}