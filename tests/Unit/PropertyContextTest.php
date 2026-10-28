<?php

declare(strict_types=1);

namespace SuperKernel\Di\Tests\Unit;

use LogicException;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;
use SuperKernel\Di\Property\PropertyContext;
use SuperKernel\Di\Tests\Fixture\ThirdPartyPropertyService;

final class PropertyContextTest extends TestCase
{
    public function testDistinguishesNoAssignmentFromAnExplicitNullAssignment(): void
    {
        $property = new ReflectionProperty(ThirdPartyPropertyService::class, 'nullable');
        $context = new PropertyContext(ThirdPartyPropertyService::class, $property);

        self::assertSame(ThirdPartyPropertyService::class, $context->className());
        self::assertSame($property, $context->property());
        self::assertFalse($context->hasAssignment());

        $context->assign(null);

        self::assertTrue($context->hasAssignment());
        self::assertNull($context->assignment());
    }

    public function testRejectsReadingOrReplacingAnUnavailableAssignment(): void
    {
        $context = new PropertyContext(
            ThirdPartyPropertyService::class,
            new ReflectionProperty(ThirdPartyPropertyService::class, 'handled'),
        );

        try {
            $context->assignment();
            self::fail('Expected an unavailable assignment to fail.');
        } catch (LogicException $exception) {
            self::assertStringContainsString('has no assignment', $exception->getMessage());
        }

        $context->assign('first');

        $this->expectException(LogicException::class);
        $this->expectExceptionMessageIsOrContains('already has an assignment');

        $context->assign('second');
    }
}
