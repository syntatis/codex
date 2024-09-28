<?php

declare(strict_types=1);

namespace Codex\Foundation\Settings;

use Codex\Foundation\Settings\Support\SettingRegistrar;
use InvalidArgumentException;
use Syntatis\Utils\Val;

use function count;

class Registry
{
	private string $prefix = '';

	/** @phpstan-var non-empty-string $settingGroup */
	private string $settingGroup;

	/** @var array<string,Setting> */
	private array $settings = [];

	/** @var array<string,SettingRegistrar> */
	private array $registered = [];

	public function __construct(string $settingGroup)
	{
		if (Val::isBlank($settingGroup)) {
			throw new InvalidArgumentException('The setting group cannot be empty.');
		}

		$this->settingGroup = $settingGroup;
	}

	public function setPrefix(string $prefix): void
	{
		$this->prefix = $prefix;
	}

	public function addSettings(Setting ...$settings): void
	{
		foreach ($settings as $key => $setting) {
			$this->settings[$setting->getName()] = $setting;
		}
	}

	public function register(): void
	{
		foreach ($this->settings as $setting) {
			$registry = new SettingRegistrar($setting, $this->settingGroup);
			$registry->setPrefix($this->prefix);
			$registry->register();

			$this->registered[$registry->getName()] = $registry;
		}
	}

	public function isRegistered(): bool
	{
		return count($this->registered) === count($this->settings);
	}

	public function getSettingGroup(): string
	{
		return $this->settingGroup;
	}

	/** @return array<string,Setting> */
	public function getSettings(): array
	{
		return $this->settings;
	}

	/** @return array<string,SettingRegistrar> */
	public function getRegisteredSettings(): array
	{
		return $this->registered;
	}

	/**
	 * Remove options from the registry.
	 *
	 * @param bool $delete Whether to delete the options from the database.
	 */
	public function deregister(bool $delete = false): void
	{
		foreach ($this->registered as $key => $registry) {
			$registry->deregister($delete);
			unset($this->registered[$key]);
		}
	}
}
