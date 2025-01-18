<?php

declare (strict_types=1);
namespace Tests;

use SuperKernel\Di\Annotation\Cases;
class TestCase
{
    public function __construct($args)
    {
        return new class(... func_get_args())
        {
            public function __construct(public array $config = [])
            {
                var_dump(__METHOD__);
            }
            public function action()
            {
                var_dump(...func_get_args());
            }
        };
    }
}