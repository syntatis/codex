<?php

declare(strict_types=1);

namespace Codex;

use Codex\Abstracts\Facade;
use Codex\Abstracts\ServiceProvider;
use Codex\Contracts\Activatable;
use Codex\Contracts\Bootable;
use Codex\Contracts\Deactivatable;
use Codex\Contracts\Extendable;
use Codex\Contracts\Hookable;
use Codex\Core\App;
use Codex\Core\Config;
use Codex\Foundation\Blocks;
use Codex\Foundation\Hooks\Hook;
use InvalidArgumentException;
use Pimple\Container as PimpleContainer;
use Pimple\Psr11\Container;
use Psr\Container\ContainerInterface;
use RecursiveDirectoryIterator;
use SplFileInfo;

use function dirname;
use function is_dir;
use function is_file;
use function is_string;
use function is_subclass_of;
use function method_exists;
use function trim;

/**
 * Orchastates the WordPress plugin lifecycle and define the required services.
 */
final class Plugin
{
	private Extendable $ext;

	private Hook $hook;

	private PimpleContainer $pimple;

	private ContainerInterface $container;

	/**
	 * @var array<string>
	 * @phpstan-var array<class-string>
	 */
	private array $services = [];

	/**
	 * The path to the plugin main file.
	 *
	 * It's optional. When provided, it will be used to register the activation
	 * and deactivation hooks.
	 */
	private string $pluginFilePath = '';

	public function __construct(Extendable $ext)
	{
		$this->ext = $ext;
		$this->hook = new Hook();
		$this->pimple = new PimpleContainer();
		$this->container = new Container($this->pimple);
	}

	public function setPluginFilePath(string $pluginFilePath): void
	{
		$this->pluginFilePath = $pluginFilePath;
	}

	/**
	 * Add service providers to the plugin.
	 *
	 * @param array<string> $services
	 * @phpstan-param array<class-string> $services
	 */
	public function addServices(array $services): void
	{
		$this->services = $services;
	}

	public function boot(): void
	{
		$this->registerCoreServices();
		$this->registerServices();

		Facade::setFacadeApplication($this->container);

		/**
		 * Register actions to run when the plugin is activated or deactivated.
		 *
		 * @see https://developer.wordpress.org/plugins/plugin-basics/activation-deactivation-hooks/
		 * @see https://developer.wordpress.org/reference/functions/register_activation_hook/
		 * @see https://developer.wordpress.org/reference/functions/register_deactivation_hook/
		 * @todo Register update hooks to run actions when the plugin is updated.
		 */
		if ($this->ext instanceof Activatable && is_file($this->pluginFilePath)) {
			$ext = $this->ext;
			register_activation_hook(
				$this->pluginFilePath,
				fn () => $ext->activate($this->container),
			);
		}

		if ($this->ext instanceof Deactivatable && is_file($this->pluginFilePath)) {
			$ext = $this->ext;
			register_deactivation_hook(
				$this->pluginFilePath,
				fn () => $ext->deactivate($this->container),
			);
		}

		/** @var Config $config */
		$config = $this->container->get('config');

		/**
		 * Load the plugin text domain for translation.
		 *
		 * @see https://developer.wordpress.org/reference/functions/load_plugin_textdomain/
		 */
		if (! $config->isBlank('app.text_domain')) {
			/** @var string $textDomain */
			$textDomain = $config->get('app.text_domain');

			load_plugin_textdomain($textDomain, false, dirname($this->pluginFilePath) . '/inc/languages/');
		}

		/**
		 * Register the blocks found in the blocks directory.
		 */
		if (! $config->isBlank('app.blocks_path')) {
			/** @var string $blocksPath */
			$blocksPath = $config->get('app.blocks_path');
			$blocksPath = wp_normalize_path($blocksPath);

			if (is_dir($blocksPath)) {
				$blocks = new Blocks($blocksPath);

				/**
				 * Register the blocks found in the specificed blocks directory.
				 */
				$this->hook->addAction('init', [$blocks, 'register'], 10, 1, ['id' => 'blocks-register']);
			}
		}

		$this->bootInstances();

		if (! ($this->ext instanceof Bootable)) {
			return;
		}

		$this->ext->boot();
	}

	public function getContainer(): ContainerInterface
	{
		return $this->container;
	}

	private function registerServices(): void
	{
		$instances = [];

		foreach ($this->services as $key => $service) {
			if (! is_subclass_of($service, ServiceProvider::class)) {
				continue;
			}

			$service = new $service($this->pimple, $this->hook);

			if (method_exists($service, 'register')) {
				$service->register();
			}

			$instances[$key] = $service;
		}

		// We need to hook and boot the instances after all of services are registered.
		foreach ($instances as $instance) {
			if ($instance instanceof Hookable) {
				$instance->hook($this->hook);
			}

			if (! ($instance instanceof Bootable)) {
				continue;
			}

			$instance->boot();
		}
	}

	private function bootInstances(): void
	{
		foreach ($this->ext->getInstances($this->getContainer()) as $instance) {
			if ($instance instanceof Hookable) {
				$instance->hook($this->hook);
			}

			if (! ($instance instanceof Bootable)) {
				continue;
			}

			$instance->boot();
		}
	}

	private function registerCoreServices(): void
	{
		$this->pimple['hook'] = $this->hook;
		$this->pimple['plugin_file_path'] = $this->pluginFilePath;
		$this->pimple['config'] = static function (PimpleContainer $container): Config {
			$config = [];

			/** @var string $pluginFilePath */
			$pluginFilePath = $container['plugin_file_path'] ?? '';
			$configPath = wp_normalize_path(dirname($pluginFilePath) . '/inc/config');

			if (is_dir($configPath)) {
				$iterator = new RecursiveDirectoryIterator($configPath, RecursiveDirectoryIterator::SKIP_DOTS);

				foreach ($iterator as $configFile) {
					if (
						! ($configFile instanceof SplFileInfo) ||
						$configFile->getExtension() !== 'php'
					) {
						continue;
					}

					$config[$configFile->getBasename('.php')] = include $configFile->getRealPath();
				}
			}

			if (
				! isset($config['app']['name']) ||
				! is_string($config['app']['name']) ||
				trim($config['app']['name']) === ''
			) {
				throw new InvalidArgumentException('The app "name" is required and cannot be empty.');
			}

			return new Config($config);
		};
		$this->pimple['app'] = static function (PimpleContainer $container): App {
			/** @var Config $config */
			$config = $container['config'];
			$name = $config->get('app.name');

			return new App($name, $config);
		};
	}
}
