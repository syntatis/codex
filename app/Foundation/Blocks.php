<?php

declare(strict_types=1);

namespace Codex\Foundation;

use InvalidArgumentException;
use RecursiveDirectoryIterator;
use SplFileInfo;

use function file_exists;
use function is_dir;

class Blocks
{
	private string $blocksPath;

	/** @param string $blocksPath The path where all the blocks are located. */
	public function __construct(string $blocksPath)
	{
		$this->blocksPath = $blocksPath;

		if (! is_dir($this->blocksPath)) {
			throw new InvalidArgumentException('The blocks path is not a directory.');
		}
	}

	public function register(): void
	{
		$blocks = new RecursiveDirectoryIterator(
			$this->blocksPath,
			RecursiveDirectoryIterator::SKIP_DOTS,
		);

		foreach ($blocks as $block) {
			if (! ($block instanceof SplFileInfo) || ! $block->isDir()) {
				continue;
			}

			if (! file_exists($block->getRealPath() . '/block.json')) {
				continue;
			}

			register_block_type($block->getRealPath());
		}
	}
}
