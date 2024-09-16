<?php

declare(strict_types=1);

namespace Codex\Contracts;

use Psr\Container\ContainerInterface;

interface Deactivatable
{
	/**
	 * Run actions when the plugin is deactivated.
	 */
	public function deactivate(ContainerInterface $container): void;
}
