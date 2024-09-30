<?php

declare(strict_types=1);

namespace Codex\Abstracts;

use Pimple\Container;

abstract class ServiceProvider
{
	protected Container $container;

	final public function __construct(Container $container)
	{
		$this->container = $container;
	}
}
