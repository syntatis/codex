<?php

declare(strict_types=1);

namespace Codex\Facades;

use Codex\Abstracts\Facade;

/**
 * @method static mixed get(string $key, mixed $default) Retrieve value from config.
 * @method static bool has(string $key) Check if key exists in config.
 * @method static bool isBlank(string $key) Check if value of the given key is blank.
 */
final class Config extends Facade
{
	protected static function getFacadeAccessor(): string
	{
		return 'app/config';
	}
}
