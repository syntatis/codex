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
		$this->name = $setting->getName();
		$this->group = $group;
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

	public function hook(Hook $hook): void
	{
		$this->hook = $hook;
	}

	public function register(): void
	{
		$inputValidator = new InputValidator($this->name);
		$inputValidator->setConstraints($this->setting->getConstraints());

		$this->priority = $this->setting->getPriority();
		$this->callbacks['default_option'] = function ($default, $option, $passedDefault) {
			return $passedDefault ? $default : $this->setting->getDefault();
		};
		$this->callbacks['add_option'] = function ($optionName, $value) use ($inputValidator): void {
			if ($optionName !== $this->name) {
				return;
			}

			$inputValidator->validate($value);
		};
		$this->callbacks['update_option'] = function ($optionName, $oldValue, $newValue) use ($inputValidator): void {
			if ($optionName !== $this->name) {
				return;
			}

			$inputValidator->validate($newValue);
		};
		$this->callbacks['init'] = fn () => register_setting(
			$this->group,
			$this->name,
			$this->setting->getSettingArgs(),
		);

		$this->hook->addFilter(
			'default_option_' . $this->name,
			$this->callbacks['default_option'],
			$this->priority,
			3,
		);

		// Run before the option is added.
		$this->hook->addAction(
			'add_option',
			$this->callbacks['add_option'],
			$this->priority,
			2,
		);

		// Run before the option is updated.
		$this->hook->addAction(
			'update_option',
			$this->callbacks['update_option'],
			$this->priority,
			3,
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
		unregister_setting($this->group, $this->name);

		if (isset($this->callbacks['default_option'])) {
			$this->hook->removeAction(
				'default_option_' . $this->name,
				$this->callbacks['default_option'],
				$this->priority,
			);
		}

		if (isset($this->callbacks['add_option'])) {
			$this->hook->removeAction(
				'add_option',
				$this->callbacks['add_option'],
				$this->priority,
			);
		}

		if (isset($this->callbacks['update_option'])) {
			$this->hook->removeAction(
				'update_option',
				$this->callbacks['update_option'],
				$this->priority,
			);
		}

		if (isset($this->callbacks['init'])) {
			$this->hook->removeAction('admin_init', $this->callbacks['init'], $this->priority);
			$this->hook->removeAction('rest_api_init', $this->callbacks['init'], $this->priority);
		}

		if ($delete !== true) {
			return;
		}

		delete_option($this->name);
	}
}
