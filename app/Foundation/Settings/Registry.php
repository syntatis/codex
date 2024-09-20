<?php

declare(strict_types=1);

namespace Codex\Foundation\Settings;

use Codex\Contracts\Hookable;
use Codex\Foundation\Hooks\Hook;
use Codex\Foundation\Settings\Support\SettingRegistrar;
use InvalidArgumentException;
use Syntatis\Utils\Val;

use function count;

class Registry implements Hookable
{
	private Hook $hook;

	private string $prefix = '';

	/** @phpstan-var non-empty-string $settingGroup */
	private string $settingGroup;

	/** @var array<Setting> */
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

	public function hook(Hook $hook): void
	{
		$this->hook = $hook;
	}

	public function setPrefix(string $prefix): void
	{
		$this->prefix = $prefix;
	}

	public function addSettings(Setting ...$settings): void
	{
		$this->settings = [...$this->settings, ...$settings];
	}

	public function register(): void
	{
		foreach ($this->settings as $setting) {
			$registry = new SettingRegistrar($setting, $this->settingGroup);
			$registry->setPrefix($this->prefix);
			$registry->hook($this->hook);
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

	/** @return array<string,SettingRegistrar> */
	public function getRegistered(): array
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
