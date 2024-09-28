<?php

declare(strict_types=1);

namespace Codex\Foundation\Settings;

use InvalidArgumentException;
use Syntatis\Utils\Val;

use function array_merge;

/**
 * @phpstan-type ValueDefault bool|float|int|string|array<array-key, bool|float|int|string|array<array-key, mixed>>|null
 * @phpstan-type ValueFormat 'date-time'|'uri'|'email'|'ip'|'uuid'|'hex-color'
 * @phpstan-type ValueType 'string'|'boolean'|'integer'|'number'|'array'|'object'
 * @phpstan-type APISchemaProperties array<string, array{type: ValueType, default?: array<mixed>|bool|float|int|string}>
 * @phpstan-type APISchema array{properties?: APISchemaProperties, items?: array{type?: ValueType, format?: ValueFormat}}
 * @phpstan-type APIConfig array{name?: string, schema: APISchema}
 * @phpstan-type SettingVars array{description?: string, show_in_rest?: APIConfig|bool}
 * @phpstan-type SettingArgs array{type: ValueType, default: ValueDefault, description?: string, show_in_rest?: APIConfig|bool}
 */
class Setting
{
	protected string $name;

	/** @phpstan-var ValueType */
	protected string $type = 'string';

	/** @phpstan-var ValueDefault */
	protected $default = null;

	/**
	 * The priority determines the order in which the `option_` related hooks
	 * are executed.
	 */
	protected int $priority = 73;

	/**
	 * @var array<string, mixed>
	 * @phpstan-var SettingVars
	 */
	protected array $settingVars = ['show_in_rest' => true];

	/** @phpstan-param ValueType $type */
	public function __construct(string $name, string $type = 'string')
	{
		if (Val::isBlank($name)) {
			throw new InvalidArgumentException('Option name must not be blank.');
		}

		$this->name = $name;
		$this->type = $type;
	}

	public function getName(): string
	{
		return $this->name;
	}

	/**
	 * @param array|bool|float|int|string $value
	 * @phpstan-param ValueDefault $value
	 *
	 * @return static
	 */
	public function withDefault($value)
	{
		$self = clone $this;
		$self->default = $value;

		return $self;
	}

	/** @return static */
	public function withLabel(string $label)
	{
		$self = clone $this;
		$self->settingVars['label'] = $label;

		return $self;
	}

	/** @return static */
	public function withDescription(string $value)
	{
		$self = clone $this;
		$self->settingVars['description'] = $value;

		return $self;
	}

	/** @return static */
	public function withPriority(int $priority)
	{
		$self = clone $this;
		$self->priority = $priority;

		return $self;
	}

	/**
	 * Whether to show the option on WordPress REST API endpoint, `/wp/v2/settings`.
	 *
	 * @phpstan-param APISchema $schema
	 *
	 * @return static
	 */
	public function apiSchema(array $schema)
	{
		$self = clone $this;
		$self->settingVars['show_in_rest'] = [
			'name' => $this->name,
			'schema' => $schema,
		];

		return $self;
	}

	/** @phpstan-return ValueDefault */
	public function getDefault()
	{
		return $this->default;
	}

	public function getPriority(): int
	{
		return $this->priority;
	}

	/**
	 * Retrieve the arguments to pass for the `register_setting` function.
	 *
	 * @see https://developer.wordpress.org/reference/functions/register_setting/#parameters
	 *
	 * @phpstan-return SettingArgs
	 */
	public function getSettingArgs(): array
	{
		return array_merge([
			'type' => $this->type,
			'default' => $this->default,
		], $this->settingVars);
	}
}
