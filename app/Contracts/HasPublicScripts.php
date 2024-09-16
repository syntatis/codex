<?php

declare(strict_types=1);

namespace Codex\Contracts;

interface HasPublicScripts
{
	/**
	 * Retrieve the scripts to enqueue in the public area.
	 *
	 * @return iterable<Enqueueable>|null
	 */
	public function getPublicScripts(): ?iterable;
}
