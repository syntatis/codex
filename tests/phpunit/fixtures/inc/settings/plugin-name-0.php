<?php

declare(strict_types=1);

use Codex\Foundation\Settings\Setting;

return [
	(new Setting('foo'))
		->withDefault('Hello, World!')
		->withConstraints(
			function ($value) {
				return strlen($value) > 0;
			}
		)
];
