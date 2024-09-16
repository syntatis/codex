<?php

declare(strict_types=1);

namespace Codex\Contracts;

interface HasAdminScripts
{
	/**
	 * Retrieve the scripts to enqueue in the admin area.
	 *
	 * @return iterable<Enqueueable>|null
	 */
	public function getAdminScripts(string $adminPage): ?iterable;
}
