<?php

declare(strict_types=1);

namespace Codex\Foundation\Hooks\Support;

use Codex\Contracts\Hookable;
use Codex\Foundation\Hooks\Action;
use Codex\Foundation\Hooks\Filter;
use Codex\Foundation\Hooks\Hook;
use ReflectionClass;

use function is_callable;
use function strlen;
use function strncmp;

/** @internal */
final class Parser implements Hookable
{
	private Hook $hook;

	private object $obj;

	// @phpstan-ignore-next-line
	private ReflectionClass $ref;

	public function __construct(object $obj)
	{
		$this->obj = $obj;
		$this->ref = new ReflectionClass($this->obj);
	}

	public function hook(Hook $hook): void
	{
		$this->hook = $hook;
	}

	public function parse(): void
	{
		$this->parseClassAttrs();
		$this->parseMethodAttrs();
	}

	private function parseClassAttrs(): void
	{
		/**
		 * A callable object is a class with an __invoke method.
		 */
		if (! is_callable($this->obj)) {
			return;
		}

		$actions = $this->ref->getAttributes(Action::class);
		$filters = $this->ref->getAttributes(Filter::class);

		foreach ($actions as $action) {
			$instance = $action->newInstance();

			$this->hook->addAction(
				$instance->getName(),
				$this->obj,
				$instance->getPriority(),
				$instance->getAcceptedArgs(),
				$instance->getOptions(),
			);
		}

		foreach ($filters as $filter) {
			$instance = $filter->newInstance();

			$this->hook->addFilter(
				$instance->getName(),
				$this->obj,
				$instance->getPriority(),
				$instance->getAcceptedArgs(),
				$instance->getOptions(),
			);
		}
	}

	private function parseMethodAttrs(): void
	{
		$methods = $this->ref->getMethods();

		foreach ($methods as $method) {
			if (! $method->isPublic() || $method->isAbstract()) {
				continue;
			}

			if (
				$method->isConstructor() ||
				$method->isDestructor() ||
				// Starts with __
				strncmp($method->getName(), '__', strlen('__')) === 0
			) {
				continue;
			}

			$actions = $method->getAttributes(Action::class);
			$filters = $method->getAttributes(Filter::class);

			foreach ($actions as $action) {
				$instance = $action->newInstance();

				$this->hook->addAction(
					$instance->getName(),
					// @phpstan-ignore-next-line - $method->getName() is callable
					[$this->obj, $method->getName()],
					$instance->getPriority(),
					$instance->getAcceptedArgs(),
					$instance->getOptions(),
				);
			}

			foreach ($filters as $filter) {
				$instance = $filter->newInstance();

				$this->hook->addFilter(
					$instance->getName(),
					// @phpstan-ignore-next-line - $method->getName() is callable
					[$this->obj, $method->getName()],
					$instance->getPriority(),
					$instance->getAcceptedArgs(),
					$instance->getOptions(),
				);
			}
		}
	}
}
