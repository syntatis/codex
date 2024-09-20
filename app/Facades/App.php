<?php

declare(strict_types=1);

namespace Codex\Facades;

use Codex\Abstracts\Facade;
use Codex\Foundation\Settings\Registry;

/**
 * @method static string name Retrieve the application name.
 * @method static array<string,Registry>|Registry|null settings(?string $group = null) Retrieve the setting registry collection.
 */
final class App extends Facade
{
	protected static function getFacadeAccessor(): string
	{
		return 'app';
	}
}
