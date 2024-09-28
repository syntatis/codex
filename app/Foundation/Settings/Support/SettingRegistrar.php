<?php

declare(strict_types=1);

namespace Codex\Foundation\Settings\Support;

use Codex\Contracts\Hookable;
use Codex\Foundation\Hooks\Hook;
use Codex\Foundation\Settings\Setting;

use function trim;

class SettingRegistrar implements Hookable
{
	private Hook $hook;

	private Setting $setting;

	private string $name;

	/** @phpstan-var non-empty-string */
	private string $group;

	/** @var array<string,callable> */
	private array $callbacks = [];

	private int $priority = 10;

	/** @phpstan-param non-empty-string $group */
	public function __construct(Setting $setting, string $group)
	{
		$this->setting = $setting;
		$this->group = $group;
		$this->name = $setting->getName();
		$this->priority = $setting->getPriority();
	}

	/**
	 * Set the option prefix. e.g. `codex_`.
	 */
	public function setPrefix(string $prefix = ''): void
	{
		$this->name = trim($prefix) . $this->name;
	}

	/**
	 * Retrieve the option name to register, which may contain the prefix if set.
	 */
	public function getName(): string
	{
		return $this->name;
	}

	public function getGroup(): string
	{
		return $this->group;
	}

	public function getSetting(): Setting
	{
		return $this->setting;
	}

	public function hook(Hook $hook): void
	{
		$this->hook = $hook;
	}

	public function register(): void
	{
		$this->callbacks['init'] = fn () => register_setting(
			$this->group,
			$this->name,
			$this->setting->getSettingArgs(),
		);

		$this->hook->addAction(
			'admin_init',
			$this->callbacks['init'],
			$this->priority,
		);

		$this->hook->addAction(
			'rest_api_init',
			$this->callbacks['init'],
			$this->priority,
		);
	}

	public function deregister(bool $delete = false): void
	{
		if (isset($this->callbacks['init'])) {
			$this->hook->removeAction('admin_init', $this->callbacks['init'], $this->priority);
			$this->hook->removeAction('rest_api_init', $this->callbacks['init'], $this->priority);

			$init = fn () => unregister_setting($this->group, $this->name);

			$this->hook->addAction('admin_init', $init, $this->priority);
			$this->hook->addAction('rest_api_init', $init, $this->priority);
		}

		if ($delete !== true) {
			return;
		}

		delete_option($this->name);
	}
}
