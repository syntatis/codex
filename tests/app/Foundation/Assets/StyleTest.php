<?php

declare(strict_types=1);

namespace Codex\Tests\Foundation\Assets;

use Codex\Foundation\Assets\Style;
use Codex\Tests\WPTestCase;
use InvalidArgumentException;

class StyleTest extends WPTestCase
{
	/** @dataProvider dataTestFilePathInvalid */
	public function testFilePathInvalidExtension(string $filePath, string $errorMessage): void
	{
		$this->expectException(InvalidArgumentException::class);
		$this->expectExceptionMessage($errorMessage);

		new Style($filePath);
	}

	public static function dataTestFilePathInvalid(): iterable
	{
		yield ['/index.scss', 'The file path must end with `.css`.'];
		yield ['/script.scss', 'The file path must end with `.css`.'];

		// Without leading slash.
		yield ['index.css', 'The file path must start with a leading slash.'];
		yield ['admin/script.css', 'The file path must start with a leading slash.'];

		// With dashes.
		yield ['/admin-style.scss', 'The file path must end with `.css`.'];
		yield ['/admin-style.scss', 'The file path must end with `.css`.'];

		// With dashes.
		yield ['/admin_style.scss', 'The file path must end with `.css`.'];
		yield ['/admin_style.tsx', 'The file path must end with `.css`.'];

		// Sub-directories
		yield ['/admin/index.scss', 'The file path must end with `.css`.'];
		yield ['/admin/index.scss', 'The file path must end with `.css`.'];
		yield ['/admin/style.scss', 'The file path must end with `.css`.'];
		yield ['/admin/style.scss', 'The file path must end with `.css`.'];

		// Nested sub-directories
		yield ['/admin/app/index.scss', 'The file path must end with `.css`.'];
		yield ['/admin/app/index.scss', 'The file path must end with `.css`.'];
		yield ['/admin/app/style.scss', 'The file path must end with `.css`.'];
		yield ['/admin/app/style.scss', 'The file path must end with `.css`.'];
	}

	/** @dataProvider dataGetHandle */
	public function testGetHandle(string $filePath, string $expected): void
	{
		$this->assertSame($expected, (new Style($filePath))->getHandle());
	}

	public static function dataGetHandle(): iterable
	{
		yield ['/index.css', 'index'];
		yield ['/style.css', 'style'];
		yield ['/style.min.css', 'style-min'];

		// With dashes.
		yield ['/admin-style.css', 'admin-style'];
		yield ['/admin-style.min.css', 'admin-style-min'];

		// Sub-directories.
		yield ['/admin/index.css', 'admin-index'];
		yield ['/admin/style.css', 'admin-style'];
		yield ['/admin/style.min.css', 'admin-style-min'];

		// Nested Sub-directories.
		yield ['/admin/app/index.css', 'admin-app-index'];
		yield ['/admin/app/style.css', 'admin-app-style'];
		yield ['/admin/app/style.min.css', 'admin-app-style-min'];
	}

	/** @dataProvider dataGetHandleFromArg */
	public function testGetHandleFromArg(string $filePath): void
	{
		$this->assertSame(
			'hello-world-admin-style',
			(new Style($filePath, 'hello-world-admin-style'))->getHandle(),
		);
	}

	public static function dataGetHandleFromArg(): iterable
	{
		yield ['/index.css'];
		yield ['/style.css'];
	}

	/** @dataProvider dataGetFilePath */
	public function testGetFilePath(string $filePath, string $expected): void
	{
		$this->assertSame($expected, (new Style($filePath))->getFilePath());
	}

	public static function dataGetFilePath(): iterable
	{
		yield ['/index.css', '/index.css'];
		yield ['/style.css', '/style.css'];
		yield ['/style.min.css', '/style.min.css'];

		// With dashes.
		yield ['/admin-style.css', '/admin-style.css'];

		// Sub-directories.
		yield ['/admin/index.css', '/admin/index.css'];
		yield ['/admin/style.css', '/admin/style.css'];
		yield ['/admin/style.min.css', '/admin/style.min.css'];

		// Nested sub-directories.
		yield ['/admin/app/index.css', '/admin/app/index.css'];
		yield ['/admin/app/style.css', '/admin/app/style.css'];
	}

	public function testForMedia(): void
	{
		$style = new Style('/style.css');

		$this->assertSame('all', $style->getMedia());

		$style = $style->forMedia('print');

		$this->assertSame('print', $style->getMedia());
	}

	public function testDependsOn(): void
	{
		$style = (new Style('/style.css'))->dependsOn('main');

		$this->assertSame(['main'], $style->getDependencies());
	}

	public function testVersionedAt(): void
	{
		$style = (new Style('/style.css'))->versionedAt('v1');

		$this->assertSame('v1', $style->getVersion());
	}
}
