<?php

declare(strict_types=1);

namespace Codex\Core;

final class App
{
	private string $name;

	private Config $config;

	public function __construct(string $name, Config $config)
	{
		$this->name = $name;
		$this->config = $config;
	}

	public function name(): string
	{
		return $this->name;
	}

	public function config(): Config
	{
		return $this->config;
	}
}
