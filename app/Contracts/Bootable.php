<?php

declare(strict_types=1);

namespace Codex\Contracts;

interface Bootable
{
	public function boot(): void;
}
