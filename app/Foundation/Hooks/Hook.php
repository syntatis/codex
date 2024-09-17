<?php

declare(strict_types=1);

namespace Codex\Foundation\Hooks;

use Closure;
use Codex\Foundation\Hooks\Exceptions\RefExistsException;
use Codex\Foundation\Hooks\Support\Parser;
use InvalidArgumentException;

use function count;
use function get_class;
use function gettype;
use function is_array;
use function is_callable;
use function is_string;
use function preg_match;
use function spl_object_hash;
use function trim;

/**
 * This class manages the registration of all actions and filters for the plugin.
 *
 * It maintains a list of all hooks to be registered with the WordPress API.
 * Call the `register` method to execute the registration of these actions
 * and filters.
 */
final class Hook
{
	/** @var array<string,array{callback:callable}> */
	private array $refs = [];

	/**
	 * Holds aliases to refs.
	 *
	 * @var array<string,string>
	 */
	private array $aliases = [];

	/**
	 * The array of actions registered with WordPress.
	 *
	 * @var array<array{tag:string,callback:callable,priority:int,accepted_args:int}>
	 */
	private array $actions = [];

	/**
	 * The array of filters registered with WordPress.
	 *
	 * @var array<array{tag:string,callback:callable,priority:int,accepted_args:int}>
	 */
	private array $filters = [];

	/**
	 * Add a new action to the collection to be registered with WordPress.
	 *
	 * @param string               $tag          The name of the WordPress action that is being registered.
	 * @param callable             $callback     The name of the function to be called with Action hook.
	 * @param int                  $priority     Optional. The priority at which the function should be fired. Default is 10.
	 * @param int                  $acceptedArgs Optional. The number of arguments that should be passed to the $callback. Default is 1.
	 * @param array<string, mixed> $options      Optional. Additional options for the action.
	 */
	public function addAction(
		string $tag,
		callable $callback,
		int $priority = 10,
		int $acceptedArgs = 1,
		array $options = []
	): void {
		add_action($tag, $callback, $priority, $acceptedArgs);

		$nativeId = $this->getNativeId($callback);
		$namedId = $this->getNamedId($options) ?? $nativeId;

		$this->addRef($namedId, $nativeId, ['callback' => $callback]);

		$this->actions = $this->add($this->actions, $tag, $callback, $priority, $acceptedArgs);
	}

	/**
	 * Add a new filter to the collection to be registered with WordPress.
	 *
	 * @param string               $tag          The name of the WordPress filter that is being registered.
	 * @param callable             $callback     The name of the function to be called with Filter hook.
	 * @param int                  $priority     Optional. The priority at which the function should be fired. Default is 10.
	 * @param int                  $acceptedArgs Optional. The number of arguments that should be passed to the $callback. Default is 1.
	 * @param array<string, mixed> $options      Optional. Additional options for the action.
	 */
	public function addFilter(
		string $tag,
		callable $callback,
		int $priority = 10,
		int $acceptedArgs = 1,
		array $options = []
	): void {
		add_filter($tag, $callback, $priority, $acceptedArgs);

		$nativeId = $this->getNativeId($callback);
		$namedId = $this->getNamedId($options) ?? $nativeId;

		$this->addRef($namedId, $nativeId, ['callback' => $callback]);

		$this->filters = $this->add($this->filters, $tag, $callback, $priority, $acceptedArgs);
	}

	/**
	 * Removes an action callback function from a specified hook.
	 *
	 * @param string          $tag      The name of the action hook to remove the callback from.
	 * @param string|callable $ref      The callback or ref id to remove from the action hook.
	 * @param int             $priority Optional. The priority of the callback function. Default is 10.
	 */
	public function removeAction(string $tag, $ref, int $priority = 10): void
	{
		$callback = is_string($ref) ? $this->getCallback($ref) : $ref;

		if (! is_callable($callback)) {
			return;
		}

		remove_action($tag, $callback, $priority);
	}

	/**
	 * Removes a filter callback function from a specified hook.
	 *
	 * @param string          $tag      The name of the filter hook to remove the callback from.
	 * @param string|callable $ref      The callback or ref id to remove from the filter hook.
	 * @param int             $priority Optional. The priority of the callback function. Default is 10.
	 */
	public function removeFilter(string $tag, $ref, int $priority = 10): void
	{
		$callback = $this->getCallback($ref);

		if (! is_callable($callback)) {
			return;
		}

		remove_filter($tag, $callback, $priority);
	}

	/**
	 * Whether the action hook has the specified callback.
	 *
	 * @param string          $tag      The name of the action hook to remove the callback from.
	 * @param string|callable $ref      The callback or ref id to remove from the filter hook.
	 * @param int             $priority Optional. The priority of the callback function. Default is `10`.
	 *
	 * @return bool|int If registered, it returns the priority of the callback. Otherwise, it returns false.
	 */
	public function hasAction(string $tag, $ref, int $priority = 10)
	{
		$callback = $this->getCallback($ref);

		if (! is_callable($callback)) {
			return false;
		}

		return has_action($tag, $callback, $priority);
	}

	/**
	 * Whether the filter hook has the specified callback.
	 *
	 * @param string          $tag      The name of the filter hook to remove the callback from.
	 * @param string|callable $ref      The callback or ref id to remove from the filter hook.
	 * @param int             $priority Optional. The priority of the callback function. Default is `10`.
	 *
	 * @return bool|int If registered, it returns the priority of the callback. Otherwise, it returns false.
	 */
	public function hasFilter(string $tag, $ref, int $priority = 10)
	{
		$callback = $this->getCallback($ref);

		if (! is_callable($callback)) {
			return false;
		}

		return has_filter($tag, $callback, $priority);
	}

	/**
	 * Remove all actions and filters from WordPress.
	 */
	public function removeAll(): void
	{
		foreach ($this->actions as $hook) {
			remove_action($hook['tag'], $hook['callback'], $hook['priority']);
		}

		foreach ($this->filters as $hook) {
			remove_filter($hook['tag'], $hook['callback'], $hook['priority']);
		}
	}

	/**
	 * Parse and register hooks annotated with attributes in the given object.
	 *
	 * @param object $obj The object containing annotated hooks.
	 */
	public function parse(object $obj): void
	{
		$parser = new Parser($obj);
		$parser->hook($this);
		$parser->parse();
	}

	/**
	 * Add a new hook (action or filter) to the collection.
	 *
	 * @param array<array{tag:string,callback:callable,priority:int,accepted_args:int}> $hooks        The current collection of hooks.
	 * @param string                                                                    $tag          The name of the hook being registered.
	 * @param callable                                                                  $callback     The function to be called when the hook is triggered.
	 * @param int                                                                       $priority     The priority at which the function should be fired.
	 * @param int                                                                       $acceptedArgs The number of arguments that should be passed to the callback.
	 *
	 * @return array<array{tag:string,callback:callable,priority:int,accepted_args:int}>
	 */
	private function add(array $hooks, string $tag, callable $callback, int $priority, int $acceptedArgs): array
	{
		$hooks[] = [
			'accepted_args' => $acceptedArgs,
			'callback' => $callback,
			'tag' => $tag,
			'priority' => $priority,
		];

		return $hooks;
	}

	/** @param array{callback:callable} $entry */
	private function addRef(string $id, string $nativeId, array $entry): void
	{
		if ($nativeId !== $id) {
			$atId = '@' . $id;

			if (isset($this->refs[$atId])) {
				throw new RefExistsException($atId);
			}

			$this->refs[$atId] = $entry;
			$this->aliases[$nativeId] = $atId;
		} else {
			$this->refs[$nativeId] = [
				'callback' => $entry['callback'],
			];
		}
	}

	/** @param array<string, mixed> $options */
	private function getNamedId(array $options = []): ?string
	{
		if (isset($options['id']) && is_string($options['id']) && trim($options['id']) !== '') {
			preg_match('/^[a-z0-9]([_.-]?[a-z0-9]+)*(\/[a-z0-9](([_.]|-{1,2})?[a-z0-9]+)*)?$/', $options['id'], $matches);

			if (count($matches) === 0) {
				throw new InvalidArgumentException(
					'Invalid ref ID format. A ref ID should only contains letters, numbers, hyphens, dots, underscores, and backslashes.',
				);
			}

			return $options['id'];
		}

		return null;
	}

	private function getNativeId(callable $callback): string
	{
		if (gettype($callback) === 'string') {
			return $callback;
		}

		if (is_array($callback)) {
			return get_class($callback[0]) . '::' . $callback[1];
		}

		return spl_object_hash(Closure::fromCallable($callback));
	}

	/** @param string|callable $ref The callback or ref to remove from the action hook. */
	private function getCallback($ref): ?callable
	{
		if (is_string($ref)) {
			if (isset($this->aliases[$ref])) {
				return $this->refs[$this->aliases[$ref]]['callback'];
			}

			if (isset($this->refs[$ref])) {
				return $this->refs[$ref]['callback'];
			}
		}

		if (is_callable($ref)) {
			return $ref;
		}

		return null;
	}
}
