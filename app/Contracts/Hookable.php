<?php

declare(strict_types=1);

namespace Codex\Contracts;

use Codex\Foundation\Hooks\Hook;

interface Hookable
{
	/**
	 * Add WordPress hooks to run.
	 */
	public function hook(Hook $hook): void;
}
