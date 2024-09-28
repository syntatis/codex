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
		parent::tear_down();

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
	}

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

		do_action('admin_init');

		$settings = App::settings();

		$this->assertArrayNotHasKey('wp-test/plugin-foo', $settings); // Unsupported file extension, `.json`.
		$this->assertArrayNotHasKey('wp-test/plugin-name-1', $settings); // Settings empty.

		// wp-test/plugin-name-0
		$this->assertInstanceOf(Registry::class, $settings['wp-test/plugin-name-0']);
		$this->assertTrue($settings['wp-test/plugin-name-0']->isRegistered());
		$this->assertTrue(array_key_exists('wp_test_foo', $settings['wp-test/plugin-name-0']->getRegisteredSettings()));
		$this->assertInstanceOf(SettingRegistrar::class, $settings['wp-test/plugin-name-0']->getRegisteredSettings()['wp_test_foo']);

		// wp-test/plugin-name-2
		$this->assertInstanceOf(Registry::class, $settings['wp-test/plugin-name-2']);
		$this->assertTrue($settings['wp-test/plugin-name-2']->isRegistered());

		$setting = App::settings('plugin-name-2');

		$this->assertInstanceOf(Registry::class, $setting);
		$this->assertSame('wp-test/plugin-name-2', $setting->getSettingGroup());
	}
}
