<?php

declare(strict_types=1);

namespace Codex\Tests\Facades;

use Codex\Contracts\Extendable;
use Codex\Facades\App;
use Codex\Facades\Config;
use Codex\Plugin;
use Codex\Tests\WPTestCase;
use Psr\Container\ContainerInterface;

class ConfigTest extends WPTestCase
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

	public function testConfig(): void
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
