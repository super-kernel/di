<?php

declare(strict_types=1);

namespace SuperKernel\Di\Tests\Unit;

use PHPUnit\Framework\TestCase;
use stdClass;
use SuperKernel\Di\Contract\RetainedEntriesInterface;
use SuperKernel\Di\Storage\RetainedEntries;

final class RetainedEntriesTest extends TestCase
{
    /**
     * These assertions protect the public interface contract even when static types already imply it.
     *
     * @noinspection PhpConditionAlreadyCheckedInspection
     */
    public function testRetainsNullAsAPresentEntry(): void
    {
        $entries = new RetainedEntries();

        $this->assertInstanceOf(RetainedEntriesInterface::class, $entries);
        $this->assertFalse($entries->has('nullable'));
        $this->assertNull($entries->retain('nullable', null));
        $this->assertTrue($entries->has('nullable'));
        $this->assertNull($entries->get('nullable'));
    }

    public function testKeepsTheFirstValueWhenAnIdentifierIsRetainedAgain(): void
    {
        $entries = new RetainedEntries();
        $retained = new stdClass();

        $entries->retain('service', $retained);

        $this->assertSame($retained, $entries->retain('service', new stdClass()));
        $this->assertSame($retained, $entries->get('service'));
    }
}
