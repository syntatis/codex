<?php

declare(strict_types=1);

namespace Codex\Core;

use Codex\Foundation\Settings\Registry;
use Syntatis\Utils\Val;

final class App
{
	private string $name;

	/** @var array<string,Registry> */
	private array $settingRegistries = [];

	/** @param array<string,Registry> $settingRegistries */
	public function __construct(string $name, array $settingRegistries = [])
	{
		$this->name = $name;
		$this->settingRegistries = $settingRegistries;
	}

	public function name(): string
	{
		return $this->name;
	}

	/** @return array<string,Registry>|Registry|null */
	public function settings(?string $group = null)
	{
		if (! Val::isBlank($group)) {
			return $this->settingRegistries[$group] ?? null;
		}

		return $this->settingRegistries;
	}
}
