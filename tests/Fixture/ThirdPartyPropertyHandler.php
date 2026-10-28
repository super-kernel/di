<?php

declare(strict_types=1);

namespace SuperKernel\Di\Tests\Fixture;

use InvalidArgumentException;
use SuperKernel\Attribute\PropertyHandler;
use SuperKernel\Contract\ContainerInterface;
use SuperKernel\Contract\PropertyAttributeHandlerInterface;
use SuperKernel\Contract\PropertyContextInterface;

#[PropertyHandler(ThirdPartyPropertyAttribute::class)]
final readonly class ThirdPartyPropertyHandler implements PropertyAttributeHandlerInterface
{
    public function __construct(ContainerInterface $container)
    {
    }

    public function handle(PropertyContextInterface $context, object $attribute): void
    {
        if (!$attribute instanceof ThirdPartyPropertyAttribute) {
            throw new InvalidArgumentException('Unexpected third-party property Attribute.');
        }

        if ($attribute->assign) {
            $context->assign($attribute->value);
        }
    }
}
