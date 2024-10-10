<?php

declare(strict_types=1);

namespace Codex\Tests\Core;

use Codex\Core\Config;
use Codex\Tests\WPTestCase;

class ConfigTest extends WPTestCase
{
	/**
	 * @dataProvider dataIsBlank
	 *
	 * @param mixed $value
	 */
	public function testIsBlank($value, bool $expect): void
	{
		$config = new Config(['foo' => $value]);

		$this->assertSame($expect, $config->isBlank('foo'));
	}

	public static function dataIsBlank(): iterable
	{
		yield 'null' => [null, true];
		yield 'false' => [false, true];
		yield 'empty string' => ['', true];
		yield 'empty array' => [[], true];
		yield 'whitespace string' => [' ', true];
		yield 'non-empty string' => ['foo', false];
		yield 'non-empty array' => [['foo'], false];
	}
}
