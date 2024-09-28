<?php

declare(strict_types=1);

namespace Codex\Tests\Facades;

use Codex\Contracts\Extendable;
use Codex\Facades\Config;
use Codex\Plugin;
use Codex\Tests\WPTestCase;
use Psr\Container\ContainerInterface;

class ConfigTest extends WPTestCase
{
	public function testGet(): void
	{
		$app = new Plugin(
			new class () implements Extendable {
				public function getInstances(ContainerInterface $container): iterable
				{
					return [];
				}

				public function init(): void
				{
				}
			},
		);
		$app->setPluginFilePath(self::getFixturesPath('/plugin-name.php'));
		$app->boot();

		self::assertSame('wp-test', Config::get('app.text_domain'));
		self::assertSame('/dist', Config::get('app.assets_path'));
		self::assertSame('https://example.org/dist', Config::get('app.assets_url'));
		self::assertSame('wp_test_', Config::get('app.option_prefix'));
		self::assertSame('wp_test_', Config::get('app.option_prefix'));
		self::assertTrue(Config::has('app.option_prefix'));
		self::assertFalse(Config::has('non-existent-key'));
		self::assertTrue(Config::isBlank('empty'));
		self::assertTrue(Config::isBlank('blank'));
	}
}
