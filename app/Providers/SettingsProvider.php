<?php

declare(strict_types=1);

namespace Codex\Providers;

use Codex\Abstracts\ServiceProvider;
use Codex\Contracts\Bootable;
use Codex\Core\Config;
use Codex\Foundation\Settings\Registry;
use Codex\Foundation\Settings\Setting;
use InvalidArgumentException;
use Pimple\Container;
use RecursiveDirectoryIterator;
use SplFileInfo;
use Syntatis\Utils\Val;

use function dirname;
use function is_dir;
use function is_string;

class SettingsProvider extends ServiceProvider implements Bootable
{
	public function register(): void
	{
		$this->container['app/setting_registries'] = static function (Container $container): array {
			/** @var Config $config */
			$config = $container['app/config'];
			$filePath = $container['app/plugin_file_path'] ?? '';
			$filePath = is_string($filePath) ? $filePath : '';

			if (Val::isBlank($filePath)) {
				throw new InvalidArgumentException('The plugin file path is required to register the settings.');
			}

			$settingsDir = wp_normalize_path(dirname($filePath) . '/inc/settings');

			if (! is_dir($settingsDir)) {
				throw new InvalidArgumentException('The settings directory does not exist.');
			}

			$appName = $config->get('app.name');
			$settingFiles = new RecursiveDirectoryIterator(
				$settingsDir,
				RecursiveDirectoryIterator::SKIP_DOTS,
			);

			/** @var array<string,Registry> $settingRegistries */
			$settingRegistries = [];

			foreach ($settingFiles as $settingFile) {
				if (
					! $settingFile instanceof SplFileInfo ||
					! $settingFile->isFile() ||
					$settingFile->getExtension() !== 'php'
				) {
					continue;
				}

				/** @var array<string,Setting> $register */
				$register = include $settingFile->getPathname();
				$settingGroup = $appName . '/' . $settingFile->getBasename('.php');

				if (Val::isBlank($register)) {
					continue;
				}

				/**
				 * Defines the registry to register and manage the plugin settings.
				 *
				 * The registry allows us to register the plugin options in the WordPress
				 * Setting API with their type, default, and other attributes.
				 */
				$registry = new Registry($settingGroup);
				$registry->addSettings(...$register);

				if (! $config->isBlank('app.option_prefix')) {
					/** @var string $prefix */
					$prefix = $config->get('app.option_prefix');
					$registry->setPrefix($prefix);
				}

				$settingRegistries[$settingGroup] = $registry;
			}

			return $settingRegistries;
		};
	}

	public function boot(): void
	{
		/**
		 * Register all the options added in the registry.
		 *
		 * @var array<string,Registry> $settings
		 */
		$settings = $this->container['app/setting_registries'];

		foreach ($settings as $setting) {
			$this->hook->addAction('admin_init', [$setting, 'register']);
			$this->hook->addAction('rest_api_init', [$setting, 'register']);
		}
	}
}
