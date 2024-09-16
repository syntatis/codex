<?php

declare(strict_types=1);

namespace Codex\Foundation\Assets;

use Codex\Contracts\Enqueueable;
use Codex\Contracts\HasInlineScript;
use Codex\Foundation\Assets\Traits\WithFilePathDefiner;
use InvalidArgumentException;
use Syntatis\Utils\Str;
use Syntatis\Utils\Val;

use function pathinfo;

/**
 * Defines the JavaScript file to enqueue.
 */
class Script implements Enqueueable
{
	use WithFilePathDefiner;

	protected bool $isTranslated = false;
	protected bool $footer = false;

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

	/** @var array<HasInlineScript> */
	protected array $inlineScripts = [];

	/** @var array<string> */
	protected array $dependencies = [];

	protected string $filePath;

	protected string $manifestPath;

	protected string $handle;

	/**
	 * @param string $filePath The path to the script file, relative to the directory path set in the
	 *                         `Enqueue` class.
	 * @param string $handle   Optional. Name of the script. Should be unique. By default, when it is
	 *                         not set, it will try to determine the handle from the file name. If
	 *                         the file name is `script.ts`, the handle will be `script`. Keep
	 *                         in mind that handles generated from file names may not be
	 *                         unique. In such cases, it's better to pass the the
	 *                         argument in this parameter to set the handle.
	 * @phpstan-param non-empty-string $filePath
	 * @phpstan-param non-empty-string|null $handle
	 */
	public function __construct(string $filePath, ?string $handle = null)
	{
		if (! Str::startsWith($filePath, '/')) {
			throw new InvalidArgumentException('The file path must start with a leading slash.');
		}

		if (! Str::endsWith($filePath, '.js')) {
			throw new InvalidArgumentException('The file path must end with `.js`.');
		}

		$this->fileInfo = pathinfo($filePath);
		$this->filePath = $this->definePath('.js');
		$this->manifestPath = $this->definePath('.asset.php');
		$this->handle = Val::isBlank($handle) ? $this->defineHandle() : $handle;
	}

	/**
	 * If set to `true` it will load with the translation data related to the
	 * script, through Wordress native function `wp_set_script_translations`.
	 *
	 * @see https://make.wordpress.org/core/2018/11/09/new-javascript-i18n-support-in-wordpress/
	 */
	public function withTranslation(bool $translated = true): self
	{
		$self = clone $this;
		$self->isTranslated = $translated;

		return $self;
	}

	public function atFooter(bool $footer = true): self
	{
		$self = clone $this;
		$self->footer = $footer;

		return $self;
	}

	/** @phpstan-param non-empty-string $version */
	public function versionedAt(string $version): self
	{
		$self = clone $this;
		$self->version = $version;

		return $self;
	}

	public function withInlineScripts(HasInlineScript ...$inlineScripts): self
	{
		$self = clone $this;
		$self->inlineScripts = $inlineScripts;

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

	public function getManifestPath(): string
	{
		return $this->manifestPath;
	}

	/** @return array<HasInlineScript> */
	public function getInlineScripts(): array
	{
		return $this->inlineScripts;
	}

	/** @inheritDoc */
	public function getDependencies(): array
	{
		return $this->dependencies;
	}

	public function isTranslated(): bool
	{
		return $this->isTranslated;
	}

	public function isAtFooter(): bool
	{
		return $this->footer;
	}

	public function getVersion(): ?string
	{
		return $this->version;
	}
}
