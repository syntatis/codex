<?php

declare(strict_types=1);

namespace Codex\Abstracts;

use Codex\Foundation\Hooks\Hook;
use Pimple\Container;

abstract class ServiceProvider
{
	protected Container $container;

	protected Hook $hook;

	final public function __construct(Container $container, Hook $hook)
	{
		$this->container = $container;
		$this->hook = $hook;
	}

	/**
	 * Register the provider.
	 */
	abstract public function register(): void;
}
