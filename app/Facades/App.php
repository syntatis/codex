<?php

declare(strict_types=1);

namespace Codex\Facades;

use Codex\Abstracts\Facade;

/**
 * @method static string name() Retrieve the application name.
 * @method static string dir(?string $path = '') Retrieve the path to a file or directory within the app.
 * @method static string url(?string $path = '') Retrieve the URL to a file or directory within the app.
 */
final class App extends Facade
{
	protected static function getFacadeAccessor(): string
	{
		return 'app';
	}
}
