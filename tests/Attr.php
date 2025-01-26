<?php
declare(strict_types=1);

namespace Tests;

use Attribute;

#[Attribute(Attribute::TARGET_METHOD)]
class Attr
{
}