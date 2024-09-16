<?php

declare(strict_types=1);

namespace Codex\Foundation\Hooks;

use Attribute;

#[Attribute(Attribute::TARGET_METHOD | Attribute::TARGET_CLASS | Attribute::IS_REPEATABLE)]
class Action extends Filter
{
}
