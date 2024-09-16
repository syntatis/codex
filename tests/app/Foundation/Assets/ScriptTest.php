<?php

declare(strict_types=1);

namespace Codex\Tests\Foundation\Assets;

use Codex\Contracts\HasInlineScript;
use Codex\Foundation\Assets\Script;
use Codex\Tests\WPTestCase;
use InvalidArgumentException;

class ScriptTest extends WPTestCase
{
	/** @dataProvider dataTestFilePathInvalid */
	public function testFilePathInvalidExtension(string $filePath, string $errorMessage): void
	{
		$this->expectException(InvalidArgumentException::class);
		$this->expectExceptionMessage($errorMessage);

		new Script($filePath);
	}

	public static function dataTestFilePathInvalid(): iterable
	{
		yield ['/index.ts', 'The file path must end with `.js`.'];
		yield ['/index.tsx', 'The file path must end with `.js`.'];
		yield ['/script.ts', 'The file path must end with `.js`.'];
		yield ['/script.tsx', 'The file path must end with `.js`.'];

		// Without leading slash.
		yield ['index.js', 'The file path must start with a leading slash.'];
		yield ['admin/script.js', 'The file path must start with a leading slash.'];

		// With dashes.
		yield ['/admin-script.ts', 'The file path must end with `.js`.'];
		yield ['/admin-script.tsx', 'The file path must end with `.js`.'];

		// With dashes.
		yield ['/admin_script.ts', 'The file path must end with `.js`.'];
		yield ['/admin_script.tsx', 'The file path must end with `.js`.'];

		// Sub-directories
		yield ['/admin/index.ts', 'The file path must end with `.js`.'];
		yield ['/admin/index.tsx', 'The file path must end with `.js`.'];
		yield ['/admin/script.ts', 'The file path must end with `.js`.'];
		yield ['/admin/script.tsx', 'The file path must end with `.js`.'];

		// Nested sub-directories
		yield ['/admin/app/index.ts', 'The file path must end with `.js`.'];
		yield ['/admin/app/index.tsx', 'The file path must end with `.js`.'];
		yield ['/admin/app/script.ts', 'The file path must end with `.js`.'];
		yield ['/admin/app/script.tsx', 'The file path must end with `.js`.'];
	}

	/** @dataProvider dataGetHandle */
	public function testGetHandle(string $filePath, string $expected): void
	{
		$this->assertSame($expected, (new Script($filePath))->getHandle());
	}

	public static function dataGetHandle(): iterable
	{
		yield ['/index.js', 'index'];
		yield ['/script.min.js', 'script-min'];
		yield ['/admin-script.js', 'admin-script']; // With dashes.
		yield ['/admin_script.js', 'admin-script']; // With underscores.

		// Sub-directories.
		yield ['/admin/index.js', 'admin-index'];
		yield ['/admin/script.js', 'admin-script'];
		yield ['/admin/script.min.js', 'admin-script-min'];

		// Nested sub-directories
		yield ['/admin/app/index.js', 'admin-app-index'];
		yield ['/admin/app/script.js', 'admin-app-script'];
		yield ['/admin/app/script.min.js', 'admin-app-script-min'];
	}

	/** @dataProvider dataGetHandleFromArg */
	public function testGetHandleFromArg(string $filePath): void
	{
		$this->assertSame(
			'hello-world-admin-script',
			(new Script($filePath, 'hello-world-admin-script'))->getHandle(),
		);
	}

	public static function dataGetHandleFromArg(): iterable
	{
		yield ['/index.js'];
		yield ['/script.js'];
	}

	/** @dataProvider dataGetFilePath */
	public function testGetFilePath(string $filePath, string $expected): void
	{
		$this->assertSame($expected, (new Script($filePath))->getFilePath());
	}

	public static function dataGetFilePath(): iterable
	{
		yield ['/index.js', '/index.js'];
		yield ['/script.min.js', '/script.min.js'];
		yield ['/admin-script.js', '/admin-script.js'];
		yield ['/admin_script.js', '/admin_script.js'];

		// Sub-directories.
		yield ['/admin/index.js', '/admin/index.js'];
		yield ['/admin/script.js', '/admin/script.js'];
		yield ['/admin/script.min.js', '/admin/script.min.js'];

		// Nested sub-directories
		yield ['/admin/app/index.js', '/admin/app/index.js'];
		yield ['/admin/app/script.js', '/admin/app/script.js'];
		yield ['/admin/app/script.min.js', '/admin/app/script.min.js'];
	}

	/** @dataProvider dataGetManifestPath */
	public function testGetManifestPath(string $filePath, string $expected): void
	{
		$this->assertSame($expected, (new Script($filePath))->getManifestPath());
	}

	public static function dataGetManifestPath(): iterable
	{
		yield ['/index.js', '/index.asset.php'];
		yield ['/script.js', '/script.asset.php'];

		// With dashes.
		yield ['/admin-script.js', '/admin-script.asset.php'];

		// Sub-directories.
		yield ['/admin/index.js', '/admin/index.asset.php'];
		yield ['/admin/script.js', '/admin/script.asset.php'];
	}

	public function testWithInlineScripts(): void
	{
		$inlineScript = new class implements HasInlineScript {
			public function getInlineScriptPosition(): string
			{
				return 'before';
			}

			public function getInlineScript(): string
			{
				return 'console.log("Hello, World!");';
			}
		};

		$script = (new Script('/admin/index.js'))->withInlineScripts($inlineScript);

		$this->assertSame([$inlineScript], $script->getInlineScripts());
	}

	public function testGetPosition(): void
	{
		$script = new Script('/admin/index.js');

		$this->assertFalse($script->isAtFooter());

		$script = (new Script('/admin/index.js'))->atFooter();

		$this->assertTrue($script->isAtFooter());

		$script = (new Script('/admin/index.js'))->atFooter(false);

		$this->assertFalse($script->isAtFooter());
	}

	public function testIsTranslated(): void
	{
		$script = new Script('/admin/index.js');

		$this->assertFalse($script->isTranslated());

		$script = (new Script('/admin/index.js'))->withTranslation();

		$this->assertTrue($script->isTranslated());

		$script = $script->withTranslation(false);

		$this->assertFalse($script->isTranslated());
	}

	public function testDependsOn(): void
	{
		$script = (new Script('/style.js'))->dependsOn('wp-component');

		$this->assertSame(['wp-component'], $script->getDependencies());
	}
}
