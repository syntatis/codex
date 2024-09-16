<?php

declare(strict_types=1);

namespace Codex\Foundation\Assets;

use InvalidArgumentException;
use Syntatis\Utils\Val;

use function array_merge;
use function is_file;

use const DIRECTORY_SEPARATOR;

/**
 * A helper class providing an OOP interface to enqueue scripts and styles in WordPress.
 */
class Enqueue
{
	/** @var array<Script> */
	private array $scripts = [];

	/** @var array<Style> */
	private array $styles = [];

	private string $dirPath;

	private string $dirUrl;

	private string $domainName;

	private string $languagePath = '';

	private ?string $prefix = null;

	/**
	 * @param string $dirPath The path to the directory containing the scripts and styles files.
	 * @param string $dirUrl  The public URL to the directory containing the scripts and styles files.
	 *                        This URL will be used to enqueue the scripts and styles. Typically, it
	 *                        may be retrieved with the `plugin_dir_url` function or the
	 *                        `get_template_directory_uri` function.
	 * @phpstan-param non-empty-string $dirPath
	 * @phpstan-param non-empty-string $dirUrl
	 */
	public function __construct(string $dirPath, string $dirUrl)
	{
		$this->dirPath = untrailingslashit($dirPath);
		$this->dirUrl = untrailingslashit($dirUrl);
	}

	/**
	 * Add the prefix to uniquely identify the scripts and styles.
	 *
	 * By default, the class uses the basename of the script or style file as the handle.
	 * This can cause problems when multiple scripts or styles have the same basename,
	 * especially those from other plugins, themes, or WordPress core. You can use
	 * this method to add a prefix to the handle and make it unique.
	 *
	 * @param string $prefix The prefix to add to the handle.
	 *                       It is recommended to use kebab case for the prefix e.g. 'my-plugin'.
	 * @phpstan-param non-empty-string $prefix
	 */
	public function setPrefix(string $prefix): void
	{
		$this->prefix = $prefix;
	}

	/**
	 * Set the domain name and language path for script translations.
	 *
	 * This will be used to localize the scripts that have been added through the `addScript` method,
	 * with the `localized` option set to `true`.
	 *
	 * @param string $domainName   The text domain to use for the translations.
	 * @param string $languagePath The path to the language files.
	 * @phpstan-param non-empty-string $domainName
	 */
	public function setTranslations(string $domainName, string $languagePath = ''): void
	{
		$this->domainName = $domainName;
		$this->languagePath = trailingslashit($languagePath);
	}

	/**
	 * Add list of scripts to enqueue.
	 */
	public function addScripts(Script ...$scripts): void
	{
		foreach ($scripts as $script) {
			$handle = $script->getHandle();

			if (isset($this->scripts[$handle])) {
				throw new InvalidArgumentException('The script handle "' . $handle . '" is already in use.');
			}

			$this->scripts[$handle] = $script;
		}
	}

	/**
	 * Add list of styles to enqueue.
	 */
	public function addStyles(Style ...$styles): void
	{
		foreach ($styles as $style) {
			$handle = $style->getHandle();

			if (isset($this->styles[$handle])) {
				throw new InvalidArgumentException('The style handle "' . $handle . '" is already in use.');
			}

			$this->styles[$handle] = $style;
		}
	}

	/**
	 * Enqueue all the scripts that have been added through the `addScript` method.
	 */
	public function scripts(): void
	{
		foreach ($this->scripts as $script) {
			$version = $script->getVersion();
			$handle = $script->getHandle();
			$handle = ! Val::isBlank($this->prefix) ? $this->prefix . $handle : $handle;
			$manifest = $this->getManifest($script->getManifestPath());

			wp_enqueue_script(
				$handle,
				$this->dirUrl . $script->getFilePath(),
				array_merge($manifest['dependencies'], $script->getDependencies()),
				! Val::isBlank($version) ? $version : $manifest['version'],
				$script->isAtFooter(),
			);

			foreach ($script->getInlineScripts() as $inlineScript) {
				wp_add_inline_script(
					$handle,
					$inlineScript->getInlineScript(),
					$inlineScript->getInlineScriptPosition(),
				);
			}

			if (! $script->isTranslated() || Val::isBlank($this->domainName)) {
				continue;
			}

			wp_set_script_translations(
				$handle,
				$this->domainName,
				$this->languagePath,
			);
		}
	}

	/**
	 * Enqueue all the styles that have been added through the `addStyle` method.
	 */
	public function styles(): void
	{
		foreach ($this->styles as $style) {
			$version = $style->getVersion();
			$handle = $style->getHandle();
			$handle = ! Val::isBlank($this->prefix) ? $this->prefix . '-' . $handle : $handle;
			$manifest = $this->getManifest($style->getManifestPath());

			wp_enqueue_style(
				$handle,
				$this->dirUrl . $style->getFilePath(),
				$style->getDependencies(),
				! Val::isBlank($version) ? $version : $manifest['version'],
				$style->getMedia(),
			);
		}
	}

	/** @return array{dependencies:array<string>,version:string|null} */
	private function getManifest(string $fileName): array
	{
		$asset = [];
		$assetFile = wp_normalize_path($this->dirPath . DIRECTORY_SEPARATOR . $fileName);

		if (is_file($assetFile)) {
			$asset = include $assetFile;
		}

		$dependencies = $asset['dependencies'] ?? [];
		$version = $asset['version'] ?? null;

		return [
			'dependencies' => $dependencies,
			'version' => $version,
		];
	}
}
