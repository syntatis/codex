<?php

declare(strict_types=1);

namespace Codex\Foundation\Hooks;

use Attribute;

#[Attribute(Attribute::TARGET_METHOD | Attribute::TARGET_CLASS | Attribute::IS_REPEATABLE)]
class Filter
{
	/**
	 * The WordPress hook name.
	 *
	 * @phpstan-var non-empty-string
	 */
	protected string $name;

	protected int $priority;

	protected int $acceptedArgs;

	/** @var array<string, mixed> */
	protected array $options;

	/**
	 * @param array<string, mixed> $options
	 * @phpstan-param non-empty-string $name
	 */
	public function __construct(
		string $name,
		int $priority = 10,
		int $acceptedArgs = 1,
		array $options = []
	) {
		$this->name = $name;
		$this->priority = $priority;
		$this->acceptedArgs = $acceptedArgs;
		$this->options = $options;
	}

	public function getName(): string
	{
		return $this->name;
	}

	public function getPriority(): int
	{
		return $this->priority;
	}

	public function getAcceptedArgs(): int
	{
		return $this->acceptedArgs;
	}

	/** @return array<string,mixed> */
	public function getOptions(): array
	{
		return $this->options;
	}
}
