<?php

declare(strict_types=1);

namespace Codex\Foundation\Assets;

use Codex\Contracts\Enqueueable;
use Codex\Contracts\HasAdminScripts;
use Codex\Contracts\HasPublicScripts;
use Codex\Contracts\Hookable;
use Codex\Foundation\Hooks\Hook;

use function is_iterable;

class Assets implements Hookable
{
	private Hook $hook;

	private Enqueue $enqueue;

	public function __construct(Enqueue $enqueue)
	{
		$this->enqueue = $enqueue;
	}

	public function hook(Hook $hook): void
	{
		$this->hook = $hook;
	}

	/** @param HasAdminScripts|HasPublicScripts $instance */
	public function enqueue($instance): void
	{
		if ($instance instanceof HasAdminScripts) {
			$this->hook->addAction(
				'admin_enqueue_scripts',
				function (string $admin) use ($instance): void {
					$assets = $instance->getAdminScripts($admin);

					if (! is_iterable($assets)) {
						return;
					}

					$this->enqueueScripts($assets);
				},
				12,
			);

			return;
		}

		$this->hook->addAction(
			'wp_enqueue_scripts',
			function () use ($instance): void {
				$assets = $instance->getPublicScripts();

				if (! is_iterable($assets)) {
					return;
				}

				$this->enqueueScripts($assets);
			},
			12,
		);
	}

	/** @param iterable<Enqueueable> $assets */
	private function enqueueScripts(iterable $assets): void
	{
		foreach ($assets as $asset) {
			if ($asset instanceof Script) {
				$this->enqueue->addScripts($asset);
			}

			if (! ($asset instanceof Style)) {
				continue;
			}

			$this->enqueue->addStyles($asset);
		}

		$this->enqueue->scripts();
		$this->enqueue->styles();
	}
}
