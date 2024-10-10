<?php

declare(strict_types=1);

namespace Codex\Core;

use Adbar\Dot;

use function count;
use function dot;
use function is_array;
use function is_string;
use function trim;

/** @internal This class should not be used directly, Developers should use the `Config` facade instead. */
final class Config
{
	/** @var Dot<string,mixed> */
	private Dot $dot;

	/** @param array<string,array<string,mixed>> $config */
	public function __construct(array $config)
	{
		$this->dot = dot($config);
	}

	/**
	 * Retrieve value of the given key from config.
	 *
	 * @param mixed|null $default
	 *
	 * @return mixed|null If the key does not exist, the default value will be returned.
	 */
	public function get(string $key, $default = null)
	{
		return $this->dot->get($key, $default);
	}

	/**
	 * Check if a given key exists on the config.
	 */
	public function has(string $key): bool
	{
		return $this->dot->has($key);
	}

	/**
	 * Check if a given key on the config is blank.
	 *
	 * Value is considered blank if it is a `null`, an empty string, an empty
	 * array, or a string with only whitespace.
	 */
	public function isBlank(string $key): bool
	{
		$value = $this->dot->get($key);

		if ($value === false || $value === null) {
			return true;
		}

		if (is_string($value) && trim($value) === '') {
			return true;
		}

		return is_array($value) && count($value) === 0;
	}
}
