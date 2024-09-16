<?php

declare(strict_types=1);

namespace Codex\Contracts;

interface HasInlineScript
{
	/**
	 * Retrieve the position to add the inline script.
	 *
	 * @phpstan-return "before"|"after"
	 */
	public function getInlineScriptPosition(): string;

	/**
	 * Retrieve the script to be added as inline script.
	 */
	public function getInlineScript(): string;
}
