<?php

declare(strict_types=1);

namespace Codex\Foundation\Assets;

use Codex\Contracts\Enqueueable;
use Codex\Foundation\Assets\Traits\WithFilePathDefiner;
use InvalidArgumentException;
use Syntatis\Utils\Str;
use Syntatis\Utils\Val;

use function pathinfo;

/**
 * Defines the stylesheet to enqueue.
 */
class Style implements Enqueueable
{
	use WithFilePathDefiner;

	/**
	 * The version of the script.
	 *
	 * If it is set, it will be appended to the script URL as a query string,
	 * and will override the version provided from the `*.asset.php` file
	 * generated from `@wordpress/scripts`.
	 *
	 * @phpstan-var non-empty-string|null
	 */
	protected ?string $version = null;

	/** @var array<string> */
	protected array $dependencies = [];

	protected string $filePath;

	protected string $manifestPath;

	protected string $handle;

	/** @phpstan-var non-empty-string */
	protected string $media = 'all';

	/**
	 * @param string $filePath The path to the script file, relative to the directory path set in the
	 *                         `Enqueue` class.
	 * @param string $handle   Optional. Name of the script. Should be unique. By default, when it's not set,
	 *                         it will be derived from the file name. If the file name is `style.scss`,
	 *                         the handle will be `style`. Keep in mind that handles generated from
	 *                         file names may not be unique. In such cases, it's better to set
	 *                         the handle manually.
	 * @phpstan-param non-empty-string $filePath
	 * @phpstan-param non-empty-string|null $handle
	 */
	public function __construct(string $filePath, ?string $handle = null)
	{
		if (! Str::startsWith($filePath, '/')) {
			throw new InvalidArgumentException('The file path must start with a leading slash.');
		}

		if (! Str::endsWith($filePath, '.css')) {
			throw new InvalidArgumentException('The file path must end with `.css`.');
		}

		$this->fileInfo = pathinfo($filePath);
		$this->filePath = $this->definePath('.css');
		$this->manifestPath = $this->definePath('.asset.php');
		$this->handle = Val::isBlank($handle) ? $this->defineHandle() : $handle;
	}

	/** @phpstan-param non-empty-string $media */
	public function forMedia(string $media): self
	{
		$self = clone $this;
		$self->media = $media;

		return $self;
	}

	/** @phpstan-param non-empty-string $version */
	public function versionedAt(string $version): self
	{
		$self = clone $this;
		$self->version = $version;

		return $self;
	}

	/** @phpstan-param non-empty-string ...$dependencies */
	public function dependsOn(string ...$dependencies): self
	{
		$self = clone $this;
		$self->dependencies = $dependencies;

		return $self;
	}

	public function getHandle(): string
	{
		return $this->handle;
	}

	public function getFilePath(): string
	{
		return $this->filePath;
	}

	public function getVersion(): ?string
	{
		return $this->version;
	}

	/** @inheritDoc */
	public function getDependencies(): array
	{
		return $this->dependencies;
	}

	/** @phpstan-return non-empty-string $media */
	public function getMedia(): string
	{
		return $this->media;
	}

	public function getManifestPath(): string
	{
		return $this->manifestPath;
	}
}
