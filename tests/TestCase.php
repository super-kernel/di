<?php
declare(strict_types=1);

namespace Tests;

use SuperKernel\Di\Annotation\Cases;

/**
 * @TestCase
 * @\Tests\TestCase
 */
#[
    Cases,
]
class TestCase
{
    #[Attr]
    protected array $awwwwwwww;

    #[Attr]
    public function __construct(public array $config = [])
    {
        var_dump(__METHOD__);
    }

    #[Attr]
    public function action()
    {
        var_dump(... func_get_args());
    }
}