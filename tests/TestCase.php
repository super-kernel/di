<?php
declare(strict_types=1);

namespace Tests;

final class TestCase
{
    public function __construct()
    {
        var_dump(__METHOD__);
    }
}