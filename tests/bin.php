<?php
declare(strict_types=1);

use SuperKernel\Di\ContainerFactory;

require_once __DIR__ . '/../vendor/autoload.php';

new ContainerFactory()()->get(Tests\Case\TestCase::class)->test();