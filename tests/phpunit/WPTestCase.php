<?php

declare(strict_types=1);

namespace Codex\Tests;

use WP_UnitTestCase;

abstract class WPTestCase extends WP_UnitTestCase
{
	private static string $fixturesPath = __DIR__ . '/fixtures';

	protected static function getFixturesPath(?string $path = ''): string
	{
		return self::$fixturesPath . $path;
	}
}
