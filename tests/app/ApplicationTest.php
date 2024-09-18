<?php

declare(strict_types=1);

namespace Codex\Tests;

use Codex\Abstracts\ServiceProvider;
use Codex\Application;
use Codex\Contracts\Activatable;
use Codex\Contracts\Bootable;
use Codex\Contracts\Deactivatable;
use Codex\Contracts\Extendable;
use Codex\Contracts\HasAdminScripts;
use Codex\Contracts\HasPublicScripts;
use Codex\Contracts\Hookable;
use Codex\Core\Config;
use Codex\Foundation\Assets\Assets;
use Codex\Foundation\Assets\Enqueue;
use Codex\Foundation\Assets\Script;
use Codex\Foundation\Assets\Style;
use Codex\Foundation\Blocks;
use Codex\Foundation\Hooks\Hook;
use Codex\Foundation\Settings\Registry as SettingsRegistry;
use Codex\Foundation\Settings\Support\SettingRegistrar;
use Codex\Providers\EnqueueProvider;
use Codex\Providers\SettingsProvider;
use InvalidArgumentException;
use Pimple\Container;
use Psr\Container\ContainerInterface;
use ReflectionFunction;
use stdClass;

use function array_key_exists;
use function array_key_last;
use function array_values;
use function get_class;

class ApplicationTest extends WPTestCase
{
	// phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
	public function tear_down(): void
	{
		unset($GLOBALS[Overture::class]);

		parent::tear_down();
	}

	public function testConfigService(): void
	{
		$app = new Application(
			new class () implements Extendable {
				public function getInstances(ContainerInterface $container): iterable
				{
					return [];
				}
			},
		);
		$app->setPluginFilePath(self::getFixturesPath('/plugin-name.php'));
		$app->boot();

		$container = $app->getContainer();
		/** @var Config $config */
		$config = $container->get('config');

		$this->assertSame('wp-test', $config->get('app.text_domain'));
		$this->assertSame('/dist', $config->get('app.assets_path'));
		$this->assertSame('https://example.org/dist', $config->get('app.assets_url'));
		$this->assertSame('wp_test_', $config->get('app.option_prefix'));
		$this->assertSame('wp_test_', $config->get('app.option_prefix'));
		$this->assertTrue($config->has('app.option_prefix'));
		$this->assertFalse($config->has('non-existent-key'));
		$this->assertTrue($config->isBlank('empty'));
		$this->assertTrue($config->isBlank('blank'));
	}

	public function testSettingsService(): void
	{
		$app = new Application(
			new class () implements Extendable {
				public function getInstances(ContainerInterface $container): iterable
				{
					return [];
				}
			},
		);
		$app->setPluginFilePath(self::getFixturesPath('/plugin-name.php'));
		$app->addServices([SettingsProvider::class]);
		$app->boot();

		$settings = $app->getContainer()->get('settings');

		$this->assertArrayNotHasKey('wp-test/plugin-foo', $settings); // Unsupported file extension, `.json`.
		$this->assertArrayNotHasKey('wp-test/plugin-name-1', $settings); // Settings empty.

		// wp-test/plugin-name-0
		$this->assertInstanceOf(SettingsRegistry::class, $settings['wp-test/plugin-name-0']);
		$this->assertTrue($settings['wp-test/plugin-name-0']->isRegistered());
		$this->assertSame('Hello, World!', get_option('wp_test_foo'));

		$registered = $settings['wp-test/plugin-name-0']->getRegistered();

		$this->assertTrue(array_key_exists('wp_test_foo', $registered));
		$this->assertInstanceOf(SettingRegistrar::class, $registered['wp_test_foo']);

		// wp-test/plugin-name-2
		$this->assertInstanceOf(SettingsRegistry::class, $settings['wp-test/plugin-name-2']);
		$this->assertTrue($settings['wp-test/plugin-name-2']->isRegistered());
		$this->assertSame(100, get_option('wp_test_bar'));

		$registered = $settings['wp-test/plugin-name-2']->getRegistered();

		$this->assertTrue(array_key_exists('wp_test_bar', $registered));
		$this->assertInstanceOf(SettingRegistrar::class, $registered['wp_test_bar']);

		$settings['wp-test/plugin-name-0']->deregister();

		// wp-test/plugin-name-0
		$this->assertFalse($settings['wp-test/plugin-name-0']->isRegistered());
		$this->assertFalse(get_option('wp_test_foo'));
		$this->assertEmpty($settings['wp-test/plugin-name-0']->getRegistered());

		// wp-test/plugin-name-2
		$this->assertInstanceOf(SettingsRegistry::class, $settings['wp-test/plugin-name-2']);
		$this->assertTrue($settings['wp-test/plugin-name-2']->isRegistered());
		$this->assertSame(100, get_option('wp_test_bar'));

		$registered = $settings['wp-test/plugin-name-2']->getRegistered();

		$this->assertTrue(array_key_exists('wp_test_bar', $registered));
		$this->assertInstanceOf(SettingRegistrar::class, $registered['wp_test_bar']);

		$settings['wp-test/plugin-name-2']->deregister();

		// wp-test/plugin-name-2
		$this->assertFalse($settings['wp-test/plugin-name-2']->isRegistered());
		$this->assertFalse(get_option('wp_test_bar'));
		$this->assertEmpty($settings['wp-test/plugin-name-2']->getRegistered());
	}

	public function testSettingsServiceAddOptionInvalidValue(): void
	{
		$app = new Application(
			new class () implements Extendable {
				public function getInstances(ContainerInterface $container): iterable
				{
					return [];
				}
			},
		);
		$app->setPluginFilePath(self::getFixturesPath('/plugin-name.php'));
		$app->addServices([SettingsProvider::class]);
		$app->boot();

		$this->assertSame('Hello, World!', get_option('wp_test_foo'));

		$this->expectException(InvalidArgumentException::class);

		add_option('wp_test_foo', '');
	}

	public function testSettingsServiceAddOptionDeregisteredInvalidValue(): void
	{
		$app = new Application(
			new class () implements Extendable {
				public function getInstances(ContainerInterface $container): iterable
				{
					return [];
				}
			},
		);
		$app->setPluginFilePath(self::getFixturesPath('/plugin-name.php'));
		$app->addServices([SettingsProvider::class]);
		$app->boot();

		$this->assertSame('Hello, World!', get_option('wp_test_foo'));

		$settings = $app->getContainer()->get('settings');
		$settings['wp-test/plugin-name-0']->deregister();

		$this->assertTrue(add_option('wp_test_foo', ''));
	}

	public function testSettingsServiceUpdateOptionInvalidValue(): void
	{
		$app = new Application(
			new class () implements Extendable {
				public function getInstances(ContainerInterface $container): iterable
				{
					return [];
				}
			},
		);
		$app->setPluginFilePath(self::getFixturesPath('/plugin-name.php'));
		$app->addServices([SettingsProvider::class]);
		$app->boot();

		$this->assertSame('Hello, World!', get_option('wp_test_foo'));
		$this->assertTrue(add_option('wp_test_foo', 'Hi!'));
		$this->assertSame('Hi!', get_option('wp_test_foo'));

		$this->expectException(InvalidArgumentException::class);

		update_option('wp_test_foo', '');
	}

	public function testSettingsServiceUpdateOptionDeregisteredInvalidValue(): void
	{
		$app = new Application(
			new class () implements Extendable {
				public function getInstances(ContainerInterface $container): iterable
				{
					return [];
				}
			},
		);
		$app->setPluginFilePath(self::getFixturesPath('/plugin-name.php'));
		$app->addServices([SettingsProvider::class]);
		$app->boot();

		$this->assertSame('Hello, World!', get_option('wp_test_foo'));
		$this->assertTrue(add_option('wp_test_foo', 'Hai!'));
		$this->assertSame('Hai!', get_option('wp_test_foo'));

		$settings = $app->getContainer()->get('settings');
		$settings['wp-test/plugin-name-0']->deregister();

		$this->assertTrue(update_option('wp_test_foo', ''));
	}

	public function testEnqueueService(): void
	{
		$app = new Application(
			new class () implements Extendable {
				public function getInstances(ContainerInterface $container): iterable
				{
					return [];
				}
			},
		);
		$app->setPluginFilePath(self::getFixturesPath('/plugin-name.php'));
		$app->addServices([EnqueueProvider::class]);
		$app->boot();

		self::assertInstanceOf(Enqueue::class, $app->getContainer()->get('enqueue'));
	}

	public function testActivatable(): void
	{
		$app = new Application(
			new class () implements Extendable, Activatable {
				public function getInstances(ContainerInterface $container): iterable
				{
					return [];
				}

				public function activate(ContainerInterface $container): void
				{
				}
			},
		);
		$app->setPluginFilePath(self::getFixturesPath('/plugin-name.php'));
		$app->boot();

		self::assertTrue(isset($GLOBALS['wp_filter']['activate_' . plugin_basename(self::getFixturesPath('/plugin-name.php'))]));
	}

	public function testDeactivatable(): void
	{
		$app = new Application(
			new class () implements Extendable, Deactivatable {
				public function getInstances(ContainerInterface $container): iterable
				{
					return [];
				}

				public function activate(ContainerInterface $container): void
				{
				}

				public function deactivate(ContainerInterface $container): void
				{
				}
			},
		);
		$app->setPluginFilePath(self::getFixturesPath('/plugin-name.php'));
		$app->boot();

		self::assertTrue(isset($GLOBALS['wp_filter']['deactivate_' . plugin_basename(self::getFixturesPath('/plugin-name.php'))]));
	}

	public function testBlocksRegisterHook(): void
	{
		$app = new Application(
			new class () implements Extendable {
				public function getInstances(ContainerInterface $container): iterable
				{
					return [];
				}

				public function activate(ContainerInterface $container): void
				{
				}

				public function deactivate(ContainerInterface $container): void
				{
				}
			},
		);
		$app->setPluginFilePath(self::getFixturesPath('/plugin-name.php'));
		$app->boot();

		/** @var Hook $hook */
		$hook = $app->getContainer()->get('hook');

		self::assertSame(10, $hook->hasAction('init', '@app.blocks.register'));

		$filters = array_values($GLOBALS['wp_filter']['init'][10]);
		$function = $filters[array_key_last($filters)]['function'];

		self::assertSame(Blocks::class, get_class($function[0]));
	}

	public function testServiceProvider(): void
	{
		$app = new Application(
			new class () implements Extendable {
				public function getInstances(ContainerInterface $container): iterable
				{
					return [];
				}

				public function activate(ContainerInterface $container): void
				{
				}

				public function deactivate(ContainerInterface $container): void
				{
				}
			},
		);
		$app->addServices([Orchestra::class, Concerto::class]);
		$app->setPluginFilePath(self::getFixturesPath('/plugin-name.php'));
		$app->boot();

		self::assertFalse($app->getContainer()->has('orchestra'));
		self::assertTrue($app->getContainer()->has('concerto'));
	}

	public function testHookableAndBootableService(): void
	{
		$app = new Application(
			new class () implements Extendable {
				public function getInstances(ContainerInterface $container): iterable
				{
					yield new Overture();
				}
			},
		);
		$app->setPluginFilePath(self::getFixturesPath('/plugin-name.php'));
		$app->boot();

		self::assertSame(123, has_action('wp_loaded', '__return_null'));
		self::assertSame(1, $GLOBALS[Overture::class]);
	}

	public function testEnqueue(): void
	{
		$app = new Application(
			new class () implements Extendable {
				public function getInstances(ContainerInterface $container): iterable
				{
					yield new Opera();
					yield new OperaPublic();
				}
			},
		);
		$app->addServices([EnqueueProvider::class]);
		$app->setPluginFilePath(self::getFixturesPath('/plugin-name.php'));
		$app->boot();

		// Admin.
		$closure = array_values($GLOBALS['wp_filter']['admin_enqueue_scripts'][12])[0]['function'];
		$class = (new ReflectionFunction($closure))->getClosureScopeClass();

		self::assertSame(Assets::class, $class->getName());

		// Public.
		$closure = array_values($GLOBALS['wp_filter']['wp_enqueue_scripts'][12])[0]['function'];
		$class = (new ReflectionFunction($closure))->getClosureScopeClass();

		self::assertSame(Assets::class, $class->getName());
	}

	public function testBoot(): void
	{
		$plugin = new class () implements Extendable, Bootable {
			public function getInstances(ContainerInterface $container): iterable
			{
				return [];
			}

			public function boot(): void
			{
				$GLOBALS[self::class] = 2;
			}
		};
		$app = new Application($plugin);
		$app->setPluginFilePath(self::getFixturesPath('/plugin-name.php'));
		$app->boot();

		self::assertSame(2, $GLOBALS[get_class($plugin)]);
	}

	public function testBootWithoutInterface(): void
	{
		$plugin = new class () implements Extendable {
			public function getInstances(ContainerInterface $container): iterable
			{
				return [];
			}

			public function boot(): void
			{
				$GLOBALS[self::class] = 3;
			}
		};
		$app = new Application($plugin);
		$app->setPluginFilePath(self::getFixturesPath('/plugin-name.php'));
		$app->boot();

		self::assertFalse(isset($GLOBALS[get_class($plugin)]));
	}
}

// phpcs:disable
class Orchestra
{
	private Container $container;

	public function __construct()
	{
		$this->container = new Container();
	}
	public function register(): void
	{
		$this->container['orchestra'] = new stdClass;
	}
}

class Concerto extends ServiceProvider
{
	public function register(): void
	{
		$this->container['concerto'] = new stdClass;
	}
}

class Overture implements Hookable, Bootable
{
	public function hook(Hook $hook): void
	{
		$hook->addAction('wp_loaded', '__return_null', 123);
	}

	public function boot(): void
	{
		$GLOBALS[self::class] = 1;
	}
}

class Opera implements HasAdminScripts
{
	public function getAdminScripts(string $adminPage): ?iterable
	{
		yield new Script('/admin.js');
	}
}

class OperaPublic implements HasPublicScripts
{
	public function getPublicScripts(): ?iterable
	{
		yield new Style('/public.js');
	}
}
