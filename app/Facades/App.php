<?php

declare(strict_types=1);

namespace Codex\Facades;

use Codex\Abstracts\Facade;
use Codex\Core\Config;

/**
 * @method static string name() Retrieve the application name.
 * @method static Config config() Retrieve the app config object.
 */
final class App extends Facade
{
	protected static function getFacadeAccessor(): string
	{
		return 'app';
	}
}
