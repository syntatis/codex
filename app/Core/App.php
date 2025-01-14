<?php

declare(strict_types=1);

namespace Codex\Core;

use function ltrim;
use function trim;

/**
 * @phpstan-type Props = array{
 * 		plugin_file_path?: string,
 * 		plugin_dir_path?: string,
 * }
 *
 * @internal This class should not be used directly, Developers should use the `App` facade instead.
 */
final class App
{
	/** @phptan-var non-empty-string */
	private string $name;

	/**
	 * @var array<string,mixed>
	 * @phpstan-var Props
	 */
	private array $props = [];

	/**
	 * @param array<string,mixed> $props Misc. properties to be set on the app.
	 * @phpstan-param non-empty-string $name
	 * @phpstan-param Props $props
	 *
	 * @internal description
	 */
	public function __construct(string $name, array $props = [])
	{
		$this->name = $name;
		$this->props = $props;
	}

	/** @phpstan-return non-empty-string */
	public function name(): string
	{
		return $this->name;
	}

	/**
	 * Retrieve the path to a file or directory within the app.
	 *
	 * @param string $path The absolute path to a file or directory within the
	 *                     plugin, added with leading slash e.g. `/dist`.
	 *
	 * @return string The absolute directory path to the file or directory, withtout the trailingslash
	 *                e.g. `/wp-content/plugins/plugin-name/dist`.
	 */
	public function dir(string $path = ''): string
	{
		$dir = trim(ltrim($path, '.'), '/');
		$dirBase = $this->props['plugin_dir_path'] ?? '';

		if ($dir === '') {
			return $dirBase;
		}

		return untrailingslashit(wp_normalize_path($dirBase . '/' . $dir));
	}

	/**
	 * Retrieve the public URL to a file or directory within the plugin.
	 *
	 * @param string $path The path to a file or directory within the plugin,
	 *                     added with leading slash e.g. `/dist`.
	 *
	 * @return string The absolute URL of the provided path, returned without
	 *                the trailingslash e.g. `https://example.com/wp-content/plugins/plugin-name/dist`.
	 */
	public function url(string $path = ''): string
	{
		$fileBase = $this->props['plugin_file_path'] ?? '';
		$url = plugins_url('', $fileBase);
		$path = trim(ltrim($path, '.'), '/');

		if (trim($path) !== '') {
			$url .= '/' . $path;
		}

		return untrailingslashit($url);
	}
}
