<?php

declare(strict_types=1);

namespace Codex\Foundation\Assets\Traits;

use Syntatis\Utils\Str;

use function sprintf;
use function str_replace;

use const DIRECTORY_SEPARATOR;

trait WithFilePathDefiner
{
	/** @var array{dirname:string,basename:string,extension?:string,filename:string} */
	protected array $fileInfo;

	private function defineHandle(): string
	{
		$fileName = str_replace('.', '-', $this->fileInfo['filename']);
		$dirName = $this->fileInfo['dirname'];

		if ($dirName === '/') {
			return Str::toKebabCase($fileName);
		}

		return Str::toKebabCase(
			sprintf(
				'%s-%s',
				str_replace(['/', '.'], '-', $dirName),
				$fileName,
			),
		);
	}

	/** @phpstan-param non-empty-string $extension */
	private function definePath(string $extension): string
	{
		$dirName = $this->fileInfo['dirname'];
		$fileName = $this->fileInfo['filename'];
		$filePath = DIRECTORY_SEPARATOR . $fileName . $extension;

		if ($this->fileInfo['dirname'] === '/') {
			return wp_normalize_path($filePath);
		}

		return wp_normalize_path($dirName . $filePath);
	}
}
