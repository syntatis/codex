<?php

declare(strict_types=1);

namespace Codex;

use Codex\Abstracts\Facade;
use Codex\Abstracts\ServiceProvider;
use Codex\Contracts\Activatable;
use Codex\Contracts\Bootable;
use Codex\Contracts\Deactivatable;
use Codex\Contracts\Enqueueable;
use Codex\Contracts\Extendable;
use Codex\Contracts\HasAdminScripts;
use Codex\Contracts\HasPublicScripts;
use Codex\Contracts\Hookable;
use Codex\Core\App;
use Codex\Core\Config;
use Codex\Foundation\Assets\Enqueue;
use Codex\Foundation\Assets\Script;
use Codex\Foundation\Assets\Style;
use Codex\Foundation\Blocks;
use Codex\Foundation\Hooks\Hook;
use Codex\Foundation\Settings\Registry;
use InvalidArgumentException;
use Pimple\Container as PimpleContainer;
use Pimple\Psr11\Container;
use Psr\Container\ContainerInterface;
use RecursiveDirectoryIterator;
use SplFileInfo;
use Syntatis\Utils\Val;

use function dirname;
use function is_dir;
use function is_file;
use function is_iterable;
use function is_string;
use function is_subclass_of;

/**
 * Orchastates the WordPress application lifecycle and define the services.
 */
final class Application
{
	private Extendable $app;

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

	public function __construct(Extendable $app)
	{
		$this->app = $app;
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
		if ($this->app instanceof Activatable && is_file($this->pluginFilePath)) {
			$app = $this->app;
			register_activation_hook(
				$this->pluginFilePath,
				fn () => $app->activate($this->container),
			);
		}

		if ($this->app instanceof Deactivatable && is_file($this->pluginFilePath)) {
			$app = $this->app;
			register_deactivation_hook(
				$this->pluginFilePath,
				fn () => $app->deactivate($this->container),
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
				$this->hook->addAction('init', [$blocks, 'register'], 10, 1, ['id' => 'app.blocks.register']);
			}
		}

		$this->bootInstances();

		if (! ($this->app instanceof Bootable)) {
			return;
		}

		$this->app->boot();
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

			/** @var ServiceProvider $service */
			$service = new $service($this->pimple, $this->hook);
			$service->register();
			$instances[$key] = $service;
		}

		foreach ($instances as $instance) {
			if (! ($instance instanceof Bootable)) {
				continue;
			}

			$instance->boot();
		}
	}

	private function bootInstances(): void
	{
		foreach ($this->app->getInstances($this->getContainer()) as $instance) {
			if ($instance instanceof Hookable) {
				$instance->hook($this->hook);
			}

			if ($this->container->has('enqueue')) {
				/** @var Enqueue $enqueue */
				$enqueue = $this->container->get('enqueue');

				$this->bootEnqueueables($instance, $enqueue);
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
		$this->pimple['config'] = function (): Config {
			$config = [];
			$configDir = dirname($this->pluginFilePath) . '/inc/config';

			if (is_dir($configDir)) {
				$configPath = wp_normalize_path(dirname($this->pluginFilePath) . '/inc/config');
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

			if (! isset($config['app']['name']) || Val::isBlank($config['app']['name'])) {
				throw new InvalidArgumentException('The app "name" is required and cannot be empty.');
			}

			return new Config($config);
		};

		$this->pimple['app.plugin_file_path'] = $this->pluginFilePath;
		$this->pimple['app'] = static function (PimpleContainer $container): App {
			/** @var Config $config */
			$config = $container['config'];

			/** @var array<string,Registry> $settings */
			$settings = $container['settings'] ?? [];

			$name = $config->get('app.name');

			if (! is_string($name) || Val::isBlank($name)) {
				throw new InvalidArgumentException('The app "name" is required and cannot be empty.');
			}

			return new App($name, $settings);
		};
	}

	private function bootEnqueueables(object $instance, Enqueue $enqueue): void
	{
		if ($instance instanceof HasAdminScripts) {
			$this->hook->addAction(
				'admin_enqueue_scripts',
				function (string $admin) use ($instance, $enqueue): void {
					$scripts = $instance->getAdminScripts($admin);

					if (! is_iterable($scripts)) {
						return;
					}

					$this->enqueueScripts($scripts, $enqueue);
				},
				12,
			);
		}

		if (! ($instance instanceof HasPublicScripts)) {
			return;
		}

		$this->hook->addAction(
			'wp_enqueue_scripts',
			function () use ($instance, $enqueue): void {
				$scripts = $instance->getPublicScripts();

				if (! is_iterable($scripts)) {
					return;
				}

				$this->enqueueScripts($scripts, $enqueue);
			},
			12,
		);
	}

	/** @param iterable<Enqueueable> $assets */
	private function enqueueScripts(iterable $assets, Enqueue $enqueue): void
	{
		foreach ($assets as $asset) {
			if ($asset instanceof Script) {
				$enqueue->addScripts($asset);
			}

			if (! ($asset instanceof Style)) {
				continue;
			}

			$enqueue->addStyles($asset);
		}

		$enqueue->scripts();
		$enqueue->styles();
	}
}
