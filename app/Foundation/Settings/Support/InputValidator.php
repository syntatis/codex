<?php

declare(strict_types=1);

namespace Codex\Foundation\Settings\Support;

use Codex\Foundation\Settings\Setting;
use InvalidArgumentException;
use Syntatis\Utils\Val;

use function is_callable;
use function is_string;
use function sprintf;

/** @phpstan-import-type Constraints from Setting */
class InputValidator
{
	private string $optionName;

	/** @phpstan-var array<Constraints> */
	private array $constraints = [];

	public function __construct(string $optionName)
	{
		$this->optionName = $optionName;
	}

	/** @phpstan-param array<Constraints> $constraints */
	public function setConstraints(array $constraints): self
	{
		$this->constraints = $constraints;

		return $this;
	}

	/** @param mixed $value */
	public function validate($value): void
	{
		if ($this->constraints === []) {
			return;
		}

		foreach ($this->constraints as $constraint) {
			if (! is_callable($constraint)) {
				continue;
			}

			$result = $constraint($value);

			if (is_string($result) && ! Val::isBlank($result)) {
				throw new InvalidArgumentException(
					sprintf('[%s] %s', $this->optionName, $result),
				);
			}

			if ($result === false) {
				throw new InvalidArgumentException(
					sprintf('[%s] Invalid value.', $this->optionName),
				);
			}
		}
	}
}
