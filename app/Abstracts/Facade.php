<?php

declare(strict_types=1);

namespace Codex\Abstracts;

use Psr\Container\ContainerInterface;

abstract class Facade
{
	/** @codeCoverageIgnore */
	final private function __construct()
	{
	}

	protected static ContainerInterface $container;

	/** @var array<string, mixed> */
	protected static array $resolvedInstance = [];

	abstract protected static function getFacadeAccessor(): string;

	public static function setFacadeApplication(ContainerInterface $container): void
	{
		static::$container = $container;
	}

	public static function clearResolvedInstances(): void
	{
		static::$resolvedInstance = [];
	}

	public static function swap(object $instance): void
	{
		$key = static::getFacadeAccessor();

		static::$resolvedInstance[$key] = $instance;
	}

	public static function reset(): void
	{
		$key = static::getFacadeAccessor();

		static::$resolvedInstance[$key] = static::$container->get($key);
	}

	/**
	 * @param mixed $args
	 *
	 * @return mixed
	 */
	public static function __callStatic(string $method, $args)
	{
		$key = static::getFacadeAccessor();

		if (! isset(static::$resolvedInstance[$key])) {
			static::$resolvedInstance[$key] = static::$container->get($key);
		}

		$instance = static::$resolvedInstance[$key];

		// @phpstan-ignore-next-line Let it throw an error.
		return $instance->$method(...$args);
	}
}
