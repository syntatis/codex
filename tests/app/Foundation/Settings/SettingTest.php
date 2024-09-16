<?php

declare(strict_types=1);

namespace Codex\Tests\Foundation\Settings;

use Codex\Foundation\Settings\Setting;
use Codex\Tests\WPTestCase;
use InvalidArgumentException;

class SettingTest extends WPTestCase
{
	public function testBlankName(): void
	{
		$this->expectException(InvalidArgumentException::class);

		new Setting('');
	}

	public function testName(): void
	{
		$this->assertSame('say', (new Setting('say'))->getName());
	}

	public function testPriority(): void
	{
		$this->assertSame(73, (new Setting('say'))->getPriority());
	}
}
