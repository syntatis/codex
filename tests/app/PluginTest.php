<?php

declare(strict_types=1);

namespace Codex\Tests;

use Codex\Abstracts\ServiceProvider;
use Codex\Contracts\Activatable;
use Codex\Contracts\Bootable;
use Codex\Contracts\Deactivatable;
use Codex\Contracts\Extendable;
use Codex\Contracts\Hookable;
use Codex\Core\Config;
use Codex\Facades\App;
use Codex\Foundation\Blocks;
use Codex\Foundation\Hooks\Hook;
use Codex\Foundation\Settings\Registry as SettingsRegistry;
use Codex\Foundation\Settings\Support\SettingRegistrar;
use Codex\Plugin;
use Codex\Providers\SettingsProvider;
use Pimple\Container;
use Psr\Container\ContainerInterface;
use stdClass;

use function array_key_exists;
use function array_key_last;
use function array_values;
use function get_class;

class PluginTest extends WPTestCase
{
	// phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
	public function set_up(): void
	{
		parent::set_up();

		remove_action('admin_init', '_maybe_update_core');
		remove_action('admin_init', '_maybe_update_plugins');
		remove_action('admin_init', '_maybe_update_themes');
		remove_action('admin_init', '_wp_check_for_scheduled_split_terms');
		remove_action('admin_init', '_wp_check_for_scheduled_update_comment_type');
		remove_action('admin_init', 'default_password_nag_handler');
		remove_action('admin_init', 'handle_legacy_widget_preview_iframe', 20);
		remove_action('admin_init', 'register_admin_color_schemes');
		remove_action('admin_init', 'send_frame_options_header');
		remove_action('admin_init', 'wp_admin_headers');
		remove_action('admin_init', 'wp_schedule_update_network_counts');
		remove_action('admin_init', 'wp_schedule_update_user_counts');
		remove_action('admin_init', ['WP_Privacy_Policy_Content', 'add_suggested_content'], 1);
		remove_action('admin_init', ['WP_Privacy_Policy_Content', 'text_change_check'], 100);
	}

	// phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
	public function tear_down(): void
	{
		unset($GLOBALS[Overture::class]);

		App::clearResolvedInstances();

		add_action('admin_init', '_wp_check_for_scheduled_split_terms');
		add_action('admin_init', '_wp_check_for_scheduled_update_comment_type');
		add_action('admin_init', 'default_password_nag_handler');
		add_action('admin_init', 'handle_legacy_widget_preview_iframe', 20);
		add_action('admin_init', 'register_admin_color_schemes');
		add_action('admin_init', 'send_frame_options_header');
		add_action('admin_init', 'wp_admin_headers');
		add_action('admin_init', 'wp_schedule_update_network_counts');
		add_action('admin_init', 'wp_schedule_update_user_counts');
		add_action('admin_init', ['WP_Privacy_Policy_Content', 'add_suggested_content'], 1);
		add_action('admin_init', ['WP_Privacy_Policy_Content', 'text_change_check'], 100);

		parent::tear_down();
	}

	public function testConfigService(): void
	{
		$app = new Plugin(
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
		$config = $container->get('app/config');

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
		// $this->markAsRisky('Does not test with "admin_init" hook, as it may lead to unexpected warning.');

		$app = new Plugin(
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

		$settingRegistries = $app->getContainer()->get('app/setting_registries');

		do_action('admin_init');

		$this->assertArrayNotHasKey('wp-test/plugin-foo', $settingRegistries); // Unsupported file extension, `.json`.
		$this->assertArrayNotHasKey('wp-test/plugin-name-1', $settingRegistries); // Settings empty.

		// wp-test/plugin-name-0
		$this->assertInstanceOf(SettingsRegistry::class, $settingRegistries['wp-test/plugin-name-0']);
		$this->assertTrue($settingRegistries['wp-test/plugin-name-0']->isRegistered());
		$this->assertSame('Hello, World!', get_option('wp_test_foo'));

		$registered = $settingRegistries['wp-test/plugin-name-0']->getRegisteredSettings();

		$this->assertTrue(array_key_exists('wp_test_foo', $registered));
		$this->assertInstanceOf(SettingRegistrar::class, $registered['wp_test_foo']);

		// wp-test/plugin-name-2
		$this->assertInstanceOf(SettingsRegistry::class, $settingRegistries['wp-test/plugin-name-2']);
		$this->assertTrue($settingRegistries['wp-test/plugin-name-2']->isRegistered());
		$this->assertSame(100, get_option('wp_test_bar'));

		$registered = $settingRegistries['wp-test/plugin-name-2']->getRegisteredSettings();

		$this->assertTrue(array_key_exists('wp_test_bar', $registered));
		$this->assertInstanceOf(SettingRegistrar::class, $registered['wp_test_bar']);

		$settingRegistries['wp-test/plugin-name-0']->deregister();

		// wp-test/plugin-name-0
		$this->assertFalse($settingRegistries['wp-test/plugin-name-0']->isRegistered());
		$this->assertFalse(get_option('wp_test_foo'));
		$this->assertEmpty($settingRegistries['wp-test/plugin-name-0']->getRegisteredSettings());

		// wp-test/plugin-name-2
		$this->assertInstanceOf(SettingsRegistry::class, $settingRegistries['wp-test/plugin-name-2']);
		$this->assertTrue($settingRegistries['wp-test/plugin-name-2']->isRegistered());
		$this->assertSame(100, get_option('wp_test_bar'));

		$registered = $settingRegistries['wp-test/plugin-name-2']->getRegisteredSettings();

		$this->assertTrue(array_key_exists('wp_test_bar', $registered));
		$this->assertInstanceOf(SettingRegistrar::class, $registered['wp_test_bar']);

		$settingRegistries['wp-test/plugin-name-2']->deregister();

		// wp-test/plugin-name-2
		$this->assertFalse($settingRegistries['wp-test/plugin-name-2']->isRegistered());
		$this->assertFalse(get_option('wp_test_bar'));
		$this->assertEmpty($settingRegistries['wp-test/plugin-name-2']->getRegisteredSettings());
	}

	public function testSettingsServiceAddOptionDeregisteredInvalidValue(): void
	{
		$app = new Plugin(
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

		do_action('admin_init');

		$this->assertSame('Hello, World!', get_option('wp_test_foo'));

		$settingRegistries = $app->getContainer()->get('app/setting_registries');
		$settingRegistries['wp-test/plugin-name-0']->deregister();

		$this->assertTrue(add_option('wp_test_foo', ''));
	}

	public function testSettingsServiceUpdateOptionDeregisteredInvalidValue(): void
	{
		$app = new Plugin(
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

		do_action('admin_init');

		$this->assertSame('Hello, World!', get_option('wp_test_foo'));
		$this->assertTrue(add_option('wp_test_foo', 'Hai!'));
		$this->assertSame('Hai!', get_option('wp_test_foo'));

		$settingRegistries = $app->getContainer()->get('app/setting_registries');
		$settingRegistries['wp-test/plugin-name-0']->deregister();

		$this->assertTrue(update_option('wp_test_foo', ''));
	}

	public function testActivatable(): void
	{
		$app = new Plugin(
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
		$app = new Plugin(
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
		$app = new Plugin(
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
		$hook = $app->getContainer()->get('app/hook');

		self::assertSame(10, $hook->hasAction('init', '@app.blocks.register'));

		$filters = array_values($GLOBALS['wp_filter']['init'][10]);
		$function = $filters[array_key_last($filters)]['function'];

		self::assertSame(Blocks::class, get_class($function[0]));
	}

	public function testServiceProvider(): void
	{
		$app = new Plugin(
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
		$app = new Plugin(
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
		$app = new Plugin($plugin);
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
		$app = new Plugin($plugin);
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
