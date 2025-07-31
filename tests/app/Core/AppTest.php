<?php

declare(strict_types=1);

namespace Codex\Tests\Core;

use Codex\Core\App;
use Codex\Tests\WPTestCase;

class AppTest extends WPTestCase
{
	/** @dataProvider dataAppName */
	public function testAppName(string $value): void
	{
		$this->assertSame($value, (new App($value))->name());
	}

	public static function dataAppName(): iterable
	{
		yield 'foo' => ['foo', 'foo'];

		// The class does not check for empty string, it should be validated before
		// being passed to the method.
		yield 'empty' => ['', ''];
	}

	/** @dataProvider dataAppDir */
	public function testAppDir(string $value, string $expect): void
	{
		$this->assertSame(
			WP_PLUGIN_DIR . '/' . $expect,
			(new App('acme', [
				'plugin_file_path' => WP_PLUGIN_DIR . '/' . $value . '/plugin.php',
				'plugin_dir_path' => WP_PLUGIN_DIR . '/' . $value,
			]))->dir(),
		);
	}

	public static function dataAppDir(): iterable
	{
		yield 'foo' => ['foo', 'foo'];
		yield '/foo' => ['/foo', '/foo'];

		// The class does not check for empty string, it should be validated before
		// being passed to the method.
		yield 'empty' => ['', ''];
	}

	/** @dataProvider dataAppDirWithPathArg */
	public function testAppDirWithPathArg(string $base, string $path, string $expect): void
	{
		$this->assertStringEndsWith(
			WP_PLUGIN_DIR . '/' . $expect,
			(new App('acme', [
				'plugin_file_path' => WP_PLUGIN_DIR . '/' . $base . '/plugin.php',
				'plugin_dir_path' => WP_PLUGIN_DIR . '/' . $base,
			]))->dir($path),
		);
	}

	public static function dataAppDirWithPathArg(): iterable
	{
		yield 'bar' => ['foo', 'bar', 'foo/bar'];
		yield '/bar' => ['foo', '/bar', 'foo/bar'];
		yield 'bar/' => ['foo', 'bar/', 'foo/bar'];
		yield '//bar' => ['foo', '//bar', 'foo/bar'];
		yield './bar' => ['foo', './bar', 'foo/bar'];
		yield 'file.jpg' => ['foo', 'file.jpg', 'foo/file.jpg'];
		yield '/file.jpg' => ['foo', '/file.jpg', 'foo/file.jpg'];
		yield '//file.jpg' => ['foo', '//file.jpg', 'foo/file.jpg'];
		yield './file.jpg' => ['foo', './file.jpg', 'foo/file.jpg'];
	}

	/** @dataProvider dataAppUrl */
	public function testAppUrl(string $base, string $path, string $expect): void
	{
		$this->assertSame(
			$expect,
			(new App('acme', [
				'plugin_file_path' => WP_PLUGIN_DIR . '/' . $base . '/plugin.php',
				'plugin_dir_path' => WP_PLUGIN_DIR . '/' . $base,
			]))->url($path),
		);
	}

	public static function dataAppUrl(): iterable
	{
		yield 'empty' => ['foo', '', 'http://example.org/wp-content/plugins/foo'];
		yield 'bar' => ['foo', 'bar', 'http://example.org/wp-content/plugins/foo/bar'];
	}
}
