<?php

declare(strict_types=1);

namespace Codex\Facades;

use Codex\Abstracts\Facade;

/**
 * @method static string get(string $key, mixed $defaults = null) Retrieve value of the given key from config.
 * @method static bool has(string $key) Check if a given key exists on the config.
 * @method static bool isBlank(string $key) Check if a given key on the config is blank.
 */
final class Config extends Facade
{
	protected static function getFacadeAccessor(): string
	{
		return 'config';
	}
}
