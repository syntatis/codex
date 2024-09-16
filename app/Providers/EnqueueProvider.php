<?php

declare(strict_types=1);

namespace Codex\Providers;

use Codex\Abstracts\ServiceProvider;
use Codex\Core\Config;
use Codex\Foundation\Assets\Enqueue;
use InvalidArgumentException;
use Pimple\Container;
use Syntatis\Utils\Val;

class EnqueueProvider extends ServiceProvider
{
	public function register(): void
	{
		$this->container['enqueue'] = $this->container->factory(static function (Container $container): Enqueue {
			/** @var Config $config */
			$config = $container['config'];

			if ($config->isBlank('app.assets_path') || $config->isBlank('app.assets_url')) {
				throw new InvalidArgumentException('The "assets_path" and "assets_url" config must not be empty.');
			}

			/** @phpstan-var non-empty-string $assetsPath */
			$assetsPath = $config->get('app.assets_path');

			/** @phpstan-var non-empty-string $assetsUrl */
			$assetsUrl = $config->get('app.assets_url');

			/** @var string $textDomain */
			$textDomain = $config->get('app.text_domain', '');

			/** @var string $prefix */
			$prefix = $config->get('app.assets_handle_prefix', '');

			/**
			 * Defines the configuration to enqueue the plugin assets.
			 */
			$enqueue = new Enqueue($assetsPath, $assetsUrl);

			if (! Val::isBlank($prefix)) {
				$enqueue->setPrefix($prefix);
			}

			if (! Val::isBlank($textDomain)) {
				$enqueue->setTranslations($textDomain);
			}

			return $enqueue;
		});
	}
}
