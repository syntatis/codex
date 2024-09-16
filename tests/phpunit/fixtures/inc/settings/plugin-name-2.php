<?php

declare(strict_types=1);

use Codex\Foundation\Settings\Setting;

return [(new Setting('bar', 'integer'))->withDefault(100)];
