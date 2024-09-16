<?php

declare(strict_types=1);

namespace Codex\Tests\Foundation\Assets;

use Codex\Contracts\HasInlineScript;
use Codex\Foundation\Assets\Enqueue;
use Codex\Foundation\Assets\Script;
use Codex\Foundation\Assets\Style;
use Codex\Tests\WPTestCase;

class EnqueueTest extends WPTestCase
{
	// phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
	public function tear_down(): void
	{
		$GLOBALS['wp_scripts'] = null;
		$GLOBALS['wp_styles'] = null;

		parent::tear_down();
	}

	/** @dataProvider dataAddScript */
	public function testAddScripts(string $filePath, string $handle, string $fileUrl): void
	{
		$enqueue = new Enqueue('/public/assets', 'https://example.com/assets');
		$enqueue->addScripts(new Script($filePath));
		$enqueue->scripts();

		$wpScripts = wp_scripts();

		$this->assertTrue(wp_script_is($handle));
		$this->assertSame($handle, $wpScripts->registered[$handle]->handle);
		$this->assertSame($fileUrl, $wpScripts->registered[$handle]->src);
	}

	/** @dataProvider dataAddScript */
	public function testAddScriptsWithPathHasTrailingSlash(string $filePath, string $handle, string $fileUrl): void
	{
		$enqueue = new Enqueue('/public/assets/', 'https://example.com/assets/');
		$enqueue->addScripts(new Script($filePath));
		$enqueue->scripts();

		$wpScripts = wp_scripts();

		$this->assertTrue(wp_script_is($handle));
		$this->assertSame($handle, $wpScripts->registered[$handle]->handle);
		$this->assertSame($fileUrl, $wpScripts->registered[$handle]->src);
	}

	public static function dataAddScript(): iterable
	{
		// With leading slash.
		yield ['/foo.js', 'foo', 'https://example.com/assets/foo.js'];
		yield ['/foo/index.js', 'foo-index', 'https://example.com/assets/foo/index.js'];
		yield ['/foo/bar.js', 'foo-bar', 'https://example.com/assets/foo/bar.js'];
		yield ['/foo/bar/index.js', 'foo-bar-index', 'https://example.com/assets/foo/bar/index.js'];
	}

	public function testAddScriptsWithStaticHandle(): void
	{
		$enqueue = new Enqueue('/public/assets', 'https://example.com/assets');
		$enqueue->addScripts(new Script('/foo.js', 'hello-world'));
		$enqueue->scripts();

		$wpScripts = wp_scripts();

		$this->assertTrue(wp_script_is('hello-world'));
		$this->assertSame('hello-world', $wpScripts->registered['hello-world']->handle);
		$this->assertSame('https://example.com/assets/foo.js', $wpScripts->registered['hello-world']->src);
	}

	public function testAddScriptsWithPrefix(): void
	{
		$enqueue = new Enqueue('/public/assets/', 'https://example.com/assets/');
		$enqueue->setPrefix('hello-world-');
		$enqueue->addScripts(new Script('/foo.js'));
		$enqueue->scripts();

		$wpScripts = wp_scripts();

		$this->assertTrue(wp_script_is('hello-world-foo'));
		$this->assertSame('hello-world-foo', $wpScripts->registered['hello-world-foo']->handle);
		$this->assertSame('https://example.com/assets/foo.js', $wpScripts->registered['hello-world-foo']->src);
	}

	public function testAddScriptsWithVersion(): void
	{
		$enqueue = new Enqueue(self::getFixturesPath('/dist'), 'https://example.com/assets');
		$enqueue->addScripts((new Script('/admin.js'))->versionedAt('1.0.0-beta.1'));
		$enqueue->scripts();

		$wpScripts = wp_scripts();

		$this->assertTrue(wp_script_is('admin'));
		$this->assertSame('https://example.com/assets/admin.js', $wpScripts->registered['admin']->src);
		$this->assertSame('1.0.0-beta.1', $wpScripts->registered['admin']->ver);
	}

	public function testAddScriptsWithVersionFromManifest(): void
	{
		$enqueue = new Enqueue(self::getFixturesPath('/dist'), 'https://example.com/assets');
		$enqueue->addScripts(new Script('/admin.js'));
		$enqueue->scripts();

		$wpScripts = wp_scripts();

		$this->assertTrue(wp_script_is('admin'));
		$this->assertSame('https://example.com/assets/admin.js', $wpScripts->registered['admin']->src);
		$this->assertSame('7cb1493e4611c2ec1223', $wpScripts->registered['admin']->ver);
	}

	public function testAddScriptsDependenciesFromManifest(): void
	{
		$enqueue = new Enqueue(self::getFixturesPath('/dist'), 'https://example.com/assets');
		$enqueue->addScripts(new Script('/admin.js'));
		$enqueue->scripts();

		$wpScripts = wp_scripts();

		$this->assertTrue(wp_script_is('admin'));
		$this->assertSame('https://example.com/assets/admin.js', $wpScripts->registered['admin']->src);
		$this->assertSame('7cb1493e4611c2ec1223', $wpScripts->registered['admin']->ver);
		$this->assertSame(['react', 'react-dom', 'wp-api-fetch', 'wp-dom-ready', 'wp-i18n'], $wpScripts->registered['admin']->deps);
	}

	public function testAddScriptsDependenciesFromManifestAndArgs(): void
	{
		$enqueue = new Enqueue(self::getFixturesPath('/dist'), 'https://example.com/assets/');
		$enqueue->addScripts((new Script('/admin.js'))->dependsOn('vue'));
		$enqueue->scripts();

		$wpScripts = wp_scripts();

		$this->assertTrue(wp_script_is('admin'));
		$this->assertSame('https://example.com/assets/admin.js', $wpScripts->registered['admin']->src);
		$this->assertSame('7cb1493e4611c2ec1223', $wpScripts->registered['admin']->ver);
		$this->assertSame(
			[
				'react',
				'react-dom',
				'wp-api-fetch',
				'wp-dom-ready',
				'wp-i18n',
				'vue',
			],
			$wpScripts->registered['admin']->deps,
		);
	}

	public function testAddScriptsWithInlineScript(): void
	{
		$this->markTestIncomplete('Requires custom assertion');

		$inlineScript = new class implements HasInlineScript {
			public function getInlineScriptPosition(): string
			{
				return 'before';
			}

			public function getInlineScriptContent(): string
			{
				return 'console.log("Hello, World!");';
			}
		};

		$enqueue = new Enqueue(self::getFixturesPath('/dist'), 'https://example.com/assets/');
		$enqueue->addScripts((new Script('/admin.js'))->withInlineScripts($inlineScript));
		$enqueue->scripts();

		$this->assertStringContainsString(
			<<<'HTML'
			<script type="text/javascript" id="admin-js-before">
			/* <![CDATA[ */
			console.log("Hello, World!");
			/* ]]> */
			</script>
			<script type="text/javascript" src="https://example.com/assets/admin.js?ver=7cb1493e4611c2ec1223" id="admin-js"></script>
			HTML,
			get_echo('wp_print_scripts'),
		);
	}

	public function testAddScriptsWithTranslations(): void
	{
		$this->markTestIncomplete('Requires custom assertion');

		$enqueue = new Enqueue(self::getFixturesPath('/dist'), 'https://example.com/assets/');
		$enqueue->setTranslations('text-domain', self::getFixturesPath('/inc/languages'));
		$enqueue->addScripts((new Script('/admin.js'))->withTranslation());
		$enqueue->scripts();

		$wpScripts = wp_scripts();

		$this->assertSame('text-domain', $wpScripts->registered['admin']->textdomain);
		$this->assertStringEndsWith('/tests/phpunit/fixtures/inc/languages/', $wpScripts->registered['admin']->translations_path);
		$this->assertStringContainsString(
			<<<'SCRIPT'
			<script type="text/javascript" id="admin-js-translations">
			/* <![CDATA[ */
			( function( domain, translations ) {
			SCRIPT,
			get_echo('wp_print_scripts'),
		);
	}

	public function testAddScriptsWithNameContainingSuffix(): void
	{
		$enqueue = new Enqueue('/public/assets/', 'https://example.com/assets/');
		$enqueue->addScripts(new Script('/bar.min.js'));
		$enqueue->scripts();

		$wpScripts = wp_scripts();

		$this->assertTrue(wp_script_is('bar-min'));
		$this->assertSame('https://example.com/assets/bar.min.js', $wpScripts->registered['bar-min']->src);
	}

	public function testAddStyles(): void
	{
		$enqueue = new Enqueue('/public/assets/', 'https://example.com/assets/');
		$enqueue->addStyles(new Style('/foo.css'));
		$enqueue->styles();

		$wpStyles = wp_styles();

		$this->assertTrue(wp_style_is('foo'));
		$this->assertSame('https://example.com/assets/foo.css', $wpStyles->registered['foo']->src);
	}

	public function testAddStylesWithVersion(): void
	{
		$enqueue = new Enqueue('/public/assets/', 'https://example.com/assets/');
		$enqueue->addStyles((new Style('/admin.css'))->versionedAt('1.0.0'));
		$enqueue->styles();

		$wpStyles = wp_styles();

		$this->assertTrue(wp_style_is('admin'));
		$this->assertSame('https://example.com/assets/admin.css', $wpStyles->registered['admin']->src);
		$this->assertSame('1.0.0', $wpStyles->registered['admin']->ver);
	}

	public function testAddStyleWithVersionFromManifest(): void
	{
		$enqueue = new Enqueue(self::getFixturesPath('/dist'), 'https://example.com/assets/');
		$enqueue->addStyles(new Style('/admin.css'));
		$enqueue->styles();

		/** @var WP_Styles $wpStyles */
		$wpStyles = wp_styles();

		$this->assertTrue(wp_style_is('admin'));
		$this->assertSame('https://example.com/assets/admin.css', $wpStyles->registered['admin']->src);
		$this->assertSame('7cb1493e4611c2ec1223', $wpStyles->registered['admin']->ver);
		$this->assertSame([], $wpStyles->registered['admin']->deps);
	}

	public function testAddStyleWithPrefix(): void
	{
		$enqueue = new Enqueue('/public/assets/', 'https://example.com/assets/');
		$enqueue->setPrefix('prefix');
		$enqueue->addStyles(new Style('/foo.css'));
		$enqueue->styles();

		$wpStyles = wp_styles();

		$this->assertTrue(wp_style_is('prefix-foo'));
		$this->assertSame('https://example.com/assets/foo.css', $wpStyles->registered['prefix-foo']->src);
	}

	public function testAddStyleDependsOn(): void
	{
		$enqueue = new Enqueue(self::getFixturesPath('/dist'), 'https://example.com/assets/');
		$enqueue->addStyles((new Style('/admin.css'))->dependsOn('bootstrap'));
		$enqueue->styles();

		$wpStyles = wp_styles();

		$this->assertTrue(wp_style_is('admin'));
		$this->assertSame('https://example.com/assets/admin.css', $wpStyles->registered['admin']->src);
		$this->assertSame('7cb1493e4611c2ec1223', $wpStyles->registered['admin']->ver);
		$this->assertSame(['bootstrap'], $wpStyles->registered['admin']->deps);
	}
}
