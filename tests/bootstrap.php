<?php
declare(strict_types=1);

use SuperKernel\Di\Container;
use SuperKernel\Di\Factory\ContainerFactory;
use Tests\Application;

require_once __DIR__ . '/../vendor/autoload.php';

$container = new Container()->get(ContainerFactory::class);

$container->get(Application::class)->run();