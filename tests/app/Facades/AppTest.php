<?php

declare(strict_types=1);

namespace Codex\Tests\Facades;

use Codex\Application;
use Codex\Contracts\Extendable;
use Codex\Facades\App;
use Codex\Foundation\Settings\Registry;
use Codex\Foundation\Settings\Support\SettingRegistrar;
use Codex\Providers\SettingsProvider;
use Codex\Tests\WPTestCase;
use Psr\Container\ContainerInterface;

use function array_key_exists;

class AppTest extends WPTestCase
{
	public function testName(): void
	{
		$app = new Application(
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
		$app->addServices([SettingsProvider::class]);
		$app->setPluginFilePath(self::getFixturesPath('/plugin-name.php'));
		$app->boot();

		$this->assertSame('wp-test', App::name());
	}

	public function testSettings(): void
	{
		$app = new Application(
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
		$app->addServices([SettingsProvider::class]);
		$app->setPluginFilePath(self::getFixturesPath('/plugin-name.php'));
		$app->boot();

		$settings = App::settings();

		$this->assertArrayNotHasKey('wp-test/plugin-foo', $settings); // Unsupported file extension, `.json`.
		$this->assertArrayNotHasKey('wp-test/plugin-name-1', $settings); // Settings empty.

		// wp-test/plugin-name-0
		$this->assertInstanceOf(Registry::class, $settings['wp-test/plugin-name-0']);
		$this->assertTrue($settings['wp-test/plugin-name-0']->isRegistered());
		$this->assertTrue(array_key_exists('wp_test_foo', $settings['wp-test/plugin-name-0']->getRegistered()));
		$this->assertInstanceOf(SettingRegistrar::class, $settings['wp-test/plugin-name-0']->getRegistered()['wp_test_foo']);

		// wp-test/plugin-name-2
		$this->assertInstanceOf(Registry::class, $settings['wp-test/plugin-name-2']);
		$this->assertTrue($settings['wp-test/plugin-name-2']->isRegistered());

		$setting = App::settings('plugin-name-2');

		$this->assertInstanceOf(Registry::class, $setting);
		$this->assertSame('wp-test/plugin-name-2', $setting->getSettingGroup());
	}
}
